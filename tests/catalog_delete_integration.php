<?php
declare(strict_types=1);
require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';

$db = app_pdo();
$db->exec("CREATE TABLE IF NOT EXISTS outbox_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,event_type VARCHAR(100) NOT NULL,
    aggregate_type VARCHAR(50) NOT NULL,aggregate_id VARCHAR(100) NOT NULL,event_key VARCHAR(190) NULL,payload JSON NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$eventKeyColumn = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='outbox_events' AND COLUMN_NAME='event_key'")->fetchColumn();
if (!(int) $eventKeyColumn) { $db->exec('ALTER TABLE outbox_events ADD COLUMN event_key VARCHAR(190) NULL AFTER aggregate_id'); }
$db->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['binhluan','return_refund_requests','donhangchitiet','donhang','sanpham','danhmuc','thuonghieu'] as $table) {
    $db->exec('DROP TABLE IF EXISTS `' . $table . '`');
}
$db->exec('SET FOREIGN_KEY_CHECKS=1');
$db->exec('CREATE TABLE danhmuc (IDDanhMuc INT PRIMARY KEY, TenDanhMuc VARCHAR(100)) ENGINE=InnoDB');
$db->exec('CREATE TABLE thuonghieu (IDThuongHieu INT PRIMARY KEY, TenThuongHieu VARCHAR(100)) ENGINE=InnoDB');
$db->exec('CREATE TABLE sanpham (
    IDSanPham INT PRIMARY KEY, TenSanPham VARCHAR(100), IDDanhMuc INT NOT NULL DEFAULT 1, IDThuongHieu INT NOT NULL DEFAULT 1,
    CONSTRAINT danhmuc FOREIGN KEY (IDDanhMuc) REFERENCES danhmuc(IDDanhMuc) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT thuonghieu FOREIGN KEY (IDThuongHieu) REFERENCES thuonghieu(IDThuongHieu) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB');
$db->exec('CREATE TABLE donhangchitiet (ID_DonHangChiTiet INT AUTO_INCREMENT PRIMARY KEY, ID_DonHang INT, ID_SanPham INT NOT NULL) ENGINE=InnoDB');

$migration = file_get_contents(dirname(__DIR__) . '/TTTT/database/migrations/20260911_0011_catalog_history_protection.sql');
if (!is_string($migration)) { throw new RuntimeException('Cannot read F04 migration.'); }
$migration = preg_replace('/^\s*--.*$/m', '', $migration) ?? $migration;
foreach (array_filter(array_map('trim', explode(';', $migration))) as $statement) {
    $db->exec($statement);
}

$rules = $db->query("SELECT CONSTRAINT_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME IN
    ('fk_sanpham_danhmuc','fk_sanpham_thuonghieu','fk_donhangchitiet_sanpham')")
    ->fetchAll(PDO::FETCH_KEY_PAIR);
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void {
    if (!$ok) { $failures[] = $message; }
};
$check(count($rules) === 3 && count(array_filter($rules, static fn($rule) => $rule === 'RESTRICT')) === 3,
    'Migration did not install three RESTRICT constraints.');

$db->exec("INSERT INTO danhmuc VALUES (1,'Đang dùng'),(2,'Không dùng')");
$db->exec("INSERT INTO thuonghieu VALUES (1,'Đang dùng'),(2,'Không dùng')");
$db->exec("INSERT INTO sanpham VALUES (1,'Có đơn',1,1),(2,'Chưa bán',1,1)");
$db->exec('INSERT INTO donhangchitiet (ID_DonHangChiTiet, ID_DonHang, ID_SanPham) VALUES (1,10,1)');

require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';
require_once dirname(__DIR__) . '/TTTT/config/catalog.php';
$check(catalog_delete($conn, 'category', 1) === 'in_use', 'Referenced category was not blocked.');
$check(catalog_delete($conn, 'brand', 1) === 'in_use', 'Referenced brand was not blocked.');
$check(catalog_delete($conn, 'product', 1) === 'in_use', 'Product in order history was not blocked.');
$check(catalog_delete($conn, 'product', 2) === 'deleted', 'Unused product was not deleted.');
$check((int) $db->query("SELECT COUNT(*) FROM outbox_events WHERE event_type='catalog.changed' AND aggregate_id='2'")->fetchColumn() === 1,
    'Deleting a product did not commit its durable RAG event.');
$check(catalog_delete($conn, 'category', 2) === 'deleted', 'Unused category was not deleted.');
$check(catalog_delete($conn, 'brand', 2) === 'deleted', 'Unused brand was not deleted.');
$check(catalog_delete($conn, 'product', 999) === 'missing', 'Missing product result is incorrect.');
$check(catalog_delete($conn, 'product', 0) === 'invalid', 'Invalid product ID was accepted.');
$_GET['delete_status'] = 'in_use';
$check(str_contains(catalog_delete_alert(), 'Không thể xóa'), 'Admin warning is missing.');
$_GET['delete_status'] = '<script>alert(1)</script>';
$check(catalog_delete_alert() === '', 'Unknown delete status was rendered.');
$check((int) $db->query('SELECT COUNT(*) FROM sanpham WHERE IDSanPham=1')->fetchColumn() === 1,
    'Protected product was removed.');
$check((int) $db->query('SELECT COUNT(*) FROM donhangchitiet WHERE ID_DonHangChiTiet=1')->fetchColumn() === 1,
    'Order detail was removed.');

try {
    $db->exec('DELETE FROM danhmuc WHERE IDDanhMuc=1');
    $check(false, 'Database allowed category cascade bypass.');
} catch (PDOException) {
    // Expected: the database is the final guard against concurrent/direct deletes.
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "catalog_delete_integration=ok\n";
