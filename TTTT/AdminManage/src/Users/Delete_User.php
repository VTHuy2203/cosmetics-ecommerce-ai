<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once __DIR__ . '/../DB_Connect.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['admin_role'] ?? '', ['admin', 'manager'], true)) {
    json_response(['ok' => false, 'error' => 'Bạn không có quyền xóa khách hàng.'], 403);
}
require_csrf(true);

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId || $userId <= 0) {
    json_response(['ok' => false, 'error' => 'Mã khách hàng không hợp lệ.'], 422);
}

try {
    $customer = $conn->prepare('SELECT HoVaTen, Role FROM khachhang WHERE ID_KhachHang = ? LIMIT 1');
    $customer->bind_param('i', $userId);
    $customer->execute();
    $record = $customer->get_result()->fetch_assoc();
    if (!$record) {
        json_response(['ok' => false, 'error' => 'Không tìm thấy khách hàng.'], 404);
    }
    if (($record['Role'] ?? 'customer') !== 'customer' && $record['Role'] !== null) {
        json_response(['ok' => false, 'error' => 'Chức năng này chỉ được phép xóa tài khoản khách hàng.'], 403);
    }

    // Commerce records are audit evidence. Never cascade-delete a customer who has orders.
    $orders = $conn->prepare('SELECT COUNT(*) AS total FROM donhang WHERE ID_KhachHang = ?');
    $orders->bind_param('i', $userId);
    $orders->execute();
    if ((int) $orders->get_result()->fetch_assoc()['total'] > 0) {
        json_response([
            'ok' => false,
            'error' => 'Không thể xóa khách hàng đã có đơn hàng vì cần giữ lịch sử giao dịch và đối soát.',
        ], 409);
    }

    $conn->begin_transaction();
    $cleanup = [
        ['chat_messages', 'user_id'],
        ['chat_appointments', 'user_id'],
        ['chat_logs', 'user_id'],
        ['customer_analysis', 'user_id'],
        ['customer_notifications', 'user_id'],
        ['customer_weekly_stats', 'user_id'],
        ['customer_score_snapshots', 'user_id'],
        ['customer_scores_current', 'user_id'],
        ['user_events', 'user_id'],
        ['nap_tien', 'ID_KhachHang'],
    ];
    foreach ($cleanup as [$table, $column]) {
        $statement = $conn->prepare("DELETE FROM `{$table}` WHERE `{$column}` = ?");
        $statement->bind_param('i', $userId);
        $statement->execute();
    }

    $delete = $conn->prepare("DELETE FROM khachhang WHERE ID_KhachHang = ? AND (Role = 'customer' OR Role IS NULL)");
    $delete->bind_param('i', $userId);
    $delete->execute();
    if ($delete->affected_rows !== 1) {
        throw new RuntimeException('Không thể xóa khách hàng.');
    }
    $conn->commit();

    json_response(['ok' => true, 'message' => 'Đã xóa khách hàng chưa có giao dịch.']);
} catch (Throwable $error) {
    if ($conn->errno === 0 || $conn->sqlstate !== '00000') {
        try {
            $conn->rollback();
        } catch (Throwable) {
        }
    }
    error_log('Delete customer failed: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => app_is_production() ? 'Không thể xóa khách hàng.' : $error->getMessage()], 500);
}
