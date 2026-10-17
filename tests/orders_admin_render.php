<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/TTTT/AdminManage/src/Oders/All_Oders.php';
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

$pageDirectory = dirname(__DIR__) . '/TTTT/AdminManage/src/Oders';
chdir($pageDirectory);

ob_start();
include $pageDirectory . '/All_Oders.php';
$html = (string) ob_get_clean();

foreach (['orders-table', 'orders-count', 'Thông tin đơn', 'order-money'] as $expected) {
    if (!str_contains($html, $expected)) {
        fwrite(STDERR, "Orders admin page is missing: {$expected}\n");
        exit(1);
    }
}
foreach (['Order_Status_Action.php', 'js-order-status-form', 'js-order-payment-form'] as $expected) {
    if (!str_contains($html, $expected)) {
        fwrite(STDERR, "Orders AJAX action is missing: {$expected}\n");
        exit(1);
    }
}
if (str_contains($html, 'Deprecated:') || str_contains($html, 'Warning:')) {
    fwrite(STDERR, "Orders admin page emitted a PHP warning.\n");
    exit(1);
}

echo "orders_admin_render=ok\n";
