<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (empty($_SESSION['user'])) {
    json_response(['success' => false, 'error' => 'unauthenticated'], 401);
}

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
if (!preg_match('/^[A-Z0-9]{4,16}$/', $code)) {
    json_response(['success' => false, 'error' => 'invalid_code'], 422);
}

$conn->begin_transaction();
$stmt = $conn->prepare(
    'SELECT d.ID_DonHang, d.TrangThaiThanhToan, d.HanThanhToan, d.ThanhToanLuc, d.TonKhoDaGiu
     FROM donhang d
     INNER JOIN khachhang k ON k.ID_KhachHang = d.ID_KhachHang
     WHERE d.MaThanhToan = ? AND k.TenDangNhap = ? LIMIT 1 FOR UPDATE'
);
$stmt->bind_param('ss', $code, $_SESSION['user']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    $conn->rollback();
    json_response(['success' => false, 'error' => 'not_found'], 404);
}

if ($order['TrangThaiThanhToan'] === 'pending'
    && $order['HanThanhToan']
    && strtotime($order['HanThanhToan']) < time()) {
    $expire = $conn->prepare(
        "UPDATE donhang SET TrangThaiThanhToan = 'expired' WHERE ID_DonHang = ? AND TrangThaiThanhToan = 'pending'"
    );
    $orderId = (int) $order['ID_DonHang'];
    $expire->bind_param('i', $orderId);
    $expire->execute();
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
        $released = $conn->prepare('UPDATE donhang SET TonKhoDaGiu = 0 WHERE ID_DonHang = ?');
        $released->bind_param('i', $orderId);
        $released->execute();
    }
    $order['TrangThaiThanhToan'] = 'expired';
}
$conn->commit();

json_response([
    'success' => true,
    'order_id' => (int) $order['ID_DonHang'],
    'payment_status' => $order['TrangThaiThanhToan'],
    'paid_at' => $order['ThanhToanLuc'],
]);
