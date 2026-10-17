<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';

/**
 * Split a migration into top-level SQL statements without breaking quoted
 * strings or comments. The migrations do not use stored-program DELIMITER
 * blocks, so a small state machine is safer than passing the whole file to
 * multi_query().
 *
 * @return list<string>
 */
function splitMigrationStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null;
    $lineComment = false;
    $blockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($lineComment) {
            $buffer .= $char;
            if ($char === "\n") {
                $lineComment = false;
            }
            continue;
        }

        if ($blockComment) {
            $buffer .= $char;
            if ($char === '*' && $next === '/') {
                $buffer .= $next;
                $i++;
                $blockComment = false;
            }
            continue;
        }

        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
                continue;
            }
            if ($char === $quote) {
                if (($quote === "'" || $quote === '"') && $next === $quote) {
                    $buffer .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if (($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) || $char === '#') {
            $lineComment = true;
            $buffer .= $char;
            continue;
        }
        if ($char === '/' && $next === '*') {
            $blockComment = true;
            $buffer .= $char . $next;
            $i++;
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === ';') {
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
}

/** @return list<string> */
function splitAlterClauses(string $clauses): array
{
    $parts = [];
    $buffer = '';
    $depth = 0;
    $quote = null;
    $length = strlen($clauses);

    for ($i = 0; $i < $length; $i++) {
        $char = $clauses[$i];
        $next = $i + 1 < $length ? $clauses[$i + 1] : '';

        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $i + 1 < $length) {
                $buffer .= $clauses[++$i];
                continue;
            }
            if ($char === $quote) {
                if (($quote === "'" || $quote === '"') && $next === $quote) {
                    $buffer .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
        } elseif ($char === '(') {
            $depth++;
        } elseif ($char === ')' && $depth > 0) {
            $depth--;
        } elseif ($char === ',' && $depth === 0) {
            $parts[] = trim($buffer);
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $parts[] = trim($buffer);
    }

    return $parts;
}

function stripLeadingSqlComments(string $statement): string
{
    do {
        $before = $statement;
        $statement = preg_replace('/^\s*(?:--[^\r\n]*(?:\r?\n|$)|#[^\r\n]*(?:\r?\n|$)|\/\*.*?\*\/)/s', '', $statement) ?? $statement;
    } while ($statement !== $before);

    return ltrim($statement);
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_row() !== null;
    $stmt->close();

    return $exists;
}

/**
 * MySQL does not implement MariaDB's ADD COLUMN IF NOT EXISTS syntax. Convert
 * that syntax into a regular ALTER after checking information_schema.
 */
function normalizeConditionalColumns(mysqli $conn, string $statement): ?string
{
    $sql = stripLeadingSqlComments($statement);
    if (!preg_match('/^ALTER\s+TABLE\s+(`?)([A-Za-z0-9_]+)\1\s+(.+)$/is', $sql, $match)) {
        return $statement;
    }
    if (stripos($match[3], 'ADD COLUMN IF NOT EXISTS') === false) {
        return $statement;
    }

    $table = $match[2];
    $kept = [];
    foreach (splitAlterClauses($match[3]) as $clause) {
        if (!preg_match('/^ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\s+(`?)([A-Za-z0-9_]+)\1\s+(.+)$/is', $clause, $columnMatch)) {
            $kept[] = $clause;
            continue;
        }
        if (!columnExists($conn, $table, $columnMatch[2])) {
            $kept[] = 'ADD COLUMN `' . $columnMatch[2] . '` ' . $columnMatch[3];
        }
    }

    if ($kept === []) {
        return null;
    }

    return 'ALTER TABLE `' . $table . '` ' . implode(",\n    ", $kept);
}

$conn->query('CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(190) NOT NULL PRIMARY KEY,
    checksum CHAR(64) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $version = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Cannot read migration: {$version}");
    }

    $checksum = hash('sha256', $sql);
    $stmt = $conn->prepare('SELECT checksum FROM schema_migrations WHERE version = ?');
    $stmt->bind_param('s', $version);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        if (!hash_equals($existing['checksum'], $checksum)) {
            throw new RuntimeException("Migration was modified after apply: {$version}");
        }
        echo "skip {$version}\n";
        continue;
    }

    if ($version === '20260816_0000_core_schema.sql') {
        // Legacy lead tables (leads, lead_events, lead_scores, customer_needs, user_intents,
        // user_actions) are dropped by 0019, so they no longer prove a complete legacy core.
        $coreTables = [
            'binhluan','chat_appointments','chat_bookings','chat_logs','chat_messages','chat_session_state',
            'consultations','danhmuc','danhsachyeuthich','donhang','donhangchitiet',
            'khachhang','nap_tien','sanpham','thuonghieu',
            'tintucbaiviet','users','user_events',
        ];
        $placeholders = implode(',', array_fill(0, count($coreTables), '?'));
        $types = str_repeat('s', count($coreTables));
        $tableCheck = $conn->prepare(
            "SELECT COUNT(*) AS present FROM information_schema.TABLES
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$placeholders})"
        );
        $tableCheck->bind_param($types, ...$coreTables);
        $tableCheck->execute();
        $present = (int) $tableCheck->get_result()->fetch_assoc()['present'];
        if ($present !== 0 && $present !== count($coreTables)) {
            throw new RuntimeException(
                "Core schema is partial ({$present}/" . count($coreTables) . ' tables); restore or reconcile before migration.'
            );
        }
        if ($present === count($coreTables)) {
            $stmt = $conn->prepare('INSERT INTO schema_migrations(version, checksum) VALUES (?, ?)');
            $stmt->bind_param('ss', $version, $checksum);
            $stmt->execute();
            echo "adopted {$version} (complete legacy core detected)\n";
            continue;
        }
    }

    foreach (splitMigrationStatements($sql) as $statement) {
        $statement = normalizeConditionalColumns($conn, $statement);
        if ($statement === null) {
            continue;
        }
        try {
            $result = $conn->query($statement);
            if ($result instanceof mysqli_result) {
                $result->free();
            }
        } catch (mysqli_sql_exception $exception) {
            throw new RuntimeException(
                "Migration failed {$version}: {$exception->getMessage()}",
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    $stmt = $conn->prepare('INSERT INTO schema_migrations(version, checksum) VALUES (?, ?)');
    $stmt->bind_param('ss', $version, $checksum);
    $stmt->execute();
    echo "applied {$version}\n";
}
