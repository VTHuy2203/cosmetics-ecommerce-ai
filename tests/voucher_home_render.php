<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/TTTT/WebBanHangMyPham/src/View/index.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';

$admin = $conn->query("SELECT ID_KhachHang FROM khachhang WHERE Role IN ('admin','manager') ORDER BY ID_KhachHang LIMIT 1")->fetch_assoc();
$customer = $conn->query("SELECT ID_KhachHang, TenDangNhap, Role FROM khachhang WHERE Role NOT IN ('admin','manager','employee','shipper') ORDER BY ID_KhachHang LIMIT 1")->fetch_assoc();
if (!$admin || !$customer) {
    fwrite(STDERR, "Voucher home fixtures are unavailable.\n");
    exit(1);
}

$voucherId = 0;
try {
    $code = 'HOME' . strtoupper(bin2hex(random_bytes(4)));
    $name = 'Voucher home render test';
    $description = 'Temporary voucher';
    $starts = date('Y-m-d H:i:s', time() - 60);
    $ends = date('Y-m-d H:i:s', time() + 3600);
    $adminId = (int) $admin['ID_KhachHang'];
    $insert = $conn->prepare(
        "INSERT INTO vouchers(code,name,description,discount_percent,quantity_total,per_user_limit,
         min_order_amount,starts_at,ends_at,status,announce_on_publish,published_at,created_by)
         VALUES (?,?,?,20,5,1,0,?,?,'active',0,NOW(),?)"
    );
    $insert->bind_param('sssssi', $code, $name, $description, $starts, $ends, $adminId);
    $insert->execute();
    $voucherId = (int) $conn->insert_id;

    $_SESSION = [
        'IDNguoiDung' => (int) $customer['ID_KhachHang'],
        'user' => (string) $customer['TenDangNhap'],
        'user_role' => (string) $customer['Role'],
    ];
    $pageDirectory = dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/View';
    $previousDirectory = getcwd();
    chdir($pageDirectory);
    ob_start();
    include $pageDirectory . '/index.php';
    $html = (string) ob_get_clean();
    chdir($previousDirectory ?: dirname(__DIR__));

    foreach (['Voucher dành cho bạn', $code, 'Nhận voucher', 'Voucher_Claim.php'] as $expected) {
        if (!str_contains($html, $expected)) {
            throw new RuntimeException('Home voucher section is missing: ' . $expected);
        }
    }
    echo "voucher_home_render=ok\n";
} finally {
    if ($voucherId > 0) {
        $conn->query('DELETE FROM vouchers WHERE id = ' . $voucherId);
    }
}
