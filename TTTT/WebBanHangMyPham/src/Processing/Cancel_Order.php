<?php
/**
 * Cancel Order Processing
 * Handles order cancellation with refund logic based on payment method
 */

ob_start();

include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/VoucherService.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo 'error: Bạn cần đăng nhập để thực hiện thao tác này.';
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error: Phương thức yêu cầu không hợp lệ.';
    exit();
}
require_csrf();

// Get and validate input
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
$cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
$qr_refund_agreed = isset($_POST['qr_refund_agreed']) ? intval($_POST['qr_refund_agreed']) : 0;

if ($order_id <= 0) {
    echo 'error: Mã đơn hàng không hợp lệ.';
    exit();
}

if (mb_strlen($cancel_reason) < 3 || mb_strlen($cancel_reason) > 500) {
    echo 'error: Vui lòng nhập lý do hủy đơn hàng.';
    exit();
}

// Get user ID
$userStmt = $conn->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
$userStmt->bind_param('s', $_SESSION['user']);
$userStmt->execute();
$row_user = $userStmt->get_result()->fetch_assoc();
if (!$row_user) {
    echo 'error: Không tìm thấy thông tin người dùng.';
    exit();
}
$customer_id = intval($row_user['ID_KhachHang']);

// Get order information
$conn->begin_transaction();
$orderStmt = $conn->prepare('SELECT * FROM donhang WHERE ID_DonHang = ? AND ID_KhachHang = ? FOR UPDATE');
$orderStmt->bind_param('ii', $order_id, $customer_id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    $conn->rollback();
    echo 'error: Đơn hàng không tồn tại hoặc bạn không có quyền hủy đơn hàng này.';
    exit();
}

$order_status = intval($order['TrangThai']);
$id_shipper = isset($order['ID_Shipper']) ? intval($order['ID_Shipper']) : 0;
$is_paid = isset($order['DaThanhToan']) && intval($order['DaThanhToan']) == 1;
$order_amount = intval($order['TongTien']);
$ghi_chu = isset($order['GhiChu']) ? $order['GhiChu'] : '';

// Check if order can be cancelled
$can_cancel = ($order_status == 0) || ($order_status == 1 && ($id_shipper == 0 || is_null($order['ID_Shipper'])));

if (!$can_cancel) {
    $conn->rollback();
    echo 'error: Đơn hàng không thể hủy tại thời điểm này.';
    exit();
}

// Check COD cancellation - if shipper has accepted, cannot cancel
if ($payment_method === 'cod' && $id_shipper > 0) {
    $conn->rollback();
    echo 'error: Đơn hàng đang được giao và không thể hủy tại thời điểm này.';
    exit();
}

try {
    // The server-side order value is authoritative; never trust the submitted method.
    $actual_payment_method = (string) ($order['PhuongThucThanhToan'] ?? '');
    if (!in_array($actual_payment_method, ['cod', 'qr', 'balance', 'sepay_pg'], true)) {
        $actual_payment_method = stripos($ghi_chu, 'Thanh toán bằng số dư tài khoản') !== false
            ? 'balance'
            : (stripos($ghi_chu, 'QR:') !== false ? 'qr' : 'cod');
    }

    if ($actual_payment_method === 'qr' && $is_paid && $qr_refund_agreed !== 1) {
        throw new DomainException('Bạn cần đồng ý nhận khoản hoàn vào số dư trước khi hủy đơn QR đã thanh toán.');
    }
    if ($actual_payment_method === 'sepay_pg' && $is_paid) {
        throw new DomainException('Đơn đã thanh toán qua SePay cần được nhân viên xử lý hoàn tiền và đối soát.');
    }

    $refundApplied = $is_paid && in_array($actual_payment_method, ['balance', 'qr'], true);
    if ($refundApplied) {
        $refund = $conn->prepare('UPDATE khachhang SET SoDu = SoDu + ? WHERE ID_KhachHang = ?');
        $refund->bind_param('ii', $order_amount, $customer_id);
        $refund->execute();
        if ($refund->affected_rows !== 1) {
            throw new RuntimeException('Không thể hoàn tiền vào số dư.');
        }
    }
    
    // Restore reserved stock exactly once, regardless of fulfilment status.
    if ((int) ($order['TonKhoDaGiu'] ?? 0) === 1) {
        $details = $conn->prepare('SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = ?');
        $details->bind_param('i', $order_id);
        $details->execute();
        $result_order_details = $details->get_result();
        
        if ($result_order_details) {
            while ($order_detail = mysqli_fetch_array($result_order_details)) {
                $product_id = intval($order_detail['ID_SanPham']);
                $quantity = intval($order_detail['SoLuong']);
                
                // Restore stock
                $restoreStock = $conn->prepare('UPDATE sanpham SET SoLuong = SoLuong + ? WHERE IDSanPham = ?');
                $restoreStock->bind_param('ii', $quantity, $product_id);
                $restoreStock->execute();
            }
        }
    }

    (new VoucherService($conn))->releaseForOrder($order_id, 'customer_cancelled');
    
    // Update order status to cancelled (status = -1 or update GhiChu with cancellation info)
    // We'll mark it as cancelled by updating GhiChu and setting a special status
    // First, check if we need to add a cancellation status column or use existing status
    $updated_ghi_chu = mb_substr($ghi_chu . ' | [ĐÃ HỦY] Lý do: ' . $cancel_reason, 0, 255);
    
    // Update order - set status to a cancelled state (we'll use -1 for cancelled)
    // But first check if status column can handle negative values
    $paymentStatus = $refundApplied
        ? 'refunded'
        : (in_array($actual_payment_method, ['qr', 'sepay_pg'], true) ? 'expired' : 'cod_pending');
    $updateStmt = $conn->prepare(
        'UPDATE donhang SET TrangThai = -1, GhiChu = ?, TrangThaiThanhToan = ?, TonKhoDaGiu = 0 WHERE ID_DonHang = ?'
    );
    $updateStmt->bind_param('ssi', $updated_ghi_chu, $paymentStatus, $order_id);
    $update_order = $updateStmt->execute();
    if (!$update_order) {
        throw new Exception('Lỗi khi cập nhật trạng thái đơn hàng: ' . mysqli_error($conn));
    }

    $payload = json_encode([
        'order_id' => $order_id,
        'user_id' => $customer_id,
        'refund_amount' => $refundApplied ? $order_amount : 0,
        'reason' => mb_substr($cancel_reason, 0, 255),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $event = $conn->prepare(
        "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
         VALUES ('order.cancelled', 'order', ?, ?)"
    );
    $aggregateId = (string) $order_id;
    $event->bind_param('ss', $aggregateId, $payload);
    $event->execute();
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Send notification to admin and staff
    $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
    if (file_exists($wsPushFile)) {
        try {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                $customer_name = $order['HoVaTen'] ?? 'Khách hàng';
                $customer_id = $order['ID_KhachHang'] ?? null;
                $message = "Đơn hàng #$order_id của $customer_name đã bị hủy. Lý do: $cancel_reason";
                
                // Notify admin and staff
                $notification_result = websocket_push(
                    ['admin_channel', 'staff_channel'],
                    [
                        'type' => 'order_cancelled',
                        'title' => 'Đơn hàng đã bị hủy',
                        'message' => $message,
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'customer_id' => $customer_id,
                            'cancel_reason' => $cancel_reason,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                
                // Also notify customer if they own the order
                if ($customer_id) {
                    websocket_push(
                        ['user_channel'],
                        [
                            'type' => 'order_cancelled',
                            'title' => 'Đơn hàng đã bị hủy',
                            'message' => "Đơn hàng #$order_id của bạn đã bị hủy. Lý do: $cancel_reason",
                            'sound' => 'notification',
                            'data' => [
                                'order_id' => $order_id,
                                'cancel_reason' => $cancel_reason,
                                'timestamp' => date('Y-m-d H:i:s')
                            ]
                        ]
                    );
                }
                
                if ($notification_result) {
                    error_log("Cancel_Order.php: ✅ Notification sent successfully for cancelled order #{$order_id}");
                } else {
                    error_log("Cancel_Order.php: ⚠️ Notification function returned false for order #{$order_id}");
                }
            } else {
                error_log("Cancel_Order.php: ❌ Function websocket_push not found");
            }
        } catch (Exception $e) {
            error_log("Cancel_Order.php: ❌ Exception sending notification: " . $e->getMessage());
        }
    } else {
        error_log("Cancel_Order.php: ❌ WebSocket_Push.php file not found at: {$wsPushFile}");
    }
    
    ob_end_clean();
    echo 'success';
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    ob_end_clean();
    echo 'error: ' . $e->getMessage();
    exit();
}

