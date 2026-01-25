<?php
/**
 * Admin Return/Cancellation Requests Page
 * Displays all return/refund requests and canceled orders
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Check admin access
$admin_role = $_SESSION['admin_role'] ?? null;
$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');

if (!$is_admin_or_manager) {
    header("Location: ../View/Admin_Login.php");
    exit();
}

// Handle delete request
if (isset($_GET['delete_request']) && $is_admin_or_manager) {
    $delete_id = intval($_GET['delete_request']);
    mysqli_query($conn, "DELETE FROM return_refund_requests WHERE ID_Request = $delete_id");
    header('Location: Admin_Return_Cancellation_Requests.php?msg=deleted');
    exit();
}

// Handle delete canceled order
if (isset($_GET['delete_order']) && $is_admin_or_manager) {
    $delete_order_id = intval($_GET['delete_order']);
    // Check if order is canceled or approved return
    $order_check = mysqli_query($conn, "SELECT TrangThai, GhiChu FROM donhang WHERE ID_DonHang = $delete_order_id");
    if ($order_check && mysqli_num_rows($order_check) > 0) {
        $order_data = mysqli_fetch_array($order_check);
        $ghi_chu = $order_data['GhiChu'] ?? '';
        // Only allow deletion if order is canceled or has approved return
        if (stripos($ghi_chu, '[ĐÃ HỦY]') !== false || stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false) {
            // Delete order details first
            mysqli_query($conn, "DELETE FROM donhangchitiet WHERE ID_DonHang = $delete_order_id");
            // Delete the order
            mysqli_query($conn, "DELETE FROM donhang WHERE ID_DonHang = $delete_order_id");
        }
    }
    header('Location: Admin_Return_Cancellation_Requests.php?msg=order_deleted');
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

// Get canceled orders (including shipper-canceled orders - status = -1 OR GhiChu contains [ĐÃ HỦY] OR [KHÁCH HÀNG TỪ CHỐI])
$sql_canceled = "SELECT d.*, k.HoVaTen, k.Email, k.SDT
                 FROM donhang d
                 LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                 WHERE (d.TrangThai = -1 OR d.GhiChu LIKE '%[ĐÃ HỦY]%' OR d.GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%')
                 ORDER BY d.ID_DonHang DESC
                 LIMIT 50";
$result_canceled = mysqli_query($conn, $sql_canceled);

// Get orders where customer refused delivery
$sql_refused = "SELECT d.*, k.HoVaTen, k.Email, k.SDT, s.HoVaTen as ShipperName
                FROM donhang d
                LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                LEFT JOIN khachhang s ON d.ID_Shipper = s.ID_KhachHang
                WHERE d.GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%'
                ORDER BY d.ID_DonHang DESC";
$result_refused = mysqli_query($conn, $sql_refused);

// Handle mark goods as returned/not returned (Admin action)
if (isset($_GET['mark_returned']) && $_GET['mark_returned'] > 0 && $is_admin_or_manager) {
    $order_id = intval($_GET['mark_returned']);
    $status = isset($_GET['status']) ? $_GET['status'] : 'returned';
    
    // Get order info
    $order_query = mysqli_query($conn, "SELECT GhiChu, ID_KhachHang, HoVaTen FROM donhang WHERE ID_DonHang = $order_id");
    if ($order_query && mysqli_num_rows($order_query) > 0) {
        $order_row = mysqli_fetch_array($order_query);
        $current_ghi_chu = $order_row['GhiChu'] ?? '';
        $customer_id = intval($order_row['ID_KhachHang']);
        $customer_name = $order_row['HoVaTen'] ?? 'Khách hàng';
        
        if ($status === 'returned') {
            // Mark as returned
            if (stripos($current_ghi_chu, '[ĐÃ TRẢ VỀ CỬA HÀNG]') === false) {
                $updated_ghi_chu = $current_ghi_chu . ' | [ĐÃ TRẢ VỀ CỬA HÀNG]';
                $updated_ghi_chu_escaped = mysqli_real_escape_string($conn, $updated_ghi_chu);
                mysqli_query($conn, "UPDATE donhang SET GhiChu = '$updated_ghi_chu_escaped' WHERE ID_DonHang = $order_id");
                
                // Send notification to Admin and Staff
                $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
                if (file_exists($wsPushFile)) {
                    require_once($wsPushFile);
                    if (function_exists('websocket_push')) {
                        websocket_push(
                            ['admin_channel', 'staff_channel'],
                            [
                                'type' => 'goods_returned_to_shop',
                                'title' => 'Hàng đã hoàn về cửa hàng',
                                'message' => "Đơn hàng #{$order_id} của khách hàng {$customer_name} - Shipper đã trả hàng về cửa hàng.",
                                'sound' => 'notification',
                                'data' => [
                                    'order_id' => $order_id,
                                    'customer_name' => $customer_name,
                                    'customer_id' => $customer_id,
                                    'status' => 'returned',
                                    'timestamp' => date('Y-m-d H:i:s')
                                ]
                            ]
                        );
                    }
                }
            }
        } else {
            // Mark as not returned (remove the marker)
            $updated_ghi_chu = str_ireplace('| [ĐÃ TRẢ VỀ CỬA HÀNG]', '', $current_ghi_chu);
            $updated_ghi_chu = trim(preg_replace('/\s+\|\s+/', ' | ', $updated_ghi_chu));
            $updated_ghi_chu_escaped = mysqli_real_escape_string($conn, $updated_ghi_chu);
            mysqli_query($conn, "UPDATE donhang SET GhiChu = '$updated_ghi_chu_escaped' WHERE ID_DonHang = $order_id");
        }
    }
    
    header('Location: Admin_Return_Cancellation_Requests.php?msg=' . ($status === 'returned' ? 'marked_returned' : 'marked_not_returned'));
    exit();
}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">
                        <i class="fas fa-undo me-2"></i>Yêu Cầu Hoàn Trả / Hủy Đơn Hàng
                    </h1>
                    
                    <!-- Return/Refund Requests -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Yêu Cầu Hoàn Trả</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
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
                                                    <a href="Admin_Return_Refund_Detail.php?request_id=<?php echo $row['ID_Request']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
                                                    <?php if (($status == 'rejected' || $status == 'approved') && $is_admin_or_manager): ?>
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
                    
                    <!-- Customer Refused Delivery Section -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-warning">
                            <h6 class="m-0 font-weight-bold">Đơn Hàng Khách Hàng Từ Chối Nhận (Shipper Đã Hủy)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách Hàng</th>
                                            <th>Shipper</th>
                                            <th>Lý Do Từ Chối</th>
                                            <th>Trạng Thái Hàng</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_refused && mysqli_num_rows($result_refused) > 0):
                                            while ($row = mysqli_fetch_array($result_refused)):
                                                $ghi_chu = $row['GhiChu'] ?? '';
                                                $refusal_reason = 'Không có lý do';
                                                if (preg_match('/\[KHÁCH HÀNG TỪ CHỐI\]\s*Lý do:\s*(.+?)(?:\||$)/i', $ghi_chu, $matches)) {
                                                    $refusal_reason = trim($matches[1]);
                                                }
                                                $returned_to_shop = (stripos($ghi_chu, '[ĐÃ TRẢ VỀ CỬA HÀNG]') !== false);
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['HoVaTen']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['SDT']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['ShipperName'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="text-danger" title="<?php echo htmlspecialchars($refusal_reason); ?>">
                                                        <?php echo htmlspecialchars(mb_substr($refusal_reason, 0, 50)) . (mb_strlen($refusal_reason) > 50 ? '...' : ''); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($returned_to_shop): ?>
                                                        <span class="badge bg-success">Đã trả về cửa hàng</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Chưa trả về cửa hàng</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="../Oders/View_Oder_Detail.php?oder_id=<?php echo $row['ID_DonHang']; ?>&view=all" 
                                                       class="btn btn-sm btn-info mb-1">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
                                                    <?php if ($is_admin_or_manager): ?>
                                                        <?php if (!$returned_to_shop): ?>
                                                            <a href="?mark_returned=<?php echo $row['ID_DonHang']; ?>&status=returned" 
                                                               class="btn btn-sm btn-success mb-1"
                                                               onclick="return confirm('Xác nhận shipper đã trả hàng về cửa hàng?');">
                                                                <i class="fas fa-check-circle"></i> Đã hoàn về shop
                                                            </a>
                                                            <a href="?mark_returned=<?php echo $row['ID_DonHang']; ?>&status=not_returned" 
                                                               class="btn btn-sm btn-warning"
                                                               onclick="return confirm('Xác nhận hàng chưa được trả về cửa hàng?');">
                                                                <i class="fas fa-times-circle"></i> Chưa hoàn về shop
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Đã hoàn về shop</span>
                                                            <!-- Don't show "Not Yet Returned" button when already returned -->
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    Không có đơn hàng nào khách hàng từ chối
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
    header('Location: Admin_Return_Cancellation_Requests.php?msg=deleted');
    exit();
}
?>

