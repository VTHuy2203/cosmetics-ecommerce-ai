<?php
/**
 * Shipper Delivered Orders History Page
 * Shows all orders that have been delivered by this shipper
 */
include("../DB_Connect.php");
include("../Layouts/Head.php");

// Check if user is shipper
$user_role = $_SESSION['user_role'] ?? 'customer';
$user_id = $_SESSION['IDNguoiDung'] ?? 0;

if ($user_role !== 'shipper') {
    header("Location: index.php");
    exit();
}

// Get all delivered orders (status = 3) assigned to this shipper
$sql_delivered = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi 
                  FROM donhang d
                  LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                  WHERE d.ID_Shipper = $user_id AND d.TrangThai = 3
                  ORDER BY d.ID_DonHang DESC";
$result_delivered = mysqli_query($conn, $sql_delivered);

// Get canceled orders (status = -1) assigned to this shipper (customer refused)
$sql_canceled = "SELECT d.*, k.HoVaTen, k.SDT, k.DiaChi 
                 FROM donhang d
                 LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                 WHERE d.ID_Shipper = $user_id AND (d.TrangThai = -1 OR d.GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%')
                 ORDER BY d.ID_DonHang DESC";
$result_canceled = mysqli_query($conn, $sql_canceled);

// Get statistics (delivered orders)
$stats_query = mysqli_query($conn, "SELECT 
    COUNT(*) as total_delivered,
    SUM(CASE WHEN DaThanhToan = 1 THEN 1 ELSE 0 END) as total_paid,
    SUM(TongTien) as total_revenue
    FROM donhang 
    WHERE ID_Shipper = $user_id AND TrangThai = 3");
$stats = mysqli_fetch_array($stats_query);
$total_delivered = $stats['total_delivered'] ?? 0;
$total_paid = $stats['total_paid'] ?? 0;
$total_revenue = $stats['total_revenue'] ?? 0;

// Get canceled orders statistics
$stats_canceled_query = mysqli_query($conn, "SELECT COUNT(*) as total_canceled
    FROM donhang 
    WHERE ID_Shipper = $user_id AND (TrangThai = -1 OR GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%')");
$stats_canceled = mysqli_fetch_array($stats_canceled_query);
$total_canceled = $stats_canceled['total_canceled'] ?? 0;
?>
<body class="bg-light">
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="fas fa-history me-2"></i>Lịch Sử Đơn Hàng Đã Giao</h2>
                    <a href="Shipper_Orders.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm border-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title text-primary"><i class="fas fa-box me-2"></i>Tổng đơn đã giao</h5>
                                <h2 class="text-primary"><?php echo number_format($total_delivered); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-success">
                            <div class="card-body text-center">
                                <h5 class="card-title text-success"><i class="fas fa-check-circle me-2"></i>Đã thanh toán</h5>
                                <h2 class="text-success"><?php echo number_format($total_paid); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-info">
                            <div class="card-body text-center">
                                <h5 class="card-title text-info"><i class="fas fa-money-bill-wave me-2"></i>Tổng doanh thu</h5>
                                <h2 class="text-info"><?php echo number_format($total_revenue, 0, ',', '.'); ?> đ</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-danger">
                            <div class="card-body text-center">
                                <h5 class="card-title text-danger"><i class="fas fa-times-circle me-2"></i>Đơn đã hủy</h5>
                                <h2 class="text-danger"><?php echo number_format($total_canceled); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Delivered Orders Table -->
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Danh Sách Đơn Hàng Đã Giao</h5>
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
                                        <th>Thanh toán</th>
                                        <th>Ngày đặt</th>
                                        <th>Ngày giao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($result_delivered && mysqli_num_rows($result_delivered) > 0):
                                        while ($row = mysqli_fetch_array($result_delivered)): 
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
                                                // Try to get delivery date from order update time or use current date
                                                if (isset($row['NgayCapNhat']) && !empty($row['NgayCapNhat'])) {
                                                    echo date('d/m/Y H:i', strtotime($row['NgayCapNhat']));
                                                } else {
                                                    echo date('d/m/Y H:i');
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                Bạn chưa có đơn hàng nào đã giao
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
    
    <?php include("../Layouts/Footer.php"); ?>
    <?php include("../Layouts/Link.php"); ?>
</body>
</html>

