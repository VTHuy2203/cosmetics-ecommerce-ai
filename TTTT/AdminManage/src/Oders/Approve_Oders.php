<?php
// Start output buffering to prevent "headers already sent" error
ob_start();

include("../Layouts/headHTML.php");

include("../DB_Connect.php");

// Staff can approve orders, Admin/Manager have full access
// No restriction needed here - access is controlled by Admin_Access_Check.php

// Get pending orders (TrangThai = 0 - not yet approved) sorted by newest first
$sql = "SELECT * FROM donhang WHERE TrangThai = '0' ORDER BY NgayTao DESC";

$result = mysqli_query($conn, $sql);

// Kiểm tra nếu có lỗi trong quá trình truy vấn
if (!$result) {
    die("Có lỗi xảy ra: " . mysqli_error($conn));
}

//duyệt đơn hàng
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'approve') {
    require_csrf();
    // CRITICAL: Use ONLY admin session variables (completely ignore UI session)
    // Staff access is determined by admin_role via Admin_Login.php
    $admin_role = $_SESSION['admin_role'] ?? null;
    $is_staff_via_admin = ($admin_role === 'employee');
    $is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');
    
    // Allow staff (via admin login), admin, or manager to approve orders
    if (!$is_staff_via_admin && !$is_admin_or_manager) {
        header('Location: Approve_Oders.php?error=unauthorized');
        exit();
    }
    $oder_id = (int) ($_POST['order_id'] ?? 0);
    $transition = $conn->prepare(
        "UPDATE donhang SET TrangThai = 1
         WHERE ID_DonHang = ? AND TrangThai = 0
         AND (PhuongThucThanhToan = 'cod' OR TrangThaiThanhToan = 'paid')"
    );
    $transition->bind_param('i', $oder_id);
    $transition->execute();
    $result_update_trangthai = $transition->affected_rows === 1;
    if (!$result_update_trangthai) {
        header('Location: Approve_Oders.php?error=invalid_state');
        exit();
    }
    
    // Log approval attempt for debugging
    error_log("Approve_Oders.php: Processing approval for order #{$oder_id}");
    error_log("Approve_Oders.php: Update result: " . ($result_update_trangthai ? 'SUCCESS' : 'FAILED: ' . mysqli_error($conn)));
    
    // CRITICAL: Get order info FIRST, before any database operations that might fail
    // This ensures we have the data needed for notifications even if other operations fail
    $orderStatement = $conn->prepare('SELECT ID_KhachHang, HoVaTen FROM donhang WHERE ID_DonHang = ?');
    $orderStatement->bind_param('i', $oder_id);
    $orderStatement->execute();
    $order_info = $orderStatement->get_result()->fetch_assoc();
    $customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
    $customer_id = $order_info['ID_KhachHang'] ?? null;
    
    // Get staff/admin name who approved the order
    $staff_name = 'Admin';
    if (isset($_SESSION['admin_name'])) {
        $staff_name = $_SESSION['admin_name'];
    } elseif (isset($_SESSION['admin_username'])) {
        $staff_name = $_SESSION['admin_username'];
    }
    $is_staff = ($admin_role === 'employee');
    $approver_name = $is_staff ? $staff_name : 'Admin';
    
    // Stock is reserved atomically when checkout creates the order.
    
    // CRITICAL: Send WebSocket notifications REGARDLESS of whether product quantity update succeeded
    // Notifications must be sent even if other operations fail
    $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
    $notifications_sent = false;
    $notification_count = 0;
    
    error_log("Approve_Oders.php: Attempting to send notifications for order #{$oder_id}");
    error_log("Approve_Oders.php: WebSocket file path: {$wsPushFile}");
    error_log("Approve_Oders.php: File exists: " . (file_exists($wsPushFile) ? 'YES' : 'NO'));
    error_log("Approve_Oders.php: Is Staff: " . ($is_staff ? 'YES' : 'NO'));
    error_log("Approve_Oders.php: Approver Name: {$approver_name}");
    
    if (file_exists($wsPushFile)) {
        try {
            require_once($wsPushFile);
            error_log("Approve_Oders.php: WebSocket_Push.php loaded successfully");
            
            if (!function_exists('websocket_push')) {
                error_log("Approve_Oders.php: ERROR - websocket_push function not found after require_once");
            } else {
                error_log("Approve_Oders.php: websocket_push function is available");
            }
            
            // 1. Notify Shipper - Order is ready to be accepted (IMPORTANT: Remind to refresh page)
            if (function_exists('websocket_push')) {
                error_log("Approve_Oders.php: Sending notification to shipper_channel for order #{$oder_id}");
                $shipper_message = $is_staff 
                    ? "Đơn hàng #{$oder_id} từ khách hàng {$customer_name} đã được nhân viên {$approver_name} duyệt và sẵn sàng để nhận. Vui lòng làm mới trang để xem đơn hàng mới!"
                    : "Đơn hàng #{$oder_id} từ khách hàng {$customer_name} đã được {$approver_name} duyệt và sẵn sàng để nhận. Vui lòng làm mới trang để xem đơn hàng mới!";
                
                $shipper_result = websocket_push(
                    ['shipper_channel'],
                    [
                        'type' => 'order_approved',
                        'title' => 'Đơn hàng mới cần giao',
                        'message' => $shipper_message,
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
                            'customer_name' => $customer_name,
                            'approver_name' => $approver_name,
                            'is_staff_approved' => $is_staff,
                            'status' => 1,
                            'status_text' => 'Đã duyệt',
                            'refresh_required' => true,
                            'refresh_message' => 'Vui lòng làm mới trang để xem đơn hàng mới được gán',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                if ($shipper_result) {
                    $notifications_sent = true;
                    $notification_count++;
                    error_log("Approve_Oders.php: ✅ Shipper notification sent successfully for order #{$oder_id}");
                } else {
                    error_log("Approve_Oders.php: ❌ Shipper notification failed for order #{$oder_id}");
                }
            } else {
                error_log("Approve_Oders.php: ❌ websocket_push function not found");
            }
            
            // 2. Notify Customer/User - Order confirmed (IMPORTANT: Only notify the customer who owns this order)
            if (function_exists('websocket_push') && $customer_id) {
                error_log("Approve_Oders.php: Sending notification to user_channel for order #{$oder_id} (customer_id: {$customer_id})");
                $user_message = $is_staff 
                    ? "Đơn hàng #{$oder_id} của bạn đã được nhân viên {$approver_name} xác nhận và đang được chuẩn bị"
                    : "Đơn hàng #{$oder_id} của bạn đã được {$approver_name} xác nhận và đang được chuẩn bị";
                
                $user_result = websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'order_confirmed',
                        'title' => 'Đơn hàng đã được xác nhận!',
                        'message' => $user_message,
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
                            'customer_id' => $customer_id,
                            'status' => 1,
                            'status_text' => 'Đã duyệt',
                            'approver_name' => $approver_name,
                            'is_staff_approved' => $is_staff,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                if ($user_result) {
                    $notifications_sent = true;
                    $notification_count++;
                    error_log("Approve_Oders.php: ✅ Customer notification sent successfully for order #{$oder_id}");
                } else {
                    error_log("Approve_Oders.php: ❌ Customer notification failed for order #{$oder_id}");
                }
            } else {
                if (!$customer_id) {
                    error_log("Approve_Oders.php: ⚠️ Customer ID not found for order #{$oder_id}, skipping customer notification");
                }
            }
            
            // 3. Notify Admin - IMPORTANT: Always notify admin when staff approves an order
            if (function_exists('websocket_push')) {
                $admin_title = $is_staff ? 'Đơn hàng đã được nhân viên duyệt' : 'Đơn hàng đã được duyệt';
                $admin_message = $is_staff 
                    ? "Nhân viên {$approver_name} đã duyệt đơn hàng #{$oder_id} của khách hàng {$customer_name}"
                    : "Đơn hàng #{$oder_id} của khách hàng {$customer_name} đã được {$approver_name} duyệt";
                
                error_log("Approve_Oders.php: Sending notification to admin_channel for order #{$oder_id}");
                $admin_result = websocket_push(
                    ['admin_channel'],
                    [
                        'type' => $is_staff ? 'order_approved_by_staff' : 'order_confirmed',
                        'title' => $admin_title,
                        'message' => $admin_message,
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
                            'customer_name' => $customer_name,
                            'customer_id' => $customer_id,
                            'approver_name' => $approver_name,
                            'is_staff_approved' => $is_staff,
                            'status' => 1,
                            'status_text' => 'Đã duyệt',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                if ($admin_result) {
                    $notifications_sent = true;
                    $notification_count++;
                    error_log("Approve_Oders.php: ✅ Admin notification sent successfully for order #{$oder_id}");
                } else {
                    error_log("Approve_Oders.php: ❌ Admin notification failed for order #{$oder_id}");
                }
            }
            
            // 4. Notify Staff channel - ALWAYS notify staff when order is approved (by staff or admin)
            if (function_exists('websocket_push')) {
                $staff_title = $is_staff ? 'Đơn hàng đã được nhân viên duyệt' : 'Đơn hàng đã được duyệt';
                $staff_message = $is_staff 
                    ? "Nhân viên {$approver_name} đã duyệt đơn hàng #{$oder_id} của khách hàng {$customer_name}"
                    : "Đơn hàng #{$oder_id} của khách hàng {$customer_name} đã được {$approver_name} duyệt";
                
                error_log("Approve_Oders.php: Sending notification to staff_channel for order #{$oder_id}");
                $staff_result = websocket_push(
                    ['staff_channel'],
                    [
                        'type' => 'order_approved_by_staff',
                        'title' => $staff_title,
                        'message' => $staff_message,
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
                            'customer_name' => $customer_name,
                            'approver_name' => $approver_name,
                            'is_staff_approved' => $is_staff,
                            'status' => 1,
                            'status_text' => 'Đã duyệt',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                if ($staff_result) {
                    $notifications_sent = true;
                    $notification_count++;
                    error_log("Approve_Oders.php: ✅ Staff notification sent successfully for order #{$oder_id} (approved by: " . ($is_staff ? 'Staff' : 'Admin') . ")");
                } else {
                    error_log("Approve_Oders.php: ❌ Staff notification failed for order #{$oder_id}");
                }
            }
        } catch (Exception $e) {
            error_log("Approve_Oders.php: ❌ EXCEPTION sending WebSocket notifications: " . $e->getMessage());
            error_log("Approve_Oders.php: Stack trace: " . $e->getTraceAsString());
        }
    } else {
        error_log("Approve_Oders.php: ❌ WebSocket_Push.php file not found at: " . $wsPushFile);
    }
    
    // Log notification status
    if ($notifications_sent) {
        error_log("Approve_Oders.php: ✅ SUCCESS - {$notification_count} notifications sent for order #{$oder_id}");
    } else {
        error_log("Approve_Oders.php: ❌ WARNING - No notifications were sent for order #{$oder_id}");
    }
    
    // CRITICAL: Add a small delay to ensure queue file is written before redirect
    // This ensures notifications are queued before the page redirects
    usleep(100000); // 100ms delay to ensure file write completes

    // Clear output buffer before redirect to prevent "headers already sent" error
    ob_end_clean();

    if (($_POST['return_to'] ?? '') !== 'all') {
        header('Location: Approve_Oders.php?approved=' . $oder_id);
        exit();
    } else {
        header('Location: All_Oders.php?approved=' . $oder_id);
        exit();
    }
}
?>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include("../Layouts/SlideBar.php"); ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include("../Layouts/TopBar.php"); ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Đơn Hàng Cần Duyệt</h1>
                    <p class="mb-4 text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Danh sách đơn hàng chưa được duyệt. Staff có quyền duyệt đơn hàng thay mặt Admin để Shipper có thể nhận và giao hàng.
                    </p>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Trạng Thái</th>
                                            <th>Xem Chi Tiết</th>
                                            <th>Hủy Đơn</th>
                                            <th>Tên Khách Hàng</th>
                                            <th>SDT</th>
                                            <th>Ghi Chú</th>
                                            <th>Tổng Tiền</th>
                                            <th>Địa Chỉ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = 1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo '<tr>';
                                            echo ' <td>' . $stt . '</td>';
                                            echo '<td class="text-center">' . admin_order_transition_form((int) $row['ID_DonHang'], 'approve', 'Duyệt', 'btn-danger', 'approve') . '</td>';
                                            echo ' <td class="text-center"> <a href="View_Oder_Detail.php?oder_id='.$row['ID_DonHang'].'&view=approve" class="btn btn-primary">Xem Chi Tiết</a>' . '</td>';
                                            echo '<td class="text-center">' . admin_cancel_order_form((int) $row['ID_DonHang'], 'approve') . '</td>';
                                            echo ' <td>' . $row['HoVaTen'] . '</td>';
                                            echo ' <td>' . $row['SDT'] . '</td>';
                                            echo ' <td>' . $row['GhiChu'] . '</td>';
                                            echo ' <td>' . $row['TongTien'] . '</td>';
                                            echo ' <td>' . $row['DiaChi'] . '</td>';
                                            echo '</tr>';
                                            $stt++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->
            <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>
        </div>
        <!-- End of Page Wrapper -->
        <?php include("../Layouts/Link.php"); ?>
</body>

</html>
