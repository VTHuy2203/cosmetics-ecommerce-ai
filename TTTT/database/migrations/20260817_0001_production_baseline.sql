-- Production baseline for MySQL 8.0.29+ / MySQL 8.4.
-- Apply with: php TTTT/bin/migrate.php

ALTER TABLE khachhang
    ADD COLUMN IF NOT EXISTS Role ENUM('customer','shipper','employee','manager','admin') NOT NULL DEFAULT 'customer' AFTER DiaChi,
    ADD COLUMN IF NOT EXISTS customer_label ENUM('spam','follow-up','potential') NOT NULL DEFAULT 'spam',
    ADD COLUMN IF NOT EXISTS discount_percent INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_weekly_purchase BIGINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS label_updated_at TIMESTAMP NULL;

ALTER TABLE donhang
    ADD COLUMN IF NOT EXISTS NgayTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ID_DonHang,
    ADD COLUMN IF NOT EXISTS DaThanhToan TINYINT(1) NOT NULL DEFAULT 0 AFTER TrangThai,
    ADD COLUMN IF NOT EXISTS PhuongThucThanhToan ENUM('cod','qr','balance') NOT NULL DEFAULT 'cod' AFTER DaThanhToan,
    ADD COLUMN IF NOT EXISTS TrangThaiThanhToan ENUM('pending','paid','failed','expired','refunded','cod_pending') NOT NULL DEFAULT 'pending' AFTER PhuongThucThanhToan,
    ADD COLUMN IF NOT EXISTS MaThanhToan VARCHAR(16) NULL AFTER TrangThaiThanhToan,
    ADD COLUMN IF NOT EXISTS SoTienHang BIGINT NOT NULL DEFAULT 0 AFTER MaThanhToan,
    ADD COLUMN IF NOT EXISTS PhiVanChuyen BIGINT NOT NULL DEFAULT 0 AFTER SoTienHang,
    ADD COLUMN IF NOT EXISTS TonKhoDaGiu TINYINT(1) NOT NULL DEFAULT 0 AFTER PhiVanChuyen,
    ADD COLUMN IF NOT EXISTS HanThanhToan DATETIME NULL AFTER TonKhoDaGiu,
    ADD COLUMN IF NOT EXISTS ThanhToanLuc DATETIME NULL AFTER HanThanhToan,
    ADD COLUMN IF NOT EXISTS MaThamChieuThanhToan VARCHAR(100) NULL AFTER ThanhToanLuc,
    ADD COLUMN IF NOT EXISTS ID_Shipper INT NULL AFTER MaThamChieuThanhToan;

ALTER TABLE donhang MODIFY COLUMN TongTien BIGINT NOT NULL;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND INDEX_NAME = 'uq_khachhang_username') = 0,
    'ALTER TABLE khachhang ADD UNIQUE KEY uq_khachhang_username (TenDangNhap)',
    'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND INDEX_NAME = 'idx_donhang_shipper_status') = 0,
    'ALTER TABLE donhang ADD KEY idx_donhang_shipper_status (ID_Shipper, TrangThai)',
    'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND INDEX_NAME = 'uq_khachhang_email') = 0,
    'ALTER TABLE khachhang ADD UNIQUE KEY uq_khachhang_email (Email)',
    'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND INDEX_NAME = 'uq_donhang_mathanhToan') = 0,
    'ALTER TABLE donhang ADD UNIQUE KEY uq_donhang_mathanhToan (MaThanhToan)',
    'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND INDEX_NAME = 'idx_donhang_payment_status') = 0,
    'ALTER TABLE donhang ADD KEY idx_donhang_payment_status (TrangThaiThanhToan, HanThanhToan)',
    'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

-- Legacy orders in these states already had stock deducted by the old approval/delivery flow.
UPDATE donhang SET TonKhoDaGiu = 1 WHERE TrangThai IN (1, 2, 3) AND TonKhoDaGiu = 0;
UPDATE donhang
SET SoTienHang = TongTien,
    TrangThaiThanhToan = CASE WHEN DaThanhToan = 1 THEN 'paid' ELSE 'cod_pending' END,
    PhuongThucThanhToan = 'cod'
WHERE SoTienHang = 0;

ALTER TABLE donhangchitiet
    ADD COLUMN IF NOT EXISTS DonGia BIGINT NOT NULL DEFAULT 0 AFTER SoLuong,
    ADD COLUMN IF NOT EXISTS ThanhTien BIGINT NOT NULL DEFAULT 0 AFTER DonGia;

UPDATE donhangchitiet d
INNER JOIN sanpham s ON s.IDSanPham = d.ID_SanPham
SET d.DonGia = s.GiaTien, d.ThanhTien = s.GiaTien * d.SoLuong
WHERE d.DonGia = 0;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider VARCHAR(32) NOT NULL DEFAULT 'sepay',
    provider_transaction_id VARCHAR(100) NOT NULL,
    reference_code VARCHAR(100) NULL,
    payment_code VARCHAR(32) NULL,
    order_id INT NULL,
    transfer_type ENUM('in','out') NOT NULL,
    amount BIGINT NOT NULL,
    account_number VARCHAR(64) NOT NULL,
    status ENUM('received','matched','duplicate','amount_mismatch','unmatched','rejected') NOT NULL DEFAULT 'received',
    payload JSON NOT NULL,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_provider_transaction (provider, provider_transaction_id),
    KEY idx_payment_code (payment_code),
    KEY idx_payment_order (order_id),
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES donhang(ID_DonHang) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS outbox_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(100) NOT NULL,
    aggregate_type VARCHAR(50) NOT NULL,
    aggregate_id VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_outbox_pending (processed_at, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_verifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_created (email, created_at),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipper_requests (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    license VARCHAR(50) NOT NULL,
    vehicle VARCHAR(50) NOT NULL DEFAULT '',
    hometown VARCHAR(255) NULL,
    permanent_address TEXT NULL,
    temporary_address TEXT NULL,
    id_card_front VARCHAR(255) NULL,
    id_card_back VARCHAR(255) NULL,
    note TEXT NULL,
    work_location ENUM('in-store','remote') NULL,
    requested_role ENUM('shipper','employee') NOT NULL DEFAULT 'shipper',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_shipper_user (user_id),
    KEY idx_shipper_status (status),
    CONSTRAINT fk_shipper_user FOREIGN KEY (user_id) REFERENCES khachhang(ID_KhachHang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE shipper_requests
    ADD COLUMN IF NOT EXISTS hometown VARCHAR(255) NULL AFTER vehicle,
    ADD COLUMN IF NOT EXISTS permanent_address TEXT NULL AFTER hometown,
    ADD COLUMN IF NOT EXISTS temporary_address TEXT NULL AFTER permanent_address,
    ADD COLUMN IF NOT EXISTS id_card_front VARCHAR(255) NULL AFTER temporary_address,
    ADD COLUMN IF NOT EXISTS id_card_back VARCHAR(255) NULL AFTER id_card_front,
    ADD COLUMN IF NOT EXISTS work_location ENUM('in-store','remote') NULL AFTER note,
    ADD COLUMN IF NOT EXISTS requested_role ENUM('shipper','employee') NOT NULL DEFAULT 'shipper' AFTER work_location;

UPDATE shipper_requests sr
INNER JOIN khachhang k ON k.ID_KhachHang = sr.user_id
SET sr.requested_role = 'employee'
WHERE k.Role = 'employee';

CREATE TABLE IF NOT EXISTS admin_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    order_id INT NULL,
    user_id INT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_notification_read (is_read, created_at),
    KEY idx_admin_notification_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_customer_notification_user (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    message TEXT NOT NULL,
    direction ENUM('user','bot') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_user_created (user_id, created_at),
    KEY idx_chat_session_created (session_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE chat_messages SET session_id = CONCAT('legacy-', id) WHERE session_id IS NULL OR session_id = '';

ALTER TABLE chat_messages
    MODIFY COLUMN session_id VARCHAR(128) NOT NULL;

CREATE TABLE IF NOT EXISTS chat_appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    appt_at DATETIME NOT NULL,
    note TEXT NULL,
    message_content TEXT NULL,
    message_sent_at TIMESTAMP NULL,
    guest_name VARCHAR(255) NULL,
    guest_phone VARCHAR(20) NULL,
    guest_address TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_appointment_user (user_id),
    KEY idx_chat_appointment_session (session_id),
    KEY idx_chat_appointment_time (appt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE chat_appointments
    MODIFY COLUMN session_id VARCHAR(128) NOT NULL,
    ADD COLUMN IF NOT EXISTS message_content TEXT NULL AFTER note,
    ADD COLUMN IF NOT EXISTS message_sent_at TIMESTAMP NULL AFTER message_content,
    ADD COLUMN IF NOT EXISTS guest_name VARCHAR(255) NULL AFTER message_sent_at,
    ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) NULL AFTER guest_name,
    ADD COLUMN IF NOT EXISTS guest_address TEXT NULL AFTER guest_phone;

CREATE TABLE IF NOT EXISTS guest_customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(128) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NULL,
    email VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guest_session (session_id),
    KEY idx_guest_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_analysis (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    label ENUM('spam','follow-up','potential') NOT NULL DEFAULT 'spam',
    product_interest TEXT NULL,
    last_need TEXT NULL,
    message_count INT NOT NULL DEFAULT 0,
    has_appointment TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customer_analysis_session (session_id),
    KEY idx_customer_analysis_user (user_id),
    KEY idx_customer_analysis_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    ID_HinhAnh BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ID_SanPham INT NOT NULL,
    DuongDan VARCHAR(255) NOT NULL,
    ThuTu INT NOT NULL DEFAULT 0,
    ThoiGianTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_HinhAnh),
    KEY idx_product_image_product (ID_SanPham)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
