<?php
/**
 * Admin Access Control
 * Ensures proper separation between Admin UI and User UI sessions
 * This file should be included at the top of all admin pages
 * 
 * IMPORTANT: Skip this check for login pages by setting $skip_admin_check = true before including
 */

// Skip check if explicitly requested (e.g., for login pages)
if (isset($skip_admin_check) && $skip_admin_check === true) {
    return;
}

// config/security.php is loaded before this file and owns the shared
// MOSHOPSESSID session. Never start PHP's default PHPSESSID here.

// CRITICAL: Admin panel access is determined SOLELY by admin session variables
// UI session (user, user_role, IDNguoiDung) should NEVER affect admin panel access
// This ensures complete separation between UI login and Admin login

// Get ADMIN-SPECIFIC session info (separate from User UI sessions)
$admin_role = $_SESSION['admin_role'] ?? null;
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;
$has_admin_session = isset($_SESSION['admin_user'], $_SESSION['admin_id'], $_SESSION['admin_role']);

// Re-read the role on every protected request so disabling or demoting an
// account takes effect immediately instead of lasting until session expiry.
if ($has_admin_session) {
    try {
        $accessDatabase = app_pdo();
        $accessStatement = $accessDatabase->prepare(
            "SELECT Role FROM khachhang WHERE ID_KhachHang = ? AND TenDangNhap = ? AND Role IN ('admin','manager','employee') LIMIT 1"
        );
        $accessStatement->execute([(int) $_SESSION['admin_id'], (string) $_SESSION['admin_user']]);
        $databaseRole = (string) ($accessStatement->fetchColumn() ?: '');
        if ($databaseRole === '' || !hash_equals((string) $admin_role, $databaseRole)) {
            unset($_SESSION['admin_user'], $_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_is_admin']);
            $has_admin_session = false;
            $admin_role = null;
        }
    } catch (Throwable $error) {
        error_log('Admin access role verification failed; code=' . (int) $error->getCode());
        unset($_SESSION['admin_user'], $_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_is_admin']);
        $has_admin_session = false;
        $admin_role = null;
    }
}

// CRITICAL: We completely ignore UI session variables for admin access decisions
// UI login should NEVER affect Admin panel access
// Only admin session variables (admin_role, admin_user, admin_id, admin_is_admin) matter here

$current_page = basename($_SERVER['PHP_SELF']);

// Define allowed pages for each role
$shipper_allowed_pages = ['Shipper_View_Orders.php', 'Admin_Logout.php'];
// Staff (employee role via admin login) can access their dashboard and approve orders
$staff_allowed_pages = [
    'Staff_Dashboard.php',
    'Staff_Order_Management.php', 'Approve_Oders.php', 'All_Oders.php', 'View_Oder_Detail.php',
    'Order_Status_Action.php',
    'Staff_Canceled_Orders.php', 'Staff_Return_Refund_Requests.php', 'Staff_Return_Refund_Detail.php',
    'Manage_Users.php', 'Customer_Detail.php', 'Admin_Customer_Analysis.php',
    'Admin_Guest_Customers.php', 'Admin_Guest_Chat_History.php',
    'Export_Customer_Analysis_CSV.php', 'Export_Users_CSV.php',
    'Private_File.php', 'Admin_Logout.php',
    'Admin_Notifications.php',
];
// Admin-only order management pages (not accessible to staff)
$admin_only_order_pages = ['Prepare_Oders.php', 'Delete_Oder.php', 'Shipping_Oders.php', 'Success_Oders.php'];

// Check if current user is staff (via admin login, not UI login)
$is_staff_via_admin = ($admin_role === 'employee');

// CRITICAL: Staff can ONLY approve orders, not prepare/cancel/ship
// These pages are restricted to Admin/Manager only (not staff)
if (in_array($current_page, $admin_only_order_pages) && $is_staff_via_admin) {
    // Staff cannot access these order management pages
    header('Location: ../Users/Staff_Dashboard.php?error=unauthorized');
    exit();
}

// Restrict staff (via admin login) to staff-allowed pages only
if ($is_staff_via_admin && !in_array($current_page, $staff_allowed_pages)) {
    // Staff can only access staff-specific pages
    header('Location: ../Users/Staff_Dashboard.php?error=unauthorized');
    exit();
}

// Every environment, including localhost, requires a real authenticated session.
if (!$has_admin_session || !in_array($admin_role, ['admin', 'manager', 'employee'], true)) {
    header('Location: /TTTT/AdminManage/src/View/Admin_Login.php');
    exit();
}

// If we reach here, user has valid admin panel access
// Ensure admin_is_admin is set correctly based on role
if ($admin_role === 'admin' || $admin_role === 'manager') {
    $_SESSION['admin_is_admin'] = true;
} else if ($admin_role === 'employee') {
    // Staff role - ensure admin_is_admin is NOT set
    unset($_SESSION['admin_is_admin']);
}
?>
