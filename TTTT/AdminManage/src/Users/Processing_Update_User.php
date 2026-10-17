<?php
require_once "../DB_Connect.php";
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';

// Kiểm tra form gửi là post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['User_id'])) {
    require_csrf();
    // Lấy dữ liệu từ form
    $User_id = filter_var($_POST['User_id'], FILTER_VALIDATE_INT);
    $hoVaTen = trim((string) ($_POST['hoVaTen'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $SDT = trim((string) ($_POST['SDT'] ?? ''));
    $diaChi = trim((string) ($_POST['diaChi'] ?? ''));
    if ($User_id === false || $User_id < 1 || $hoVaTen === '' || mb_strlen($hoVaTen) > 100
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9+().\s-]{8,20}$/', $SDT)
        || $diaChi === '' || mb_strlen($diaChi) > 255) {
        http_response_code(422);
        exit('invalid_user_data');
    }


    // Thực hiện truy vấn để cập nhật thông tin user trong CSDL
    $updateStatement = $conn->prepare('UPDATE khachhang SET HoVaTen = ?, Email = ?, SDT = ?, DiaChi = ? WHERE ID_KhachHang = ?');
    $updateStatement->bind_param('ssssi', $hoVaTen, $email, $SDT, $diaChi, $User_id);
    $result = $updateStatement->execute();

    if ($result) {
        // Check if user is a shipper (exclude shippers from notification)
        $shipperStatement = $conn->prepare('SELECT COUNT(*) AS c FROM shipper_requests WHERE user_id = ?');
        $shipperStatement->bind_param('i', $User_id);
        $shipperStatement->execute();
        $check_shipper = $shipperStatement->get_result();
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
