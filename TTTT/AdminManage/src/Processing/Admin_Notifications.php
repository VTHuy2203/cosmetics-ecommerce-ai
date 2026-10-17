<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Phiên làm việc đã hết hạn.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    $ids = array_slice($ids, 0, 50);
    if ($ids !== []) {
        $idList = implode(',', $ids);
        $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE id IN ({$idList})");
    }
    echo json_encode(['ok' => true, 'updated' => count($ids)], JSON_UNESCAPED_UNICODE);
    exit;
}

$afterId = max(0, (int) ($_GET['after_id'] ?? 0));
$recentOnly = ($_GET['recent'] ?? '') === '1';

try {
    if ($afterId === 0 && $recentOnly) {
        $statement = $conn->prepare(
            'SELECT id, type, message, order_id, created_at
             FROM admin_notifications
             WHERE is_read = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
             ORDER BY id ASC LIMIT 20'
        );
    } else {
        $statement = $conn->prepare(
            'SELECT id, type, message, order_id, created_at
             FROM admin_notifications
             WHERE id > ? AND is_read = 0 ORDER BY id ASC LIMIT 20'
        );
        $statement->bind_param('i', $afterId);
    }
    $statement->execute();
    $items = [];
    foreach ($statement->get_result() as $row) {
        $items[] = [
            'id' => (int) $row['id'],
            'type' => (string) $row['type'],
            'title' => match ((string) $row['type']) {
                'order.created' => 'Có đơn hàng mới',
                'appointment.created' => 'Lịch hẹn tư vấn mới',
                default => 'Thông báo quản trị',
            },
            'message' => (string) $row['message'],
            'order_id' => $row['order_id'] === null ? null : (int) $row['order_id'],
            'created_at' => (string) $row['created_at'],
        ];
    }
    $unread = $conn->query('SELECT COUNT(*) AS total FROM admin_notifications WHERE is_read = 0')->fetch_assoc();
    $latest = $conn->query('SELECT COALESCE(MAX(id), 0) AS latest_id FROM admin_notifications')->fetch_assoc();
    echo json_encode([
        'ok' => true,
        'items' => $items,
        'unread' => (int) ($unread['total'] ?? 0),
        'latest_id' => (int) ($latest['latest_id'] ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Admin notification polling failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'items' => [], 'message' => 'Không thể tải thông báo.'], JSON_UNESCAPED_UNICODE);
}
