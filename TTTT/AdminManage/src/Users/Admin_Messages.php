<?php
// Admin_Messages.php - Review and label customer messages
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Access control is handled by Admin_Access_Check.php (included via headHTML.php)
// Just read admin session info for display purposes
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

// Use PDO for chat tables (they use PDO, not mysqli)
function pdo_connect_chat() {
    $dsn = "mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4";
    return new PDO($dsn, "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

$pdo = pdo_connect_chat();

// Ensure schema
$pdo->exec("
    CREATE TABLE IF NOT EXISTS chat_message_labels(
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NULL,
        session_id VARCHAR(128) NOT NULL,
        label ENUM('not_potential', 'needs_followup', 'potential') NOT NULL,
        notes TEXT NULL,
        labeled_by INT NULL,
        labeled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_msg (message_id),
        INDEX idx_user (user_id),
        INDEX idx_session (session_id),
        INDEX idx_label (label),
        FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Handle label update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'label') {
    $message_id = (int)$_POST['message_id'];
    $label = $_POST['label'];
    $notes = $_POST['notes'] ?? '';
    $admin_id = $_SESSION['admin_id'] ?? null;
    
    if (in_array($label, ['not_potential', 'needs_followup', 'potential'])) {
        $stmt = $pdo->prepare("SELECT user_id, session_id FROM chat_messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $msg = $stmt->fetch();
        
        if ($msg) {
            $stmt = $pdo->prepare("
                INSERT INTO chat_message_labels (message_id, user_id, session_id, label, notes, labeled_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    label = VALUES(label),
                    notes = VALUES(notes),
                    labeled_by = VALUES(labeled_by),
                    labeled_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$message_id, $msg['user_id'], $msg['session_id'], $label, $notes, $admin_id]);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$filter_label = $_GET['label'] ?? '';

$sql = "
    SELECT 
        cm.id, cm.user_id, cm.session_id, cm.message, cm.direction, cm.created_at,
        kh.HoVaTen, kh.Email, kh.SDT,
        cml.label, cml.notes, cml.labeled_at,
        ca.id as has_appointment, ca.appt_at as appointment_time
    FROM chat_messages cm
    LEFT JOIN khachhang kh ON cm.user_id = kh.ID_KhachHang
    LEFT JOIN chat_message_labels cml ON cm.id = cml.message_id
    LEFT JOIN chat_appointments ca ON cm.session_id = ca.session_id AND cm.user_id = ca.user_id
    WHERE cm.direction = 'user'
";

$params = [];
if ($filter_label) {
    $sql .= " AND cml.label = ?";
    $params[] = $filter_label;
}
$sql .= " ORDER BY cm.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT 
        COALESCE(cml.label, 'unlabeled') as label,
        COUNT(*) as count
    FROM chat_messages cm
    LEFT JOIN chat_message_labels cml ON cm.id = cml.message_id
    WHERE cm.direction = 'user'
    GROUP BY label
")->fetchAll();
?>
<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>
        <div class="container-fluid">
          <h1 class="h3 mb-4 text-gray-800">💬 Quản lý tin nhắn khách hàng</h1>
          
          <div class="row mb-4">
            <?php
            $total = 0; $unlabeled = 0; $not_potential = 0; $needs_followup = 0; $potential = 0;
            foreach ($stats as $stat) {
                $total += $stat['count'];
                if ($stat['label'] === 'unlabeled') $unlabeled = $stat['count'];
                if ($stat['label'] === 'not_potential') $not_potential = $stat['count'];
                if ($stat['label'] === 'needs_followup') $needs_followup = $stat['count'];
                if ($stat['label'] === 'potential') $potential = $stat['count'];
            }
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng tin nhắn</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total; ?></div>
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
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chưa phân loại</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $unlabeled; ?></div>
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
                      <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Không tiềm năng</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $not_potential; ?></div>
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
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tiềm năng</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $potential; ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">Danh sách tin nhắn</h6>
              <div>
                <form method="GET" class="d-inline">
                  <select name="label" onchange="this.form.submit()" class="form-control form-control-sm d-inline-block" style="width: auto;">
                    <option value="">Tất cả</option>
                    <option value="not_potential" <?php echo $filter_label === 'not_potential' ? 'selected' : ''; ?>>Không tiềm năng</option>
                    <option value="needs_followup" <?php echo $filter_label === 'needs_followup' ? 'selected' : ''; ?>>Cần theo dõi</option>
                    <option value="potential" <?php echo $filter_label === 'potential' ? 'selected' : ''; ?>>Tiềm năng</option>
                  </select>
                </form>
                <a href="Export_Messages_CSV.php<?php echo $filter_label ? '?label='.urlencode($filter_label) : ''; ?>" class="btn btn-sm btn-success ml-2">Xuất CSV</a>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Khách hàng</th>
                      <th>Tin nhắn</th>
                      <th>Thời gian</th>
                      <th>Đã đặt lịch</th>
                      <th>Nhãn</th>
                      <th>Phân loại</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($messages as $msg): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($msg['id']); ?></td>
                        <td>
                          <?php if ($msg['HoVaTen']): ?>
                            <strong><?php echo htmlspecialchars($msg['HoVaTen']); ?></strong><br>
                            <small><?php echo htmlspecialchars($msg['Email'] ?: $msg['SDT'] ?: 'N/A'); ?></small>
                          <?php else: ?>
                            <small>Khách vãng lai<br><?php echo htmlspecialchars(substr($msg['session_id'], 0, 12)); ?></small>
                          <?php endif; ?>
                        </td>
                        <td style="max-width: 300px; word-wrap: break-word;"><?php echo htmlspecialchars($msg['message']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                        <td>
                          <?php if ($msg['has_appointment']): ?>
                            <span class="badge badge-success">Có</span>
                          <?php else: ?>
                            <span class="badge badge-secondary">Chưa</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php
                          $label_class = 'badge-secondary';
                          $label_text = 'Chưa phân loại';
                          if ($msg['label']) {
                            $label_map = ['not_potential' => ['Không tiềm năng', 'danger'], 'needs_followup' => ['Cần theo dõi', 'warning'], 'potential' => ['Tiềm năng', 'success']];
                            if (isset($label_map[$msg['label']])) {
                              $label_text = $label_map[$msg['label']][0];
                              $label_class = 'badge-' . $label_map[$msg['label']][1];
                            }
                          }
                          ?>
                          <span class="badge <?php echo $label_class; ?>"><?php echo $label_text; ?></span>
                        </td>
                        <td>
                          <form method="POST" class="form-inline">
                            <input type="hidden" name="action" value="label">
                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                            <select name="label" class="form-control form-control-sm mr-1" required>
                              <option value="">-- Chọn --</option>
                              <option value="not_potential" <?php echo $msg['label'] === 'not_potential' ? 'selected' : ''; ?>>Không tiềm năng</option>
                              <option value="needs_followup" <?php echo $msg['label'] === 'needs_followup' ? 'selected' : ''; ?>>Cần theo dõi</option>
                              <option value="potential" <?php echo $msg['label'] === 'potential' ? 'selected' : ''; ?>>Tiềm năng</option>
                            </select>
                            <input type="text" name="notes" placeholder="Ghi chú" value="<?php echo htmlspecialchars($msg['notes'] ?? ''); ?>" class="form-control form-control-sm mr-1" style="width: 100px;">
                            <button type="submit" class="btn btn-sm btn-primary">Lưu</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include("../Layouts/Link.php"); ?>
    </div>
  </div>
</body>
</html>
