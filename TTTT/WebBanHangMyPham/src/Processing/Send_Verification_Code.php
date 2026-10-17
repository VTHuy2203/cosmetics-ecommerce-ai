<?php
/**
 * Send email verification code
 */

include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';

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
    $smtp_user = $config['smtp_username'] ?? '';
    $smtp_pass = $config['smtp_password'] ?? '';
    $from_email = $config['from_email'] ?? 'no-reply@example.com';
    $from_name = $config['from_name'] ?? 'Mơ Shop';
    
    $subject = 'Mã xác nhận đăng ký tài khoản - Mơ Shop';
    $message = "Xin chào!\n\nCảm ơn bạn đã đăng ký tài khoản tại Mơ Shop.\n\nMã xác nhận của bạn là: $code\n\nHoặc truy cập: $code_link\n\nMã này có hiệu lực trong 30 phút.\n\nTrân trọng,\nĐội ngũ Mơ Shop";
    
    // Use PHP's mail() function with proper headers (works on most servers)
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
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
    require_csrf();
    $email = strtolower(trim((string) $_POST['email']));
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'invalid_email';
        exit();
    }
    if (!auth_rate_limit('registration-otp:' . $email, 5, 900)) {
        http_response_code(429);
        echo 'rate_limited';
        exit();
    }
    $emailLogId = substr(hash('sha256', $email), 0, 12);

    // Check for cooldown (30 seconds between resends as requested)
    $lastSend = $conn->prepare('SELECT created_at FROM email_verifications WHERE email = ? ORDER BY created_at DESC LIMIT 1');
    $lastSend->bind_param('s', $email);
    $lastSend->execute();
    $last_row = $lastSend->get_result()->fetch_assoc();
    if ($last_row) {
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
    
    $verification_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $insert = $conn->prepare(
        'INSERT INTO email_verifications (email, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
    );
    $insert->bind_param('ss', $email, $verification_code);
    if (!$insert->execute()) {
        error_log('Failed to insert OTP; email_ref=' . $emailLogId);
        echo 'error';
        exit();
    }

    // Update the session only after the durable record succeeds. A cooldown
    // rejection must never silently replace the code the customer received.
    $_SESSION['email_verification_code'] = $verification_code;
    $_SESSION['email_verification_email'] = $email;
    $_SESSION['email_verification_time'] = time();
    
    // Create direct link to view code (for development/testing)
    $code_link = rtrim(app_env('APP_URL', 'http://localhost/TTTT') ?? '', '/') . '/WebBanHangMyPham/src/View/Register.php';
    
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
            $mail->Host = (string) ($email_config['smtp_host'] ?? 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = (string) ($email_config['smtp_username'] ?? '');
            $mail->Password = (string) ($email_config['smtp_password'] ?? '');
            $mail->SMTPSecure = (string) ($email_config['smtp_secure'] ?? 'tls');
            $mail->Port = (int) ($email_config['port'] ?? 587);
                
                $mail->CharSet = 'UTF-8';
                $mail->SMTPDebug = 0; // Disable debug for production
                // $mail->Debugoutput = 'html';

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
                error_log('Email sent successfully via PHPMailer; email_ref=' . $emailLogId);
        } catch (\Exception $e) {
            $error_msg = isset($mail) && method_exists($mail, 'ErrorInfo') ? $mail->ErrorInfo : $e->getMessage();
            error_log('PHPMailer send failed; email_ref=' . $emailLogId);
            $email_sent = false;
        } catch (\Error $e) {
            error_log('PHPMailer runtime failed; email_ref=' . $emailLogId);
            $email_sent = false;
        } catch (\Throwable $e) {
            error_log('Email sending failed; email_ref=' . $emailLogId);
            $email_sent = false;
        }
    }
    
    // Fallback: Try simple SMTP using socket connection if PHPMailer failed
    if (!$email_sent) {
        error_log('PHPMailer failed, trying fallback SMTP; email_ref=' . $emailLogId);
        $email_sent = sendEmailViaSMTP($email, $verification_code, $code_link, $email_config);
        if ($email_sent) {
            error_log('Email sent successfully via fallback SMTP; email_ref=' . $emailLogId);
        }
    }
    
    // Try to send email using PHP mail() function if both PHPMailer and SMTP failed
    if (!$email_sent) {
        error_log('PHPMailer and SMTP failed, trying PHP mail; email_ref=' . $emailLogId);
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
        if (mail($email, $subject, $message, $headers)) {
            $email_sent = true;
            error_log('Email sent successfully via PHP mail; email_ref=' . $emailLogId);
        } else {
            error_log('PHP mail failed; email_ref=' . $emailLogId);
        }
    }
    
    // Return response
    if ($email_sent) {
        echo 'success';
    } else {
        error_log('All email sending methods failed; email_ref=' . $emailLogId);
        http_response_code(503);
        echo 'error';
    }
} else {
    http_response_code(405);
    echo 'error';
}
?>

