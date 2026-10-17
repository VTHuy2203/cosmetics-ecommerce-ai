<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'message' => 'Phương thức không hợp lệ.'], 405);
}
require_csrf(true);
if (empty($_SESSION['user'])) {
    json_response(['ok' => false, 'error' => 'unauthenticated', 'message' => 'Vui lòng đăng nhập để nhận voucher.'], 401);
}

$voucherId = filter_input(INPUT_POST, 'voucher_id', FILTER_VALIDATE_INT) ?: 0;
if ($voucherId < 1) {
    json_response(['ok' => false, 'message' => 'Voucher không hợp lệ.'], 422);
}

$conn->begin_transaction();
try {
    $customer = $conn->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
    $username = (string) $_SESSION['user'];
    $customer->bind_param('s', $username);
    $customer->execute();
    $userId = (int) ($customer->get_result()->fetch_assoc()['ID_KhachHang'] ?? 0);
    if ($userId < 1) {
        throw new RuntimeException('Không tìm thấy tài khoản.');
    }

    $voucherStatement = $conn->prepare(
        "SELECT id, code, quantity_total, quantity_used, per_user_limit FROM vouchers
         WHERE id = ? AND status = 'active' AND starts_at <= NOW() AND ends_at > NOW() LIMIT 1 FOR UPDATE"
    );
    $voucherStatement->bind_param('i', $voucherId);
    $voucherStatement->execute();
    $voucher = $voucherStatement->get_result()->fetch_assoc();
    if (!$voucher) {
        throw new DomainException('Voucher chưa hoạt động hoặc đã hết hạn.');
    }

    $existing = $conn->prepare('SELECT status FROM voucher_claims WHERE voucher_id = ? AND user_id = ? LIMIT 1');
    $existing->bind_param('ii', $voucherId, $userId);
    $existing->execute();
    $existingClaim = $existing->get_result()->fetch_assoc();
    if ($existingClaim && $existingClaim['status'] === 'claimed') {
        $conn->commit();
        json_response(['ok' => true, 'already_claimed' => true, 'code' => $voucher['code'], 'message' => 'Voucher đã có trong kho của bạn.']);
    }
    if ($existingClaim && $existingClaim['status'] === 'redeemed') {
        $usage = $conn->prepare(
            'SELECT COUNT(*) AS total FROM voucher_redemptions WHERE voucher_id = ? AND user_id = ? AND released_at IS NULL'
        );
        $usage->bind_param('ii', $voucherId, $userId);
        $usage->execute();
        $used = (int) ($usage->get_result()->fetch_assoc()['total'] ?? 0);
        if ($used >= (int) $voucher['per_user_limit']) {
            throw new DomainException('Bạn đã sử dụng hết lượt của voucher này.');
        }
    }

    $claimedCount = $conn->prepare("SELECT COUNT(*) AS total FROM voucher_claims WHERE voucher_id = ? AND status = 'claimed'");
    $claimedCount->bind_param('i', $voucherId);
    $claimedCount->execute();
    $reserved = (int) ($claimedCount->get_result()->fetch_assoc()['total'] ?? 0);
    if ((int) $voucher['quantity_used'] + $reserved >= (int) $voucher['quantity_total']) {
        throw new DomainException('Voucher đã được nhận hết.');
    }

    $claim = $conn->prepare(
        "INSERT INTO voucher_claims(voucher_id,user_id,status) VALUES (?,?,'claimed')
         ON DUPLICATE KEY UPDATE status='claimed', claimed_at=NOW(), redeemed_at=NULL, order_id=NULL"
    );
    $claim->bind_param('ii', $voucherId, $userId);
    $claim->execute();
    $conn->commit();
    json_response(['ok' => true, 'code' => $voucher['code'], 'message' => 'Đã lưu voucher vào kho của bạn.']);
} catch (DomainException $error) {
    $conn->rollback();
    json_response(['ok' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Voucher claim failed: ' . $error->getMessage());
    json_response(['ok' => false, 'message' => 'Không thể nhận voucher lúc này.'], 500);
}
