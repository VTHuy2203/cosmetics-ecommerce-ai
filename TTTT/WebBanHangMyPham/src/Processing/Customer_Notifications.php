<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

$userId = (int) ($_SESSION['IDNguoiDung'] ?? 0);
if ($userId < 1) {
    json_response(['ok' => false, 'error' => 'unauthenticated'], 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $unreadOnly = ($_GET['unread_only'] ?? '') === '1';
    $sql = 'SELECT id, type, title, message, is_read, created_at
            FROM customer_notifications WHERE user_id = ?';
    if ($unreadOnly) {
        $sql .= ' AND is_read = 0';
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 50';
    $statement = $conn->prepare($sql);
    $statement->bind_param('i', $userId);
    $statement->execute();
    $notifications = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

    $count = $conn->prepare('SELECT COUNT(*) AS count FROM customer_notifications WHERE user_id = ? AND is_read = 0');
    $count->bind_param('i', $userId);
    $count->execute();
    $unreadCount = (int) $count->get_result()->fetch_assoc()['count'];

    $status = $conn->prepare(
        'SELECT customer_label, customer_value_tier, total_weekly_purchase FROM khachhang WHERE ID_KhachHang = ?'
    );
    $status->bind_param('i', $userId);
    $status->execute();

    json_response([
        'ok' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'customer_status' => $status->get_result()->fetch_assoc() ?: null,
    ]);
}

if ($method !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);

$raw = file_get_contents('php://input');
$input = is_string($raw) && strlen($raw) <= 4096 ? json_decode($raw, true) : null;
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'invalid_json'], 400);
}

if (($input['mark_all_read'] ?? false) === true) {
    $statement = $conn->prepare('UPDATE customer_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $statement->bind_param('i', $userId);
} else {
    $notificationId = (int) ($input['notification_id'] ?? 0);
    if ($notificationId < 1) {
        json_response(['ok' => false, 'error' => 'invalid_notification'], 422);
    }
    $statement = $conn->prepare(
        'UPDATE customer_notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND is_read = 0'
    );
    $statement->bind_param('ii', $notificationId, $userId);
}
$statement->execute();
json_response(['ok' => true, 'marked' => $statement->affected_rows]);
