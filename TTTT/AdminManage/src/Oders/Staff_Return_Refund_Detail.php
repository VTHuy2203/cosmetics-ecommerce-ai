<?php
/**
 * Staff Return/Refund Request Detail Page
 * Shows request details with attachments and approve/reject functionality
 */
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/config/returns.php';
require_once '../Layouts/Admin_Access_Check.php';

// Check staff access
$admin_role = $_SESSION['admin_role'] ?? null;
$is_staff_via_admin = ($admin_role === 'employee');
$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');

if (!$is_staff_via_admin && !$is_admin_or_manager) {
    header("Location: ../View/Admin_Login.php");
    exit();
}

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    header('Location: Staff_Return_Refund_Requests.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        process_return_request($conn, $request_id, $action, (int) ($_SESSION['admin_id'] ?? 0), (string) $admin_role);
        header('Location: Staff_Return_Refund_Requests.php?msg=' . ($action === 'approve' ? 'approved' : 'rejected'));
        exit();
    } catch (DomainException $error) {
        http_response_code(409);
        exit('Yêu cầu đã được xử lý hoặc dữ liệu hoàn tiền không hợp lệ.');
    } catch (Throwable $error) {
        error_log('Staff return processing failed: ' . $error->getMessage());
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
    header('Location: Staff_Return_Refund_Requests.php?error=not_found');
    exit();
}

$request = mysqli_fetch_array($result_request);

include("../Layouts/headHTML.php");

// Parse attachments
$attachments = [];
if (!empty($request['Attachments'])) {
    $attachments = json_decode($request['Attachments'], true);
    if (!is_array($attachments)) {
        $attachments = [];
    }
}

$status = $request['TrangThai'];
$status_class = ($status == 'pending') ? 'warning' : (($status == 'approved') ? 'success' : 'danger');
$status_text = ($status == 'pending') ? 'Chờ xử lý' : (($status == 'approved') ? 'Đã chấp nhận' : 'Đã từ chối');
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 text-gray-800">
                            <i class="fas fa-undo me-2"></i>Chi Tiết Yêu Cầu Hoàn Trả
                        </h1>
                        <a href="Staff_Return_Refund_Requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Request Information -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Thông Tin Yêu Cầu</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Mã yêu cầu:</th>
                                            <td><strong>#<?php echo $request['ID_Request']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Mã đơn hàng:</th>
                                            <td><strong>#<?php echo $request['ID_DonHang']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Khách hàng:</th>
                                            <td><?php echo htmlspecialchars($request['CustomerName']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Sản phẩm:</th>
                                            <td><?php echo htmlspecialchars($request['TenSanPham']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Giá sản phẩm:</th>
                                            <td><?php echo number_format($request['ProductPrice'] ?? 0, 0, ',', '.'); ?> đ</td>
                                        </tr>
                                        <tr>
                                            <th>Số lượng:</th>
                                            <td><?php echo (int) ($request['SoLuongYeuCau'] ?? 0); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số tiền hoàn trả:</th>
                                            <td><strong class="text-danger"><?php 
                                                $refund_amount = return_refund_preview_amount($request);
                                                echo number_format($refund_amount, 0, ',', '.'); ?> đ</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Tổng tiền đơn hàng:</th>
                                            <td><small class="text-muted"><?php echo number_format($request['TongTien'], 0, ',', '.'); ?> đ</small></td>
                                        </tr>
                                        <tr>
                                            <th>Trạng thái:</th>
                                            <td>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Ngày tạo:</th>
                                            <td><?php echo isset($request['ThoiGianTao']) ? date('d/m/Y H:i', strtotime($request['ThoiGianTao'])) : 'N/A'; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Thông Tin Liên Hệ</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Họ và tên:</th>
                                            <td><?php echo htmlspecialchars($request['ContactName']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số điện thoại:</th>
                                            <td><?php echo htmlspecialchars($request['ContactPhone']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td><?php echo htmlspecialchars($request['ContactEmail']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Địa chỉ:</th>
                                            <td><?php echo htmlspecialchars($request['ContactAddress']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Refund Reason -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Lý Do Hoàn Trả</h6>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($request['RefundReason'])); ?></p>
                                </div>
                            </div>
                            
                            <!-- Attachments -->
                            <?php if (!empty($attachments)): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Tệp Đính Kèm</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($attachments as $attachment): 
                                            $file_path = '../Users/Private_File.php?path=' . rawurlencode('return_refunds/' . basename($attachment['filename']));
                                        ?>
                                            <div class="col-md-4 mb-3">
                                                <?php if ($attachment['type'] === 'image'): ?>
                                                    <img src="<?php echo $file_path; ?>" 
                                                         class="img-thumbnail" 
                                                         alt="<?php echo htmlspecialchars($attachment['original_name']); ?>"
                                                         style="max-width: 100%; height: auto; cursor: pointer;"
                                                         onclick="window.open('<?php echo $file_path; ?>', '_blank')">
                                                    <p class="small text-muted mt-1"><?php echo htmlspecialchars($attachment['original_name']); ?></p>
                                                <?php else: ?>
                                                    <div class="border p-3 text-center">
                                                        <i class="fas fa-video fa-3x text-primary mb-2"></i>
                                                        <p class="small mb-1"><?php echo htmlspecialchars($attachment['original_name']); ?></p>
                                                        <a href="<?php echo $file_path; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-download me-1"></i>Tải xuống
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Action Panel -->
                        <div class="col-lg-4">
                            <?php if ($status == 'pending'): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-primary text-white">
                                    <h6 class="m-0 font-weight-bold">Thao Tác</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <?php echo csrf_field(); ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Nếu chấp nhận, số tiền sẽ được hoàn lại vào số dư tài khoản của khách hàng.
                                        </div>
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-block mb-2"
                                                onclick="return confirm('Bạn có chắc muốn chấp nhận yêu cầu hoàn trả này? Số tiền sẽ được hoàn lại vào tài khoản khách hàng.');">
                                            <i class="fas fa-check me-2"></i>Đồng ý
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-block"
                                                onclick="return confirm('Bạn có chắc muốn từ chối yêu cầu hoàn trả này?');">
                                            <i class="fas fa-times me-2"></i>Hủy yêu cầu
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php elseif ($status == 'rejected'): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-danger text-white">
                                    <h6 class="m-0 font-weight-bold">Yêu Cầu Đã Bị Từ Chối</h6>
                                </div>
                                <div class="card-body">
                                    <a href="Staff_Return_Refund_Requests.php?delete_request=<?php echo $request_id; ?>" 
                                       class="btn btn-danger btn-block"
                                       onclick="return confirm('Bạn có chắc muốn xóa yêu cầu này?');">
                                        <i class="fas fa-trash me-2"></i>Xóa Yêu Cầu
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
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
