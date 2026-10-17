<?php
/**
 * Export Customer Analysis to CSV
 * Only accessible by Manager and Admin roles
 */

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';

$admin_role = $_SESSION['admin_role'] ?? null;
if (!in_array($admin_role, ['manager', 'admin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

// Create PDO connection
try {
    $pdo = app_pdo();
} catch (PDOException $e) {
    error_log('Analysis export database error: ' . $e->getMessage());
    die('Database connection failed.');
}

// Same filters as Admin_Customer_Analysis.php
$filter = in_array($_GET['label'] ?? 'all', ['all', 'normal', 'spam', 'follow-up', 'potential'], true) ? ($_GET['label'] ?? 'all') : 'all';
$search = trim((string) ($_GET['search'] ?? ''));
$validDate = static fn(string $value): string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
$date_from = $validDate((string) ($_GET['date_from'] ?? ''));
$date_to = $validDate((string) ($_GET['date_to'] ?? ''));

// Build query
$where = [];
$params = [];

if ($filter !== 'all') {
    $where[] = "sc.segment = ?";
    $params[] = $filter;
}

if ($search !== '') {
    $where[] = "(k.HoVaTen LIKE ? OR k.Email LIKE ? OR k.SDT LIKE ? OR ca.last_need LIKE ? OR ca.product_interest LIKE ?)";
    $searchParam = "%{$search}%";
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
}

if ($date_from !== '') {
    $where[] = "sc.last_activity_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to !== '') {
    $where[] = "sc.last_activity_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get customers
$sql = "SELECT 
    sc.subject_key AS id,
    sc.user_id,
    sc.session_id,
    sc.segment AS label,
    sc.intent_score,
    sc.spam_risk_score,
    sc.value_tier,
    sc.reason_codes,
    sc.last_activity_at,
    ca.product_interest,
    ca.last_need,
    ca.message_count,
    ca.has_appointment,
    sc.calculated_at AS updated_at,
    k.ID_KhachHang,
    k.HoVaTen,
    k.Email,
    k.SDT,
    k.DiaChi,
    (SELECT COUNT(*) FROM chat_messages cm WHERE (sc.user_id IS NOT NULL AND cm.user_id=sc.user_id) OR (sc.user_id IS NULL AND cm.user_id IS NULL AND cm.session_id=sc.session_id)) AS total_messages,
    (SELECT COUNT(*) FROM chat_appointments appt WHERE (sc.user_id IS NOT NULL AND appt.user_id=sc.user_id) OR (sc.user_id IS NULL AND appt.user_id IS NULL AND appt.session_id=sc.session_id)) AS total_appointments
FROM customer_scores_current sc
LEFT JOIN khachhang k ON sc.user_id = k.ID_KhachHang
LEFT JOIN customer_analysis ca ON ca.id=(
    SELECT ca2.id FROM customer_analysis ca2
    WHERE (sc.user_id IS NOT NULL AND ca2.user_id=sc.user_id)
       OR (sc.user_id IS NULL AND ca2.user_id IS NULL AND ca2.session_id=sc.session_id)
    ORDER BY ca2.updated_at DESC,ca2.id DESC LIMIT 1
)
{$whereClause}
ORDER BY FIELD(sc.segment,'potential','follow-up','normal','spam'), sc.intent_score DESC, sc.last_activity_at DESC
LIMIT 1000";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customer_analysis_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

function csv_safe(mixed $value): string
{
    $value = (string) $value;
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
}

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV headers
fputcsv($output, [
    'ID',
    'User ID',
    'Session ID',
    'Customer Name',
    'Email',
    'Phone',
    'Address',
    'Label',
    'Intent Score',
    'Spam Risk',
    'Value Tier',
    'Score Reasons',
    'Last Activity',
    'Product Interest',
    'Last Need',
    'Message Count',
    'Has Appointment',
    'Total Messages',
    'Total Appointments',
    'Updated At'
], ',', '"', '');

// Write data rows
foreach ($customers as $customer) {
    fputcsv($output, array_map('csv_safe', [
        $customer['id'],
        $customer['user_id'] ?? 'N/A',
        $customer['session_id'],
        $customer['HoVaTen'] ?? 'Guest',
        $customer['Email'] ?? 'N/A',
        $customer['SDT'] ?? 'N/A',
        $customer['DiaChi'] ?? 'N/A',
        ucfirst($customer['label']),
        (int) $customer['intent_score'],
        (int) $customer['spam_risk_score'],
        $customer['value_tier'],
        implode('; ', array_map(
            static fn(array $reason): string => sprintf('%s (%+d)', $reason['label'] ?? $reason['code'] ?? '', (int) ($reason['points'] ?? 0)),
            json_decode((string) ($customer['reason_codes'] ?? '[]'), true) ?: []
        )),
        $customer['last_activity_at'] ?? '',
        $customer['product_interest'] ?? '',
        $customer['last_need'] ?? '',
        $customer['message_count'],
        $customer['has_appointment'] ? 'Yes' : 'No',
        $customer['total_messages'] ?? 0,
        $customer['total_appointments'] ?? 0,
        $customer['updated_at']
    ]), ',', '"', '');
}

fclose($output);
exit();
?>

