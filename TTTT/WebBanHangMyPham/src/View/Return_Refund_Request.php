<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit();
}

// Get user info
$sql_inf_user = "SELECT * FROM khachhang WHERE TenDangNhap = '" . $_SESSION['user'] . "'";
$result_user = mysqli_query($conn, $sql_inf_user);
$row_user = mysqli_fetch_array($result_user);
$idKH = $row_user['ID_KhachHang'];

// Get order ID from query string
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: Order_Tracking.php');
    exit();
}

// Verify order belongs to user and is delivered and paid
$sql_order = "SELECT * FROM donhang WHERE ID_DonHang = $order_id AND ID_KhachHang = $idKH AND TrangThai = 3 AND DaThanhToan = 1";
$result_order = mysqli_query($conn, $sql_order);

if (!$result_order || mysqli_num_rows($result_order) == 0) {
    header('Location: Order_Tracking.php?error=invalid_order');
    exit();
}

$order = mysqli_fetch_array($result_order);

// Create return_refund_requests table if it doesn't exist
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

// Get order products
$sql_products = "SELECT dct.*, sp.TenSanPham, sp.IDSanPham 
                 FROM donhangchitiet dct 
                 INNER JOIN sanpham sp ON dct.ID_SanPham = sp.IDSanPham 
                 WHERE dct.ID_DonHang = $order_id";
$result_products = mysqli_query($conn, $sql_products);

// Check if return/refund request already exists for this order (pending or approved)
$sql_check_request = "SELECT * FROM return_refund_requests WHERE ID_DonHang = $order_id AND ID_KhachHang = $idKH AND TrangThai != 'rejected'";
$result_check = mysqli_query($conn, $sql_check_request);
if ($result_check && mysqli_num_rows($result_check) > 0) {
    $existing_request = mysqli_fetch_array($result_check);
    if ($existing_request['TrangThai'] == 'pending') {
        // Show message that return request is in progress
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Yêu cầu hoàn trả đang xử lý</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                        <h4 class="mb-3">Yêu cầu hoàn trả đang được xử lý</h4>
                        <p class="text-muted mb-4">Return request is in progress, please wait.</p>
                        <a href="Order_Tracking.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>
</html>';
        exit();
    } else {
        header('Location: Order_Tracking.php?error=request_exists');
        exit();
    }
}
?>

<body class="bg-light">
    <?php include("../Layouts/Banner_Header.php"); ?>
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>
    <?php include("../Layouts/Link.php"); ?>
    
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-undo me-2"></i>Yêu Cầu Hoàn Hàng Trả Tiền
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Nếu yêu cầu hoàn trả được chấp nhận, số tiền sẽ được hoàn lại vào số dư tài khoản của bạn.
                        </div>
                        
                        <form id="returnRefundForm" method="POST" action="../Processing/Submit_Return_Refund_Request.php" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            
                            <!-- Contact Information -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-user me-2"></i>Thông Tin Liên Hệ</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="contact_name" value="<?php echo htmlspecialchars($row_user['HoVaTen']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars($row_user['SDT']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars($row_user['Email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="contact_address" value="<?php echo htmlspecialchars($row_user['DiaChi']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Information -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-shopping-bag me-2"></i>Thông Tin Đơn Hàng</h5>
                                <div class="mb-3">
                                    <label class="form-label">Mã đơn hàng <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="#<?php echo $order_id; ?>" readonly>
                                    <input type="hidden" name="order_id_display" value="<?php echo $order_id; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sản phẩm cần hoàn trả <span class="text-danger">*</span></label>
                                    <select class="form-select" name="product_id" id="productSelect" required>
                                        <option value="">-- Chọn sản phẩm --</option>
                                        <?php 
                                        mysqli_data_seek($result_products, 0);
                                        while ($product = mysqli_fetch_array($result_products)): 
                                        ?>
                                            <option value="<?php echo $product['IDSanPham']; ?>">
                                                <?php echo htmlspecialchars($product['TenSanPham']); ?> (Số lượng: <?php echo $product['SoLuong']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Refund Reason -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-comment-alt me-2"></i>Lý Do Hoàn Trả</h5>
                                <div class="mb-3">
                                    <label class="form-label">Lý do hoàn trả <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="refund_reason" rows="5" placeholder="Vui lòng mô tả chi tiết lý do bạn muốn hoàn trả sản phẩm..." required></textarea>
                                </div>
                            </div>
                            
                            <!-- File Attachments -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-paperclip me-2"></i>Tệp Đính Kèm</h5>
                                <div class="mb-3">
                                    <label class="form-label">Hình ảnh (Tối đa 5 ảnh, mỗi ảnh tối đa 5MB)</label>
                                    <input type="file" class="form-control" name="attachments[]" id="imageFiles" accept="image/*" multiple>
                                    <small class="text-muted">Định dạng: JPG, PNG, GIF</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Video (Tối đa 2 video, mỗi video tối đa 50MB)</label>
                                    <input type="file" class="form-control" name="attachments[]" id="videoFiles" accept="video/*" multiple>
                                    <small class="text-muted">Định dạng: MP4, AVI, MOV</small>
                                </div>
                                <div id="filePreview" class="mt-3"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="Order_Tracking.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi Yêu Cầu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // File preview and validation
        document.getElementById('imageFiles').addEventListener('change', function(e) {
            handleFilePreview(e.target.files, 'image');
        });
        
        document.getElementById('videoFiles').addEventListener('change', function(e) {
            handleFilePreview(e.target.files, 'video');
        });
        
        function handleFilePreview(files, type) {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = '';
            
            Array.from(files).forEach((file, index) => {
                const maxSize = type === 'image' ? 5 * 1024 * 1024 : 50 * 1024 * 1024; // 5MB for images, 50MB for videos
                
                if (file.size > maxSize) {
                    alert(`File ${file.name} vượt quá kích thước cho phép (${type === 'image' ? '5MB' : '50MB'})`);
                    return;
                }
                
                const div = document.createElement('div');
                div.className = 'mb-2 p-2 border rounded';
                div.innerHTML = `
                    <i class="fas fa-${type === 'image' ? 'image' : 'video'} me-2"></i>
                    ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                `;
                preview.appendChild(div);
            });
        }
        
        // Form validation
        document.getElementById('returnRefundForm').addEventListener('submit', function(e) {
            const imageFiles = document.getElementById('imageFiles').files;
            const videoFiles = document.getElementById('videoFiles').files;
            
            if (imageFiles.length > 5) {
                e.preventDefault();
                alert('Chỉ được tải lên tối đa 5 hình ảnh');
                return false;
            }
            
            if (videoFiles.length > 2) {
                e.preventDefault();
                alert('Chỉ được tải lên tối đa 2 video');
                return false;
            }
            
            return true;
        });
    </script>
    
    <?php include("../Layouts/Footer.php"); ?>
</body>
</html>
