<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/WebBanHangMyPham/src/DB_Connect.php';

$processed = 0;
for ($iteration = 0; $iteration < 100; $iteration++) {
    $conn->begin_transaction();
    try {
        $eventResult = $conn->query(
            "SELECT id, event_type, aggregate_type, aggregate_id, payload
             FROM outbox_events
             WHERE processed_at IS NULL AND available_at <= NOW()
               AND event_type IN (
                 'order.created','payment.succeeded','payment.expired','payment.review_required','payment.voided',
                 'wallet.deposit_requested','wallet.deposit_confirmed','return.requested','return.approved',
                 'return.rejected','user.registered','order.cancelled','order.refunded','appointment.created'
               )
             ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED"
        );
        $event = $eventResult->fetch_assoc();
        if (!$event) {
            $conn->commit();
            break;
        }

        $eventId = (int) $event['id'];
        $eventType = (string) $event['event_type'];
        $payload = json_decode((string) $event['payload'], true, 32, JSON_THROW_ON_ERROR);
        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;
        $orderId = isset($payload['order_id']) ? (int) $payload['order_id'] : 0;
        if ($userId <= 0 && $orderId > 0) {
            $owner = $conn->prepare('SELECT ID_KhachHang FROM donhang WHERE ID_DonHang = ? LIMIT 1');
            $owner->bind_param('i', $orderId);
            $owner->execute();
            $ownerRow = $owner->get_result()->fetch_assoc();
            $userId = (int) ($ownerRow['ID_KhachHang'] ?? 0);
        }

        $adminMessage = null;
        $customerTitle = null;
        $customerMessage = null;
        switch ($eventType) {
            case 'order.created':
                $adminMessage = "Đơn hàng #{$orderId} vừa được tạo và đang chờ xử lý.";
                $customerTitle = 'Đã tiếp nhận đơn hàng';
                $customerMessage = "Đơn hàng #{$orderId} đã được hệ thống tiếp nhận.";
                break;
            case 'payment.succeeded':
                $customerTitle = 'Thanh toán thành công';
                $customerMessage = "Thanh toán cho đơn hàng #{$orderId} đã được xác nhận tự động.";
                break;
            case 'payment.expired':
                $customerTitle = 'Mã thanh toán đã hết hạn';
                $customerMessage = "Mã thanh toán của đơn hàng #{$orderId} đã hết hạn; tồn kho đã được hoàn lại.";
                break;
            case 'payment.review_required':
                $adminMessage = "Thanh toán SePay cho đơn #{$orderId} cần đối soát thủ công. Không giao hàng trước khi xử lý.";
                $customerTitle = 'Thanh toán đang được đối soát';
                $customerMessage = "Thanh toán của đơn hàng #{$orderId} đang được đối soát. Vui lòng không thanh toán lại.";
                break;
            case 'payment.voided':
                $adminMessage = "SePay báo giao dịch của đơn #{$orderId} đã bị void/hủy; cần kiểm tra đối soát.";
                $customerTitle = 'Giao dịch đã bị hủy';
                $customerMessage = "Giao dịch của đơn hàng #{$orderId} đã bị hủy hoặc đảo trạng thái.";
                break;
            case 'wallet.deposit_requested':
                $adminMessage = 'Có yêu cầu nạp ví #' . (int) ($payload['deposit_id'] ?? 0) . ' đang chờ đối soát.';
                break;
            case 'wallet.deposit_confirmed':
                $customerTitle = 'Nạp ví thành công';
                $customerMessage = 'Số dư đã được cộng ' . number_format((int) ($payload['amount'] ?? 0), 0, ',', '.') . ' đ.';
                break;
            case 'return.requested':
                $adminMessage = 'Có yêu cầu hoàn trả mới #' . (int) ($payload['request_id'] ?? 0) . " cho đơn hàng #{$orderId}.";
                break;
            case 'return.approved':
                $customerTitle = 'Yêu cầu hoàn trả đã được duyệt';
                $customerMessage = 'Khoản hoàn ' . number_format((int) ($payload['refund_amount'] ?? 0), 0, ',', '.') . ' đ đã được cộng vào số dư.';
                break;
            case 'return.rejected':
                $customerTitle = 'Yêu cầu hoàn trả bị từ chối';
                $customerMessage = 'Yêu cầu hoàn trả của bạn đã được xử lý và không được chấp thuận.';
                break;
            case 'user.registered':
                $adminMessage = 'Có tài khoản mới đăng ký trên hệ thống.';
                break;
            case 'appointment.created':
                $who = trim((string) ($payload['name'] ?? '')) ?: 'Khách hàng';
                $phone = trim((string) ($payload['phone'] ?? ''));
                $when = !empty($payload['appt_at']) ? date('d/m/Y H:i', strtotime((string) $payload['appt_at'])) : '';
                $adminMessage = "Lịch hẹn tư vấn mới #" . (int) ($payload['appointment_id'] ?? 0) . ": {$who}"
                    . ($phone !== '' ? " ({$phone})" : '') . ($when !== '' ? " lúc {$when}" : '') . '.';
                break;
            case 'order.cancelled':
            case 'order.refunded':
                $adminMessage = "Đơn hàng #{$orderId} đã thay đổi trạng thái: {$eventType}.";
                $customerTitle = $eventType === 'order.cancelled' ? 'Đơn hàng đã được hủy' : 'Đơn hàng đã được hoàn tiền';
                $customerMessage = "Đơn hàng #{$orderId} đã được xử lý. Vui lòng kiểm tra số dư nếu có khoản hoàn.";
                break;
        }

        if ($adminMessage !== null) {
            $insertAdmin = $conn->prepare(
                'INSERT IGNORE INTO admin_notifications(type, message, order_id, user_id, outbox_event_id)
                 VALUES (?, ?, NULLIF(?, 0), NULLIF(?, 0), ?)'
            );
            $insertAdmin->bind_param('ssiii', $eventType, $adminMessage, $orderId, $userId, $eventId);
            $insertAdmin->execute();
        }
        if ($userId > 0 && $customerTitle !== null && $customerMessage !== null) {
            $insertCustomer = $conn->prepare(
                'INSERT IGNORE INTO customer_notifications(user_id, type, title, message, outbox_event_id)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insertCustomer->bind_param('isssi', $userId, $eventType, $customerTitle, $customerMessage, $eventId);
            $insertCustomer->execute();
        }

        $complete = $conn->prepare('UPDATE outbox_events SET processed_at = NOW(), attempts = attempts + 1 WHERE id = ?');
        $complete->bind_param('i', $eventId);
        $complete->execute();
        $conn->commit();
        $processed++;
    } catch (Throwable $error) {
        $conn->rollback();
        error_log('Outbox processing failed: ' . $error->getMessage());
        break;
    }
}

echo "processed={$processed}\n";
