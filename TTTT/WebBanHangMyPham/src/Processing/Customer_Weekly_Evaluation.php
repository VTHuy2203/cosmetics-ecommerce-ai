<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/CustomerScoringService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$role = (string) ($_SESSION['admin_role'] ?? $_SESSION['user_role'] ?? '');
$hasBackOfficeSession = isset($_SESSION['admin_id'])
    || (isset($_SESSION['IDNguoiDung'], $_SESSION['user']) && $role === 'employee');
if (!$hasBackOfficeSession || !in_array($role, ['admin', 'manager', 'employee'], true)) {
    json_response(['ok' => false, 'error' => 'Vui lòng đăng nhập lại trang quản trị.'], 401);
}
require_csrf(true);

try {
    $pdo = app_pdo();
    $scoring = new CustomerScoringService($pdo);
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));

    $customers = $pdo->query(
        "SELECT ID_KhachHang, customer_label, discount_percent
         FROM khachhang WHERE Role = 'customer' OR Role IS NULL"
    )->fetchAll();

    $statsStatement = $pdo->prepare(
        "SELECT COUNT(*) total_orders, COALESCE(SUM(TongTien),0) total_amount
         FROM donhang
         WHERE ID_KhachHang=? AND NgayTao BETWEEN ? AND ? AND TrangThai=3 AND DaThanhToan=1"
    );
    $bookingStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM chat_appointments WHERE user_id=? AND created_at BETWEEN ? AND ?'
    );
    $chatStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM chat_messages WHERE user_id=? AND direction=\'user\' AND created_at BETWEEN ? AND ?'
    );
    $upsert = $pdo->prepare(
        "INSERT INTO customer_weekly_stats
            (user_id,week_start,week_end,total_orders,total_amount,has_booking,has_chat,customer_label,previous_label,discount_percent)
         VALUES (?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE total_orders=VALUES(total_orders),total_amount=VALUES(total_amount),
            has_booking=VALUES(has_booking),has_chat=VALUES(has_chat),customer_label=VALUES(customer_label),
            previous_label=VALUES(previous_label),discount_percent=VALUES(discount_percent),evaluated_at=NOW()"
    );
    $weeklyTotal = $pdo->prepare('UPDATE khachhang SET total_weekly_purchase=? WHERE ID_KhachHang=?');

    $updated = 0;
    foreach ($customers as $customer) {
        $userId = (int) $customer['ID_KhachHang'];
        $previousLabel = (string) ($customer['customer_label'] ?: 'normal');
        $from = $weekStart . ' 00:00:00';
        $to = $weekEnd . ' 23:59:59';

        $statsStatement->execute([$userId, $from, $to]);
        $order = $statsStatement->fetch() ?: ['total_orders' => 0, 'total_amount' => 0];
        $bookingStatement->execute([$userId, $from, $to]);
        $hasBooking = (int) $bookingStatement->fetchColumn() > 0;
        $chatStatement->execute([$userId, $from, $to]);
        $hasChat = (int) $chatStatement->fetchColumn() > 0;

        $score = $scoring->scoreSubject($userId, null);
        $upsert->execute([
            $userId,
            $weekStart,
            $weekEnd,
            (int) $order['total_orders'],
            (int) $order['total_amount'],
            $hasBooking ? 1 : 0,
            $hasChat ? 1 : 0,
            $score['segment'],
            $previousLabel,
            (int) $customer['discount_percent'],
        ]);
        $weeklyTotal->execute([(int) $order['total_amount'], $userId]);
        $updated++;
    }

    json_response([
        'ok' => true,
        'updated' => $updated,
        'notifications_sent' => 0,
        'week' => $weekStart . ' to ' . $weekEnd,
        'message' => 'Đã cập nhật thống kê và điểm. Ưu đãi không bị thay đổi tự động.',
    ]);
} catch (Throwable $error) {
    error_log('Weekly customer evaluation failed: ' . $error->getMessage());
    json_response([
        'ok' => false,
        'error' => app_is_production() ? 'Không thể cập nhật thống kê tuần.' : $error->getMessage(),
    ], 500);
}
