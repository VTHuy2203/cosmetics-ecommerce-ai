<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/CheckoutService.php';
require_once dirname(__DIR__) . '/Layouts/VietQR_Helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);

if (empty($_SESSION['user']) || empty($_SESSION['cart'])) {
    json_response(['success' => false, 'error' => 'unauthenticated'], 401);
}

try {
    $bankBin = trim((string) app_env('VIETQR_BANK_BIN', '970422'));
    $accountNo = preg_replace('/\s+/', '', (string) app_env('VIETQR_ACCOUNT_NO', ''));
    $accountName = trim((string) app_env('VIETQR_ACCOUNT_NAME', ''));
    if ($bankBin === '' || $accountNo === '' || $accountName === '') {
        throw new RuntimeException('VietQR bank account is not configured.');
    }

    $service = new CheckoutService($conn);
    $order = $service->create(
        (string) $_SESSION['user'],
        $_SESSION['cart'],
        'qr',
        ['ShippingRegion' => $_POST['ShippingRegion'] ?? ''],
        (string) ($_POST['GhiChu'] ?? ''),
        isset($_SESSION['voucher_code']) ? (string) $_SESSION['voucher_code'] : null
    );

    $detailStatement = $conn->prepare(
        'SELECT s.TenSanPham, d.SoLuong
         FROM donhangchitiet d
         INNER JOIN sanpham s ON s.IDSanPham = d.ID_SanPham
         WHERE d.ID_DonHang = ? ORDER BY d.ID_SanPham'
    );
    $orderId = (int) $order['order_id'];
    $detailStatement->bind_param('i', $orderId);
    $detailStatement->execute();
    $parts = [];
    foreach ($detailStatement->get_result() as $detail) {
        $parts[] = trim((string) $detail['TenSanPham']) . ' x' . (int) $detail['SoLuong'];
    }
    $transferContent = normalizeVietQRDescription(implode(', ', $parts));
    if ($transferContent === '') {
        throw new RuntimeException('Could not build VietQR transfer content.');
    }

    $order['account_name'] = $accountName;
    $order['account_number'] = $accountNo;
    $order['transfer_content'] = $transferContent;
    $order['qr_url'] = getVietQRUrl($accountNo, $bankBin, (int) $order['amount'], $transferContent);

    unset($_SESSION['cart'], $_SESSION['sum_Total'], $_SESSION['voucher_code']);
    json_response(['success' => true, 'order' => $order], 201);
} catch (CheckoutException $error) {
    json_response(['success' => false, 'error' => $error->reason, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    error_log('QR checkout failed: ' . $error->getMessage());
    json_response(['success' => false, 'error' => 'checkout_failed'], 500);
}
