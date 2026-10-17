<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';
require_once dirname(__DIR__) . '/WebBanHangMyPham/src/Services/VoucherService.php';

$ids = [];
$result = $conn->query(
    "SELECT ID_DonHang FROM donhang
     WHERE TrangThaiThanhToan = 'pending' AND HanThanhToan IS NOT NULL AND HanThanhToan < NOW()
     ORDER BY HanThanhToan ASC LIMIT 100"
);
while ($row = $result->fetch_assoc()) {
    $ids[] = (int) $row['ID_DonHang'];
}

$expired = 0;
foreach ($ids as $orderId) {
    $conn->begin_transaction();
    try {
        $orderStmt = $conn->prepare(
            "SELECT ID_DonHang, TonKhoDaGiu FROM donhang
             WHERE ID_DonHang = ? AND TrangThaiThanhToan = 'pending' AND HanThanhToan < NOW() FOR UPDATE"
        );
        $orderStmt->bind_param('i', $orderId);
        $orderStmt->execute();
        $order = $orderStmt->get_result()->fetch_assoc();
        if (!$order) {
            $conn->rollback();
            continue;
        }

        if ((int) $order['TonKhoDaGiu'] === 1) {
            $details = $conn->prepare('SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = ?');
            $details->bind_param('i', $orderId);
            $details->execute();
            // Buffer the rows before preparing another statement on this connection,
            // otherwise mysqli fails with "Commands out of sync".
            $detailRows = $details->get_result()->fetch_all(MYSQLI_ASSOC);
            $restore = $conn->prepare('UPDATE sanpham SET SoLuong = SoLuong + ? WHERE IDSanPham = ?');
            foreach ($detailRows as $detail) {
                $quantity = (int) $detail['SoLuong'];
                $productId = (int) $detail['ID_SanPham'];
                $restore->bind_param('ii', $quantity, $productId);
                $restore->execute();
            }
        }

        (new VoucherService($conn))->releaseForOrder($orderId, 'payment_expired');

        $update = $conn->prepare(
            "UPDATE donhang SET TrangThaiThanhToan = 'expired', TonKhoDaGiu = 0 WHERE ID_DonHang = ?"
        );
        $update->bind_param('i', $orderId);
        $update->execute();

        $payload = json_encode(['order_id' => $orderId], JSON_UNESCAPED_SLASHES);
        $event = $conn->prepare(
            "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
             VALUES ('payment.expired', 'order', ?, ?)"
        );
        $aggregateId = (string) $orderId;
        $event->bind_param('ss', $aggregateId, $payload);
        $event->execute();
        $conn->commit();
        $expired++;
    } catch (Throwable $error) {
        $conn->rollback();
        error_log("Failed to expire order {$orderId}: " . $error->getMessage());
    }
}

$depositResult = $conn->query(
    "UPDATE nap_tien SET TrangThai = 2
     WHERE TrangThai = 0 AND HanThanhToan IS NOT NULL AND HanThanhToan < NOW()"
);
$expiredDeposits = $depositResult ? $conn->affected_rows : 0;

echo "expired_orders={$expired} expired_deposits={$expiredDeposits}\n";
