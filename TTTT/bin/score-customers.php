<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/WebBanHangMyPham/src/Services/CustomerScoringService.php';

try {
    $result = (new CustomerScoringService(app_pdo()))->scoreAll();
    echo json_encode([
        'ok' => $result['failed'] === 0,
        'scoring_version' => CustomerScoringService::VERSION,
        ...$result,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['failed'] === 0 ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, 'Customer scoring failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
