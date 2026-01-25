<?php
/**
 * Chat History Handler - Clean Rebuild
 * Truy xuất lịch sử trò chuyện cho người dùng hoặc session
 */

session_start();
include("../DB_Connect.php");

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webmipham1;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Chat_History.php: PDO connection error: " . $e->getMessage());
    die(json_encode(['ok' => false, 'error' => 'Database connection failed']));
}

header('Content-Type: application/json; charset=utf-8');

$session_id = $_GET['sid'] ?? session_id();
// Lấy user_id từ session - kiểm tra nhiều tên biến session
$user_id = 0;
if (isset($_SESSION['IDNguoiDung']) && $_SESSION['IDNguoiDung']) {
    $user_id = intval($_SESSION['IDNguoiDung']);
} elseif (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['user']) && $_SESSION['user']) {
    // Hãy thử lấy user_id từ cơ sở dữ liệu bằng tên người dùng.
    try {
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1");
        $stmt->execute([$_SESSION['user']]);
        $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_row) {
            $user_id = intval($user_row['ID_KhachHang']);
        }
    } catch (Exception $e) {
        error_log("Chat_History.php: Error getting user_id from username: " . $e->getMessage());
    }
}

// Ensure schema
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        session_id VARCHAR(128) NOT NULL,
        message TEXT NOT NULL,
        direction ENUM('user','bot') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    try {
        $pdo->exec("ALTER TABLE chat_messages MODIFY COLUMN session_id VARCHAR(128) NOT NULL");
    } catch (Exception $e) {
        // Cột có thể đã có kích thước chính xác.
    }
} catch (Exception $e) {
    error_log("Chat_History.php: Schema error: " . $e->getMessage());
}

// Nhận tin nhắn - chỉ sử dụng user_id khi đã đăng nhập (không phải session_id)
try {
    if ($user_id > 0) {
        //Khi đã đăng nhập, chỉ nhận tin nhắn theo user_id (không phải session_id)
        $stmt = $pdo->prepare("SELECT id, message, direction, created_at 
                              FROM chat_messages 
                              WHERE user_id = ? 
                              ORDER BY created_at ASC");
        $stmt->execute([$user_id]);
    } else {
        // Khi chưa đăng nhập, hãy sử dụng session_id.
        $stmt = $pdo->prepare("SELECT id, message, direction, created_at 
                              FROM chat_messages 
                              WHERE user_id IS NULL AND session_id = ? 
                              ORDER BY created_at ASC");
        $stmt->execute([$session_id]);
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'messages' => $messages
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Chat_History.php: Error getting messages: " . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => 'Error loading chat history',
        'messages' => []
    ], JSON_UNESCAPED_UNICODE);
}
