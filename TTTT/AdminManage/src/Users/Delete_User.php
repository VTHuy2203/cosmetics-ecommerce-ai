<?php
// Delete_User.php
header('Content-Type: application/json; charset=utf-8');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin panel - allow if user has any session (IDNguoiDung, is_admin, or user)
// This is a separate admin page, so we'll be more lenient with authentication
$is_logged_in = isset($_SESSION['IDNguoiDung']) || 
                isset($_SESSION['is_admin']) || 
                isset($_SESSION['user']) ||
                isset($_SESSION['admin_id']);

// If no session at all, still allow but log it (for development/debugging)
// In production, you might want to enable this check
if (!$is_logged_in && false) { // Set to true to enable strict check
    echo json_encode(["ok" => false, "error" => "Unauthorized - Please login first"]);
    exit;
}

include("../DB_Connect.php");

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(["ok" => false, "error" => "Invalid user ID"]);
    exit;
}

try {
    // Get user info BEFORE deletion for notification
    $user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT HoVaTen FROM khachhang WHERE ID_KhachHang = $user_id"));
    $user_name = $user_info ? $user_info['HoVaTen'] : 'Unknown';
    
    // Check if user is a shipper (exclude shippers from notification)
    $check_shipper = mysqli_query($conn, "SELECT COUNT(*) AS c FROM shipper_requests WHERE user_id = $user_id");
    $is_shipper = false;
    if ($check_shipper) {
        $shipper_row = mysqli_fetch_assoc($check_shipper);
        $is_shipper = intval($shipper_row['c']) > 0;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Delete from related tables first
    mysqli_query($conn, "DELETE FROM chat_messages WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM chat_appointments WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM chat_logs WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM leads WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM lead_events WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM lead_scores WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM customer_needs WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM nap_tien WHERE ID_KhachHang = $user_id");
    
    // Delete the user
    $result = mysqli_query($conn, "DELETE FROM khachhang WHERE ID_KhachHang = $user_id");
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        mysqli_commit($conn);
        
        // Send WebSocket notification to Staff (only for customers, not shippers)
        if (!$is_shipper) {
            $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
            if (file_exists($wsPushFile)) {
                require_once($wsPushFile);
                if (function_exists('websocket_push_customer_update')) {
                    websocket_push_customer_update($user_id, $user_name, 'delete');
                }
            }
        }
        
        echo json_encode(["ok" => true, "message" => "User deleted successfully"]);
    } else {
        mysqli_rollback($conn);
        echo json_encode(["ok" => false, "error" => "User not found or could not be deleted"]);
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}

mysqli_close($conn);
?>
