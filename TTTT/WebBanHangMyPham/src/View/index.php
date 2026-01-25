<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (isset($_POST['KetThucSession']) || isset($_GET['logout'])) {
    // Only clear USER session variables, NOT admin ones
    // This prevents logging out Admin when User logs out
    if (session_status() === PHP_SESSION_ACTIVE) { 
        // Clear only User UI session variables
        unset($_SESSION['user']);
        unset($_SESSION['IDNguoiDung']);
        unset($_SESSION['user_role']);
        unset($_SESSION['is_admin']); // User UI's is_admin, not admin_is_admin
        unset($_SESSION['cart']);
        unset($_SESSION['sum_Total']);
        unset($_SESSION['HoTen']);
        unset($_SESSION['TenNguoiDung']);
        // Preserve admin session variables: admin_user, admin_id, admin_role, admin_is_admin
    }
    // XÓA COOKIE CHATBOT (rất quan trọng)
    setcookie('chat_session_id', '', time() - 3600, '/');
    header("Location: index.php");
    exit();
}
?>

<body class="bg-light">
    <!-- link scrip -->
    <?php include("../Layouts/Link.php"); ?>

    <!-- Banner header -->
    <?php include("../Layouts/Banner_Header.php"); ?>
    <!-- end Banner header -->

    <!-- header -->
    <?php include("../Layouts/Header_View.php"); ?>
    <!-- end header -->

    <!-- Navbar -->
    <?php include("../Layouts/Navbar.php"); ?>
    <!-- End Navbar -->

    <!-- Slider Banner -->
    <?php include("../Layouts/SliderBanner.php"); ?>
    <!-- End Slider Banner -->

    <!-- Category slider -->
    <?php include("../Layouts/CategorySlider.php"); ?>
    <!-- End Category slider -->

    <!-- Top Product -->
    <?php include("../Layouts/Top_Product.php"); ?>
    <!-- end Top Product -->

    <!-- New product -->
    <?php include("../Layouts/New_Product.php"); ?>
    <!-- End New product -->

    <!-- tin tức bài viết -->
    <?php include("../Layouts/News_Posts.php"); ?>
    <!-- End tin tức bài viết -->

    <!-- thương hiệu -->
    <?php include("../Layouts/Brand_Slider.php"); ?>
    <!-- End thương hiệu -->

    <!-- Contact -->
    <?php include("../Layouts/Form_Contact.php"); ?>
    <!-- end Contact -->

    <!-- footer -->
    <?php include("../Layouts/Footer.php"); ?>
    <!-- end footer -->

    <script>
    // Xử lý sự kiện nhấp vào nút "Add to Cart"
    function addToCart(IDSanPham) {
        $.ajax({
            type: 'POST',
            url: 'Cart.php',
            data: {
                IDSanPham: IDSanPham,
                addcart: 'addcart'
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    alert(response.message || 'Sản phẩm đã được thêm vào giỏ hàng.');
                    location.reload();
                } else if (response && response.status === 'error') {
                    alert(response.message || 'Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                } else {
                    alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Cart error:', status, error, xhr.responseText);
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        alert(response.message);
                    } else {
                        alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                    }
                } catch(e) {
                    alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                }
            }
        });
    }

    //thêm vào ds yêu thích
    function addToLike(IDSanPham) {
        $.ajax({
            type: 'POST',
            url: '../Processing/XuLy_YeuThich.php',
            data: {
                IDSanPham: IDSanPham
            },
            success: function(response) {
                if (response == "tontai") {
                    alert('Bạn đã thêm vào danh sách yêu thích rồi!');
                } else if (response == "success") {
                    alert('Thêm vào danh sách yêu thích thành công');
                } else if (response == "xoathanhcong") {
                    alert('Xóa khỏi danh sách yêu thích thành công');
                } else if (response == "chuadangnhap") {
                    alert('Vui lòng đăng nhập!!!');
                }
            },
            error: function() {
                alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
            }
        });
    }
    </script>

    <!--Start of Tawk.to Script-->
    <!-- <script type="text/javascript">
    var Tawk_API = Tawk_API || {},
        Tawk_LoadStart = new Date();
    (function() {
        var s1 = document.createElement("script"),
            s0 = document.getElementsByTagName("script")[0];
        s1.async = true;
        s1.src = 'https://embed.tawk.to/6593dc2c0ff6374032bb249e/1hj4p84ci';
        s1.charset = 'UTF-8';
        s1.setAttribute('crossorigin', '*');
        s0.parentNode.insertBefore(s1, s0);
    })();
    </script> -->
    <!--End of Tawk.to Script-->

    <!-- Promotional Banner for Potential Customer Program -->
    <?php include("../Layouts/Promo_Banner.php"); ?>

</body>

</html>