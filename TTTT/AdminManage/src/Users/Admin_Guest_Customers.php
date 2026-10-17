<?php
/**
 * Admin Guest Customers Page
 * View chat history and information for non-logged-in customers
 */

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

try {
    $pdo = app_pdo();
} catch (PDOException $e) {
    error_log('Admin guest database error: ' . $e->getMessage());
    die('Database connection failed.');
}

// Get filter
$filter = in_array($_GET['filter'] ?? 'all', ['all', 'normal', 'spam', 'follow-up', 'potential'], true) ? ($_GET['filter'] ?? 'all') : 'all';
$search = trim((string) ($_GET['search'] ?? ''));
$session_id = trim((string) ($_GET['session_id'] ?? ''));
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(gc.name LIKE ? OR gc.phone LIKE ? OR gc.address LIKE ? OR gc.email LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($session_id) {
    $where[] = "gc.session_id = ?";
    $params[] = $session_id;
}
if ($filter !== 'all') {
    // segment and label are ENUMs with different collations: compare them as one explicit collation.
    $where[] = "CONVERT(COALESCE(sc.segment, ca.label, 'normal') USING utf8mb4) COLLATE utf8mb4_unicode_ci = ?";
    $params[] = $filter;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get guest customers
$sql = "SELECT 
    gc.id,
    gc.session_id,
    gc.name,
    gc.phone,
    gc.address,
    gc.email,
    gc.notes,
    gc.created_at,
    gc.updated_at,
    COALESCE(sc.segment, ca.label, 'normal') AS label,
    COALESCE(sc.intent_score, ca.lead_score, 0) AS intent_score,
    ca.message_count,
    ca.has_appointment,
    COUNT(DISTINCT cm.id) as total_messages,
    COUNT(DISTINCT appt.id) as total_appointments
FROM guest_customers gc
LEFT JOIN customer_analysis ca ON gc.session_id = ca.session_id AND ca.user_id IS NULL
LEFT JOIN customer_scores_current sc ON sc.subject_key = CONCAT('session:', gc.session_id)
LEFT JOIN chat_messages cm ON gc.session_id = cm.session_id AND cm.user_id IS NULL
LEFT JOIN chat_appointments appt ON gc.session_id = appt.session_id AND appt.user_id IS NULL
{$whereClause}
GROUP BY gc.id,gc.session_id,gc.name,gc.phone,gc.address,gc.email,gc.notes,gc.created_at,gc.updated_at,sc.segment,sc.intent_score,ca.label,ca.lead_score,ca.message_count,ca.has_appointment
ORDER BY gc.updated_at DESC
LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$guests = $stmt->fetchAll();

// Get statistics
$stats = $pdo->query("
    SELECT 
        COALESCE(sc.segment, ca.label, 'normal') as label,
        COUNT(DISTINCT gc.id) as count
    FROM guest_customers gc
    LEFT JOIN customer_analysis ca ON gc.session_id = ca.session_id AND ca.user_id IS NULL
    LEFT JOIN customer_scores_current sc ON sc.subject_key = CONCAT('session:', gc.session_id)
    GROUP BY COALESCE(sc.segment, ca.label, 'normal')
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Real totals, not the size of the 100-row page.
$stats['all'] = (int) $pdo->query("SELECT COUNT(*) FROM guest_customers")->fetchColumn();
$with_contact = (int) $pdo->query("SELECT COUNT(*) FROM guest_customers WHERE phone <> ''")->fetchColumn();
?>

<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>
        <div class="container-fluid">
          <h1 class="h3 mb-4 text-gray-800">👥 Khách hàng vãng lai (Guest Customers)</h1>
          
          <!-- Statistics Cards -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng khách vãng lai</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($stats['all'] ?? 0); ?></div>
                      <small class="text-muted"><?php echo $with_contact; ?> khách đã để lại SĐT</small>
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
          
          <!-- Filters -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
            </div>
            <div class="card-body">
              <form method="GET" class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Nhãn:</label>
                  <select name="filter" class="form-control">
                    <?php foreach (['all' => 'Tất cả', 'potential' => 'Tiềm năng', 'follow-up' => 'Đang theo dõi', 'normal' => 'Bình thường', 'spam' => 'Spam'] as $value => $text): ?>
                      <option value="<?php echo $value; ?>" <?php echo $filter === $value ? 'selected' : ''; ?>><?php echo $text; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tìm kiếm:</label>
                  <input type="text" name="search" class="form-control" placeholder="Tên, SĐT, địa chỉ, email..." value="<?php echo $h($search); ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">&nbsp;</label>
                  <button type="submit" class="btn btn-primary form-control">Lọc</button>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Guest Customers Table -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Danh sách khách hàng vãng lai</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Thông tin khách hàng</th>
                      <th>Nhãn</th>
                      <th>Số tin nhắn</th>
                      <th>Có lịch hẹn</th>
                      <th>Ngày tạo</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($guests)): ?>
                      <tr>
                        <td colspan="7" class="text-center">Không có dữ liệu</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($guests as $guest): ?>
                        <tr>
                          <td><?php echo (int) $guest['id']; ?></td>
                          <td>
                            <strong><?php echo $h($guest['name']); ?></strong><br>
                            <small class="text-muted">SĐT: <?php echo $guest['phone'] !== '' ? $h($guest['phone']) : 'chưa để lại'; ?></small><br>
                            <?php if ($guest['email']): ?>
                              <small class="text-muted">Email: <?php echo $h($guest['email']); ?></small><br>
                            <?php endif; ?>
                            <?php if ($guest['address']): ?>
                              <small class="text-muted">Địa chỉ: <?php echo $h(mb_strimwidth((string) $guest['address'], 0, 100, '...', 'UTF-8')); ?></small><br>
                            <?php endif; ?>
                            <small class="text-muted">Session: <?php echo $h(substr((string) $guest['session_id'], 0, 20)); ?>...</small>
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
                            $label = $guest['label'] ?? 'unlabeled';
                            $color = $labelColors[$label] ?? 'secondary';
                            $name = $labelNames[$label] ?? ucfirst($label);
                            ?>
                            <span class="badge badge-<?php echo $color; ?>"><?php echo $h($name); ?></span>
                            <br><small class="text-muted"><?php echo (int) ($guest['intent_score'] ?? 0); ?>/100</small>
                          </td>
                          <td><?php echo (int) ($guest['total_messages'] ?? 0); ?></td>
                          <td>
                            <?php if ($guest['has_appointment'] || (int) ($guest['total_appointments'] ?? 0) > 0): ?>
                              <span class="badge badge-success">Có (<?php echo (int) ($guest['total_appointments'] ?? 0); ?>)</span>
                            <?php else: ?>
                              <span class="badge badge-secondary">Không</span>
                            <?php endif; ?>
                          </td>
                          <td><small><?php echo $h(date('d/m/Y H:i', strtotime((string) $guest['created_at']))); ?></small></td>
                          <td>
                            <a href="Admin_Guest_Chat_History.php?session_id=<?php echo $h(urlencode((string) $guest['session_id'])); ?>" class="btn btn-sm btn-info">Xem chat</a>
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
  <script>
    // Refresh the list when a guest chats or books, so staff see new contacts without F5.
    (function () {
      var timer = null;
      window.addEventListener('moshop:realtime-notification', function (event) {
        var message = event.detail || {};
        var data = message.data || {};
        var relevant = (message.type === 'chat_message' && data.is_guest) || (message.type === 'new_appointment' && data.is_guest);
        if (!relevant) return;
        clearTimeout(timer);
        timer = setTimeout(function () { location.reload(); }, 1500);
      });
    })();
  </script>
</body>
</html>

