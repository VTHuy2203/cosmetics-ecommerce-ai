<?php
/**
 * Staff Return/Refund Requests Page
 * Displays all return/refund requests
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Check staff access
$admin_role = $_SESSION['admin_role'] ?? null;
$is_staff_via_admin = ($admin_role === 'employee');
$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');

if (!$is_staff_via_admin && !$is_admin_or_manager) {
    header("Location: ../View/Admin_Login.php");
    exit();
}

// Ensure table exists
$create_table = "CREATE TABLE IF NOT EXISTS `return_refund_requests` (
    `ID_Request` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_DonHang` INT NOT NULL,
    `ID_KhachHang` INT NOT NULL,
    `ID_SanPham` INT NOT NULL,
    `ContactName` VARCHAR(255) NOT NULL,
    `ContactPhone` VARCHAR(20) NOT NULL,
    `ContactEmail` VARCHAR(255) NOT NULL,
    `ContactAddress` TEXT NOT NULL,
    `RefundReason` TEXT NOT NULL,
    `Attachments` TEXT NULL,
    `TrangThai` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `ThoiGianTao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ThoiGianCapNhat` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (ID_DonHang),
    INDEX idx_customer (ID_KhachHang),
    INDEX idx_status (TrangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $create_table);

// Get return/refund requests with product price and quantity
$sql_requests = "SELECT r.*, d.TongTien, k.HoVaTen as CustomerName, sp.TenSanPham, sp.GiaTien as ProductPrice, dct.SoLuong as ProductQuantity
                 FROM return_refund_requests r
                 LEFT JOIN donhang d ON r.ID_DonHang = d.ID_DonHang
                 LEFT JOIN khachhang k ON r.ID_KhachHang = k.ID_KhachHang
                 LEFT JOIN sanpham sp ON r.ID_SanPham = sp.IDSanPham
                 LEFT JOIN donhangchitiet dct ON dct.ID_DonHang = r.ID_DonHang AND dct.ID_SanPham = r.ID_SanPham
                 ORDER BY r.ThoiGianTao DESC";
$result_requests = mysqli_query($conn, $sql_requests);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">
                        <i class="fas fa-undo me-2"></i>Đơn Hàng Hoàn Trả (Return Orders)
                    </h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Danh Sách Yêu Cầu Hoàn Trả</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Mã YC</th>
                                            <th>Mã ĐH</th>
                                            <th>Khách Hàng</th>
                                            <th>Sản Phẩm</th>
                                            <th>Tổng Tiền</th>
                                            <th>Lý Do</th>
                                            <th>Trạng Thái</th>
                                            <th>Ngày Tạo</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_requests && mysqli_num_rows($result_requests) > 0):
                                            while ($row = mysqli_fetch_array($result_requests)):
                                                $status = $row['TrangThai'];
                                                $status_class = ($status == 'pending') ? 'warning' : (($status == 'approved') ? 'success' : 'danger');
                                                $status_text = ($status == 'pending') ? 'Chờ xử lý' : (($status == 'approved') ? 'Đã chấp nhận' : 'Đã từ chối');
                                                $refund_reason_short = mb_substr($row['RefundReason'], 0, 50) . (mb_strlen($row['RefundReason']) > 50 ? '...' : '');
                                                $created_date = isset($row['ThoiGianTao']) ? date('d/m/Y H:i', strtotime($row['ThoiGianTao'])) : 'N/A';
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['ID_Request']; ?></strong></td>
                                                <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                                                <td><?php echo htmlspecialchars($row['TenSanPham']); ?></td>
                                                <td>
                                                    <?php 
                                                    // Calculate refund amount based on product only
                                                    $product_price = intval($row['ProductPrice'] ?? 0);
                                                    $product_quantity = intval($row['ProductQuantity'] ?? 0);
                                                    $refund_amount = $product_price * $product_quantity;
                                                    echo number_format($refund_amount, 0, ',', '.'); ?> đ
                                                    <small class="text-muted d-block">(Sản phẩm: <?php echo number_format($product_price, 0, ',', '.'); ?> đ × <?php echo $product_quantity; ?>)</small>
                                                    <small class="text-muted">Tổng đơn: <?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</small>
                                                </td>
                                                <td title="<?php echo htmlspecialchars($row['RefundReason']); ?>">
                                                    <?php echo htmlspecialchars($refund_reason_short); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $created_date; ?></td>
                                                <td>
                                                    <a href="Staff_Return_Refund_Detail.php?request_id=<?php echo $row['ID_Request']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
                                                    <?php if ($status == 'rejected'): ?>
                                                        <a href="?delete_request=<?php echo $row['ID_Request']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Bạn có chắc muốn xóa yêu cầu này?');">
                                                            <i class="fas fa-trash"></i> Xóa
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    Không có yêu cầu hoàn trả nào
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
    
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <?php include("../Layouts/Link.php"); ?>
</body>
</html>

<?php
// Handle delete request
if (isset($_GET['delete_request']) && $_GET['delete_request'] > 0) {
    $request_id = intval($_GET['delete_request']);
    mysqli_query($conn, "DELETE FROM return_refund_requests WHERE ID_Request = $request_id AND TrangThai = 'rejected'");
    header('Location: Staff_Return_Refund_Requests.php?msg=deleted');
    exit();
}
?>
