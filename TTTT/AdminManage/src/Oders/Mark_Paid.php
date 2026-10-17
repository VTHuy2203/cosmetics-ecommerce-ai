<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
require_csrf();

$adminRole = $_SESSION['admin_role'] ?? null;
if (!in_array($adminRole, ['admin', 'manager'], true)) {
    http_response_code(403);
    exit('Bạn không có quyền xác nhận thanh toán.');
}

$orderId = (int) ($_POST['order_id'] ?? 0);
if ($orderId < 1) {
    http_response_code(422);
    exit('Đơn hàng không hợp lệ.');
}

$conn->begin_transaction();
try {
    $orderStatement = $conn->prepare(
        'SELECT TrangThai, DaThanhToan, PhuongThucThanhToan, TrangThaiThanhToan,
                HanThanhToan, TongTien
         FROM donhang WHERE ID_DonHang = ? FOR UPDATE'
    );
    $orderStatement->bind_param('i', $orderId);
    $orderStatement->execute();
    $order = $orderStatement->get_result()->fetch_assoc();
    if (!$order) {
        throw new RuntimeException('Không tìm thấy đơn hàng.');
    }

    if ((int) $order['DaThanhToan'] !== 1) {
        $paymentMethod = (string) $order['PhuongThucThanhToan'];
        $isCodReady = $paymentMethod === 'cod' && (int) $order['TrangThai'] === 3;
        $isQrReady = $paymentMethod === 'qr'
            && (int) $order['TrangThai'] === 0
            && (string) $order['TrangThaiThanhToan'] === 'pending'
            && (empty($order['HanThanhToan']) || strtotime((string) $order['HanThanhToan']) >= time());
        if (!$isCodReady && !$isQrReady) {
            http_response_code(409);
            throw new DomainException('Đơn hàng không ở trạng thái cho phép xác nhận thanh toán.');
        }

        $reference = sprintf('%s-%d-%s', strtoupper($paymentMethod), $orderId, date('YmdHis'));
        $update = $conn->prepare(
            "UPDATE donhang SET DaThanhToan = 1, TrangThaiThanhToan = 'paid',
             ThanhToanLuc = NOW(), MaThamChieuThanhToan = ?
             WHERE ID_DonHang = ? AND DaThanhToan = 0"
        );
        $update->bind_param('si', $reference, $orderId);
        $update->execute();
        if ($update->affected_rows !== 1) {
            throw new RuntimeException('Trạng thái đơn hàng vừa thay đổi.');
        }

        $payload = json_encode([
            'order_id' => $orderId,
            'amount' => (int) $order['TongTien'],
            'method' => $paymentMethod,
            'confirmed_by' => (int) $_SESSION['admin_id'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $event = $conn->prepare(
            "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
             VALUES ('payment.manually_confirmed', 'order', ?, ?)"
        );
        $aggregateId = (string) $orderId;
        $event->bind_param('ss', $aggregateId, $payload);
        $event->execute();
    }

    $conn->commit();
    header('Location: All_Oders.php?payment=confirmed');
    exit;
} catch (DomainException $error) {
    $conn->rollback();
    exit($error->getMessage());
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Manual payment confirmation failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Không thể xác nhận thanh toán.');
}
