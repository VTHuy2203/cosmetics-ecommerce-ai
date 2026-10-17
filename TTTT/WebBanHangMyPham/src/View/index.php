<?php
include("../DB_Connect.php");

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['KetThucSession'])) {
    require_csrf();
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

$homeVouchers = [];
$homeUserId = (int) ($_SESSION['IDNguoiDung'] ?? 0);
$voucherStatement = $conn->prepare(
    "SELECT v.*,
            COALESCE(vc.status, '') AS claim_status,
            GREATEST(v.quantity_total - v.quantity_used -
                (SELECT COUNT(*) FROM voucher_claims cr WHERE cr.voucher_id = v.id AND cr.status = 'claimed'), 0
            ) AS remaining_quantity
     FROM vouchers v
     LEFT JOIN voucher_claims vc ON vc.voucher_id = v.id AND vc.user_id = ?
     WHERE v.status = 'active' AND v.starts_at <= NOW() AND v.ends_at > NOW()
     HAVING remaining_quantity > 0 OR claim_status = 'claimed'
     ORDER BY v.ends_at ASC LIMIT 8"
);
$voucherStatement->bind_param('i', $homeUserId);
$voucherStatement->execute();
$homeVouchers = $voucherStatement->get_result()->fetch_all(MYSQLI_ASSOC);

include("../Layouts/Head.php");
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

    <?php if ($homeVouchers !== []): ?>
    <section class="container my-4" id="home-vouchers">
        <div class="d-flex align-items-end justify-content-between mb-3">
            <div><h3 class="mb-1">Voucher dành cho bạn</h3><p class="text-muted mb-0">Nhận voucher và sử dụng khi thanh toán.</p></div>
            <a href="Cart.php" class="text-decoration-none">Đến giỏ hàng →</a>
        </div>
        <div class="row g-3">
            <?php foreach ($homeVouchers as $voucher):
                $claimed = $voucher['claim_status'] === 'claimed';
                $redeemed = $voucher['claim_status'] === 'redeemed';
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="border-left:5px solid #2D9596!important;border-radius:14px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2"><span class="badge bg-danger">Giảm <?php echo (int) $voucher['discount_percent']; ?>%</span><small class="text-muted">Còn <?php echo (int) $voucher['remaining_quantity']; ?></small></div>
                        <h5 class="mt-3 mb-1"><?php echo htmlspecialchars((string) $voucher['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                        <div class="fw-bold text-success fs-5">#<?php echo htmlspecialchars((string) $voucher['code'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <p class="small text-muted my-2"><?php echo htmlspecialchars((string) ($voucher['description'] ?: 'Ưu đãi dành cho đơn hàng đủ điều kiện.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="small mb-3">Đơn tối thiểu: <strong><?php echo number_format((int) $voucher['min_order_amount'], 0, ',', '.'); ?> đ</strong><br>Hết hạn: <?php echo date('d/m/Y H:i', strtotime((string) $voucher['ends_at'])); ?></div>
                        <?php if ($homeUserId < 1): ?>
                            <a href="Login.php" class="btn btn-outline-success w-100">Đăng nhập để nhận</a>
                        <?php else: ?>
                            <button type="button" class="btn <?php echo $claimed ? 'btn-success' : ($redeemed ? 'btn-secondary' : 'btn-outline-success'); ?> w-100 home-claim-voucher"
                                data-voucher-id="<?php echo (int) $voucher['id']; ?>" <?php echo ($claimed || $redeemed) ? 'disabled' : ''; ?>>
                                <?php echo $claimed ? 'Đã nhận' : ($redeemed ? 'Đã sử dụng' : 'Nhận voucher'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

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
    document.querySelectorAll('.home-claim-voucher').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = 'Đang nhận...';
            try {
                const body = new URLSearchParams({
                    voucher_id: button.dataset.voucherId,
                    csrf_token: <?php echo json_encode(csrf_token()); ?>
                });
                const response = await fetch('../Processing/Voucher_Claim.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: body.toString()
                });
                const data = await response.json();
                if (!response.ok || !data.ok) throw new Error(data.message || 'Không thể nhận voucher.');
                button.className = 'btn btn-success w-100 home-claim-voucher';
                button.textContent = 'Đã nhận';
            } catch (error) {
                button.disabled = false;
                button.textContent = originalText;
                alert(error.message);
            }
        });
    });

    // Xử lý sự kiện nhấp vào nút "Add to Cart"
    function addToCart(IDSanPham) {
        $.ajax({
            type: 'POST',
            url: 'Cart.php',
                data: {
                    IDSanPham: IDSanPham,
                    addcart: 'addcart',
                    csrf_token: <?php echo json_encode(csrf_token()); ?>
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
                IDSanPham: IDSanPham,
                csrf_token: <?php echo json_encode(csrf_token()); ?>
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
