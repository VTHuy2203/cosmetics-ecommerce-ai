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

if (!isset($_SESSION)) {
    session_start();
}

// Allow localhost full admin access ONLY if no user is logged in
$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || 
                 $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
                 strpos($_SERVER['HTTP_HOST'], 'localhost:') !== false ||
                 strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') !== false);

// CRITICAL: Admin panel access is determined SOLELY by admin session variables
// UI session (user, user_role, IDNguoiDung) should NEVER affect admin panel access
// This ensures complete separation between UI login and Admin login

// Get ADMIN-SPECIFIC session info (separate from User UI sessions)
$admin_role = $_SESSION['admin_role'] ?? null;
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;
$has_admin_session = isset($_SESSION['admin_user']) || isset($_SESSION['admin_id']);

// CRITICAL: We completely ignore UI session variables for admin access decisions
// UI login should NEVER affect Admin panel access
// Only admin session variables (admin_role, admin_user, admin_id, admin_is_admin) matter here

$current_page = basename($_SERVER['PHP_SELF']);

// Define allowed pages for each role
$shipper_allowed_pages = ['Shipper_View_Orders.php', 'Admin_Logout.php'];
// Staff (employee role via admin login) can access their dashboard and approve orders
$staff_allowed_pages = ['Staff_Dashboard.php', 'Manage_Users.php', 'Admin_Customer_Analysis.php', 'Admin_Guest_Customers.php', 'Approve_Oders.php', 'All_Oders.php', 'View_Oder_Detail.php', 'Admin_Logout.php'];
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

// PRIORITY 1: Allow localhost admin access - grant full access on localhost
// This allows direct access without login when on localhost
if ($is_localhost) {
    // On localhost, always grant admin access if no admin session exists
    if (!$has_admin_session) {
        // Grant admin access for localhost
        $_SESSION['admin_is_admin'] = true;
        $_SESSION['admin_role'] = 'admin';
        $_SESSION['admin_user'] = 'localhost_admin';
        $_SESSION['admin_id'] = 0;
    }
    $admin_role = $_SESSION['admin_role'];
    $is_admin = ($_SESSION['admin_role'] === 'admin' || $_SESSION['admin_role'] === 'manager');
    $has_admin_session = true;
}

// PRIORITY 2: Check if user has valid admin session
// Allow admin, manager, or employee (staff) roles via admin login
// Only check this if NOT on localhost (localhost already handled above)
if (!$is_localhost) {
    // Must have admin session with valid role (admin, manager, or employee)
    if (!$has_admin_session || ($admin_role !== 'admin' && $admin_role !== 'manager' && $admin_role !== 'employee')) {
        // No valid admin session - redirect to admin login
        $login_url = '/TTTT/AdminManage/src/View/Admin_Login.php';
        header("Location: $login_url");
        exit();
    }
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

