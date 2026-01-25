<?php
/**
 * View Verification Code Page
 * For development/testing - displays verification code for an email
 */

include("../DB_Connect.php");

$email = $_GET['email'] ?? '';
$code = $_GET['code'] ?? '';

// Verify code from database
if ($email && $code) {
    $check = mysqli_query($conn, "SELECT * FROM email_verifications 
                                  WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' 
                                  AND code = '" . mysqli_real_escape_string($conn, $code) . "' 
                                  AND used = 0 
                                  AND expires_at > NOW() 
                                  ORDER BY created_at DESC LIMIT 1");
    
    if ($check && mysqli_num_rows($check) > 0) {
        $valid = true;
    } else {
        $valid = false;
    }
} else {
    $valid = false;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác nhận - Mơ Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #2f9e91 0%, #25877a 100%);
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            color: #2f9e91;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
            letter-spacing: 8px;
        }
        .email {
            color: #666;
            margin-bottom: 20px;
        }
        .note {
            color: #999;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($valid): ?>
            <h2>Mã xác nhận của bạn</h2>
            <p class="email">Email: <?php echo htmlspecialchars($email); ?></p>
            <div class="code"><?php echo htmlspecialchars($code); ?></div>
            <p class="note">Mã này có hiệu lực trong 10 phút.</p>
            <p class="note">Vui lòng quay lại trang đăng ký và nhập mã này.</p>
        <?php else: ?>
            <h2>Mã xác nhận không hợp lệ</h2>
            <p>Mã xác nhận không tồn tại hoặc đã hết hạn.</p>
            <p>Vui lòng yêu cầu mã mới.</p>
        <?php endif; ?>
    </div>
</body>
</html>

