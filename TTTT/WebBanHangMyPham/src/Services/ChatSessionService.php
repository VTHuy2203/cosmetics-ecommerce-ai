<?php
declare(strict_types=1);

function chat_session_pdo(): PDO
{
    return app_pdo();
}

function chat_session_user_id(PDO $pdo): int
{
    foreach (['IDNguoiDung', 'user_id'] as $key) {
        if (!empty($_SESSION[$key])) {
            return (int) $_SESSION[$key];
        }
    }

    if (!empty($_SESSION['user'])) {
        $statement = $pdo->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
        $statement->execute([(string) $_SESSION['user']]);
        return (int) ($statement->fetchColumn() ?: 0);
    }

    return 0;
}

function chat_session_id_is_valid(mixed $sessionId): bool
{
    return is_string($sessionId)
        && preg_match('/^[A-Za-z0-9_-]{8,128}$/', $sessionId) === 1;
}

function chat_session_generate_id(int $userId): string
{
    return ($userId > 0 ? 'u' . $userId : 'g') . '_' . bin2hex(random_bytes(18));
}

function chat_session_create(PDO $pdo, int $userId): string
{
    do {
        $sessionId = chat_session_generate_id($userId);
        try {
            $statement = $pdo->prepare(
                'INSERT INTO chat_sessions (session_id, user_id) VALUES (?, ?)'
            );
            $statement->execute([$sessionId, $userId > 0 ? $userId : null]);
            break;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
        }
    } while (true);

    $_SESSION['chat_current_session_id'] = $sessionId;
    return $sessionId;
}

function chat_session_is_owned(PDO $pdo, string $sessionId, int $userId): bool
{
    $statement = $pdo->prepare(
        $userId > 0
            ? 'SELECT 1 FROM chat_sessions WHERE session_id = ? AND user_id = ? LIMIT 1'
            : 'SELECT 1 FROM chat_sessions WHERE session_id = ? AND user_id IS NULL LIMIT 1'
    );
    $statement->execute($userId > 0 ? [$sessionId, $userId] : [$sessionId]);
    return $statement->fetchColumn() !== false;
}

function chat_session_resolve(PDO $pdo, int $userId, mixed $requestedSessionId): string
{
    // Guests may only use the server-bound session id. Logged-in customers may
    // reopen one of their own sessions from the personal page.
    if ($userId > 0 && chat_session_id_is_valid($requestedSessionId)) {
        $requestedSessionId = (string) $requestedSessionId;
        if (chat_session_is_owned($pdo, $requestedSessionId, $userId)) {
            $_SESSION['chat_current_session_id'] = $requestedSessionId;
            return $requestedSessionId;
        }

        $statement = $pdo->prepare('SELECT 1 FROM chat_sessions WHERE session_id = ? LIMIT 1');
        $statement->execute([$requestedSessionId]);
        if ($statement->fetchColumn() === false) {
            $insert = $pdo->prepare('INSERT INTO chat_sessions (session_id, user_id) VALUES (?, ?)');
            $insert->execute([$requestedSessionId, $userId]);
            $_SESSION['chat_current_session_id'] = $requestedSessionId;
            return $requestedSessionId;
        }
    }

    $current = $_SESSION['chat_current_session_id'] ?? null;
    if (chat_session_id_is_valid($current) && chat_session_is_owned($pdo, (string) $current, $userId)) {
        return (string) $current;
    }

    return chat_session_create($pdo, $userId);
}

function chat_session_touch(PDO $pdo, string $sessionId, string $firstMessage = ''): void
{
    $title = trim(preg_replace('/\s+/u', ' ', $firstMessage) ?? '');
    if (mb_strlen($title) > 90) {
        $title = mb_substr($title, 0, 87) . '...';
    }

    $statement = $pdo->prepare(
        "UPDATE chat_sessions
         SET title = CASE WHEN (title IS NULL OR title = '') AND ? <> '' THEN ? ELSE title END,
             last_message_at = NOW(), updated_at = NOW()
         WHERE session_id = ?"
    );
    $statement->execute([$title, $title, $sessionId]);
}

/** @return list<array{role:string,content:string}> */
function chat_session_recent_history(PDO $pdo, string $sessionId, int $userId, string $currentMessage): array
{
    if (!chat_session_is_owned($pdo, $sessionId, $userId)) {
        return [];
    }
    $statement = $pdo->prepare(
        $userId > 0
            ? "SELECT direction,message FROM chat_messages WHERE session_id=? AND user_id=? ORDER BY id DESC LIMIT 7"
            : "SELECT direction,message FROM chat_messages WHERE session_id=? AND user_id IS NULL ORDER BY id DESC LIMIT 7"
    );
    $statement->execute($userId > 0 ? [$sessionId, $userId] : [$sessionId]);
    $rows = array_reverse($statement->fetchAll(PDO::FETCH_ASSOC));
    $history = [];
    foreach ($rows as $row) {
        $content = trim((string) ($row['message'] ?? ''));
        if ($content === '') {
            continue;
        }
        // The AI accepts 1000 chars per turn. Keep head and tail of long answers: the closing
        // line carries the follow-up offer ("Bạn có muốn mình so sánh 2 sản phẩm này không?").
        if (mb_strlen($content) > 1000) {
            $content = mb_substr($content, 0, 690) . ' … ' . mb_substr($content, -300);
        }
        $history[] = [
            'role' => ($row['direction'] ?? '') === 'bot' ? 'assistant' : 'user',
            'content' => $content,
        ];
    }
    $last = array_key_last($history);
    if ($last !== null && $history[$last]['role'] === 'user' && $history[$last]['content'] === $currentMessage) {
        array_pop($history);
    }
    return array_slice($history, -6);
}

function chat_session_delete(PDO $pdo, string $sessionId, int $userId): bool
{
    if (!chat_session_id_is_valid($sessionId) || !chat_session_is_owned($pdo, $sessionId, $userId)) {
        return false;
    }

    $deleteMessages = $pdo->prepare(
        $userId > 0
            ? 'DELETE FROM chat_messages WHERE user_id = ? AND session_id = ?'
            : 'DELETE FROM chat_messages WHERE user_id IS NULL AND session_id = ?'
    );
    $deleteMessages->execute($userId > 0 ? [$userId, $sessionId] : [$sessionId]);

    $deleteSession = $pdo->prepare(
        $userId > 0
            ? 'DELETE FROM chat_sessions WHERE session_id = ? AND user_id = ?'
            : 'DELETE FROM chat_sessions WHERE session_id = ? AND user_id IS NULL'
    );
    $deleteSession->execute($userId > 0 ? [$sessionId, $userId] : [$sessionId]);

    $deleted = $deleteSession->rowCount() > 0;
    if ($deleted && ($_SESSION['chat_current_session_id'] ?? null) === $sessionId) {
        unset($_SESSION['chat_current_session_id']);
    }

    return $deleted;
}

function chat_session_info(PDO $pdo, string $sessionId): array
{
    $statement = $pdo->prepare(
        "SELECT cs.session_id, cs.title, cs.created_at, cs.last_message_at,
                (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id = cs.session_id) AS message_count,
                (SELECT cm.message FROM chat_messages cm
                 WHERE cm.session_id = cs.session_id AND cm.direction = 'user'
                 ORDER BY cm.created_at ASC, cm.id ASC LIMIT 1) AS first_message
         FROM chat_sessions cs WHERE cs.session_id = ? LIMIT 1"
    );
    $statement->execute([$sessionId]);
    $session = $statement->fetch() ?: [];
    $title = trim((string) ($session['title'] ?? $session['first_message'] ?? ''));
    $title = trim(preg_replace('/\s+/u', ' ', $title) ?? '');
    if ($title === '') {
        $title = 'Cuộc trò chuyện mới';
    } elseif (mb_strlen($title) > 55) {
        $title = mb_substr($title, 0, 52) . '...';
    }

    return [
        'session_id' => $sessionId,
        'title' => $title,
        'created_at' => $session['created_at'] ?? null,
        'last_message_at' => $session['last_message_at'] ?? null,
        'message_count' => (int) ($session['message_count'] ?? 0),
    ];
}

function chat_session_upcoming_appointment(PDO $pdo, int $userId, string $sessionId): ?array
{
    if ($userId > 0) {
        $statement = $pdo->prepare(
            'SELECT id, appt_at, note FROM chat_appointments
             WHERE user_id = ? AND appt_at >= NOW()
             ORDER BY appt_at ASC LIMIT 1'
        );
        $statement->execute([$userId]);
    } else {
        $statement = $pdo->prepare(
            'SELECT id, appt_at, note FROM chat_appointments
             WHERE user_id IS NULL AND session_id = ? AND appt_at >= NOW()
             ORDER BY appt_at ASC LIMIT 1'
        );
        $statement->execute([$sessionId]);
    }

    $appointment = $statement->fetch();
    return is_array($appointment) ? $appointment : null;
}

/**
 * Move a browser's guest chat history into the account that just logged in on that browser
 * (like a cart merge): chat sessions, messages, appointments, analysis and behaviour events.
 * The guest profile and its guest score are removed; the customer is re-scored by the caller.
 *
 * @param list<string> $sessionIds chat/session ids the browser used before logging in
 * @return int number of guest sessions claimed
 */
function chat_session_claim_guest_history(PDO $pdo, int $userId, array $sessionIds): int
{
    $sessionIds = array_values(array_unique(array_filter($sessionIds, 'chat_session_id_is_valid')));
    if ($userId <= 0 || $sessionIds === []) {
        return 0;
    }

    $claimed = 0;
    foreach ($sessionIds as $sessionId) {
        $pdo->beginTransaction();
        try {
            // Never take over a session that already belongs to another account.
            $owner = $pdo->prepare('SELECT user_id FROM chat_sessions WHERE session_id = ? FOR UPDATE');
            $owner->execute([$sessionId]);
            $row = $owner->fetch();
            if ($row !== false && $row['user_id'] !== null && (int) $row['user_id'] !== $userId) {
                $pdo->rollBack();
                continue;
            }

            $moved = 0;
            foreach (['chat_sessions', 'chat_messages', 'chat_appointments', 'chat_message_labels', 'user_events'] as $table) {
                $update = $pdo->prepare("UPDATE {$table} SET user_id = ? WHERE session_id = ? AND user_id IS NULL");
                $update->execute([$userId, $sessionId]);
                $moved += $update->rowCount();
            }
            // uq_analysis_subject_session: skip if the account already has a row for this session.
            $analysis = $pdo->prepare('UPDATE IGNORE customer_analysis SET user_id = ? WHERE session_id = ? AND user_id IS NULL');
            $analysis->execute([$userId, $sessionId]);
            $moved += $analysis->rowCount();

            $pdo->prepare('DELETE FROM guest_customers WHERE session_id = ?')->execute([$sessionId]);
            $pdo->prepare('DELETE FROM customer_scores_current WHERE subject_key = ?')->execute(['session:' . $sessionId]);
            $pdo->commit();
            if ($moved > 0) {
                $claimed++;
            }
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Guest history claim failed: ' . $error->getMessage());
        }
    }

    return $claimed;
}
