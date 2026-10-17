<?php
require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/CustomerEventService.php';
require_once dirname(__DIR__) . '/Services/CustomerScoringService.php';
require_once dirname(__DIR__) . '/Services/VoucherService.php';
require_once dirname(__DIR__) . '/Services/ShippingService.php';

$shippingRegions = (new ShippingService($conn))->activeRegions();

$claimedVouchers = [];
if (!empty($_SESSION['IDNguoiDung'])) {
    $claimedStatement = $conn->prepare(
        "SELECT v.code, v.name, v.discount_percent, v.min_order_amount, v.ends_at
         FROM voucher_claims c INNER JOIN vouchers v ON v.id = c.voucher_id
         WHERE c.user_id = ? AND c.status = 'claimed' AND v.status = 'active'
           AND v.starts_at <= NOW() AND v.ends_at > NOW()
         ORDER BY v.ends_at ASC"
    );
    $claimedUserId = (int) $_SESSION['IDNguoiDung'];
    $claimedStatement->bind_param('i', $claimedUserId);
    $claimedStatement->execute();
    $claimedVouchers = $claimedStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['CapNhatSoLuong'], $_POST['IDCapNhatSoLuong'])) {
    require_csrf();
    $newQuantity = filter_var($_POST['CapNhatSoLuong'], FILTER_VALIDATE_INT);
    $productId = filter_var($_POST['IDCapNhatSoLuong'], FILTER_VALIDATE_INT);
    if ($newQuantity === false || $newQuantity < 1 || $productId === false || $productId < 1) {
        http_response_code(422);
        exit('invalid_cart_quantity');
    }

    $stockStatement = $conn->prepare('SELECT SoLuong FROM sanpham WHERE IDSanPham = ? LIMIT 1');
    $stockStatement->bind_param('i', $productId);
    $stockStatement->execute();
    $stockRow = $stockStatement->get_result()->fetch_assoc();
    if ($stockRow !== null) {
        $availableStock = (int) $stockRow['SoLuong'];
        if ($availableStock <= 0) {
            foreach ($_SESSION['cart'] ?? [] as $index => $item) {
                if ((int) ($item[0] ?? 0) === $productId) {
                    array_splice($_SESSION['cart'], $index, 1);
                    break;
                }
            }
            $_SESSION['stock_warning'] = 'Sản phẩm đã hết hàng và đã được xóa khỏi giỏ hàng.';
        } else {
            if ($newQuantity > $availableStock) {
                $newQuantity = $availableStock;
                $_SESSION['stock_warning'] = "Số lượng vượt quá tồn kho. Đã tự động điều chỉnh về số lượng tối đa: $availableStock";
            }
            foreach ($_SESSION['cart'] ?? [] as $index => $item) {
                if ((int) ($item[0] ?? 0) === $productId) {
                    $_SESSION['cart'][$index][3] = $newQuantity;
                    break;
                }
            }
        }
    }
    header('Location: Cart.php', true, 303);
    exit();
}

// Handle AJAX requests first (before any HTML output)
if (isset($_POST['addcart']) && isset($_POST['IDSanPham'])) {
    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');
    require_csrf();
    
    //nếu ch có giỏ hàng thì tạo giỏ hàng mới
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $product_id = intval($_POST['IDSanPham']);
    $productStmt = $conn->prepare('SELECT * FROM sanpham WHERE IDSanPham = ? LIMIT 1');
    $productStmt->bind_param('i', $product_id);
    $productStmt->execute();
    $result_getInfAddCart = $productStmt->get_result();

    // Check if query succeeded and product exists
    if (!$result_getInfAddCart) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn cơ sở dữ liệu. Vui lòng thử lại sau.']);
        exit();
    }

    $row_getInfAddCart = mysqli_fetch_array($result_getInfAddCart);
    
    // Check if product was found
    if (!$row_getInfAddCart || !isset($row_getInfAddCart['IDSanPham'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại hoặc đã bị xóa.']);
        exit();
    }

    // CRITICAL: Check if product is out of stock (SoLuong <= 0)
    // Only show error if product actually exists and has SoLuong <= 0
    $available_stock = isset($row_getInfAddCart['SoLuong']) ? intval($row_getInfAddCart['SoLuong']) : 0;
    if ($available_stock <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.']);
        exit();
    }

    //lấy tt từ form
    $IDSanPham = $row_getInfAddCart['IDSanPham'];
    $tenSanPham = $row_getInfAddCart['TenSanPham'];
    $anhSanPham = $row_getInfAddCart['AnhSanPham'];
    $soLuong = 1;
    $giaTien = $row_getInfAddCart['GiaTien'];

    //check sp thêm vào đã tồn tại ch
    $check_sp = false;
    $total_quantity_in_cart = 0;
    for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
        if ($_SESSION['cart'][$i][0] == $IDSanPham) {
            $total_quantity_in_cart = intval($_SESSION['cart'][$i][3]);
            $check_sp = true;
            break;
        }
    }

    // Check if adding this quantity would exceed available stock
    $new_total_quantity = $total_quantity_in_cart + $soLuong;
    if ($new_total_quantity > $available_stock) {
        echo json_encode(['status' => 'error', 'message' => 'Số lượng vượt quá tồn kho. Tồn kho hiện tại: ' . $available_stock . ' sản phẩm.']);
        exit();
    }

    if (!$check_sp) {
        //thêm sp mới vào giỏ
        $arr_cart = [$IDSanPham, $tenSanPham, $anhSanPham, $soLuong, $giaTien];
        $_SESSION['cart'][] = $arr_cart;
    } else {
        // Update existing product quantity
        for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
            if ($_SESSION['cart'][$i][0] == $IDSanPham) {
                $_SESSION['cart'][$i][3] += $soLuong;
                break;
            }
        }
    }
    
    CustomerEventService::recordMysqli(
        $conn,
        CustomerEventService::currentUserId(),
        CustomerEventService::sessionId(),
        'cart_add',
        ['product_id' => (int) $IDSanPham, 'quantity' => 1, 'cart_quantity' => $new_total_quantity]
    );
    CustomerScoringService::refreshQuietly(CustomerEventService::currentUserId(), CustomerEventService::sessionId());
    echo json_encode(['status' => 'success', 'message' => 'Sản phẩm đã được thêm vào giỏ hàng.']);
    exit();
}

// If not an AJAX request, continue with normal page load
include("../Layouts/Head.php");

//nếu ch có giỏ hàng thì tạo giỏ hàng mới
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

//xóa all sp trong giỏ hàng
if (isset($_POST['del_all_cart']) && ($_POST['del_all_cart'] == 'del_all_cart')) {
    require_csrf();
    unset($_SESSION['cart']);
    unset($_SESSION['sum_Total']);
}

//xóa sp trong giỏ hàng
if (isset($_POST['del_id']) && filter_var($_POST['del_id'], FILTER_VALIDATE_INT) !== false) {
    require_csrf();
    $deleteIndex = (int) $_POST['del_id'];
    if ($deleteIndex >= 0 && $deleteIndex < count($_SESSION['cart'])) {
        array_splice($_SESSION['cart'], $deleteIndex, 1);
    }
}

//tất cả sp trong giỏ hàng
$_SESSION['sum_Product'] = 0;
$sum_product = 0;
if (isset($_SESSION['cart']) && (is_array($_SESSION['cart']))) {
    for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
        $sum_product += intval($_SESSION['cart'][$i][3]);
    }
}
$_SESSION['sum_Product'] = $sum_product;

//in sp ra giỏ hàng
$_SESSION['sum_Total'] = 0;
function showCart()
{
    if (isset($_SESSION['cart']) && (is_array($_SESSION['cart']))) {
        if (sizeof($_SESSION['cart']) > 0) {
            $sum = 0;
            for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
                $tt = intval($_SESSION['cart'][$i][4]) * intval($_SESSION['cart'][$i][3]);

                $sum += $tt;

                echo '<hr class="my-4">

                    <div class="row mb-4 d-flex justify-content-between align-items-center">
                        <div class="col-md-2 col-lg-2 col-xl-2">
                            <img src="../../../AdminManage/src/Products/' . $_SESSION['cart'][$i][2] . '" class="img-fluid rounded-3" style="max-height: 100px">
                        </div>
                        <div class="col-md-3 col-lg-3 col-xl-3">
                            <h6 class="text-black mb-0">' . $_SESSION['cart'][$i][1] . '</h6>
                        </div>
                        <div class="col-md-3 col-lg-3 col-xl-2 d-flex">
                            <button class="btn btn-link px-2 btn-step" onclick="changeQuantity(this, -1, '.$_SESSION['cart'][$i][0].')">
                                <i class="fas fa-minus"></i>
                            </button>

                            <input id="form1" min="1" name="quantity" value="' . $_SESSION['cart'][$i][3] . '" type="number" class="form-control form-control-sm" />

                            <button class="btn btn-link px-2 btn-step" onclick="changeQuantity(this, 1, '.$_SESSION['cart'][$i][0].')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="col-md-3 col-lg-2 col-xl-2 offset-lg-1">
                            <h6 class="mb-0">' . $_SESSION['cart'][$i][4] . 'đ</h6>
                        </div>
                        <div class="col-md-1 col-lg-1 col-xl-1 text-end">
                            <form action="Cart.php" method="post" class="d-inline">' . csrf_field() . '<input type="hidden" name="del_id" value="' . $i . '"><button type="submit" class="btn btn-link text-muted p-0" aria-label="Xóa sản phẩm"><i class="fas fa-times text-danger"></i></button></form>
                        </div>
                    </div>';
            }
            $_SESSION['sum_Total'] = $sum;
        } else {
            echo '<hr class="my-4">

            <div class="row mb-4 d-flex justify-content-between align-items-center">
                <h6 class="text-red mb-0">Giỏ hàng đang trống</h6>
            </div>';
        }
    } else {
        echo '<hr class="my-4">

            <div class="row mb-4 d-flex justify-content-between align-items-center">
                <h6 class="text-red mb-0">Giỏ hàng đang trống</h6>
            </div>';
    }
}

?>

<body>
    <!-- header -->
    <?php include("../Layouts/Header_View.php"); ?>
    <!-- end header -->

    <section class="h-custom bg-light">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12">
                    <div class="card card-registration card-registration-2" style="border-radius: 15px;">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-lg-8">
                                    <div class="p-5">
                                        <div class="d-flex justify-content-between align-items-center mb-5">
                                            <h2 class="fw-bold mb-0 text-black">Giỏ Hàng</h2>
                                        </div>

                                        <?php 
                                        // Show stock warning if exists
                                        if (isset($_SESSION['stock_warning'])) {
                                            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
                                            echo htmlspecialchars($_SESSION['stock_warning']);
                                            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                            echo '</div>';
                                            unset($_SESSION['stock_warning']);
                                        }
                                        showCart(); ?>

                                        <hr class="my-4">
                                        <form action="Cart.php" method="post">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" name="del_all_cart" value="del_all_cart" class="btn btn-delete-cart-all">Xóa tất cả</button>
                                        </form>
                                        <hr class="my-4">

                                        <div class="pt-5">
                                            <h6 class="mb-0"><a href="index.php" class="text-body"><i class="fas fa-long-arrow-alt-left me-2"></i>Back to shop</a></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 form-cart-muahang">
                                    <div class="p-5">
                                        <h3 class="fw-bold mb-5 mt-2 pt-1">Thanh Toán</h3>
                                        <hr class="my-4">

                                        <h5 class="text-uppercase mb-3">Hình Thức Thanh Toán</h5>

                                        <div class="mb-4 pb-2">
                                            <select id="paymentMethod" class="select form-control" onchange="updatePaymentButtons()">
                                                <option value="cod">Nhận Hàng Thanh Toán (COD)</option>
                                                <?php
                                                // VietQR and balance payments need an account; guests pay on delivery.
                                                if (isset($_SESSION['user'])) {
                                                    echo '<option value="qr">Chuyển khoản qua VietQR</option>';
                                                    $balanceStmt = $conn->prepare('SELECT ID_KhachHang, SoDu FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
                                                    $balanceUsername = (string) $_SESSION['user'];
                                                    $balanceStmt->bind_param('s', $balanceUsername);
                                                    $balanceStmt->execute();
                                                    $user_row = $balanceStmt->get_result()->fetch_assoc();
                                                    $account_balance = isset($user_row['SoDu']) ? intval($user_row['SoDu']) : 0;
                                                    
                                                    // Always display account balance payment option, even if balance is 0
                                                    echo '<option value="balance">Thanh toán bằng số dư tài khoản (Số dư: ' . number_format($account_balance, 0, ',', '.') . ' đ)</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <?php
                                        // Display account balance information (without warning message)
                                        if (isset($_SESSION['user'])) {
                                            $account_balance = $account_balance ?? 0;
                                            
                                            // Only display balance information, no warning
                                            echo '<div class="mb-3">';
                                            echo '<small class="text-muted">Số dư tài khoản: <strong>' . number_format($account_balance, 0, ',', '.') . ' đ</strong></small>';
                                            echo '</div>';
                                        }
                                        ?>

                                        <?php if (!isset($_SESSION['user'])): ?>
                                        <p class="small mb-4">Mua hàng không cần tài khoản: thanh toán khi nhận hàng, nhân viên gọi xác nhận đơn. <a href="Login.php" class="fw-bold text-dark">Đăng nhập</a> để dùng mã giảm giá, VietQR và theo dõi đơn hàng.</p>
                                        <?php else: ?>
                                        <h5 class="text-uppercase mb-3">Mã Giảm Giá</h5>

                                        <div class="mb-4">
                                            <div class="input-group">
                                                <input type="text" id="voucherCode" maxlength="32"
                                                       value="<?php echo htmlspecialchars((string) ($_SESSION['voucher_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                       placeholder="Ví dụ: MOSHOP20" class="form-control text-uppercase" />
                                                <button type="button" class="btn btn-outline-dark" onclick="applyVoucher()">Áp dụng</button>
                                                <?php if (!empty($_SESSION['voucher_code'])): ?>
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeVoucher()" title="Bỏ mã">×</button>
                                                <?php endif; ?>
                                            </div>
                                            <small id="voucherMessage" class="d-block mt-2 text-muted">Mã được kiểm tra lại khi tạo đơn hàng.</small>
                                            <?php if ($claimedVouchers !== []): ?>
                                                <div class="mt-3">
                                                    <small class="d-block text-muted mb-2">Voucher đã nhận:</small>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php foreach ($claimedVouchers as $claimedVoucher): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success claimed-voucher-button"
                                                                data-voucher-code="<?php echo htmlspecialchars((string) $claimedVoucher['code'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                #<?php echo htmlspecialchars((string) $claimedVoucher['code'], ENT_QUOTES, 'UTF-8'); ?> · -<?php echo (int) $claimedVoucher['discount_percent']; ?>%
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <h5 class="text-uppercase mb-3">Khu vực giao hàng</h5>
                                        <div class="mb-4">
                                            <select id="shippingRegion" class="form-control" onchange="refreshShippingQuote()" <?php echo $shippingRegions===[]?'disabled':''; ?>>
                                                <option value="">-- Chọn khu vực --</option>
                                                <?php foreach($shippingRegions as $region): ?><option value="<?php echo htmlspecialchars($region['code'],ENT_QUOTES,'UTF-8'); ?>" <?php echo count($shippingRegions)===1?'selected':''; ?>><?php echo htmlspecialchars($region['name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?>
                                            </select>
                                            <?php if($shippingRegions===[]): ?><small class="text-danger">Chưa có biểu phí được kích hoạt. Vui lòng liên hệ cửa hàng.</small><?php endif; ?>
                                        </div>

                                        <hr class="my-4">

                                        <?php
                                        $subtotal = isset($_SESSION['sum_Total']) ? intval($_SESSION['sum_Total']) : 0;
                                        $voucher_discount = 0;
                                        $voucher_error = '';
                                        $applied_voucher = null;
                                        if ($subtotal > 0 && !empty($_SESSION['voucher_code']) && isset($_SESSION['user'])) {
                                            try {
                                                $voucherUserId = (int) ($user_row['ID_KhachHang'] ?? ($_SESSION['IDNguoiDung'] ?? 0));
                                                $applied_voucher = (new VoucherService($conn))->validate(
                                                    (string) $_SESSION['voucher_code'],
                                                    $voucherUserId,
                                                    $subtotal
                                                );
                                                $voucher_discount = (int) $applied_voucher['discount_amount'];
                                            } catch (VoucherException $voucherException) {
                                                $voucher_error = $voucherException->getMessage();
                                                unset($_SESSION['voucher_code']);
                                            }
                                        }
                                        $total_with_shipping = max(0, $subtotal - $voucher_discount);
                                        ?>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="text-uppercase">Tổng tiền sản phẩm</h6>
                                            <h6><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</h6>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="text-uppercase">Phí vận chuyển</h6>
                                            <h6 id="shippingFeeDisplay">Chọn khu vực</h6>
                                        </div>
                                        <small id="freeShippingHint" class="d-block text-success mb-2"></small>

                                        <?php if ($applied_voucher !== null): ?>
                                        <div class="d-flex justify-content-between mb-2 text-success">
                                            <h6 class="text-uppercase">Giảm giá (<?php echo htmlspecialchars((string) $applied_voucher['code'], ENT_QUOTES, 'UTF-8'); ?>)</h6>
                                            <h6>-<?php echo number_format($voucher_discount, 0, ',', '.'); ?> đ</h6>
                                        </div>
                                        <?php elseif ($voucher_error !== ''): ?>
                                        <div class="alert alert-warning py-2 small"><?php echo htmlspecialchars($voucher_error, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                        
                                        <hr class="my-3">

                                        <div class="d-flex justify-content-between mb-5">
                                            <h5 class="text-uppercase"><strong>Tổng cộng</strong></h5>
                                            <h5><strong id="checkoutTotalDisplay"><?php echo number_format($total_with_shipping, 0, ',', '.'); ?> đ + phí ship</strong></h5>
                                        </div>

                                        <?php
                                        // Informational status only. Promotions must be calculated by checkout,
                                        // so this block never promises a discount that is not in the order total.
                                        if (isset($_SESSION['user']) && isset($_SESSION['IDNguoiDung'])) {
                                            try {
                                                $pdoCart = app_pdo();
                                                $stmt = $pdoCart->prepare("SELECT customer_label,customer_value_tier FROM khachhang WHERE ID_KhachHang = ?");
                                                $stmt->execute([$_SESSION['IDNguoiDung']]);
                                                $custData = $stmt->fetch(PDO::FETCH_ASSOC);

                                                $customerLabel = $custData['customer_label'] ?? 'normal';
                                                $isPotential = ($customerLabel === 'potential');
                                                $valueTier = htmlspecialchars((string)($custData['customer_value_tier'] ?? 'new'));

                                                echo '<div class="card mb-4" style="background: linear-gradient(135deg, ' . ($isPotential ? '#fff9e6' : '#f0f4ff') . ' 0%, ' . ($isPotential ? '#fff3cd' : '#e8ecf1') . ' 100%); border: 2px solid ' . ($isPotential ? '#ffd700' : '#667eea') . ';">';
                                                echo '<div class="card-body p-3">';

                                                if ($isPotential) {
                                                    echo '<div class="d-flex align-items-center mb-2">';
                                                    echo '<span style="font-size:24px;">⭐</span>';
                                                    echo '<strong class="ms-2" style="color:#e85d04;">Bạn là Khách hàng Tiềm năng!</strong>';
                                                    echo '</div>';
                                                    echo '<p class="mb-0 small">Mơ Shop sẽ ưu tiên hỗ trợ nhu cầu và đơn hàng hiện tại của bạn.</p>';
                                                } else {
                                                    echo '<div class="d-flex align-items-center mb-2">';
                                                    echo '<span style="font-size:20px;">🎯</span>';
                                                    echo '<strong class="ms-2" style="color:#667eea;">Hạng thành viên: ' . $valueTier . '</strong>';
                                                    echo '</div>';
                                                    echo '<p class="mb-0 small">Điểm thành viên và ưu đãi được quản lý độc lập với mức độ quan tâm mua hàng.</p>';
                                                }

                                                echo '</div>';
                                                echo '</div>';
                                            } catch (Exception $e) {
                                                // Silently fail
                                            }
                                        }
                                        ?>

                                        <?php
                                        if (isset($_SESSION['user'])) {
                                            if (isset($_SESSION['cart']) && (is_array($_SESSION['cart']))) {
                                                if (sizeof($_SESSION['cart']) > 0) {
                                                    echo '<div class="d-grid gap-2" id="paymentButtons">';
                                                    echo '<button onclick="processPayment(\'cod\')" class="btn btn-dark btn-lg">Thanh toán COD</button>';
                                                    echo '<button onclick="processPayment(\'qr\')" class="btn btn-success btn-lg">Thanh toán bằng QR</button>';
                                                    echo '</div>';
                                                } else {
                                                    echo '<a href="index.php" class="btn btn-dark btn-block btn-lg" data-mdb-ripple-color="dark">Thêm sản phẩm để mua hàng</a>';
                                                }
                                            } else {
                                                echo '<a href="index.php" class="btn btn-dark btn-block btn-lg" data-mdb-ripple-color="dark">Thêm sản phẩm để mua hàng</a>';
                                            }
                                        } elseif (!empty($_SESSION['cart'])) {
                                            // Guest checkout: CheckOut.php asks for name, phone and delivery address.
                                            echo '<div class="d-grid gap-2" id="paymentButtons">';
                                            echo '<button onclick="processPayment(\'cod\')" class="btn btn-dark btn-lg">Đặt hàng (COD)</button>';
                                            echo '<a href="Login.php" class="btn btn-outline-dark">Đăng nhập để mua hàng</a>';
                                            echo '</div>';
                                        } else {
                                            echo '<a href="index.php" class="btn btn-dark btn-block btn-lg" data-mdb-ripple-color="dark">Thêm sản phẩm để mua hàng</a>';
                                        }
                                        ?>
                                        
                                        <script>
                                        const voucherCsrfToken = <?php echo json_encode(csrf_token()); ?>;

                                        async function updateVoucher(action) {
                                            const input = document.getElementById('voucherCode');
                                            const message = document.getElementById('voucherMessage');
                                            const body = new URLSearchParams({
                                                csrf_token: voucherCsrfToken,
                                                action: action,
                                                code: input ? input.value.trim().toUpperCase() : ''
                                            });
                                            if (message) {
                                                message.className = 'd-block mt-2 text-muted';
                                                message.textContent = 'Đang kiểm tra...';
                                            }
                                            try {
                                                const response = await fetch('../Processing/Voucher_Validate.php', {
                                                    method: 'POST',
                                                    headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                                                    body: body.toString()
                                                });
                                                const data = await response.json();
                                                if (!response.ok || !data.ok) {
                                                    throw new Error(data.message || 'Mã giảm giá không hợp lệ.');
                                                }
                                                window.location.reload();
                                            } catch (error) {
                                                if (message) {
                                                    message.className = 'd-block mt-2 text-danger';
                                                    message.textContent = error.message;
                                                }
                                            }
                                        }

                                        function applyVoucher() { updateVoucher('apply'); }
                                        function removeVoucher() { updateVoucher('remove'); }
                                        document.querySelectorAll('.claimed-voucher-button').forEach((button) => {
                                            button.addEventListener('click', () => {
                                                const input = document.getElementById('voucherCode');
                                                if (input) input.value = button.dataset.voucherCode || '';
                                                applyVoucher();
                                            });
                                        });

                                        function updatePaymentButtons() {
                                            const method = document.getElementById('paymentMethod').value;
                                            const buttonsDiv = document.getElementById('paymentButtons');
                                            if (!buttonsDiv) return;
                                            
                                            if (method === 'balance') {
                                                buttonsDiv.innerHTML = '<button onclick="processPayment(\'balance\')" class="btn btn-primary btn-lg">Thanh toán bằng số dư</button>';
                                            } else if (method === 'cod') {
                                                buttonsDiv.innerHTML = '<button onclick="processPayment(\'cod\')" class="btn btn-dark btn-lg">Thanh toán COD</button>';
                                            } else if (method === 'qr') {
                                                buttonsDiv.innerHTML = '<button onclick="processPayment(\'qr\')" class="btn btn-success btn-lg">Thanh toán bằng QR</button>';
                                            }
                                        }
                                        
                                        function processPayment(method) {
                                            const region = document.getElementById('shippingRegion')?.value || '';
                                            if (!region) { alert('Vui lòng chọn khu vực giao hàng.'); return; }
                                            if (method === 'balance') {
                                                // Process balance payment directly from cart
                                                $.ajax({
                                                    type: 'POST',
                                                    url: '../Processing/XuLy_DonHang_Balance.php',
                                                    data: {
                                                        csrf_token: <?php require_once dirname(__DIR__, 3) . '/config/security.php'; echo json_encode(csrf_token()); ?>,
                                                        pay_by_balance: '1',
                                                        ShippingRegion: region,
                                                    },
                                                    success: function(response) {
                                                        response = response.trim();
                                                        // Get the last line in case there are warnings before success
                                                        var lines = response.split('\n');
                                                        var lastLine = lines[lines.length - 1].trim();
                                                        console.log('Balance payment response:', response);
                                                        
                                                        if (lastLine === 'success' || response.includes('success')) {
                                                            alert('Thanh toán thành công! Đơn hàng đã được tạo và số dư đã được trừ.');
                                                            window.location.href = 'Order_Tracking.php';
                                                        } else if (response.includes('insufficient_balance')) {
                                                            alert('Số dư tài khoản không đủ để thanh toán. Vui lòng chọn phương thức thanh toán khác hoặc nạp thêm tiền vào tài khoản.');
                                                        } else if (response.includes('stock_error:')) {
                                                            var errorMsg = response.substring(response.indexOf('stock_error:') + 'stock_error:'.length).trim();
                                                            alert('Lỗi: ' + errorMsg);
                                                        } else if (response.includes('error_total')) {
                                                            alert('Tổng tiền không hợp lệ. Vui lòng kiểm tra lại giỏ hàng.');
                                                        } else if (response.includes('error:') && !response.includes('success')) {
                                                            var errorMsg = response.substring(response.indexOf('error:') + 'error:'.length).trim();
                                                            alert('Có lỗi khi tạo đơn hàng: ' + errorMsg + '. Vui lòng thử lại.');
                                                            console.error('Order creation error:', errorMsg);
                                                        } else if (response.includes('error') && !response.includes('success')) {
                                                            alert('Có lỗi khi tạo đơn hàng. Vui lòng thử lại.');
                                                            console.error('Order creation failed with generic error');
                                                        } else {
                                                            // If response contains success anywhere, treat as success
                                                            if (response.toLowerCase().includes('success')) {
                                                                alert('Thanh toán thành công! Đơn hàng đã được tạo và số dư đã được trừ.');
                                                                window.location.href = 'Order_Tracking.php';
                                                            } else {
                                                                alert('Có lỗi xảy ra khi thanh toán. Vui lòng thử lại.');
                                                                console.error('Unknown payment response:', response);
                                                            }
                                                        }
                                                    },
                                                    error: function(xhr, status, error) {
                                                        console.error('Payment AJAX error:', status, error);
                                                        console.error('Response text:', xhr.responseText);
                                                        console.error('Status code:', xhr.status);
                                                        alert('Đã xảy ra lỗi khi kết nối đến server. Vui lòng thử lại sau.\n\nLỗi: ' + error);
                                                    }
                                                });
                                            } else if (method === 'cod') {
                                                window.location.href = 'CheckOut.php?shipping_region=' + encodeURIComponent(region);
                                            } else if (method === 'qr') {
                                                window.location.href = 'CheckOut_QR.php?shipping_region=' + encodeURIComponent(region);
                                            }
                                        }

                                        async function refreshShippingQuote() {
                                            const region=document.getElementById('shippingRegion')?.value||'';
                                            if(!region)return;
                                            const response=await fetch('../Processing/Shipping_Quote.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf_token:<?php echo json_encode(csrf_token()); ?>,region_code:region})});
                                            const data=await response.json();
                                            if(!response.ok||!data.success){alert(data.message||'Không thể tính phí vận chuyển.');return;}
                                            document.getElementById('shippingFeeDisplay').textContent=data.quote.fee===0?'Miễn phí':new Intl.NumberFormat('vi-VN').format(data.quote.fee)+' đ';
                                            document.getElementById('checkoutTotalDisplay').textContent=new Intl.NumberFormat('vi-VN').format(data.quote.total)+' đ';
                                            const hint=document.getElementById('freeShippingHint');
                                            const freeMin=data.quote.free_shipping_min;
                                            if(hint){hint.textContent=freeMin&&data.quote.fee>0?'Mua thêm '+new Intl.NumberFormat('vi-VN').format(freeMin-data.quote.subtotal)+' đ để được miễn phí vận chuyển':'';}
                                        }
                                        // A single active region (the default nationwide rate) is preselected: quote it right away.
                                        document.addEventListener('DOMContentLoaded',()=>{if(document.getElementById('shippingRegion')?.value)refreshShippingQuote();});
                                        </script>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>
