<?php
/**
 * Admin Layout Head
 * Includes session start and access control
 */
if (!isset($_SESSION)) {
    session_start();
}

// Include admin access check (blocks shippers and ensures proper access)
include(__DIR__ . '/Admin_Access_Check.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Mơ Shop - ADMIN</title>

    <!-- Custom fonts for this template-->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
    
    <!-- Realtime WebSocket Client -->
    <script src="/TTTT/WebBanHangMyPham/JS/websocket_client.js"></script>
    <script>
        // Initialize WebSocket with admin role
        document.addEventListener('DOMContentLoaded', function() {
            <?php
            $admin_role = 'admin';
            $admin_id = null;
            if (isset($_SESSION['admin_role'])) {
                $admin_role = $_SESSION['admin_role'];
            }
            if (isset($_SESSION['admin_id'])) {
                $admin_id = $_SESSION['admin_id'];
            } elseif (isset($_SESSION['IDNguoiDung'])) {
                $admin_id = $_SESSION['IDNguoiDung'];
            }
            
            // Map admin_role to WebSocket role
            // 'employee' in admin session should map to 'staff' for WebSocket
            $ws_role = $admin_role;
            if ($admin_role === 'employee') {
                $ws_role = 'staff';
            }
            
            // Debug: Log role detection
            error_log("headHTML.php: admin_role = {$admin_role}, ws_role = {$ws_role}, admin_id = " . ($admin_id ?: 'null'));
            ?>
            console.log('🔌 headHTML.php: Initializing WebSocket with role:', '<?php echo $ws_role; ?>', 'user_id:', <?php echo $admin_id ?: 'null'; ?>);
            initRealtimeWebSocket('<?php echo $ws_role; ?>', <?php echo $admin_id ?: 'null'; ?>);
        });
    </script>
</head>