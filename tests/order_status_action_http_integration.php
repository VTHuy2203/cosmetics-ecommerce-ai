<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/DB_Connect.php';

$admin = $conn->query(
    "SELECT ID_KhachHang, TenDangNhap, Role FROM khachhang
     WHERE Role IN ('admin','manager') ORDER BY ID_KhachHang LIMIT 1"
)->fetch_assoc();
$customer = $conn->query(
    "SELECT ID_KhachHang, HoVaTen, Email, SDT, DiaChi FROM khachhang
     WHERE Role NOT IN ('admin','manager','employee','shipper') ORDER BY ID_KhachHang LIMIT 1"
)->fetch_assoc();
if (!$admin || !$customer) {
    fwrite(STDERR, "Order action fixtures are unavailable.\n");
    exit(1);
}

$insert = $conn->prepare(
    "INSERT INTO donhang
     (ID_KhachHang, HoVaTen, Email, SDT, DiaChi, TongTien, TrangThai, DaThanhToan,
      GhiChu, PhuongThucThanhToan, TrangThaiThanhToan, TonKhoDaGiu)
     VALUES (?, ?, ?, ?, ?, 10000, 0, 0, 'HTTP integration test', 'cod', 'cod_pending', 0)"
);
$customerId = (int) $customer['ID_KhachHang'];
$insert->bind_param(
    'issss',
    $customerId,
    $customer['HoVaTen'],
    $customer['Email'],
    $customer['SDT'],
    $customer['DiaChi']
);
$insert->execute();
$orderId = (int) $conn->insert_id;

$sessionId = 'orderaction' . bin2hex(random_bytes(8));
$csrf = bin2hex(random_bytes(32));
$sessionPath = 'C:/laragon/tmp';

try {
    session_save_path($sessionPath);
    session_name('MOSHOPSESSID');
    session_id($sessionId);
    session_start();
    $_SESSION = [
        'admin_id' => (int) $admin['ID_KhachHang'],
        'admin_user' => (string) $admin['TenDangNhap'],
        'admin_role' => (string) $admin['Role'],
        'admin_is_admin' => true,
        '_csrf_token' => $csrf,
    ];
    session_write_close();

    $request = curl_init('http://localhost/TTTT/AdminManage/src/Oders/Order_Status_Action.php');
    curl_setopt_array($request, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'csrf_token' => $csrf,
            'order_id' => $orderId,
            'action' => 'approve',
            'return_to' => 'all',
        ]),
        CURLOPT_COOKIE => 'MOSHOPSESSID=' . $sessionId,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = (string) curl_exec($request);
    $httpStatus = (int) curl_getinfo($request, CURLINFO_HTTP_CODE);
    $curlError = curl_error($request);
    curl_close($request);

    $decoded = json_decode($body, true);
    if ($curlError !== '' || $httpStatus !== 200 || !is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
        fwrite(STDERR, "Order action HTTP failed: status={$httpStatus}; curl={$curlError}; body={$body}\n");
        exit(1);
    }
    $state = $conn->query("SELECT TrangThai FROM donhang WHERE ID_DonHang = {$orderId}")->fetch_assoc();
    if ((int) ($state['TrangThai'] ?? -99) !== 1) {
        fwrite(STDERR, "Order action did not update the database.\n");
        exit(1);
    }
    echo "order_status_action_http_integration=ok\n";
} finally {
    $conn->query("DELETE FROM customer_notifications WHERE user_id = {$customerId} AND message LIKE '%#{$orderId}%'");
    $conn->query("DELETE FROM outbox_events WHERE aggregate_type = 'order' AND aggregate_id = '{$orderId}'");
    $conn->query("DELETE FROM donhang WHERE ID_DonHang = {$orderId}");
    @unlink($sessionPath . '/sess_' . $sessionId);

    $queuePath = dirname(__DIR__) . '/TTTT/WebSocketServer/message_queue.json';
    if (is_file($queuePath)) {
        $queue = json_decode((string) file_get_contents($queuePath), true);
        if (is_array($queue)) {
            $queue = array_values(array_filter($queue, static function (array $entry) use ($orderId): bool {
                return (int) ($entry['message']['data']['order_id'] ?? 0) !== $orderId;
            }));
            file_put_contents($queuePath, json_encode($queue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
    }
}
