<?php
/**
 * Export Employee Customers CSV
 * Allows employees to export customer data (read-only)
 */

require_once dirname(__DIR__, 3) . '/config/security.php';

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

$pdo = app_pdo();

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
    'Spam Risk (0-100)',
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
], ',', '"', '');

// Get all customers (excluding admin and manager)
$users_sql = "SELECT * FROM khachhang WHERE Role = 'customer' OR Role IS NULL ORDER BY ID_KhachHang DESC";
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
        $caStmt = $pdo->prepare("SELECT sc.segment AS label,sc.intent_score,sc.spam_risk_score,sc.calculated_at AS updated_at,
                                        ca.product_interest,ca.last_need,ca.has_appointment,ca.session_id
                                 FROM customer_scores_current sc
                                 LEFT JOIN customer_analysis ca ON ca.id=(SELECT ca2.id FROM customer_analysis ca2 WHERE ca2.user_id=sc.user_id ORDER BY ca2.updated_at DESC,ca2.id DESC LIMIT 1)
                                 WHERE sc.user_id=? LIMIT 1");
        $caStmt->execute([$user_id]);
        $ca = $caStmt->fetch();
        
        if ($ca) {
            $lead_label = $ca['label'] ?? 'normal';
            $lead_score = (int) ($ca['intent_score'] ?? 0);
            $lead_prob = (int) ($ca['spam_risk_score'] ?? 0);
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
        $chatStmt = $pdo->prepare("SELECT message, direction FROM chat_messages 
                                   WHERE user_id = ? 
                                   ORDER BY created_at DESC 
                                   LIMIT 50");
        $chatStmt->execute([$user_id]);
        $user_msgs = [];
        $bot_msgs = [];
        while ($msg = $chatStmt->fetch()) {
            if ($msg['direction'] === 'bot') {
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
        if (in_array($lead_filter, ['normal','follow-up','potential','spam'], true) && $lead_label !== $lead_filter) {
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
    ], ',', '"', '');
}

fclose($out);
exit();

