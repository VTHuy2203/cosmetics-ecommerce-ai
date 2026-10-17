<?php
declare(strict_types=1);

define('MOSHOP_SKIP_SESSION', true);
require_once __DIR__ . '/WebBanHangMyPham/src/DB_Connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $result = $conn->query('SELECT 1 AS ok');
    if (!$result || (int) $result->fetch_assoc()['ok'] !== 1) {
        throw new RuntimeException('database check failed');
    }
    echo json_encode(['status' => 'ok']);
} catch (Throwable $error) {
    error_log('Health check failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'unavailable']);
}

