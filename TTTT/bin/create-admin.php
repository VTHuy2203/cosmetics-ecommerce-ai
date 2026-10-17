<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';

$username = app_env('ADMIN_BOOTSTRAP_USERNAME');
$password = app_env('ADMIN_BOOTSTRAP_PASSWORD');
$email = app_env('ADMIN_BOOTSTRAP_EMAIL');
if (!$username || !preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)
    || !$password || strlen($password) < 12
    || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Set ADMIN_BOOTSTRAP_USERNAME, ADMIN_BOOTSTRAP_PASSWORD (12+ chars), and ADMIN_BOOTSTRAP_EMAIL.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';
$name = 'Administrator';
$phone = '';
$address = '';
$stmt = $conn->prepare(
    'INSERT INTO khachhang(TenDangNhap, MatKhau, HoVaTen, Email, SDT, DiaChi, Role)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('sssssss', $username, $hash, $name, $email, $phone, $address, $role);
$stmt->execute();
echo "Admin account provisioned. Clear the ADMIN_BOOTSTRAP_* variables now.\n";
