<?php
// Admin_Bookings.php - View consultation bookings
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/AdminManage/src/Layouts/Admin_Access_Check.php';

// Simple admin check - you should enhance this with proper authentication
$is_admin = in_array((string)($_SESSION['admin_role']??''), ['admin','manager'], true);

if (!$is_admin) {
    // Redirect to login or show error
    header('Location: /TTTT/WebBanHangMyPham/src/View/Login.php');
    exit;
}

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';
$pdo = app_pdo();

// Get bookings
$stmt = $pdo->query("
    SELECT 
        ca.id,
        ca.user_id,
        ca.session_id,
        ca.appt_at,
        ca.note,
        ca.message_content,
        ca.message_sent_at,
        ca.created_at,
        kh.HoVaTen,
        kh.Email,
        kh.SDT
    FROM chat_appointments ca
    LEFT JOIN khachhang kh ON ca.user_id = kh.ID_KhachHang
    ORDER BY ca.appt_at DESC, ca.created_at DESC
    LIMIT 500
");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đặt lịch tư vấn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-upcoming {
            background: #d4edda;
            color: #155724;
        }
        .badge-past {
            background: #f8d7da;
            color: #721c24;
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
        .message-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Quản lý đặt lịch tư vấn</h1>
        
        <?php
        $total = count($bookings);
        $upcoming = 0;
        $past = 0;
        $now = new DateTime();
        foreach ($bookings as $b) {
            $appt_time = new DateTime($b['appt_at']);
            if ($appt_time > $now) {
                $upcoming++;
            } else {
                $past++;
            }
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total; ?></h3>
                <p>Tổng số lịch đặt</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $upcoming; ?></h3>
                <p>Sắp tới</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $past; ?></h3>
                <p>Đã qua</p>
            </div>
        </div>
        
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <p>Chưa có lịch đặt tư vấn nào.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>Thời gian đặt</th>
                        <th>Nội dung tin nhắn</th>
                        <th>Thời gian gửi tin nhắn</th>
                        <th>Ghi chú</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <?php
                        $appt_time = new DateTime($b['appt_at']);
                        $is_upcoming = $appt_time > $now;
                        $customer_name = $b['HoVaTen'] ?: "Khách vãng lai (Session: " . substr($b['session_id'], 0, 8) . ")";
                        $customer_info = $b['Email'] ? "Email: {$b['Email']}<br>SDT: {$b['SDT']}" : "Chưa có thông tin";
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($customer_name); ?></strong><br>
                                <small style="color: #6c757d;"><?php echo $customer_info; ?></small>
                            </td>
                            <td><?php echo $appt_time->format('d/m/Y H:i'); ?></td>
                            <td class="message-content" title="<?php echo htmlspecialchars($b['message_content'] ?: 'N/A'); ?>">
                                <?php echo htmlspecialchars($b['message_content'] ?: 'N/A'); ?>
                            </td>
                            <td><?php echo $b['message_sent_at'] ? date('d/m/Y H:i', strtotime($b['message_sent_at'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($b['note'] ?: '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $is_upcoming ? 'badge-upcoming' : 'badge-past'; ?>">
                                    <?php echo $is_upcoming ? 'Sắp tới' : 'Đã qua'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="javascript:window.location.reload()" class="btn btn-primary">Làm mới</a>
            <a href="Export_Bookings_CSV.php" class="btn btn-success" style="margin-left: 10px;">Xuất CSV</a>
        </div>
    </div>
</body>
</html>
