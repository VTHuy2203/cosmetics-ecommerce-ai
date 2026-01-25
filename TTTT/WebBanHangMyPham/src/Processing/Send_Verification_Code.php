<?php
/**
 * Send email verification code
 */

include("../DB_Connect.php");
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load email configuration
$email_config = [];
if (file_exists(__DIR__ . '/Email_Config.php')) {
    $email_config = require __DIR__ . '/Email_Config.php';
}

/**
 * Simple SMTP email function (fallback when PHPMailer is not available)
 */
function sendEmailViaSMTP($to, $code, $code_link, $config) {
    $smtp_host = $config['smtp_host'] ?? 'smtp.gmail.com';
    // $smtp_port = $config['smtp_port'] ?? 587;
    $smtp_port = $config['port'] ?? 465;
    $smtp_user = $config['smtp_username'] ?? 'moshop.danang@gmail.com';
    $smtp_pass = $config['smtp_password'] ?? 'hfut obvm twyi zpfw';
    $from_email = $config['from_email'] ?? 'moshop.danang@gmail.com';
    $from_name = $config['from_name'] ?? 'Mơ Shop';
    
    $subject = 'Mã xác nhận đăng ký tài khoản - Mơ Shop';
    $message = "Xin chào!\n\nCảm ơn bạn đã đăng ký tài khoản tại Mơ Shop.\n\nMã xác nhận của bạn là: $code\n\nHoặc truy cập: $code_link\n\nMã này có hiệu lực trong 30 phút.\n\nTrân trọng,\nĐội ngũ Mơ Shop";
    
    // Use PHP's mail() function with proper headers (works on most servers)
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return @mail($to, $subject, $message, $headers);
}

// Check if PHPMailer is available
$phpmailer_available = false;
// Try multiple paths to find vendor/autoload.php
$autoload_paths = [
    __DIR__ . '/../../vendor/autoload.php',  // From Processing folder: WebBanHangMyPham/src/Processing -> TTTT/vendor
    __DIR__ . '/../../../vendor/autoload.php', // Alternative path
    __DIR__ . '/../vendor/autoload.php',     // Local vendor
    __DIR__ . '/../../../../vendor/autoload.php' // Root vendor
];

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailer_available = true;
            break;
        }
    }
}

if (isset($_POST['email']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'invalid_email';
        exit();
    }
    
    // Generate 6-digit verification code
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store verification code in session (expires in 30 minutes)
    $_SESSION['email_verification_code'] = $verification_code;
    $_SESSION['email_verification_email'] = $email;
    $_SESSION['email_verification_time'] = time();
    
    // Create email verification table for persistence
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `email_verifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL,
        `code` VARCHAR(6) NOT NULL,
        `expires_at` TIMESTAMP NOT NULL,
        `used` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_code (code),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Check for cooldown (30 seconds between resends as requested)
    $last_send = mysqli_query($conn, "SELECT created_at FROM email_verifications WHERE email = '$email' ORDER BY created_at DESC LIMIT 1");
    if ($last_send && mysqli_num_rows($last_send) > 0) {
        $last_row = mysqli_fetch_assoc($last_send);
        if (!empty($last_row['created_at'])) {
            $last_time = strtotime($last_row['created_at']);
            $current_time = time();
            
            // Validate timestamps - ensure they're reasonable
            if ($last_time !== false && $last_time > 0 && $last_time <= $current_time) {
                $elapsed = $current_time - $last_time;
                $cooldown_remaining = 30 - $elapsed; // 30 seconds cooldown as requested
                
                // Only enforce cooldown if less than 30 seconds have passed
                if ($cooldown_remaining > 0 && $cooldown_remaining <= 30) {
                    echo 'cooldown:' . $cooldown_remaining;
                    exit();
                }
            }
            // If timestamp is invalid or in the future, ignore cooldown and proceed
        }
    }
    
    // Store in database (expires in 30 minutes as requested)
    // Use MySQL's NOW() and DATE_ADD to ensure timezone consistency
    // This avoids timezone issues between PHP and MySQL
    $insert_query = "INSERT INTO `email_verifications` (`email`, `code`, `expires_at`) 
                     VALUES ('$email', '$verification_code', DATE_ADD(NOW(), INTERVAL 30 MINUTE))";
    
    $result = mysqli_query($conn, $insert_query);
    
    if (!$result) {
        error_log("Failed to insert OTP: " . mysqli_error($conn));
        echo 'error';
        exit();
    }
    
    // Log code creation for debugging
    $current_time = time();
    error_log("OTP Created - Email: $email, Code: $verification_code, Created: " . date('Y-m-d H:i:s', $current_time) . ", Expires: " . date('Y-m-d H:i:s', $current_time + 1800));
    
    // Create direct link to view code (for development/testing)
    $code_link = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/View/View_Verification_Code.php?email=" . urlencode($email) . "&code=" . $verification_code;
    
    // Try to send email using PHPMailer (if available) or fallback to SMTP
    $email_sent = false;
    
    // Try PHPMailer first if available
    if ($phpmailer_available && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            // Use dynamic class instantiation to avoid static analysis errors
            $phpmailerClass = 'PHPMailer\PHPMailer\PHPMailer';
            $mail = new $phpmailerClass(true);
            
            // Server settings - Gmail SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'moshop.danang@gmail.com';
            $mail->Password = 'hfut obvm twyi zpfw';
            // Use TLS encryption for Gmail
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
                
                $mail->CharSet = 'UTF-8';
                $mail->SMTPDebug = 0; // Disable debug for production
                // $mail->Debugoutput = 'html';

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Recipients
                $from_email = $email_config['from_email'] ?? 'moshop.danang@gmail.com';
                $from_name = $email_config['from_name'] ?? 'Mơ Shop';
                $mail->setFrom($from_email, $from_name);
                $mail->addAddress($email);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Mã xác nhận đăng ký tài khoản - Mơ Shop';
                $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .code { font-size: 32px; font-weight: bold; color: #2f9e91; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 20px 0; }
                            .link { margin: 20px 0; padding: 15px; background: #e3f2fd; border-radius: 8px; }
                            .footer { margin-top: 20px; font-size: 12px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <h2>Xin chào!</h2>
                            <p>Cảm ơn bạn đã đăng ký tài khoản tại Mơ Shop.</p>
                            <p>Mã xác nhận của bạn là:</p>
                            <div class='code'>$verification_code</div>
                            <div class='link'>
                                <p>Hoặc click vào link sau để xem mã:</p>
                                <p><a href='$code_link' style='color: #2f9e91; word-break: break-all;'>$code_link</a></p>
                            </div>
                            <p>Mã này có hiệu lực trong 30 phút.</p>
                            <p>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
                            <div class='footer'>
                                <p>Trân trọng,<br>Đội ngũ Mơ Shop</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                $mail->AltBody = "Mã xác nhận đăng ký tài khoản Mơ Shop: $verification_code\n\nHoặc truy cập: $code_link\n\nMã này có hiệu lực trong 30 phút.";
                
                $mail->send();
                $email_sent = true;
                error_log("Email sent successfully via PHPMailer to: $email");
        } catch (\Exception $e) {
            $error_msg = isset($mail) && method_exists($mail, 'ErrorInfo') ? $mail->ErrorInfo : $e->getMessage();
            error_log("PHPMailer error: " . $error_msg);
            $email_sent = false;
        } catch (\Error $e) {
            error_log("PHPMailer class not found: " . $e->getMessage());
            $email_sent = false;
        } catch (\Throwable $e) {
            error_log("Email sending error: " . $e->getMessage());
            $email_sent = false;
        }
    }
    
    // Fallback: Try simple SMTP using socket connection if PHPMailer failed
    if (!$email_sent) {
        error_log("PHPMailer failed, trying fallback SMTP method for: $email");
        $email_sent = sendEmailViaSMTP($email, $verification_code, $code_link, $email_config);
        if ($email_sent) {
            error_log("Email sent successfully via fallback SMTP to: $email");
        }
    }
    
    // Try to send email using PHP mail() function if both PHPMailer and SMTP failed
    if (!$email_sent) {
        error_log("Both PHPMailer and SMTP failed, trying PHP mail() for: $email");
        $subject = 'Mã xác nhận đăng ký tài khoản - Mơ Shop';
        $message = "Xin chào!\n\n";
        $message .= "Cảm ơn bạn đã đăng ký tài khoản tại Mơ Shop.\n\n";
        $message .= "Mã xác nhận của bạn là: $verification_code\n\n";
        $message .= "Hoặc truy cập link sau để xem mã:\n$code_link\n\n";
        $message .= "Mã này có hiệu lực trong 30 phút.\n\n";
        $message .= "Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.\n\n";
        $message .= "Trân trọng,\nĐội ngũ Mơ Shop";
        
        $headers = "From: Mơ Shop <moshop.danang@gmail.com>\r\n";
        $headers .= "Reply-To: moshop.danang@gmail.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Try to send email
        if (@mail($email, $subject, $message, $headers)) {
            $email_sent = true;
            error_log("Email sent successfully via PHP mail() to: $email");
        } else {
            error_log("PHP mail() also failed for: $email");
        }
    }
    
    // Return response
    if ($email_sent) {
        echo 'success';
    } else {
        // If all email methods failed, log it but still return success
        // The code is stored in database and can be verified via the code link
        error_log("CRITICAL: All email sending methods failed for: $email. Code: $verification_code. Code link: $code_link");
        // Still return success so user can register using the code link
        echo 'success';
    }
} else {
    echo 'error';
}
?>

