<?php
/**
 * Staff Dashboard
 * Shows latest updates: new customers and chat messages
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// CRITICAL: Use ONLY admin session variables (completely ignore UI session)
// Staff access is determined by admin_role via Admin_Login.php, not UI login
$admin_role = $_SESSION['admin_role'] ?? null;
$is_staff_via_admin = ($admin_role === 'employee');
$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');

// Allow staff (via admin login) or admin/manager to access this page
if (!$is_staff_via_admin && !$is_admin_or_manager) {
    // No valid admin session - redirect to admin login
    header("Location: ../View/Admin_Login.php");
    exit();
}

// Use PDO for chat tables
function pdo_connect_chat() {
    $dsn = "mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4";
    return new PDO($dsn, "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

$pdo = pdo_connect_chat();

// Get new customers (last 7 days)
$sql_new_customers = "SELECT * FROM khachhang 
                      WHERE Role != 'admin' AND Role != 'manager'
                      AND DATE(NgayTao) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      ORDER BY NgayTao DESC 
                      LIMIT 20";
$result_new_customers = mysqli_query($conn, $sql_new_customers);

// Get latest chat messages (last 24 hours)
$sql_latest_messages = "SELECT cm.*, kh.HoVaTen, kh.SDT, kh.Email
                        FROM chat_messages cm
                        LEFT JOIN khachhang kh ON cm.user_id = kh.ID_KhachHang
                        WHERE cm.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ORDER BY cm.created_at DESC
                        LIMIT 50";
$stmt_messages = $pdo->query($sql_latest_messages);
$latest_messages = $stmt_messages->fetchAll();

// Get statistics
$sql_total_customers = "SELECT COUNT(*) as total FROM khachhang WHERE Role != 'admin' AND Role != 'manager'";
$result_total = mysqli_query($conn, $sql_total_customers);
$total_customers = mysqli_fetch_array($result_total)['total'];

$sql_today_messages = "SELECT COUNT(*) as total FROM chat_messages WHERE DATE(created_at) = CURDATE()";
$stmt_today = $pdo->query($sql_today_messages);
$today_messages = $stmt_today->fetch()['total'];

// Get pending orders (TrangThai = '0' or 0 - not yet approved by Admin)
// CRITICAL: Check both string '0' and integer 0 for TrangThai
$sql_pending_orders = "SELECT * FROM donhang WHERE TrangThai = '0' OR TrangThai = 0 ORDER BY NgayTao DESC LIMIT 10";
$result_pending_orders = mysqli_query($conn, $sql_pending_orders);

// Check for query errors
if (!$result_pending_orders) {
    error_log("Staff_Dashboard.php: Query error: " . mysqli_error($conn));
    $result_pending_orders = false;
    $total_pending_orders = 0;
} else {
    $sql_total_pending = "SELECT COUNT(*) as total FROM donhang WHERE TrangThai = '0' OR TrangThai = 0";
    $result_total_pending = mysqli_query($conn, $sql_total_pending);
    if ($result_total_pending) {
        $total_pending_row = mysqli_fetch_array($result_total_pending);
        $total_pending_orders = isset($total_pending_row['total']) ? intval($total_pending_row['total']) : 0;
    } else {
        $total_pending_orders = 0;
    }
}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Bảng Điều Khiển Nhân Viên</h1>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng Khách Hàng
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_customers); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Khách Hàng Mới (7 ngày)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo mysqli_num_rows($result_new_customers); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Tin Nhắn Hôm Nay
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($today_messages); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Tin Nhắn Mới (24h)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($latest_messages); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Đơn Hàng Cần Duyệt
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_pending_orders); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Statistics Row for Return Orders -->
                    <div class="row mb-4">
                        <?php
                        // Get pending return/refund requests count
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
                        
                        $sql_total_return = "SELECT COUNT(*) as total FROM return_refund_requests WHERE TrangThai = 'pending'";
                        $result_total_return = mysqli_query($conn, $sql_total_return);
                        $total_return_requests = 0;
                        if ($result_total_return && mysqli_num_rows($result_total_return) > 0) {
                            $total_return_row = mysqli_fetch_array($result_total_return);
                            $total_return_requests = isset($total_return_row['total']) ? intval($total_return_row['total']) : 0;
                        }
                        ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 clickable-card" style="cursor: pointer;" onclick="window.location.href='../Oders/Staff_Return_Refund_Requests.php';" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Đơn Hàng Hoàn Trả
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_return_requests); ?></div>
                                            <small class="text-muted">Click để xem tất cả</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-undo fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Orders Section for Staff -->
                    <div class="row mb-4">
                        <div class="col-xl-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-danger">
                                        <i class="fas fa-shopping-cart me-2"></i>Đơn Hàng Cần Duyệt
                                    </h6>
                                    <a href="../Oders/Approve_Oders.php" class="btn btn-danger btn-sm">
                                        <i class="fas fa-eye me-1"></i>Xem Tất Cả (<?php echo $total_pending_orders; ?>)
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if ($result_pending_orders && mysqli_num_rows($result_pending_orders) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>STT</th>
                                                        <th>Tên Khách Hàng</th>
                                                        <th>SDT</th>
                                                        <th>Tổng Tiền</th>
                                                        <th>Địa Chỉ</th>
                                                        <th>Ngày Đặt</th>
                                                        <th>Thao Tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $stt = 1;
                                                    mysqli_data_seek($result_pending_orders, 0);
                                                    while ($order = mysqli_fetch_array($result_pending_orders)): 
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $stt++; ?></td>
                                                            <td><?php echo htmlspecialchars($order['HoVaTen']); ?></td>
                                                            <td><?php echo htmlspecialchars($order['SDT']); ?></td>
                                                            <td><?php echo number_format($order['TongTien'], 0, ',', '.') . ' ₫'; ?></td>
                                                            <td><?php echo htmlspecialchars(substr($order['DiaChi'], 0, 50)) . (strlen($order['DiaChi']) > 50 ? '...' : ''); ?></td>
                                                            <td><?php echo isset($order['NgayTao']) ? date('d/m/Y H:i', strtotime($order['NgayTao'])) : 'N/A'; ?></td>
                                                            <td class="text-center">
                                                                <a href="../Oders/Approve_Oders.php?oder_id=<?php echo $order['ID_DonHang']; ?>&approve=approve" 
                                                                   class="btn btn-success btn-sm" 
                                                                   onclick="return confirm('Bạn có chắc chắn muốn duyệt đơn hàng này?')">
                                                                    <i class="fas fa-check me-1"></i>Duyệt
                                                                </a>
                                                                <a href="../Oders/View_Oder_Detail.php?oder_id=<?php echo $order['ID_DonHang']; ?>&view=approve" 
                                                                   class="btn btn-primary btn-sm">
                                                                    <i class="fas fa-eye me-1"></i>Chi Tiết
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php 
                                                    endwhile;
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if ($total_pending_orders > 10): ?>
                                            <div class="text-center mt-3">
                                                <a href="../Oders/Approve_Oders.php" class="btn btn-outline-danger">
                                                    Xem Tất Cả <?php echo $total_pending_orders; ?> Đơn Hàng Cần Duyệt
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                            <p class="mb-0">Không có đơn hàng nào cần duyệt.</p>
                                            <p class="text-muted small mt-2">Tất cả đơn hàng đã được duyệt hoặc chưa có đơn hàng mới.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- New Customers -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user-plus me-2"></i>Khách Hàng Mới (7 Ngày Qua)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Họ Tên</th>
                                                    <th>Email</th>
                                                    <th>SDT</th>
                                                    <th>Ngày Đăng Ký</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if ($result_new_customers && mysqli_num_rows($result_new_customers) > 0):
                                                    mysqli_data_seek($result_new_customers, 0);
                                                    while ($customer = mysqli_fetch_array($result_new_customers)): 
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($customer['HoVaTen']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['SDT']); ?></td>
                                                        <td><?php echo isset($customer['NgayTao']) ? date('d/m/Y H:i', strtotime($customer['NgayTao'])) : 'N/A'; ?></td>
                                                    </tr>
                                                <?php 
                                                    endwhile;
                                                else:
                                                ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-3">
                                                            Không có khách hàng mới trong 7 ngày qua
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Latest Chat Messages -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-info">
                                        <i class="fas fa-comments me-2"></i>Tin Nhắn Mới Nhất (24 Giờ Qua)
                                    </h6>
                                </div>
                                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                    <?php if (count($latest_messages) > 0): ?>
                                        <div class="list-group">
                                            <?php foreach ($latest_messages as $msg): 
                                                $is_user = $msg['direction'] === 'user';
                                                $customer_name = $msg['HoVaTen'] ?? ($msg['SDT'] ?? 'Khách vãng lai');
                                                $msg_time = isset($msg['created_at']) ? date('d/m/Y H:i', strtotime($msg['created_at'])) : 'N/A';
                                            ?>
                                                <div class="list-group-item <?php echo $is_user ? 'list-group-item-primary' : 'list-group-item-light'; ?> mb-2">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">
                                                            <?php if ($is_user): ?>
                                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($customer_name); ?>
                                                            <?php else: ?>
                                                                <i class="fas fa-robot me-1"></i>Bot
                                                            <?php endif; ?>
                                                        </h6>
                                                        <small><?php echo $msg_time; ?></small>
                                                    </div>
                                                    <p class="mb-1"><?php echo htmlspecialchars(substr($msg['content'], 0, 150)); ?><?php echo strlen($msg['content']) > 150 ? '...' : ''; ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            Không có tin nhắn mới trong 24 giờ qua
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include("../Layouts/Link.php"); ?>
</body>
</html>

