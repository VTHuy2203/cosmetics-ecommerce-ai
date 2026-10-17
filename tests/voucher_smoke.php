<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Services/VoucherService.php';

$cases = [
    [100000, 10, null, 10000],
    [100000, 20, 15000, 15000],
    [100000, 100, null, 100000],
    [99999, 0, null, 0],
];
foreach ($cases as [$subtotal, $percent, $maximum, $expected]) {
    $actual = VoucherService::calculateDiscount($subtotal, $percent, $maximum);
    if ($actual !== $expected) {
        fwrite(STDERR, "Voucher calculation mismatch: expected={$expected} actual={$actual}\n");
        exit(1);
    }
}

foreach (['sale20' => 'SALE20', ' MO_SHOP-10 ' => 'MO_SHOP-10', '#01' => '01', 'bad code!' => ''] as $input => $expected) {
    if (VoucherService::normalizeCode($input) !== $expected) {
        fwrite(STDERR, "Voucher normalization failed for {$input}\n");
        exit(1);
    }
}

echo "voucher_smoke=ok\n";
