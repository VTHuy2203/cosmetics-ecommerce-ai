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

if (empty($_SESSION['user']) || empty($_SESSION['cart'])) {
    http_response_code(401);
    exit('error');
}

try {
    $service = new CheckoutService($conn);
    $service->create(
        (string) $_SESSION['user'],
        $_SESSION['cart'],
        'balance',
        ['ShippingRegion' => $_POST['ShippingRegion'] ?? ''],
        '',
        isset($_SESSION['voucher_code']) ? (string) $_SESSION['voucher_code'] : null
    );
    unset($_SESSION['cart'], $_SESSION['sum_Total'], $_SESSION['voucher_code']);
    echo 'success';
} catch (CheckoutException $error) {
    http_response_code(422);
    $prefix = match ($error->reason) {
        'insufficient_balance' => 'insufficient_balance',
        'insufficient_stock', 'stock_race' => 'stock_error:',
        default => 'error:',
    };
    echo $prefix . ($prefix === 'insufficient_balance' ? '' : $error->getMessage());
} catch (Throwable $error) {
    error_log('Balance checkout failed: ' . $error->getMessage());
    http_response_code(500);
    echo 'error';
}
