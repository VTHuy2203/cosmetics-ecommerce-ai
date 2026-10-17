<?php
/**
 * Chat Send Handler - Clean Rebuild
 * Handles chat messages, product recommendations, and appointment scheduling
 */

include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/ChatSessionService.php';
require_once dirname(__DIR__) . '/Services/CustomerScoringService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);
if (!auth_rate_limit('chat:' . ($_SESSION['IDNguoiDung'] ?? session_id()), 30, 60)) {
    json_response(['ok' => false, 'error' => 'rate_limited'], 429);
}

try {
    $pdo = chat_session_pdo();
} catch (PDOException $e) {
    error_log("Chat_Send.php: PDO connection error: " . $e->getMessage());
    die(json_encode(['ok' => false, 'error' => 'Database connection failed']));
}

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Get request data
$rawInput = file_get_contents('php://input');
if (!is_string($rawInput) || strlen($rawInput) > 16384) {
    json_response(['ok' => false, 'error' => 'payload_too_large'], 413);
}
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'invalid_json'], 400);
}
$message = is_string($input['message'] ?? null) ? trim($input['message']) : '';
$requested_session_id = $input['session_id'] ?? null;
if ($message === '' || mb_strlen($message) > 1000) {
    json_response(['ok' => false, 'error' => 'invalid_message'], 422);
}
$user_id = chat_session_user_id($pdo);
$session_id = chat_session_resolve($pdo, $user_id, $requested_session_id);

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

// Helper functions
function detectLanguage($text) {
    $text_lower = mb_strtolower((string) $text, 'UTF-8');
    // Any Vietnamese diacritic (booking form "Tên:/SĐT:/Địa chỉ:" included) means Vietnamese.
    if (preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/u', $text_lower) === 1) {
        return 'vi';
    }
    // The shop serves Vietnamese customers: answer in English only for clearly English text.
    $english = ['hello', 'hi', 'please', 'want', 'need', 'what', 'which', 'how', 'price', 'product', 'skin',
        'recommend', 'buy', 'cream', 'lipstick', 'thanks', 'thank', 'the', 'for', 'my', 'is', 'are', 'can', 'you'];
    $words = preg_split('/[^a-z]+/', $text_lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return count(array_intersect($words, $english)) >= 2 ? 'en' : 'vi';
}

/** @return array{name:string,phone:string,address:string}|string Clean guest contact, or an error message. */
function validateGuestContact(string $name, string $phone, string $address)
{
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    $phone = preg_replace('/[\s.\-()]/', '', $phone) ?? '';
    $address = trim(preg_replace('/\s+/u', ' ', $address) ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        return 'Bạn vui lòng nhập họ tên (2–100 ký tự) để Mơ Shop liên hệ nhé.';
    }
    if (preg_match('/^(?:0|\+84)(?:3|5|7|8|9)\d{8}$/', $phone) !== 1) {
        return 'Số điện thoại chưa đúng định dạng (ví dụ 0912345678). Bạn kiểm tra lại giúp mình nhé.';
    }
    if (mb_strlen($address) > 255) {
        $address = mb_substr($address, 0, 255);
    }
    return ['name' => $name, 'phone' => $phone, 'address' => $address];
}

/** Realtime toast for admin/staff; fail-open so chat never breaks when the socket server is down. */
function chatRealtimePush(array $message): void
{
    try {
        $wsPushFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (is_file($wsPushFile)) {
            require_once $wsPushFile;
            if (function_exists('websocket_push')) {
                websocket_push(['admin_channel', 'staff_channel'], $message);
            }
        }
    } catch (Throwable $e) {
        error_log('Chat_Send.php: realtime push failed: ' . $e->getMessage());
    }
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

function parseDateTime($text) {
    // Extract only the datetime part (before any pipe | character for guest info)
    $datetime_part = $text;
    if (strpos($text, '|') !== false) {
        $datetime_part = trim(explode('|', $text)[0]);
    }
    // Try to parse Vietnamese datetime format: DD/MM/YYYY HH:MM
    if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\s+(\d{1,2})[:h](\d{2})/i', $datetime_part, $matches)) {
        $day = intval($matches[1]);
        $month = intval($matches[2]);
        $year = intval($matches[3]);
        if ($year < 100) $year += 2000;
        $hour = intval($matches[4]);
        $minute = intval($matches[5]);

        if (checkdate($month, $day, $year) && $hour >= 0 && $hour < 24 && $minute >= 0 && $minute < 60) {
            $result = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
            return $result;
        }
    }

    return null;
}

function callRAGAPI($message, $user_id, $session_id, array $history = [], array $context_product_ids = []) {
    $serviceUrl = rtrim(app_env('AI_SERVICE_URL', '') ?? '', '/');
    if ($serviceUrl === '') {
        return null;
    }
    $api_url = $serviceUrl . '/chat';
    $serviceToken = app_env('AI_SERVICE_TOKEN', '') ?? '';

    $payload = json_encode([
        'message' => $message,
        'user_id' => $user_id ?: null,
        'session_id' => $session_id,
        'history' => $history,
        'context_product_ids' => array_slice(array_values($context_product_ids), 0, 5),
    ], JSON_UNESCAPED_UNICODE);
    $headers = ['Content-Type: application/json'];
    if ($serviceToken !== '') {
        $headers[] = 'Authorization: Bearer ' . $serviceToken;
    }

    $response = false;
    $http_code = 0;
    $transport_error = '';

    // Laragon installations do not always load ext-curl for Apache even when
    // PHP CLI has it. Keep a native HTTP fallback so chat never dies with a
    // fatal "undefined function curl_init" error.
    if (function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 75);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $transport_error = $response === false ? curl_error($ch) : '';
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => 75,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($api_url, false, $context);
        $response_headers = $http_response_header ?? [];
        if (isset($response_headers[0]) && preg_match('/\s(\d{3})\s/', $response_headers[0], $matches)) {
            $http_code = (int) $matches[1];
        }
        if ($response === false) {
            $last_error = error_get_last();
            $transport_error = (string) ($last_error['message'] ?? 'HTTP request failed');
        }
    }
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['reply'])) {
            return $data;
        }
    }

    error_log(sprintf(
        'Chat_Send.php: AI request failed (HTTP %d): %s %s',
        $http_code,
        $transport_error,
        $response === false ? 'transport_failure' : 'non_success_response'
    ));
    
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
    
} catch (Exception $e) {
    error_log("Chat_Send.php: Error saving user message: " . $e->getMessage());
}

// Realtime: tell admin/staff about every new customer message, guests included.
$chat_sender_name = $user_name;
if ($user_id <= 0) {
    $chat_sender_name = 'Khách vãng lai #' . substr($session_id, 0, 8);
    try {
        $guestName = $pdo->prepare("SELECT name FROM guest_customers WHERE session_id = ? AND phone <> '' LIMIT 1");
        $guestName->execute([$session_id]);
        $chat_sender_name = (string) ($guestName->fetchColumn() ?: $chat_sender_name);
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error reading guest name: " . $e->getMessage());
    }
}
chatRealtimePush([
    'type' => 'chat_message',
    'title' => $user_id > 0 ? 'Tin nhắn mới' : 'Khách vãng lai nhắn tin',
    'message' => $chat_sender_name . ': ' . mb_strimwidth($message, 0, 80, '...', 'UTF-8'),
    'sound' => 'notification',
    'data' => [
        'user_id' => $user_id > 0 ? $user_id : null,
        'user_name' => $chat_sender_name,
        'session_id' => $session_id,
        'is_guest' => $user_id <= 0,
        'message_preview' => mb_strimwidth($message, 0, 200, '...', 'UTF-8'),
        'timestamp' => date('Y-m-d H:i:s'),
    ],
]);
chat_session_touch($pdo, $session_id, $message);

// Process message
$lang = detectLanguage($message);
$reply = "";
$has_booking_form = false;

// Check for datetime FIRST (if user is submitting booking form)
$parsed_dt = parseDateTime($message);

// Check if user is responding to consultation question (only if not submitting datetime)
$is_booking_yes = false;
$is_booking_no = false;

// Check for booking intent (only if not submitting datetime)
$booking_intent = !$parsed_dt ? hasBookingIntent($message) : false;

// Check if products have already been suggested (to avoid suggesting multiple times)
$products_already_suggested = false;
$booking_confirmation_pending = false;
try {
    if ($user_id > 0) {
        $check_stmt = $pdo->prepare("SELECT id FROM chat_messages WHERE user_id = ? AND session_id = ? AND direction = 'bot' AND (message LIKE '%Bạn có muốn đặt lịch tư vấn%' OR message LIKE '%Would you like to schedule%') ORDER BY created_at DESC LIMIT 1");
        $check_stmt->execute([$user_id, $session_id]);
    } else {
        $check_stmt = $pdo->prepare("SELECT id FROM chat_messages WHERE session_id = ? AND direction = 'bot' AND (message LIKE '%Bạn có muốn đặt lịch tư vấn%' OR message LIKE '%Would you like to schedule%') ORDER BY created_at DESC LIMIT 1");
        $check_stmt->execute([$session_id]);
    }
    $products_already_suggested = $check_stmt->fetch() !== false;

    $latestBot = $pdo->prepare(
        $user_id > 0
            ? "SELECT message FROM chat_messages WHERE user_id=? AND session_id=? AND direction='bot' ORDER BY id DESC LIMIT 1"
            : "SELECT message FROM chat_messages WHERE user_id IS NULL AND session_id=? AND direction='bot' ORDER BY id DESC LIMIT 1"
    );
    $latestBot->execute($user_id > 0 ? [$user_id, $session_id] : [$session_id]);
    $latestBotMessage = (string) ($latestBot->fetchColumn() ?: '');
    $booking_confirmation_pending = str_contains($latestBotMessage, 'Bạn có muốn đặt lịch tư vấn')
        || str_contains($latestBotMessage, 'Would you like to schedule');
} catch (Exception $e) {
    error_log("Chat_Send.php: Error checking if products already suggested: " . $e->getMessage());
}
$is_booking_yes = !$parsed_dt && $booking_confirmation_pending && isBookingYes($message);
$is_booking_no = !$parsed_dt && $booking_confirmation_pending && isBookingNo($message);

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
    $booking_error = null;

    if ($user_id == 0 && preg_match('/\|\s*Tên:\s*([^|]+)\s*\|\s*SĐT:\s*([^|]+)\s*\|\s*Địa chỉ:\s*(.*)/iu', $message, $matches)) {
        $contact = validateGuestContact($matches[1], $matches[2], $matches[3]);
        if (is_string($contact)) {
            $booking_error = $contact;
        } else {
            $guest_name = $contact['name'];
            $guest_phone = $contact['phone'];
            $guest_address = $contact['address'];
            // One profile per chat session (uq_guest_session): the placeholder row is filled in.
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
                error_log("Chat_Send.php: Error saving guest customer: " . $e->getMessage());
            }
        }
    }
    if ($booking_error === null && strtotime($parsed_dt) <= time()) {
        $booking_error = 'Thời gian hẹn đã qua. Bạn chọn giúp mình một thời điểm trong tương lai nhé.';
    }

    if ($booking_error !== null) {
        $reply = $booking_error . "\n\n[[BOOKING_FORM]]";
        $has_booking_form = true;
    } else try {
        $stmt = $pdo->prepare("INSERT INTO chat_appointments (user_id, session_id, appt_at, message_content, message_sent_at, guest_name, guest_phone, guest_address) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $stmt->execute([$user_id ?: null, $session_id, $parsed_dt, $message, $guest_name, $guest_phone, $guest_address]);
        $appointment_id = (int) $pdo->lastInsertId();
        $appointment_who = $user_id > 0 ? $user_name : ($guest_name ?: 'Khách vãng lai #' . substr($session_id, 0, 8));
        $appointment_when = date('d/m/Y H:i', strtotime($parsed_dt));

        // Persistent admin notification (Notification Center) via the outbox, plus an instant toast.
        try {
            $outbox = $pdo->prepare("INSERT INTO outbox_events (event_type, aggregate_type, aggregate_id, payload) VALUES ('appointment.created', 'appointment', ?, ?)");
            $outbox->execute([(string) $appointment_id, json_encode([
                'appointment_id' => $appointment_id,
                'user_id' => $user_id > 0 ? $user_id : null,
                'session_id' => $session_id,
                'name' => $appointment_who,
                'phone' => $guest_phone,
                'appt_at' => $parsed_dt,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        } catch (Exception $e) {
            error_log("Chat_Send.php: Error queueing appointment notification: " . $e->getMessage());
        }
        chatRealtimePush([
            'type' => 'new_appointment',
            'title' => 'Lịch hẹn tư vấn mới',
            'message' => $appointment_who . ($guest_phone ? " ({$guest_phone})" : '') . ' hẹn lúc ' . $appointment_when,
            'sound' => 'notification',
            'data' => [
                'appointment_id' => $appointment_id,
                'user_id' => $user_id > 0 ? $user_id : null,
                'session_id' => $session_id,
                'is_guest' => $user_id <= 0,
                'name' => $appointment_who,
                'phone' => $guest_phone,
                'appt_at' => $parsed_dt,
            ],
        ]);

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
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, has_appointment, message_count)
                                   VALUES (?, ?, 1, 1)
                                   ON DUPLICATE KEY UPDATE
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
                    $stmt = $pdo->prepare("UPDATE customer_analysis SET has_appointment = 1, message_count = message_count + 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$exists['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, has_appointment, message_count) VALUES (?, ?, 1, 1)");
                    $stmt->execute([$user_id ?: null, $session_id]);
                }
            } catch (Exception $e2) {
                error_log("Chat_Send.php: Error updating customer analysis: " . $e2->getMessage());
            }
        }
        
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error saving appointment: " . $e->getMessage());
        $reply = "Xin lỗi, hệ thống chưa thể lưu lịch hẹn. Vui lòng thử lại sau.";
    }
    
} elseif (!$parsed_dt && !$is_booking_yes && !$is_booking_no && !$booking_intent) {
    // AI service is the single intent router for product, policy and general chat.
    $history = chat_session_recent_history($pdo, $session_id, $user_id, $message);
    // Products suggested last in this session, so "so sánh 2 sản phẩm này" / "có" compares them.
    $context_product_ids = [];
    try {
        $contextStmt = $pdo->prepare('SELECT context_product_ids FROM chat_sessions WHERE session_id = ? LIMIT 1');
        $contextStmt->execute([$session_id]);
        $context_product_ids = array_values(array_filter(array_map('intval', explode(',', (string) $contextStmt->fetchColumn()))));
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error reading product context: " . $e->getMessage());
    }
    $rag_response = callRAGAPI($message, $user_id, $session_id, $history, $context_product_ids);

    if ($rag_response && isset($rag_response['reply'])) {
        $reply = $rag_response['reply'];
        $lang = in_array(($rag_response['language'] ?? ''), ['vi', 'en'], true) ? $rag_response['language'] : $lang;
        $is_product_intent = ($rag_response['intent'] ?? '') === 'product';
        $has_products = $is_product_intent && !empty($rag_response['hits']);
        $follow_up = (string) ($rag_response['follow_up'] ?? '');

        if ($has_products) {
            $suggestedIds = [];
            foreach ($rag_response['hits'] as $hit) {
                $hitId = (int) ($hit['meta']['IDSanPham'] ?? $hit['id'] ?? 0);
                if ($hitId > 0 && !in_array($hitId, $suggestedIds, true)) {
                    $suggestedIds[] = $hitId;
                }
            }
            try {
                $pdo->prepare('UPDATE chat_sessions SET context_product_ids = ? WHERE session_id = ?')
                    ->execute([implode(',', array_slice($suggestedIds, 0, 5)) ?: null, $session_id]);
            } catch (Exception $e) {
                error_log("Chat_Send.php: Error saving product context: " . $e->getMessage());
            }
        }

        // One question per turn: when the assistant just offered a comparison, the consultation
        // offer waits for the next product answer (e.g. right after the comparison table).
        if ($has_products && !$products_already_suggested && $follow_up !== 'compare_offer') {
            // Ask about consultation AFTER recommending products (ONLY ONCE)
            if ($lang === 'vi') {
                $reply .= "\n\nBạn có muốn đặt lịch tư vấn với nhân viên Mơ Shop không?";
            } else {
                $reply .= "\n\nWould you like to schedule a consultation with Mơ Shop staff?";
            }
        }
        
        // Only product intent changes product-interest scoring. Store the product names the
        // assistant actually suggested; the customer's own words go to last_need.
        if ($is_product_intent) try {
            $interestNames = [];
            foreach (array_slice($rag_response['hits'] ?? [], 0, 3) as $hit) {
                $hitName = trim((string) ($hit['meta']['TenSanPham'] ?? ''));
                if ($hitName !== '' && !in_array($hitName, $interestNames, true)) {
                    $interestNames[] = $hitName;
                }
            }
            $product_interest = $interestNames !== [] ? mb_substr(implode('; ', $interestNames), 0, 1000) : null;

            $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
            $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
            $exists = $check->fetch();

            if ($exists) {
                // Keep the previous interest when this answer found no in-stock product.
                $stmt = $pdo->prepare("UPDATE customer_analysis SET
                                       product_interest = COALESCE(?, product_interest),
                                       last_need = ?,
                                       message_count = message_count + 1,
                                       updated_at = NOW()
                                       WHERE id = ?");
                $stmt->execute([$product_interest, $message, $exists['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, product_interest, last_need, message_count)
                                       VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$user_id ?: null, $session_id, $product_interest, $message]);
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
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, last_need, message_count)
                                   VALUES (?, ?, ?, 1)");
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
                                   last_need = ?,
                                   message_count = message_count + 1,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$message, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, last_need, message_count)
                                   VALUES (?, ?, ?, 1)");
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
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, last_need, message_count)
                                   VALUES (?, ?, ?, 1)");
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
    
        // A legitimate generic message is normal. Spam requires explicit abuse
        // signals and is evaluated independently by CustomerScoringService.
    try {
        $check = $pdo->prepare("SELECT id FROM customer_analysis WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL)) AND session_id = ? LIMIT 1");
        $check->execute([$user_id ?: null, $user_id ?: null, $session_id]);
        $exists = $check->fetch();
        
        if ($exists) {
            // Greetings are not a customer need: count the message, keep the last real need.
            $stmt = $pdo->prepare("UPDATE customer_analysis SET
                                   message_count = message_count + 1,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customer_analysis (user_id, session_id, message_count)
                                   VALUES (?, ?, 1)");
            $stmt->execute([$user_id ?: null, $session_id]);
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
        // Một hồ sơ cho mỗi phiên (uq_guest_session): tạo tên giữ chỗ lần đầu, sau đó chỉ cập nhật thời gian.
        $stmt = $pdo->prepare("INSERT INTO guest_customers (session_id, name, phone, notes) VALUES (?, ?, '', ?)
                               ON DUPLICATE KEY UPDATE updated_at = NOW()");
        $stmt->execute([$session_id, "Khách vãng lai #" . substr($session_id, 0, 8), "Chat started at " . date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        error_log("Chat_Send.php: Error creating/updating guest customer: " . $e->getMessage());
    }
}

// Chấm lại điểm tiềm năng ngay sau tin nhắn (lỗi chấm điểm không ảnh hưởng chat).
CustomerScoringService::refreshQuietly($user_id > 0 ? (int) $user_id : null, $session_id);

// Trả về phản hồi
echo json_encode([
    'ok' => true,
    'reply' => $reply,
    'language' => $lang,
    'has_booking_form' => $has_booking_form,
    'session_id' => $session_id,
    'session' => chat_session_info($pdo, $session_id),
    'upcoming_appointment' => chat_session_upcoming_appointment($pdo, $user_id, $session_id)
], JSON_UNESCAPED_UNICODE);
