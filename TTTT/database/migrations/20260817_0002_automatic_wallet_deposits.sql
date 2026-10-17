ALTER TABLE nap_tien
    ADD COLUMN IF NOT EXISTS MaThanhToan VARCHAR(16) NULL AFTER NoiDung,
    ADD COLUMN IF NOT EXISTS HanThanhToan DATETIME NULL AFTER TrangThai,
    ADD COLUMN IF NOT EXISTS MaThamChieuThanhToan VARCHAR(100) NULL AFTER HanThanhToan,
    ADD COLUMN IF NOT EXISTS ThanhToanLuc DATETIME NULL AFTER MaThamChieuThanhToan;

ALTER TABLE nap_tien
    ADD UNIQUE KEY uq_nap_tien_mathanhToan (MaThanhToan),
    ADD KEY idx_nap_tien_pending_expiry (TrangThai, HanThanhToan);

ALTER TABLE payment_transactions
    ADD COLUMN IF NOT EXISTS deposit_id INT NULL AFTER order_id,
    ADD KEY idx_payment_deposit (deposit_id);

ALTER TABLE donhang
    ADD COLUMN IF NOT EXISTS GiaoHangLuc DATETIME NULL AFTER ThanhToanLuc;

UPDATE donhang SET GiaoHangLuc = NgayTao WHERE TrangThai = 3 AND GiaoHangLuc IS NULL;

CREATE TABLE IF NOT EXISTS return_refund_requests (
    ID_Request INT NOT NULL AUTO_INCREMENT,
    ID_DonHang INT NOT NULL,
    ID_KhachHang INT NOT NULL,
    ID_SanPham INT NOT NULL,
    ContactName VARCHAR(255) NOT NULL,
    ContactPhone VARCHAR(20) NOT NULL,
    ContactEmail VARCHAR(255) NOT NULL,
    ContactAddress TEXT NOT NULL,
    RefundReason TEXT NOT NULL,
    Attachments JSON NULL,
    TrangThai ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    ThoiGianTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ThoiGianCapNhat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Request),
    KEY idx_return_order (ID_DonHang),
    KEY idx_return_customer (ID_KhachHang),
    KEY idx_return_status (TrangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admin_notifications
    ADD COLUMN IF NOT EXISTS outbox_event_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD UNIQUE KEY uq_admin_notification_outbox (outbox_event_id);

ALTER TABLE customer_notifications
    ADD COLUMN IF NOT EXISTS outbox_event_id BIGINT UNSIGNED NULL AFTER message,
    ADD UNIQUE KEY uq_customer_notification_outbox (outbox_event_id);
