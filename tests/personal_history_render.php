<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/TTTT/WebBanHangMyPham/src/View/Personal.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';

$customer = $conn->query(
    "SELECT k.ID_KhachHang, k.TenDangNhap, k.HoVaTen, k.Role
     FROM khachhang k
     LEFT JOIN chat_sessions cs ON cs.user_id = k.ID_KhachHang
     WHERE k.Role NOT IN ('admin','manager','employee','shipper')
     GROUP BY k.ID_KhachHang, k.TenDangNhap, k.HoVaTen, k.Role
     ORDER BY COUNT(cs.id) DESC, k.ID_KhachHang LIMIT 1"
)->fetch_assoc();
if (!$customer) {
    fwrite(STDERR, "Customer fixture is unavailable.\n");
    exit(1);
}

$_SESSION = [
    'IDNguoiDung' => (int) $customer['ID_KhachHang'],
    'user_id' => (int) $customer['ID_KhachHang'],
    'user' => (string) $customer['TenDangNhap'],
    'HoTen' => (string) $customer['HoVaTen'],
    'user_role' => (string) $customer['Role'],
];

$pageDirectory = dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/View';
chdir($pageDirectory);
ob_start();
include $pageDirectory . '/Personal.php';
$html = (string) ob_get_clean();

foreach (['chat-history', 'Lịch sử trò chuyện', 'consultation-history', 'Lịch tư vấn', 'phiên kỹ thuật cũ', 'Mở chat', 'Xem lại'] as $expected) {
    if (!str_contains($html, $expected)) {
        fwrite(STDERR, "Personal page is missing: {$expected}\n");
        exit(1);
    }
}
if (str_contains($html, 'Warning:') || str_contains($html, 'Deprecated:')) {
    fwrite(STDERR, "Personal page emitted a PHP warning.\n");
    exit(1);
}

echo "personal_history_render=ok\n";
