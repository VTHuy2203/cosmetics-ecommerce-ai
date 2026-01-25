<?php
/**
 * Export Customer Analysis to CSV
 * Only accessible by Manager and Admin roles
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_role = $_SESSION['admin_role'] ?? null;
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

// Check permission - Manager, Admin, or admin_is_admin session can export
if ($admin_role !== 'manager' && $admin_role !== 'admin' && !$is_admin) {
    // Check if user has IDNguoiDung (might be admin without role set)
    if (!isset($_SESSION['IDNguoiDung']) && !isset($_SESSION['user'])) {
        die('Access denied. Only Manager and Admin can export data.');
    }
}

include("../DB_Connect.php");

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webmipham1;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get filter
$filter = $_GET['label'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where = [];
$params = [];

if ($filter !== 'all') {
    $where[] = "ca.label = ?";
    $params[] = $filter;
}

if ($search) {
    $where[] = "(k.HoVaTen LIKE ? OR k.Email LIKE ? OR k.SDT LIKE ? OR ca.last_need LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($date_from) {
    $where[] = "ca.updated_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where[] = "ca.updated_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get customers
$sql = "SELECT 
    ca.id,
    ca.user_id,
    ca.session_id,
    ca.label,
    ca.product_interest,
    ca.last_need,
    ca.message_count,
    ca.has_appointment,
    ca.updated_at,
    k.ID_KhachHang,
    k.HoVaTen,
    k.Email,
    k.SDT,
    k.DiaChi,
    COUNT(DISTINCT cm.id) as total_messages,
    COUNT(DISTINCT appt.id) as total_appointments
FROM (
    SELECT ca1.*
    FROM customer_analysis ca1
    INNER JOIN (
        SELECT user_id, MAX(updated_at) as max_updated
        FROM customer_analysis
        WHERE user_id IS NOT NULL
        GROUP BY user_id
    ) ca2 ON ca1.user_id = ca2.user_id AND ca1.updated_at = ca2.max_updated
    UNION
    SELECT ca3.*
    FROM customer_analysis ca3
    WHERE ca3.user_id IS NULL
) ca
LEFT JOIN khachhang k ON ca.user_id = k.ID_KhachHang
LEFT JOIN chat_messages cm ON (ca.user_id = cm.user_id AND ca.user_id IS NOT NULL) OR (ca.user_id IS NULL AND ca.session_id = cm.session_id)
LEFT JOIN chat_appointments appt ON (ca.user_id = appt.user_id AND ca.user_id IS NOT NULL) OR (ca.user_id IS NULL AND ca.session_id = appt.session_id)
{$whereClause}
GROUP BY ca.id, ca.user_id, ca.session_id, ca.label, ca.product_interest, ca.last_need, ca.message_count, ca.has_appointment, ca.updated_at, k.ID_KhachHang, k.HoVaTen, k.Email, k.SDT, k.DiaChi
ORDER BY ca.updated_at DESC
LIMIT 1000";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customer_analysis_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

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
    'Product Interest',
    'Last Need',
    'Message Count',
    'Has Appointment',
    'Total Messages',
    'Total Appointments',
    'Updated At'
], ',');

// Write data rows
foreach ($customers as $customer) {
    fputcsv($output, [
        $customer['id'],
        $customer['user_id'] ?? 'N/A',
        $customer['session_id'],
        $customer['HoVaTen'] ?? 'Guest',
        $customer['Email'] ?? 'N/A',
        $customer['SDT'] ?? 'N/A',
        $customer['DiaChi'] ?? 'N/A',
        ucfirst($customer['label']),
        $customer['product_interest'] ?? '',
        $customer['last_need'] ?? '',
        $customer['message_count'],
        $customer['has_appointment'] ? 'Yes' : 'No',
        $customer['total_messages'] ?? 0,
        $customer['total_appointments'] ?? 0,
        $customer['updated_at']
    ], ',');
}

fclose($output);
exit();
?>

