CREATE TABLE IF NOT EXISTS voucher_claims (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    voucher_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    status ENUM('claimed','redeemed','released') NOT NULL DEFAULT 'claimed',
    claimed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    redeemed_at DATETIME NULL,
    order_id INT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_voucher_claim_user (voucher_id, user_id),
    KEY idx_voucher_claim_user_status (user_id, status),
    CONSTRAINT fk_voucher_claim_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    CONSTRAINT fk_voucher_claim_user FOREIGN KEY (user_id) REFERENCES khachhang(ID_KhachHang) ON DELETE CASCADE,
    CONSTRAINT fk_voucher_claim_order FOREIGN KEY (order_id) REFERENCES donhang(ID_DonHang) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
