<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

use SePay\Builders\CheckoutBuilder;
use SePay\SePayClient;

final class SePayGatewayService
{
    private readonly SePayClient $client;
    private readonly string $environment;
    private readonly string $appUrl;

    public function __construct()
    {
        if (!app_bool('SEPAY_PG_ENABLED')) {
            throw new RuntimeException('SePay Payment Gateway is disabled.');
        }

        $merchantId = trim((string) app_env('SEPAY_PG_MERCHANT_ID', ''));
        $secretKey = trim((string) app_env('SEPAY_PG_SECRET_KEY', ''));
        $this->environment = strtolower(trim((string) app_env('SEPAY_PG_ENV', 'sandbox')));
        $this->appUrl = rtrim(trim((string) app_env('APP_URL', '')), '/');

        if ($merchantId === '' || $secretKey === '') {
            throw new RuntimeException('SePay Payment Gateway credentials are not configured.');
        }
        if (!in_array($this->environment, [SePayClient::ENVIRONMENT_SANDBOX, SePayClient::ENVIRONMENT_PRODUCTION], true)) {
            throw new RuntimeException('SEPAY_PG_ENV must be sandbox or production.');
        }
        if (!$this->isPublicHttpsUrl($this->appUrl)) {
            throw new RuntimeException('APP_URL must be a public HTTPS URL before enabling SePay Payment Gateway.');
        }

        $this->client = new SePayClient(
            $merchantId,
            $secretKey,
            $this->environment,
            [
                'timeout' => 15,
                'retry_attempts' => 2,
                'retry_delay' => 500,
                'debug' => false,
                'user_agent' => 'MoShop/1.0 SePay-PHP-SDK/1.0.0',
            ]
        );
    }

    /**
     * @param array{order_id:int,amount:int,sepay_invoice:string} $order
     * @return array{action:string,fields:array<string,mixed>}
     */
    public function createCheckout(array $order, int $customerId): array
    {
        $invoice = (string) ($order['sepay_invoice'] ?? '');
        $orderId = (int) ($order['order_id'] ?? 0);
        $amount = (int) ($order['amount'] ?? 0);
        if ($invoice === '' || $orderId <= 0 || $amount <= 0 || $customerId <= 0) {
            throw new InvalidArgumentException('Invalid order data for SePay checkout.');
        }

        $resultBase = $this->appUrl . '/WebBanHangMyPham/src/View/Payment_Gateway_Result.php';
        $checkoutData = CheckoutBuilder::make()
            ->paymentMethod('BANK_TRANSFER')
            ->currency('VND')
            ->orderInvoiceNumber($invoice)
            ->orderAmount($amount)
            ->operation('PURCHASE')
            ->orderDescription('Thanh toan don hang ' . $orderId)
            ->customerId('MOSHOP_' . $customerId)
            ->successUrl($resultBase . '?result=success&invoice=' . rawurlencode($invoice))
            ->errorUrl($resultBase . '?result=error&invoice=' . rawurlencode($invoice))
            ->cancelUrl($resultBase . '?result=cancel&invoice=' . rawurlencode($invoice))
            ->build();

        return [
            'action' => $this->client->checkout()->getCheckoutUrl($this->environment),
            'fields' => $this->client->checkout()->generateFormFields($checkoutData),
        ];
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return str_contains($host, '.');
    }
}
