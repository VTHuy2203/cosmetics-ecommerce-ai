<?php
// Admin_Messages.php - Review and label customer messages
session_start();

// Simple admin check - enhance with proper authentication
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$is_admin) {
    header('Location: /TTTT/WebBanHangMyPham/src/View/Login.php');
    exit;
}

$DEFAULTS = [
    "DB_HOST" => "127.0.0.1",
    "DB_NAME" => "webmipham1",
    "DB_USER" => "root",
    "DB_PASS" => "",
    "DB_CHARSET" => "utf8mb4",
];

function pdo_connect($cfg) {
    $dsn = "mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset={$cfg['DB_CHARSET']}";
    return new PDO($dsn, $cfg['DB_USER'], $cfg['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

$pdo = pdo_connect($DEFAULTS);

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
    $admin_id = $_SESSION['IDNguoiDung'] ?? null;
    
    if (in_array($label, ['not_potential', 'needs_followup', 'potential'])) {
        // Get message info
        $stmt = $pdo->prepare("SELECT user_id, session_id FROM chat_messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $msg = $stmt->fetch();
        
        if ($msg) {
            // Insert or update label
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

// Get filter
$filter_label = $_GET['label'] ?? '';

// Get messages with labels
$sql = "
    SELECT 
        cm.id,
        cm.user_id,
        cm.session_id,
        cm.message,
        cm.direction,
        cm.created_at,
        kh.HoVaTen,
        kh.Email,
        kh.SDT,
        cml.label,
        cml.notes,
        cml.labeled_at,
        ca.id as has_appointment,
        ca.appt_at as appointment_time
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

// Get statistics
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
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tin nhắn khách hàng</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2f9e91;
            margin-bottom: 30px;
            border-bottom: 2px solid #2f9e91;
            padding-bottom: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #2f9e91 0%, #268077 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary {
            background: #2f9e91;
            color: white;
        }
        .btn-primary:hover {
            background: #268077;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-not-potential {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-needs-followup {
            background: #fff3cd;
            color: #856404;
        }
        .badge-potential {
            background: #d4edda;
            color: #155724;
        }
        .badge-unlabeled {
            background: #e2e3e5;
            color: #383d41;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #2f9e91;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .message-content {
            max-width: 400px;
            word-wrap: break-word;
        }
        .label-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .label-form select {
            padding: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .label-form input[type="text"] {
            padding: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            flex: 1;
        }
        .label-form button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #2f9e91;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 Quản lý tin nhắn khách hàng</h1>
        
        <div class="stats">
            <?php
            $total = 0;
            $unlabeled = 0;
            $not_potential = 0;
            $needs_followup = 0;
            $potential = 0;
            foreach ($stats as $stat) {
                $total += $stat['count'];
                if ($stat['label'] === 'unlabeled') $unlabeled = $stat['count'];
                if ($stat['label'] === 'not_potential') $not_potential = $stat['count'];
                if ($stat['label'] === 'needs_followup') $needs_followup = $stat['count'];
                if ($stat['label'] === 'potential') $potential = $stat['count'];
            }
            ?>
            <div class="stat-card">
                <h3><?php echo $total; ?></h3>
                <p>Tổng tin nhắn</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $unlabeled; ?></h3>
                <p>Chưa phân loại</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $not_potential; ?></h3>
                <p>Không tiềm năng</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $needs_followup; ?></h3>
                <p>Cần theo dõi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $potential; ?></h3>
                <p>Tiềm năng</p>
            </div>
        </div>
        
        <div class="filters">
            <label>Lọc theo nhãn:</label>
            <select onchange="window.location.href='?label='+this.value">
                <option value="">Tất cả</option>
                <option value="not_potential" <?php echo $filter_label === 'not_potential' ? 'selected' : ''; ?>>Không tiềm năng</option>
                <option value="needs_followup" <?php echo $filter_label === 'needs_followup' ? 'selected' : ''; ?>>Cần theo dõi</option>
                <option value="potential" <?php echo $filter_label === 'potential' ? 'selected' : ''; ?>>Tiềm năng</option>
            </select>
            <a href="Export_Messages_CSV.php<?php echo $filter_label ? '?label='.urlencode($filter_label) : ''; ?>" class="btn btn-success">Xuất CSV</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Khách hàng</th>
                    <th>Tin nhắn</th>
                    <th>Thời gian</th>
                    <th>Đã đặt lịch</th>
                    <th>Nhãn hiện tại</th>
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
                        <td class="message-content"><?php echo htmlspecialchars($msg['message']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                        <td>
                            <?php if ($msg['has_appointment']): ?>
                                <span class="badge badge-success">Có (<?php echo date('d/m/Y H:i', strtotime($msg['appointment_time'])); ?>)</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Chưa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $label_class = 'badge-unlabeled';
                            $label_text = 'Chưa phân loại';
                            if ($msg['label']) {
                                $label_class = 'badge-' . str_replace('_', '-', $msg['label']);
                                $label_map = [
                                    'not_potential' => 'Không tiềm năng',
                                    'needs_followup' => 'Cần theo dõi',
                                    'potential' => 'Tiềm năng'
                                ];
                                $label_text = $label_map[$msg['label']] ?? $msg['label'];
                            }
                            ?>
                            <span class="badge <?php echo $label_class; ?>"><?php echo $label_text; ?></span>
                        </td>
                        <td>
                            <form method="POST" class="label-form" style="margin: 0;">
                                <input type="hidden" name="action" value="label">
                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                <select name="label" required>
                                    <option value="">-- Chọn --</option>
                                    <option value="not_potential" <?php echo $msg['label'] === 'not_potential' ? 'selected' : ''; ?>>Không tiềm năng</option>
                                    <option value="needs_followup" <?php echo $msg['label'] === 'needs_followup' ? 'selected' : ''; ?>>Cần theo dõi</option>
                                    <option value="potential" <?php echo $msg['label'] === 'potential' ? 'selected' : ''; ?>>Tiềm năng</option>
                                </select>
                                <input type="text" name="notes" placeholder="Ghi chú..." value="<?php echo htmlspecialchars($msg['notes'] ?? ''); ?>">
                                <button type="submit">Lưu</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
