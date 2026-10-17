<?php
declare(strict_types=1);

ob_start();
ini_set('display_errors', '0');

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';

header('Content-Type: application/json; charset=utf-8');

function order_action_response(int $status, array $payload): never
{
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function order_action_store_customer_notification(int $customerId, string $type, string $title, string $message): void
{
    global $conn;

    $notification = $conn->prepare(
        'INSERT INTO customer_notifications(user_id, type, title, message) VALUES (?, ?, ?, ?)'
    );
    $notification->bind_param('isss', $customerId, $type, $title, $message);
    $notification->execute();
}

function order_action_push_customer(int $customerId, int $orderId, string $type, string $title, string $message, int $status, string $statusText): void
{
    $pushFile = dirname(__DIR__, 3) . '/WebSocketServer/WebSocket_Push.php';
    if (!is_file($pushFile)) {
        return;
    }

    try {
        require_once $pushFile;
        if (function_exists('websocket_push')) {
            websocket_push(['user_channel'], [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'sound' => 'notification',
                'data' => [
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'status' => $status,
                    'status_text' => $statusText,
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
            ]);
        }
    } catch (Throwable $error) {
        error_log('Order realtime notification failed: ' . $error->getMessage());
    }
}

function order_action_notify_team(int $orderId, string $action, string $customerName, int $status, string $statusText): void
{
    $pushFile = dirname(__DIR__, 3) . '/WebSocketServer/WebSocket_Push.php';
    if (!is_file($pushFile)) {
        return;
    }
    $channels = $action === 'approve'
        ? ['admin_channel', 'staff_channel', 'shipper_channel']
        : ['admin_channel', 'staff_channel'];
    try {
        require_once $pushFile;
        if (function_exists('websocket_push')) {
            websocket_push($channels, [
                'type' => 'order_status_update',
                'title' => 'Trạng thái đơn hàng đã thay đổi',
                'message' => "Đơn hàng #{$orderId} của {$customerName}: {$statusText}",
                'sound' => 'notification',
                'data' => [
                    'order_id' => $orderId,
                    'customer_name' => $customerName,
                    'status' => $status,
                    'status_text' => $statusText,
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
            ]);
        }
    } catch (Throwable $error) {
        error_log('Order team realtime notification failed: ' . $error->getMessage());
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    order_action_response(405, ['ok' => false, 'message' => 'Phương thức không được hỗ trợ.']);
}

if (!csrf_is_valid()) {
    order_action_response(403, ['ok' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.']);
}

$role = (string) ($_SESSION['admin_role'] ?? '');
$action = (string) ($_POST['action'] ?? '');
$orderId = (int) ($_POST['order_id'] ?? 0);

if ($orderId < 1 || !in_array($action, ['approve', 'prepare', 'shipping', 'mark_paid'], true)) {
    order_action_response(422, ['ok' => false, 'message' => 'Yêu cầu cập nhật đơn hàng không hợp lệ.']);
}

$allowedRoles = [
    'approve' => ['admin', 'manager', 'employee'],
    'prepare' => ['admin', 'manager'],
    'shipping' => ['admin', 'manager'],
    'mark_paid' => ['admin', 'manager'],
];
if (!in_array($role, $allowedRoles[$action], true)) {
    order_action_response(403, ['ok' => false, 'message' => 'Tài khoản của bạn không có quyền thực hiện thao tác này.']);
}

$conn->begin_transaction();
try {
    $select = $conn->prepare(
        'SELECT ID_DonHang, ID_KhachHang, HoVaTen, TongTien, TrangThai, DaThanhToan,
                PhuongThucThanhToan, TrangThaiThanhToan, HanThanhToan
         FROM donhang WHERE ID_DonHang = ? FOR UPDATE'
    );
    $select->bind_param('i', $orderId);
    $select->execute();
    $order = $select->get_result()->fetch_assoc();
    if (!$order) {
        throw new DomainException('Không tìm thấy đơn hàng.');
    }

    $currentStatus = (int) $order['TrangThai'];
    $customerId = (int) $order['ID_KhachHang'];
    $paymentMethod = strtolower((string) ($order['PhuongThucThanhToan'] ?? 'cod'));
    $paymentStatus = strtolower((string) ($order['TrangThaiThanhToan'] ?? ''));
    $status = $currentStatus;
    $statusText = '';
    $title = '';
    $message = '';
    $type = 'order_status_update';
    $removeFromFilteredList = false;

    if ($action === 'mark_paid') {
        if ((int) $order['DaThanhToan'] === 1 || $paymentStatus === 'paid') {
            throw new DomainException('Đơn hàng đã được xác nhận thanh toán trước đó.');
        }
        $isCodReady = $paymentMethod === 'cod' && $currentStatus === 3;
        $isQrReady = $paymentMethod === 'qr'
            && $currentStatus === 0
            && $paymentStatus === 'pending'
            && (empty($order['HanThanhToan']) || strtotime((string) $order['HanThanhToan']) >= time());
        if (!$isCodReady && !$isQrReady) {
            throw new DomainException('Đơn hàng chưa ở trạng thái cho phép xác nhận thanh toán.');
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
            throw new RuntimeException('Trạng thái thanh toán vừa được thay đổi.');
        }
        $statusText = 'Đã thanh toán';
        $title = 'Thanh toán đã được xác nhận';
        $message = "Thanh toán cho đơn hàng #{$orderId} đã được cửa hàng xác nhận.";
        $type = 'payment_confirmed';

        $payload = json_encode([
            'order_id' => $orderId,
            'amount' => (int) $order['TongTien'],
            'method' => $paymentMethod,
            'confirmed_by' => (int) ($_SESSION['admin_id'] ?? 0),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $event = $conn->prepare(
            "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
             VALUES ('payment.manually_confirmed', 'order', ?, ?)"
        );
        $aggregateId = (string) $orderId;
        $event->bind_param('ss', $aggregateId, $payload);
        $event->execute();
    } else {
        $transitions = [
            'approve' => ['from' => 0, 'to' => 1, 'text' => 'Đã duyệt', 'title' => 'Đơn hàng đã được xác nhận', 'message' => "Đơn hàng #{$orderId} đã được xác nhận và đang chờ chuẩn bị."],
            'prepare' => ['from' => 1, 'to' => 2, 'text' => 'Đang giao', 'title' => 'Đơn hàng đang được giao', 'message' => "Đơn hàng #{$orderId} đã được chuẩn bị và chuyển sang giao hàng."],
            'shipping' => ['from' => 2, 'to' => 3, 'text' => 'Giao thành công', 'title' => 'Đơn hàng đã giao thành công', 'message' => "Đơn hàng #{$orderId} đã được xác nhận giao thành công."],
        ];
        $transition = $transitions[$action];
        if ($currentStatus !== $transition['from']) {
            throw new DomainException('Trạng thái đơn hàng vừa thay đổi. Vui lòng tải lại danh sách.');
        }
        if ($action === 'approve' && $paymentMethod !== 'cod' && $paymentStatus !== 'paid') {
            $instruction = $role === 'employee'
                ? 'Admin hoặc Manager cần đối soát và xác nhận thanh toán trước.'
                : 'Hãy bấm “Đánh dấu đã thanh toán” sau khi đối soát, rồi duyệt đơn.';
            throw new DomainException('Đơn QR chưa được xác nhận thanh toán. ' . $instruction);
        }
        if ($action === 'shipping' && $paymentMethod !== 'cod' && $paymentStatus !== 'paid') {
            throw new DomainException('Không thể hoàn tất đơn chưa thanh toán.');
        }

        $status = $transition['to'];
        if ($action === 'shipping') {
            $update = $conn->prepare(
                "UPDATE donhang SET TrangThai = 3, GiaoHangLuc = NOW(),
                 DaThanhToan = IF(PhuongThucThanhToan = 'cod', 1, DaThanhToan),
                 TrangThaiThanhToan = IF(PhuongThucThanhToan = 'cod', 'paid', TrangThaiThanhToan),
                 ThanhToanLuc = IF(PhuongThucThanhToan = 'cod', NOW(), ThanhToanLuc),
                 MaThamChieuThanhToan = IF(PhuongThucThanhToan = 'cod', CONCAT('COD-ADMIN-', ID_DonHang, '-', UNIX_TIMESTAMP()), MaThamChieuThanhToan)
                 WHERE ID_DonHang = ? AND TrangThai = 2"
            );
        } else {
            $update = $conn->prepare('UPDATE donhang SET TrangThai = ? WHERE ID_DonHang = ? AND TrangThai = ?');
            $update->bind_param('iii', $status, $orderId, $currentStatus);
        }
        if ($action === 'shipping') {
            $update->bind_param('i', $orderId);
        }
        $update->execute();
        if ($update->affected_rows !== 1) {
            throw new RuntimeException('Không thể cập nhật trạng thái đơn hàng.');
        }
        $statusText = $transition['text'];
        $title = $transition['title'];
        $message = $transition['message'];
        $removeFromFilteredList = (string) ($_POST['return_to'] ?? 'all') !== 'all';
    }

    if ($customerId > 0) {
        order_action_store_customer_notification($customerId, $type, $title, $message);
    }
    $conn->commit();

    if ($customerId > 0) {
        order_action_push_customer($customerId, $orderId, $type, $title, $message, $status, $statusText);
    }
    if ($action !== 'mark_paid') {
        order_action_notify_team($orderId, $action, trim((string) ($order['HoVaTen'] ?? '')) ?: 'Khách hàng', $status, $statusText);
    }

    $nextAction = null;
    if ($action === 'approve' && in_array($role, ['admin', 'manager'], true)) {
        $nextAction = ['action' => 'prepare', 'label' => 'Chuẩn bị', 'button_class' => 'btn-info'];
    } elseif ($action === 'prepare' && in_array($role, ['admin', 'manager'], true)) {
        $nextAction = ['action' => 'shipping', 'label' => 'Hoàn tất giao', 'button_class' => 'btn-warning'];
    }

    order_action_response(200, [
        'ok' => true,
        'message' => $message,
        'order_id' => $orderId,
        'action' => $action,
        'status' => $status,
        'status_text' => $statusText,
        'remove_row' => $removeFromFilteredList,
        'next_action' => $nextAction,
    ]);
} catch (DomainException $error) {
    $conn->rollback();
    order_action_response(409, ['ok' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Order status API failed: ' . $error->getMessage());
    order_action_response(500, ['ok' => false, 'message' => 'Không thể cập nhật đơn hàng lúc này.']);
}
