<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/TTTT/AdminManage/src/Notifications/Notification_Center.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';

$admin = $conn->query(
    "SELECT ID_KhachHang, Role, TenDangNhap FROM khachhang
     WHERE Role IN ('admin','manager') ORDER BY ID_KhachHang LIMIT 1"
)->fetch_assoc();
if (!$admin) {
    fwrite(STDERR, "Admin notification fixture is unavailable.\n");
    exit(1);
}
$_SESSION = [
    'admin_id' => (int) $admin['ID_KhachHang'],
    'admin_role' => (string) $admin['Role'],
    'admin_user' => (string) $admin['TenDangNhap'],
    'admin_is_admin' => true,
];

$pageDirectory = dirname(__DIR__) . '/TTTT/AdminManage/src/Notifications';
chdir($pageDirectory);
ob_start();
include $pageDirectory . '/Notification_Center.php';
$html = (string) ob_get_clean();

foreach (['Trung Tâm Thông Báo', 'Đánh dấu tất cả đã đọc', 'notification-item', 'admin-live-notifications'] as $expected) {
    if (!str_contains($html, $expected)) {
        fwrite(STDERR, "Admin notification center is missing: {$expected}\n");
        exit(1);
    }
}
if (str_contains($html, 'Deprecated:') || str_contains($html, 'Warning:')) {
    fwrite(STDERR, "Admin notification center emitted a PHP warning.\n");
    exit(1);
}
echo "admin_notification_center_render=ok\n";
