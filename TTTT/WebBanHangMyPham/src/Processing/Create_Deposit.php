<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once __DIR__ . '/../Layouts/VietQR_Helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!isset($_SESSION['user'])) {
    json_response(['success' => false, 'message' => 'Vui lòng đăng nhập lại.'], 401);
}
require_csrf();

$amount = filter_input(INPUT_POST, 'SoTien', FILTER_VALIDATE_INT);
$allowedAmounts = [10000, 20000, 50000, 100000, 200000, 500000];
if ($amount === false || !in_array($amount, $allowedAmounts, true)) {
    json_response(['success' => false, 'message' => 'Mức nạp tiền không hợp lệ.'], 422);
}

$bankBin = trim((string) app_env('VIETQR_BANK_BIN', ''));
$accountNo = preg_replace('/\s+/', '', (string) app_env('VIETQR_ACCOUNT_NO', ''));
$accountName = trim((string) app_env('VIETQR_ACCOUNT_NAME', ''));
if ($bankBin === '' || $accountNo === '' || $accountName === '') {
    json_response(['success' => false, 'message' => 'Kênh nạp tiền đang được cấu hình.'], 503);
}

$userStmt = $conn->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
$username = (string) $_SESSION['user'];
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) {
    json_response(['success' => false, 'message' => 'Tài khoản không tồn tại.'], 401);
}
$userId = (int) $user['ID_KhachHang'];

$pendingStmt = $conn->prepare(
    'SELECT COUNT(*) AS total FROM nap_tien WHERE ID_KhachHang = ? AND TrangThai = 0 AND HanThanhToan >= NOW()'
);
$pendingStmt->bind_param('i', $userId);
$pendingStmt->execute();
if ((int) $pendingStmt->get_result()->fetch_assoc()['total'] >= 3) {
    json_response(['success' => false, 'message' => 'Bạn đang có quá nhiều yêu cầu nạp tiền chưa hoàn tất.'], 429);
}

$ttlMinutes = max(5, min(60, (int) app_env('PAYMENT_TTL_MINUTES', '15')));
$paymentPrefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) app_env('SEPAY_PAYMENT_PREFIX', 'MO')));
$paymentPrefix = substr($paymentPrefix !== '' ? $paymentPrefix : 'MO', 0, 5);
$conn->begin_transaction();
try {
    $insert = $conn->prepare(
        'INSERT INTO nap_tien (ID_KhachHang, SoTien, NoiDung, MaThanhToan, TrangThai, HanThanhToan)
         VALUES (?, ?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    );

    $paymentCode = '';
    $note = '';
    $inserted = false;
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $paymentCode = $paymentPrefix . 'N' . strtoupper(bin2hex(random_bytes(4)));
        $note = 'NAP ' . $paymentCode;
        $insert->bind_param('iissi', $userId, $amount, $note, $paymentCode, $ttlMinutes);
        try {
            $insert->execute();
            $inserted = true;
            break;
        } catch (mysqli_sql_exception $error) {
            if ((int) $error->getCode() !== 1062) {
                throw $error;
            }
        }
    }
    if (!$inserted) {
        throw new RuntimeException('Could not allocate a unique deposit payment code.');
    }

    $depositId = (int) $conn->insert_id;
    $eventPayload = json_encode(
        ['deposit_id' => $depositId, 'user_id' => $userId, 'amount' => $amount, 'payment_code' => $paymentCode],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $event = $conn->prepare(
        "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
         VALUES ('wallet.deposit_requested', 'deposit', ?, ?)"
    );
    $aggregateId = (string) $depositId;
    $event->bind_param('ss', $aggregateId, $eventPayload);
    $event->execute();
    $conn->commit();

    json_response([
        'success' => true,
        'deposit_id' => $depositId,
        'payment_code' => $paymentCode,
        'amount' => $amount,
        'expires_in_seconds' => $ttlMinutes * 60,
        'account_name' => $accountName,
        'account_number' => $accountNo,
        'qr_url' => getVietQRUrl($accountNo, $bankBin, $amount, $note),
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Create deposit failed: ' . $error->getMessage());
    json_response(['success' => false, 'message' => 'Không thể tạo yêu cầu nạp tiền.'], 500);
}
