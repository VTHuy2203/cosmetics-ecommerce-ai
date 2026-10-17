<?php
declare(strict_types=1);

function gatewayCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_ENV['SEPAY_PG_ENABLED'] = 'true';
$_ENV['SEPAY_PG_ENV'] = 'sandbox';
$_ENV['SEPAY_PG_MERCHANT_ID'] = 'SP-TEST-SMOKE';
$_ENV['SEPAY_PG_SECRET_KEY'] = 'unit-test-secret-not-a-real-credential';
$_ENV['APP_URL'] = 'https://staging.example.com/TTTT';

require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Services/SePayGatewayService.php';

$service = new SePayGatewayService();
$checkout = $service->createCheckout([
    'order_id' => 42,
    'amount' => 100000,
    'sepay_invoice' => 'MOSHOP_20260817000000_SMOKE123456',
], 7);

gatewayCheck($checkout['action'] === 'https://pay-sandbox.sepay.vn/v1/checkout/init', 'Sandbox checkout URL');
gatewayCheck(($checkout['fields']['merchant'] ?? '') === 'SP-TEST-SMOKE', 'Merchant field');
gatewayCheck(($checkout['fields']['payment_method'] ?? '') === 'BANK_TRANSFER', 'Payment method');
gatewayCheck(($checkout['fields']['order_amount'] ?? '') === '100000', 'Order amount');
gatewayCheck(($checkout['fields']['order_invoice_number'] ?? '') === 'MOSHOP_20260817000000_SMOKE123456', 'Invoice number');
$signature = (string) ($checkout['fields']['signature'] ?? '');
gatewayCheck(strlen($signature) === 44 && base64_decode($signature, true) !== false, 'Checkout signature');
gatewayCheck(
    str_contains((string) ($checkout['fields']['success_url'] ?? ''), '/Payment_Gateway_Result.php?result=success'),
    'Success callback URL'
);

$_ENV['APP_URL'] = 'https://localhost/TTTT';
$rejectedLocalhost = false;
try {
    new SePayGatewayService();
} catch (RuntimeException) {
    $rejectedLocalhost = true;
}
gatewayCheck($rejectedLocalhost, 'Reject localhost callback URL');

echo "SePay Payment Gateway smoke tests passed\n";
