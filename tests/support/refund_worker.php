<?php
declare(strict_types=1);

require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__, 2) . '/TTTT/config/returns.php';

$requestId = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);
if (!$requestId) { exit(2); }
$db = new mysqli(
    (string) getenv('MYSQL_HOST'), (string) getenv('MYSQL_USER'),
    (string) getenv('MYSQL_PASSWORD'), (string) getenv('MYSQL_DB'),
    (int) getenv('MYSQL_PORT')
);
$db->set_charset('utf8mb4');
try {
    process_return_request($db, (int) $requestId, 'approve', 3, 'admin');
    echo 'approved';
} catch (DomainException) {
    echo 'rejected';
}
