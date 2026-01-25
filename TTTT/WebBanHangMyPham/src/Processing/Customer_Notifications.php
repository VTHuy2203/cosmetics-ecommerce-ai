<?php
/**
 * Customer Notifications API
 * 
 * Endpoints:
 * - GET: Fetch notifications for a user
 * - POST: Mark notification as read
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Get notifications for user
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
        
        if ($userId <= 0) {
            echo json_encode(["ok" => false, "error" => "Invalid user_id"]);
            exit;
        }

        $sql = "SELECT id, type, title, message, is_read, created_at 
                FROM customer_notifications 
                WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();

        // Get unread count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_notifications 
                                    WHERE user_id = ? AND is_read = 0");
        $countStmt->execute([$userId]);
        $unreadCount = (int)$countStmt->fetch()['count'];

        // Get customer status info
        $statusStmt = $pdo->prepare("SELECT customer_label, discount_percent, total_weekly_purchase 
                                     FROM khachhang WHERE ID_KhachHang = ?");
        $statusStmt->execute([$userId]);
        $status = $statusStmt->fetch();

        echo json_encode([
            "ok" => true,
            "notifications" => $notifications,
            "unread_count" => $unreadCount,
            "customer_status" => $status ?: null
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'POST') {
        // Mark notification as read
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationId = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;
        $markAllRead = isset($input['mark_all_read']) && $input['mark_all_read'];
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

        if ($markAllRead && $userId > 0) {
            // Mark all as read for user
            $stmt = $pdo->prepare("UPDATE customer_notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            echo json_encode(["ok" => true, "marked" => $stmt->rowCount()]);
        } elseif ($notificationId > 0) {
            // Mark single notification as read
            $stmt = $pdo->prepare("UPDATE customer_notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$notificationId]);
            echo json_encode(["ok" => true, "marked" => $stmt->rowCount()]);
        } else {
            echo json_encode(["ok" => false, "error" => "Invalid parameters"]);
        }
    } else {
        echo json_encode(["ok" => false, "error" => "Method not allowed"]);
    }

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}

