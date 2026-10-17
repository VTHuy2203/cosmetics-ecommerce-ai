<?php
declare(strict_types=1);

final class VoucherException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}

final class VoucherService
{
    public function __construct(private readonly mysqli $db)
    {
    }

    /** @return array<string,mixed> */
    public function validate(string $code, int $userId, int $subtotal, bool $forUpdate = false): array
    {
        $code = self::normalizeCode($code);
        if ($code === '') {
            throw new VoucherException('voucher_required', 'Vui lòng nhập mã giảm giá.');
        }
        if ($subtotal <= 0) {
            throw new VoucherException('empty_cart', 'Giỏ hàng đang trống.');
        }

        $sql = 'SELECT * FROM vouchers WHERE code = ? LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
        $statement = $this->db->prepare($sql);
        $statement->bind_param('s', $code);
        $statement->execute();
        $voucher = $statement->get_result()->fetch_assoc();
        if (!$voucher) {
            throw new VoucherException('voucher_not_found', 'Mã giảm giá không tồn tại.');
        }

        $now = time();
        $startsAt = strtotime((string) $voucher['starts_at']);
        $endsAt = strtotime((string) $voucher['ends_at']);
        $status = (string) $voucher['status'];
        if (in_array($status, ['draft', 'paused', 'expired'], true)) {
            throw new VoucherException('voucher_inactive', 'Mã giảm giá hiện không hoạt động.');
        }
        if ($startsAt === false || $startsAt > $now) {
            throw new VoucherException('voucher_not_started', 'Mã giảm giá chưa đến thời gian sử dụng.');
        }
        if ($endsAt === false || $endsAt <= $now) {
            throw new VoucherException('voucher_expired', 'Mã giảm giá đã hết hạn.');
        }
        if ((int) $voucher['quantity_used'] >= (int) $voucher['quantity_total']) {
            throw new VoucherException('voucher_exhausted', 'Mã giảm giá đã hết lượt sử dụng.');
        }

        $claim = $this->db->prepare(
            "SELECT status FROM voucher_claims WHERE voucher_id = ? AND user_id = ? LIMIT 1"
        );
        $voucherId = (int) $voucher['id'];
        $claim->bind_param('ii', $voucherId, $userId);
        $claim->execute();
        $claimStatus = $claim->get_result()->fetch_assoc()['status'] ?? null;
        if ($claimStatus !== 'claimed') {
            $claimCount = $this->db->prepare(
                "SELECT COUNT(*) AS total FROM voucher_claims WHERE voucher_id = ? AND status = 'claimed'"
            );
            $claimCount->bind_param('i', $voucherId);
            $claimCount->execute();
            $reserved = (int) ($claimCount->get_result()->fetch_assoc()['total'] ?? 0);
            if ((int) $voucher['quantity_used'] + $reserved >= (int) $voucher['quantity_total']) {
                throw new VoucherException('voucher_exhausted', 'Voucher đã được khách hàng khác nhận hết.');
            }
        }
        if ($subtotal < (int) $voucher['min_order_amount']) {
            throw new VoucherException(
                'voucher_minimum_not_met',
                'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format((int) $voucher['min_order_amount'], 0, ',', '.') . ' đ.'
            );
        }

        $usage = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM voucher_redemptions WHERE voucher_id = ? AND user_id = ? AND released_at IS NULL'
        );
        $usage->bind_param('ii', $voucherId, $userId);
        $usage->execute();
        $usedByUser = (int) ($usage->get_result()->fetch_assoc()['total'] ?? 0);
        if ($usedByUser >= (int) $voucher['per_user_limit']) {
            throw new VoucherException('voucher_user_limit', 'Bạn đã dùng hết lượt cho mã giảm giá này.');
        }

        $discount = self::calculateDiscount(
            $subtotal,
            (int) $voucher['discount_percent'],
            $voucher['max_discount_amount'] === null ? null : (int) $voucher['max_discount_amount']
        );
        $voucher['discount_amount'] = $discount;
        return $voucher;
    }

    /** @param array<string,mixed> $voucher */
    public function redeem(array $voucher, int $userId, int $orderId): void
    {
        $voucherId = (int) $voucher['id'];
        $discount = (int) $voucher['discount_amount'];
        $update = $this->db->prepare(
            'UPDATE vouchers SET quantity_used = quantity_used + 1 WHERE id = ? AND quantity_used < quantity_total'
        );
        $update->bind_param('i', $voucherId);
        $update->execute();
        if ($update->affected_rows !== 1) {
            throw new VoucherException('voucher_exhausted', 'Mã giảm giá vừa hết lượt sử dụng.');
        }
        $insert = $this->db->prepare(
            'INSERT INTO voucher_redemptions(voucher_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)'
        );
        $insert->bind_param('iiii', $voucherId, $userId, $orderId, $discount);
        $insert->execute();

        $claim = $this->db->prepare(
            "UPDATE voucher_claims SET status = 'redeemed', redeemed_at = NOW(), order_id = ?
             WHERE voucher_id = ? AND user_id = ? AND status = 'claimed'"
        );
        $claim->bind_param('iii', $orderId, $voucherId, $userId);
        $claim->execute();
    }

    public function releaseForOrder(int $orderId, string $reason): bool
    {
        $reason = mb_substr(trim($reason), 0, 50);
        $select = $this->db->prepare(
            'SELECT id, voucher_id, released_at FROM voucher_redemptions WHERE order_id = ? FOR UPDATE'
        );
        $select->bind_param('i', $orderId);
        $select->execute();
        $redemption = $select->get_result()->fetch_assoc();
        if (!$redemption || $redemption['released_at'] !== null) {
            return false;
        }

        $redemptionId = (int) $redemption['id'];
        $release = $this->db->prepare(
            'UPDATE voucher_redemptions SET released_at = NOW(), release_reason = ? WHERE id = ? AND released_at IS NULL'
        );
        $release->bind_param('si', $reason, $redemptionId);
        $release->execute();
        if ($release->affected_rows !== 1) {
            return false;
        }

        $voucherId = (int) $redemption['voucher_id'];
        $restore = $this->db->prepare(
            'UPDATE vouchers SET quantity_used = GREATEST(quantity_used - 1, 0) WHERE id = ?'
        );
        $restore->bind_param('i', $voucherId);
        $restore->execute();

        $restoreClaim = $this->db->prepare(
            "UPDATE voucher_claims c
             INNER JOIN vouchers v ON v.id = c.voucher_id
             SET c.status = IF(v.status = 'active' AND v.ends_at > NOW(), 'claimed', 'released'),
                 c.redeemed_at = NULL, c.order_id = NULL
             WHERE c.order_id = ? AND c.status = 'redeemed'"
        );
        $restoreClaim->bind_param('i', $orderId);
        $restoreClaim->execute();
        return true;
    }

    public static function calculateDiscount(int $subtotal, int $percent, ?int $maximum): int
    {
        if ($subtotal <= 0 || $percent < 1 || $percent > 100) {
            return 0;
        }
        $discount = intdiv($subtotal * $percent, 100);
        if ($maximum !== null && $maximum > 0) {
            $discount = min($discount, $maximum);
        }
        return min($subtotal, max(0, $discount));
    }

    public static function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = ltrim($code, '#');
        return preg_match('/^[A-Z0-9_-]{2,32}$/', $code) ? $code : '';
    }
}
