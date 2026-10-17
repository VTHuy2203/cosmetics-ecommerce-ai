<?php require_once dirname(__DIR__, 3) . '/config/security.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title>Mơ Shop</title>
    <link rel="stylesheet" href="/TTTT/WebBanHangMyPham/LIB/Bootstrap/css/bootstrap.min.css">
    <script src="/TTTT/WebBanHangMyPham/LIB/Bootstrap/js/bootstrap.bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="../../JS/script_SliderCard.js" defer></script>
    <script src="../../JS/script_Header.js" defer></script>
    <script src="../../JS/scrip_Cart.js" defer></script>
    <script src="../../JS/scrip_nav.js" defer></script>
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
        window.CHAT_PHP_BASE    = "/TTTT/WebBanHangMyPham/src/Processing";
        window.CURRENT_USER_ID  = <?= (int)$chat_uid ?>;
        window.CURRENT_USER_NAME= <?= json_encode($chat_name ?: 'bạn', JSON_UNESCAPED_UNICODE) ?>;
        window.CHAT_CSRF_TOKEN  = <?= json_encode(csrf_token()) ?>;
        window.CHAT_PERSONAL_URL= "/TTTT/WebBanHangMyPham/src/View/Personal.php";
</script>

<?php if (app_bool('AI_CHAT_ENABLED')): ?>
<script src="/TTTT/WebBanHangMyPham/JS/chat_widget.js?v=20261017-1" defer></script>
<?php endif; ?>

<!-- Realtime WebSocket Client -->
<?php if ((app_env('REALTIME_WS_URL', '') ?? '') !== ''): ?>
<script src="/TTTT/WebBanHangMyPham/JS/websocket_client.js"></script>
<script>
    window.WEBSOCKET_URL = <?= json_encode(app_env('REALTIME_WS_URL'), JSON_UNESCAPED_SLASHES) ?>;
    window.REALTIME_TICKET_URL = "/TTTT/WebBanHangMyPham/src/Processing/Realtime_Ticket.php";
    window.REALTIME_CONTEXT = "customer";
    window.REALTIME_CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
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
        
        ?>
        initRealtimeWebSocket('<?php echo $user_role; ?>', <?php echo $user_id ?: 'null'; ?>);
    });
</script>
<?php endif; ?>

</head>
