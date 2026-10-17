<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/TTTT/AdminManage/src/Marketing/Manage_Marketing.php';
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
include dirname(__DIR__) . '/TTTT/AdminManage/src/Marketing/Manage_Marketing.php';
$html = (string) ob_get_clean();
if (!str_contains($html, 'Voucher &amp; thông báo') && !str_contains($html, 'Voucher & thông báo')) {
    fwrite(STDERR, "Marketing admin page did not render.\n");
    exit(1);
}
echo "marketing_admin_render=ok\n";
