<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/CheckoutService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('error');
}
require_csrf();

if (empty($_SESSION['cart'])) {
    http_response_code(401);
    exit('error');
}
$isGuest = empty($_SESSION['user']);
// Guest orders are COD only and need no account, so throttle them per client address.
if ($isGuest && !auth_rate_limit('guest-checkout:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 3600)) {
    http_response_code(429);
    exit('error:Bạn đã đặt quá nhiều đơn trong thời gian ngắn. Vui lòng thử lại sau hoặc đăng nhập.');
}
try {
    $service = new CheckoutService($conn);
    $username = $isGuest
        ? $service->ensureGuestCustomer(
            (string) ($_POST['HoVaTen'] ?? ''),
            (string) ($_POST['Sdt'] ?? ''),
            (string) ($_POST['DiaChi'] ?? '')
        )
        : (string) $_SESSION['user'];
    $order = $service->create(
        $username,
        $_SESSION['cart'],
        'cod',
        [
            'HoVaTen' => $_POST['HoVaTen'] ?? '',
            'Email' => $_POST['Email'] ?? '',
            'Sdt' => $_POST['Sdt'] ?? '',
            'DiaChi' => $_POST['DiaChi'] ?? '',
            'ShippingRegion' => $_POST['ShippingRegion'] ?? '',
        ],
        (string) ($_POST['GhiChu'] ?? ''),
        !$isGuest && isset($_SESSION['voucher_code']) ? (string) $_SESSION['voucher_code'] : null
    );
    unset($_SESSION['cart'], $_SESSION['sum_Total'], $_SESSION['voucher_code']);
    // Guests cannot open Order_Tracking: give them the order number to quote when staff call.
    echo $isGuest ? 'success:' . (int) $order['order_id'] : 'success';
} catch (CheckoutException $error) {
    http_response_code(422);
    echo $error->reason === 'insufficient_stock' ? 'stock_error:' . $error->getMessage() : 'error:' . $error->getMessage();
} catch (Throwable $error) {
    error_log('COD checkout failed: ' . $error->getMessage());
    http_response_code(500);
    echo 'error';
}
