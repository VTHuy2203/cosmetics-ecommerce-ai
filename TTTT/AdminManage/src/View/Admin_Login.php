<?php
// Skip admin access check for login page
$skip_admin_check = true;
include("../DB_Connect.php");
// session_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }


// If already logged in as admin/manager/staff, redirect to appropriate dashboard
// Check ADMIN-SPECIFIC session variables (completely separate from UI session)
if (isset($_SESSION['admin_role']) && isset($_SESSION['admin_user'])) {
    if ($_SESSION['admin_role'] === 'employee') {
        header("Location: ../Users/Staff_Dashboard.php");
        exit();
    } else if ($_SESSION['admin_role'] === 'admin' || $_SESSION['admin_role'] === 'manager') {
        header("Location: ../Oders/All_Oders.php");
        exit();
    }
}

$error = '';

if (isset($_POST['username']) && isset($_POST['password']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Check if Role column exists
    $checkCol = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'Role'");
    $colExists = false;
    if ($checkCol) {
        $colRow = mysqli_fetch_assoc($checkCol);
        $colExists = intval($colRow['c']) > 0;
    }
    if (!$colExists) {
        @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `Role` ENUM('customer','shipper','manager','admin') NOT NULL DEFAULT 'customer' AFTER `DiaChi`");
    }
    
    // Check admin/manager/staff credentials for admin panel access
    // Allow admin, manager, and employee (staff) roles to login via admin panel
    $sql_login = "SELECT * FROM khachhang WHERE TenDangNhap = '$username' AND MatKhau = '$password' AND (Role = 'admin' OR Role = 'manager' OR Role = 'employee')";
    $result = mysqli_query($conn, $sql_login);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        // Use ADMIN-SPECIFIC session variables to avoid conflicts with User UI
        // This creates a completely separate session from UI login
        $_SESSION['admin_user'] = $row['TenDangNhap'];
        $_SESSION['admin_id'] = $row['ID_KhachHang'];
        $_SESSION['admin_role'] = $row['Role']; // This will be 'admin', 'manager', or 'employee'
        
        // Set admin_is_admin only for actual admin/manager roles
        // Staff (employee) will have admin_role='employee' but admin_is_admin=false
        if ($row['Role'] === 'admin' || $row['Role'] === 'manager') {
            $_SESSION['admin_is_admin'] = true;
        } else {
            // Staff role - clear any existing admin_is_admin flag
            unset($_SESSION['admin_is_admin']);
        }
        
        // Redirect staff to staff dashboard, admin/manager to orders
        if ($row['Role'] === 'employee') {
            header("Location: ../Users/Staff_Dashboard.php");
        } else {
            header("Location: ../Oders/All_Oders.php");
        }
        exit();
    } else {
        // If login fails and username is 'admin', try to auto-create admin account
        if ($username === 'admin' && $password === '123admin') {
            // Auto-create admin account if it doesn't exist
            $check_admin = mysqli_query($conn, "SELECT * FROM khachhang WHERE TenDangNhap = 'admin'");
            if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
                // Create admin account
                $insert_sql = "INSERT INTO khachhang (TenDangNhap, MatKhau, HoVaTen, Email, SDT, DiaChi, Role) 
                               VALUES ('admin', '123admin', 'Administrator', 'admin@moshop.com', '0000000000', 'Admin Office', 'admin')";
                    if (mysqli_query($conn, $insert_sql)) {
                        // Retry login
                        $result = mysqli_query($conn, $sql_login);
                        if ($result && mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_array($result);
                            // Use ADMIN-SPECIFIC session variables to avoid conflicts with User UI
                            $_SESSION['admin_user'] = $row['TenDangNhap'];
                            $_SESSION['admin_id'] = $row['ID_KhachHang'];
                            $_SESSION['admin_role'] = $row['Role'];
                            if ($row['Role'] === 'admin' || $row['Role'] === 'manager') {
                                $_SESSION['admin_is_admin'] = true;
                            } else {
                                unset($_SESSION['admin_is_admin']);
                            }
                            
                            if ($row['Role'] === 'employee') {
                                header("Location: ../Users/Staff_Dashboard.php");
                            } else {
                                header("Location: ../Oders/All_Oders.php");
                            }
                            exit();
                        }
                    }
            }
        }
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - Mơ Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h3><i class="fas fa-shield-alt"></i> Admin Login</h3>
            <p class="mb-0">Mơ Shop - Quản trị hệ thống</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted small mb-2">
                    <strong>Default Admin Credentials:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>123admin</code>
                </p>
                <a href="../../WebBanHangMyPham/src/View/index.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left"></i> Về trang chủ
                </a>
                <br>
                <a href="../Processing/Create_Default_Admin.php" class="text-primary text-decoration-none small">
                    <i class="fas fa-cog"></i> Create/Reset Admin Account
                </a>
            </div>
        </div>
    </div>
</body>
</html>

