<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../DB_Connect.php");

// CRITICAL: Only Admin and Manager can cancel orders (not staff)
// Use ONLY admin session variables (completely ignore UI session)
$admin_role = $_SESSION['admin_role'] ?? null;
$is_staff_via_admin = ($admin_role === 'employee');
$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');

// Debug logging (remove in production)
error_log("Delete_Oder.php - admin_role: " . ($admin_role ?? 'null'));
error_log("Delete_Oder.php - is_admin_or_manager: " . ($is_admin_or_manager ? 'true' : 'false'));
error_log("Delete_Oder.php - is_staff_via_admin: " . ($is_staff_via_admin ? 'true' : 'false'));

if (isset($_GET['del_oder_id']) && isset($_GET['view'])) {
    $del_oder_id = intval($_GET['del_oder_id']);
    
    // CRITICAL: Block staff (via admin login) from canceling orders
    if ($is_staff_via_admin || !$is_admin_or_manager) {
        error_log("Delete_Oder.php - Access denied for role: " . ($admin_role ?? 'null'));
        // Redirect based on view parameter
        $redirect_page = 'All_Oders.php';
        if ($_GET['view'] == 'approve') {
            $redirect_page = 'Approve_Oders.php';
        } else if ($_GET['view'] == 'prepare') {
            $redirect_page = 'Prepare_Oders.php';
        } else if ($_GET['view'] == 'shipping') {
            $redirect_page = 'Shipping_Oders.php';
        } else if ($_GET['view'] == 'success') {
            $redirect_page = 'Success_Oders.php';
        }
        header('Location: ' . $redirect_page . '?error=unauthorized');
        exit();
    }
    
    error_log("Delete_Oder.php - Attempting to delete order: " . $del_oder_id);
    
    // Check if order exists
    $order_check = mysqli_query($conn, "SELECT ID_KhachHang, TongTien, DaThanhToan, TrangThai FROM donhang WHERE ID_DonHang = '$del_oder_id'");
    if (!$order_check) {
        error_log("Delete_Oder.php - Error checking order: " . mysqli_error($conn));
        header('Location: All_Oders.php?error=' . urlencode('Không tìm thấy đơn hàng hoặc lỗi truy vấn'));
        exit();
    }
    
    $order_row = mysqli_fetch_array($order_check);
    
    if (!$order_row) {
        error_log("Delete_Oder.php - Order not found: " . $del_oder_id);
        header('Location: All_Oders.php?error=' . urlencode('Đơn hàng không tồn tại'));
        exit();
    }
    
    // Restore stock if order was approved (status = 1 or higher)
    if ($order_row && intval($order_row['TrangThai']) >= 1) {
        // Get order details and restore stock
        $order_details = mysqli_query($conn, "SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = '$del_oder_id'");
        while ($detail = mysqli_fetch_array($order_details)) {
            $product_id = intval($detail['ID_SanPham']);
            $quantity = intval($detail['SoLuong']);
            
            // Restore product quantity
            mysqli_query($conn, "UPDATE sanpham SET SoLuong = SoLuong + $quantity WHERE IDSanPham = $product_id");
        }
    }
    
    if ($order_row && intval($order_row['DaThanhToan']) == 1) {
        // Order was paid with balance - refund the amount
        $user_id = $order_row['ID_KhachHang'];
        $refund_amount = intval($order_row['TongTien']);
        
        // Ensure SoDu column exists (check first to avoid duplicate column error)
        $check_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
        $col_exists = false;
        if ($check_col) {
            $col_row = mysqli_fetch_assoc($check_col);
            $col_exists = intval($col_row['c']) > 0;
        }
        if (!$col_exists) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
        }
        
        // Refund to user account
        mysqli_query($conn, "UPDATE khachhang SET SoDu = SoDu + $refund_amount WHERE ID_KhachHang = '$user_id'");
        
        // Create notification for refund
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `admin_notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(50) NOT NULL,
            `message` TEXT NOT NULL,
            `order_id` INT NULL,
            `user_id` INT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_is_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $user_info = mysqli_fetch_array(mysqli_query($conn, "SELECT HoVaTen FROM khachhang WHERE ID_KhachHang = '$user_id'"));
        $user_name = $user_info['HoVaTen'] ?? 'N/A';
        $refund_msg = "Đơn hàng #$del_oder_id của khách hàng $user_name đã bị hủy. Đã hoàn tiền " . number_format($refund_amount, 0, ',', '.') . " đ vào tài khoản.";
        mysqli_query($conn, "INSERT INTO `admin_notifications` (`type`, `message`, `order_id`, `user_id`) 
                            VALUES ('order_refunded', '$refund_msg', '$del_oder_id', '$user_id')");
    }
    
    // First, delete order details (child records) to avoid foreign key constraint issues
    $delete_details = mysqli_query($conn, "DELETE FROM donhangchitiet WHERE ID_DonHang = '$del_oder_id'");
    if (!$delete_details) {
        error_log("Delete_Oder.php - Error deleting order details: " . mysqli_error($conn));
    }
    
    // Then delete the order
    $sql_del_oder_id = "DELETE FROM donhang WHERE ID_DonHang = '$del_oder_id'";
    $delete_Result = mysqli_query($conn, $sql_del_oder_id);
    
    if (!$delete_Result) {
        error_log("Delete_Oder.php - Error deleting order: " . mysqli_error($conn));
    }

    if ($delete_Result) {
        error_log("Delete_Oder.php - Order deleted successfully: " . $del_oder_id);
        
        // Get customer info for notification
        $customer_id = $order_row['ID_KhachHang'] ?? null;
        $customer_name = 'Khách hàng';
        if ($customer_id) {
            $customer_query = mysqli_query($conn, "SELECT HoVaTen FROM khachhang WHERE ID_KhachHang = '$customer_id'");
            if ($customer_query) {
                $customer_row = mysqli_fetch_array($customer_query);
                if ($customer_row) {
                    $customer_name = $customer_row['HoVaTen'];
                }
            }
        }
        
        // Send WebSocket notification to remove order from UI with proper error message
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            $old_error_reporting = error_reporting(0);
            $old_display_errors = ini_get('display_errors');
            ini_set('display_errors', 0);
            try {
                require_once($wsPushFile);
                
                // Notify User with proper message
                if (function_exists('websocket_push')) {
                    websocket_push(
                        ['user_channel'],
                        [
                            'type' => 'order_deleted',
                            'title' => 'Đơn hàng đã bị hủy',
                            'message' => "Đơn hàng #{$del_oder_id} đã bị hủy. Sản phẩm không còn khả dụng, vui lòng tải lại trang.",
                            'sound' => 'notification',
                            'data' => [
                                'order_id' => $del_oder_id,
                                'customer_id' => $customer_id,
                                'error_message' => 'Sản phẩm không còn khả dụng, vui lòng tải lại trang.',
                                'timestamp' => date('Y-m-d H:i:s')
                            ]
                        ]
                    );
                }
                
                // Notify Admin and Staff
                if (function_exists('websocket_push')) {
                    websocket_push(
                        ['admin_channel', 'staff_channel'],
                        [
                            'type' => 'order_deleted',
                            'title' => 'Đơn hàng đã bị hủy',
                            'message' => "Đơn hàng #{$del_oder_id} của khách hàng {$customer_name} đã bị hủy",
                            'sound' => 'notification',
                            'data' => [
                                'order_id' => $del_oder_id,
                                'customer_name' => $customer_name,
                                'customer_id' => $customer_id,
                                'timestamp' => date('Y-m-d H:i:s')
                            ]
                        ]
                    );
                }
                
                // Notify Shipper if order was assigned
                if (function_exists('websocket_push')) {
                    websocket_push(
                        ['shipper_channel'],
                        [
                            'type' => 'order_deleted',
                            'title' => 'Đơn hàng đã bị hủy',
                            'message' => "Đơn hàng #{$del_oder_id} đã bị hủy và không còn khả dụng",
                            'sound' => 'notification',
                            'data' => [
                                'order_id' => $del_oder_id,
                                'timestamp' => date('Y-m-d H:i:s')
                            ]
                        ]
                    );
                }
            } catch (Exception $e) {
                error_log("Delete_Oder.php: Error sending WebSocket notification: " . $e->getMessage());
            }
            error_reporting($old_error_reporting);
            ini_set('display_errors', $old_display_errors);
        }
        
        if ($_GET['view'] == 'all') {
            header('Location: All_Oders.php');
            exit();
        } else if ($_GET['view'] == 'approve') {
            header('Location: Approve_Oders.php');
            exit();
        }else if ($_GET['view'] == 'prepare') {
            header('Location: Prepare_Oders.php');
            exit();
        }else if ($_GET['view'] == 'shipping') {
            header('Location: Shipping_Oders.php');
            exit();
        }else if ($_GET['view'] == 'success') {
            header('Location: Success_Oders.php');
            exit();
        }
    } else {
        $error_msg = mysqli_error($conn);
        error_log("Delete_Oder.php - Failed to delete order: " . $error_msg);
        
        // Redirect with error message
        $redirect_page = 'All_Oders.php';
        if ($_GET['view'] == 'approve') {
            $redirect_page = 'Approve_Oders.php';
        } else if ($_GET['view'] == 'prepare') {
            $redirect_page = 'Prepare_Oders.php';
        } else if ($_GET['view'] == 'shipping') {
            $redirect_page = 'Shipping_Oders.php';
        } else if ($_GET['view'] == 'success') {
            $redirect_page = 'Success_Oders.php';
        }
        
        // Show error message
        $error_encoded = urlencode("Không thể xóa đơn hàng. Lỗi: " . $error_msg);
        header('Location: ' . $redirect_page . '?error=' . $error_encoded);
        exit();
    }
} else {
    // Missing parameters
    error_log("Delete_Oder.php - Missing parameters: del_oder_id or view");
    header('Location: All_Oders.php?error=missing_params');
    exit();
}
