<?php
declare(strict_types=1);

define('MOSHOP_SKIP_SESSION', true);
require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false], 405);
}

$secret = app_env('SEPAY_WEBHOOK_SECRET', '') ?? '';
$expectedAccount = preg_replace('/\s+/', '', app_env('SEPAY_ACCOUNT_NUMBER', '') ?? '');
if ($secret === '' || $expectedAccount === '') {
    error_log('SePay webhook is not configured.');
    json_response(['success' => false], 503);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '' || strlen($rawBody) > 65536) {
    json_response(['success' => false], 400);
}

$signature = (string) ($_SERVER['HTTP_X_SEPAY_SIGNATURE'] ?? '');
$timestamp = (int) ($_SERVER['HTTP_X_SEPAY_TIMESTAMP'] ?? 0);
if (!sepay_signature_is_valid($rawBody, $secret, $signature, $timestamp)) {
    json_response(['success' => false], 401);
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)
    || !isset($payload['id'], $payload['transferType'], $payload['transferAmount'], $payload['accountNumber'])) {
    json_response(['success' => false], 400);
}

$transactionId = (string) $payload['id'];
$transferType = strtolower((string) $payload['transferType']);
$amount = filter_var($payload['transferAmount'], FILTER_VALIDATE_INT);
$account = preg_replace('/\s+/', '', (string) $payload['accountNumber']);
$paymentCode = strtoupper(trim((string) ($payload['code'] ?? '')));
$referenceCode = mb_substr((string) ($payload['referenceCode'] ?? ''), 0, 100);

if (!in_array($transferType, ['in', 'out'], true) || $amount === false || $amount <= 0) {
    json_response(['success' => false], 400);
}

$conn->begin_transaction();
try {
    $initialStatus = ($transferType === 'in' && hash_equals($expectedAccount, $account)) ? 'received' : 'rejected';
    $insert = $conn->prepare(
        'INSERT IGNORE INTO payment_transactions
         (provider, provider_transaction_id, reference_code, payment_code, transfer_type, amount, account_number, status, payload)
         VALUES (\'sepay\', ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->bind_param(
        'ssssisss',
        $transactionId,
        $referenceCode,
        $paymentCode,
        $transferType,
        $amount,
        $account,
        $initialStatus,
        $rawBody
    );
    $insert->execute();
    if ($insert->affected_rows === 0) {
        $conn->commit();
        json_response(['success' => true]);
    }

    if ($initialStatus === 'rejected' || !preg_match('/^[A-Z0-9]{4,16}$/', $paymentCode)) {
        $status = $initialStatus === 'rejected' ? 'rejected' : 'unmatched';
        $update = $conn->prepare(
            'UPDATE payment_transactions SET status = ?, processed_at = NOW() WHERE provider = \'sepay\' AND provider_transaction_id = ?'
        );
        $update->bind_param('ss', $status, $transactionId);
        $update->execute();
        $conn->commit();
        json_response(['success' => true]);
    }

    $orderStmt = $conn->prepare(
        "SELECT ID_DonHang, TongTien, TrangThaiThanhToan, HanThanhToan
         FROM donhang WHERE MaThanhToan = ? FOR UPDATE"
    );
    $orderStmt->bind_param('s', $paymentCode);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();

    $status = 'unmatched';
    $orderId = null;
    $depositId = null;
    if ($order) {
        $orderId = (int) $order['ID_DonHang'];
        if ((int) $order['TongTien'] !== $amount) {
            $status = 'amount_mismatch';
        } elseif ($order['TrangThaiThanhToan'] === 'paid') {
            $status = 'duplicate';
        } elseif ($order['TrangThaiThanhToan'] === 'pending'
            && (!$order['HanThanhToan'] || strtotime($order['HanThanhToan']) >= time())) {
            $status = 'matched';
            $pay = $conn->prepare(
                "UPDATE donhang SET DaThanhToan = 1, TrangThaiThanhToan = 'paid', ThanhToanLuc = NOW(),
                 MaThamChieuThanhToan = ? WHERE ID_DonHang = ? AND TrangThaiThanhToan = 'pending'"
            );
            $pay->bind_param('si', $referenceCode, $orderId);
            $pay->execute();

            $eventPayload = json_encode([
                'order_id' => $orderId,
                'provider' => 'sepay',
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $event = $conn->prepare(
                "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
                 VALUES ('payment.succeeded', 'order', ?, ?)"
            );
            $aggregateId = (string) $orderId;
            $event->bind_param('ss', $aggregateId, $eventPayload);
            $event->execute();
        }
    } else {
        $depositStmt = $conn->prepare(
            'SELECT ID_Nap, ID_KhachHang, SoTien, TrangThai, HanThanhToan
             FROM nap_tien WHERE MaThanhToan = ? FOR UPDATE'
        );
        $depositStmt->bind_param('s', $paymentCode);
        $depositStmt->execute();
        $deposit = $depositStmt->get_result()->fetch_assoc();

        if ($deposit) {
            $depositId = (int) $deposit['ID_Nap'];
            if ((int) $deposit['SoTien'] !== $amount) {
                $status = 'amount_mismatch';
            } elseif ((int) $deposit['TrangThai'] === 1) {
                $status = 'duplicate';
            } elseif ((int) $deposit['TrangThai'] === 0
                && (!$deposit['HanThanhToan'] || strtotime($deposit['HanThanhToan']) >= time())) {
                $status = 'matched';
                $complete = $conn->prepare(
                    'UPDATE nap_tien SET TrangThai = 1, MaThamChieuThanhToan = ?, ThanhToanLuc = NOW()
                     WHERE ID_Nap = ? AND TrangThai = 0'
                );
                $complete->bind_param('si', $referenceCode, $depositId);
                $complete->execute();
                if ($complete->affected_rows !== 1) {
                    throw new RuntimeException('Deposit was concurrently completed.');
                }

                $userId = (int) $deposit['ID_KhachHang'];
                $credit = $conn->prepare('UPDATE khachhang SET SoDu = SoDu + ? WHERE ID_KhachHang = ?');
                $credit->bind_param('ii', $amount, $userId);
                $credit->execute();
                if ($credit->affected_rows !== 1) {
                    throw new RuntimeException('Deposit customer no longer exists.');
                }

                $eventPayload = json_encode([
                    'deposit_id' => $depositId,
                    'user_id' => $userId,
                    'provider' => 'sepay',
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $event = $conn->prepare(
                    "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
                     VALUES ('wallet.deposit_confirmed', 'deposit', ?, ?)"
                );
                $aggregateId = (string) $depositId;
                $event->bind_param('ss', $aggregateId, $eventPayload);
                $event->execute();
            }
        }
    }

    $update = $conn->prepare(
        'UPDATE payment_transactions SET status = ?, order_id = ?, deposit_id = ?, processed_at = NOW()
         WHERE provider = \'sepay\' AND provider_transaction_id = ?'
    );
    $update->bind_param('siis', $status, $orderId, $depositId, $transactionId);
    $update->execute();
    $conn->commit();
    json_response(['success' => true]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('SePay webhook failed: ' . $error->getMessage());
    json_response(['success' => false], 500);
}
