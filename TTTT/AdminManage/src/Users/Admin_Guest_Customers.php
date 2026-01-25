<?php
/**
 * Admin Guest Customers Page
 * View chat history and information for non-logged-in customers
 */

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webmipham1;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure guest_customers table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS guest_customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(128) NOT NULL,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NULL,
        email VARCHAR(255) NULL,
        notes TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_session_id (session_id),
        INDEX idx_phone (phone)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    error_log("Admin_Guest_Customers.php: Schema error: " . $e->getMessage());
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$session_id = $_GET['session_id'] ?? '';

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
    ca.label,
    ca.message_count,
    ca.has_appointment,
    COUNT(DISTINCT cm.id) as total_messages,
    COUNT(DISTINCT appt.id) as total_appointments
FROM guest_customers gc
LEFT JOIN customer_analysis ca ON gc.session_id = ca.session_id AND ca.user_id IS NULL
LEFT JOIN chat_messages cm ON gc.session_id = cm.session_id AND cm.user_id IS NULL
LEFT JOIN chat_appointments appt ON gc.session_id = appt.session_id AND appt.user_id IS NULL
{$whereClause}
GROUP BY gc.id, gc.session_id, gc.name, gc.phone, gc.address, gc.email, gc.notes, gc.created_at, gc.updated_at, ca.label, ca.message_count, ca.has_appointment
ORDER BY gc.updated_at DESC
LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$guests = $stmt->fetchAll();

// Get statistics
$stats = $pdo->query("
    SELECT 
        COALESCE(ca.label, 'unlabeled') as label,
        COUNT(DISTINCT gc.id) as count
    FROM guest_customers gc
    LEFT JOIN customer_analysis ca ON gc.session_id = ca.session_id AND ca.user_id IS NULL
    GROUP BY ca.label
")->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['all'] = count($guests);
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
          
          <!-- Filters -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
            </div>
            <div class="card-body">
              <form method="GET" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Tìm kiếm:</label>
                  <input type="text" name="search" class="form-control" placeholder="Tên, SĐT, địa chỉ, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-6">
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
                          <td><?php echo $guest['id']; ?></td>
                          <td>
                            <strong><?php echo htmlspecialchars($guest['name']); ?></strong><br>
                            <small class="text-muted">SĐT: <?php echo htmlspecialchars($guest['phone']); ?></small><br>
                            <?php if ($guest['email']): ?>
                              <small class="text-muted">Email: <?php echo htmlspecialchars($guest['email']); ?></small><br>
                            <?php endif; ?>
                            <?php if ($guest['address']): ?>
                              <small class="text-muted">Địa chỉ: <?php echo htmlspecialchars(substr($guest['address'], 0, 100)); ?><?php echo strlen($guest['address']) > 100 ? '...' : ''; ?></small><br>
                            <?php endif; ?>
                            <small class="text-muted">Session: <?php echo substr($guest['session_id'], 0, 20); ?>...</small>
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
                            $label = $guest['label'] ?? 'unlabeled';
                            $color = $labelColors[$label] ?? 'secondary';
                            $name = $labelNames[$label] ?? ucfirst($label);
                            ?>
                            <span class="badge badge-<?php echo $color; ?>"><?php echo $name; ?></span>
                          </td>
                          <td><?php echo $guest['total_messages'] ?? 0; ?></td>
                          <td>
                            <?php if ($guest['has_appointment']): ?>
                              <span class="badge badge-success">Có (<?php echo $guest['total_appointments'] ?? 0; ?>)</span>
                            <?php else: ?>
                              <span class="badge badge-secondary">Không</span>
                            <?php endif; ?>
                          </td>
                          <td><small><?php echo date('d/m/Y H:i', strtotime($guest['created_at'])); ?></small></td>
                          <td>
                            <a href="Admin_Guest_Chat_History.php?session_id=<?php echo urlencode($guest['session_id']); ?>" class="btn btn-sm btn-info">Xem chat</a>
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
</body>
</html>

