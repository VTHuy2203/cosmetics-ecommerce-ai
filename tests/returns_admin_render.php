<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/TTTT/AdminManage/src/Oders/Admin_Return_Cancellation_Requests.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';

$admin = $conn->query(
    "SELECT ID_KhachHang, Role, TenDangNhap FROM khachhang
     WHERE Role IN ('admin','manager') ORDER BY ID_KhachHang LIMIT 1"
)->fetch_assoc();
if (!$admin) {
    fwrite(STDERR, "Admin fixture is unavailable.\n");
    exit(1);
}
$_SESSION = [
    'admin_id' => (int) $admin['ID_KhachHang'],
    'admin_role' => (string) $admin['Role'],
    'admin_user' => (string) $admin['TenDangNhap'],
    'admin_is_admin' => true,
];

ob_start();
include dirname(__DIR__) . '/TTTT/AdminManage/src/Oders/Admin_Return_Cancellation_Requests.php';
$html = (string) ob_get_clean();
foreach (['return-summary', 'return-request-card', 'Yêu cầu hoàn trả'] as $expected) {
    if (!str_contains($html, $expected)) {
        fwrite(STDERR, "Returns admin page is missing: {$expected}\n");
        exit(1);
    }
}
if (str_contains($html, 'Deprecated:') || str_contains($html, 'Warning:')) {
    fwrite(STDERR, "Returns admin page emitted a PHP warning.\n");
    exit(1);
}
echo "returns_admin_render=ok\n";
