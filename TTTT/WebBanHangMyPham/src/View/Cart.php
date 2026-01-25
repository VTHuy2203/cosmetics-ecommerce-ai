<?php
// Start session before including any files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle AJAX requests first (before any HTML output)
if (isset($_POST['addcart']) && isset($_POST['IDSanPham'])) {
    // Include DB connection only (no Head.php to avoid HTML output)
    include("../DB_Connect.php");
    
    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');
    
    //nếu ch có giỏ hàng thì tạo giỏ hàng mới
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $product_id = intval($_POST['IDSanPham']);
    $sql_getInfAddCart = "SELECT * FROM sanpham WHERE IDSanPham = " . $product_id;
    $result_getInfAddCart = mysqli_query($conn, $sql_getInfAddCart);

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
    
    echo json_encode(['status' => 'success', 'message' => 'Sản phẩm đã được thêm vào giỏ hàng.']);
    exit();
}

// If not an AJAX request, continue with normal page load
include("../DB_Connect.php");
include("../Layouts/Head.php");

//nếu ch có giỏ hàng thì tạo giỏ hàng mới
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

//xóa all sp trong giỏ hàng
if (isset($_POST['del_all_cart']) && ($_POST['del_all_cart'] == 'del_all_cart')) {
    unset($_SESSION['cart']);
    unset($_SESSION['sum_Total']);
}

//xóa sp trong giỏ hàng
if (isset($_GET['del_id']) && ($_GET['del_id'] >= 0)) {
    array_splice($_SESSION['cart'], $_GET['del_id'], 1);
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
                            <a href="Cart.php?del_id=' . $i . '" class="text-muted"><i class="fas fa-times text-danger"></i></a>
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

//cập nhật số lượng sp qua input số lượng
if (isset($_GET['CapNhatSoLuong']) && isset($_GET['IDCapNhatSoLuong'])) {
    if (isset($_SESSION['cart'])) {
        $new_quantity = intval($_GET['CapNhatSoLuong']);
        $product_id = intval($_GET['IDCapNhatSoLuong']);
        
        // Check stock availability
        $sql_check_stock = "SELECT SoLuong FROM sanpham WHERE IDSanPham = '$product_id'";
        $result_check_stock = mysqli_query($conn, $sql_check_stock);
        if ($result_check_stock && mysqli_num_rows($result_check_stock) > 0) {
            $stock_row = mysqli_fetch_array($result_check_stock);
            if ($stock_row && isset($stock_row['SoLuong'])) {
                $available_stock = intval($stock_row['SoLuong']);
                
                // Only enforce stock limit if product actually has stock
                if ($available_stock > 0 && $new_quantity > $available_stock) {
                    // Set to max available stock
                    $new_quantity = $available_stock;
                    $_SESSION['stock_warning'] = "Số lượng vượt quá tồn kho. Đã tự động điều chỉnh về số lượng tối đa: $available_stock";
                } elseif ($available_stock <= 0) {
                    // Product is out of stock, remove from cart or set to 0
                    $_SESSION['stock_warning'] = "Sản phẩm đã hết hàng và đã được xóa khỏi giỏ hàng.";
                    // Remove product from cart
                    for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
                        if ($_SESSION['cart'][$i][0] == $product_id) {
                            array_splice($_SESSION['cart'], $i, 1);
                            header('Location: Cart.php');
                            exit();
                        }
                    }
                }
            }
        }
        
        for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
            if ($_SESSION['cart'][$i][0] == $product_id) {
                $_SESSION['cart'][$i][3] = $new_quantity;
                break;
            }
        }
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
                                                <option value="qr">Chuyển khoản qua VietQR</option>
                                                <?php
                                                if (isset($_SESSION['user'])) {
                                                    // Get account balance - check if column exists first
                                                    $checkCol = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
                                                    $colExists = false;
                                                    if ($checkCol) {
                                                        $colRow = mysqli_fetch_assoc($checkCol);
                                                        $colExists = intval($colRow['c']) > 0;
                                                    }
                                                    if (!$colExists) {
                                                        @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
                                                    }
                                                    
                                                    $sql_user = "SELECT ID_KhachHang, SoDu FROM khachhang WHERE TenDangNhap = '" . $_SESSION['user'] . "'";
                                                    $result_user = mysqli_query($conn, $sql_user);
                                                    $user_row = mysqli_fetch_array($result_user);
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
                                            $sql_user = "SELECT ID_KhachHang, SoDu FROM khachhang WHERE TenDangNhap = '" . $_SESSION['user'] . "'";
                                            $result_user = mysqli_query($conn, $sql_user);
                                            $user_row = mysqli_fetch_array($result_user);
                                            $account_balance = isset($user_row['SoDu']) ? intval($user_row['SoDu']) : 0;
                                            
                                            // Only display balance information, no warning
                                            echo '<div class="mb-3">';
                                            echo '<small class="text-muted">Số dư tài khoản: <strong>' . number_format($account_balance, 0, ',', '.') . ' đ</strong></small>';
                                            echo '</div>';
                                        }
                                        ?>

                                        <h5 class="text-uppercase mb-3">Mã Giảm Giá</h5>

                                        <div class="mb-5">
                                            <div class="form-outline">
                                                <input type="text" id="form3Examplea2" placeholder="Nhập tại đây..." class="form-control form-control-lg" />
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <?php
                                        // Calculate shipping fee: 25,000 VND for orders under 500,000 VND
                                        $subtotal = isset($_SESSION['sum_Total']) ? intval($_SESSION['sum_Total']) : 0;
                                        $free_shipping_threshold = 500000; // 500,000 VND
                                        $shipping_fee = 0;
                                        if ($subtotal > 0 && $subtotal < $free_shipping_threshold) {
                                            $shipping_fee = 25000; // 25,000 VND shipping fee
                                        }
                                        $total_with_shipping = $subtotal + $shipping_fee;
                                        ?>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="text-uppercase">Tổng tiền sản phẩm</h6>
                                            <h6><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</h6>
                                        </div>
                                        
                                        <?php if ($shipping_fee > 0): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="text-uppercase">Phí vận chuyển</h6>
                                            <h6><?php echo number_format($shipping_fee, 0, ',', '.'); ?> đ</h6>
                                        </div>
                                        <?php else: ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="text-success text-uppercase">
                                                <i class="fas fa-check-circle me-2"></i>Đơn hàng này được miễn phí vận chuyển.
                                            </h6>
                                            <h6 class="text-success">Miễn phí</h6>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <hr class="my-3">

                                        <div class="d-flex justify-content-between mb-5">
                                            <h5 class="text-uppercase"><strong>Tổng cộng</strong></h5>
                                            <h5><strong><?php echo number_format($total_with_shipping, 0, ',', '.'); ?> đ</strong></h5>
                                        </div>

                                        <?php
                                        // VIP Progress tracking - Show progress towards becoming potential customer
                                        if (isset($_SESSION['user']) && isset($_SESSION['IDNguoiDung'])) {
                                            try {
                                                $dsn = "mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4";
                                                $pdoCart = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                                                $stmt = $pdoCart->prepare("SELECT customer_label, total_weekly_purchase, discount_percent FROM khachhang WHERE ID_KhachHang = ?");
                                                $stmt->execute([$_SESSION['IDNguoiDung']]);
                                                $custData = $stmt->fetch(PDO::FETCH_ASSOC);

                                                $customerLabel = $custData['customer_label'] ?? 'normal';
                                                $weeklyPurchase = (int)($custData['total_weekly_purchase'] ?? 0);
                                                $discountPercent = (int)($custData['discount_percent'] ?? 0);
                                                $targetAmount = 5000000;
                                                $progressPercent = min(100, ($weeklyPurchase / $targetAmount) * 100);
                                                $amountNeeded = max(0, $targetAmount - $weeklyPurchase);
                                                $isPotential = ($customerLabel === 'potential');

                                                echo '<div class="card mb-4" style="background: linear-gradient(135deg, ' . ($isPotential ? '#fff9e6' : '#f0f4ff') . ' 0%, ' . ($isPotential ? '#fff3cd' : '#e8ecf1') . ' 100%); border: 2px solid ' . ($isPotential ? '#ffd700' : '#667eea') . ';">';
                                                echo '<div class="card-body p-3">';

                                                if ($isPotential) {
                                                    echo '<div class="d-flex align-items-center mb-2">';
                                                    echo '<span style="font-size:24px;">⭐</span>';
                                                    echo '<strong class="ms-2" style="color:#e85d04;">Bạn là Khách hàng Tiềm năng!</strong>';
                                                    echo '</div>';
                                                    echo '<p class="mb-2 small">Bạn đang được giảm <strong>' . $discountPercent . '%</strong> cho tất cả đơn hàng.</p>';
                                                    echo '<p class="mb-0 small text-muted">Tiếp tục mua sắm trên 1.000.000đ/tuần để duy trì ưu đãi.</p>';
                                                } else {
                                                    echo '<div class="d-flex align-items-center mb-2">';
                                                    echo '<span style="font-size:20px;">🎯</span>';
                                                    echo '<strong class="ms-2" style="color:#667eea;">Tiến trình VIP</strong>';
                                                    echo '</div>';
                                                    echo '<p class="mb-2 small">Mua thêm <strong>' . number_format($amountNeeded, 0, ',', '.') . 'đ</strong> để trở thành Khách hàng Tiềm năng và được <strong>giảm 20%</strong>!</p>';

                                                    // Progress bar
                                                    echo '<div class="progress mb-2" style="height: 12px;">';
                                                    echo '<div class="progress-bar" role="progressbar" style="width: ' . $progressPercent . '%; background: linear-gradient(90deg, #667eea, #764ba2);" aria-valuenow="' . $progressPercent . '" aria-valuemin="0" aria-valuemax="100"></div>';
                                                    echo '</div>';
                                                    echo '<div class="d-flex justify-content-between small text-muted">';
                                                    echo '<span>Đã mua: ' . number_format($weeklyPurchase, 0, ',', '.') . 'đ</span>';
                                                    echo '<span>Mục tiêu: 5.000.000đ</span>';
                                                    echo '</div>';
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
                                        } else {
                                            echo '<a href="Login.php" class="btn btn-dark btn-block btn-lg" data-mdb-ripple-color="dark">Đăng nhập để mua hàng</a>';
                                        }
                                        ?>
                                        
                                        <script>
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
                                            if (method === 'balance') {
                                                // Process balance payment directly from cart
                                                var shippingFee = <?php echo $shipping_fee; ?>;
                                                $.ajax({
                                                    type: 'POST',
                                                    url: '../Processing/XuLy_DonHang_Balance.php',
                                                    data: {
                                                        pay_by_balance: '1',
                                                        shipping_fee: shippingFee
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
                                                window.location.href = 'CheckOut.php';
                                            } else if (method === 'qr') {
                                                window.location.href = 'CheckOut_QR.php';
                                            }
                                        }
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