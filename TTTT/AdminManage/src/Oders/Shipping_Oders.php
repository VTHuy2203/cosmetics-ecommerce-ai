<?php
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Session is already started in headHTML.php, no need to start again
// Access control is handled by Admin_Access_Check.php (included via headHTML.php)
// This page is accessible to admin/manager roles (shippers use User UI version)

$sql = "SELECT * from donhang where TrangThai = '2'";

$result = mysqli_query($conn, $sql);

// Kiểm tra nếu có lỗi trong quá trình truy vấn
if (!$result) {
    die("Có lỗi xảy ra: " . mysqli_error($conn));
}

//duyệt đơn hàng - Shipper confirms order completion
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'shipping')
{
    require_csrf();
    $oder_id = (int) ($_POST['order_id'] ?? 0);
    $shipper_id = $_SESSION['admin_id'] ?? $_SESSION['IDNguoiDung'] ?? 0;
    
    $transition = $conn->prepare(
        "UPDATE donhang SET TrangThai = 3, GiaoHangLuc = NOW(),
         DaThanhToan = IF(PhuongThucThanhToan = 'cod', 1, DaThanhToan),
         TrangThaiThanhToan = IF(PhuongThucThanhToan = 'cod', 'paid', TrangThaiThanhToan),
         ThanhToanLuc = IF(PhuongThucThanhToan = 'cod', NOW(), ThanhToanLuc),
         MaThamChieuThanhToan = IF(PhuongThucThanhToan = 'cod', CONCAT('COD-ADMIN-', ID_DonHang, '-', UNIX_TIMESTAMP()), MaThamChieuThanhToan)
         WHERE ID_DonHang = ? AND TrangThai = 2
         AND (PhuongThucThanhToan = 'cod' OR TrangThaiThanhToan = 'paid')"
    );
    $transition->bind_param('i', $oder_id);
    $transition->execute();
    $result_update_trangthai = $transition->affected_rows === 1;
    
    // NOTE: Stock is NOT reduced here - it was already reduced when admin approved the order
    if ($result_update_trangthai) {
        // Create notification for Admin about order completion
        $order_info = mysqli_fetch_array(mysqli_query($conn, "SELECT HoVaTen, TongTien FROM donhang WHERE ID_DonHang = '$oder_id'"));
        $customer_name = $order_info['HoVaTen'] ?? 'N/A';
        $total = $order_info['TongTien'] ?? 0;
        $shipper_info = mysqli_fetch_array(mysqli_query($conn, "SELECT HoVaTen FROM khachhang WHERE ID_KhachHang = '$shipper_id'"));
        $shipper_name = $shipper_info['HoVaTen'] ?? 'Shipper';
        
        $notification_msg = "Đơn hàng #$oder_id của khách hàng $customer_name đã được giao thành công bởi shipper $shipper_name. Tổng tiền: " . number_format($total, 0, ',', '.') . " đ";
        mysqli_query($conn, "INSERT INTO `admin_notifications` (`type`, `message`, `order_id`, `user_id`) 
                            VALUES ('order_completed', '$notification_msg', '$oder_id', '$shipper_id')");
        
        // Send WebSocket notification to Admin, User, and Staff with shipper name
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
                        'message' => "Đơn hàng #{$oder_id} của khách hàng {$customer_name} đã được shipper {$shipper_name} giao thành công. Tổng tiền: " . number_format($total, 0, ',', '.') . " đ",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
                            'customer_name' => $customer_name,
                            'shipper_name' => $shipper_name,
                            'shipper_id' => $shipper_id,
                            'total_amount' => $total,
                            'status' => 3,
                            'status_text' => 'Đã giao thành công',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
            
            // Notify User
            if (function_exists('websocket_push')) {
                $order_query = mysqli_query($conn, "SELECT ID_KhachHang FROM donhang WHERE ID_DonHang = '$oder_id'");
                $order_row = mysqli_fetch_array($order_query);
                $customer_id = $order_row['ID_KhachHang'] ?? null;
                
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'order_delivered',
                        'title' => 'Đơn hàng đã được giao thành công!',
                        'message' => "Đơn hàng #{$oder_id} của bạn đã được shipper {$shipper_name} giao thành công",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
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
                        'message' => "Shipper {$shipper_name} đã giao thành công đơn hàng #{$oder_id} của khách hàng {$customer_name}",
                        'sound' => 'notification',
                        'data' => [
                            'order_id' => $oder_id,
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

    if (($_POST['return_to'] ?? '') !== 'all') {
        header('Location: Shipping_Oders.php');
        exit();
    } else {
        header('Location: All_Oders.php');
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
                    <h1 class="h3 mb-2 text-gray-800">Tất Cả Đơn Hàng</h1>

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
                                            echo '<td class="text-center">' . admin_order_transition_form((int) $row['ID_DonHang'], 'shipping', 'Hoàn tất giao', 'btn-warning', 'shipping') . '</td>';
                                            echo ' <td class="text-center"> <a href="View_Oder_Detail.php?oder_id='.$row['ID_DonHang'].'&view=shipping" class="btn btn-primary">Xem Chi Tiết</a>' . '</td>';
                                            echo '<td class="text-center">' . admin_cancel_order_form((int) $row['ID_DonHang'], 'shipping') . '</td>';
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
