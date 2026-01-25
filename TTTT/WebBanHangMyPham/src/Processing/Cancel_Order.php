<?php
/**
 * Cancel Order Processing
 * Handles order cancellation with refund logic based on payment method
 */

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../DB_Connect.php");

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

// Get and validate input
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
$cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
$qr_refund_agreed = isset($_POST['qr_refund_agreed']) ? intval($_POST['qr_refund_agreed']) : 0;

if ($order_id <= 0) {
    echo 'error: Mã đơn hàng không hợp lệ.';
    exit();
}

if (empty($cancel_reason)) {
    echo 'error: Vui lòng nhập lý do hủy đơn hàng.';
    exit();
}

// Get user ID
$sql_user = "SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = '" . mysqli_real_escape_string($conn, $_SESSION['user']) . "'";
$result_user = mysqli_query($conn, $sql_user);
if (!$result_user || mysqli_num_rows($result_user) == 0) {
    echo 'error: Không tìm thấy thông tin người dùng.';
    exit();
}
$row_user = mysqli_fetch_array($result_user);
$customer_id = intval($row_user['ID_KhachHang']);

// Get order information
$sql_order = "SELECT * FROM donhang WHERE ID_DonHang = $order_id AND ID_KhachHang = $customer_id";
$result_order = mysqli_query($conn, $sql_order);

if (!$result_order || mysqli_num_rows($result_order) == 0) {
    echo 'error: Đơn hàng không tồn tại hoặc bạn không có quyền hủy đơn hàng này.';
    exit();
}

$order = mysqli_fetch_array($result_order);
$order_status = intval($order['TrangThai']);
$id_shipper = isset($order['ID_Shipper']) ? intval($order['ID_Shipper']) : 0;
$is_paid = isset($order['DaThanhToan']) && intval($order['DaThanhToan']) == 1;
$order_amount = intval($order['TongTien']);
$ghi_chu = isset($order['GhiChu']) ? $order['GhiChu'] : '';

// Check if order can be cancelled
$can_cancel = ($order_status == 0) || ($order_status == 1 && ($id_shipper == 0 || is_null($order['ID_Shipper'])));

if (!$can_cancel) {
    echo 'error: Đơn hàng không thể hủy tại thời điểm này.';
    exit();
}

// Check COD cancellation - if shipper has accepted, cannot cancel
if ($payment_method === 'cod' && $id_shipper > 0) {
    echo 'error: Đơn hàng đang được giao và không thể hủy tại thời điểm này.';
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Determine actual payment method from GhiChu if not provided correctly
    $actual_payment_method = $payment_method;
    if (stripos($ghi_chu, 'Thanh toán bằng số dư tài khoản') !== false) {
        $actual_payment_method = 'balance';
    } elseif (stripos($ghi_chu, 'QR:') !== false) {
        $actual_payment_method = 'qr';
    } else {
        $actual_payment_method = 'cod';
    }
    
    // Handle refund based on payment method
    if ($actual_payment_method === 'balance' && $is_paid) {
        // Refund to account balance
        // Ensure SoDu column exists
        $check_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
        $col_exists = false;
        if ($check_col) {
            $col_row = mysqli_fetch_assoc($check_col);
            $col_exists = intval($col_row['c']) > 0;
        }
        if (!$col_exists) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
        }
        
        // Refund amount to customer account
        $refund_result = mysqli_query($conn, "UPDATE khachhang SET SoDu = SoDu + $order_amount WHERE ID_KhachHang = $customer_id");
        if (!$refund_result) {
            throw new Exception('Lỗi khi hoàn tiền vào tài khoản: ' . mysqli_error($conn));
        }
    } elseif ($actual_payment_method === 'qr' && $is_paid && $qr_refund_agreed == 1) {
        // Refund QR payment to account balance (user agreed)
        // Ensure SoDu column exists
        $check_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
        $col_exists = false;
        if ($check_col) {
            $col_row = mysqli_fetch_assoc($check_col);
            $col_exists = intval($col_row['c']) > 0;
        }
        if (!$col_exists) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
        }
        
        // Refund amount to customer account
        $refund_result = mysqli_query($conn, "UPDATE khachhang SET SoDu = SoDu + $order_amount WHERE ID_KhachHang = $customer_id");
        if (!$refund_result) {
            throw new Exception('Lỗi khi hoàn tiền vào tài khoản: ' . mysqli_error($conn));
        }
    }
    // For COD or QR without agreement, no refund needed (cash refund by shipper)
    
    // Restore stock if order was approved (status >= 1)
    if ($order_status >= 1) {
        $sql_order_details = "SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = $order_id";
        $result_order_details = mysqli_query($conn, $sql_order_details);
        
        if ($result_order_details) {
            while ($order_detail = mysqli_fetch_array($result_order_details)) {
                $product_id = intval($order_detail['ID_SanPham']);
                $quantity = intval($order_detail['SoLuong']);
                
                // Restore stock
                $restore_stock = mysqli_query($conn, "UPDATE sanpham SET SoLuong = SoLuong + $quantity WHERE IDSanPham = $product_id");
                if (!$restore_stock) {
                    throw new Exception('Lỗi khi khôi phục tồn kho sản phẩm: ' . mysqli_error($conn));
                }
            }
        }
    }
    
    // Update order status to cancelled (status = -1 or update GhiChu with cancellation info)
    // We'll mark it as cancelled by updating GhiChu and setting a special status
    // First, check if we need to add a cancellation status column or use existing status
    $cancel_reason_escaped = mysqli_real_escape_string($conn, $cancel_reason);
    $updated_ghi_chu = $ghi_chu . ' | [ĐÃ HỦY] Lý do: ' . $cancel_reason_escaped;
    
    // Update order - set status to a cancelled state (we'll use -1 for cancelled)
    // But first check if status column can handle negative values
    $update_order = mysqli_query($conn, "UPDATE donhang SET TrangThai = -1, GhiChu = '$updated_ghi_chu' WHERE ID_DonHang = $order_id");
    if (!$update_order) {
        // If -1 doesn't work, try updating GhiChu only and keep status
        $update_order = mysqli_query($conn, "UPDATE donhang SET GhiChu = '$updated_ghi_chu' WHERE ID_DonHang = $order_id");
        if (!$update_order) {
            throw new Exception('Lỗi khi cập nhật trạng thái đơn hàng: ' . mysqli_error($conn));
        }
    }
    
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

