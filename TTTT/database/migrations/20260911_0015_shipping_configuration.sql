-- F07: one versioned source of truth for region/weight shipping fees.
-- No rates are seeded: an admin must create and activate a complete version.

ALTER TABLE sanpham
    ADD COLUMN TrongLuongGram INT UNSIGNED NULL AFTER SoLuong;

CREATE TABLE shipping_config_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    origin_label VARCHAR(255) NOT NULL,
    free_shipping_min BIGINT UNSIGNED NULL,
    status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
    active_guard TINYINT GENERATED ALWAYS AS (CASE WHEN status = 'active' THEN 1 ELSE NULL END) STORED,
    created_by INT NOT NULL,
    activated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipping_single_active (active_guard),
    CONSTRAINT fk_shipping_version_creator FOREIGN KEY (created_by) REFERENCES khachhang(ID_KhachHang) ON DELETE RESTRICT,
    CONSTRAINT fk_shipping_version_activator FOREIGN KEY (activated_by) REFERENCES khachhang(ID_KhachHang) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipping_regions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    version_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(120) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipping_region_code (version_id, code),
    CONSTRAINT fk_shipping_region_version FOREIGN KEY (version_id) REFERENCES shipping_config_versions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipping_rate_bands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    region_id BIGINT UNSIGNED NOT NULL,
    min_weight_grams INT UNSIGNED NOT NULL,
    max_weight_grams INT UNSIGNED NULL,
    fee BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipping_band_start (region_id, min_weight_grams),
    KEY idx_shipping_band_lookup (region_id, min_weight_grams, max_weight_grams),
    CONSTRAINT fk_shipping_band_region FOREIGN KEY (region_id) REFERENCES shipping_regions(id) ON DELETE CASCADE,
    CONSTRAINT chk_shipping_band_range CHECK (max_weight_grams IS NULL OR max_weight_grams >= min_weight_grams)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipping_config_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    version_id BIGINT UNSIGNED NOT NULL,
    actor_id INT NOT NULL,
    action VARCHAR(40) NOT NULL,
    details JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_shipping_audit_version (version_id, created_at),
    CONSTRAINT fk_shipping_audit_version FOREIGN KEY (version_id) REFERENCES shipping_config_versions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_shipping_audit_actor FOREIGN KEY (actor_id) REFERENCES khachhang(ID_KhachHang) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE donhang
    ADD COLUMN ShippingConfigVersionID BIGINT UNSIGNED NULL AFTER PhiVanChuyen,
    ADD COLUMN ShippingRegionCode VARCHAR(32) NULL AFTER ShippingConfigVersionID,
    ADD COLUMN ShippingRegionName VARCHAR(120) NULL AFTER ShippingRegionCode,
    ADD COLUMN TongKhoiLuongGram INT UNSIGNED NULL AFTER ShippingRegionName,
    ADD CONSTRAINT fk_order_shipping_version FOREIGN KEY (ShippingConfigVersionID)
        REFERENCES shipping_config_versions(id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Legacy orders retain their existing PhiVanChuyen without a fabricated region
-- or weight. New checkout requires an active configuration and full snapshot.
