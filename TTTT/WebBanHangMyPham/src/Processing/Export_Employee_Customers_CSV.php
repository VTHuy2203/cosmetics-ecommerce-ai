<?php
/**
 * Export Employee Customers CSV
 * Allows employees to export customer data (read-only)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an employee
if (!isset($_SESSION['user']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    die('Access denied. Only employees can export customer data.');
}

include("../DB_Connect.php");

// Check if employee is approved
$user_id = $_SESSION['IDNguoiDung'];
$employee_check = mysqli_query($conn, "SELECT status FROM shipper_requests WHERE user_id = $user_id AND status = 'approved' ORDER BY created_at DESC LIMIT 1");
if (!$employee_check || mysqli_num_rows($employee_check) == 0) {
    die('Access denied. Your employee account has not been approved.');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers_export_' . date('Y-m-d_His') . '.csv"');

// Create PDO connection for advanced queries
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

// Get filter from query string
$lead_filter = isset($_GET['lead']) ? trim($_GET['lead']) : '';

// Output CSV
$out = fopen('php://output', 'w');
// Add BOM for UTF-8 Excel compatibility
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers - Complete customer information with appointment history
fputcsv($out, [
    'User ID',
    'Họ và Tên',
    'Tên Đăng Nhập',
    'Số Điện Thoại',
    'Email',
    'Địa Chỉ',
    'Số Dư',
    'Role',
    'Lead Label',
    'Lead Score',
    'Lead Probability (%)',
    'Sản Phẩm Quan Tâm',
    'Nhu Cầu Gần Nhất',
    'Lần Tương Tác Cuối',
    'Đã Đặt Lịch',
    'Tổng Số Lịch Đặt',
    'Lịch Đặt (Chi Tiết)',
    'Tin Nhắn Chat (User)',
    'Tin Nhắn Chat (Bot)',
    'Session ID',
    'Ghi Chú'
], ',');

// Get all customers (excluding admin and manager)
$users_sql = "SELECT * FROM khachhang WHERE Role != 'admin' AND Role != 'manager' ORDER BY ID_KhachHang DESC";
$users_result = mysqli_query($conn, $users_sql);

while ($user = mysqli_fetch_assoc($users_result)) {
    $user_id = (int)$user['ID_KhachHang'];
    
    // Get lead information from customer_analysis
    $lead = null;
    $lead_label = 'N/A';
    $lead_score = 0;
    $lead_prob = 0;
    $product_interest = '';
    $last_need = '';
    $has_appointment = 'Không';
    $total_appointments = 0;
    $appointment_details = '';
    $last_interaction = '';
    $user_messages = '';
    $bot_messages = '';
    $session_id = '';
    
    try {
        // Get latest customer analysis
        $caStmt = $pdo->prepare("SELECT label, product_interest, last_need, has_appointment, updated_at, session_id 
                                 FROM customer_analysis 
                                 WHERE user_id = ? 
                                 ORDER BY updated_at DESC 
                                 LIMIT 1");
        $caStmt->execute([$user_id]);
        $ca = $caStmt->fetch();
        
        if ($ca) {
            $label = $ca['label'] ?? 'spam';
            $lead_label = $label === 'potential' ? 'Potential' : ($label === 'follow-up' ? 'Neutral' : 'Spam');
            $lead_score = $label === 'potential' ? 90 : ($label === 'follow-up' ? 50 : 30);
            $lead_prob = $lead_score;
            $product_interest = $ca['product_interest'] ?? '';
            $last_need = $ca['last_need'] ?? '';
            $has_appointment = ($ca['has_appointment'] ?? 0) ? 'Có' : 'Không';
            $last_interaction = $ca['updated_at'] ?? '';
            $session_id = $ca['session_id'] ?? '';
        }
        
        // Get all appointments for this customer
        $apptStmt = $pdo->prepare("SELECT id, appt_at, message_content, message_sent_at, note 
                                   FROM chat_appointments 
                                   WHERE user_id = ? 
                                   ORDER BY appt_at DESC");
        $apptStmt->execute([$user_id]);
        $appointments = $apptStmt->fetchAll();
        
        $total_appointments = count($appointments);
        $appt_details = [];
        foreach ($appointments as $appt) {
            $appt_time = new DateTime($appt['appt_at']);
            $appt_details[] = sprintf(
                "ID:%d | Thời gian:%s | Nội dung:%s | Ghi chú:%s",
                $appt['id'],
                $appt_time->format('d/m/Y H:i'),
                $appt['message_content'] ?: 'N/A',
                $appt['note'] ?: '-'
            );
        }
        $appointment_details = implode(' || ', $appt_details);
        
    } catch (Exception $e) {
        error_log("Export_Employee_Customers_CSV.php: Error fetching customer_analysis: " . $e->getMessage());
    }
    
    // Get chat messages
    try {
        $chatStmt = $pdo->prepare("SELECT message, is_bot FROM chat_messages 
                                   WHERE user_id = ? 
                                   ORDER BY created_at DESC 
                                   LIMIT 50");
        $chatStmt->execute([$user_id]);
        $user_msgs = [];
        $bot_msgs = [];
        while ($msg = $chatStmt->fetch()) {
            if ($msg['is_bot']) {
                $bot_msgs[] = $msg['message'];
            } else {
                $user_msgs[] = $msg['message'];
            }
        }
        $user_messages = implode(' | ', array_slice($user_msgs, 0, 10));
        $bot_messages = implode(' | ', array_slice($bot_msgs, 0, 10));
    } catch (Exception $e) {
        // chat_messages table might not exist
    }
    
    // Apply lead filter
    if ($lead_filter !== '') {
        $filter_map = [
            'potential' => 'Potential',
            'neutral' => 'Neutral',
            'spam' => 'Spam'
        ];
        if (isset($filter_map[$lead_filter]) && $lead_label !== $filter_map[$lead_filter]) {
            continue; // Skip this customer
        }
    }
    
    // Write customer data to CSV with appointment history
    fputcsv($out, [
        $user_id,
        $user['HoVaTen'] ?? '',
        $user['TenDangNhap'] ?? '',
        $user['SDT'] ?? '',
        $user['Email'] ?? '',
        $user['DiaChi'] ?? '',
        number_format(isset($user['SoDu']) ? $user['SoDu'] : 0, 0, ',', '.') . ' đ',
        $user['Role'] ?? 'customer',
        $lead_label,
        $lead_score,
        $lead_prob,
        $product_interest,
        $last_need,
        $last_interaction,
        $has_appointment,
        $total_appointments,
        $appointment_details ?: 'Không có lịch đặt',
        $user_messages,
        $bot_messages,
        $session_id,
        '' // Ghi chú - empty for employees
    ], ',');
}

fclose($out);
exit();

