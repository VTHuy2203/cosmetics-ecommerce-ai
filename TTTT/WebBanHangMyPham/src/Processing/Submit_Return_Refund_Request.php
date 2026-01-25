<?php
/**
 * Submit Return/Refund Request
 * Handles form submission and file uploads
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

// Get user ID
$sql_user = "SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = '" . mysqli_real_escape_string($conn, $_SESSION['user']) . "'";
$result_user = mysqli_query($conn, $sql_user);
if (!$result_user || mysqli_num_rows($result_user) == 0) {
    echo 'error: Không tìm thấy thông tin người dùng.';
    exit();
}
$row_user = mysqli_fetch_array($result_user);
$customer_id = intval($row_user['ID_KhachHang']);

// Get and validate input
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$contact_name = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
$contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
$contact_email = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
$contact_address = isset($_POST['contact_address']) ? trim($_POST['contact_address']) : '';
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$refund_reason = isset($_POST['refund_reason']) ? trim($_POST['refund_reason']) : '';

// Validation
if ($order_id <= 0) {
    echo 'error: Mã đơn hàng không hợp lệ.';
    exit();
}

if (empty($contact_name) || empty($contact_phone) || empty($contact_email) || empty($contact_address)) {
    echo 'error: Vui lòng điền đầy đủ thông tin liên hệ.';
    exit();
}

if ($product_id <= 0) {
    echo 'error: Vui lòng chọn sản phẩm cần hoàn trả.';
    exit();
}

if (empty($refund_reason)) {
    echo 'error: Vui lòng nhập lý do hoàn trả.';
    exit();
}

// Verify order belongs to user and is delivered and paid
$sql_order = "SELECT * FROM donhang WHERE ID_DonHang = $order_id AND ID_KhachHang = $customer_id AND TrangThai = 3 AND DaThanhToan = 1";
$result_order = mysqli_query($conn, $sql_order);

if (!$result_order || mysqli_num_rows($result_order) == 0) {
    echo 'error: Đơn hàng không hợp lệ hoặc không thể hoàn trả.';
    exit();
}

$order = mysqli_fetch_array($result_order);

// Check 7-day return window
// Use NgayTao as delivery date approximation (since order status 3 = delivered)
// Note: Ideally we should track actual delivery timestamp, but for now using order creation date
$order_date = strtotime($order['NgayTao']);
$current_date = time();
$days_diff = ($current_date - $order_date) / (60 * 60 * 24);

if ($days_diff > 7) {
    echo 'error: Đơn hàng đã quá 7 ngày kể từ ngày giao hàng. Không thể yêu cầu hoàn trả.';
    exit();
}

// Also check if order is already returned
if (stripos($order['GhiChu'] ?? '', '[ĐÃ HOÀN TRẢ]') !== false) {
    echo 'error: Đơn hàng này đã được hoàn trả trước đó.';
    exit();
}

// Check if return/refund request already exists
$sql_check = "SELECT * FROM return_refund_requests WHERE ID_DonHang = $order_id AND ID_KhachHang = $customer_id AND TrangThai != 'rejected'";
$result_check = mysqli_query($conn, $sql_check);
if ($result_check && mysqli_num_rows($result_check) > 0) {
    echo 'error: Bạn đã gửi yêu cầu hoàn trả cho đơn hàng này.';
    exit();
}

// Create return_refund_requests table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS `return_refund_requests` (
    `ID_Request` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_DonHang` INT NOT NULL,
    `ID_KhachHang` INT NOT NULL,
    `ID_SanPham` INT NOT NULL,
    `ContactName` VARCHAR(255) NOT NULL,
    `ContactPhone` VARCHAR(20) NOT NULL,
    `ContactEmail` VARCHAR(255) NOT NULL,
    `ContactAddress` TEXT NOT NULL,
    `RefundReason` TEXT NOT NULL,
    `Attachments` TEXT NULL,
    `TrangThai` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `ThoiGianTao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ThoiGianCapNhat` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (ID_DonHang),
    INDEX idx_customer (ID_KhachHang),
    INDEX idx_status (TrangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $create_table);

// Handle file uploads
$upload_dir = '../../../AdminManage/src/ReturnRefundAttachments/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$attachments = [];
$allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$allowed_video_types = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo'];

if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
    $files = $_FILES['attachments'];
    $image_count = 0;
    $video_count = 0;
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];
            $file_name = $files['name'][$i];
            $file_tmp = $files['tmp_name'][$i];
            
            // Check file type
            $is_image = in_array($file_type, $allowed_image_types);
            $is_video = in_array($file_type, $allowed_video_types);
            
            if (!$is_image && !$is_video) {
                continue; // Skip invalid file types
            }
            
            // Check file count limits
            if ($is_image && $image_count >= 5) {
                continue; // Skip if already 5 images
            }
            if ($is_video && $video_count >= 2) {
                continue; // Skip if already 2 videos
            }
            
            // Check file size
            $max_size = $is_image ? (5 * 1024 * 1024) : (50 * 1024 * 1024); // 5MB for images, 50MB for videos
            if ($file_size > $max_size) {
                continue; // Skip oversized files
            }
            
            // Generate unique filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'request_' . $order_id . '_' . time() . '_' . $i . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $attachments[] = [
                    'type' => $is_image ? 'image' : 'video',
                    'filename' => $new_filename,
                    'original_name' => $file_name
                ];
                
                if ($is_image) {
                    $image_count++;
                } else {
                    $video_count++;
                }
            }
        }
    }
}

// Escape strings for SQL
$contact_name_escaped = mysqli_real_escape_string($conn, $contact_name);
$contact_phone_escaped = mysqli_real_escape_string($conn, $contact_phone);
$contact_email_escaped = mysqli_real_escape_string($conn, $contact_email);
$contact_address_escaped = mysqli_real_escape_string($conn, $contact_address);
$refund_reason_escaped = mysqli_real_escape_string($conn, $refund_reason);
$attachments_json = json_encode($attachments);

// Insert return/refund request
$sql_insert = "INSERT INTO return_refund_requests 
               (ID_DonHang, ID_KhachHang, ID_SanPham, ContactName, ContactPhone, ContactEmail, ContactAddress, RefundReason, Attachments, TrangThai) 
               VALUES 
               ($order_id, $customer_id, $product_id, '$contact_name_escaped', '$contact_phone_escaped', '$contact_email_escaped', '$contact_address_escaped', '$refund_reason_escaped', '$attachments_json', 'pending')";

$result_insert = mysqli_query($conn, $sql_insert);

if (!$result_insert) {
    echo 'error: Lỗi khi tạo yêu cầu hoàn trả: ' . mysqli_error($conn);
    exit();
}

$request_id = mysqli_insert_id($conn);

// Get product name for notification
$sql_product = "SELECT TenSanPham FROM sanpham WHERE IDSanPham = $product_id";
$result_product = mysqli_query($conn, $sql_product);
$product_name = 'Sản phẩm';
if ($result_product && mysqli_num_rows($result_product) > 0) {
    $product_row = mysqli_fetch_array($result_product);
    $product_name = $product_row['TenSanPham'];
}

// Send WebSocket notification to Admin and Staff
$wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
if (file_exists($wsPushFile)) {
    $old_error_reporting = error_reporting(0);
    $old_display_errors = ini_get('display_errors');
    ini_set('display_errors', 0);
    try {
        require_once($wsPushFile);
        if (function_exists('websocket_push')) {
            $message = "Khách hàng {$contact_name} đã yêu cầu hoàn trả đơn hàng #{$order_id} - Sản phẩm: {$product_name}";
            websocket_push(
                ['admin_channel', 'staff_channel'],
                [
                    'type' => 'return_refund_request',
                    'title' => 'Yêu cầu hoàn trả mới',
                    'message' => $message,
                    'sound' => 'notification',
                    'data' => [
                        'request_id' => $request_id,
                        'order_id' => $order_id,
                        'customer_id' => $customer_id,
                        'customer_name' => $contact_name,
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ]
            );
        }
    } catch (Exception $e) {
        // Silently fail
    }
    error_reporting($old_error_reporting);
    ini_set('display_errors', $old_display_errors);
}

ob_end_clean();
header('Location: ../View/Order_Tracking.php?msg=return_request_submitted');
exit();
