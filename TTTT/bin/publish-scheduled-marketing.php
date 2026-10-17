<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';
require_once dirname(__DIR__) . '/WebBanHangMyPham/src/Services/MarketingService.php';

try {
    $result = (new MarketingService($conn))->publishDue();
    printf(
        "vouchers=%d broadcasts=%d expired=%d\n",
        $result['vouchers'],
        $result['broadcasts'],
        $result['expired']
    );
} catch (Throwable $error) {
    error_log('Scheduled marketing failed: ' . $error->getMessage());
    fwrite(STDERR, "scheduled_marketing_failed\n");
    exit(1);
}
