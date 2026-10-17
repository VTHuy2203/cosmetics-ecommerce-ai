<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';

$apply = in_array('--apply', $argv, true);
$result = $conn->query('SELECT ID_KhachHang, MatKhau FROM khachhang ORDER BY ID_KhachHang');
$legacy = [];
while ($row = $result->fetch_assoc()) {
    if ((password_get_info((string) $row['MatKhau'])['algoName'] ?? 'unknown') === 'unknown') {
        $legacy[] = ['id' => (int) $row['ID_KhachHang'], 'password' => (string) $row['MatKhau']];
    }
}

echo 'legacy_passwords=' . count($legacy) . "\n";
if (!$apply) {
    echo "dry-run only; pass --apply to hash these values in place\n";
    exit;
}

$update = $conn->prepare('UPDATE khachhang SET MatKhau = ? WHERE ID_KhachHang = ? AND MatKhau = ?');
$updated = 0;
foreach ($legacy as $account) {
    $hash = password_hash($account['password'], PASSWORD_DEFAULT);
    $accountId = $account['id'];
    $legacyPassword = $account['password'];
    $update->bind_param('sis', $hash, $accountId, $legacyPassword);
    $update->execute();
    $updated += $update->affected_rows;
}
echo "updated={$updated}\n";
