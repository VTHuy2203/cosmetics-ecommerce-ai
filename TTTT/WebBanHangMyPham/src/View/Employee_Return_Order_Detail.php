<?php
/**
 * Employee Return Order Detail Page (User UI)
 * Staff-only page for viewing return request details and approving/rejecting
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

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    header('Location: Employee_Return_Orders.php');
    exit();
}

// Get request details with product quantity
$sql_request = "SELECT r.*, d.TongTien, d.TrangThai as OrderStatus, d.NgayTao as OrderDate, k.HoVaTen as CustomerName, k.ID_KhachHang, sp.TenSanPham, sp.GiaTien as ProductPrice, dct.SoLuong as ProductQuantity
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

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start(); // Start output buffering
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'approve') {
        // Calculate refund amount based on the specific product being returned
        $product_price = intval($request['ProductPrice'] ?? 0);
        $product_quantity = intval($request['ProductQuantity'] ?? 0);
        $refund_amount = $product_price * $product_quantity;
        
        // Update request status
        mysqli_query($conn, "UPDATE return_refund_requests SET TrangThai = 'approved' WHERE ID_Request = $request_id");
        
        // Refund to customer account balance
        $customer_id = intval($request['ID_KhachHang']);
        
        // Ensure SoDu column exists (check both SoDu and SoDuVi)
        $check_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND (COLUMN_NAME = 'SoDu' OR COLUMN_NAME = 'SoDuVi')");
        $has_balance_col = false;
        $balance_col = 'SoDu';
        if ($check_col) {
            $col_row = mysqli_fetch_assoc($check_col);
            if (intval($col_row['c']) > 0) {
                $has_balance_col = true;
                // Check which column exists
                $check_so_du = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
                if ($check_so_du) {
                    $so_du_row = mysqli_fetch_assoc($check_so_du);
                    if (intval($so_du_row['c']) > 0) {
                        $balance_col = 'SoDu';
                    } else {
                        $balance_col = 'SoDuVi';
                    }
                }
            }
        }
        
        if (!$has_balance_col) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
            $balance_col = 'SoDu';
        }
        
        mysqli_query($conn, "UPDATE khachhang SET $balance_col = $balance_col + $refund_amount WHERE ID_KhachHang = $customer_id");
        
        // Update order note to mark as returned
        $order_id = intval($request['ID_DonHang']);
        $product_name = mysqli_real_escape_string($conn, $request['TenSanPham']);
        $updated_ghi_chu = mysqli_real_escape_string($conn, $request['GhiChu'] ?? '');
        if (stripos($updated_ghi_chu, '[ĐÃ HOÀN TRẢ]') === false) {
            $updated_ghi_chu = ($updated_ghi_chu ? $updated_ghi_chu . ' ' : '') . '[ĐÃ HOÀN TRẢ] ' . $product_name;
        }
        mysqli_query($conn, "UPDATE donhang SET GhiChu = '$updated_ghi_chu' WHERE ID_DonHang = $order_id");
        
        // Send notification to customer
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'return_refund_approved',
                        'title' => 'Yêu cầu hoàn trả đã được duyệt',
                        'message' => "Yêu cầu hoàn trả đơn hàng #{$request['ID_DonHang']} của bạn đã được duyệt. Số tiền {$refund_amount} đã được hoàn vào số dư tài khoản của bạn. Chúng tôi xin lỗi vì không đáp ứng được kỳ vọng của bạn.",
                        'sound' => 'notification',
                        'data' => [
                            'request_id' => $request_id,
                            'order_id' => $request['ID_DonHang'],
                            'customer_id' => $customer_id,
                            'refund_amount' => $refund_amount,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                // Notify Admin
                websocket_push(
                    ['admin_channel'],
                    [
                        'type' => 'return_refund_approved_staff',
                        'title' => 'Yêu cầu hoàn trả đã được Staff duyệt',
                        'message' => "Staff đã duyệt yêu cầu hoàn trả đơn hàng #{$request['ID_DonHang']} của khách hàng {$request['CustomerName']}. Số tiền hoàn: " . number_format($refund_amount, 0, ',', '.') . " đ",
                        'sound' => 'notification',
                        'data' => [
                            'request_id' => $request_id,
                            'order_id' => $request['ID_DonHang'],
                            'customer_id' => $customer_id,
                            'customer_name' => $request['CustomerName'],
                            'refund_amount' => $refund_amount,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
        
        ob_end_clean();
        header('Location: Employee_Return_Orders.php?msg=approved');
        exit();
        
    } elseif ($action === 'reject') {
        // Reject request
        mysqli_query($conn, "UPDATE return_refund_requests SET TrangThai = 'rejected' WHERE ID_Request = $request_id");
        
        // Send notification to user
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'return_refund_rejected',
                        'title' => 'Yêu cầu hoàn trả đã bị từ chối',
                        'message' => "Yêu cầu hoàn trả đơn hàng #{$request['ID_DonHang']} của bạn đã bị từ chối. Vui lòng liên hệ với chúng tôi nếu bạn có thắc mắc.",
                        'sound' => 'notification',
                        'data' => [
                            'request_id' => $request_id,
                            'order_id' => $request['ID_DonHang'],
                            'customer_id' => intval($request['ID_KhachHang']),
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
                // Notify Admin
                websocket_push(
                    ['admin_channel'],
                    [
                        'type' => 'return_refund_rejected_staff',
                        'title' => 'Yêu cầu hoàn trả đã bị Staff từ chối',
                        'message' => "Staff đã từ chối yêu cầu hoàn trả đơn hàng #{$request['ID_DonHang']} của khách hàng {$request['CustomerName']}.",
                        'sound' => 'notification',
                        'data' => [
                            'request_id' => $request_id,
                            'order_id' => $request['ID_DonHang'],
                            'customer_id' => intval($request['ID_KhachHang']),
                            'customer_name' => $request['CustomerName'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
        
        ob_end_clean();
        header('Location: Employee_Return_Orders.php?msg=rejected');
        exit();
    }
}

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
$product_quantity = intval($request['ProductQuantity'] ?? 0);
$refund_amount = $product_price * $product_quantity;
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
                                    
                                    $file_path = '../../AdminManage/src/ReturnRefundAttachments/' . $filename;
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

