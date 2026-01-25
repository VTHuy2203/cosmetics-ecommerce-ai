<?php
// Export_Messages_CSV.php - Export customer messages to CSV
session_start();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

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

// Get filter
$filter_label = $_GET['label'] ?? '';

// Build query
$sql = "
    SELECT 
        cm.id,
        cm.user_id,
        cm.session_id,
        cm.message,
        cm.created_at,
        kh.HoVaTen,
        kh.Email,
        kh.SDT,
        COALESCE(cml.label, 'unlabeled') as label,
        cml.notes,
        cml.labeled_at,
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

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customer_messages_' . date('Y-m-d') . '.csv"');
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
    'Message',
    'Created At',
    'Label',
    'Notes',
    'Labeled At',
    'Has Appointment',
    'Appointment Time'
], ',');

// Write data
while ($row = $stmt->fetch()) {
    $label_map = [
        'not_potential' => 'Không tiềm năng',
        'needs_followup' => 'Cần theo dõi',
        'potential' => 'Tiềm năng',
        'unlabeled' => 'Chưa phân loại'
    ];
    
    fputcsv($output, [
        $row['id'],
        $row['user_id'] ?: '',
        $row['session_id'],
        $row['HoVaTen'] ?: 'Khách vãng lai',
        $row['Email'] ?: '',
        $row['SDT'] ?: '',
        $row['message'],
        $row['created_at'],
        $label_map[$row['label']] ?? $row['label'],
        $row['notes'] ?: '',
        $row['labeled_at'] ?: '',
        $row['has_appointment'],
        $row['appointment_time'] ?: ''
    ], ',');
}

fclose($output);
exit;
