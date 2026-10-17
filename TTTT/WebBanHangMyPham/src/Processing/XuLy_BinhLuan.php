<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/config/security.php';
header('Content-Type: text/html; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); exit('method_not_allowed');
}
if (!is_string($_SESSION['user'] ?? null) || $_SESSION['user'] === '') {
    http_response_code(401); exit('login_required');
}
$csrf = $_POST['csrf_token'] ?? '';
if (!is_string($csrf) || !csrf_is_valid($csrf)) {
    http_response_code(403); exit('csrf_invalid');
}
$rawId = $_POST['IDSanPham'] ?? null;
$productId = is_scalar($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
$content = $_POST['NoiDungBinhLuan'] ?? null;
if (!$productId || $productId < 1 || !is_string($content)
    || !mb_check_encoding($content, 'UTF-8') || trim($content) === '' || mb_strlen($content, 'UTF-8') > 1000) {
    http_response_code(422); exit('invalid_comment');
}
require_once __DIR__ . '/../DB_Connect.php';
try {
    $customer = $conn->prepare('SELECT ID_KhachHang, HoVaTen FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
    $customer->bind_param('s', $_SESSION['user']);
    $customer->execute();
    $user = $customer->get_result()->fetch_assoc();
    if (!$user) { http_response_code(401); exit('login_required'); }
    $userId = (int) $user['ID_KhachHang'];
    // Preserve the existing rule: received purchase required; any approved
    // return for this product among those orders prevents a new comment.
    $orders = $conn->prepare(
        "SELECT d.GhiChu, EXISTS (
            SELECT 1 FROM return_refund_requests r
            WHERE r.ID_DonHang = d.ID_DonHang AND r.ID_KhachHang = d.ID_KhachHang
              AND r.ID_SanPham = ? AND r.TrangThai = 'approved'
         ) AS has_return
         FROM donhang d INNER JOIN donhangchitiet dc ON dc.ID_DonHang = d.ID_DonHang
         WHERE d.ID_KhachHang = ? AND dc.ID_SanPham = ? AND d.TrangThai = 3"
    );
    $orders->bind_param('iii', $productId, $userId, $productId);
    $orders->execute();
    $purchases = $orders->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($purchases === []) { http_response_code(403); exit('not_verified_purchase'); }
    foreach ($purchases as $purchase) {
        if ((int) $purchase['has_return'] === 1 || stripos((string) $purchase['GhiChu'], '[ĐÃ HOÀN TRẢ]') !== false) {
            http_response_code(403); exit('return_approved');
        }
    }
    $insert = $conn->prepare('INSERT INTO binhluan (ID_KhachHang, ID_SanPham, NoiDungBinhLuan, DaMuaHang) VALUES (?, ?, ?, 1)');
    $insert->bind_param('iis', $userId, $productId, $content);
    $insert->execute();
    $nameHtml = htmlspecialchars((string) $user['HoVaTen'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $contentHtml = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">'
        . '<h6 class="card-title">' . $nameHtml . ' <span class="text-success"><i class="fa-solid fa-circle-check"></i> Đã mua hàng</span></h6>'
        . '<p class="card-text mx-2 fs-6">' . $contentHtml . '</p></div>';
} catch (Throwable $error) {
    error_log('Comment creation failed; code=' . (int) $error->getCode());
    http_response_code(500); echo 'error';
}
