<?php
/**
 * Customer Return Request Management Page
 * Shows all return/refund requests submitted by the customer
 */
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit();
}

// Get user info
$sql_inf_user = "SELECT * FROM khachhang WHERE TenDangNhap = '" . mysqli_real_escape_string($conn, $_SESSION['user']) . "'";
$result_user = mysqli_query($conn, $sql_inf_user);
$row_user = mysqli_fetch_array($result_user);
$idKH = $row_user['ID_KhachHang'];

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

// Get all return/refund requests for this customer
$sql_requests = "SELECT r.*, d.TongTien, sp.TenSanPham, sp.GiaTien as ProductPrice, dct.SoLuong as ProductQuantity
                 FROM return_refund_requests r
                 LEFT JOIN donhang d ON r.ID_DonHang = d.ID_DonHang
                 LEFT JOIN sanpham sp ON r.ID_SanPham = sp.IDSanPham
                 LEFT JOIN donhangchitiet dct ON dct.ID_DonHang = r.ID_DonHang AND dct.ID_SanPham = r.ID_SanPham
                 WHERE r.ID_KhachHang = $idKH
                 ORDER BY r.ThoiGianTao DESC";
$result_requests = mysqli_query($conn, $sql_requests);
?>
<body class="bg-light">
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="fas fa-undo me-2"></i>Yêu Cầu Hoàn Trả Của Tôi</h2>
                    <a href="Order_Tracking.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'return_request_submitted'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Yêu cầu hoàn trả của bạn đã được gửi thành công! Chúng tôi sẽ xử lý và thông báo cho bạn sớm nhất.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh Sách Yêu Cầu Hoàn Trả</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_requests && mysqli_num_rows($result_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã YC</th>
                                            <th>Mã ĐH</th>
                                            <th>Sản Phẩm</th>
                                            <th>Số Tiền Hoàn Trả</th>
                                            <th>Lý Do</th>
                                            <th>Trạng Thái</th>
                                            <th>Ngày Tạo</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_array($result_requests)): 
                                            $status = $row['TrangThai'];
                                            $status_class = ($status == 'pending') ? 'warning' : (($status == 'approved') ? 'success' : 'danger');
                                            $status_text = ($status == 'pending') ? 'Chờ xử lý' : (($status == 'approved') ? 'Đã chấp nhận' : 'Đã từ chối');
                                            $refund_reason_short = mb_substr($row['RefundReason'], 0, 50) . (mb_strlen($row['RefundReason']) > 50 ? '...' : '');
                                            $created_date = isset($row['ThoiGianTao']) ? date('d/m/Y H:i', strtotime($row['ThoiGianTao'])) : 'N/A';
                                            
                                            // Calculate refund amount
                                            $product_price = intval($row['ProductPrice'] ?? 0);
                                            $product_quantity = intval($row['ProductQuantity'] ?? 0);
                                            $refund_amount = $product_price * $product_quantity;
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['ID_Request']; ?></strong></td>
                                                <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['TenSanPham']); ?></td>
                                                <td>
                                                    <strong class="text-danger"><?php echo number_format($refund_amount, 0, ',', '.'); ?> đ</strong>
                                                    <br><small class="text-muted"><?php echo number_format($product_price, 0, ',', '.'); ?> đ × <?php echo $product_quantity; ?></small>
                                                </td>
                                                <td title="<?php echo htmlspecialchars($row['RefundReason']); ?>">
                                                    <?php echo htmlspecialchars($refund_reason_short); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                    <?php if ($status == 'approved'): ?>
                                                        <div class="alert alert-info mt-2 mb-0 p-2 small">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Số tiền đã được hoàn lại vào số dư tài khoản của bạn.
                                                        </div>
                                                    <?php elseif ($status == 'rejected'): ?>
                                                        <div class="alert alert-warning mt-2 mb-0 p-2 small">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Vui lòng liên hệ với chúng tôi nếu bạn có thắc mắc.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info mt-2 mb-0 p-2 small">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Nếu yêu cầu được chấp nhận, số tiền sẽ được hoàn lại vào số dư tài khoản của bạn.
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $created_date; ?></td>
                                                <td>
                                                    <a href="Get_Order_Detail.php?order_id=<?php echo $row['ID_DonHang']; ?>" 
                                                       class="btn btn-sm btn-info mb-1">
                                                        <i class="fas fa-eye me-1"></i>Xem đơn hàng
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Bạn chưa có yêu cầu hoàn trả nào.</p>
                                <a href="Order_Tracking.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-1"></i>Xem đơn hàng của tôi
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include("../Layouts/Footer.php"); ?>
</body>
</html>

