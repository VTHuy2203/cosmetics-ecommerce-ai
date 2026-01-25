<?php
/**
 * Employee Approve Order Processing
 * Allows employees (staff) logged in via UI to approve orders
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

include("../DB_Connect.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an employee
if (!isset($_SESSION['user']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    ob_end_clean();
    header("Location: ../View/Login.php");
    exit();
}

// Check if employee is approved
$user_id = $_SESSION['IDNguoiDung'];
$employee_check = mysqli_query($conn, "SELECT status FROM shipper_requests WHERE user_id = $user_id AND status = 'approved' ORDER BY created_at DESC LIMIT 1");
if (!$employee_check || mysqli_num_rows($employee_check) == 0) {
    ob_end_clean();
    header("Location: ../View/index.php?error=not_approved");
    exit();
}

// Check if order ID and approve flag are provided
if (!isset($_GET['order_id']) || !isset($_GET['approve']) || $_GET['approve'] != '1') {
    ob_end_clean();
    header("Location: ../View/Employee_Customer_List.php?error=invalid_request");
    exit();
}

$order_id = intval($_GET['order_id']);

// Get order details first
$sql_order = "SELECT * FROM donhang WHERE ID_DonHang = '$order_id' AND (TrangThai = '0' OR TrangThai = 0)";
$result_order = mysqli_query($conn, $sql_order);

if (!$result_order || mysqli_num_rows($result_order) == 0) {
    ob_end_clean();
    header("Location: ../View/Employee_Customer_List.php?error=order_not_found");
    exit();
}

$order_row = mysqli_fetch_array($result_order);

// Get order info for notifications
$customer_name = $order_row['HoVaTen'] ?? 'Khách hàng';
$customer_id = $order_row['ID_KhachHang'] ?? null;

// Get staff name who approved the order
$staff_name = $_SESSION['user'] ?? 'Nhân viên';
if (isset($_SESSION['HoVaTen'])) {
    $staff_name = $_SESSION['HoVaTen'];
}

// Approve the order - update TrangThai to 1 (Chuẩn Bị)
$sql_update = "UPDATE donhang SET TrangThai = '1' WHERE ID_DonHang = '$order_id'";
$result_update = mysqli_query($conn, $sql_update);

if ($result_update) {
    // Reduce product quantity when order is approved
    $order_details = mysqli_query($conn, "SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = '$order_id'");
    
    if ($order_details) {
        while ($detail = mysqli_fetch_array($order_details)) {
            $product_id = intval($detail['ID_SanPham']);
            $quantity = intval($detail['SoLuong']);
            
            // Reduce product quantity (ensure it doesn't go below 0)
            mysqli_query($conn, "UPDATE sanpham SET SoLuong = SoLuong - $quantity WHERE IDSanPham = $product_id AND SoLuong >= $quantity");
        }
    }
    
    // CRITICAL: Send WebSocket notifications to Admin, Shipper, and Customer
    $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
    
    if (file_exists($wsPushFile)) {
        try {
            require_once($wsPushFile);
            
            if (function_exists('websocket_push')) {
                // 1. Notify Shipper - Order is ready to be accepted (IMPORTANT: Remind to refresh page)
                websocket_push(
                    ['shipper_channel'],
                    [
                        'type' => 'order_approved',
                        'title' => 'Đơn hàng mới cần giao',
                        'message' => "Đơn hàng #{$order_id} từ khách hàng {$customer_name} đã được nhân viên {$staff_name} duyệt và sẵn sàng để nhận. Vui lòng làm mới trang để xem đơn hàng mới!",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'approver_name' => $staff_name,
                            'is_staff_approved' => true,
                            'status' => 1,
                            'status_text' => 'Đã duyệt',
                            'refresh_required' => true,
                            'refresh_message' => 'Vui lòng làm mới trang để xem đơn hàng mới được gán',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                
                // 2. Notify Customer/User - Order confirmed (only notify the customer who owns this order)
                if ($customer_id) {
                    websocket_push(
                        ['user_channel'],
                        [
                            'type' => 'order_confirmed',
                            'title' => 'Đơn hàng đã được xác nhận!',
                            'message' => "Đơn hàng #{$order_id} của bạn đã được nhân viên {$staff_name} xác nhận và đang được chuẩn bị",
                            'sound' => 'notification',
                            'data' => [
                                'order_id' => $order_id,
                                'customer_id' => $customer_id,
                                'status' => 1,
                                'status_text' => 'Đã duyệt',
                                'approver_name' => $staff_name,
                                'is_staff_approved' => true,
                                'timestamp' => date('Y-m-d H:i:s')
                            ]
                        ]
                    );
                }
                
                // 3. Notify Admin - IMPORTANT: Always notify admin when staff approves an order
                websocket_push(
                    ['admin_channel'],
                    [
                        'type' => 'order_approved_by_staff',
                        'title' => 'Đơn hàng đã được nhân viên duyệt',
                        'message' => "Nhân viên {$staff_name} đã duyệt đơn hàng #{$order_id} của khách hàng {$customer_name}",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'customer_id' => $customer_id,
                            'approver_name' => $staff_name,
                            'is_staff_approved' => true,
                            'status' => 1,
                            'status_text' => 'Đã duyệt',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                
                error_log("Employee_Approve_Order.php: ✅ Notifications sent for order #{$order_id} (approved by staff: {$staff_name})");
            }
        } catch (Exception $e) {
            error_log("Employee_Approve_Order.php: ❌ EXCEPTION sending WebSocket notifications: " . $e->getMessage());
        }
    }
    
    // Add a small delay to ensure queue file is written before redirect
    usleep(100000); // 100ms delay
    
    // Clear any output buffer and redirect
    ob_end_clean();
    header("Location: ../View/Employee_Customer_List.php?success=order_approved&order_id=$order_id");
    exit();
} else {
    // Error updating order
    // Clear any output buffer and redirect
    ob_end_clean();
    header("Location: ../View/Employee_Customer_List.php?error=approval_failed");
    exit();
}

