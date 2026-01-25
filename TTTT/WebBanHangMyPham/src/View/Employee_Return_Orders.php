<?php
/**
 * Employee Return Orders Page (User UI)
 * Staff-only page for viewing and managing return/refund requests
 * SECURITY: This page does NOT include admin interfaces or navigation
 */
include("../Layouts/Head.php");
include("../DB_Connect.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an employee
if (!isset($_SESSION['user']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: Login.php");
    exit();
}

// Check if employee is approved
$user_id = $_SESSION['IDNguoiDung'];
$employee_check = mysqli_query($conn, "SELECT status FROM shipper_requests WHERE user_id = $user_id AND status = 'approved' ORDER BY created_at DESC LIMIT 1");
if (!$employee_check || mysqli_num_rows($employee_check) == 0) {
    echo "<script>alert('Tài khoản nhân viên của bạn chưa được phê duyệt. Vui lòng đợi Admin phê duyệt.'); window.location.href='index.php';</script>";
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

// Handle delete request
if (isset($_GET['delete_request']) && $_GET['delete_request'] > 0) {
    $request_id = intval($_GET['delete_request']);
    mysqli_query($conn, "DELETE FROM return_refund_requests WHERE ID_Request = $request_id AND TrangThai = 'rejected'");
    header('Location: Employee_Return_Orders.php?msg=deleted');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Hoàn Trả - Nhân Viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="fas fa-undo me-2"></i>Đơn Hàng Hoàn Trả (Return Orders)
                    </h2>
                    <a href="Employee_Customer_List.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>

                <!-- Message Display -->
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Yêu cầu đã được xóa thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Return Requests Table -->
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh Sách Yêu Cầu Hoàn Trả</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã YC</th>
                                        <th>Mã ĐH</th>
                                        <th>Khách Hàng</th>
                                        <th>Sản Phẩm</th>
                                        <th>Tiền Hoàn</th>
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
                                            $product_price = intval($row['ProductPrice'] ?? 0);
                                            $product_quantity = intval($row['ProductQuantity'] ?? 0);
                                            $refund_amount = $product_price * $product_quantity;
                                            $tong_tien = intval($row['TongTien'] ?? 0);
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['ID_Request']; ?></strong></td>
                                            <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['CustomerName'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['TenSanPham'] ?? ''); ?></td>
                                            <td>
                                                <strong><?php echo number_format($refund_amount, 0, ',', '.'); ?> đ</strong>
                                                <small class="text-muted d-block">(SP: <?php echo number_format($product_price, 0, ',', '.'); ?> đ × <?php echo $product_quantity; ?>)</small>
                                                <small class="text-muted">Tổng đơn: <?php echo number_format($tong_tien, 0, ',', '.'); ?> đ</small>
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
                                                <a href="Employee_Return_Order_Detail.php?request_id=<?php echo $row['ID_Request']; ?>" 
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

    <?php include("../Layouts/Footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

