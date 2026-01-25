<?php
/**
 * Staff Return/Refund Request Detail Page
 * Shows request details with attachments and approve/reject functionality
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

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    header('Location: Staff_Return_Refund_Requests.php');
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
    header('Location: Staff_Return_Refund_Requests.php?error=not_found');
    exit();
}

$request = mysqli_fetch_array($result_request);

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'approve') {
        // Calculate refund amount based on the specific product being returned
        // CRITICAL: Use order detail price and quantity, NOT total order amount
        $product_price = intval($request['ProductPrice'] ?? 0);
        $product_quantity = intval($request['ProductQuantity'] ?? 0);
        
        // Double-check: If price/quantity not found, get directly from order details
        if ($product_price <= 0 || $product_quantity <= 0) {
            $order_id = intval($request['ID_DonHang']);
            $product_id = intval($request['ID_SanPham']);
            $sql_check_detail = "SELECT sp.GiaTien, dct.SoLuong 
                                FROM donhangchitiet dct
                                INNER JOIN sanpham sp ON dct.ID_SanPham = sp.IDSanPham
                                WHERE dct.ID_DonHang = $order_id AND dct.ID_SanPham = $product_id
                                LIMIT 1";
            $result_check_detail = mysqli_query($conn, $sql_check_detail);
            if ($result_check_detail && mysqli_num_rows($result_check_detail) > 0) {
                $detail_row = mysqli_fetch_array($result_check_detail);
                $product_price = intval($detail_row['GiaTien'] ?? 0);
                $product_quantity = intval($detail_row['SoLuong'] ?? 0);
            }
        }
        
        $refund_amount = $product_price * $product_quantity;
        
        // CRITICAL: Verify refund amount is NOT greater than order total
        $order_total = intval($request['TongTien'] ?? 0);
        if ($refund_amount > $order_total) {
            // Something is wrong - log error and use proportional calculation
            error_log("CRITICAL: Refund amount ($refund_amount) exceeds order total ($order_total) for Order #{$order_id}, Product #{$product_id}");
            // Use proportional refund instead
            // Calculate what percentage this product represents of the order
            $all_products_total = 0;
            $sql_all_products = "SELECT SUM(sp.GiaTien * dct.SoLuong) as total 
                                FROM donhangchitiet dct
                                INNER JOIN sanpham sp ON dct.ID_SanPham = sp.IDSanPham
                                WHERE dct.ID_DonHang = $order_id";
            $result_all_products = mysqli_query($conn, $sql_all_products);
            if ($result_all_products && mysqli_num_rows($result_all_products) > 0) {
                $all_row = mysqli_fetch_array($result_all_products);
                $all_products_total = intval($all_row['total'] ?? 0);
                if ($all_products_total > 0) {
                    $product_subtotal = $product_price * $product_quantity;
                    $refund_amount = round(($order_total * $product_subtotal) / $all_products_total);
                }
            }
        }
        
        if ($refund_amount <= 0) {
            header('Location: Staff_Return_Refund_Requests.php?error=invalid_refund_amount');
            exit();
        }
        
        // Approve request - refund to account balance
        mysqli_query($conn, "UPDATE return_refund_requests SET TrangThai = 'approved' WHERE ID_Request = $request_id");
        
        // Update order status to "Returned" (status = 4)
        // First check if status 4 column exists, if not we'll use GhiChu to mark as returned
        $order_id = intval($request['ID_DonHang']);
        $current_ghi_chu = '';
        $order_info = mysqli_fetch_array(mysqli_query($conn, "SELECT GhiChu FROM donhang WHERE ID_DonHang = $order_id"));
        if ($order_info) {
            $current_ghi_chu = $order_info['GhiChu'] ?? '';
        }
        $product_name = mysqli_real_escape_string($conn, $request['TenSanPham'] ?? '');
        $updated_ghi_chu = $current_ghi_chu . ' | [ĐÃ HOÀN TRẢ] Sản phẩm: ' . $product_name;
        $updated_ghi_chu_escaped = mysqli_real_escape_string($conn, $updated_ghi_chu);
        mysqli_query($conn, "UPDATE donhang SET GhiChu = '$updated_ghi_chu_escaped' WHERE ID_DonHang = $order_id");
        
        // Ensure SoDu column exists
        $check_col = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
        $col_exists = false;
        if ($check_col) {
            $col_row = mysqli_fetch_assoc($check_col);
            $col_exists = intval($col_row['c']) > 0;
        }
        if (!$col_exists) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
        }
        
        // Refund amount to customer account (only for the specific product)
        $customer_id = intval($request['ID_KhachHang']);
        mysqli_query($conn, "UPDATE khachhang SET SoDu = SoDu + $refund_amount WHERE ID_KhachHang = $customer_id");
        
        // Send notifications to Customer and Admin
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                // Notify Customer
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'return_refund_approved',
                        'title' => 'Yêu cầu hoàn trả đã được chấp nhận',
                        'message' => "Yêu cầu hoàn trả đơn hàng #{$request['ID_DonHang']} của bạn đã được chấp nhận. Số tiền " . number_format($refund_amount, 0, ',', '.') . " đ đã được hoàn lại vào số dư tài khoản của bạn. Chúng tôi xin lỗi vì không đáp ứng được kỳ vọng của bạn.",
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
                        'type' => 'return_refund_approved_admin',
                        'title' => 'Yêu cầu hoàn trả đã được chấp nhận',
                        'message' => "Staff đã chấp nhận yêu cầu hoàn trả đơn hàng #{$request['ID_DonHang']} của khách hàng {$request['CustomerName']}. Số tiền hoàn trả: " . number_format($refund_amount, 0, ',', '.') . " đ",
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
        
        header('Location: Staff_Return_Refund_Requests.php?msg=approved');
        exit();
        
    } elseif ($action === 'reject') {
        // Reject request
        mysqli_query($conn, "UPDATE return_refund_requests SET TrangThai = 'rejected' WHERE ID_Request = $request_id");
        
        // Send notifications to Customer and Admin
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                // Notify Customer
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
                        'type' => 'return_refund_rejected_admin',
                        'title' => 'Yêu cầu hoàn trả đã bị từ chối',
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
        
        header('Location: Staff_Return_Refund_Requests.php?msg=rejected');
        exit();
    }
}

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
                                            <td><?php echo $request['ProductQuantity'] ?? 0; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số tiền hoàn trả:</th>
                                            <td><strong class="text-danger"><?php 
                                                $product_price = intval($request['ProductPrice'] ?? 0);
                                                $product_quantity = intval($request['ProductQuantity'] ?? 0);
                                                $refund_amount = $product_price * $product_quantity;
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
                                            $file_path = '../../../AdminManage/src/ReturnRefundAttachments/' . $attachment['filename'];
                                            $full_path = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'AdminManage' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ReturnRefundAttachments' . DIRECTORY_SEPARATOR . $attachment['filename'];
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
