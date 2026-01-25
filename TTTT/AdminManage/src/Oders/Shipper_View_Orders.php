<?php
/**
 * Shipper View Orders - Restricted Admin View
 * Shippers can only view orders they can accept/deliver
 * No edit/delete permissions
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Check if user is shipper (from User UI session)
$user_role = $_SESSION['user_role'] ?? 'customer';
$user_id = $_SESSION['IDNguoiDung'] ?? 0;

// If not shipper, redirect
if ($user_role !== 'shipper') {
    header("Location: ../View/Admin_Login.php");
    exit();
}

// Get available orders (status = 1, approved by admin, no shipper assigned)
// Order: oldest at top, newest at bottom (ASC - ascending order by ID or date)
$sql_available = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi, k.Email
                  FROM donhang d
                  LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                  WHERE d.TrangThai = 1 AND (d.ID_Shipper IS NULL OR d.ID_Shipper = 0)
                  ORDER BY COALESCE(d.NgayTao, d.ID_DonHang) ASC, d.ID_DonHang ASC";
$result_available = mysqli_query($conn, $sql_available);

// Get my assigned orders (status = 2, assigned to me)
// Order: oldest at top, newest at bottom (ASC - ascending order)
$sql_my_orders = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi, k.Email
                  FROM donhang d
                  LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                  WHERE d.ID_Shipper = $user_id AND d.TrangThai = 2
                  ORDER BY d.ID_DonHang ASC";
$result_my_orders = mysqli_query($conn, $sql_my_orders);

// Handle order acceptance
if (isset($_GET['accept_order']) && $_GET['accept_order'] > 0) {
    $order_id = intval($_GET['accept_order']);
    mysqli_query($conn, "UPDATE donhang SET TrangThai = 2, ID_Shipper = $user_id WHERE ID_DonHang = $order_id AND TrangThai = 1");
    header('Location: Shipper_View_Orders.php?msg=accepted');
    exit();
}

// Handle delivery confirmation
if (isset($_GET['confirm_delivery']) && $_GET['confirm_delivery'] > 0) {
    $order_id = intval($_GET['confirm_delivery']);
    $result_update = mysqli_query($conn, "UPDATE donhang SET TrangThai = 3 WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id AND TrangThai = 2");
    
    // Reduce product quantity when order is delivered
    if ($result_update) {
        $order_details = mysqli_query($conn, "SELECT ID_SanPham, SoLuong FROM donhangchitiet WHERE ID_DonHang = $order_id");
        while ($detail = mysqli_fetch_array($order_details)) {
            $product_id = intval($detail['ID_SanPham']);
            $quantity = intval($detail['SoLuong']);
            mysqli_query($conn, "UPDATE sanpham SET SoLuong = SoLuong - $quantity WHERE IDSanPham = $product_id AND SoLuong >= $quantity");
        }
    }
    
    header('Location: Shipper_View_Orders.php?msg=delivered');
    exit();
}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Quản Lý Đơn Hàng - Shipper</h1>
                    
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-<?php echo $_GET['msg'] === 'accepted' ? 'success' : 'info'; ?> alert-dismissible fade show">
                            <?php echo $_GET['msg'] === 'accepted' ? 'Đã nhận đơn hàng thành công!' : 'Đã xác nhận giao hàng thành công!'; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Available Orders -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-info">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-list me-2"></i>Đơn Hàng Có Sẵn (Chưa được nhận)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách hàng</th>
                                            <th>Địa chỉ giao hàng</th>
                                            <th>Số điện thoại</th>
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
                                                <td><?php echo htmlspecialchars($row['HoVaTen']); ?></td>
                                                <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['SDT']); ?></td>
                                                <td><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</td>
                                                <td><?php echo isset($row['NgayTao']) ? date('d/m/Y H:i', strtotime($row['NgayTao'])) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="?accept_order=<?php echo $row['ID_DonHang']; ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       onclick="return confirm('Nhận đơn hàng #<?php echo $row['ID_DonHang']; ?>?');">
                                                        <i class="fas fa-check"></i> Nhận đơn
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    Không có đơn hàng nào sẵn sàng để nhận
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Orders -->
                    <div class="card shadow">
                        <div class="card-header py-3 bg-primary">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-clipboard-list me-2"></i>Đơn Hàng Của Tôi (Đang giao)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách hàng</th>
                                            <th>Địa chỉ giao hàng</th>
                                            <th>Số điện thoại</th>
                                            <th>Tổng tiền</th>
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
                                                <td><?php echo htmlspecialchars($row['HoVaTen']); ?></td>
                                                <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['SDT']); ?></td>
                                                <td><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</td>
                                                <td><?php echo isset($row['NgayTao']) ? date('d/m/Y H:i', strtotime($row['NgayTao'])) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="?confirm_delivery=<?php echo $row['ID_DonHang']; ?>" 
                                                       class="btn btn-sm btn-primary" 
                                                       onclick="return confirm('Xác nhận đã giao hàng thành công cho đơn hàng #<?php echo $row['ID_DonHang']; ?>?');">
                                                        <i class="fas fa-check-circle"></i> Xác nhận giao hàng
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
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
    </div>
    
    <?php include("../Layouts/Link.php"); ?>
</body>
</html>

