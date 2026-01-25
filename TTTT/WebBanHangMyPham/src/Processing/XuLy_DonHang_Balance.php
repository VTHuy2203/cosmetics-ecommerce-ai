<?php
/**
 * Process order payment using account balance
 * Deducts balance and creates order with paid status
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../DB_Connect.php");

function recalc_total_from_cart(): int {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    $sum = 0;
    for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
        if (isset($_SESSION['cart'][$i][4]) && isset($_SESSION['cart'][$i][3])) {
            $sum += intval($_SESSION['cart'][$i][4]) * intval($_SESSION['cart'][$i][3]);
        }
    }
    return $sum;
}

if (isset($_SESSION['user']) && isset($_SESSION['cart']) && !empty($_SESSION['cart']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get customer info
    $sql_inf_user = "SELECT * FROM khachhang WHERE TenDangNhap = '" . $_SESSION['user'] . "'";
    $result = mysqli_query($conn, $sql_inf_user);
    $row = mysqli_fetch_array($result);
    
    if (!$row) {
        echo 'error';
        exit();
    }
    
    $ID_KhachHang = $row['ID_KhachHang'];
    $HoVaTen = $row['HoVaTen'];
    $Email = $row['Email'];
    $Sdt = $row['SDT'];
    $DiaChi = $row['DiaChi'];
    
    // Check stock availability before creating order
    $stock_error = false;
    $stock_error_message = '';
    for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
        $product_id = intval($_SESSION['cart'][$i][0]);
        $cart_quantity = intval($_SESSION['cart'][$i][3]);
        
        $sql_check_stock = "SELECT SoLuong, TenSanPham FROM sanpham WHERE IDSanPham = '$product_id'";
        $result_check_stock = mysqli_query($conn, $sql_check_stock);
        if ($result_check_stock && mysqli_num_rows($result_check_stock) > 0) {
            $stock_row = mysqli_fetch_array($result_check_stock);
            if ($stock_row && isset($stock_row['SoLuong']) && isset($stock_row['TenSanPham'])) {
                $available_stock = intval($stock_row['SoLuong']);
                $product_name = $stock_row['TenSanPham'];
                
                // CRITICAL: Check if product is out of stock (SoLuong <= 0)
                // Only show error if product actually exists and has SoLuong <= 0
                if ($available_stock <= 0) {
                    $stock_error = true;
                    $stock_error_message = "Sản phẩm '" . $product_name . "' không còn hàng. Vui lòng đợi Admin cập nhật.";
                    break;
                }
                
                // Check if cart quantity exceeds available stock
                if ($cart_quantity > $available_stock) {
                    $stock_error = true;
                    $stock_error_message = "Số lượng sản phẩm '" . $product_name . "' vượt quá tồn kho. Tồn kho hiện tại: " . $available_stock . " sản phẩm.";
                    break;
                }
            } else {
                // Invalid product data
                $stock_error = true;
                $stock_error_message = "Dữ liệu sản phẩm không hợp lệ.";
                break;
            }
        } else {
            // Product not found
            $stock_error = true;
            $stock_error_message = "Sản phẩm không tồn tại hoặc đã bị xóa.";
            break;
        }
    }
    
    if ($stock_error) {
        echo 'stock_error:' . $stock_error_message;
        exit();
    }
    
    // Ensure SoDu column exists - check first
    $checkCol = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
    $colExists = false;
    if ($checkCol) {
        $colRow = mysqli_fetch_assoc($checkCol);
        $colExists = intval($colRow['c']) > 0;
    }
    if (!$colExists) {
        @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
    }
    
    // Recalculate total from cart to ensure accuracy
    $subtotal = recalc_total_from_cart();
    
    // Fallback to session total if cart calculation fails
    if ($subtotal <= 0 && isset($_SESSION['sum_Total'])) {
        $subtotal = intval($_SESSION['sum_Total']);
    }
    
    if ($subtotal <= 0) {
        echo 'error_total';
        exit();
    }
    
    // Calculate shipping fee: 25,000 VND for orders under 500,000 VND
    $free_shipping_threshold = 500000; // 500,000 VND
    $shipping_fee = 0;
    if (isset($_POST['shipping_fee'])) {
        $shipping_fee = intval($_POST['shipping_fee']);
    } else {
        // Calculate shipping fee if not provided
        if ($subtotal < $free_shipping_threshold) {
            $shipping_fee = 25000; // 25,000 VND
        }
    }
    
    $TongTien = $subtotal + $shipping_fee;
    
    // Check balance - ensure we get the latest balance
    $balance_query = mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'");
    if (!$balance_query) {
        echo 'error: Lỗi khi truy vấn số dư. Vui lòng thử lại.';
        exit();
    }
    
    $u = mysqli_fetch_array($balance_query);
    $soDu = isset($u['SoDu']) ? intval($u['SoDu']) : 0;
    
    // Debug: Log balance check
    error_log("Balance check: User ID=$ID_KhachHang, Balance=$soDu, Total=$TongTien, Sufficient=" . ($soDu >= $TongTien ? 'yes' : 'no'));
    
    if ($soDu < $TongTien) {
        echo 'insufficient_balance';
        exit();
    }
    
    // Ensure DaThanhToan column exists - check first
    $checkCol2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'DaThanhToan'");
    $colExists2 = false;
    if ($checkCol2) {
        $colRow2 = mysqli_fetch_assoc($checkCol2);
        $colExists2 = intval($colRow2['c']) > 0;
    }
    if (!$colExists2) {
        @mysqli_query($conn, "ALTER TABLE `donhang` ADD `DaThanhToan` TINYINT(1) NOT NULL DEFAULT 0 AFTER `TrangThai`");
    }
    
    // Ensure NgayTao column exists
    $check_ngaytao = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'NgayTao'");
    $ngaytao_exists = false;
    if ($check_ngaytao) {
        $nt_row = mysqli_fetch_assoc($check_ngaytao);
        $ngaytao_exists = intval($nt_row['c']) > 0;
    }
    if (!$ngaytao_exists) {
        @mysqli_query($conn, "ALTER TABLE `donhang` ADD `NgayTao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `ID_DonHang`");
    }
    
    // Deduct balance FIRST (before creating order) - if order fails, we'll refund
    $newBal = $soDu - $TongTien;
    $balance_update_result = mysqli_query($conn, "UPDATE khachhang SET SoDu=$newBal WHERE ID_KhachHang='$ID_KhachHang'");
    
    if (!$balance_update_result) {
        $error_msg = mysqli_error($conn);
        echo 'error: Lỗi khi trừ số dư. ' . ($error_msg ? htmlspecialchars($error_msg) : 'Vui lòng thử lại.');
        exit();
    }
    
    // Escape strings for SQL to prevent errors
    $HoVaTen_escaped = mysqli_real_escape_string($conn, $HoVaTen);
    $Email_escaped = mysqli_real_escape_string($conn, $Email);
    $Sdt_escaped = mysqli_real_escape_string($conn, $Sdt);
    $DiaChi_escaped = mysqli_real_escape_string($conn, $DiaChi);
    $ghiChu_escaped = mysqli_real_escape_string($conn, 'Thanh toán bằng số dư tài khoản');
    
    // Create order with paid status
    // Note: NgayTao will be set automatically by DEFAULT CURRENT_TIMESTAMP if column exists
    $sql_insert_donhang = "INSERT INTO `donhang` (`ID_DonHang`, `ID_KhachHang`, `HoVaTen`, `Email`, `SDT`, `DiaChi`, `TongTien`, `TrangThai`, `GhiChu`, `DaThanhToan`) 
    VALUES (NULL, '$ID_KhachHang', '$HoVaTen_escaped', '$Email_escaped', '$Sdt_escaped', '$DiaChi_escaped', '$TongTien', '0', '$ghiChu_escaped', '1')";
    
    $result_insert_donhang = mysqli_query($conn, $sql_insert_donhang);
    
    if ($result_insert_donhang) {
        $id_donhang = mysqli_insert_id($conn);
        
        if (!$id_donhang) {
            // If insert ID is 0, there might be an issue
            echo 'error: Không thể tạo đơn hàng. Vui lòng thử lại.';
            // Refund balance
            mysqli_query($conn, "UPDATE khachhang SET SoDu=$soDu WHERE ID_KhachHang='$ID_KhachHang'");
            exit();
        }
        
        // Insert order details
        $order_detail_error = false;
        for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
            $product_id_safe = intval($_SESSION['cart'][$i][0]);
            $quantity_safe = intval($_SESSION['cart'][$i][3]);
            
            $sql_insert_donhang_chitiet = "INSERT INTO `donhangchitiet` (`ID_DonHangChiTiet`, `ID_DonHang`, `ID_SanPham`, `SoLuong`) 
                            VALUES (NULL, '$id_donhang', '$product_id_safe', '$quantity_safe');";
            
            if (!mysqli_query($conn, $sql_insert_donhang_chitiet)) {
                $order_detail_error = true;
                break;
            }
        }
        
        if ($order_detail_error) {
            // If order details failed, delete order and refund
            mysqli_query($conn, "DELETE FROM donhang WHERE ID_DonHang='$id_donhang'");
            mysqli_query($conn, "UPDATE khachhang SET SoDu=$soDu WHERE ID_KhachHang='$ID_KhachHang'");
            echo 'error: Lỗi khi tạo chi tiết đơn hàng. Vui lòng thử lại.';
            exit();
        }
        
        // Insert admin notification
        mysqli_query($conn, "INSERT INTO `admin_notifications` (`type`, `message`, `order_id`, `user_id`) 
                            VALUES ('order_paid', '$notification_msg_escaped', '$id_donhang', '$ID_KhachHang')");
        
        // Clear cart after successful order creation
        unset($_SESSION['cart']);
        unset($_SESSION['sum_Total']);
        
        // Send WebSocket notification to Admin and Staff
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            try {
                require_once($wsPushFile);
                if (function_exists('websocket_push_new_order')) {
                    $notification_result = websocket_push_new_order($id_donhang, $HoVaTen, $TongTien);
                    if ($notification_result) {
                        error_log("XuLy_DonHang_Balance.php: ✅ Notification sent successfully for order #{$id_donhang}");
                    } else {
                        error_log("XuLy_DonHang_Balance.php: ⚠️ Notification function returned false for order #{$id_donhang}");
                    }
                } else {
                    error_log("XuLy_DonHang_Balance.php: ❌ Function websocket_push_new_order not found");
                }
            } catch (Exception $e) {
                error_log("XuLy_DonHang_Balance.php: ❌ Exception sending notification: " . $e->getMessage());
            }
        } else {
            error_log("XuLy_DonHang_Balance.php: ❌ WebSocket_Push.php file not found at: {$wsPushFile}");
        }
        
        echo 'success';
        exit();
    } else {
        // If order creation failed, refund balance
        mysqli_query($conn, "UPDATE khachhang SET SoDu=$soDu WHERE ID_KhachHang='$ID_KhachHang'");
        $error_msg = mysqli_error($conn);
        
        // Log the actual SQL error for debugging
        error_log("Order creation failed - SQL: $sql_insert_donhang");
        error_log("Order creation failed - Error: " . $error_msg);
        
        // Provide detailed error message
        $error_message = 'Lỗi khi tạo đơn hàng. ';
        if ($error_msg) {
            $error_message .= htmlspecialchars($error_msg);
        } else {
            $error_message .= 'Vui lòng thử lại.';
        }
        echo 'error: ' . $error_message;
        exit();
    }
} else {
    // Log why the condition failed
    $missing_items = [];
    if (!isset($_SESSION['user'])) $missing_items[] = 'user session';
    if (!isset($_SESSION['cart'])) $missing_items[] = 'cart session';
    if (empty($_SESSION['cart'])) $missing_items[] = 'cart is empty';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') $missing_items[] = 'not POST request';
    
    error_log("XuLy_DonHang_Balance.php condition failed - Missing: " . implode(', ', $missing_items));
    echo 'error: Thiếu thông tin cần thiết để tạo đơn hàng. Vui lòng thử lại.';
}
?>

