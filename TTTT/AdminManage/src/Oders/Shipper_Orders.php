<?php
/**
 * Shipper Orders Page - ADMIN UI VERSION
 * NOTE: This page should NOT be accessed by shippers from User UI
 * Shippers should use: WebBanHangMyPham/src/View/Shipper_Orders.php
 * This page is kept for admin viewing shipper orders
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// This page requires admin access (admin can view all shipper orders)
// Shippers should use the User UI version instead
// Access control is handled by Admin_Access_Check.php (included via headHTML.php)
$admin_id = $_SESSION['admin_id'] ?? 0;
$user_id = $admin_id; // For compatibility with existing code

// Handle order acceptance
if (isset($_GET['accept_order']) && $_GET['accept_order'] > 0) {
    $order_id = intval($_GET['accept_order']);
    mysqli_query($conn, "UPDATE donhang SET TrangThai = 2, ID_Shipper = $user_id WHERE ID_DonHang = $order_id AND TrangThai = 1");
    header('Location: Shipper_Orders.php?msg=accepted');
    exit();
}

// Handle delivery confirmation
if (isset($_GET['confirm_delivery']) && $_GET['confirm_delivery'] > 0) {
    $order_id = intval($_GET['confirm_delivery']);
    // Update order: set status to 3 (Delivered)
    // NOTE: Stock is NOT reduced here - it was already reduced when admin approved the order
    $result_update = mysqli_query($conn, "UPDATE donhang SET TrangThai = 3 WHERE ID_DonHang = $order_id AND ID_Shipper = $user_id AND TrangThai = 2");
    
    header('Location: Shipper_Orders.php?msg=delivered');
    exit();
}

// Ensure ID_Shipper column exists
@mysqli_query($conn, "ALTER TABLE `donhang` ADD `ID_Shipper` INT NULL AFTER `DaThanhToan`");

// Get orders for this shipper
$sql = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi 
        FROM donhang d
        LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
        WHERE (d.ID_Shipper = $user_id OR (d.ID_Shipper IS NULL AND d.TrangThai = 1))
        ORDER BY d.ID_DonHang DESC";
$result = mysqli_query($conn, $sql);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Đơn hàng của tôi (Shipper)</h1>
                    
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-<?php echo $_GET['msg'] === 'accepted' ? 'success' : 'info'; ?> alert-dismissible fade show">
                            <?php echo $_GET['msg'] === 'accepted' ? 'Đã nhận đơn hàng thành công!' : 'Đã xác nhận giao hàng thành công!'; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách hàng</th>
                                            <th>Địa chỉ</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_array($result)): ?>
                                            <tr>
                                                <td>#<?php echo $row['ID_DonHang']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['HoVaTen']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($row['SDT']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                                <td><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <?php
                                                    $status = intval($row['TrangThai']);
                                                    $statusText = ['Cần duyệt', 'Chuẩn bị', 'Đang giao', 'Giao thành công'];
                                                    $statusClass = ['danger', 'info', 'warning', 'success'];
                                                    ?>
                                                    <span class="badge badge-<?php echo $statusClass[$status]; ?>">
                                                        <?php echo $statusText[$status]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($row['TrangThai'] == 1 && !$row['ID_Shipper']): ?>
                                                        <a href="?accept_order=<?php echo $row['ID_DonHang']; ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           onclick="return confirm('Bạn có chắc muốn nhận đơn hàng này?');">
                                                            Nhận đơn
                                                        </a>
                                                    <?php elseif ($row['ID_Shipper'] == $user_id && $row['TrangThai'] == 2): ?>
                                                        <a href="?confirm_delivery=<?php echo $row['ID_DonHang']; ?>" 
                                                           class="btn btn-sm btn-primary" 
                                                           onclick="return confirm('Xác nhận đã giao hàng thành công?');">
                                                            Xác nhận giao hàng
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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
