<?php
/**
 * Shipper Orders Page - User Interface
 * Allows shippers to view and accept orders from the User UI
 */
include("../DB_Connect.php");
include("../Layouts/Head.php");
// Session is already started in Head.php, so we don't need to start it again

// Check if user is shipper
$user_role = $_SESSION['user_role'] ?? 'customer';
$user_id = $_SESSION['IDNguoiDung'] ?? 0;

if ($user_role !== 'shipper') {
    header("Location: index.php");
    exit();
}

// Ensure ID_Shipper column exists (check first to avoid duplicate column error)
$check_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'ID_Shipper'");
$col_exists = false;
if ($check_col) {
    $col_row = mysqli_fetch_assoc($check_col);
    $col_exists = intval($col_row['c']) > 0;
}
if (!$col_exists) {
    @mysqli_query($conn, "ALTER TABLE `donhang` ADD `ID_Shipper` INT NULL AFTER `DaThanhToan`");
}

// Handle order acceptance
if (isset($_GET['accept_order']) && $_GET['accept_order'] > 0) {
    $order_id = intval($_GET['accept_order']);
    // Update order: set status to 2 (Preparing) and assign shipper
    $result = mysqli_query($conn, "UPDATE donhang SET TrangThai = 2, ID_Shipper = $user_id WHERE ID_DonHang = $order_id AND TrangThai = 1");
    
    if ($result) {
        // Get shipper name
        $shipper_query = mysqli_query($conn, "SELECT HoVaTen, TenDangNhap FROM khachhang WHERE ID_KhachHang = $user_id");
        $shipper_info = mysqli_fetch_array($shipper_query);
        $shipper_name = $shipper_info['HoVaTen'] ?? $shipper_info['TenDangNhap'] ?? 'Shipper';
        
        // Get order info
        $order_query = mysqli_query($conn, "SELECT ID_KhachHang, HoVaTen FROM donhang WHERE ID_DonHang = $order_id");
        $order_info = mysqli_fetch_array($order_query);
        $customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
        $customer_id = $order_info['ID_KhachHang'] ?? null;
        
        // Send WebSocket notification to Admin, User, and Staff
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            
            // Notify Admin
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['admin_channel'],
                    [
                        'type' => 'order_accepted_by_shipper',
                        'title' => 'Đơn hàng đã được shipper nhận',
                        'message' => "Đơn hàng #{$order_id} của khách hàng {$customer_name} đã được shipper {$shipper_name} nhận và đang chuẩn bị giao hàng",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'shipper_name' => $shipper_name,
                            'shipper_id' => $user_id,
                            'status' => 2,
                            'status_text' => 'Đang chuẩn bị giao hàng',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
            
            // Notify User
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'order_status_update',
                        'title' => 'Đơn hàng đang được giao',
                        'message' => "Đơn hàng #{$order_id} của bạn đã được shipper {$shipper_name} nhận và đang chuẩn bị giao hàng",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_id' => $customer_id,
                            'shipper_name' => $shipper_name,
                            'status' => 2,
                            'status_text' => 'Đang chuẩn bị giao hàng',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
            
            // Notify Staff
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['staff_channel'],
                    [
                        'type' => 'order_accepted_by_shipper',
                        'title' => 'Đơn hàng đã được shipper nhận',
                        'message' => "Shipper {$shipper_name} đã nhận đơn hàng #{$order_id} của khách hàng {$customer_name}",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'shipper_name' => $shipper_name,
                            'status' => 2,
                            'status_text' => 'Đang chuẩn bị giao hàng',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
    }
    
    header('Location: Shipper_Orders.php?msg=accepted');
    exit();
}

// Handle delivery confirmation
if (isset($_GET['confirm_delivery']) && $_GET['confirm_delivery'] > 0) {
    $order_id = intval($_GET['confirm_delivery']);
    // Update order: set status to 3 (Delivered) and DaThanhToan = 1 (Paid)
    // When shipper confirms successful delivery, order is automatically marked as paid
    // NOTE: Stock is NOT reduced here - it was already reduced when admin approved the order
    $result_update = mysqli_query($conn, "UPDATE donhang SET TrangThai = 3, DaThanhToan = 1 WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id AND TrangThai = 2");
    
    if ($result_update) {
        // Get shipper name
        $shipper_query = mysqli_query($conn, "SELECT HoVaTen, TenDangNhap FROM khachhang WHERE ID_KhachHang = $user_id");
        $shipper_info = mysqli_fetch_array($shipper_query);
        $shipper_name = $shipper_info['HoVaTen'] ?? $shipper_info['TenDangNhap'] ?? 'Shipper';
        
        // Get order info
        $order_query = mysqli_query($conn, "SELECT ID_KhachHang, HoVaTen FROM donhang WHERE ID_DonHang = $order_id");
        $order_info = mysqli_fetch_array($order_query);
        $customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
        $customer_id = $order_info['ID_KhachHang'] ?? null;
        
        // Send WebSocket notification to Admin, User, and Staff
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            
            // Notify Admin with detailed message
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['admin_channel'],
                    [
                        'type' => 'order_delivered',
                        'title' => 'Đơn hàng đã được giao thành công',
                        'message' => "Đơn hàng #{$order_id} của khách hàng {$customer_name} đã được shipper {$shipper_name} giao thành công",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'shipper_name' => $shipper_name,
                            'shipper_id' => $user_id,
                            'status' => 3,
                            'status_text' => 'Đã giao thành công',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
            
            // Notify User
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'order_delivered',
                        'title' => 'Đơn hàng đã được giao thành công!',
                        'message' => "Đơn hàng #{$order_id} của bạn đã được shipper {$shipper_name} giao thành công",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_id' => $customer_id,
                            'shipper_name' => $shipper_name,
                            'status' => 3,
                            'status_text' => 'Đã giao thành công',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
            
            // Notify Staff
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['staff_channel'],
                    [
                        'type' => 'order_delivered',
                        'title' => 'Đơn hàng đã được giao thành công',
                        'message' => "Shipper {$shipper_name} đã giao thành công đơn hàng #{$order_id} của khách hàng {$customer_name}",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'shipper_name' => $shipper_name,
                            'status' => 3,
                            'status_text' => 'Đã giao thành công',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
    }
    
    header('Location: Shipper_Orders.php?msg=delivered');
    exit();
}

// Handle customer refusal/rejection
if (isset($_POST['customer_refused']) && isset($_POST['order_id']) && isset($_POST['refusal_reason'])) {
    $order_id = intval($_POST['order_id']);
    $refusal_reason = mysqli_real_escape_string($conn, trim($_POST['refusal_reason']));
    
    if (empty($refusal_reason)) {
        header('Location: Shipper_Orders.php?msg=refusal_error');
        exit();
    }
    
    // Update order with refusal status and reason
    // We'll use a special status or add to GhiChu
    // For now, we'll add to GhiChu and set a flag
    $current_ghi_chu = '';
    $order_info = mysqli_fetch_array(mysqli_query($conn, "SELECT GhiChu FROM donhang WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id"));
    if ($order_info) {
        $current_ghi_chu = $order_info['GhiChu'] ?? '';
    }
    
    $updated_ghi_chu = $current_ghi_chu . ' | [KHÁCH HÀNG TỪ CHỐI] Lý do: ' . $refusal_reason;
    
    // CRITICAL: Update order status to "Canceled" (-1) when shipper reports customer refusal
    // This removes "Out for Delivery" status in Admin interface
    $updated_ghi_chu_escaped = mysqli_real_escape_string($conn, $updated_ghi_chu);
    $result_update = mysqli_query($conn, "UPDATE donhang SET TrangThai = -1, GhiChu = '$updated_ghi_chu_escaped' WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id AND TrangThai = 2");
    
    if ($result_update) {
        // Get order info for notifications
        $order_query = mysqli_query($conn, "SELECT ID_KhachHang, HoVaTen FROM donhang WHERE ID_DonHang = $order_id");
        $order_info = mysqli_fetch_array($order_query);
        $customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
        $customer_id = $order_info['ID_KhachHang'] ?? null;
        
        $shipper_query = mysqli_query($conn, "SELECT HoVaTen, TenDangNhap FROM khachhang WHERE ID_KhachHang = $user_id");
        $shipper_info = mysqli_fetch_array($shipper_query);
        $shipper_name = $shipper_info['HoVaTen'] ?? $shipper_info['TenDangNhap'] ?? 'Shipper';
        
        // Send WebSocket notification to Admin and Staff
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['admin_channel', 'staff_channel'],
                    [
                        'type' => 'customer_refused_delivery',
                        'title' => 'Khách hàng từ chối nhận hàng',
                        'message' => "Đơn hàng #{$order_id} của khách hàng {$customer_name} đã bị từ chối bởi shipper {$shipper_name}. Lý do: {$refusal_reason}",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'customer_id' => $customer_id,
                            'shipper_name' => $shipper_name,
                            'shipper_id' => $user_id,
                            'refusal_reason' => $refusal_reason,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
    }
    
    header('Location: Shipper_Orders.php?msg=customer_refused');
    exit();
}

// Handle shipper returned goods to shop
if (isset($_GET['returned_to_shop']) && $_GET['returned_to_shop'] > 0) {
    $order_id = intval($_GET['returned_to_shop']);
    
    // Update order GhiChu to mark goods as returned
    $current_ghi_chu = '';
    $order_info = mysqli_fetch_array(mysqli_query($conn, "SELECT GhiChu FROM donhang WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id"));
    if ($order_info) {
        $current_ghi_chu = $order_info['GhiChu'] ?? '';
    }
    
    // Check if already marked as returned
    if (stripos($current_ghi_chu, '[ĐÃ TRẢ VỀ CỬA HÀNG]') === false) {
        $updated_ghi_chu = $current_ghi_chu . ' | [ĐÃ TRẢ VỀ CỬA HÀNG]';
        mysqli_query($conn, "UPDATE donhang SET GhiChu = '$updated_ghi_chu' WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id");
        
        // Send notification to Admin and Staff
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                $shipper_query = mysqli_query($conn, "SELECT HoVaTen, TenDangNhap FROM khachhang WHERE ID_KhachHang = $user_id");
                $shipper_info = mysqli_fetch_array($shipper_query);
                $shipper_name = $shipper_info['HoVaTen'] ?? $shipper_info['TenDangNhap'] ?? 'Shipper';
                
                websocket_push(
                    ['admin_channel', 'staff_channel'],
                    [
                        'type' => 'goods_returned_to_shop',
                        'title' => 'Hàng đã được trả về cửa hàng',
                        'message' => "Shipper {$shipper_name} đã trả hàng về cửa hàng cho đơn hàng #{$order_id}",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'shipper_name' => $shipper_name,
                            'shipper_id' => $user_id,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
    }
    
    header('Location: Shipper_Orders.php?msg=returned_to_shop');
    exit();
}

// Get available orders (status = 1, approved by admin, no shipper assigned yet)
// Exclude cancelled orders (status = -1 or GhiChu contains [ĐÃ HỦY])
// Order: oldest at top, newest at bottom (ASC - ascending order by ID or date)
$sql_available = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi 
                  FROM donhang d
                  LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                  WHERE d.TrangThai = 1 
                  AND (d.ID_Shipper IS NULL OR d.ID_Shipper = 0)
                  AND d.TrangThai != -1
                  AND (d.GhiChu IS NULL OR d.GhiChu NOT LIKE '%[ĐÃ HỦY]%')
                  ORDER BY COALESCE(d.NgayTao, d.ID_DonHang) ASC, d.ID_DonHang ASC";
$result_available = mysqli_query($conn, $sql_available);

// Handle payment confirmation by shipper
if (isset($_GET['confirm_payment']) && $_GET['confirm_payment'] > 0) {
    $order_id = intval($_GET['confirm_payment']);
    
    // Ensure DaThanhToan column exists
    $check_paid_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'DaThanhToan'");
    $paid_col_exists = false;
    if ($check_paid_col) {
        $paid_col_row = mysqli_fetch_assoc($check_paid_col);
        $paid_col_exists = intval($paid_col_row['c']) > 0;
    }
    if (!$paid_col_exists) {
        @mysqli_query($conn, "ALTER TABLE `donhang` ADD `DaThanhToan` TINYINT(1) NOT NULL DEFAULT 0 AFTER `TrangThai`");
    }
    
    // Update payment status - only if order is assigned to this shipper
    $result = mysqli_query($conn, "UPDATE donhang SET DaThanhToan = 1 WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id");
    
    if ($result) {
        // Get shipper name
        $shipper_query = mysqli_query($conn, "SELECT HoVaTen, TenDangNhap FROM khachhang WHERE ID_KhachHang = $user_id");
        $shipper_info = mysqli_fetch_array($shipper_query);
        $shipper_name = $shipper_info['HoVaTen'] ?? $shipper_info['TenDangNhap'] ?? 'Shipper';
        
        // Get order info
        $order_query = mysqli_query($conn, "SELECT ID_KhachHang, HoVaTen, TongTien FROM donhang WHERE ID_DonHang = $order_id");
        $order_info = mysqli_fetch_array($order_query);
        $customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
        $customer_id = $order_info['ID_KhachHang'] ?? null;
        $total_amount = $order_info['TongTien'] ?? 0;
        
        // Send WebSocket notification to Admin, Staff, and User
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            
            // Notify Admin and Staff
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['admin_channel', 'staff_channel'],
                    [
                        'type' => 'order_paid_by_shipper',
                        'title' => 'Đơn hàng đã được shipper xác nhận thanh toán',
                        'message' => "Shipper {$shipper_name} đã xác nhận đơn hàng #{$order_id} của khách hàng {$customer_name} đã được thanh toán. Tổng tiền: " . number_format($total_amount, 0, ',', '.') . " đ",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_name' => $customer_name,
                            'shipper_name' => $shipper_name,
                            'shipper_id' => $user_id,
                            'total_amount' => $total_amount,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
            
            // Notify User
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'order_paid',
                        'title' => 'Đơn hàng đã thanh toán',
                        'message' => "Đơn hàng #{$order_id} của bạn đã được shipper {$shipper_name} xác nhận thanh toán",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $order_id,
                            'customer_id' => $customer_id,
                            'shipper_name' => $shipper_name,
                            'total_amount' => $total_amount,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
    }
    
    header('Location: Shipper_Orders.php?msg=payment_confirmed');
    exit();
}

// Get my assigned orders (status = 2, assigned to me)
// Order: oldest at top, newest at bottom (ASC - ascending order)
// Ensure DaThanhToan column exists
$check_paid_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'DaThanhToan'");
$paid_col_exists = false;
if ($check_paid_col) {
    $paid_col_row = mysqli_fetch_assoc($check_paid_col);
    $paid_col_exists = intval($paid_col_row['c']) > 0;
}
if (!$paid_col_exists) {
    @mysqli_query($conn, "ALTER TABLE `donhang` ADD `DaThanhToan` TINYINT(1) NOT NULL DEFAULT 0 AFTER `TrangThai`");
}

// Get my assigned orders: 
// - Status 2 (delivering) OR 
// - Status 3 (delivered) but NOT paid yet (DaThanhToan = 0)
// Hide orders only when BOTH delivered (status 3) AND paid (DaThanhToan = 1)
// Also exclude cancelled orders (status = -1 or GhiChu contains [ĐÃ HỦY])
$sql_my_orders = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi 
                  FROM donhang d
                  LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                  WHERE d.ID_Shipper = $user_id 
                  AND d.TrangThai != -1
                  AND (d.GhiChu IS NULL OR d.GhiChu NOT LIKE '%[ĐÃ HỦY]%')
                  AND (d.TrangThai = 2 OR (d.TrangThai = 3 AND (d.DaThanhToan IS NULL OR d.DaThanhToan = 0)))
                  ORDER BY d.ID_DonHang ASC";
$result_my_orders = mysqli_query($conn, $sql_my_orders);
?>
<body class="bg-light">
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="fas fa-truck me-2"></i>Quản Lý Đơn Hàng - Shipper</h2>
                
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-<?php 
                        echo $_GET['msg'] === 'accepted' ? 'success' : 
                            ($_GET['msg'] === 'delivered' ? 'info' : 
                            ($_GET['msg'] === 'payment_confirmed' ? 'success' : 'info')); 
                    ?> alert-dismissible fade show">
                        <?php 
                        if ($_GET['msg'] === 'accepted') {
                            echo 'Đã nhận đơn hàng thành công!';
                        } elseif ($_GET['msg'] === 'delivered') {
                            echo 'Đã xác nhận giao hàng thành công!';
                        } elseif ($_GET['msg'] === 'payment_confirmed') {
                            echo 'Đã xác nhận thanh toán thành công!';
                        } else {
                            echo 'Thao tác thành công!';
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Available Orders Section -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Đơn Hàng Có Sẵn (Chưa được nhận)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã ĐH</th>
                                        <th>Khách hàng</th>
                                        <th>Địa chỉ</th>
                                        <th>Tổng tiền</th>
                                        <th>Ngày đặt</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($result_available && mysqli_num_rows($result_available) > 0):
                                        while ($row = mysqli_fetch_array($result_available)): 
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['HoVaTen']); ?></strong><br>
                                                <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['SDT']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                            <td><strong><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</strong></td>
                                            <td><?php echo isset($row['NgayTao']) ? date('d/m/Y H:i', strtotime($row['NgayTao'])) : 'N/A'; ?></td>
                                            <td>
                                                <a href="?accept_order=<?php echo $row['ID_DonHang']; ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   onclick="return confirm('Bạn có chắc muốn nhận đơn hàng #<?php echo $row['ID_DonHang']; ?>?');">
                                                    <i class="fas fa-check"></i> Nhận đơn
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                Hiện tại không có đơn hàng nào sẵn sàng để nhận
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- My Orders Section -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Đơn Hàng Của Tôi</h5>
                        <a href="Shipper_Delivered_Orders.php" class="btn btn-light btn-sm">
                            <i class="fas fa-history me-1"></i>Xem lịch sử đã giao
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã ĐH</th>
                                        <th>Khách hàng</th>
                                        <th>Địa chỉ</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thanh toán</th>
                                        <th>Ngày đặt</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($result_my_orders && mysqli_num_rows($result_my_orders) > 0):
                                        while ($row = mysqli_fetch_array($result_my_orders)): 
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['HoVaTen']); ?></strong><br>
                                                <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['SDT']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                            <td><strong><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</strong></td>
                                            <td>
                                                <?php 
                                                $order_status = intval($row['TrangThai']);
                                                $is_delivered = ($order_status == 3);
                                                if ($is_delivered) {
                                                    echo '<span class="badge bg-info"><i class="fas fa-check-circle"></i> Đã giao</span>';
                                                } else {
                                                    echo '<span class="badge bg-primary"><i class="fas fa-truck"></i> Đang giao</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $is_paid = isset($row['DaThanhToan']) && intval($row['DaThanhToan']) == 1;
                                                if ($is_paid) {
                                                    echo '<span class="badge bg-success"><i class="fas fa-check"></i> Đã thanh toán</span>';
                                                } else {
                                                    echo '<span class="badge bg-warning"><i class="fas fa-clock"></i> Chưa thanh toán</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo isset($row['NgayTao']) ? date('d/m/Y H:i', strtotime($row['NgayTao'])) : 'N/A'; ?></td>
                                            <td>
                                                <?php 
                                                // Check if customer refused
                                                $ghi_chu = isset($row['GhiChu']) ? $row['GhiChu'] : '';
                                                $customer_refused = (stripos($ghi_chu, '[KHÁCH HÀNG TỪ CHỐI]') !== false);
                                                $returned_to_shop = (stripos($ghi_chu, '[ĐÃ TRẢ VỀ CỬA HÀNG]') !== false);
                                                
                                                if ($order_status == 2): 
                                                    if ($customer_refused && !$returned_to_shop):
                                                        // Customer refused - show return to shop button
                                                ?>
                                                        <div class="alert alert-warning p-2 mb-2">
                                                            <small><strong>Lý do từ chối:</strong><br>
                                                            <?php 
                                                            if (preg_match('/\[KHÁCH HÀNG TỪ CHỐI\]\s*Lý do:\s*(.+?)(?:\||$)/i', $ghi_chu, $matches)) {
                                                                echo htmlspecialchars(trim($matches[1]));
                                                            }
                                                            ?>
                                                            </small>
                                                        </div>
                                                        <a href="?returned_to_shop=<?php echo $row['ID_DonHang']; ?>" 
                                                           class="btn btn-sm btn-warning btn-block"
                                                           onclick="return confirm('Xác nhận đã trả hàng về cửa hàng?');">
                                                            <i class="fas fa-store me-1"></i> Đã trả hàng về cửa hàng
                                                        </a>
                                                <?php 
                                                    else:
                                                        // Normal delivery buttons
                                                ?>
                                                        <div class="btn-group-vertical" role="group">
                                                            <a href="?confirm_delivery=<?php echo $row['ID_DonHang']; ?>" 
                                                               class="btn btn-sm btn-success mb-1" 
                                                               onclick="return confirm('Xác nhận đã giao hàng thành công cho đơn hàng #<?php echo $row['ID_DonHang']; ?>?');">
                                                                <i class="fas fa-check-circle"></i> Giao hàng thành công
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger" 
                                                                    onclick="openCustomerRefusalModal(<?php echo $row['ID_DonHang']; ?>)">
                                                                <i class="fas fa-times-circle"></i> Khách hàng từ chối
                                                            </button>
                                                        </div>
                                                <?php 
                                                    endif;
                                                elseif ($order_status == 3 && !$is_paid): ?>
                                                    <!-- Delivered but not paid yet (shouldn't happen with new logic, but keep for safety) -->
                                                    <span class="text-muted small">Đã giao hàng</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Đã hoàn thành</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-clipboard fa-2x mb-2"></i><br>
                                                Bạn chưa có đơn hàng nào đang giao
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Refusal Modal -->
    <div class="modal fade" id="customerRefusalModal" tabindex="-1" aria-labelledby="customerRefusalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="customerRefusalModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Khách Hàng Từ Chối Nhận Hàng
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="customerRefusalForm" method="POST" action="">
                    <input type="hidden" name="customer_refused" value="1">
                    <input type="hidden" name="order_id" id="refusalOrderId" value="">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Vui lòng nhập lý do khách hàng từ chối nhận hàng.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lý do từ chối <span class="text-danger">*</span></label>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPresetReason('Không thể liên hệ khách hàng (sau 3 lần thử)')">
                                    <i class="fas fa-phone-slash me-1"></i> Không thể liên hệ khách hàng (sau 3 lần thử)
                                </button>
                            </div>
                            <textarea class="form-control" name="refusal_reason" id="refusalReason" rows="4" placeholder="Nhập lý do khách hàng từ chối nhận hàng..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check me-2"></i>Xác nhận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openCustomerRefusalModal(orderId) {
            document.getElementById('refusalOrderId').value = orderId;
            document.getElementById('refusalReason').value = '';
            var modal = new bootstrap.Modal(document.getElementById('customerRefusalModal'));
            modal.show();
        }
        
        function setPresetReason(reason) {
            document.getElementById('refusalReason').value = reason;
        }
    </script>
    
    <?php include("../Layouts/Footer.php"); ?>
    <?php include("../Layouts/Link.php"); ?>
</body>
</html>

