<?php
// Disable error display to prevent corrupting AJAX response
// Errors are still logged to error log file
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include("../DB_Connect.php");
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log all incoming POST data for debugging
error_log("Registration attempt - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Registration attempt - POST keys: " . implode(', ', array_keys($_POST)));
error_log("Registration attempt - TenDangNhap: " . (isset($_POST['TenDangNhap']) ? $_POST['TenDangNhap'] : 'NOT SET'));

if (
    isset($_POST['HoVaTen']) &&
    isset($_POST['Email']) &&
    isset($_POST['Sdt']) &&
    isset($_POST['DiaChi']) &&
    isset($_POST['TenDangNhap']) &&
    isset($_POST['MatKhau']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $HoVaTen = mysqli_real_escape_string($conn, $_POST['HoVaTen']);
    $Email = mysqli_real_escape_string($conn, $_POST['Email']);
    $Sdt = mysqli_real_escape_string($conn, $_POST['Sdt']);
    $DiaChi = mysqli_real_escape_string($conn, $_POST['DiaChi']);
    $TenDangNhap = mysqli_real_escape_string($conn, $_POST['TenDangNhap']);
    $MatKhau = mysqli_real_escape_string($conn, $_POST['MatKhau']);
    $isShipper = isset($_POST['is_shipper']) && $_POST['is_shipper'] == '1';
    $isEmployee = isset($_POST['is_employee']) && $_POST['is_employee'] == '1';
    $registerRole = isset($_POST['register_role']) ? $_POST['register_role'] : 'customer';
    $isStaff = ($isShipper || $isEmployee);
    $license = isset($_POST['license']) ? mysqli_real_escape_string($conn, $_POST['license']) : '';
    $vehicle = isset($_POST['vehicle']) ? mysqli_real_escape_string($conn, $_POST['vehicle']) : '';
    $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
    $hometown = isset($_POST['hometown']) ? mysqli_real_escape_string($conn, $_POST['hometown']) : '';
    $permanent_address = isset($_POST['permanent_address']) ? mysqli_real_escape_string($conn, $_POST['permanent_address']) : '';
    $temporary_address = isset($_POST['temporary_address']) ? mysqli_real_escape_string($conn, $_POST['temporary_address']) : '';
    $work_location = isset($_POST['work_location']) ? mysqli_real_escape_string($conn, $_POST['work_location']) : '';
    $verification_code = isset($_POST['verification_code']) ? mysqli_real_escape_string($conn, $_POST['verification_code']) : '';
    
    // Handle ID card image uploads (for both shipper and employee)
    $id_card_images = [];
    if ($isStaff && isset($_FILES['id_card_images']) && is_array($_FILES['id_card_images']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/shipper_id_cards/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        for ($i = 0; $i < count($_FILES['id_card_images']['name']); $i++) {
            if ($_FILES['id_card_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($_FILES['id_card_images']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $file_name = time() . '_' . $i . '_' . basename($_FILES['id_card_images']['name'][$i]);
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['id_card_images']['tmp_name'][$i], $target_file)) {
                        $id_card_images[] = 'uploads/shipper_id_cards/' . $file_name;
                    }
                }
            }
        }
    }

    // Verify email verification code
    if ($verification_code) {
        // Get current time for logging
        $current_time = time();
        
        // Check in database - code expires in 30 minutes
        // Use MySQL's NOW() for consistent timezone handling
        // Only check codes that haven't expired yet using MySQL's time comparison
        $code_check = mysqli_query($conn, "SELECT *, 
                                          UNIX_TIMESTAMP(created_at) as created_timestamp,
                                          UNIX_TIMESTAMP(expires_at) as expires_timestamp,
                                          UNIX_TIMESTAMP(NOW()) as server_timestamp
                                          FROM email_verifications 
                                          WHERE email = '$Email' 
                                          AND code = '$verification_code' 
                                          AND used = 0 
                                          AND expires_at > NOW()
                                          ORDER BY created_at DESC LIMIT 1");
        
        // Check for SQL errors
        if (!$code_check) {
            $sql_error = mysqli_error($conn);
            error_log("SQL Error in OTP verification: " . $sql_error);
            echo 'error';
            exit();
        }
        
        // Log verification attempt
        error_log("OTP Verification - Email: $Email, Code: $verification_code, Current time: $current_time");
        
        if (mysqli_num_rows($code_check) == 0) {
            // Code not found - check if it exists at all
            $expired_check = mysqli_query($conn, "SELECT *, 
                                                 UNIX_TIMESTAMP(created_at) as created_timestamp,
                                                 UNIX_TIMESTAMP(expires_at) as expires_timestamp,
                                                 UNIX_TIMESTAMP(NOW()) as server_timestamp
                                                 FROM email_verifications 
                                                 WHERE email = '$Email' 
                                                 AND code = '$verification_code' 
                                                 ORDER BY created_at DESC LIMIT 1");
            
            // Check for SQL errors
            if (!$expired_check) {
                $sql_error = mysqli_error($conn);
                error_log("SQL Error in expired check: " . $sql_error);
                echo 'error';
                exit();
            }
            
            if (mysqli_num_rows($expired_check) > 0) {
                $expired_row = mysqli_fetch_array($expired_check);
                
                // Log expiry details
                $created_ts = intval($expired_row['created_timestamp']);
                $expires_ts = intval($expired_row['expires_timestamp']);
                $server_now = intval($expired_row['server_timestamp']);
                $elapsed = $current_time - $created_ts;
                $remaining = $expires_ts - $current_time;
                
                error_log("OTP Expiry Check - Created: $created_ts, Expires: $expires_ts, Current: $current_time, Server NOW: $server_now, Elapsed: $elapsed seconds, Remaining: $remaining seconds, Used: " . $expired_row['used']);
                
                if ($expired_row['used'] == 1) {
                    echo 'code_used';
                } else {
                    // Check expiry using server-side calculation
                    if ($expires_ts > 0 && $current_time > $expires_ts) {
                        error_log("OTP Expired - Code expired $remaining seconds ago");
                        echo 'code_expired';
                    } else {
                        // Code exists but query didn't match - might be a timing issue
                        error_log("OTP Not Found - Code exists but query didn't match. Expires at: " . $expired_row['expires_at']);
                        echo 'code_expired';
                    }
                }
            } else {
                error_log("OTP Invalid - No code found for email: $Email, code: $verification_code");
                echo 'invalid_code';
            }
            exit();
        }
        
        // Code found - expiry already checked in SQL query (expires_at > NOW())
        // So if we get here, the code is valid and not expired
        $code_row = mysqli_fetch_array($code_check);
        $created_ts = intval($code_row['created_timestamp']);
        $expires_ts = intval($code_row['expires_timestamp']);
        $server_now = intval($code_row['server_timestamp']);
        $current_time = time();
        $elapsed = $current_time - $created_ts;
        $remaining = $expires_ts - $current_time;
        
        // Log verification details
        error_log("OTP Found - Created: $created_ts, Expires: $expires_ts, Current: $current_time, Server NOW: $server_now, Elapsed: $elapsed seconds, Remaining: $remaining seconds");
        
        // Code is valid (expiry already checked in SQL WHERE clause)
        
        // Code is valid - mark as used
        $update_result = mysqli_query($conn, "UPDATE email_verifications SET used = 1 WHERE id = " . intval($code_row['id']));
        if (!$update_result) {
            error_log("Failed to mark OTP as used: " . mysqli_error($conn));
        }
        error_log("OTP Verified Successfully - Code marked as used");
    } else {
        error_log("OTP Missing - No verification code provided");
        echo 'missing_code';
        exit();
    }

    //check tồn tại (check if username already exists)
    $sql_check = "SELECT * FROM khachhang WHERE TenDangNhap = '$TenDangNhap'";
    error_log("Registration - Checking username: '$TenDangNhap' with SQL: $sql_check");
    $result_check = mysqli_query($conn, $sql_check);

    if (!$result_check) {
        error_log("Error checking username existence: " . mysqli_error($conn));
        echo 'error';
        exit();
    }

    $num_rows = mysqli_num_rows($result_check);
    error_log("Registration - Username check result: $num_rows rows found for '$TenDangNhap'");

    if ($num_rows > 0) {
        // Log which existing user was found
        $existing_user = mysqli_fetch_array($result_check);
        error_log("Registration - Existing user found: ID=" . $existing_user['ID_KhachHang'] . ", TenDangNhap=" . $existing_user['TenDangNhap']);
        echo 'tontai';
        exit();
    }

    // Ensure role column exists (check first to avoid duplicate column error)
    $check_role = mysqli_query($conn, "SHOW COLUMNS FROM `khachhang` LIKE 'Role'");
    if (!$check_role || mysqli_num_rows($check_role) == 0) {
        @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `Role` ENUM('customer','shipper','employee','manager','admin') NOT NULL DEFAULT 'customer' AFTER `DiaChi`");
    } else {
        // Check if 'employee' is in the ENUM, if not, update it
        $column_info = mysqli_fetch_assoc($check_role);
        if (isset($column_info['Type']) && strpos($column_info['Type'], 'employee') === false) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` MODIFY `Role` ENUM('customer','shipper','employee','manager','admin') NOT NULL DEFAULT 'customer'");
        }
    }
    
    // Ensure shipper_requests table exists with new fields
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `shipper_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `license` VARCHAR(50) NOT NULL,
        `vehicle` VARCHAR(50) NOT NULL,
        `hometown` VARCHAR(255) NULL,
        `permanent_address` TEXT NULL,
        `temporary_address` TEXT NULL,
        `id_card_front` VARCHAR(255) NULL,
        `id_card_back` VARCHAR(255) NULL,
        `note` TEXT NULL,
        `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Add new columns if they don't exist (MySQL doesn't support IF NOT EXISTS in ALTER TABLE, so check first)
    $checkCols = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shipper_requests'");
    $existing_cols = [];
    if ($checkCols) {
        while ($col = mysqli_fetch_assoc($checkCols)) {
            $existing_cols[] = $col['COLUMN_NAME'];
        }
    }
    
    if (!in_array('hometown', $existing_cols)) {
        @mysqli_query($conn, "ALTER TABLE `shipper_requests` ADD COLUMN `hometown` VARCHAR(255) NULL AFTER `vehicle`");
    }
    if (!in_array('permanent_address', $existing_cols)) {
        @mysqli_query($conn, "ALTER TABLE `shipper_requests` ADD COLUMN `permanent_address` TEXT NULL AFTER `hometown`");
    }
    if (!in_array('temporary_address', $existing_cols)) {
        @mysqli_query($conn, "ALTER TABLE `shipper_requests` ADD COLUMN `temporary_address` TEXT NULL AFTER `permanent_address`");
    }
    if (!in_array('id_card_front', $existing_cols)) {
        @mysqli_query($conn, "ALTER TABLE `shipper_requests` ADD COLUMN `id_card_front` VARCHAR(255) NULL AFTER `temporary_address`");
    }
    if (!in_array('id_card_back', $existing_cols)) {
        @mysqli_query($conn, "ALTER TABLE `shipper_requests` ADD COLUMN `id_card_back` VARCHAR(255) NULL AFTER `id_card_front`");
    }
    if (!in_array('work_location', $existing_cols)) {
        @mysqli_query($conn, "ALTER TABLE `shipper_requests` ADD COLUMN `work_location` ENUM('in-store','remote') NULL AFTER `note`");
    }

    //thêm user (insert user)
    // Validate and sanitize role value to match ENUM
    $valid_roles = ['customer', 'shipper', 'employee', 'manager', 'admin'];
    $role = in_array($registerRole, $valid_roles) ? $registerRole : 'customer';
    $role = mysqli_real_escape_string($conn, $role); // Additional safety
    
    $sql_insert_user = "INSERT INTO `khachhang` (`ID_KhachHang`, `TenDangNhap`, `MatKhau`, `HoVaTen`, `Email`, `SDT`, `DiaChi`, `Role`) 
    VALUES (NULL, '$TenDangNhap', '$MatKhau', '$HoVaTen', '$Email', '$Sdt', '$DiaChi', '$role')";

    $result_insert_user = mysqli_query($conn, $sql_insert_user);
    
    if (!$result_insert_user) {
        $error_msg = mysqli_error($conn);
        error_log("Failed to insert user: " . $error_msg);
        echo 'error';
        exit();
    }

    $user_id = mysqli_insert_id($conn);
    error_log("User registered successfully - ID: $user_id, Role: $role");
    
    // Send WebSocket notification to Admin for new user registration
    if ($role === 'customer') {
        // Use the WebSocket_Push.php from WebSocketServer directory
        // Path: from Processing/ go up 3 levels to TTTT/, then into WebSocketServer/
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_new_user')) {
                websocket_push_new_user($user_id, $HoVaTen);
            }
        }
    }
    
    // If staff registration (shipper or employee), create shipper request
    // Vehicle not required anymore - simplified form
    if ($isStaff && $license) {
        $id_card_front = isset($id_card_images[0]) ? mysqli_real_escape_string($conn, $id_card_images[0]) : '';
        $id_card_back = isset($id_card_images[1]) ? mysqli_real_escape_string($conn, $id_card_images[1]) : '';
        
        // Use main address as permanent_address if not provided
        if (empty($permanent_address)) {
            $permanent_address = $DiaChi;
        }
        $work_location_escaped = mysqli_real_escape_string($conn, $work_location);
        $sql_insert_shipper = "INSERT INTO `shipper_requests` (`user_id`, `license`, `vehicle`, `hometown`, `permanent_address`, `temporary_address`, `id_card_front`, `id_card_back`, `note`, `work_location`, `status`) 
                               VALUES ('$user_id', '$license', '$vehicle', '$hometown', '$permanent_address', '$temporary_address', '$id_card_front', '$id_card_back', '$note', '$work_location_escaped', 'pending')";
        $shipper_result = mysqli_query($conn, $sql_insert_shipper);
        if ($shipper_result) {
            error_log("Shipper request created successfully for user ID: $user_id");
            // Don't show success yet - wait for approval
            echo ($isShipper ? 'shipper_pending' : 'employee_pending');
        } else {
            error_log("Failed to create shipper request: " . mysqli_error($conn));
            echo 'error';
        }
    } else {
        error_log("Regular customer registration successful for user ID: $user_id");
        echo 'success';
    }

} else {
    // Log why registration failed
    $missing_fields = [];
    if (!isset($_POST['HoVaTen'])) $missing_fields[] = 'HoVaTen';
    if (!isset($_POST['Email'])) $missing_fields[] = 'Email';
    if (!isset($_POST['Sdt'])) $missing_fields[] = 'Sdt';
    if (!isset($_POST['DiaChi'])) $missing_fields[] = 'DiaChi';
    if (!isset($_POST['TenDangNhap'])) $missing_fields[] = 'TenDangNhap';
    if (!isset($_POST['MatKhau'])) $missing_fields[] = 'MatKhau';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') $missing_fields[] = 'REQUEST_METHOD not POST';
    
    error_log("Registration failed - Missing fields: " . implode(', ', $missing_fields));
    error_log("Registration failed - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    echo 'error';
}
?>
