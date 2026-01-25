<?php
/**
 * Admin Shipper Statistics Page
 * Shows statistics for all shippers and their delivered orders
 */
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Get all shippers (only Role = 'shipper', exclude staff/employee)
$sql_shippers = "SELECT k.ID_KhachHang, k.HoVaTen, k.TenDangNhap, k.Email, k.SDT, k.ThoiGianTao
                 FROM khachhang k
                 INNER JOIN shipper_requests sr ON k.ID_KhachHang = sr.user_id
                 WHERE sr.status = 'approved' AND k.Role = 'shipper'
                 ORDER BY k.HoVaTen ASC";
$result_shippers = mysqli_query($conn, $sql_shippers);

// Get overall statistics
$sql_overall = "SELECT 
    COUNT(DISTINCT d.ID_Shipper) as total_shippers,
    COUNT(CASE WHEN d.TrangThai = 3 THEN 1 END) as total_delivered,
    COUNT(CASE WHEN d.TrangThai = 3 AND d.DaThanhToan = 1 THEN 1 END) as total_paid_delivered,
    SUM(CASE WHEN d.TrangThai = 3 THEN d.TongTien ELSE 0 END) as total_revenue
    FROM donhang d
    WHERE d.ID_Shipper IS NOT NULL AND d.ID_Shipper > 0";
$result_overall = mysqli_query($conn, $sql_overall);
$overall_stats = mysqli_fetch_array($result_overall);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-truck me-2"></i>Thống Kê Shipper</h1>
                    
                    <!-- Overall Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng Shipper
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($overall_stats['total_shippers'] ?? 0); ?>
                                            </div>
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
                                                Tổng Đơn Đã Giao
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($overall_stats['total_delivered'] ?? 0); ?>
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
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Đã Thanh Toán
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($overall_stats['total_paid_delivered'] ?? 0); ?>
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
                                                Tổng Doanh Thu
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($overall_stats['total_revenue'] ?? 0, 0, ',', '.'); ?> đ
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipper Details Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Chi Tiết Theo Shipper</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Shipper</th>
                                            <th>Email</th>
                                            <th>SĐT</th>
                                            <th>Đơn đã giao</th>
                                            <th>Đã thanh toán</th>
                                            <th>Chưa thanh toán</th>
                                            <th>Tổng doanh thu</th>
                                            <th>Ngày đăng ký</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_shippers && mysqli_num_rows($result_shippers) > 0):
                                            // Prepare data for comparison chart
                                            $shipper_chart_data = [];
                                            
                                            while ($shipper = mysqli_fetch_array($result_shippers)):
                                                $shipper_id = $shipper['ID_KhachHang'];
                                                
                                                // Get statistics for this shipper
                                                $sql_shipper_stats = "SELECT 
                                                    COUNT(CASE WHEN TrangThai = 3 THEN 1 END) as delivered,
                                                    COUNT(CASE WHEN TrangThai = 3 AND DaThanhToan = 1 THEN 1 END) as paid,
                                                    COUNT(CASE WHEN TrangThai = 3 AND (DaThanhToan IS NULL OR DaThanhToan = 0) THEN 1 END) as unpaid,
                                                    SUM(CASE WHEN TrangThai = 3 THEN TongTien ELSE 0 END) as revenue
                                                    FROM donhang
                                                    WHERE ID_Shipper = $shipper_id";
                                                $result_stats = mysqli_query($conn, $sql_shipper_stats);
                                                $stats = mysqli_fetch_array($result_stats);
                                                
                                                // Add to chart data
                                                $shipper_chart_data[] = [
                                                    'name' => $shipper['HoVaTen'],
                                                    'delivered' => intval($stats['delivered'] ?? 0)
                                                ];
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($shipper['HoVaTen']); ?></strong><br>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($shipper['TenDangNhap']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($shipper['Email'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($shipper['SDT'] ?? 'N/A'); ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-primary"><?php echo number_format($stats['delivered'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-success"><?php echo number_format($stats['paid'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-warning"><?php echo number_format($stats['unpaid'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-right">
                                                    <strong><?php echo number_format($stats['revenue'] ?? 0, 0, ',', '.'); ?> đ</strong>
                                                </td>
                                                <td><?php echo isset($shipper['ThoiGianTao']) ? date('d/m/Y', strtotime($shipper['ThoiGianTao'])) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="Admin_Shipper_Detail_Statistics.php?shipper_id=<?php echo $shipper_id; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-chart-bar"></i> Chi tiết
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    Chưa có shipper nào được phê duyệt
                                                </td>
                                            </tr>
                                        <?php 
                                            endif;
                                            // Initialize empty array if no shippers
                                            if (!isset($shipper_chart_data)) {
                                                $shipper_chart_data = [];
                                            }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comparison Chart: All Shippers -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">So Sánh Đơn Hàng Đã Giao Theo Shipper</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="shipperComparisonChart" width="400" height="100"></canvas>
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
        // Comparison Chart - All Shippers
        const shipperComparisonData = <?php echo json_encode($shipper_chart_data); ?>;
        
        const ctxComparison = document.getElementById('shipperComparisonChart').getContext('2d');
        const comparisonChart = new Chart(ctxComparison, {
            type: 'bar',
            data: {
                labels: shipperComparisonData.map(s => s.name),
                datasets: [{
                    label: 'Đơn hàng đã giao',
                    data: shipperComparisonData.map(s => s.delivered),
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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
                        text: 'So sánh số đơn hàng đã giao của các Shipper'
                    }
                }
            }
        });
    </script>
</body>
</html>

