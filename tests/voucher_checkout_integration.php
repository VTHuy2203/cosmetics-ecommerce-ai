<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Services/CheckoutService.php';

$voucherId = 0;
$orderId = 0;
$eventId = 0;
$productId = 0;
try {
    $admin = $conn->query("SELECT ID_KhachHang FROM khachhang WHERE Role IN ('admin','manager') ORDER BY ID_KhachHang LIMIT 1")->fetch_assoc();
    $customer = $conn->query("SELECT ID_KhachHang, TenDangNhap FROM khachhang WHERE Role IS NULL OR Role = '' OR Role = 'customer' ORDER BY ID_KhachHang LIMIT 1")->fetch_assoc();
    $product = $conn->query('SELECT IDSanPham, GiaTien, SoLuong FROM sanpham WHERE SoLuong > 0 AND GiaTien > 0 ORDER BY IDSanPham LIMIT 1')->fetch_assoc();
    if (!$admin || !$customer || !$product) {
        throw new RuntimeException('Integration fixtures are unavailable.');
    }

    $productId = (int) $product['IDSanPham'];
    $subtotal = (int) $product['GiaTien'];
    $code = 'TEST' . strtoupper(bin2hex(random_bytes(4)));
    $name = 'Automated checkout test';
    $description = 'Temporary record';
    $percent = 10;
    $quantity = 2;
    $limit = 1;
    $minimum = 0;
    $maximum = null;
    $starts = date('Y-m-d H:i:s', time() - 60);
    $ends = date('Y-m-d H:i:s', time() + 3600);
    $status = 'active';
    $announce = 0;
    $createdBy = (int) $admin['ID_KhachHang'];
    $insert = $conn->prepare(
        'INSERT INTO vouchers(code,name,description,discount_percent,quantity_total,per_user_limit,min_order_amount,
         max_discount_amount,starts_at,ends_at,status,announce_on_publish,published_at,created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)'
    );
    $insert->bind_param(
        'sssiiiiisssii',
        $code, $name, $description, $percent, $quantity, $limit, $minimum, $maximum,
        $starts, $ends, $status, $announce, $createdBy
    );
    $insert->execute();
    $voucherId = (int) $conn->insert_id;

    $claim = $conn->prepare("INSERT INTO voucher_claims(voucher_id,user_id,status) VALUES (?,?,'claimed')");
    $claimUserId = (int) $customer['ID_KhachHang'];
    $claim->bind_param('ii', $voucherId, $claimUserId);
    $claim->execute();

    $expectedDiscount = VoucherService::calculateDiscount($subtotal, $percent, null);
    $expectedShipping = $subtotal < 500000 ? 25000 : 0;
    $expectedAmount = $subtotal - $expectedDiscount + $expectedShipping;
    $order = (new CheckoutService($conn))->create(
        (string) $customer['TenDangNhap'],
        [[$productId, 'test', '', 1, $subtotal]],
        'cod',
        ['HoVaTen' => 'Khach Thu Nghiem', 'Email' => 'integration@example.test', 'Sdt' => '0900000000', 'DiaChi' => 'Da Nang'],
        'Automated voucher integration test',
        $code
    );
    $orderId = (int) $order['order_id'];
    if ((int) $order['discount_amount'] !== $expectedDiscount || (int) $order['amount'] !== $expectedAmount) {
        throw new RuntimeException('Checkout returned an incorrect voucher total.');
    }
    $stored = $conn->query("SELECT TongTien, SoTienGiam, MaVoucher FROM donhang WHERE ID_DonHang = {$orderId}")->fetch_assoc();
    if (!$stored || (int) $stored['TongTien'] !== $expectedAmount || (int) $stored['SoTienGiam'] !== $expectedDiscount || $stored['MaVoucher'] !== $code) {
        throw new RuntimeException('Stored order voucher values are incorrect.');
    }
    $claimState = $conn->query("SELECT status, order_id FROM voucher_claims WHERE voucher_id = {$voucherId} AND user_id = {$claimUserId}")->fetch_assoc();
    if (!$claimState || $claimState['status'] !== 'redeemed' || (int) $claimState['order_id'] !== $orderId) {
        throw new RuntimeException('Claimed voucher was not marked as redeemed.');
    }

    $conn->begin_transaction();
    $released = (new VoucherService($conn))->releaseForOrder($orderId, 'integration_test');
    $releaseState = $conn->query(
        "SELECT v.quantity_used, r.released_at
         FROM vouchers v JOIN voucher_redemptions r ON r.voucher_id = v.id
         WHERE r.order_id = {$orderId}"
    )->fetch_assoc();
    if (!$released || !$releaseState || (int) $releaseState['quantity_used'] !== 0 || $releaseState['released_at'] === null) {
        $conn->rollback();
        throw new RuntimeException('Voucher release is not idempotent or did not restore quantity.');
    }
    $restoredClaim = $conn->query("SELECT status, order_id FROM voucher_claims WHERE voucher_id = {$voucherId} AND user_id = {$claimUserId}")->fetch_assoc();
    if (!$restoredClaim || $restoredClaim['status'] !== 'claimed' || $restoredClaim['order_id'] !== null) {
        $conn->rollback();
        throw new RuntimeException('Released order did not restore the voucher to the customer wallet.');
    }
    if ((new VoucherService($conn))->releaseForOrder($orderId, 'integration_test_duplicate')) {
        $conn->rollback();
        throw new RuntimeException('Voucher was released more than once.');
    }
    $conn->rollback();
    echo "voucher_checkout_integration=ok order={$orderId}\n";
} finally {
    if ($orderId > 0) {
        $event = $conn->query("SELECT id FROM outbox_events WHERE aggregate_type = 'order' AND CAST(aggregate_id AS UNSIGNED) = {$orderId} ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $eventId = (int) ($event['id'] ?? 0);
        if ($eventId > 0) {
            $conn->query("DELETE FROM customer_notifications WHERE outbox_event_id = {$eventId}");
            $conn->query("DELETE FROM admin_notifications WHERE outbox_event_id = {$eventId}");
            $conn->query("DELETE FROM outbox_events WHERE id = {$eventId}");
        }
        $eventTable = $conn->query("SHOW TABLES LIKE 'customer_events'")->fetch_row();
        if ($eventTable) {
            $conn->query("DELETE FROM customer_events WHERE event_type = 'order_created' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.order_id')) AS UNSIGNED) = {$orderId}");
        }
        $conn->query("DELETE FROM voucher_redemptions WHERE order_id = {$orderId}");
        $conn->query("DELETE FROM donhangchitiet WHERE ID_DonHang = {$orderId}");
        $conn->query("DELETE FROM donhang WHERE ID_DonHang = {$orderId}");
        if ($productId > 0) {
            $conn->query("UPDATE sanpham SET SoLuong = SoLuong + 1 WHERE IDSanPham = {$productId}");
        }
    }
    if ($voucherId > 0) {
        $conn->query("DELETE FROM vouchers WHERE id = {$voucherId}");
    }
}
