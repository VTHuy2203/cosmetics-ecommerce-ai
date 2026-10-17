CREATE TABLE IF NOT EXISTS vouchers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    discount_percent TINYINT UNSIGNED NOT NULL,
    quantity_total INT UNSIGNED NOT NULL,
    quantity_used INT UNSIGNED NOT NULL DEFAULT 0,
    per_user_limit SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    min_order_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    max_discount_amount BIGINT UNSIGNED NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('draft','scheduled','active','paused','expired') NOT NULL DEFAULT 'scheduled',
    announce_on_publish TINYINT(1) NOT NULL DEFAULT 1,
    announcement_title VARCHAR(255) NULL,
    announcement_message VARCHAR(1000) NULL,
    published_at DATETIME NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_voucher_code (code),
    KEY idx_voucher_publish (status, starts_at, ends_at),
    CONSTRAINT fk_voucher_admin FOREIGN KEY (created_by) REFERENCES khachhang(ID_KhachHang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS broadcast_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    type ENUM('sale','voucher','system') NOT NULL DEFAULT 'sale',
    scheduled_at DATETIME NOT NULL,
    status ENUM('scheduled','published','cancelled') NOT NULL DEFAULT 'scheduled',
    published_at DATETIME NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_broadcast_due (status, scheduled_at),
    CONSTRAINT fk_broadcast_admin FOREIGN KEY (created_by) REFERENCES khachhang(ID_KhachHang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voucher_redemptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    voucher_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount BIGINT UNSIGNED NOT NULL,
    redeemed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_voucher_redemption_order (order_id),
    KEY idx_voucher_redemption_user (voucher_id, user_id),
    CONSTRAINT fk_redemption_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id),
    CONSTRAINT fk_redemption_user FOREIGN KEY (user_id) REFERENCES khachhang(ID_KhachHang),
    CONSTRAINT fk_redemption_order FOREIGN KEY (order_id) REFERENCES donhang(ID_DonHang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE donhang
    ADD COLUMN IF NOT EXISTS VoucherID BIGINT UNSIGNED NULL AFTER PhiVanChuyen,
    ADD COLUMN IF NOT EXISTS MaVoucher VARCHAR(32) NULL AFTER VoucherID,
    ADD COLUMN IF NOT EXISTS SoTienGiam BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER MaVoucher;

ALTER TABLE donhang
    ADD KEY idx_order_voucher (VoucherID),
    ADD CONSTRAINT fk_order_voucher FOREIGN KEY (VoucherID) REFERENCES vouchers(id) ON DELETE SET NULL;
