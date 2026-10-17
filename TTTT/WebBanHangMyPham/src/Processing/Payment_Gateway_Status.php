<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (empty($_SESSION['user'])) {
    json_response(['success' => false, 'error' => 'unauthenticated'], 401);
}

$invoice = strtoupper(trim((string) ($_GET['invoice'] ?? '')));
if (!preg_match('/^[A-Z0-9_-]{6,100}$/', $invoice)) {
    json_response(['success' => false, 'error' => 'invalid_invoice'], 422);
}

$stmt = $conn->prepare(
    "SELECT d.ID_DonHang, d.TrangThaiThanhToan, d.ThanhToanLuc
     FROM donhang d
     INNER JOIN khachhang k ON k.ID_KhachHang = d.ID_KhachHang
     WHERE d.SePayInvoiceNumber = ? AND d.PhuongThucThanhToan = 'sepay_pg'
       AND k.TenDangNhap = ? LIMIT 1"
);
$stmt->bind_param('ss', $invoice, $_SESSION['user']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    json_response(['success' => false, 'error' => 'not_found'], 404);
}

json_response([
    'success' => true,
    'order_id' => (int) $order['ID_DonHang'],
    'payment_status' => (string) $order['TrangThaiThanhToan'],
    'paid_at' => $order['ThanhToanLuc'],
]);
