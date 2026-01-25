<?php
/**
 * Staff Order Management Page
 * Dedicated page for Staff to manage orders, handle shipper cancellations, and manage return requests
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

// Get all orders (Staff sees last 30 days, Admin sees all)
if ($is_staff_via_admin) {
    $sql_orders = "SELECT d.*, k.HoVaTen, k.Email, k.SDT, s.HoVaTen as ShipperName
                   FROM donhang d
                   LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                   LEFT JOIN khachhang s ON d.ID_Shipper = s.ID_KhachHang
                   WHERE d.NgayTao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   ORDER BY d.NgayTao DESC";
} else {
    $sql_orders = "SELECT d.*, k.HoVaTen, k.Email, k.SDT, s.HoVaTen as ShipperName
                   FROM donhang d
                   LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                   LEFT JOIN khachhang s ON d.ID_Shipper = s.ID_KhachHang
                   ORDER BY d.NgayTao DESC";
}
$result_orders = mysqli_query($conn, $sql_orders);

// Get orders where customer refused delivery
$sql_refused = "SELECT d.*, k.HoVaTen, k.Email, k.SDT, s.HoVaTen as ShipperName
                FROM donhang d
                LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                LEFT JOIN khachhang s ON d.ID_Shipper = s.ID_KhachHang
                WHERE d.GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%'
                ORDER BY d.ID_DonHang DESC";
$result_refused = mysqli_query($conn, $sql_refused);

// Handle mark goods as returned/not returned (Staff/Admin action)
if (isset($_GET['mark_returned']) && $_GET['mark_returned'] > 0) {
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
    
    header('Location: Staff_Order_Management.php?msg=' . ($status === 'returned' ? 'marked_returned' : 'marked_not_returned'));
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
                        <i class="fas fa-clipboard-list me-2"></i>Quản Lý Đơn Hàng
                    </h1>
                    
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
                                                    <a href="View_Oder_Detail.php?oder_id=<?php echo $row['ID_DonHang']; ?>&view=all" 
                                                       class="btn btn-sm btn-info mb-1">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
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
                                                        <span class="badge bg-success mb-1">Đã hoàn về shop</span>
                                                        <a href="?mark_returned=<?php echo $row['ID_DonHang']; ?>&status=not_returned" 
                                                           class="btn btn-sm btn-warning"
                                                           onclick="return confirm('Xác nhận hàng chưa được trả về cửa hàng?');">
                                                            <i class="fas fa-times-circle"></i> Chưa hoàn về shop
                                                        </a>
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
                    
                    <!-- All Orders Section -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Tất Cả Đơn Hàng</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Mã ĐH</th>
                                            <th>Trạng Thái</th>
                                            <th>Khách Hàng</th>
                                            <th>Shipper</th>
                                            <th>Tổng Tiền</th>
                                            <th>Thanh Toán</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_orders && mysqli_num_rows($result_orders) > 0):
                                            $stt = 1;
                                            while ($row = mysqli_fetch_array($result_orders)):
                                                $ghi_chu = $row['GhiChu'] ?? '';
                                                $st = intval($row['TrangThai']);
                                                $is_canceled = ($st == -1) || (stripos($ghi_chu, '[ĐÃ HỦY]') !== false);
                                                $is_customer_refused = (stripos($ghi_chu, '[KHÁCH HÀNG TỪ CHỐI]') !== false);
                                                
                                                // Status text
                                                if ($is_canceled || $st == -1) {
                                                    $status_text = $is_customer_refused ? 'Đơn đã bị hủy (Khách từ chối)' : 'Đơn đã bị hủy';
                                                    $status_class = 'danger';
                                                } else if ($st == 0) {
                                                    $status_text = 'Cần Duyệt';
                                                    $status_class = 'warning';
                                                } else if ($st == 1) {
                                                    $status_text = 'Chuẩn Bị';
                                                    $status_class = 'info';
                                                } else if ($st == 2) {
                                                    if ($is_customer_refused) {
                                                        $status_text = 'Đơn đã bị hủy (Khách từ chối)';
                                                        $status_class = 'danger';
                                                    } else {
                                                        $status_text = 'Đang Giao';
                                                        $status_class = 'primary';
                                                    }
                                                } else if ($st == 3) {
                                                    $status_text = 'Giao Thành Công';
                                                    $status_class = 'success';
                                                } else {
                                                    $status_text = 'Không xác định';
                                                    $status_class = 'secondary';
                                                }
                                                
                                                $paid = isset($row['DaThanhToan']) && intval($row['DaThanhToan']) == 1;
                                        ?>
                                            <tr>
                                                <td><?php echo $stt; ?></td>
                                                <td><strong>#<?php echo $row['ID_DonHang']; ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['HoVaTen']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['SDT']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['ShipperName'] ?? 'Chưa có'); ?></td>
                                                <td><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <?php if ($paid): ?>
                                                        <span class="badge bg-success">Đã thanh toán</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Chưa thanh toán</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="View_Oder_Detail.php?oder_id=<?php echo $row['ID_DonHang']; ?>&view=all" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
                                                    <?php if ($is_staff_via_admin && $st == 0): ?>
                                                        <a href="Approve_Oders.php?oder_id=<?php echo $row['ID_DonHang']; ?>&approve=all" 
                                                           class="btn btn-sm btn-danger">
                                                            <i class="fas fa-check"></i> Duyệt
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php 
                                                $stt++;
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    Không có đơn hàng nào
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card shadow h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-undo fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Yêu Cầu Hoàn Trả</h5>
                                    <p class="card-text">Quản lý các yêu cầu hoàn trả và hoàn tiền của khách hàng</p>
                                    <a href="Staff_Return_Refund_Requests.php" class="btn btn-primary">
                                        <i class="fas fa-arrow-right"></i> Xem Chi Tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card shadow h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                    <h5 class="card-title">Đơn Hàng Đã Hủy</h5>
                                    <p class="card-text">Xem danh sách các đơn hàng đã bị hủy và lý do hủy</p>
                                    <a href="Staff_Canceled_Orders.php" class="btn btn-danger">
                                        <i class="fas fa-arrow-right"></i> Xem Chi Tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card shadow h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-3x text-info mb-3"></i>
                                    <h5 class="card-title">Tất Cả Đơn Hàng</h5>
                                    <p class="card-text">Xem tất cả đơn hàng trong hệ thống</p>
                                    <a href="All_Oders.php" class="btn btn-info">
                                        <i class="fas fa-arrow-right"></i> Xem Chi Tiết
                                    </a>
                                </div>
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

