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

try {
    $pdo = app_pdo();
} catch (PDOException $e) {
    error_log('Admin analysis database error: ' . $e->getMessage());
    die('Database connection failed.');
}

// Get filters
$filter = in_array($_GET['label'] ?? 'all', ['all', 'normal', 'spam', 'follow-up', 'potential'], true) ? ($_GET['label'] ?? 'all') : 'all';
$search = trim((string) ($_GET['search'] ?? ''));
$validDate = static fn(string $value): string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
$date_from = $validDate((string) ($_GET['date_from'] ?? ''));
$date_to = $validDate((string) ($_GET['date_to'] ?? ''));

// Date range applies to the customer's last real activity, for both the table and the statistics.
$date_where = [];
$date_params = [];
if ($date_from !== '') {
    $date_where[] = "sc.last_activity_at >= ?";
    $date_params[] = $date_from . ' 00:00:00';
}
if ($date_to !== '') {
    $date_where[] = "sc.last_activity_at <= ?";
    $date_params[] = $date_to . ' 23:59:59';
}

// Build query
$where = $date_where;
$params = $date_params;

if ($filter !== 'all') {
    $where[] = "sc.segment = ?";
    $params[] = $filter;
}

if ($search !== '') {
    $where[] = "(k.HoVaTen LIKE ? OR k.Email LIKE ? OR k.SDT LIKE ? OR ca.last_need LIKE ? OR ca.product_interest LIKE ?)";
    $searchParam = "%{$search}%";
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get customers with analysis - show latest data per user_id (not per session_id)
// Use subquery to get latest record per user_id - fix GROUP BY error
$sql = "SELECT 
    sc.subject_key AS id,
    sc.user_id,
    sc.session_id,
    sc.segment AS label,
    sc.intent_score,
    sc.spam_risk_score,
    sc.value_score,
    sc.value_tier,
    sc.reason_codes,
    sc.scoring_version,
    ca.product_interest,
    ca.last_need,
    COALESCE(ca.message_count, 0) AS message_count,
    COALESCE(ca.has_appointment, 0) AS has_appointment,
    sc.calculated_at AS updated_at,
    sc.last_activity_at,
    sc.manual_segment,
    k.ID_KhachHang,
    k.HoVaTen,
    k.Email,
    k.SDT,
    k.DiaChi,
    COUNT(DISTINCT cm.id) as total_messages,
    COUNT(DISTINCT appt.id) as total_appointments
FROM customer_scores_current sc
LEFT JOIN khachhang k ON sc.user_id = k.ID_KhachHang
LEFT JOIN customer_analysis ca ON ca.id = (
    SELECT ca2.id FROM customer_analysis ca2
    WHERE (sc.user_id IS NOT NULL AND ca2.user_id = sc.user_id)
       OR (sc.user_id IS NULL AND ca2.user_id IS NULL AND ca2.session_id = sc.session_id)
    ORDER BY ca2.updated_at DESC, ca2.id DESC LIMIT 1
)
LEFT JOIN chat_messages cm ON (sc.user_id = cm.user_id AND sc.user_id IS NOT NULL) OR (sc.user_id IS NULL AND sc.session_id = cm.session_id)
LEFT JOIN chat_appointments appt ON (sc.user_id = appt.user_id AND sc.user_id IS NOT NULL) OR (sc.user_id IS NULL AND sc.session_id = appt.session_id)
{$whereClause}
GROUP BY sc.subject_key,sc.user_id,sc.session_id,sc.segment,sc.intent_score,sc.spam_risk_score,sc.value_score,sc.value_tier,sc.reason_codes,sc.scoring_version,sc.calculated_at,sc.last_activity_at,sc.manual_segment,ca.product_interest,ca.last_need,ca.message_count,ca.has_appointment,k.ID_KhachHang,k.HoVaTen,k.Email,k.SDT,k.DiaChi
ORDER BY FIELD(sc.segment,'potential','follow-up','normal','spam'), sc.intent_score DESC, sc.last_activity_at DESC
LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Statistics per label within the same date range.
$stats_where = $date_where ? 'WHERE ' . implode(' AND ', $date_where) : '';
$stats_stmt = $pdo->prepare("SELECT sc.segment, COUNT(*) AS count FROM customer_scores_current sc {$stats_where} GROUP BY sc.segment");
$stats_stmt->execute($date_params);
$stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stats['all'] = array_sum($stats);

$audience_stmt = $pdo->prepare("SELECT SUM(sc.user_id IS NOT NULL) AS registered, SUM(sc.user_id IS NULL) AS guests
                                FROM customer_scores_current sc {$stats_where}");
$audience_stmt->execute($date_params);
$audience = $audience_stmt->fetch() ?: [];
$last_run = $pdo->query("SELECT MAX(calculated_at) FROM customer_scores_current")->fetchColumn();

$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$can_export = in_array($_SESSION['admin_role'] ?? '', ['admin', 'manager'], true);
?>

<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>
        <div class="container-fluid">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
              <h1 class="h3 mb-1 text-gray-800">📊 Phân tích khách hàng</h1>
              <small class="text-muted">
                Phân tích gần nhất: <?php echo $last_run ? $h(date('d/m/Y H:i', strtotime((string) $last_run))) : 'chưa chạy'; ?>
                · Tự cập nhật khi khách đặt đơn, chat, thêm giỏ/yêu thích và định kỳ 5 phút
              </small>
            </div>
            <button type="button" class="btn btn-primary" id="btn-rescore" onclick="rescoreCustomers()">
              <i class="fas fa-sync-alt"></i> <span id="rescore-text">Phân tích lại</span>
              <span id="rescore-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
          </div>

          <!-- Statistics Cards -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng khách hàng</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($stats['all'] ?? 0); ?></div>
                      <small class="text-muted"><?php echo (int) ($audience['registered'] ?? 0); ?> tài khoản · <?php echo (int) ($audience['guests'] ?? 0); ?> khách vãng lai</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Bình thường</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['normal'] ?? 0; ?></div>
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
          
          <!-- Filters (label, search and last-activity date apply to the table, cards and charts) -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
            </div>
            <div class="card-body">
              <form method="GET" class="row g-3">
                <div class="col-md-3 mb-2">
                  <label class="form-label">Hoạt động từ ngày:</label>
                  <input type="date" name="date_from" class="form-control" value="<?php echo $h($date_from); ?>">
                </div>
                <div class="col-md-3 mb-2">
                  <label class="form-label">Đến ngày:</label>
                  <input type="date" name="date_to" class="form-control" value="<?php echo $h($date_to); ?>">
                </div>
                <div class="col-md-6 mb-2"></div>
                <div class="col-md-3">
                  <label class="form-label">Nhãn:</label>
                  <select name="label" class="form-control">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="normal" <?php echo $filter === 'normal' ? 'selected' : ''; ?>>Bình thường</option>
                    <option value="spam" <?php echo $filter === 'spam' ? 'selected' : ''; ?>>Spam</option>
                    <option value="follow-up" <?php echo $filter === 'follow-up' ? 'selected' : ''; ?>>Đang theo dõi</option>
                    <option value="potential" <?php echo $filter === 'potential' ? 'selected' : ''; ?>>Tiềm năng</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tìm kiếm:</label>
                  <input type="text" name="search" class="form-control" placeholder="Tên, email, SĐT, nhu cầu, sản phẩm..." value="<?php echo $h($search); ?>">
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
              <?php if ($can_export): ?>
                <a href="Export_Customer_Analysis_CSV.php?<?php echo $h(http_build_query(['label' => $filter, 'search' => $search, 'date_from' => $date_from, 'date_to' => $date_to])); ?>" class="btn btn-sm btn-success">Xuất CSV</a>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <style>
                  .analysis-table .col-fit { width: 1%; white-space: nowrap; }
                  .analysis-table .col-score { min-width: 190px; }
                  .analysis-table .col-text { min-width: 200px; }
                  .analysis-table .col-customer { min-width: 180px; }
                </style>
                <table class="table table-bordered analysis-table" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th class="col-fit">ID</th>
                      <th class="col-customer">Khách hàng</th>
                      <th class="col-fit">Nhãn</th>
                      <th class="col-score">Điểm</th>
                      <th class="col-text">Nhu cầu gần nhất</th>
                      <th class="col-text">Sản phẩm quan tâm</th>
                      <th class="col-fit">Số tin nhắn</th>
                      <th class="col-fit">Có lịch hẹn</th>
                      <th class="col-fit">Hoạt động gần nhất</th>
                      <th class="col-fit">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($customers)): ?>
                      <tr>
                        <td colspan="10" class="text-center">Không có dữ liệu</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($customers as $customer): ?>
                        <tr>
                          <td class="col-fit" title="<?php echo $h($customer['id']); ?>">
                            <?php echo $customer['user_id'] ? '#' . (int) $customer['user_id'] : '<small class="text-muted">Vãng lai</small>'; ?>
                          </td>
                          <td>
                            <?php if ($customer['ID_KhachHang']): ?>
                              <strong><?php echo $h($customer['HoVaTen'] ?: 'N/A'); ?></strong><br>
                              <small class="text-muted"><?php echo $h($customer['Email'] ?: 'N/A'); ?></small><br>
                              <small class="text-muted"><?php echo $h($customer['SDT'] ?: 'N/A'); ?></small>
                            <?php else: ?>
                              <small class="text-muted">Khách vãng lai (Session: <?php echo $h(substr((string) $customer['session_id'], 0, 20)); ?>...)</small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php
                            $labelColors = [
                              'normal' => 'secondary',
                              'spam' => 'danger',
                              'follow-up' => 'warning',
                              'potential' => 'success'
                            ];
                            $labelNames = [
                              'normal' => 'Bình thường',
                              'spam' => 'Spam',
                              'follow-up' => 'Đang theo dõi',
                              'potential' => 'Tiềm năng'
                            ];
                            $color = $labelColors[$customer['label']] ?? 'secondary';
                            $name = $labelNames[$customer['label']] ?? ucfirst((string) $customer['label']);
                            $reasons = json_decode((string) ($customer['reason_codes'] ?? '[]'), true) ?: [];
                            ?>
                            <span class="badge badge-<?php echo $color; ?>"><?php echo $h($name); ?></span>
                            <?php if (!empty($customer['manual_segment'])): ?>
                              <br><small class="text-muted">(gán thủ công)</small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <strong><?php echo (int) $customer['intent_score']; ?>/100</strong><br>
                            <small class="text-muted">Spam: <?php echo (int) $customer['spam_risk_score']; ?> · Giá trị: <?php echo $h($customer['value_tier']); ?></small>
                            <?php foreach ($reasons as $reason): ?>
                              <br><small class="<?php echo (int) ($reason['points'] ?? 0) < 0 ? 'text-danger' : 'text-success'; ?>"><?php echo sprintf('%+d', (int) ($reason['points'] ?? 0)); ?> <?php echo $h($reason['label'] ?? $reason['code'] ?? ''); ?></small>
                            <?php endforeach; ?>
                          </td>
                          <td>
                            <?php if ($customer['last_need']): ?>
                              <small><?php echo $h(mb_strimwidth((string) $customer['last_need'], 0, 100, '...', 'UTF-8')); ?></small>
                            <?php else: ?>
                              <small class="text-muted">N/A</small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($customer['product_interest']): ?>
                              <small><?php echo $h(mb_strimwidth((string) $customer['product_interest'], 0, 100, '...', 'UTF-8')); ?></small>
                            <?php else: ?>
                              <small class="text-muted">N/A</small>
                            <?php endif; ?>
                          </td>
                          <td><?php echo (int) ($customer['total_messages'] ?? 0); ?></td>
                          <td>
                            <?php if ($customer['has_appointment']): ?>
                              <span class="badge badge-success">Có (<?php echo (int) ($customer['total_appointments'] ?? 0); ?>)</span>
                            <?php else: ?>
                              <span class="badge badge-secondary">Không</span>
                            <?php endif; ?>
                          </td>
                          <td><small><?php echo $customer['last_activity_at'] ? $h(date('d/m/Y H:i', strtotime((string) $customer['last_activity_at']))) : 'Chưa có'; ?></small></td>
                          <td>
                            <?php if ($customer['ID_KhachHang']): ?>
                              <a href="Customer_Detail.php?id=<?php echo (int) $customer['ID_KhachHang']; ?>" class="btn btn-sm btn-info">Chi tiết</a>
                            <?php else: ?>
                              <a href="Admin_Messages.php?session_id=<?php echo $h(urlencode((string) $customer['session_id'])); ?>" class="btn btn-sm btn-info">Xem chat</a>
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
    // Re-score every customer and guest session, then reload with the current filters.
    function rescoreCustomers() {
      const btn = document.getElementById('btn-rescore');
      const text = document.getElementById('rescore-text');
      const spinner = document.getElementById('rescore-spinner');
      btn.disabled = true;
      text.textContent = 'Đang phân tích...';
      spinner.classList.remove('d-none');

      fetch('/TTTT/WebBanHangMyPham/src/Processing/Score_Lead.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-Token': <?php echo json_encode(csrf_token()); ?>
        }
      })
      .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok && res.status !== 207) {
          throw new Error(data.error || 'Máy chủ không thể xử lý yêu cầu');
        }
        return data;
      })
      .then(data => {
        if (!data.ok && data.failed) {
          alert('Đã phân tích ' + data.updated + ' khách, lỗi ' + data.failed + '.');
        }
        location.reload();
      })
      .catch(err => {
        alert('Không thể phân tích khách hàng: ' + err.message);
        btn.disabled = false;
        text.textContent = 'Phân tích lại';
        spinner.classList.add('d-none');
      });
    }

    // Chart data
    const chartData = {
      labels: ['Bình thường', 'Spam', 'Đang theo dõi', 'Tiềm năng'],
      data: [
        <?php echo $stats['normal'] ?? 0; ?>,
        <?php echo $stats['spam'] ?? 0; ?>,
        <?php echo $stats['follow-up'] ?? 0; ?>,
        <?php echo $stats['potential'] ?? 0; ?>
      ],
      colors: {
        background: [
          'rgba(108, 117, 125, 0.6)',
          'rgba(255, 99, 132, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)'
        ],
        border: [
          'rgba(108, 117, 125, 1)',
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

