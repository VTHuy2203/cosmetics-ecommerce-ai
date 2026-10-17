<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !isset($_SESSION['user'])) {
    json_response(['success' => false], 401);
}

$depositId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$statement = $conn->prepare(
    'SELECT n.TrangThai, n.HanThanhToan, n.ThanhToanLuc
     FROM nap_tien n
     INNER JOIN khachhang k ON k.ID_KhachHang = n.ID_KhachHang
     WHERE n.ID_Nap = ? AND k.TenDangNhap = ? LIMIT 1'
);
$username = (string) $_SESSION['user'];
$statement->bind_param('is', $depositId, $username);
$statement->execute();
$deposit = $statement->get_result()->fetch_assoc();
if (!$deposit) {
    json_response(['success' => false], 404);
}

$status = match ((int) $deposit['TrangThai']) {
    1 => 'paid',
    2 => 'expired',
    default => ($deposit['HanThanhToan'] && strtotime($deposit['HanThanhToan']) < time()) ? 'expired' : 'pending',
};
json_response(['success' => true, 'status' => $status]);
