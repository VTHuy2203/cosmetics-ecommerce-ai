<?php
// Thay vì session_start() trần:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mơ Shop</title>
    <link rel="stylesheet" href="/TTTT/Web_VanPhongPham/LIB/Bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="../../JS/script_SliderCard.js" defer></script>
    <script src="../../JS/script_Header.js" defer></script>
    <script src="../../JS/scrip_Cart.js" defer></script>
    <script src="../../JS/scrip_nav.js" defer></script>
    <script>
        // trigger background lead scoring occasionally
        window.addEventListener('load', function(){
            try {
                setTimeout(function(){ fetch('../Processing/Score_Lead.php', {method:'POST'}); }, 3000);
            } catch(e){}
        });
    </script>

    <link rel="stylesheet" href="../../CSS/style_SliderCard.css">
    <link rel="stylesheet" href="../../CSS/style_Header.css">
    <link rel="stylesheet" href="../../CSS/style_SliderBanner.css">
    <link rel="stylesheet" href="../../CSS/style_Cart.css">
    <link rel="stylesheet" href="../../CSS/style_Navbar.css">
    <link rel="stylesheet" href="../../CSS/style_BannerHeader.css">
    <link rel="stylesheet" href="../../CSS/style_Detail_Product.css">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>


<script>
    <?php
        // BẢO ĐẢM CÓ SESSION
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Tạo UID ổn định cho chat
        $chat_uid = 0;
        if (isset($_SESSION['IDNguoiDung']) && $_SESSION['IDNguoiDung']) {
            $chat_uid = (int)$_SESSION['IDNguoiDung'];
        } elseif (isset($_SESSION['user']) && $_SESSION['user'] !== '') {
            // hash username -> số nguyên dương ổn định
            $chat_uid = sprintf("%u", crc32($_SESSION['user']));
        }

        // Tên hiển thị
        $chat_name = '';
        if (!empty($_SESSION['HoTen']))            $chat_name = $_SESSION['HoTen'];
        elseif (!empty($_SESSION['TenNguoiDung'])) $chat_name = $_SESSION['TenNguoiDung'];
        elseif (!empty($_SESSION['user']))         $chat_name = $_SESSION['user'];
    ?>
        // ==== Config cho widget ====
        window.CHAT_BACKEND_URL = "http://127.0.0.1:8010";
        window.CHAT_PHP_BASE    = "/TTTT/WebBanHangMyPham/src/Processing";
        window.CURRENT_USER_ID  = <?= (int)$chat_uid ?>;
        window.CURRENT_USER_NAME= <?= json_encode($chat_name ?: 'bạn', JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- ép tải lại JS nếu cần -->
<script src="/TTTT/WebBanHangMyPham/JS/chat_widget.js?v=20251103a" defer></script>

<!-- Realtime WebSocket Client -->
<script src="/TTTT/WebBanHangMyPham/JS/websocket_client.js"></script>
<script>
    // Initialize WebSocket with user role
    document.addEventListener('DOMContentLoaded', function() {
        <?php
        $user_role = 'user';
        $user_id = null;
        if (isset($_SESSION['IDNguoiDung'])) {
            $user_id = $_SESSION['IDNguoiDung'];
        }
        if (isset($_SESSION['user_role'])) {
            $user_role = $_SESSION['user_role'];
        } elseif (isset($_SESSION['Role'])) {
            $user_role = $_SESSION['Role'];
        }
        
        // Debug: Log role detection
        error_log("Head.php: Detected role = {$user_role}, user_id = " . ($user_id ?: 'null'));
        ?>
        console.log('🔌 Head.php: Initializing WebSocket with role:', '<?php echo $user_role; ?>', 'user_id:', <?php echo $user_id ?: 'null'; ?>);
        initRealtimeWebSocket('<?php echo $user_role; ?>', <?php echo $user_id ?: 'null'; ?>);
    });
</script>

</head>
