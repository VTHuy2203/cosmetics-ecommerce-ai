<?php
/**
 * Admin Shipper Detail Statistics Page
 * Shows detailed statistics for a specific shipper including daily delivery chart
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Get shipper ID from query parameter
$shipper_id = isset($_GET['shipper_id']) ? intval($_GET['shipper_id']) : 0;

if ($shipper_id <= 0) {
    header('Location: Admin_Shipper_Statistics.php');
    exit();
}

// Get shipper information
$sql_shipper = "SELECT k.ID_KhachHang, k.HoVaTen, k.TenDangNhap, k.Email, k.SDT, k.ThoiGianTao
                FROM khachhang k
                WHERE k.ID_KhachHang = $shipper_id AND k.Role = 'shipper'";
$result_shipper = mysqli_query($conn, $sql_shipper);

if (!$result_shipper || mysqli_num_rows($result_shipper) == 0) {
    header('Location: Admin_Shipper_Statistics.php?error=shipper_not_found');
    exit();
}

$shipper = mysqli_fetch_array($result_shipper);

// Get overall statistics for this shipper
$sql_stats = "SELECT 
    COUNT(CASE WHEN TrangThai = 3 THEN 1 END) as total_delivered,
    COUNT(CASE WHEN TrangThai = 3 AND DaThanhToan = 1 THEN 1 END) as total_paid,
    COUNT(CASE WHEN TrangThai = 3 AND (DaThanhToan IS NULL OR DaThanhToan = 0) THEN 1 END) as total_unpaid,
    SUM(CASE WHEN TrangThai = 3 THEN TongTien ELSE 0 END) as total_revenue
    FROM donhang
    WHERE ID_Shipper = $shipper_id";
$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_array($result_stats);

// Get daily delivery statistics for the last 7 days
$daily_deliveries = [];
$day_names = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];

// Check if NgayTao column exists
$check_ngaytao = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'NgayTao'");
$ngaytao_exists = false;
if ($check_ngaytao) {
    $nt_row = mysqli_fetch_assoc($check_ngaytao);
    $ngaytao_exists = intval($nt_row['c']) > 0;
}

// Get deliveries for each day of the current week
// Note: Since there's no delivery date column, we use NgayTao (order creation date) as approximation
// This shows when orders were created, which is close to when they might have been delivered
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = $day_names[date('w', strtotime($date))];
    $date_display = date('d/m', strtotime($date));
    
    // Use NgayTao if it exists to filter by date
    if ($ngaytao_exists) {
        $sql_daily = "SELECT COUNT(*) as count
                      FROM donhang
                      WHERE ID_Shipper = $shipper_id 
                      AND TrangThai = 3
                      AND DATE(NgayTao) = '$date'";
    } else {
        // If NgayTao doesn't exist, we can't filter by date accurately
        // For now, return 0 for all days (better than showing incorrect data)
        $sql_daily = "SELECT 0 as count";
    }
    
    $result_daily = mysqli_query($conn, $sql_daily);
    if ($result_daily) {
        $daily_data = mysqli_fetch_array($result_daily);
        $count = intval($daily_data['count'] ?? 0);
    } else {
        $count = 0;
    }
    
    $daily_deliveries[] = [
        'date' => $date_display,
        'day' => $day_name,
        'count' => $count
    ];
}

// Get unsuccessful deliveries (customer refused)
$sql_unsuccessful = "SELECT d.*, k.HoVaTen as CustomerName, k.SDT as CustomerPhone
                     FROM donhang d
                     LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
                     WHERE d.ID_Shipper = $shipper_id 
                     AND d.GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%'
                     ORDER BY d.ID_DonHang DESC
                     LIMIT 50";
$result_unsuccessful = mysqli_query($conn, $sql_unsuccessful);

// Get all delivered orders for this shipper
$sql_orders = "SELECT d.*, k.HoVaTen as CustomerName, k.SDT as CustomerPhone
               FROM donhang d
               LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
               WHERE d.ID_Shipper = $shipper_id AND d.TrangThai = 3
               ORDER BY d.ID_DonHang DESC
               LIMIT 50";
$result_orders = mysqli_query($conn, $sql_orders);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-line me-2"></i>Thống Kê Chi Tiết: <?php echo htmlspecialchars($shipper['HoVaTen']); ?>
                        </h1>
                        <a href="Admin_Shipper_Statistics.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                    </div>
                    
                    <!-- Shipper Info Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Thông Tin Shipper</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Tên:</strong> <?php echo htmlspecialchars($shipper['HoVaTen']); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Username:</strong> @<?php echo htmlspecialchars($shipper['TenDangNhap']); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($shipper['Email'] ?? 'N/A'); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>SĐT:</strong> <?php echo htmlspecialchars($shipper['SDT'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng Đơn Đã Giao
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($stats['total_delivered'] ?? 0); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-box fa-2x text-gray-300"></i>
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
                                                Đã Thanh Toán
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($stats['total_paid'] ?? 0); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                                Chưa Thanh Toán
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($stats['total_unpaid'] ?? 0); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                                Tổng Doanh Thu
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?> đ
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
                                                Giao Hàng Không Thành Công
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $unsuccessful_count = $result_unsuccessful ? mysqli_num_rows($result_unsuccessful) : 0;
                                                echo number_format($unsuccessful_count);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Daily Delivery Chart (Last 7 Days) -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Biểu Đồ Đơn Hàng Đã Giao Theo Ngày (7 Ngày Gần Nhất)</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyDeliveryChart" width="400" height="100"></canvas>
                        </div>
                    </div>
                    
                    <!-- Unsuccessful Deliveries -->
                    <?php if ($result_unsuccessful && mysqli_num_rows($result_unsuccessful) > 0): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-danger text-white">
                            <h6 class="m-0 font-weight-bold">Giao Hàng Không Thành Công</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách hàng</th>
                                            <th>Lý do từ chối</th>
                                            <th>Trạng thái hàng</th>
                                            <th>Ngày</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($result_unsuccessful, 0);
                                        while ($order = mysqli_fetch_array($result_unsuccessful)):
                                            $ghi_chu = $order['GhiChu'] ?? '';
                                            $refusal_reason = 'Không có lý do';
                                            if (preg_match('/\[KHÁCH HÀNG TỪ CHỐI\]\s*Lý do:\s*(.+?)(?:\||$)/i', $ghi_chu, $matches)) {
                                                $refusal_reason = trim($matches[1]);
                                            }
                                            $returned_to_shop = (stripos($ghi_chu, '[ĐÃ TRẢ VỀ CỬA HÀNG]') !== false);
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $order['ID_DonHang']; ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['CustomerPhone'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <span class="text-danger"><?php echo htmlspecialchars($refusal_reason); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($returned_to_shop): ?>
                                                        <span class="badge bg-success">Đã trả về cửa hàng</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Chưa trả về cửa hàng</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($order['NgayTao']) && !empty($order['NgayTao'])) {
                                                        echo date('d/m/Y H:i', strtotime($order['NgayTao']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Recent Delivered Orders -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Đơn Hàng Đã Giao Gần Đây</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách hàng</th>
                                            <th>Tổng tiền</th>
                                            <th>Thanh toán</th>
                                            <th>Ngày giao</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_orders && mysqli_num_rows($result_orders) > 0):
                                            while ($order = mysqli_fetch_array($result_orders)):
                                                $is_paid = isset($order['DaThanhToan']) && intval($order['DaThanhToan']) == 1;
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $order['ID_DonHang']; ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['CustomerPhone'] ?? ''); ?></small>
                                                </td>
                                                <td><strong><?php echo number_format($order['TongTien'], 0, ',', '.'); ?> đ</strong></td>
                                                <td>
                                                    <?php if ($is_paid): ?>
                                                        <span class="badge bg-success">Đã thanh toán</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Chưa thanh toán</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($order['NgayTao']) && !empty($order['NgayTao'])) {
                                                        echo date('d/m/Y H:i', strtotime($order['NgayTao']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    Chưa có đơn hàng nào đã giao
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
    
    <?php include("../Layouts/Link.php"); ?>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Daily Delivery Chart
        const dailyData = <?php echo json_encode($daily_deliveries); ?>;
        
        const ctxDaily = document.getElementById('dailyDeliveryChart').getContext('2d');
        const dailyChart = new Chart(ctxDaily, {
            type: 'bar',
            data: {
                labels: dailyData.map(d => d.day + ' (' + d.date + ')'),
                datasets: [{
                    label: 'Số đơn hàng đã giao',
                    data: dailyData.map(d => d.count),
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Số đơn hàng đã giao trong 7 ngày gần nhất'
                    }
                }
            }
        });
    </script>
</body>
</html>

