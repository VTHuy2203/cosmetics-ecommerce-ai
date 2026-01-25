<?php
/**
 * Chat Send Handler - Clean Rebuild
 * Handles chat messages, product recommendations, and appointment scheduling
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
    error_log("Chat_Send.php: PDO connection error: " . $e->getMessage());
    die(json_encode(['ok' => false, 'error' => 'Database connection failed']));
}

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$session_id = $input['session_id'] ?? session_id();
// Get user_id from session - check multiple session variable names
$user_id = 0;
if (isset($_SESSION['IDNguoiDung']) && $_SESSION['IDNguoiDung']) {
    $user_id = intval($_SESSION['IDNguoiDung']);
} elseif (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['user']) && $_SESSION['user']) {
    // Try to get user_id from database using username
    try {
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1");
        $stmt->execute([$_SESSION['user']]);
        $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_row) {
            $user_id = intval($user_row['ID_KhachHang']);
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error getting user_id from username: " . $e->getMessage());
    }
}

// Get user info if logged in
$user_name = "Bạn";
if ($user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT HoVaTen, TenDangNhap FROM khachhang WHERE ID_KhachHang = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user_name = $user['HoVaTen'] ?: ($user['TenDangNhap'] ?: "Bạn");
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error getting user name: " . $e->getMessage());
    }
}

// Ensure database schema
function ensureSchema($pdo) {
    try {
        // Ensure chat_messages table
        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NULL,
      session_id VARCHAR(128) NOT NULL,
            message TEXT NOT NULL,
            direction ENUM('user','bot') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Ensure chat_appointments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_appointments (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NULL,
      session_id VARCHAR(128) NOT NULL,
            appt_at DATETIME NOT NULL,
            note TEXT NULL,
            message_content TEXT NULL,
            message_sent_at TIMESTAMP NULL,
            guest_name VARCHAR(255) NULL,
            guest_phone VARCHAR(20) NULL,
            guest_address TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_appt_at (appt_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Ensure guest_customers table for non-logged-in customers
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
        
        // Ensure customer_analysis table
        $pdo->exec("CREATE TABLE IF NOT EXISTS customer_analysis (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NULL,
      session_id VARCHAR(128) NOT NULL,
            label ENUM('spam','follow-up','potential') NOT NULL DEFAULT 'spam',
            product_interest TEXT NULL,
            last_need TEXT NULL,
            message_count INT NOT NULL DEFAULT 0,
            has_appointment TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_label (label)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Try to add unique key if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE customer_analysis ADD UNIQUE KEY unique_user_session (user_id, session_id)");
        } catch (Exception $e) {
            // Key might already exist, ignore error
        }
        
        // Modify session_id column if needed
        try {
            $pdo->exec("ALTER TABLE chat_messages MODIFY COLUMN session_id VARCHAR(128) NOT NULL");
        } catch (Exception $e) {
            // Column might already be correct size
        }
        
        try {
            $pdo->exec("ALTER TABLE chat_appointments MODIFY COLUMN session_id VARCHAR(128) NOT NULL");
        } catch (Exception $e) {
            // Column might already be correct size
        }

        // Add missing columns for guest info if they don't exist
        try {
            $pdo->exec("ALTER TABLE chat_appointments ADD COLUMN message_content TEXT NULL AFTER note");
        } catch (Exception $e) {
            // Column might already exist
        }

        try {
            $pdo->exec("ALTER TABLE chat_appointments ADD COLUMN message_sent_at TIMESTAMP NULL AFTER message_content");
        } catch (Exception $e) {
            // Column might already exist
        }

        try {
            $pdo->exec("ALTER TABLE chat_appointments ADD COLUMN guest_name VARCHAR(255) NULL AFTER message_sent_at");
        } catch (Exception $e) {
            // Column might already exist
        }

        try {
            $pdo->exec("ALTER TABLE chat_appointments ADD COLUMN guest_phone VARCHAR(20) NULL AFTER guest_name");
        } catch (Exception $e) {
            // Column might already exist
        }

        try {
            $pdo->exec("ALTER TABLE chat_appointments ADD COLUMN guest_address TEXT NULL AFTER guest_phone");
        } catch (Exception $e) {
            // Column might already exist
        }

    } catch (Exception $e) {
        error_log("Chat_Send.php: Schema error: " . $e->getMessage());
    }
}

ensureSchema($pdo);

// Helper functions
function detectLanguage($text) {
    $vietnamese = ['xin chào', 'chào', 'tôi', 'muốn', 'mua', 'sản phẩm', 'giá', 'đặt lịch', 'tư vấn'];
    $text_lower = mb_strtolower($text, 'UTF-8');
    foreach ($vietnamese as $word) {
        if (mb_strpos($text_lower, $word, 0, 'UTF-8') !== false) {
            return 'vi';
        }
    }
    return 'en';
}

function hasBookingIntent($text) {
    $booking_keywords = [
        'đặt lịch', 'dat lich', 'đặt hẹn', 'dat hen',
        'appointment', 'book', 'schedule', 'hẹn', 'hen'
    ];
    $text_lower = mb_strtolower($text, 'UTF-8');
    foreach ($booking_keywords as $keyword) {
        if (mb_strpos($text_lower, $keyword, 0, 'UTF-8') !== false) {
      return true;
    }
  }
  return false;
}

function isBookingYes($text) {
    $yes_keywords = ['có', 'co', 'yes', 'ok', 'okay', 'đồng ý', 'dong y', 'muốn', 'muon', 'want', 'được', 'duoc'];
    $text_lower = mb_strtolower($text, 'UTF-8');
    foreach ($yes_keywords as $keyword) {
        if (mb_strpos($text_lower, $keyword, 0, 'UTF-8') !== false) {
        return true;
    }
  }
  return false;
}

function isBookingNo($text) {
    $no_keywords = ['không', 'khong', 'no', 'không cần', 'khong can', 'không muốn', 'khong muon', 'không muốn', 'khong muon', 'không cần', 'khong can', 'không', 'khong'];
    $text_lower = mb_strtolower($text, 'UTF-8');
    foreach ($no_keywords as $keyword) {
        if (mb_strpos($text_lower, $keyword, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}

function isProductSearch($text) {
    // More comprehensive product search detection (Renamed concept: shouldCallRAG)
    $product_keywords = [
        'mua', 'tìm', 'tim', 'bán', 'ban', 'giá', 'gia', 'sản phẩm', 'san pham', 'product',
        'son', 'kem', 'serum', 'mặt nạ', 'mat na', 'makeup', 'mỹ phẩm', 'my pham', 'cosmetic',
        'sữa rửa mặt', 'sua rua mat', 'nước tẩy trang', 'nuoc tay trang', 'tẩy trang', 'tay trang',
        'chống nắng', 'chong nang', 'dưỡng', 'duong', 'dưỡng ẩm', 'duong am', 'moisturizer',
        'toner', 'essence', 'cleanser', 'mask', 'cream', 'lotion', 'gel', 'spray',
        'trắng da', 'trang da', 'whitening', 'trị mụn', 'tri mun', 'acne', 'chống lão hóa', 'chong lao hoa',
        // RAG keywords (Shop info, Policy, Comparison, FAQs)
        'so sánh', 'so sanh', 'compare', 'khác nhau', 'giong nhau',
        'chính sách', 'chinh sach', 'policy', 'đổi trả', 'doi tra', 'return',
        'địa chỉ', 'dia chi', 'address', 'cửa hàng', 'cua hang', 'shop',
        'liên hệ', 'lien he', 'contact', 'nhân viên', 'nhan vien', 'staff',
        'bảo hành', 'bao hanh', 'freeship', 'ship', 'giao hàng',
        'tư vấn', 'tu van', 'da dầu', 'da khô', 'da nhạy cảm', 'da hỗn hợp', 'loại da'
    ];
    $text_lower = mb_strtolower($text, 'UTF-8');
    foreach ($product_keywords as $keyword) {
        if (mb_strpos($text_lower, $keyword, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}

function parseDateTime($text) {
    // Log the incoming text for debugging
    error_log("parseDateTime input: " . $text);

    // Extract only the datetime part (before any pipe | character for guest info)
    $datetime_part = $text;
    if (strpos($text, '|') !== false) {
        $datetime_part = trim(explode('|', $text)[0]);
    }
    error_log("parseDateTime datetime_part: " . $datetime_part);

    // Try to parse Vietnamese datetime format: DD/MM/YYYY HH:MM
    if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\s+(\d{1,2})[:h](\d{2})/i', $datetime_part, $matches)) {
        $day = intval($matches[1]);
        $month = intval($matches[2]);
        $year = intval($matches[3]);
        if ($year < 100) $year += 2000;
        $hour = intval($matches[4]);
        $minute = intval($matches[5]);

        error_log("parseDateTime parsed: day=$day, month=$month, year=$year, hour=$hour, minute=$minute");

        if (checkdate($month, $day, $year) && $hour >= 0 && $hour < 24 && $minute >= 0 && $minute < 60) {
            $result = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
            error_log("parseDateTime result: " . $result);
            return $result;
        } else {
            error_log("parseDateTime: checkdate failed or invalid time");
        }
    } else {
        error_log("parseDateTime: regex did not match");
    }

    return null;
}

function callRAGAPI($message, $user_id, $session_id) {
    $api_url = "http://127.0.0.1:8010/chat";
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'message' => $message,
        'user_id' => $user_id ?: null,
        'session_id' => $session_id
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['reply'])) {
            return $data;
        }
    }
    
    return null;
}

// Save user message - prioritize user_id when logged in
try {
    // When user is logged in, use user_id; otherwise use session_id
    if ($user_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, session_id, message, direction) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$user_id, $session_id, $message]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, session_id, message, direction) VALUES (?, ?, ?, 'user')");
        $stmt->execute([null, $session_id, $message]);
    }
    
    // Send WebSocket notification to Admin for new chat message (only if user is logged in)
    if ($user_id > 0) {
        try {
            $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
            if (file_exists($wsPushFile)) {
                require_once($wsPushFile);
                if (function_exists('websocket_push_chat_message')) {
                    websocket_push_chat_message($user_id, $user_name, $message);
                }
            }
        } catch (Exception $e) {
            error_log("Chat_Send.php: Error sending WebSocket notification: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log("Chat_Send.php: Error saving user message: " . $e->getMessage());
}

// Process message
$lang = detectLanguage($message);
$reply = "";
$has_booking_form = false;

// Check for datetime FIRST (if user is submitting booking form)
$parsed_dt = parseDateTime($message);

// Check if user is responding to consultation question (only if not submitting datetime)
$is_booking_yes = !$parsed_dt ? isBookingYes($message) : false;
$is_booking_no = !$parsed_dt ? isBookingNo($message) : false;

// Check for booking intent (only if not submitting datetime)
$booking_intent = !$parsed_dt ? hasBookingIntent($message) : false;

// Check if products have already been suggested (to avoid suggesting multiple times)
$products_already_suggested = false;
try {
    if ($user_id > 0) {
        $check_stmt = $pdo->prepare("SELECT id FROM chat_messages WHERE user_id = ? AND direction = 'bot' AND (message LIKE '%Bạn có muốn đặt lịch tư vấn%' OR message LIKE '%Would you like to schedule%') ORDER BY created_at DESC LIMIT 1");
        $check_stmt->execute([$user_id]);
    } else {
        $check_stmt = $pdo->prepare("SELECT id FROM chat_messages WHERE session_id = ? AND direction = 'bot' AND (message LIKE '%Bạn có muốn đặt lịch tư vấn%' OR message LIKE '%Would you like to schedule%') ORDER BY created_at DESC LIMIT 1");
        $check_stmt->execute([$session_id]);
    }
    $products_already_suggested = $check_stmt->fetch() !== false;
} catch (Exception $e) {
    error_log("Chat_Send.php: Error checking if products already suggested: " . $e->getMessage());
}

// THỨ TỰ ƯU TIÊN:
// 1. Thời gian gửi thông tin (biểu mẫu đặt lịch đã được gửi) - ƯU TIÊN CAO NHẤT
// 2. Tìm kiếm sản phẩm (đề xuất sản phẩm trước) - ƯU TIÊN THỨ HAI (PHẢI được thực hiện trước ý định đặt lịch)
// 3. Phản hồi Có/Không cho tư vấn
// 4. Ý định đặt lịch (chỉ khi KHÔNG phải là tìm kiếm sản phẩm)
// 5. Lời chào chung

// Handle datetime submission FIRST (booking form submitted)
if ($parsed_dt) {
    // User provided datetime - save appointment (booking form submitted)
    // Parse guest info from message if not logged in (format: "DD/MM/YYYY HH:MM | Tên: ... | SĐT: ... | Địa chỉ: ...")
    $guest_name = null;
    $guest_phone = null;
    $guest_address = null;
    
    if ($user_id == 0 && preg_match('/\|\s*Tên:\s*([^|]+)\s*\|\s*SĐT:\s*([^|]+)\s*\|\s*Địa chỉ:\s*(.+)/i', $message, $matches)) {
        $guest_name = trim($matches[1]);
        $guest_phone = trim($matches[2]);
        $guest_address = trim($matches[3]);
        
        // Save guest customer info
        try {
            $guest_stmt = $pdo->prepare("INSERT INTO guest_customers (session_id, name, phone, address) 
                                        VALUES (?, ?, ?, ?)
                                        ON DUPLICATE KEY UPDATE
                                        name = VALUES(name),
                                        phone = VALUES(phone),
                                        address = VALUES(address),
                                        updated_at = NOW()");
            $guest_stmt->execute([$session_id, $guest_name, $guest_phone, $guest_address]);
        } catch (Exception $e) {
            // Try without ON DUPLICATE KEY if unique key doesn't exist
            try {
                $check_guest = $pdo->prepare("SELECT id FROM guest_customers WHERE session_id = ? LIMIT 1");
                $check_guest->execute([$session_id]);
                $exists_guest = $check_guest->fetch();
                
                if ($exists_guest) {
                    $guest_stmt = $pdo->prepare("UPDATE guest_customers SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
                    $guest_stmt->execute([$guest_name, $guest_phone, $guest_address, $exists_guest['id']]);
                } else {
                    $guest_stmt = $pdo->prepare("INSERT INTO guest_customers (session_id, name, phone, address) VALUES (?, ?, ?, ?)");
                    $guest_stmt->execute([$session_id, $guest_name, $guest_phone, $guest_address]);
                }
            } catch (Exception $e2) {
                error_log("Chat_Send.php: Error saving guest customer: " . $e2->getMessage());
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_appointments (user_id, session_id, appt_at, message_content, message_sent_at, guest_name, guest_phone, guest_address) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $stmt->execute([$user_id ?: null, $session_id, $parsed_dt, $message, $guest_name, $guest_phone, $guest_address]);
        
        // Send thank you message after booking
        if ($lang === 'vi') {
            $reply = "Cảm ơn bạn rất nhiều! Mình đã ghi nhận lịch hẹn tư vấn của bạn vào " . date('d/m/Y H:i', strtotime($parsed_dt)) . ". " .
                     "Nhân viên Mơ Shop sẽ liên hệ với bạn sớm nhất có thể. Chúc bạn một ngày tốt lành!";
        } else {
            $reply = "Thank you very much! I have recorded your consultation appointment for " . date('d/m/Y H:i', strtotime($parsed_dt)) . ". " .
                     "Mơ Shop staff will contact you as soon as possible. Have a great day!";
        }
        
        // Update customer analysis
        try {
            // First try with unique key
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, has_appointment, message_count) 
                                   VALUES (?, ?, 'potential', 1, 1)
                                   ON DUPLICATE KEY UPDATE
                                   label = 'potential', 
                                   has_appointment = 1, 
                                   message_count = message_count + 1,
                                   updated_at = NOW()");
            $stmt->execute([$user_id ?: null, $session_id]);
        } catch (Exception $e) {
            // Nếu khóa duy nhất không tồn tại, hãy thử cập nhật hoặc chèn.
            try {
                $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
                $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
                $exists = $check->fetch();
                
                if ($exists) {
                    $stmt = $pdo->prepare("UPDATE customer_analysis SET label = 'potential', has_appointment = 1, message_count = message_count + 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$exists['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, has_appointment, message_count) VALUES (?, ?, 'potential', 1, 1)");
                    $stmt->execute([$user_id ?: null, $session_id]);
                }
            } catch (Exception $e2) {
                error_log("Chat_Send.php: Error updating customer analysis: " . $e2->getMessage());
            }
        }
        
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error saving appointment: " . $e->getMessage());
        error_log("Chat_Send.php: user_id=$user_id, session_id=$session_id, parsed_dt=$parsed_dt");
        error_log("Chat_Send.php: guest_name=$guest_name, guest_phone=$guest_phone, guest_address=$guest_address");
        $reply = "Xin lỗi, có lỗi xảy ra khi lưu lịch hẹn: " . $e->getMessage() . ". Vui lòng thử lại sau.";
    }
    
} elseif (isProductSearch($message) && !$parsed_dt) {
    // Product search - ALWAYS call RAG API to recommend products FIRST
    // This MUST come before booking intent check to prioritize product recommendations
    $rag_response = callRAGAPI($message, $user_id, $session_id);
    
    if ($rag_response && isset($rag_response['reply'])) {
        $reply = $rag_response['reply'];
        
        // Check if reply contains products (has prices or product names)
        $has_products = preg_match('/\d+[,.]?\d*\s*VND|\d+[,.]?\d*\s*đồng|Thương hiệu|Giá|sản phẩm|product|VND|đồng/iu', $reply) || 
                       (isset($rag_response['hits']) && count($rag_response['hits']) > 0);
        
        if ($has_products && !$products_already_suggested) {
            // Ask about consultation AFTER recommending products (ONLY ONCE)
            if ($lang === 'vi') {
                $reply .= "\n\nBạn có muốn đặt lịch tư vấn với nhân viên Mơ Shop không?";
            } else {
                $reply .= "\n\nWould you like to schedule a consultation with Mơ Shop staff?";
            }
        }
        
        // Cập nhật phân tích khách hàng
        try {
            $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
            $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
            $exists = $check->fetch();
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE customer_analysis SET 
                                       label = IF(label = 'potential' OR has_appointment = 1, 'potential', 'follow-up'),
                                       product_interest = ?,
                                       last_need = ?,
                                       message_count = message_count + 1,
                                       updated_at = NOW()
                                       WHERE id = ?");
                $stmt->execute([$message, $message, $exists['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, product_interest, last_need, message_count) 
                                       VALUES (?, ?, 'follow-up', ?, ?, 1)");
                $stmt->execute([$user_id ?: null, $session_id, $message, $message]);
            }
        } catch (Exception $e) {
            error_log("Chat_Send.php: Error updating customer analysis: " . $e->getMessage());
        }
    } else {
        $reply = "Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.";
    }
    
} elseif ($is_booking_yes && !$parsed_dt) {
    // User wants to book - show form immediately
    $reply = "Cảm ơn bạn! Bạn muốn đặt lịch tư vấn vào thời gian nào ạ?\n\n" .
             "Bạn hãy chọn thời gian ở **biểu mẫu dưới đây** (Ngày/Tháng/Năm và Giờ/Phút) hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n\n" .
             "[[BOOKING_FORM]]";
    $has_booking_form = true;
    
    // Cập nhật phân tích khách hàng - phát hiện ý định đặt hàng (chưa phải là khách hàng tiềm năng, nhưng đang thể hiện sự quan tâm)
    try {
        $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
        $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
        $exists = $check->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE customer_analysis SET 
                                   last_need = ?,
                                   message_count = message_count + 1,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$message, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, last_need, message_count) 
                                   VALUES (?, ?, 'follow-up', ?, 1)");
            $stmt->execute([$user_id ?: null, $session_id, $message]);
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error updating customer analysis for booking intent: " . $e->getMessage());
    }
    
} elseif ($is_booking_no) {
    // Người dùng không muốn lên lịch - chỉ cần nói lời cảm ơn.
    if ($lang === 'vi') {
        $reply = "Cảm ơn bạn! Nếu bạn có bất kỳ câu hỏi nào khác, đừng ngần ngại liên hệ với chúng tôi nhé!";
    } else {
        $reply = "Thank you! If you have any other questions, please don't hesitate to contact us!";
    }
    
    // Cập nhật phân tích khách hàng - theo dõi (đã hỏi về sản phẩm nhưng chưa hẹn gặp)
    try {
        $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
        $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
        $exists = $check->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE customer_analysis SET 
                                   label = IF(label = 'potential' OR has_appointment = 1, 'potential', 'follow-up'),
                                   last_need = ?,
                                   message_count = message_count + 1,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$message, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, last_need, message_count) 
                                   VALUES (?, ?, 'follow-up', ?, 1)");
            $stmt->execute([$user_id ?: null, $session_id, $message]);
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error updating customer analysis for booking no: " . $e->getMessage());
    }
    
} elseif ($booking_intent && !$parsed_dt) {
    // Người dùng muốn đặt chỗ - hiển thị biểu mẫu ngay lập tức
    $reply = "Cảm ơn bạn! Bạn muốn đặt lịch tư vấn vào thời gian nào ạ?\n\n" .
             "Bạn hãy chọn thời gian ở **biểu mẫu dưới đây** (Ngày/Tháng/Năm và Giờ/Phút) hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n\n" .
             "[[BOOKING_FORM]]";
    $has_booking_form = true;
    
    // Cập nhật phân tích khách hàng - phát hiện ý định đặt phòng (chưa phải là khách hàng tiềm năng, nhưng đang thể hiện sự quan tâm)
    try {
        $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
        $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
        $exists = $check->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE customer_analysis SET 
                                   last_need = ?,
                                   message_count = message_count + 1,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$message, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, last_need, message_count) 
                                   VALUES (?, ?, 'follow-up', ?, 1)");
            $stmt->execute([$user_id ?: null, $session_id, $message]);
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error updating customer analysis for booking intent: " . $e->getMessage());
    }
    
} else {
    // Lời chào chung hoặc tin nhắn khác
// Sử dụng "Xin chào" nếu người dùng chưa đăng nhập, nếu không thì sử dụng tên người dùng
    $greeting_name = ($user_id > 0 && $user_name !== "Bạn") ? $user_name : ($lang === 'vi' ? "bạn" : "there");
    
    if ($lang === 'vi') {
        if ($user_id > 0 && $user_name !== "Bạn") {
            $reply = "Xin chào {$user_name}! Tôi rất vui được giúp bạn. Bạn có câu hỏi gì cần tư vấn không ạ?";
        } else {
            $reply = "Xin chào bạn! Tôi rất vui được giúp bạn. Bạn có câu hỏi gì cần tư vấn không ạ?";
        }
    } else {
        if ($user_id > 0 && $user_name !== "Bạn") {
            $reply = "Hello {$user_name}! I'm happy to help you. Do you have any questions?";
        } else {
            $reply = "Hello there! I'm happy to help you. Do you have any questions?";
        }
    }
    
    // Cập nhật phân tích khách hàng (gửi thư rác nếu không có tìm kiếm sản phẩm và không có đặt chỗ)
    try {
        $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
        $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
        $exists = $check->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE customer_analysis SET 
                                   label = IF(label = 'potential' OR has_appointment = 1 OR product_interest IS NOT NULL, label, 'spam'),
                                   last_need = ?,
                                   message_count = message_count + 1,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$message, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, label, last_need, message_count) 
                                   VALUES (?, ?, 'spam', ?, 1)");
            $stmt->execute([$user_id ?: null, $session_id, $message]);
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error updating customer analysis: " . $e->getMessage());
    }
}

// Lưu phản hồi của bot - ưu tiên user_id khi đăng nhập
try {
    // Khi người dùng đã đăng nhập, hãy sử dụng user_id; nếu không, hãy sử dụng session_id.
    if ($user_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, session_id, message, direction) VALUES (?, ?, ?, 'bot')");
        $stmt->execute([$user_id, $session_id, $reply]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, session_id, message, direction) VALUES (?, ?, ?, 'bot')");
        $stmt->execute([null, $session_id, $reply]);
    }
} catch (Exception $e) {
    error_log("Chat_Send.php: Error saving bot message: " . $e->getMessage());
}

// Tạo/cập nhật hồ sơ khách hàng không đăng nhập (khách vãng lai).
if ($user_id == 0 && !empty($session_id)) {
    try {
        // Kiểm tra xem khách hàng không đăng ký có mặt hay không.
        $check_guest = $pdo->prepare("SELECT id FROM guest_customers WHERE session_id = ? LIMIT 1");
        $check_guest->execute([$session_id]);
        $exists_guest = $check_guest->fetch();

        if (!$exists_guest) {
            // Tạo khách hàng khách mới với session_id làm tên giữ chỗ.
            $guest_name = "Khách vãng lai #" . substr($session_id, 0, 8);
            $stmt = $pdo->prepare("INSERT INTO guest_customers (session_id, name, phone, notes) VALUES (?, ?, '', ?)");
            $stmt->execute([$session_id, $guest_name, "Chat started at " . date('Y-m-d H:i:s')]);
        } else {
            // Cập nhật dấu thời gian của khách hàng hiện có
            $stmt = $pdo->prepare("UPDATE guest_customers SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$exists_guest['id']]);
        }
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error creating/updating guest customer: " . $e->getMessage());
    }
}

// Trả về phản hồi
echo json_encode([
    'ok' => true,
    'reply' => $reply,
    'language' => $lang,
    'has_booking_form' => $has_booking_form
], JSON_UNESCAPED_UNICODE);
