<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';
require_once dirname(__DIR__, 3) . '/WebBanHangMyPham/src/Services/VoucherService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
require_csrf();

$adminRole = $_SESSION['admin_role'] ?? null;
if (!in_array($adminRole, ['admin', 'manager'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$view = (string) ($_POST['view'] ?? 'all');
$redirects = [
    'all' => 'All_Oders.php',
    'approve' => 'Approve_Oders.php',
    'prepare' => 'Prepare_Oders.php',
    'shipping' => 'Shipping_Oders.php',
    'success' => 'Success_Oders.php',
];
$redirect = $redirects[$view] ?? $redirects['all'];
if (!$orderId) {
    header('Location: ' . $redirect . '?error=invalid_order');
    exit;
}

$conn->begin_transaction();
try {
    $orderStmt = $conn->prepare(
        'SELECT ID_DonHang, ID_KhachHang, TongTien, TrangThai, DaThanhToan, TrangThaiThanhToan,
         PhuongThucThanhToan, TonKhoDaGiu, GhiChu FROM donhang WHERE ID_DonHang = ? FOR UPDATE'
    );
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    if (!$order || (int) $order['TrangThai'] === -1) {
        throw new RuntimeException('order_not_found_or_cancelled');
    }

    if ((int) $order['TonKhoDaGiu'] === 1) {
        $details = $conn->prepare('SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = ?');
        $details->bind_param('i', $orderId);
        $details->execute();
        $restore = $conn->prepare('UPDATE sanpham SET SoLuong = SoLuong + ? WHERE IDSanPham = ?');
        foreach ($details->get_result() as $detail) {
            $quantity = (int) $detail['SoLuong'];
            $productId = (int) $detail['ID_SanPham'];
            $restore->bind_param('ii', $quantity, $productId);
            $restore->execute();
        }
    }


    (new VoucherService($conn))->releaseForOrder((int) $orderId, 'admin_cancelled');

    $paymentStatus = (string) $order['TrangThaiThanhToan'];
    if ((int) $order['DaThanhToan'] === 1 && $paymentStatus !== 'refunded') {
        // Current business policy refunds paid cancellations to the internal wallet.
        $refund = $conn->prepare('UPDATE khachhang SET SoDu = SoDu + ? WHERE ID_KhachHang = ?');
        $amount = (int) $order['TongTien'];
        $customerId = (int) $order['ID_KhachHang'];
        $refund->bind_param('ii', $amount, $customerId);
        $refund->execute();
        $paymentStatus = 'refunded';
    } elseif ($paymentStatus === 'pending') {
        $paymentStatus = 'expired';
    }

    $note = mb_substr(trim((string) $order['GhiChu'] . ' | [ADMIN HỦY]'), 0, 255);
    $update = $conn->prepare(
        'UPDATE donhang SET TrangThai = -1, TrangThaiThanhToan = ?, TonKhoDaGiu = 0, GhiChu = ? WHERE ID_DonHang = ?'
    );
    $update->bind_param('ssi', $paymentStatus, $note, $orderId);
    $update->execute();

    $payload = json_encode(['order_id' => $orderId, 'actor_role' => $adminRole], JSON_UNESCAPED_SLASHES);
    $event = $conn->prepare(
        "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
         VALUES ('order.cancelled', 'order', ?, ?)"
    );
    $aggregateId = (string) $orderId;
    $event->bind_param('ss', $aggregateId, $payload);
    $event->execute();

    $conn->commit();
    header('Location: ' . $redirect . '?cancelled=' . $orderId);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Admin cancel order failed: ' . $error->getMessage());
    header('Location: ' . $redirect . '?error=cancel_failed');
}
exit;
