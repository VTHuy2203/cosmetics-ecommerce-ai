<?php
/**
 * Customer Weekly Statistics View
 * Shows weekly customer evaluation with purchase amounts and labels
 */
require_once dirname(__DIR__, 3) . '/config/security.php';

$role = (string) ($_SESSION['admin_role'] ?? $_SESSION['user_role'] ?? '');
$hasBackOfficeSession = isset($_SESSION['admin_id'])
    || (isset($_SESSION['IDNguoiDung'], $_SESSION['user']) && $role === 'employee');
if (!$hasBackOfficeSession || !in_array($role, ['admin', 'manager', 'employee'], true)) {
    header('Location: ' . (isset($_SESSION['admin_role'])
        ? '/TTTT/AdminManage/src/View/Admin_Login.php'
        : 'Login.php'));
    exit;
}

$pdo = app_pdo();

// Get filter parameters
$weekFilter = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
$labelFilter = $_GET['label'] ?? '';

// Build query
$sql = "SELECT 
    cws.*, 
    k.HoVaTen, k.Email, k.SDT,
    CASE 
        WHEN cws.customer_label = 'potential' THEN '⭐ Tiềm năng'
        WHEN cws.customer_label = 'follow-up' THEN '📋 Theo dõi'
        WHEN cws.customer_label = 'normal' THEN 'Bình thường'
        ELSE 'Spam'
    END as label_display
FROM customer_weekly_stats cws
JOIN khachhang k ON cws.user_id = k.ID_KhachHang
WHERE cws.week_start = ?";

$params = [$weekFilter];

if ($labelFilter) {
    $sql .= " AND cws.customer_label = ?";
    $params[] = $labelFilter;
}

$sql .= " ORDER BY cws.total_amount DESC, cws.customer_label ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats = $stmt->fetchAll();

// Get summary counts
$summaryStmt = $pdo->prepare("SELECT 
    customer_label, 
    COUNT(*) as count, 
    SUM(total_amount) as total_revenue
FROM customer_weekly_stats 
WHERE week_start = ?
GROUP BY customer_label");
$summaryStmt->execute([$weekFilter]);
$summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Get available weeks
$weeksStmt = $pdo->query("SELECT DISTINCT week_start FROM customer_weekly_stats ORDER BY week_start DESC LIMIT 12");
$availableWeeks = $weeksStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê Khách hàng Tuần - Mơ Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .label-potential { background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%); color: #333; }
        .label-follow-up { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .label-normal { background: #6c757d; color: #fff; }
        .label-spam { background: #dc3545; color: #fff; }
        .star-icon { color: #ffd700; font-size: 1.2em; }
        .summary-card { border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .summary-potential { background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%); border: 2px solid #ffd700; }
        .summary-follow-up { background: linear-gradient(135deg, #e8e4f8 0%, #d4c8f0 100%); border: 2px solid #764ba2; }
        .summary-normal { background: #f1f3f5; border: 2px solid #6c757d; }
        .summary-spam { background: #f8d7da; border: 2px solid #dc3545; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4">📊 Thống kê Khách hàng Tuần</h2>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-4">
            <label class="form-label">Tuần:</label>
            <select class="form-select" id="weekFilter" onchange="applyFilters()">
                <?php foreach ($availableWeeks as $week): ?>
                <option value="<?= $week ?>" <?= $week === $weekFilter ? 'selected' : '' ?>>
                    <?= date('d/m/Y', strtotime($week)) ?> - <?= date('d/m/Y', strtotime($week . ' +6 days')) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Phân loại:</label>
            <select class="form-select" id="labelFilter" onchange="applyFilters()">
                <option value="">Tất cả</option>
                <option value="normal" <?= $labelFilter === 'normal' ? 'selected' : '' ?>>Bình thường</option>
                <option value="potential" <?= $labelFilter === 'potential' ? 'selected' : '' ?>>⭐ Tiềm năng</option>
                <option value="follow-up" <?= $labelFilter === 'follow-up' ? 'selected' : '' ?>>📋 Theo dõi</option>
                <option value="spam" <?= $labelFilter === 'spam' ? 'selected' : '' ?>>❌ Spam</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-success" onclick="runEvaluation()">🔄 Chạy đánh giá tuần</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php 
        $summaryMap = array_column($summary, null, 'customer_label');
        $labels = ['normal' => ['Bình thường', 'summary-normal'],
                   'potential' => ['⭐ Tiềm năng', 'summary-potential'], 
                   'follow-up' => ['📋 Theo dõi', 'summary-follow-up'], 
                   'spam' => ['❌ Spam', 'summary-spam']];
        foreach ($labels as $key => $info): 
            $data = $summaryMap[$key] ?? ['count' => 0, 'total_revenue' => 0];
        ?>
        <div class="col-md-3">
            <div class="summary-card <?= $info[1] ?>">
                <h5><?= $info[0] ?></h5>
                <div class="d-flex justify-content-between">
                    <span>Số lượng: <strong><?= $data['count'] ?></strong></span>
                    <span>Doanh thu: <strong><?= number_format($data['total_revenue'], 0, ',', '.') ?> VND</strong></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Đơn hàng</th>
                    <th>Tổng mua</th>
                    <th>Đặt lịch</th>
                    <th>Chat</th>
                    <th>Phân loại</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['customer_label'] === 'potential'): ?>
                        <span class="star-icon">⭐</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($row['HoVaTen']) ?>
                    </td>
                    <td>
                        <small><?= htmlspecialchars($row['Email']) ?></small><br>
                        <small><?= htmlspecialchars($row['SDT']) ?></small>
                    </td>
                    <td><?= $row['total_orders'] ?></td>
                    <td><strong><?= number_format($row['total_amount'], 0, ',', '.') ?> VND</strong></td>
                    <td><?= $row['has_booking'] ? '✅' : '❌' ?></td>
                    <td><?= $row['has_chat'] ? '✅' : '❌' ?></td>
                    <td><span class="badge label-<?= $row['customer_label'] ?>"><?= $row['label_display'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyFilters() {
    const week = document.getElementById('weekFilter').value;
    const label = document.getElementById('labelFilter').value;
    window.location.href = `?week=${week}&label=${label}`;
}

function runEvaluation() {
    if (confirm('Chạy đánh giá khách hàng tuần này?')) {
        fetch('../Processing/Customer_Weekly_Evaluation.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': <?php echo json_encode(csrf_token()); ?>
            }
        })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    alert(`Đã đánh giá ${data.updated} khách hàng, gửi ${data.notifications_sent} thông báo.`);
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.error);
                }
            });
    }
}
</script>
</body>
</html>
