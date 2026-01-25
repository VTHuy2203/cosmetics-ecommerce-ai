<?php
include("../Layouts/headHTML.php");

include("../DB_Connect.php");

// CRITICAL: Only Admin and Manager can prepare orders (not staff)
// Use ONLY admin session variables (completely ignore UI session)
$admin_role = $_SESSION['admin_role'] ?? null;
$is_staff_via_admin = ($admin_role === 'employee');
$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');

// Block staff (via admin login) from accessing this page
if ($is_staff_via_admin) {
    header('Location: ../Users/Staff_Dashboard.php?error=unauthorized');
    exit();
}

// Must be admin or manager
if (!$is_admin_or_manager) {
    header('Location: ../View/Admin_Login.php');
    exit();
}

$sql = "SELECT * from donhang where TrangThai = '1'";

$result = mysqli_query($conn, $sql);

// Kiểm tra nếu có lỗi trong quá trình truy vấn
if (!$result) {
    die("Có lỗi xảy ra: " . mysqli_error($conn));
}

//duyệt đơn hàng
if(isset($_GET['oder_id']) && isset($_GET['prepare']))
{
    // CRITICAL: Double-check role before preparing (use ONLY admin session)
    $admin_role = $_SESSION['admin_role'] ?? null;
    $is_staff_via_admin = ($admin_role === 'employee');
    $is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');
    
    // Only admin/manager can prepare orders
    if ($is_staff_via_admin || !$is_admin_or_manager) {
        header('Location: Prepare_Oders.php?error=unauthorized');
        exit();
    }
    $oder_id = intval($_GET['oder_id']);
    $sql_update_trangthai = "UPDATE `donhang` SET `TrangThai` = '2' WHERE `donhang`.`ID_DonHang` = '$oder_id';";

    $result_update_trangthai = mysqli_query($conn, $sql_update_trangthai);
    
    if ($result_update_trangthai) {
        // Get order info for WebSocket notification
        $order_info = mysqli_fetch_array(mysqli_query($conn, "SELECT ID_KhachHang, HoVaTen FROM donhang WHERE ID_DonHang = '$oder_id'"));
        $customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
        
        // Send WebSocket notification to User and Admin
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            $old_error_reporting = error_reporting(0);
            $old_display_errors = ini_get('display_errors');
            ini_set('display_errors', 0);
            try {
                require_once($wsPushFile);
                if (function_exists('websocket_push_order_status')) {
                    websocket_push_order_status($oder_id, 2, 'Đang chuẩn bị giao hàng', ['admin_channel', 'user_channel']);
                }
            } catch (Exception $e) {
                error_log("Prepare_Oders.php: Error sending WebSocket notification: " . $e->getMessage());
            }
            error_reporting($old_error_reporting);
            ini_set('display_errors', $old_display_errors);
        }
    }

    if ($_GET['prepare'] == 'prepare') {
        header('Location: Prepare_Oders.php');
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
                                            echo ' <td class="text-center"> <a href="Prepare_Oders.php?oder_id='.$row['ID_DonHang'].'&prepare=prepare" class="btn btn-info">Chuẩn Bị</a>' . '</td>';
                                            echo ' <td class="text-center"> <a href="View_Oder_Detail.php?oder_id='.$row['ID_DonHang'].'&view=prepare" class="btn btn-primary">Xem Chi Tiết</a>' . '</td>';
                                            echo ' <td class="text-center"> <a href="Delete_Oder.php?del_oder_id='.$row['ID_DonHang'].'&view=prepare" class="btn btn-danger">Hủy</a>' . '</td>';
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