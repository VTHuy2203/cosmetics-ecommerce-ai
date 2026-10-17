<?php
declare(strict_types=1);

/**
 * Allocate an integer VND discount proportionally across immutable order lines.
 * The rounding remainder goes to the last positive line, matching migration 0014.
 *
 * @param array<int, int> $lineTotals
 * @return array<int, int>
 */
function allocate_order_discount(array $lineTotals, int $discount): array
{
    $normalized = [];
    $subtotal = 0;
    foreach ($lineTotals as $key => $lineTotal) {
        if ($lineTotal < 0) {
            throw new DomainException('Order line total cannot be negative.');
        }
        $normalized[$key] = $lineTotal;
        $subtotal += $lineTotal;
    }
    if ($subtotal <= 0) {
        throw new DomainException('Order subtotal must be positive.');
    }

    $discount = max(0, min($discount, $subtotal));
    $allocated = [];
    $allocatedTotal = 0;
    $lastPositiveKey = null;
    foreach ($normalized as $key => $lineTotal) {
        $share = $lineTotal === 0 ? 0 : intdiv($discount * $lineTotal, $subtotal);
        $allocated[$key] = $share;
        $allocatedTotal += $share;
        if ($lineTotal > 0) {
            $lastPositiveKey = $key;
        }
    }
    if ($lastPositiveKey !== null) {
        $allocated[$lastPositiveKey] += $discount - $allocatedTotal;
    }
    return $allocated;
}
