<?php
// Export_Bookings_CSV.php - Export consultation bookings to CSV
require_once dirname(__DIR__, 3) . '/config/security.php';
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;
if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

function pdo_connect_chat() {
    return app_pdo();
}

$pdo = pdo_connect_chat();

$stmt = $pdo->query("
    SELECT ca.id, ca.user_id, ca.session_id, ca.appt_at, ca.note,
           ca.message_content, ca.message_sent_at, ca.created_at,
           kh.HoVaTen, kh.Email, kh.SDT
    FROM chat_appointments ca
    LEFT JOIN khachhang kh ON ca.user_id = kh.ID_KhachHang
    ORDER BY ca.appt_at DESC, ca.created_at DESC
");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="consultation_bookings_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, ['ID','User ID','Session ID','Customer Name','Email','Phone','Appointment Time','Message Content','Message Sent At','Note','Created At'], ',', '"', '');

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'], $row['user_id'] ?: '', $row['session_id'],
        $row['HoVaTen'] ?: 'Khách vãng lai', $row['Email'] ?: '', $row['SDT'] ?: '',
        $row['appt_at'], $row['message_content'] ?: '', $row['message_sent_at'] ?: '',
        $row['note'] ?: '', $row['created_at']
    ], ',', '"', '');
}

fclose($output);
exit;
