ALTER TABLE voucher_redemptions
    ADD COLUMN IF NOT EXISTS released_at DATETIME NULL AFTER redeemed_at,
    ADD COLUMN IF NOT EXISTS release_reason VARCHAR(50) NULL AFTER released_at;

ALTER TABLE voucher_redemptions
    ADD KEY idx_voucher_redemption_active (voucher_id, user_id, released_at);
