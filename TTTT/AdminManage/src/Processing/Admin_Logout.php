<?php
/**
 * Admin Logout Handler
 * CRITICAL: Properly logs out admin users and clears ONLY admin-specific sessions
 * This preserves User UI sessions (user, user_role, IDNguoiDung) to maintain complete separation
 * UI login session remains unaffected when logging out from Admin panel
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CRITICAL: Clear ONLY admin-specific session variables (preserve User UI sessions)
// This ensures UI login session remains intact when logging out from Admin panel
// Admin session variables: admin_user, admin_id, admin_role, admin_is_admin
// UI session variables (NOT cleared): user, user_role, IDNguoiDung
unset($_SESSION['admin_user']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_is_admin']);

// Redirect to admin login
header("Location: ../View/Admin_Login.php");
exit();
?>

