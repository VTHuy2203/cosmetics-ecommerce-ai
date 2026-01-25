<?php
// Admin/Export_Users_CSV.php - Export users with lead filter and detailed info
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DEFAULTS = [
  "DB_HOST"    => "127.0.0.1",
  "DB_NAME"    => "webmipham1",
  "DB_USER"    => "root",
  "DB_PASS"    => "",
  "DB_CHARSET" => "utf8mb4",
];

function pdo_connect($cfg){
  $dsn = "mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset={$cfg['DB_CHARSET']}";
  return new PDO($dsn, $cfg['DB_USER'], $cfg['DB_PASS'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
}

function mysqli_connect_old(){
  return mysqli_connect("127.0.0.1", "root", "", "webmipham1");
}

$pdo = pdo_connect($DEFAULTS);
$conn = mysqli_connect_old();

// Get filter from query string
$lead_filter = isset($_GET['lead']) ? trim($_GET['lead']) : '';

// Check which columns exist in leads table
$columns = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_COLUMN);
$has_created_at = in_array('created_at', $columns);
$has_updated_at = in_array('updated_at', $columns);
$has_last_interaction = in_array('last_interaction', $columns);
$has_ThoiGianTao = in_array('ThoiGianTao', $columns);

// Build the query based on available columns
$timeColumn = 'last_interaction';
if (!$has_last_interaction) {
  if ($has_updated_at) {
    $timeColumn = 'updated_at';
  } else if ($has_created_at) {
    $timeColumn = 'created_at';
  } else if ($has_ThoiGianTao) {
    $timeColumn = 'ThoiGianTao';
  } else {
    $timeColumn = 'NULL';
  }
}

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
  'Lead Probability (%)',
  'Sản Phẩm Quan Tâm',
  'Nhu Cầu Gần Nhất',
  'Lần Tương Tác Cuối',
  'Đã Đặt Lịch',
  'Thời Gian Đặt Lịch',
  'Tin Nhắn Chat (User)',
  'Tin Nhắn Chat (Bot)',
  'Session ID',
  'Ghi Chú'
], ',');

// Get all users from khachhang table
$users_sql = "SELECT * FROM khachhang";
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
    // Build ORDER BY clause based on available columns
    $orderByCol = 'id';
    if ($has_updated_at) {
      $orderByCol = 'updated_at';
    } else if ($has_created_at) {
      $orderByCol = 'created_at';
    } else if ($has_ThoiGianTao) {
      $orderByCol = 'ThoiGianTao';
    } else if ($has_last_interaction) {
      $orderByCol = 'last_interaction';
    }
    
    $lead_stmt = $pdo->prepare("SELECT label, score, product_interest, last_message, last_interaction, appointment_at FROM leads WHERE user_id = ? ORDER BY $orderByCol DESC LIMIT 1");
    $lead_stmt->execute([$user_id]);
    $lead = $lead_stmt->fetch();
    
    if ($lead) {
      $lead_label = $lead['label'] ?? 'N/A';
      $lead_score = $lead['score'] ?? 0;
      $lead_prob = min(100, max(0, (int)$lead_score));
      $product_interest = $lead['product_interest'] ?? '';
      $last_need = $lead['last_message'] ?? '';
      $appointment_at = $lead['appointment_at'] ?? '';
      $has_appointment = !empty($appointment_at) ? 'Có' : 'Không';
      
      // Apply filter
      if ($lead_filter !== '') {
        // Map Vietnamese labels to English
        $label_map = [
          'potential' => 'potential',
          'Tiềm năng cao' => 'potential',
          'Đã đặt lịch' => 'potential',
          'Cần chăm sóc' => 'potential',
          'average' => 'neutral',
          'normal' => 'neutral',
          'spam' => 'spam'
        ];
        $mapped_label = $label_map[$lead_label] ?? $lead_label;
        if ($mapped_label !== $lead_filter) {
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
    // If leads table query fails, continue with user data only
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
  
  // Get session ID - Try multiple sources
  $session_id = '';
  try {
    // First try from leads table
    $orderByCol = 'id';
    if ($has_updated_at) {
      $orderByCol = 'updated_at';
    } else if ($has_created_at) {
      $orderByCol = 'created_at';
    } else if ($has_ThoiGianTao) {
      $orderByCol = 'ThoiGianTao';
    } else if ($has_last_interaction) {
      $orderByCol = 'last_interaction';
    }
    
    $sid_stmt = $pdo->prepare("SELECT session_id FROM leads WHERE user_id = ? ORDER BY $orderByCol DESC LIMIT 1");
    $sid_stmt->execute([$user_id]);
    $sid_row = $sid_stmt->fetch();
    if ($sid_row && !empty($sid_row['session_id'])) {
      $session_id = $sid_row['session_id'];
    } else {
      // Fallback to chat_messages
      $sid_stmt2 = $pdo->prepare("SELECT session_id FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 1");
      $sid_stmt2->execute([$user_id]);
      $sid_row2 = $sid_stmt2->fetch();
      if ($sid_row2 && !empty($sid_row2['session_id'])) {
        $session_id = $sid_row2['session_id'];
      } else {
        // Try chat_appointments
        $sid_stmt3 = $pdo->prepare("SELECT session_id FROM chat_appointments WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $sid_stmt3->execute([$user_id]);
        $sid_row3 = $sid_stmt3->fetch();
        if ($sid_row3 && !empty($sid_row3['session_id'])) {
          $session_id = $sid_row3['session_id'];
        }
      }
    }
  } catch (Exception $e) {
    // Continue if session_id query fails
  }
  
  // Get last interaction time
  $last_interaction = '';
  if ($lead && isset($lead['last_interaction'])) {
    $last_interaction = $lead['last_interaction'];
  } else if ($timeColumn !== 'NULL') {
    try {
      $interaction_stmt = $pdo->prepare("SELECT $timeColumn FROM leads WHERE user_id = ? ORDER BY $timeColumn DESC LIMIT 1");
      $interaction_stmt->execute([$user_id]);
      $interaction_row = $interaction_stmt->fetch();
      if ($interaction_row) {
        $last_interaction = $interaction_row[$timeColumn] ?? '';
      }
    } catch (Exception $e) {
      // Continue if last_interaction query fails
    }
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
  
  // Get customer needs from customer_needs table if available
  if (!$last_need) {
    try {
      $need_result = mysqli_query($conn, "SELECT need_text FROM customer_needs WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 1");
      if ($need_result && ($need_row = mysqli_fetch_assoc($need_result))) {
        $last_need = $need_row['need_text'];
      }
    } catch (Exception $e) {
      // Continue if customer_needs query fails
    }
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
    $lead_prob . '%',
    $product_interest,
    $last_need,
    $last_interaction ?: 'N/A',
    $has_appointment,
    $appointment_at ?: 'N/A',
    substr($user_messages, 0, 500), // Limit message length
    substr($bot_messages, 0, 500),  // Limit message length
    $session_id ?: 'N/A',
    $notes_text ?: '' // Ghi chú từ appointment
  ], ',');
}

fclose($out);
mysqli_close($conn);
?>
