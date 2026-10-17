<?php
declare(strict_types=1);

define('MOSHOP_SKIP_SESSION', true);
define('MOSHOP_SKIP_ORIGIN_CHECK', true);
require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false], 405);
}
if (!app_bool('SEPAY_PG_ENABLED')) {
    json_response(['success' => false], 503);
}

$secret = trim((string) app_env('SEPAY_PG_SECRET_KEY', ''));
$providedSecret = trim((string) ($_SERVER['HTTP_X_SECRET_KEY'] ?? ''));
if ($secret === '' || $providedSecret === '' || !hash_equals($secret, $providedSecret)) {
    json_response(['success' => false], 401);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '' || strlen($rawBody) > 65536) {
    json_response(['success' => false], 400);
}

try {
    $payload = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    json_response(['success' => false], 400);
}

if (!is_array($payload) || !is_array($payload['order'] ?? null) || !is_array($payload['transaction'] ?? null)) {
    json_response(['success' => false], 400);
}

$notificationType = strtoupper(trim((string) ($payload['notification_type'] ?? '')));
$timestamp = filter_var($payload['timestamp'] ?? null, FILTER_VALIDATE_INT);
$remoteOrder = $payload['order'];
$transaction = $payload['transaction'];
$invoice = strtoupper(trim((string) ($remoteOrder['order_invoice_number'] ?? '')));
$externalTransactionId = trim((string) ($transaction['id'] ?? $transaction['transaction_id'] ?? ''));

if (!in_array($notificationType, ['ORDER_PAID', 'TRANSACTION_VOID'], true)
    || $timestamp === false || $timestamp <= 0
    || !preg_match('/^[A-Z0-9_-]{6,100}$/', $invoice)
    || $externalTransactionId === '') {
    json_response(['success' => false], 400);
}

$eventTransactionId = strtolower($notificationType) . ':' . hash('sha256', $externalTransactionId);
$referenceCode = mb_substr((string) ($transaction['transaction_id'] ?? $externalTransactionId), 0, 100);
$gatewayOrderId = mb_substr((string) ($remoteOrder['id'] ?? ''), 0, 100);
$orderAmount = parseVndAmount($remoteOrder['order_amount'] ?? null);
$transactionAmount = parseVndAmount($transaction['transaction_amount'] ?? null);
$orderCurrency = strtoupper(trim((string) ($remoteOrder['order_currency'] ?? '')));
$transactionCurrency = strtoupper(trim((string) ($transaction['transaction_currency'] ?? '')));
$remoteOrderStatus = strtoupper(trim((string) ($remoteOrder['order_status'] ?? '')));
$transactionStatus = strtoupper(trim((string) ($transaction['transaction_status'] ?? '')));

$conn->begin_transaction();
try {
    $initialStatus = $notificationType === 'TRANSACTION_VOID' ? 'voided' : 'received';
    $accountNumber = 'sepay-payment-gateway';
    $insert = $conn->prepare(
        'INSERT IGNORE INTO payment_transactions
         (provider, provider_transaction_id, reference_code, payment_code, transfer_type, amount, account_number, status, payload)
         VALUES (\'sepay_pg\', ?, ?, NULL, \'in\', ?, ?, ?, ?)'
    );
    $recordedAmount = max(0, $transactionAmount ?? $orderAmount ?? 0);
    $insert->bind_param('ssisss', $eventTransactionId, $referenceCode, $recordedAmount, $accountNumber, $initialStatus, $rawBody);
    $insert->execute();
    if ($insert->affected_rows === 0) {
        $conn->commit();
        json_response(['success' => true]);
    }

    $orderStmt = $conn->prepare(
        'SELECT ID_DonHang, ID_KhachHang, TongTien, TrangThaiThanhToan, TonKhoDaGiu
         FROM donhang
         WHERE SePayInvoiceNumber = ? AND PhuongThucThanhToan = \'sepay_pg\' LIMIT 1 FOR UPDATE'
    );
    $orderStmt->bind_param('s', $invoice);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    if (!$order) {
        updateGatewayTransaction($conn, 'unmatched', null, $eventTransactionId);
        $conn->commit();
        json_response(['success' => true]);
    }

    $orderId = (int) $order['ID_DonHang'];
    $currentStatus = (string) $order['TrangThaiThanhToan'];
    $transactionRecordStatus = $initialStatus;

    if ($notificationType === 'ORDER_PAID') {
        $isApproved = $remoteOrderStatus === 'CAPTURED'
            && $transactionStatus === 'APPROVED'
            && $orderCurrency === 'VND'
            && $transactionCurrency === 'VND';
        $amountMatches = $orderAmount !== null
            && $transactionAmount !== null
            && $orderAmount === $transactionAmount
            && $orderAmount === (int) $order['TongTien'];

        if (!$isApproved || !$amountMatches) {
            $transactionRecordStatus = $amountMatches ? 'rejected' : 'amount_mismatch';
            markGatewayReview($conn, $orderId, $referenceCode, $gatewayOrderId, $isApproved);
            enqueueGatewayEvent($conn, 'payment.review_required', $orderId, (int) $order['ID_KhachHang'], [
                'reason' => $amountMatches ? 'invalid_gateway_status' : 'amount_mismatch',
                'transaction_id' => $referenceCode,
            ]);
        } elseif ($currentStatus === 'paid') {
            $transactionRecordStatus = 'duplicate';
        } elseif ($currentStatus === 'pending' && (int) $order['TonKhoDaGiu'] === 1) {
            $pay = $conn->prepare(
                "UPDATE donhang
                 SET DaThanhToan = 1, TrangThaiThanhToan = 'paid', ThanhToanLuc = NOW(),
                     MaThamChieuThanhToan = ?, SePayGatewayOrderId = ?
                 WHERE ID_DonHang = ? AND TrangThaiThanhToan = 'pending'"
            );
            $pay->bind_param('ssi', $referenceCode, $gatewayOrderId, $orderId);
            $pay->execute();
            if ($pay->affected_rows !== 1) {
                throw new RuntimeException('Gateway order was concurrently updated.');
            }
            $transactionRecordStatus = 'matched';
            enqueueGatewayEvent($conn, 'payment.succeeded', $orderId, (int) $order['ID_KhachHang'], [
                'provider' => 'sepay_pg',
                'transaction_id' => $referenceCode,
                'amount' => $transactionAmount,
            ]);
        } else {
            $transactionRecordStatus = 'review';
            markGatewayReview($conn, $orderId, $referenceCode, $gatewayOrderId, true);
            enqueueGatewayEvent($conn, 'payment.review_required', $orderId, (int) $order['ID_KhachHang'], [
                'reason' => 'payment_received_after_order_closed',
                'transaction_id' => $referenceCode,
            ]);
        }
    } else {
        if ($currentStatus === 'pending') {
            releaseGatewayStock($conn, $orderId, (int) $order['TonKhoDaGiu']);
            $fail = $conn->prepare(
                "UPDATE donhang SET TrangThaiThanhToan = 'failed', TonKhoDaGiu = 0,
                 SePayGatewayOrderId = ? WHERE ID_DonHang = ? AND TrangThaiThanhToan = 'pending'"
            );
            $fail->bind_param('si', $gatewayOrderId, $orderId);
            $fail->execute();
        } elseif ($currentStatus === 'paid') {
            markGatewayReview($conn, $orderId, $referenceCode, $gatewayOrderId, true);
            $transactionRecordStatus = 'review';
        }
        enqueueGatewayEvent($conn, 'payment.voided', $orderId, (int) $order['ID_KhachHang'], [
            'transaction_id' => $referenceCode,
            'previous_status' => $currentStatus,
        ]);
    }

    updateGatewayTransaction($conn, $transactionRecordStatus, $orderId, $eventTransactionId);
    $conn->commit();
    json_response(['success' => true]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('SePay Payment Gateway IPN failed: ' . $error->getMessage());
    json_response(['success' => false], 500);
}

function parseVndAmount(mixed $value): ?int
{
    $value = trim((string) $value);
    if (!preg_match('/^(0|[1-9][0-9]{0,14})(?:\.0{1,2})?$/', $value)) {
        return null;
    }
    $whole = explode('.', $value, 2)[0];
    $amount = filter_var($whole, FILTER_VALIDATE_INT);
    return $amount === false ? null : $amount;
}

function updateGatewayTransaction(mysqli $conn, string $status, ?int $orderId, string $eventTransactionId): void
{
    $update = $conn->prepare(
        'UPDATE payment_transactions SET status = ?, order_id = ?, processed_at = NOW()
         WHERE provider = \'sepay_pg\' AND provider_transaction_id = ?'
    );
    $update->bind_param('sis', $status, $orderId, $eventTransactionId);
    $update->execute();
}

function markGatewayReview(
    mysqli $conn,
    int $orderId,
    string $referenceCode,
    string $gatewayOrderId,
    bool $paymentObserved
): void
{
    $observed = $paymentObserved ? 1 : 0;
    $review = $conn->prepare(
        "UPDATE donhang SET DaThanhToan = GREATEST(DaThanhToan, ?), TrangThaiThanhToan = 'review',
         ThanhToanLuc = CASE WHEN ? = 1 THEN COALESCE(ThanhToanLuc, NOW()) ELSE ThanhToanLuc END,
         MaThamChieuThanhToan = ?, SePayGatewayOrderId = ? WHERE ID_DonHang = ?"
    );
    $review->bind_param('iissi', $observed, $observed, $referenceCode, $gatewayOrderId, $orderId);
    $review->execute();
}

/** @param array<string,mixed> $extra */
function enqueueGatewayEvent(mysqli $conn, string $eventType, int $orderId, int $userId, array $extra): void
{
    $payload = json_encode(
        array_merge(['order_id' => $orderId, 'user_id' => $userId, 'provider' => 'sepay_pg'], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    $event = $conn->prepare(
        'INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload) VALUES (?, \'order\', ?, ?)'
    );
    $aggregateId = (string) $orderId;
    $event->bind_param('sss', $eventType, $aggregateId, $payload);
    $event->execute();
}

function releaseGatewayStock(mysqli $conn, int $orderId, int $stockHeld): void
{
    if ($stockHeld !== 1) {
        return;
    }
    $details = $conn->prepare('SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = ?');
    $details->bind_param('i', $orderId);
    $details->execute();
    $restore = $conn->prepare('UPDATE sanpham SET SoLuong = SoLuong + ? WHERE IDSanPham = ?');
    foreach ($details->get_result() as $detail) {
        $quantity = (int) $detail['SoLuong'];
        $productId = (int) $detail['ID_SanPham'];
        $restore->bind_param('ii', $quantity, $productId);
        $restore->execute();
    }
}
