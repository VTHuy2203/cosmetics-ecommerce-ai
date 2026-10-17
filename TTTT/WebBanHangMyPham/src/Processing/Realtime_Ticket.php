<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/config/realtime.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);
$raw = file_get_contents('php://input');
if (!is_string($raw) || strlen($raw) > 1024) {
    json_response(['ok' => false, 'error' => 'invalid_request'], 422);
}
try {
    $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    json_response(['ok' => false, 'error' => 'invalid_json'], 400);
}
$context = is_array($input) && is_string($input['context'] ?? null) ? $input['context'] : '';
if (!in_array($context, ['customer', 'admin'], true)) {
    json_response(['ok' => false, 'error' => 'invalid_context'], 422);
}
$secret = app_env('REALTIME_TICKET_SECRET', '') ?? '';
$origin = realtime_normalize_origin(app_env('APP_URL', '') ?? '');
if (strlen($secret) < 32 || $origin === null) {
    json_response(['ok' => false, 'error' => 'realtime_unavailable'], 503);
}

require_once __DIR__ . '/../DB_Connect.php';
$identity = ['sub' => 0, 'role' => 'guest', 'channel' => 'user_channel', 'context' => $context, 'origin' => $origin];
if ($context === 'admin') {
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    $username = is_string($_SESSION['admin_user'] ?? null) ? $_SESSION['admin_user'] : '';
    if ($id < 1 || $username === '') {
        json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
    $statement = $conn->prepare('SELECT Role FROM khachhang WHERE ID_KhachHang = ? AND TenDangNhap = ? LIMIT 1');
    $statement->bind_param('is', $id, $username);
    $statement->execute();
    $role = (string) ($statement->get_result()->fetch_assoc()['Role'] ?? '');
    $channels = ['admin' => 'admin_channel', 'manager' => 'admin_channel', 'employee' => 'staff_channel'];
    if (!isset($channels[$role])) {
        json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
    $identity = ['sub' => $id, 'role' => $role, 'channel' => $channels[$role], 'context' => 'admin', 'origin' => $origin];
} elseif (!empty($_SESSION['IDNguoiDung']) && is_string($_SESSION['user'] ?? null)) {
    $id = (int) $_SESSION['IDNguoiDung'];
    $username = $_SESSION['user'];
    $statement = $conn->prepare('SELECT Role FROM khachhang WHERE ID_KhachHang = ? AND TenDangNhap = ? LIMIT 1');
    $statement->bind_param('is', $id, $username);
    $statement->execute();
    $role = (string) ($statement->get_result()->fetch_assoc()['Role'] ?? '');
    if ($role === '') {
        json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
    $role = $role === 'shipper' ? 'shipper' : 'customer';
    $identity = ['sub' => $id, 'role' => $role, 'channel' => $role === 'shipper' ? 'shipper_channel' : 'user_channel', 'context' => 'customer', 'origin' => $origin];
}

try {
    $ticket = realtime_ticket_issue($identity, $secret, time(), 60);
    json_response(['ok' => true, 'ticket' => $ticket, 'expires_in' => 60]);
} catch (Throwable $error) {
    error_log('Realtime ticket issue failed; code=' . (int) $error->getCode());
    json_response(['ok' => false, 'error' => 'realtime_unavailable'], 503);
}
