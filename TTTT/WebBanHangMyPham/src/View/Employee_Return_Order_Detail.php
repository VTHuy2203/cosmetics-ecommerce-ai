<?php
/**
 * Employee Return Order Detail Page (User UI)
 * Staff-only page for viewing return request details and approving/rejecting
 * SECURITY: This page does NOT include admin interfaces or navigation
 */
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/config/returns.php';

// Verify the current database role; session role values can become stale.
$user_id = filter_var($_SESSION['IDNguoiDung'] ?? null, FILTER_VALIDATE_INT);
$username = trim((string) ($_SESSION['user'] ?? ''));
if (!$user_id || $username === '') {
    header("Location: Login.php");
    exit();
}

// Check if employee is approved
$employee_check = $conn->prepare(
    "SELECT k.Role FROM khachhang k
     WHERE k.ID_KhachHang = ? AND k.TenDangNhap = ? AND k.Role = 'employee'
       AND EXISTS (SELECT 1 FROM shipper_requests s WHERE s.user_id = k.ID_KhachHang AND s.status = 'approved')"
);
$employee_check->bind_param('is', $user_id, $username);
$employee_check->execute();
if (!$employee_check->get_result()->fetch_assoc()) {
    echo "<script>alert('Tài khoản nhân viên của bạn chưa được phê duyệt. Vui lòng đợi Admin phê duyệt.'); window.location.href='index.php';</script>";
    exit();
}

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    header('Location: Employee_Return_Orders.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        process_return_request($conn, $request_id, $action, (int) $user_id, 'employee');
        header('Location: Employee_Return_Orders.php?msg=' . ($action === 'approve' ? 'approved' : 'rejected'));
        exit();
    } catch (DomainException $error) {
        http_response_code(409);
        exit('Yêu cầu đã được xử lý hoặc dữ liệu hoàn tiền không hợp lệ.');
    } catch (Throwable $error) {
        error_log('Employee return processing failed: ' . $error->getMessage());
        http_response_code(500);
        exit('Không thể xử lý yêu cầu hoàn trả.');
    }
}

// Get request details with product quantity
$sql_request = "SELECT r.*, d.TongTien, d.TrangThai as OrderStatus, d.NgayTao as OrderDate, k.HoVaTen as CustomerName, k.ID_KhachHang, sp.TenSanPham, dct.DonGia as ProductPrice, dct.SoLuong as ProductQuantity, dct.ThanhTienThucTra
                FROM return_refund_requests r
                LEFT JOIN donhang d ON r.ID_DonHang = d.ID_DonHang
                LEFT JOIN khachhang k ON r.ID_KhachHang = k.ID_KhachHang
                LEFT JOIN sanpham sp ON r.ID_SanPham = sp.IDSanPham
                LEFT JOIN donhangchitiet dct ON dct.ID_DonHang = r.ID_DonHang AND dct.ID_SanPham = r.ID_SanPham
                WHERE r.ID_Request = $request_id";
$result_request = mysqli_query($conn, $sql_request);

if (!$result_request || mysqli_num_rows($result_request) == 0) {
    header('Location: Employee_Return_Orders.php?error=not_found');
    exit();
}

$request = mysqli_fetch_array($result_request);

include("../Layouts/Head.php");

// Parse attachments (JSON array of file paths)
$attachments = [];
if (!empty($request['Attachments'])) {
    $attachments = json_decode($request['Attachments'], true);
    if (!is_array($attachments)) {
        $attachments = [];
    }
}

// Calculate refund amount
$product_price = intval($request['ProductPrice'] ?? 0);
$product_quantity = intval($request['SoLuongYeuCau'] ?? 0);
$refund_amount = return_refund_preview_amount($request);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Yêu Cầu Hoàn Trả - Nhân Viên</title>
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
                        <i class="fas fa-eye me-2"></i>Chi Tiết Yêu Cầu Hoàn Trả
                    </h2>
                    <a href="Employee_Return_Orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>

                <!-- Request Status -->
                <div class="alert alert-<?php echo $request['TrangThai'] == 'pending' ? 'warning' : ($request['TrangThai'] == 'approved' ? 'success' : 'danger'); ?>">
                    <strong>Trạng thái:</strong> 
                    <?php 
                    echo $request['TrangThai'] == 'pending' ? 'Chờ xử lý' : ($request['TrangThai'] == 'approved' ? 'Đã chấp nhận' : 'Đã từ chối');
                    ?>
                </div>

                <!-- Request Details -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông Tin Yêu Cầu</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Mã Yêu Cầu:</th>
                                        <td><strong>#<?php echo $request['ID_Request']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Mã Đơn Hàng:</th>
                                        <td><strong>#<?php echo $request['ID_DonHang']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Khách Hàng:</th>
                                        <td><?php echo htmlspecialchars($request['CustomerName']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Sản Phẩm:</th>
                                        <td><?php echo htmlspecialchars($request['TenSanPham']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Số Lượng:</th>
                                        <td><?php echo $product_quantity; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Giá Sản Phẩm:</th>
                                        <td><?php echo number_format($product_price, 0, ',', '.'); ?> đ</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <th><strong>Số Tiền Hoàn:</strong></th>
                                        <td><strong><?php echo number_format($refund_amount, 0, ',', '.'); ?> đ</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Tổng Tiền Đơn Hàng:</th>
                                        <td><?php echo number_format(intval($request['TongTien'] ?? 0), 0, ',', '.'); ?> đ</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Tên Liên Hệ:</th>
                                        <td><?php echo htmlspecialchars($request['ContactName']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Số Điện Thoại:</th>
                                        <td><?php echo htmlspecialchars($request['ContactPhone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><?php echo htmlspecialchars($request['ContactEmail']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Địa Chỉ:</th>
                                        <td><?php echo htmlspecialchars($request['ContactAddress']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Ngày Tạo:</th>
                                        <td><?php echo isset($request['ThoiGianTao']) ? date('d/m/Y H:i:s', strtotime($request['ThoiGianTao'])) : 'N/A'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Refund Reason -->
                        <div class="mt-4">
                            <h5><i class="fas fa-comment-alt me-2"></i>Lý Do Hoàn Trả:</h5>
                            <div class="alert alert-light">
                                <?php echo nl2br(htmlspecialchars($request['RefundReason'])); ?>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <?php if (!empty($attachments) && is_array($attachments)): ?>
                        <div class="mt-4">
                            <h5><i class="fas fa-paperclip me-2"></i>Tệp Đính Kèm:</h5>
                            <div class="row">
                                <?php foreach ($attachments as $attachment): 
                                    // Handle both array format and string format
                                    if (is_array($attachment)) {
                                        $filename = $attachment['filename'] ?? '';
                                        $original_name = $attachment['original_name'] ?? '';
                                        $attachment_type = $attachment['type'] ?? '';
                                    } else {
                                        // Legacy string format
                                        $filename = $attachment;
                                        $original_name = $attachment;
                                        $attachment_type = '';
                                    }
                                    
                                    if (empty($filename)) continue;
                                    
                                    $file_path = '../Processing/Return_Attachment.php?request_id=' . (int) $request_id . '&file=' . rawurlencode(basename($filename));
                                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                    $is_video = in_array($file_ext, ['mp4', 'avi', 'mov', 'wmv', 'flv']);
                                    $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) || $attachment_type === 'image';
                                ?>
                                    <div class="col-md-3 mb-3">
                                        <?php if ($is_image || $attachment_type === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($file_path); ?>" 
                                                 class="img-thumbnail" 
                                                 alt="<?php echo htmlspecialchars($original_name); ?>" 
                                                 style="max-width: 100%; max-height: 200px; cursor: pointer;"
                                                 onclick="window.open(this.src, '_blank')">
                                            <p class="small text-muted mt-1"><?php echo htmlspecialchars($original_name); ?></p>
                                        <?php elseif ($is_video || $attachment_type === 'video'): ?>
                                            <video class="img-thumbnail" controls style="max-width: 100%; max-height: 200px;">
                                                <source src="<?php echo htmlspecialchars($file_path); ?>" type="video/<?php echo $file_ext; ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                            <p class="small text-muted mt-1"><?php echo htmlspecialchars($original_name); ?></p>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-file me-2"></i><?php echo htmlspecialchars($original_name); ?>
                                                <br>
                                                <a href="<?php echo htmlspecialchars($file_path); ?>" target="_blank" class="btn btn-sm btn-primary mt-2">
                                                    <i class="fas fa-download"></i> Tải xuống
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons (Only for pending requests) -->
                        <?php if ($request['TrangThai'] == 'pending'): ?>
                        <div class="mt-4 border-top pt-4">
                            <form method="POST" onsubmit="return confirm('Bạn có chắc muốn thực hiện hành động này?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" id="action_input">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <button type="button" 
                                                class="btn btn-success btn-lg w-100" 
                                                onclick="if(confirm('Bạn có chắc muốn duyệt yêu cầu này? Số tiền <?php echo number_format($refund_amount, 0, ',', '.'); ?> đ sẽ được hoàn vào tài khoản khách hàng.')) { document.getElementById('action_input').value = 'approve'; this.form.submit(); }">
                                            <i class="fas fa-check-circle me-2"></i>Duyệt Yêu Cầu
                                        </button>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <button type="button" 
                                                class="btn btn-danger btn-lg w-100" 
                                                onclick="if(confirm('Bạn có chắc muốn từ chối yêu cầu này?')) { document.getElementById('action_input').value = 'reject'; this.form.submit(); }">
                                            <i class="fas fa-times-circle me-2"></i>Từ Chối Yêu Cầu
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../Layouts/Footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

