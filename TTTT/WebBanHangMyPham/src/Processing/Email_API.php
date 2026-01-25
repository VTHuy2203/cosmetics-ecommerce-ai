<?php
/**
 * Email API - Send verification code via external API or direct link
 * This creates a simple API endpoint that can be called to send emails
 */

header('Content-Type: application/json; charset=utf-8');
include("../DB_Connect.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = isset($input['email']) ? mysqli_real_escape_string($conn, $input['email']) : '';
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email']);
        exit();
    }
    
    // Tạo mã xác minh 6 chữ số
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Tạo bảng xác minh email
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
    
    // Store in database (expires in 30 minutes)
    $expires_at = date('Y-m-d H:i:s', time() + 1800);
    mysqli_query($conn, "INSERT INTO `email_verifications` (`email`, `code`, `expires_at`) 
                        VALUES ('$email', '$verification_code', '$expires_at')");
    
    // Phương án 1: Sử dụng API của EmailJS (dịch vụ miễn phí)
    // You can sign up at https://www.emailjs.com/ and get an API key
    $emailjs_service_id = 'your_service_id'; // Thay thế bằng ID dịch vụ EmailJS của bạn
    $emailjs_template_id = 'your_template_id'; // Thay thế bằng ID mẫu EmailJS của bạn
    $emailjs_user_id = 'your_user_id'; // Thay thế bằng ID người dùng EmailJS của bạn
    
    // Phương án 2: Sử dụng API của SendGrid (yêu cầu khóa API)
    $sendgrid_api_key = ''; // Thêm khóa API SendGrid của bạn vào đây
    
    // Phương án 3: Tạo liên kết trực tiếp để xem mã nguồn (dành cho mục đích phát triển/kiểm thử)
    $code_link = "http://" . $_SERVER['HTTP_HOST'] . "/TTTT/WebBanHangMyPham/src/View/View_Verification_Code.php?email=" . urlencode($email) . "&code=" . $verification_code;
    
    // Try EmailJS first
    if ($emailjs_service_id !== 'your_service_id' && $emailjs_user_id !== 'your_user_id') {
        $emailjs_url = "https://api.emailjs.com/api/v1.0/email/send";
        $emailjs_data = [
            'service_id' => $emailjs_service_id,
            'template_id' => $emailjs_template_id,
            'user_id' => $emailjs_user_id,
            'template_params' => [
                'to_email' => $email,
                'verification_code' => $verification_code,
                'code_link' => $code_link
            ]
        ];
        
        $ch = curl_init($emailjs_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailjs_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
            exit();
        }
    }
    
    // Try SendGrid if configured
    if (!empty($sendgrid_api_key)) {
        $sendgrid_url = "https://api.sendgrid.com/v3/mail/send";
        $sendgrid_data = [
            'personalizations' => [[
                'to' => [['email' => $email]],
                'subject' => 'Mã xác nhận đăng ký tài khoản - Mơ Shop'
            ]],
            'from' => ['email' => 'noreply@moshop.com', 'name' => 'Mơ Shop'],
            'content' => [[
                'type' => 'text/html',
                'value' => "
                    <html>
                    <body>
                        <h2>Xin chào!</h2>
                        <p>Mã xác nhận đăng ký tài khoản Mơ Shop của bạn là:</p>
                        <h1 style='color: #2f9e91;'>$verification_code</h1>
                        <p>Mã này có hiệu lực trong 30 phút.</p>
                        <p>Hoặc click vào link sau để xem mã: <a href='$code_link'>Xem mã xác nhận</a></p>
                    </body>
                    </html>
                "
            ]]
        ];
        
        $ch = curl_init($sendgrid_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendgrid_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $sendgrid_api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
            exit();
        }
    }
    
    // Fallback: Trả về liên kết mã để phát triển.
    echo json_encode([
        'success' => true, 
        'message' => 'Verification code generated. For development, use this link:',
        'code_link' => $code_link,
        'code' => $verification_code // Chỉ dành cho mục đích phát triển - loại bỏ khi sử dụng trong môi trường sản xuất.
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>

