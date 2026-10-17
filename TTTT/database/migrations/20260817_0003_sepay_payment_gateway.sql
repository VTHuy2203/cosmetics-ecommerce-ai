ALTER TABLE donhang
    MODIFY COLUMN PhuongThucThanhToan ENUM('cod','qr','balance','sepay_pg') NOT NULL DEFAULT 'cod',
    MODIFY COLUMN TrangThaiThanhToan ENUM('pending','paid','failed','expired','refunded','cod_pending','review') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS SePayInvoiceNumber VARCHAR(100) NULL AFTER MaThanhToan,
    ADD COLUMN IF NOT EXISTS SePayGatewayOrderId VARCHAR(100) NULL AFTER SePayInvoiceNumber;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND INDEX_NAME = 'uq_donhang_sepay_invoice') = 0,
    'ALTER TABLE donhang ADD UNIQUE KEY uq_donhang_sepay_invoice (SePayInvoiceNumber)',
    'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

ALTER TABLE payment_transactions
    MODIFY COLUMN status ENUM('received','matched','duplicate','amount_mismatch','unmatched','rejected','voided','review')
        NOT NULL DEFAULT 'received';
