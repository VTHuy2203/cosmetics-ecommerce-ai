<?php
require_once "../DB_Connect.php";

// Kiểm tra form gửi là post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['User_id'])) {
    // Lấy dữ liệu từ form
    $User_id = $_POST['User_id'];
    $hoVaTen = $_POST['hoVaTen'];
    $email = $_POST['email'];
    $SDT = $_POST['SDT'];
    $diaChi = $_POST['diaChi'];


    // Thực hiện truy vấn để cập nhật thông tin user trong CSDL
    $update_sql = "UPDATE khachhang SET HoVaTen='$hoVaTen', Email='$email', SDT='$SDT', DiaChi='$diaChi'  WHERE ID_KhachHang='$User_id'";

    $result = mysqli_query($conn, $update_sql);

    if ($result) {
        // Check if user is a shipper (exclude shippers from notification)
        $check_shipper = mysqli_query($conn, "SELECT COUNT(*) AS c FROM shipper_requests WHERE user_id = '$User_id'");
        $is_shipper = false;
        if ($check_shipper) {
            $shipper_row = mysqli_fetch_assoc($check_shipper);
            $is_shipper = intval($shipper_row['c']) > 0;
        }
        
        // Send WebSocket notification to Staff (only for customers, not shippers)
        if (!$is_shipper) {
            $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
            if (file_exists($wsPushFile)) {
                require_once($wsPushFile);
                if (function_exists('websocket_push_customer_update')) {
                    websocket_push_customer_update($User_id, $hoVaTen, 'update');
                }
            }
        }
    }

    header('Location: Manage_Users.php');
    exit();
} else {
    header('Location: Manage_Users.php');
    exit();
}

mysqli_close($conn);
?>