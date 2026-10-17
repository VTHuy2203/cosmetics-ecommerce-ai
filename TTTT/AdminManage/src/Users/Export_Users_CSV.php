<?php
// Admin/Export_Users_CSV.php - Export users with lead filter and detailed info
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/DB_Connect.php';
if (!isset($_SESSION['admin_id']) || !in_array((string) ($_SESSION['admin_role'] ?? ''), ['admin','manager','employee'], true)) {
  http_response_code(403);
  exit('Access denied.');
}
$pdo = app_pdo();

// Get filter from query string
$lead_filter = isset($_GET['lead']) ? trim($_GET['lead']) : '';

// Output CSV headers
$out = fopen('php://output', 'w');
// Add BOM for UTF-8 Excel compatibility
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers: User Info + Lead Info + Chat Messages + Other Details
fputcsv($out, [
  'User ID',
  'Họ và Tên',
  'Số Điện Thoại',
  'Email',
  'Địa Chỉ',
  'Số Dư',
  'Lead Label',
  'Lead Score',
  'Spam Risk (0-100)',
  'Sản Phẩm Quan Tâm',
  'Nhu Cầu Gần Nhất',
  'Lần Tương Tác Cuối',
  'Đã Đặt Lịch',
  'Thời Gian Đặt Lịch',
  'Tin Nhắn Chat (User)',
  'Tin Nhắn Chat (Bot)',
  'Session ID',
  'Ghi Chú'
], ',', '"', '');

// Get all users from khachhang table
$users_sql = "SELECT * FROM khachhang WHERE Role = 'customer' OR Role IS NULL";
$users_result = mysqli_query($conn, $users_sql);

while ($user = mysqli_fetch_assoc($users_result)) {
  $user_id = (int)$user['ID_KhachHang'];
  
  // Get lead information
  $lead = null;
  $lead_label = 'N/A';
  $lead_score = 0;
  $lead_prob = 0;
  $product_interest = '';
  $last_need = '';
  $appointment_at = '';
  $has_appointment = 'Không';
  
  try {
    $lead_stmt = $pdo->prepare("SELECT sc.segment AS label,sc.intent_score AS score,sc.spam_risk_score,sc.calculated_at AS last_interaction,
                                      ca.product_interest,ca.last_need,ca.has_appointment,ca.session_id
                               FROM customer_scores_current sc
                               LEFT JOIN customer_analysis ca ON ca.id=(SELECT ca2.id FROM customer_analysis ca2 WHERE ca2.user_id=sc.user_id ORDER BY ca2.updated_at DESC,ca2.id DESC LIMIT 1)
                               WHERE sc.user_id=? LIMIT 1");
    $lead_stmt->execute([$user_id]);
    $lead = $lead_stmt->fetch();
    
    if ($lead) {
      $lead_label = $lead['label'] ?? 'N/A';
      $lead_score = $lead['score'] ?? 0;
      $lead_prob = min(100, max(0, (int) ($lead['spam_risk_score'] ?? 0)));
      $product_interest = $lead['product_interest'] ?? '';
      $last_need = $lead['last_need'] ?? '';
      $has_appointment = !empty($lead['has_appointment']) ? 'Có' : 'Không';
      
      // Apply filter
      if ($lead_filter !== '') {
        if ($lead_label !== $lead_filter) {
          continue; // Skip this user if doesn't match filter
        }
      }
    } else {
      // No lead data - skip if filtering for specific lead type
      if ($lead_filter !== '' && $lead_filter !== 'all') {
        continue;
      }
    }
  } catch (Exception $e) {
    // If the scoring query fails, continue with user data only
  }
  
  // Get chat messages for this user
  $user_messages = '';
  $bot_messages = '';
  try {
    $msg_stmt = $pdo->prepare("SELECT message, direction FROM chat_messages WHERE user_id = ? ORDER BY id ASC LIMIT 50");
    $msg_stmt->execute([$user_id]);
    $messages = $msg_stmt->fetchAll();
    
    $user_msgs = [];
    $bot_msgs = [];
    foreach ($messages as $msg) {
      if ($msg['direction'] === 'user') {
        $user_msgs[] = $msg['message'];
      } else {
        $bot_msgs[] = $msg['message'];
      }
    }
    $user_messages = implode(' | ', $user_msgs);
    $bot_messages = implode(' | ', $bot_msgs);
  } catch (Exception $e) {
    // If chat_messages query fails, continue
  }
  
  // Get session and appointment information from the active chat tables.
  $session_id = '';
  try {
    $sid_stmt = $pdo->prepare("SELECT session_id FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $sid_stmt->execute([$user_id]);
    $sid_row = $sid_stmt->fetch();
    if ($sid_row && !empty($sid_row['session_id'])) {
      $session_id = $sid_row['session_id'];
    } else {
      $sid_stmt2 = $pdo->prepare("SELECT session_id FROM chat_appointments WHERE user_id = ? ORDER BY id DESC LIMIT 1");
      $sid_stmt2->execute([$user_id]);
      $sid_row2 = $sid_stmt2->fetch();
      if ($sid_row2 && !empty($sid_row2['session_id'])) {
        $session_id = $sid_row2['session_id'];
      }
    }
    $appointmentStmt = $pdo->prepare('SELECT MAX(appt_at) FROM chat_appointments WHERE user_id=?');
    $appointmentStmt->execute([$user_id]);
    $appointment_at = (string) ($appointmentStmt->fetchColumn() ?: '');
  } catch (Exception $e) {
    // Continue if session_id query fails
  }
  
  // Get last interaction time
  $last_interaction = '';
  if ($lead && isset($lead['last_interaction'])) {
    $last_interaction = $lead['last_interaction'];
  }
  
  // Get notes from chat_appointments (most recent appointment note)
  $notes_text = '';
  try {
    $notes_stmt = $pdo->prepare("SELECT note FROM chat_appointments WHERE user_id = ? AND note IS NOT NULL AND note != '' ORDER BY id DESC LIMIT 1");
    $notes_stmt->execute([$user_id]);
    $notes_row = $notes_stmt->fetch();
    if ($notes_row && !empty($notes_row['note'])) {
      $notes_text = $notes_row['note'];
      // If note is just a datetime, try to get a real note from another appointment
      if (preg_match('/^\d{4}-\d{2}-\d{2}/', $notes_text)) {
        $notes_stmt2 = $pdo->prepare("SELECT note FROM chat_appointments WHERE user_id = ? AND note IS NOT NULL AND note != '' AND note NOT LIKE '%/%' ORDER BY id DESC LIMIT 5");
        $notes_stmt2->execute([$user_id]);
        $notes_rows = $notes_stmt2->fetchAll();
        foreach ($notes_rows as $nr) {
          if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $nr['note'])) {
            $notes_text = $nr['note'];
            break;
          }
        }
      }
    }
  } catch (Exception $e) {
    // Continue if notes query fails
  }
  
  // Write CSV row
  fputcsv($out, [
    $user_id,
    $user['HoVaTen'] ?? '',
    $user['SDT'] ?? '',
    $user['Email'] ?? '',
    $user['DiaChi'] ?? '',
    number_format($user['SoDu'] ?? 0, 0, ',', '.') . ' đ',
    $lead_label,
    $lead_score,
    $lead_prob,
    $product_interest,
    $last_need,
    $last_interaction ?: 'N/A',
    $has_appointment,
    $appointment_at ?: 'N/A',
    substr($user_messages, 0, 500), // Limit message length
    substr($bot_messages, 0, 500),  // Limit message length
    $session_id ?: 'N/A',
    $notes_text ?: '' // Ghi chú từ appointment
  ], ',', '"', '');
}

fclose($out);
mysqli_close($conn);
?>
