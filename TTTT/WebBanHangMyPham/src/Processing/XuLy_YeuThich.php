<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/security.php';
require_once __DIR__ . '/../DB_Connect.php';
require_once __DIR__ . '/../Services/CustomerEventService.php';
require_once __DIR__ . '/../Services/CustomerScoringService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo 'method_not_allowed';
    exit;
}

require_csrf();

$username = trim((string) ($_SESSION['user'] ?? ''));
if ($username === '') {
    http_response_code(401);
    echo 'chuadangnhap';
    exit;
}

$productId = filter_input(INPUT_POST, 'IDSanPham', FILTER_VALIDATE_INT);
$isDelete = false;
if (!$productId) {
    $productId = filter_input(INPUT_POST, 'del_like_product_id', FILTER_VALIDATE_INT);
    $isDelete = true;
}
if (!$productId || $productId <= 0) {
    http_response_code(422);
    echo 'invalid_product';
    exit;
}

try {
    $userStatement = $conn->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
    $userStatement->bind_param('s', $username);
    $userStatement->execute();
    $user = $userStatement->get_result()->fetch_assoc();
    $userId = (int) ($user['ID_KhachHang'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        echo 'chuadangnhap';
        exit;
    }

    if ($isDelete) {
        $statement = $conn->prepare('DELETE FROM danhsachyeuthich WHERE ID_KhachHang = ? AND ID_SanPham = ?');
        $statement->bind_param('ii', $userId, $productId);
        $statement->execute();
        if ($statement->affected_rows > 0) {
            CustomerEventService::recordMysqli($conn, $userId, CustomerEventService::sessionId(), 'wishlist_remove', ['product_id' => $productId]);
            CustomerScoringService::refreshQuietly($userId, null);
        }
        echo 'xoathanhcong';
        exit;
    }

    $check = $conn->prepare('SELECT 1 FROM danhsachyeuthich WHERE ID_KhachHang = ? AND ID_SanPham = ? LIMIT 1');
    $check->bind_param('ii', $userId, $productId);
    $check->execute();
    if ($check->get_result()->fetch_row()) {
        echo 'tontai';
        exit;
    }

    $statement = $conn->prepare('INSERT INTO danhsachyeuthich (ID_KhachHang, ID_SanPham) VALUES (?, ?)');
    $statement->bind_param('ii', $userId, $productId);
    $statement->execute();
    CustomerEventService::recordMysqli($conn, $userId, CustomerEventService::sessionId(), 'wishlist_add', ['product_id' => $productId]);
    CustomerScoringService::refreshQuietly($userId, null);
    echo 'success';
} catch (Throwable $error) {
    error_log('Wishlist operation failed: ' . $error->getMessage());
    http_response_code(500);
    echo 'error';
}
