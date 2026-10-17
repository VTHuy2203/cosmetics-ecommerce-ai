<?php
// Export_Bookings_CSV.php - Export consultation bookings to CSV
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/AdminManage/src/Layouts/Admin_Access_Check.php';

$is_admin = in_array((string)($_SESSION['admin_role']??''), ['admin','manager'], true);
if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
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
");

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="consultation_bookings_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Add BOM for UTF-8 Excel compatibility
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'ID',
    'User ID',
    'Session ID',
    'Customer Name',
    'Email',
    'Phone',
    'Appointment Time',
    'Message Content',
    'Message Sent At',
    'Note',
    'Created At'
], ',', '"', '');

// Write data
while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'],
        $row['user_id'] ?: '',
        $row['session_id'],
        $row['HoVaTen'] ?: 'Khách vãng lai',
        $row['Email'] ?: '',
        $row['SDT'] ?: '',
        $row['appt_at'],
        $row['message_content'] ?: '',
        $row['message_sent_at'] ?: '',
        $row['note'] ?: '',
        $row['created_at']
    ], ',', '"', '');
}

fclose($output);
exit;
