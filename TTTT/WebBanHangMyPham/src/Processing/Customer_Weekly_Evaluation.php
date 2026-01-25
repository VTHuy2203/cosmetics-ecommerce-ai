<?php
/**
 * Customer Weekly Evaluation System
 * 
 * Classification Logic:
 * - Spam: Only chatting without booking or purchasing
 * - Follow-up (Theo dõi): Made a booking appointment
 * - Potential (Tiềm năng): Purchased > 5,000,000 VND in 1 week
 * 
 * Discount Rules:
 * - New potential customer: 20% discount next week
 * - If potential customer doesn't meet requirement: 10% discount
 * - To maintain potential status: must buy > 1,000,000 VND/week
 * - If not met: back to normal customer
 */

header('Content-Type: application/json; charset=utf-8');

$DEFAULTS = [
    "DB_HOST"    => "127.0.0.1",
    "DB_NAME"    => "webmipham1",
    "DB_USER"    => "root",
    "DB_PASS"    => "",
    "DB_CHARSET" => "utf8mb4",
];

function pdo_connect($cfg) {
    $dsn = "mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset={$cfg['DB_CHARSET']}";
    return new PDO($dsn, $cfg['DB_USER'], $cfg['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

try {
    $pdo = pdo_connect($DEFAULTS);

    // Create customer_weekly_stats table
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_weekly_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        week_start DATE NOT NULL,
        week_end DATE NOT NULL,
        total_orders INT NOT NULL DEFAULT 0,
        total_amount BIGINT NOT NULL DEFAULT 0,
        has_booking TINYINT(1) NOT NULL DEFAULT 0,
        has_chat TINYINT(1) NOT NULL DEFAULT 0,
        customer_label ENUM('spam','follow-up','potential') NOT NULL DEFAULT 'spam',
        previous_label ENUM('spam','follow-up','potential') NULL,
        discount_percent INT NOT NULL DEFAULT 0,
        evaluated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_week (user_id, week_start),
        INDEX idx_user_id (user_id),
        INDEX idx_week (week_start),
        INDEX idx_label (customer_label)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add discount fields to khachhang table if not exists
    $pdo->exec("ALTER TABLE khachhang 
        ADD COLUMN IF NOT EXISTS customer_label ENUM('spam','follow-up','potential') DEFAULT 'spam',
        ADD COLUMN IF NOT EXISTS discount_percent INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS total_weekly_purchase BIGINT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS label_updated_at TIMESTAMP NULL");

    // Create customer notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Calculate current week boundaries
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
    $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));

    // Get all customers
    $customers = $pdo->query("SELECT ID_KhachHang, HoVaTen, customer_label, discount_percent 
                              FROM khachhang 
                              WHERE Role = 'customer' OR Role IS NULL")->fetchAll();

    $updated = 0;
    $notifications = [];

    foreach ($customers as $customer) {
        $userId = $customer['ID_KhachHang'];
        $currentLabel = $customer['customer_label'] ?? 'spam';
        $previousLabel = $currentLabel;

        // Calculate weekly purchase amount (completed orders only, TrangThai = 3)
        $orderStmt = $pdo->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(TongTien), 0) as total_amount
                                    FROM donhang 
                                    WHERE ID_KhachHang = ? 
                                    AND NgayTao >= ? AND NgayTao <= ?
                                    AND TrangThai = 3");
        $orderStmt->execute([$userId, $weekStart . ' 00:00:00', $weekEnd . ' 23:59:59']);
        $orderData = $orderStmt->fetch();
        
        $totalOrders = (int)$orderData['order_count'];
        $totalAmount = (int)$orderData['total_amount'];

        // Check for booking appointments
        $bookingStmt = $pdo->prepare("SELECT COUNT(*) as booking_count 
                                       FROM chat_appointments 
                                       WHERE user_id = ? 
                                       AND created_at >= ? AND created_at <= ?");
        $bookingStmt->execute([$userId, $weekStart . ' 00:00:00', $weekEnd . ' 23:59:59']);
        $hasBooking = (int)$bookingStmt->fetch()['booking_count'] > 0;

        // Check for chat activity
        $chatStmt = $pdo->prepare("SELECT COUNT(*) as chat_count 
                                    FROM chat_messages 
                                    WHERE user_id = ? 
                                    AND created_at >= ? AND created_at <= ?");
        $chatStmt->execute([$userId, $weekStart . ' 00:00:00', $weekEnd . ' 23:59:59']);
        $hasChat = (int)$chatStmt->fetch()['chat_count'] > 0;

        // Determine new label based on criteria
        $newLabel = 'spam';
        $discountPercent = 0;

        if ($totalAmount >= 5000000) {
            // Purchased > 5M VND in 1 week = Potential customer
            $newLabel = 'potential';
            if ($previousLabel !== 'potential') {
                // New potential customer - 20% discount
                $discountPercent = 20;
                $notifications[] = [
                    'user_id' => $userId,
                    'type' => 'potential_status',
                    'title' => '🌟 Chúc mừng! Bạn đã trở thành Khách hàng Tiềm năng!',
                    'message' => "Cảm ơn bạn đã mua sắm tại Mơ Shop! Với tổng đơn hàng " . 
                                number_format($totalAmount, 0, ',', '.') . " VND trong tuần này, " .
                                "bạn đã trở thành Khách hàng Tiềm năng và được giảm 20% cho tuần tiếp theo!"
                ];
            } else {
                // Already potential - maintain 20% discount if meeting requirement
                $discountPercent = 20;
            }
        } elseif ($previousLabel === 'potential') {
            // Was potential but didn't meet >5M requirement
            if ($totalAmount >= 1000000) {
                // Still buying >1M - maintain potential with 10% discount
                $newLabel = 'potential';
                $discountPercent = 10;
                $notifications[] = [
                    'user_id' => $userId,
                    'type' => 'potential_warning',
                    'title' => '⚠️ Giảm mức ưu đãi',
                    'message' => "Tuần này bạn đã mua " . number_format($totalAmount, 0, ',', '.') . " VND. " .
                                "Để duy trì ưu đãi 20%, bạn cần mua hàng trên 5.000.000 VND/tuần. " .
                                "Hiện tại bạn được giảm 10%."
                ];
            } else {
                // Not meeting 1M requirement - back to normal
                $newLabel = $hasBooking ? 'follow-up' : 'spam';
                $discountPercent = 0;
                $notifications[] = [
                    'user_id' => $userId,
                    'type' => 'potential_lost',
                    'title' => '📢 Thông báo về trạng thái khách hàng',
                    'message' => "Tuần này bạn chưa đạt yêu cầu mua hàng tối thiểu 1.000.000 VND để duy trì " .
                                "trạng thái Khách hàng Tiềm năng. Hãy mua sắm để nhận ưu đãi nhé!"
                ];
            }
        } elseif ($hasBooking) {
            // Has booking = Follow-up
            $newLabel = 'follow-up';
        } elseif ($hasChat && !$hasBooking && $totalOrders == 0) {
            // Only chat, no booking, no orders = Spam
            $newLabel = 'spam';
        }

        // Insert weekly stats
        $statsStmt = $pdo->prepare("INSERT INTO customer_weekly_stats 
            (user_id, week_start, week_end, total_orders, total_amount, has_booking, has_chat, 
             customer_label, previous_label, discount_percent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_orders = VALUES(total_orders),
                total_amount = VALUES(total_amount),
                has_booking = VALUES(has_booking),
                has_chat = VALUES(has_chat),
                customer_label = VALUES(customer_label),
                previous_label = VALUES(previous_label),
                discount_percent = VALUES(discount_percent),
                evaluated_at = NOW()");
        $statsStmt->execute([
            $userId, $weekStart, $weekEnd, $totalOrders, $totalAmount,
            $hasBooking ? 1 : 0, $hasChat ? 1 : 0, $newLabel, $previousLabel, $discountPercent
        ]);

        // Update customer record
        $updateStmt = $pdo->prepare("UPDATE khachhang SET 
            customer_label = ?,
            discount_percent = ?,
            total_weekly_purchase = ?,
            label_updated_at = NOW()
            WHERE ID_KhachHang = ?");
        $updateStmt->execute([$newLabel, $discountPercent, $totalAmount, $userId]);

        $updated++;
    }

    // Insert notifications
    foreach ($notifications as $notif) {
        $notifStmt = $pdo->prepare("INSERT INTO customer_notifications 
            (user_id, type, title, message) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([$notif['user_id'], $notif['type'], $notif['title'], $notif['message']]);
    }

    echo json_encode([
        "ok" => true,
        "updated" => $updated,
        "notifications_sent" => count($notifications),
        "week" => "$weekStart to $weekEnd"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}

