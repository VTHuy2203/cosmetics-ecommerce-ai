<?php
// Export_Messages_CSV.php - Export customer messages to CSV
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
$filter_label = $_GET['label'] ?? '';

$sql = "
    SELECT 
        cm.id, cm.user_id, cm.session_id, cm.message, cm.created_at,
        kh.HoVaTen, kh.Email, kh.SDT,
        COALESCE(cml.label, 'unlabeled') as label, cml.notes, cml.labeled_at,
        CASE WHEN ca.id IS NOT NULL THEN 'Yes' ELSE 'No' END as has_appointment,
        ca.appt_at as appointment_time
    FROM chat_messages cm
    LEFT JOIN khachhang kh ON cm.user_id = kh.ID_KhachHang
    LEFT JOIN chat_message_labels cml ON cm.id = cml.message_id
    LEFT JOIN chat_appointments ca ON cm.session_id = ca.session_id AND cm.user_id = ca.user_id
    WHERE cm.direction = 'user'
";

$params = [];
if ($filter_label) {
    $sql .= " AND cml.label = ?";
    $params[] = $filter_label;
}
$sql .= " ORDER BY cm.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customer_messages_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, ['ID','User ID','Session ID','Customer Name','Email','Phone','Message','Created At','Label','Notes','Labeled At','Has Appointment','Appointment Time'], ',', '"', '');

$label_map = ['not_potential' => 'Không tiềm năng', 'needs_followup' => 'Cần theo dõi', 'potential' => 'Tiềm năng', 'unlabeled' => 'Chưa phân loại'];

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'], $row['user_id'] ?: '', $row['session_id'],
        $row['HoVaTen'] ?: 'Khách vãng lai', $row['Email'] ?: '', $row['SDT'] ?: '',
        $row['message'], $row['created_at'],
        $label_map[$row['label']] ?? $row['label'], $row['notes'] ?: '', $row['labeled_at'] ?: '',
        $row['has_appointment'], $row['appointment_time'] ?: ''
    ], ',', '"', '');
}

fclose($output);
exit;
