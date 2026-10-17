<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/bootstrap.php';

$errors = [];
$warnings = [];
$required = [
    'APP_URL', 'MYSQL_HOST', 'MYSQL_DB', 'MYSQL_USER', 'MYSQL_PASSWORD',
    'SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'MAIL_FROM_ADDRESS',
    'VIETQR_BANK_BIN', 'VIETQR_ACCOUNT_NO', 'VIETQR_ACCOUNT_NAME', 'SEPAY_ACCOUNT_NUMBER',
    'SEPAY_WEBHOOK_SECRET', 'TURNSTILE_SITE_KEY', 'TURNSTILE_SECRET_KEY',
];
foreach ($required as $key) {
    if ((app_env($key, '') ?? '') === '') {
        $errors[] = "Missing environment value: {$key}";
    }
}

if (!str_starts_with(app_env('APP_URL', '') ?? '', 'https://')) {
    $errors[] = 'APP_URL must use HTTPS in production.';
}
if (strlen(app_env('SEPAY_WEBHOOK_SECRET', '') ?? '') < 32) {
    $errors[] = 'SEPAY_WEBHOOK_SECRET must be at least 32 characters.';
}
if (app_bool('SEPAY_PG_ENABLED')) {
    $gatewayEnvironment = strtolower((string) app_env('SEPAY_PG_ENV', ''));
    if (!in_array($gatewayEnvironment, ['sandbox', 'production'], true)) {
        $errors[] = 'SEPAY_PG_ENV must be sandbox or production.';
    }
    if (trim((string) app_env('SEPAY_PG_MERCHANT_ID', '')) === '') {
        $errors[] = 'SEPAY_PG_MERCHANT_ID is required when SePay Payment Gateway is enabled.';
    }
    if (strlen(trim((string) app_env('SEPAY_PG_SECRET_KEY', ''))) < 24) {
        $errors[] = 'SEPAY_PG_SECRET_KEY appears invalid.';
    }
    $appHost = strtolower((string) parse_url(app_env('APP_URL', '') ?? '', PHP_URL_HOST));
    if ($appHost === '' || $appHost === 'localhost' || str_ends_with($appHost, '.local') || !str_contains($appHost, '.')) {
        $errors[] = 'SePay Payment Gateway requires APP_URL to use a public DNS hostname.';
    }
}
$paymentPrefix = (string) app_env('SEPAY_PAYMENT_PREFIX', '');
if (!preg_match('/^[A-Z0-9]{1,5}$/', $paymentPrefix)) {
    $errors[] = 'SEPAY_PAYMENT_PREFIX must contain 1-5 uppercase letters or digits.';
}
if (strlen(app_env('TURNSTILE_SECRET_KEY', '') ?? '') < 10) {
    $errors[] = 'TURNSTILE_SECRET_KEY appears invalid.';
}
$vietQrAccount = preg_replace('/\s+/', '', app_env('VIETQR_ACCOUNT_NO', '') ?? '');
$sepayAccount = preg_replace('/\s+/', '', app_env('SEPAY_ACCOUNT_NUMBER', '') ?? '');
if ($vietQrAccount !== '' && $sepayAccount !== '' && !hash_equals($vietQrAccount, $sepayAccount)) {
    $errors[] = 'VIETQR_ACCOUNT_NO and SEPAY_ACCOUNT_NUMBER must identify the same receiving account.';
}
if (app_bool('AI_CHAT_ENABLED')) {
    if ((app_env('AI_SERVICE_URL', '') ?? '') === '' || strlen(app_env('AI_SERVICE_TOKEN', '') ?? '') < 32) {
        $errors[] = 'AI chat requires AI_SERVICE_URL and an AI_SERVICE_TOKEN of at least 32 characters.';
    }
}
if ((app_env('MYSQL_USER', '') ?? '') === 'root') {
    $warnings[] = 'The application database user should not be root.';
}

try {
    require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';
    $requiredColumns = [
        ['donhang', 'TrangThaiThanhToan'],
        ['donhang', 'MaThanhToan'],
        ['donhang', 'TonKhoDaGiu'],
        ['donhang', 'GiaoHangLuc'],
        ['donhang', 'SePayInvoiceNumber'],
        ['donhang', 'SePayGatewayOrderId'],
        ['donhangchitiet', 'DonGia'],
        ['nap_tien', 'MaThanhToan'],
        ['shipper_requests', 'requested_role'],
    ];
    foreach ($requiredColumns as [$table, $column]) {
        $statement = $conn->prepare(
            'SELECT COUNT(*) AS count FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        if ((int) $statement->get_result()->fetch_assoc()['count'] !== 1) {
            $errors[] = "Migration missing: {$table}.{$column}";
        }
    }
    foreach (['payment_transactions', 'outbox_events', 'return_refund_requests', 'schema_migrations'] as $table) {
        $statement = $conn->prepare(
            'SELECT COUNT(*) AS count FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $statement->bind_param('s', $table);
        $statement->execute();
        if ((int) $statement->get_result()->fetch_assoc()['count'] !== 1) {
            $errors[] = "Required table missing: {$table}";
        }
    }
} catch (Throwable $error) {
    $errors[] = 'Database preflight failed: ' . $error->getMessage();
}

foreach ($warnings as $warning) {
    fwrite(STDERR, "WARN  {$warning}\n");
}
foreach ($errors as $error) {
    fwrite(STDERR, "ERROR {$error}\n");
}
if ($errors !== []) {
    exit(1);
}
echo "production preflight passed\n";
