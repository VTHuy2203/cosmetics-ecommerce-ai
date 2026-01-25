<?php
/**
 * Admin Customer Analysis Page
 * Shows customer labels (spam, follow-up, potential) based on chat history
 */

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Session is already started in headHTML.php, no need to start again

// Access control is handled by Admin_Access_Check.php (included via headHTML.php)
// Just read admin session info for display purposes
$admin_role = $_SESSION['admin_role'] ?? null;
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webmipham1;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure customer_analysis table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_analysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        session_id VARCHAR(128) NOT NULL,
        label ENUM('spam','follow-up','potential') NOT NULL DEFAULT 'spam',
        product_interest TEXT NULL,
        last_need TEXT NULL,
        message_count INT NOT NULL DEFAULT 0,
        has_appointment TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        INDEX idx_label (label),
        UNIQUE KEY unique_user_session (user_id, session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    error_log("Admin_Customer_Analysis.php: Schema error: " . $e->getMessage());
}

// Get filter
$filter = $_GET['label'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($filter !== 'all') {
    $where[] = "ca.label = ?";
    $params[] = $filter;
}

if ($search) {
    $where[] = "(k.HoVaTen LIKE ? OR k.Email LIKE ? OR k.SDT LIKE ? OR ca.last_need LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get customers with analysis - show latest data per user_id (not per session_id)
// Use subquery to get latest record per user_id - fix GROUP BY error
$sql = "SELECT 
    ca.id,
    ca.user_id,
    ca.session_id,
    ca.label,
    ca.product_interest,
    ca.last_need,
    ca.message_count,
    ca.has_appointment,
    ca.updated_at,
    k.ID_KhachHang,
    k.HoVaTen,
    k.Email,
    k.SDT,
    k.DiaChi,
    COUNT(DISTINCT cm.id) as total_messages,
    COUNT(DISTINCT appt.id) as total_appointments
FROM (
    SELECT ca1.*
    FROM customer_analysis ca1
    INNER JOIN (
        SELECT user_id, MAX(updated_at) as max_updated
        FROM customer_analysis
        WHERE user_id IS NOT NULL
        GROUP BY user_id
    ) ca2 ON ca1.user_id = ca2.user_id AND ca1.updated_at = ca2.max_updated
    UNION
    SELECT ca3.*
    FROM customer_analysis ca3
    WHERE ca3.user_id IS NULL
) ca
LEFT JOIN khachhang k ON ca.user_id = k.ID_KhachHang
LEFT JOIN chat_messages cm ON (ca.user_id = cm.user_id AND ca.user_id IS NOT NULL) OR (ca.user_id IS NULL AND ca.session_id = cm.session_id)
LEFT JOIN chat_appointments appt ON (ca.user_id = appt.user_id AND ca.user_id IS NOT NULL) OR (ca.user_id IS NULL AND ca.session_id = appt.session_id)
{$whereClause}
GROUP BY ca.id, ca.user_id, ca.session_id, ca.label, ca.product_interest, ca.last_need, ca.message_count, ca.has_appointment, ca.updated_at, k.ID_KhachHang, k.HoVaTen, k.Email, k.SDT, k.DiaChi
ORDER BY ca.updated_at DESC
LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get date filter
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$date_params = [];
$date_where = '';
if ($date_from) {
    $date_where .= " AND ca.updated_at >= ?";
    $date_params[] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $date_where .= " AND ca.updated_at <= ?";
    $date_params[] = $date_to . ' 23:59:59';
}

// Get statistics - count unique customers per label (latest record per user_id)
$stats_sql = "
    SELECT 
        ca.label,
        COUNT(DISTINCT ca.user_id) as count
    FROM (
        SELECT ca1.*
        FROM customer_analysis ca1
        INNER JOIN (
            SELECT user_id, MAX(updated_at) as max_updated
            FROM customer_analysis
            WHERE user_id IS NOT NULL
            GROUP BY user_id
        ) ca2 ON ca1.user_id = ca2.user_id AND ca1.updated_at = ca2.max_updated
        UNION
        SELECT ca3.*
        FROM customer_analysis ca3
        WHERE ca3.user_id IS NULL
    ) ca
    WHERE 1=1 {$date_where}
    GROUP BY ca.label
";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($date_params);
$stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['all'] = array_sum($stats);
?>

<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>
        <div class="container-fluid">
          <h1 class="h3 mb-4 text-gray-800">📊 Phân tích khách hàng</h1>
          
          <!-- Statistics Cards -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng khách hàng</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['all'] ?? 0; ?></div>
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
                      <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Spam</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['spam'] ?? 0; ?></div>
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
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Follow-up</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['follow-up'] ?? 0; ?></div>
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
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Potential</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['potential'] ?? 0; ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Chart Section Toggle Button -->
          <div class="card shadow mb-4">
            <div class="card-body text-center">
              <button type="button" class="btn btn-primary btn-lg" id="toggleChartsBtn" onclick="toggleCharts()">
                <i class="fas fa-chart-bar"></i> Hiển thị biểu đồ
              </button>
            </div>
          </div>
          
          <!-- Chart Section (Hidden by default) -->
          <div id="chartSection" class="row mb-4" style="display: none;">
            <div class="col-md-6">
              <div class="card shadow mb-4">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">📊 Biểu đồ cột (Bar Chart)</h6>
                </div>
                <div class="card-body">
                  <canvas id="customerBarChart" height="200"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card shadow mb-4">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">🥧 Biểu đồ tròn (Pie Chart)</h6>
                </div>
                <div class="card-body">
                  <canvas id="customerPieChart" height="200"></canvas>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Date Filter for Charts -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">📅 Lọc theo ngày</h6>
            </div>
            <div class="card-body">
              <form method="GET" class="row g-3" id="dateFilterForm">
                <div class="col-md-4">
                  <label class="form-label">Từ ngày:</label>
                  <input type="date" name="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Đến ngày:</label>
                  <input type="date" name="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">&nbsp;</label>
                  <button type="submit" class="btn btn-primary form-control">Lọc</button>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Filters -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
            </div>
            <div class="card-body">
              <form method="GET" class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Nhãn:</label>
                  <select name="label" class="form-control">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="spam" <?php echo $filter === 'spam' ? 'selected' : ''; ?>>Spam</option>
                    <option value="follow-up" <?php echo $filter === 'follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                    <option value="potential" <?php echo $filter === 'potential' ? 'selected' : ''; ?>>Potential</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tìm kiếm:</label>
                  <input type="text" name="search" class="form-control" placeholder="Tên, email, SĐT, nhu cầu..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">&nbsp;</label>
                  <button type="submit" class="btn btn-primary form-control">Lọc</button>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Customer Table -->
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">Danh sách khách hàng</h6>
              <?php
              // Check if user is Manager or Admin
              $user_role = $_SESSION['user_role'] ?? 'customer';
              if ($user_role === 'manager' || $user_role === 'admin') {
                echo '<a href="Export_Customer_Analysis_CSV.php?' . http_build_query($_GET) . '" class="btn btn-sm btn-success">Xuất CSV</a>';
              }
              ?>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Khách hàng</th>
                      <th>Nhãn</th>
                      <th>Nhu cầu gần nhất</th>
                      <th>Sản phẩm quan tâm</th>
                      <th>Số tin nhắn</th>
                      <th>Có lịch hẹn</th>
                      <th>Cập nhật</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($customers)): ?>
                      <tr>
                        <td colspan="9" class="text-center">Không có dữ liệu</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($customers as $customer): ?>
                        <tr>
                          <td><?php echo $customer['id']; ?></td>
                          <td>
                            <?php if ($customer['ID_KhachHang']): ?>
                              <strong><?php echo htmlspecialchars($customer['HoVaTen'] ?: 'N/A'); ?></strong><br>
                              <small class="text-muted"><?php echo htmlspecialchars($customer['Email'] ?: 'N/A'); ?></small><br>
                              <small class="text-muted"><?php echo htmlspecialchars($customer['SDT'] ?: 'N/A'); ?></small>
                            <?php else: ?>
                              <small class="text-muted">Khách vãng lai (Session: <?php echo substr($customer['session_id'], 0, 20); ?>...)</small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php
                            $labelColors = [
                              'spam' => 'danger',
                              'follow-up' => 'warning',
                              'potential' => 'success'
                            ];
                            $labelNames = [
                              'spam' => 'Spam',
                              'follow-up' => 'Follow-up',
                              'potential' => 'Potential'
                            ];
                            $color = $labelColors[$customer['label']] ?? 'secondary';
                            $name = $labelNames[$customer['label']] ?? ucfirst($customer['label']);
                            ?>
                            <span class="badge badge-<?php echo $color; ?>"><?php echo $name; ?></span>
                          </td>
                          <td>
                            <?php if ($customer['last_need']): ?>
                              <small><?php echo htmlspecialchars(substr($customer['last_need'], 0, 100)); ?><?php echo strlen($customer['last_need']) > 100 ? '...' : ''; ?></small>
                            <?php else: ?>
                              <small class="text-muted">N/A</small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($customer['product_interest']): ?>
                              <small><?php echo htmlspecialchars(substr($customer['product_interest'], 0, 100)); ?><?php echo strlen($customer['product_interest']) > 100 ? '...' : ''; ?></small>
                            <?php else: ?>
                              <small class="text-muted">N/A</small>
                            <?php endif; ?>
                          </td>
                          <td><?php echo $customer['total_messages'] ?? 0; ?></td>
                          <td>
                            <?php if ($customer['has_appointment']): ?>
                              <span class="badge badge-success">Có (<?php echo $customer['total_appointments'] ?? 0; ?>)</span>
                            <?php else: ?>
                              <span class="badge badge-secondary">Không</span>
                            <?php endif; ?>
                          </td>
                          <td><small><?php echo date('d/m/Y H:i', strtotime($customer['updated_at'])); ?></small></td>
                          <td>
                            <?php if ($customer['ID_KhachHang']): ?>
                              <a href="Customer_Detail.php?id=<?php echo $customer['ID_KhachHang']; ?>" class="btn btn-sm btn-info">Chi tiết</a>
                            <?php else: ?>
                              <a href="Admin_Messages.php?session_id=<?php echo urlencode($customer['session_id']); ?>" class="btn btn-sm btn-info">Xem chat</a>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
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
  
  <!-- Chart.js for visualization -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <script>
    // Chart data
    const chartData = {
      labels: ['Spam', 'Follow-up', 'Potential'],
      data: [
        <?php echo $stats['spam'] ?? 0; ?>,
        <?php echo $stats['follow-up'] ?? 0; ?>,
        <?php echo $stats['potential'] ?? 0; ?>
      ],
      colors: {
        background: [
          'rgba(255, 99, 132, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)'
        ],
        border: [
          'rgba(255, 99, 132, 1)',
          'rgba(255, 206, 86, 1)',
          'rgba(75, 192, 192, 1)'
        ]
      }
    };

    let barChart = null;
    let pieChart = null;

    // Toggle charts visibility
    function toggleCharts() {
      const chartSection = document.getElementById('chartSection');
      const toggleBtn = document.getElementById('toggleChartsBtn');
      
      if (chartSection.style.display === 'none') {
        chartSection.style.display = 'flex';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Ẩn biểu đồ';
        
        // Initialize charts if not already initialized
        if (!barChart) {
          initCharts();
        }
      } else {
        chartSection.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-chart-bar"></i> Hiển thị biểu đồ';
      }
    }

    // Initialize charts
    function initCharts() {
      // Bar Chart - Vertical axis = number of customers, Horizontal axis = labels
      const barCtx = document.getElementById('customerBarChart');
      if (barCtx) {
        barChart = new Chart(barCtx, {
          type: 'bar',
          data: {
            labels: chartData.labels, // Horizontal axis = evaluation labels
            datasets: [{
              label: 'Số lượng khách hàng',
              data: chartData.data, // Vertical axis = number of customers
              backgroundColor: chartData.colors.background,
              borderColor: chartData.colors.border,
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'x', // Horizontal axis = labels
            scales: {
              y: { // Vertical axis = number of customers
                beginAtZero: true,
                ticks: {
                  stepSize: 1,
                  precision: 0
                },
                title: {
                  display: true,
                  text: 'Số lượng khách hàng'
                }
              },
              x: { // Horizontal axis = evaluation labels
                title: {
                  display: true,
                  text: 'Nhãn đánh giá'
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
                text: 'Phân bổ khách hàng theo nhãn (Bar Chart)',
                font: { size: 14 }
              }
            }
          }
        });
      }

      // Pie Chart
      const pieCtx = document.getElementById('customerPieChart');
      if (pieCtx) {
        pieChart = new Chart(pieCtx, {
          type: 'pie',
          data: {
            labels: chartData.labels,
            datasets: [{
              label: 'Số lượng khách hàng',
              data: chartData.data,
              backgroundColor: chartData.colors.background,
              borderColor: chartData.colors.border,
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              legend: {
                display: true,
                position: 'right'
              },
              title: {
                display: true,
                text: 'Phân bổ khách hàng theo nhãn (Pie Chart)',
                font: { size: 14 }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const label = context.label || '';
                    const value = context.parsed || 0;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return label + ': ' + value + ' (' + percentage + '%)';
                  }
                }
              }
            }
          }
        });
      }
    }
  </script>
</body>
</html>

