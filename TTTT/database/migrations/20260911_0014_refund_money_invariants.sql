-- F03: immutable product-money allocation and idempotent refund ledger.
-- Shipping is intentionally excluded from every refundable amount.

ALTER TABLE donhangchitiet
    ADD COLUMN SoTienGiamPhanBo BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER ThanhTien,
    ADD COLUMN ThanhTienThucTra BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER SoTienGiamPhanBo;

-- Backfill old order lines from the immutable order/detail snapshots. Each
-- order's rounding remainder is assigned to its last positive line so the
-- allocated discount equals the order discount exactly.
WITH line_allocation AS (
    SELECT dc.ID_DonHangChiTiet,
           dc.ID_DonHang,
           GREATEST(0, dc.ThanhTien) AS line_total,
           LEAST(GREATEST(0, COALESCE(d.SoTienGiam, 0)),
                 SUM(GREATEST(0, dc.ThanhTien)) OVER (PARTITION BY dc.ID_DonHang)) AS order_discount,
           SUM(GREATEST(0, dc.ThanhTien)) OVER (PARTITION BY dc.ID_DonHang) AS order_subtotal,
           ROW_NUMBER() OVER (
               PARTITION BY dc.ID_DonHang
               ORDER BY (dc.ThanhTien > 0) DESC, dc.ID_DonHangChiTiet DESC
           ) AS remainder_line
    FROM donhangchitiet dc
    INNER JOIN donhang d ON d.ID_DonHang = dc.ID_DonHang
), base_allocation AS (
    SELECT *,
           CASE WHEN order_subtotal > 0
                THEN FLOOR(order_discount * line_total / order_subtotal)
                ELSE 0 END AS base_discount
    FROM line_allocation
), final_allocation AS (
    SELECT *,
           SUM(base_discount) OVER (PARTITION BY ID_DonHang) AS allocated_base
    FROM base_allocation
)
UPDATE donhangchitiet dc
INNER JOIN final_allocation a ON a.ID_DonHangChiTiet = dc.ID_DonHangChiTiet
SET dc.SoTienGiamPhanBo = a.base_discount
        + CASE WHEN a.remainder_line = 1 THEN a.order_discount - a.allocated_base ELSE 0 END,
    dc.ThanhTienThucTra = a.line_total - (
        a.base_discount
        + CASE WHEN a.remainder_line = 1 THEN a.order_discount - a.allocated_base ELSE 0 END
    );

ALTER TABLE return_refund_requests
    ADD COLUMN SoLuongYeuCau INT UNSIGNED NULL AFTER ID_SanPham,
    ADD COLUMN SoTienHoan BIGINT UNSIGNED NULL AFTER TrangThai,
    ADD COLUMN XuLyBoi INT NULL AFTER SoTienHoan,
    ADD COLUMN VaiTroXuLy VARCHAR(32) NULL AFTER XuLyBoi,
    ADD COLUMN ThoiGianXuLy DATETIME NULL AFTER VaiTroXuLy;

UPDATE return_refund_requests r
INNER JOIN donhangchitiet dc
    ON dc.ID_DonHang = r.ID_DonHang AND dc.ID_SanPham = r.ID_SanPham
SET r.SoLuongYeuCau = dc.SoLuong
WHERE r.SoLuongYeuCau IS NULL OR r.SoLuongYeuCau = 0;

ALTER TABLE return_refund_requests
    MODIFY COLUMN SoLuongYeuCau INT UNSIGNED NOT NULL;

CREATE TABLE IF NOT EXISTS refund_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT NOT NULL,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    destination ENUM('wallet') NOT NULL DEFAULT 'wallet',
    actor_id INT NOT NULL,
    actor_role VARCHAR(32) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_refund_request (request_id),
    KEY idx_refund_order_product (order_id, product_id),
    KEY idx_refund_customer (customer_id),
    CONSTRAINT fk_refund_request FOREIGN KEY (request_id)
        REFERENCES return_refund_requests (ID_Request) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_refund_order FOREIGN KEY (order_id)
        REFERENCES donhang (ID_DonHang) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_refund_customer FOREIGN KEY (customer_id)
        REFERENCES khachhang (ID_KhachHang) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_refund_product FOREIGN KEY (product_id)
        REFERENCES sanpham (IDSanPham) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_refund_quantity CHECK (quantity > 0),
    CONSTRAINT chk_refund_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing approved rows cannot be safely inserted into refund_transactions:
-- old code did not persist the actual credited amount. They remain visible in
-- return_refund_requests and must be reconciled from wallet/audit records.
