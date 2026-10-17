<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once __DIR__ . '/../Services/VoucherService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);
if (empty($_SESSION['user'])) {
    json_response(['ok' => false, 'error' => 'unauthenticated', 'message' => 'Vui lòng đăng nhập.'], 401);
}

$action = (string) ($_POST['action'] ?? 'apply');
if ($action === 'remove') {
    unset($_SESSION['voucher_code']);
    json_response(['ok' => true, 'message' => 'Đã bỏ mã giảm giá.']);
}

try {
    $customer = $conn->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
    $username = (string) $_SESSION['user'];
    $customer->bind_param('s', $username);
    $customer->execute();
    $userId = (int) ($customer->get_result()->fetch_assoc()['ID_KhachHang'] ?? 0);
    if ($userId < 1) {
        json_response(['ok' => false, 'error' => 'customer_not_found', 'message' => 'Không tìm thấy tài khoản.'], 404);
    }

    $quantities = [];
    foreach ((array) ($_SESSION['cart'] ?? []) as $row) {
        $productId = (int) ($row[0] ?? 0);
        $quantity = (int) ($row[3] ?? 0);
        if ($productId > 0 && $quantity > 0) {
            $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
        }
    }
    $subtotal = 0;
    $product = $conn->prepare('SELECT GiaTien FROM sanpham WHERE IDSanPham = ? LIMIT 1');
    foreach ($quantities as $productId => $quantity) {
        $product->bind_param('i', $productId);
        $product->execute();
        $row = $product->get_result()->fetch_assoc();
        if (!$row) {
            throw new VoucherException('invalid_cart', 'Một sản phẩm trong giỏ không còn tồn tại.');
        }
        $subtotal += (int) $row['GiaTien'] * $quantity;
    }

    $voucher = (new VoucherService($conn))->validate((string) ($_POST['code'] ?? ''), $userId, $subtotal);
    $_SESSION['voucher_code'] = (string) $voucher['code'];
    json_response([
        'ok' => true,
        'message' => 'Áp dụng mã giảm giá thành công.',
        'voucher' => [
            'code' => $voucher['code'],
            'name' => $voucher['name'],
            'discount_percent' => (int) $voucher['discount_percent'],
            'discount_amount' => (int) $voucher['discount_amount'],
        ],
    ]);
} catch (VoucherException $error) {
    unset($_SESSION['voucher_code']);
    json_response(['ok' => false, 'error' => $error->reason, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    error_log('Voucher validation failed: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => 'voucher_failed', 'message' => 'Không thể kiểm tra mã lúc này.'], 500);
}
