<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Layouts/VietQR_Helper.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';

function vietqrCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$bankBin = (string) app_env('VIETQR_BANK_BIN', '');
$accountNumber = (string) app_env('VIETQR_ACCOUNT_NO', '');
$accountName = (string) app_env('VIETQR_ACCOUNT_NAME', '');
$description = normalizeVietQRDescription('Son Môi Đỏ x2, Sữa Rửa Mặt x1');

vietqrCheck($bankBin !== '', 'Missing bank BIN.');
vietqrCheck($accountNumber !== '', 'Missing bank account number.');
vietqrCheck($accountName !== '', 'Missing bank account name.');
vietqrCheck($description === 'Son Môi Đỏ x2, Sữa Rửa Mặt x1', 'Unicode transfer content was corrupted.');

$url = getVietQRUrl($accountNumber, $bankBin, 298000, $description);
parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
vietqrCheck((int) ($query['amount'] ?? 0) === 298000, 'VietQR amount is incorrect.');
vietqrCheck(($query['addInfo'] ?? '') === $description, 'VietQR transfer content is incorrect.');

$checkoutSource = file_get_contents(
    dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Processing/XuLy_DonHang_QR.php'
);
vietqrCheck(is_string($checkoutSource), 'Could not read QR checkout source.');
vietqrCheck(strpos($checkoutSource, 'SePayGatewayService') === false, 'QR checkout still depends on SePay.');
vietqrCheck(strpos($checkoutSource, 'TenSanPham') !== false, 'QR checkout does not use server product names.');
vietqrCheck(strpos($checkoutSource, 'SoLuong') !== false, 'QR checkout does not use server quantities.');

$detailStatement = $conn->prepare(
    'SELECT s.TenSanPham, d.SoLuong
     FROM donhangchitiet d
     INNER JOIN sanpham s ON s.IDSanPham = d.ID_SanPham
     WHERE d.ID_DonHang = ? LIMIT 1'
);
vietqrCheck($detailStatement instanceof mysqli_stmt, 'Order detail query is incompatible with the database schema.');

echo "vietqr_manual_smoke=ok\n";
