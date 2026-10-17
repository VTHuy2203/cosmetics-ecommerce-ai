<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing'
    || getenv('MYSQL_HOST') !== '127.0.0.1' || getenv('MYSQL_PORT') !== '13316'
    || getenv('MYSQL_DB') !== 'moshop_test_audit_20260911') {
    throw new RuntimeException('Unsafe F10 catalog event environment.');
}
require_once dirname(__DIR__, 2) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';
require_once dirname(__DIR__, 2) . '/TTTT/config/catalog.php';

$action = $argv[1] ?? '';
$productId = 9902;
if ($action === 'upsert') {
    $conn->begin_transaction();
    try {
        $statement = $conn->prepare(
            "INSERT INTO sanpham(IDSanPham,TenSanPham,IDDanhMuc,IDThuongHieu,SoLuong,TrongLuongGram,GiaTien,MoTaChiTiet,AnhSanPham,NoiBat)
             VALUES(?,'Serum F10 pipeline',9901,9901,2,80,456789,'Serum kiểm thử đồng bộ','',0)
             ON DUPLICATE KEY UPDATE TenSanPham=VALUES(TenSanPham),SoLuong=2,GiaTien=456789"
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        catalog_enqueue_rag_event($conn, $productId, 'upsert');
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
} elseif ($action === 'delete') {
    if (catalog_delete($conn, 'product', $productId) !== 'deleted') {
        throw new RuntimeException('Synthetic product could not be deleted.');
    }
} else {
    throw new InvalidArgumentException('Use upsert or delete.');
}
$check = $conn->prepare(
    "SELECT id,event_type,payload FROM outbox_events WHERE aggregate_type='product' AND aggregate_id=? ORDER BY id DESC LIMIT 1"
);
$aggregate = (string) $productId;
$check->bind_param('s', $aggregate);
$check->execute();
$event = $check->get_result()->fetch_assoc();
if (!$event || $event['event_type'] !== 'catalog.changed') {
    throw new RuntimeException('Durable catalog event was not committed.');
}
echo json_encode(['event_id' => (int) $event['id'], 'action' => $action], JSON_THROW_ON_ERROR) . PHP_EOL;
