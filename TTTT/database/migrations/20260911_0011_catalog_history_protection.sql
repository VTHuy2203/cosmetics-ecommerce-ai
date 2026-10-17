-- Protect catalog and order history from cascading parent deletes.
-- Preflight: adding the order-detail FK intentionally fails if orphan rows exist;
-- investigate those rows instead of deleting or inventing historical products.
-- These ALTER statements take metadata locks. Apply during a maintenance window.

ALTER TABLE sanpham
    DROP FOREIGN KEY danhmuc,
    DROP FOREIGN KEY thuonghieu;

ALTER TABLE sanpham
    ADD CONSTRAINT fk_sanpham_danhmuc
        FOREIGN KEY (IDDanhMuc) REFERENCES danhmuc (IDDanhMuc)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT fk_sanpham_thuonghieu
        FOREIGN KEY (IDThuongHieu) REFERENCES thuonghieu (IDThuongHieu)
        ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE donhangchitiet
    ADD CONSTRAINT fk_donhangchitiet_sanpham
        FOREIGN KEY (ID_SanPham) REFERENCES sanpham (IDSanPham)
        ON DELETE RESTRICT ON UPDATE CASCADE;

-- Recovery: DDL is atomic per ALTER on MySQL 8. If a later ALTER fails, keep the
-- RESTRICT constraints already applied, repair orphan references from a verified
-- backup, then rerun the unapplied migration. Do not restore CASCADE semantics.
