<?php
declare(strict_types=1);

require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';
require_once dirname(__DIR__) . '/TTTT/config/returns.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Services/MoneyAllocationService.php';

$pdo = app_pdo();
$mysqli = new mysqli(
    (string) getenv('MYSQL_HOST'), (string) getenv('MYSQL_USER'),
    (string) getenv('MYSQL_PASSWORD'), (string) getenv('MYSQL_DB'),
    (int) getenv('MYSQL_PORT')
);
$mysqli->set_charset('utf8mb4');

$addColumn = static function (string $table, string $column, string $definition) use ($pdo): void {
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $check->execute([$table, $column]);
    if (!(int) $check->fetchColumn()) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
};
foreach ([
    ['khachhang','SoDu','BIGINT NOT NULL DEFAULT 0'],
    ['donhang','SoTienGiam','BIGINT NOT NULL DEFAULT 0'],
    ['donhang','TrangThaiThanhToan',"VARCHAR(32) NOT NULL DEFAULT 'pending'"],
    ['donhang','TongTien','BIGINT NOT NULL DEFAULT 0'],
    ['donhangchitiet','SoLuong','INT NOT NULL DEFAULT 1'],
    ['donhangchitiet','DonGia','BIGINT NOT NULL DEFAULT 0'],
    ['donhangchitiet','ThanhTien','BIGINT NOT NULL DEFAULT 0'],
] as [$table,$column,$definition]) { $addColumn($table,$column,$definition); }
$pdo->exec("CREATE TABLE IF NOT EXISTS outbox_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(100) NOT NULL,
    aggregate_type VARCHAR(50) NOT NULL, aggregate_id VARCHAR(100) NOT NULL,
    payload JSON NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('DROP TABLE IF EXISTS refund_transactions');
$dropColumn = static function (string $table, string $column) use ($pdo): void {
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $check->execute([$table, $column]);
    if ((int) $check->fetchColumn()) { $pdo->exec("ALTER TABLE `$table` DROP COLUMN `$column`"); }
};
foreach (['SoTienGiamPhanBo','ThanhTienThucTra'] as $column) { $dropColumn('donhangchitiet', $column); }
foreach (['SoLuongYeuCau','SoTienHoan','XuLyBoi','VaiTroXuLy','ThoiGianXuLy'] as $column) {
    $dropColumn('return_refund_requests', $column);
}
$pdo->exec('DELETE FROM return_refund_requests');
$pdo->exec('DELETE FROM donhangchitiet WHERE ID_DonHang >= 9300');
$pdo->exec('DELETE FROM donhang WHERE ID_DonHang >= 9300');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// Old snapshot proves deterministic migration backfill and rounding.
$pdo->exec("INSERT INTO donhang (ID_DonHang,ID_KhachHang,TrangThai,GhiChu,SoTienGiam,TrangThaiThanhToan,TongTien)
    VALUES (9300,1,3,'',101,'paid',924)");
$pdo->exec("INSERT INTO donhangchitiet (ID_DonHang,ID_SanPham,SoLuong,DonGia,ThanhTien)
    VALUES (9300,1,1,333,333),(9300,2,1,667,667)");

$migration = file_get_contents(dirname(__DIR__) . '/TTTT/database/migrations/20260911_0014_refund_money_invariants.sql');
if (!is_string($migration)) { throw new RuntimeException('Cannot read refund migration.'); }
$pdo->exec($migration);

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) { $failures[] = $message; }
};
$check(allocate_order_discount([1000000], 200000) === [200000], '20% single-line allocation is incorrect.');
$check(allocate_order_discount([333,667], 101) === [33,68], 'Rounding remainder allocation is incorrect.');
$backfill = $pdo->query('SELECT SoTienGiamPhanBo,ThanhTienThucTra FROM donhangchitiet WHERE ID_DonHang=9300 ORDER BY ID_DonHangChiTiet')->fetchAll(PDO::FETCH_NUM);
$check($backfill === [[33,300],[68,599]], 'Migration did not preserve exact line allocation totals.');

// 1,000,000 product money - 20% voucher = 800,000 refundable. TongTien
// includes a 25,000 shipping snapshot that must never be refunded.
$pdo->exec("INSERT INTO donhang (ID_DonHang,ID_KhachHang,TrangThai,GhiChu,SoTienGiam,TrangThaiThanhToan,TongTien)
    VALUES (9301,1,3,'',200000,'paid',825000)");
$pdo->exec("INSERT INTO donhangchitiet
    (ID_DonHang,ID_SanPham,SoLuong,DonGia,ThanhTien,SoTienGiamPhanBo,ThanhTienThucTra)
    VALUES (9301,1,2,500000,1000000,200000,800000)");
$originalBalance = (int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn();
$pdo->exec('UPDATE khachhang SET SoDu=0 WHERE ID_KhachHang=1');
$pdo->exec("INSERT INTO return_refund_requests
    (ID_Request,ID_DonHang,ID_KhachHang,ID_SanPham,SoLuongYeuCau,TrangThai)
    VALUES (9301,9301,1,1,1,'pending'),(9302,9301,1,1,1,'pending'),(9303,9301,1,1,1,'pending')");

process_return_request($mysqli, 9301, 'approve', 3, 'admin');
$check((int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn() === 400000,
    'First partial refund is not half of product money actually paid.');
try {
    process_return_request($mysqli, 9301, 'approve', 3, 'admin');
    $failures[] = 'Retry unexpectedly processed the same refund twice.';
} catch (DomainException) {}
$check((int) $pdo->query('SELECT COUNT(*) FROM refund_transactions WHERE request_id=9301')->fetchColumn() === 1,
    'Idempotency ledger does not contain exactly one transaction.');
$check((int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn() === 400000,
    'Retry changed the wallet balance.');

process_return_request($mysqli, 9302, 'approve', 4, 'employee');
$check((int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn() === 800000,
    'Full cumulative refund is not exactly 800,000 VND.');
$check((int) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM refund_transactions WHERE order_id=9301')->fetchColumn() === 800000,
    'Refund ledger includes voucher or shipping money.');
try {
    process_return_request($mysqli, 9303, 'approve', 3, 'admin');
    $failures[] = 'Approval exceeded purchased quantity.';
} catch (DomainException) {}
$check((int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn() === 800000,
    'Rejected over-refund changed the wallet.');

$ledgerDuplicateBlocked = false;
try {
    $pdo->exec("INSERT INTO refund_transactions
        (request_id,order_id,customer_id,product_id,quantity,amount,destination,actor_id,actor_role)
        VALUES (9301,9301,1,1,1,1,'wallet',3,'admin')");
} catch (PDOException) { $ledgerDuplicateBlocked = true; }
$check($ledgerDuplicateBlocked, 'Database permits a duplicate refund transaction.');

// Two approvals racing for one purchased unit: the shared detail-row lock and
// cumulative ledger allow exactly one credit.
$pdo->exec("INSERT INTO donhang (ID_DonHang,ID_KhachHang,TrangThai,GhiChu,SoTienGiam,TrangThaiThanhToan,TongTien)
    VALUES (9302,1,3,'',20000,'paid',80000)");
$pdo->exec("INSERT INTO donhangchitiet
    (ID_DonHang,ID_SanPham,SoLuong,DonGia,ThanhTien,SoTienGiamPhanBo,ThanhTienThucTra)
    VALUES (9302,2,1,100000,100000,20000,80000)");
$pdo->exec("INSERT INTO return_refund_requests
    (ID_Request,ID_DonHang,ID_KhachHang,ID_SanPham,SoLuongYeuCau,TrangThai)
    VALUES (9310,9302,1,2,1,'pending'),(9311,9302,1,2,1,'pending')");
$balanceBeforeRace = (int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn();
$worker = __DIR__ . '/support/refund_worker.php';
$processes = [];
foreach ([9310,9311] as $racingRequest) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-d', 'extension=mysqli', $worker, (string) $racingRequest], [['pipe','r'],['pipe','w'],['pipe','w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Could not start refund concurrency worker.'); }
    fclose($pipes[0]);
    $processes[] = [$process,$pipes];
}
$workerResults = [];
foreach ($processes as [$process,$pipes]) {
    $workerResults[] = trim((string) stream_get_contents($pipes[1]));
    $workerError = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]); fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) { $failures[] = 'Concurrency worker failed: ' . $workerError; }
}
sort($workerResults);
$check($workerResults === ['approved','rejected'], 'Concurrent approvals did not produce exactly one winner.');
$check((int) $pdo->query('SELECT COUNT(*) FROM refund_transactions WHERE order_id=9302')->fetchColumn() === 1,
    'Concurrent approvals wrote more than one ledger transaction.');
$check((int) $pdo->query('SELECT SoDu FROM khachhang WHERE ID_KhachHang=1')->fetchColumn() === $balanceBeforeRace + 80000,
    'Concurrent approvals credited an incorrect wallet amount.');

$pdo->exec("DELETE FROM outbox_events WHERE aggregate_type='return_request' AND CAST(aggregate_id AS UNSIGNED) BETWEEN 9300 AND 9399");
$pdo->exec('DELETE FROM refund_transactions WHERE order_id BETWEEN 9300 AND 9399');
$pdo->exec('DELETE FROM return_refund_requests WHERE ID_DonHang BETWEEN 9300 AND 9399');
$pdo->exec('DELETE FROM donhangchitiet WHERE ID_DonHang BETWEEN 9300 AND 9399');
$pdo->exec('DELETE FROM donhang WHERE ID_DonHang BETWEEN 9300 AND 9399');
$restoreBalance = $pdo->prepare('UPDATE khachhang SET SoDu=? WHERE ID_KhachHang=1');
$restoreBalance->execute([$originalBalance]);

if ($failures !== []) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "refund_integration=ok\n";
