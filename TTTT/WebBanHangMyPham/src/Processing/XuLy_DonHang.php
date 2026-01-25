<?php 
    include("../DB_Connect.php");
    session_start();

    function recalc_total_from_cart(): int {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
        $sum = 0;
        for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
            $sum += intval($_SESSION['cart'][$i][4]) * intval($_SESSION['cart'][$i][3]);
        }
        return $sum;
    }

    if(isset($_POST['ID_KhachHang']) &&
        isset($_POST['HoVaTen']) &&
        isset($_POST['Email']) &&
        isset($_POST['Sdt']) &&
        isset($_POST['DiaChi']) &&
        isset($_POST['GhiChu']) &&
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_SESSION['cart']))
    {
        $ID_KhachHang = $_POST['ID_KhachHang'];
        $HoVaTen = $_POST['HoVaTen'];
        $Email = $_POST['Email'];
        $Sdt = $_POST['Sdt'];
        $DiaChi = $_POST['DiaChi'];
        $GhiChu = $_POST['GhiChu'];

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

        $subtotal = isset($_SESSION['sum_Total']) ? intval($_SESSION['sum_Total']) : recalc_total_from_cart();
        if ($subtotal <= 0) { echo 'error_total'; exit(); }
        
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

        $payByBalance = isset($_POST['pay_by_balance']) && $_POST['pay_by_balance'] == '1';
        $paidFlag = 0;
        if ($payByBalance) {
            // ensure SoDu column exists (check first to avoid duplicate column error)
            $check_sodu = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
            $sodu_exists = false;
            if ($check_sodu) {
                $sodu_row = mysqli_fetch_assoc($check_sodu);
                $sodu_exists = intval($sodu_row['c']) > 0;
            }
            if (!$sodu_exists) {
                @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
            }
            
            $u = mysqli_fetch_array(mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'"));
            $soDu = intval($u['SoDu'] ?? 0);
            if ($soDu >= $TongTien) {
                $newBal = $soDu - $TongTien;
                mysqli_query($conn, "UPDATE khachhang SET SoDu=$newBal WHERE ID_KhachHang='$ID_KhachHang'");
                $paidFlag = 1;
            } else {
                echo 'insufficient_balance';
                exit();
            }
        }

        // ensure DaThanhToan column exists
        $check_dathanhtoan = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'DaThanhToan'");
        $dathanhtoan_exists = false;
        if ($check_dathanhtoan) {
            $dt_row = mysqli_fetch_assoc($check_dathanhtoan);
            $dathanhtoan_exists = intval($dt_row['c']) > 0;
        }
        if (!$dathanhtoan_exists) {
            @mysqli_query($conn, "ALTER TABLE `donhang` ADD `DaThanhToan` TINYINT(1) NOT NULL DEFAULT 0 AFTER `TrangThai`");
        }
        
        // ensure NgayTao column exists
        $check_ngaytao = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'NgayTao'");
        $ngaytao_exists = false;
        if ($check_ngaytao) {
            $nt_row = mysqli_fetch_assoc($check_ngaytao);
            $ngaytao_exists = intval($nt_row['c']) > 0;
        }
        if (!$ngaytao_exists) {
            @mysqli_query($conn, "ALTER TABLE `donhang` ADD `NgayTao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `ID_DonHang`");
        }

        $sql_insert_donhang = "INSERT INTO `donhang` (`ID_DonHang`, `ID_KhachHang`, `HoVaTen`, `Email`, `SDT`, `DiaChi`, `TongTien`, `TrangThai`, `GhiChu`, `DaThanhToan`) 
        VALUES (NULL, '$ID_KhachHang', '$HoVaTen', '$Email', '$Sdt', '$DiaChi', '$TongTien', '0', '$GhiChu', '$paidFlag');";

        $result_insert_donhang = mysqli_query($conn, $sql_insert_donhang);
        
        // Check for SQL errors
        if (!$result_insert_donhang) {
            error_log("Order creation error: " . mysqli_error($conn));
            echo 'error';
            exit();
        }

        // Order created successfully
        $id_donhang = mysqli_insert_id($conn);
        
        if ($id_donhang > 0) {
            // Insert order details
            for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
                $product_id = intval($_SESSION['cart'][$i][0]);
                $quantity = intval($_SESSION['cart'][$i][3]);
                
                $sql_insert_donhang_chitiet = "INSERT INTO `donhangchitiet` (`ID_DonHangChiTiet`, `ID_DonHang`, `ID_SanPham`, `SoLuong`) 
                                VALUES (NULL, '$id_donhang', '$product_id', '$quantity');";

                $result_detail = mysqli_query($conn, $sql_insert_donhang_chitiet);
                if (!$result_detail) {
                    error_log("Order detail creation error: " . mysqli_error($conn));
                    // Rollback: delete the order if detail insertion fails
                    mysqli_query($conn, "DELETE FROM donhang WHERE ID_DonHang = '$id_donhang'");
                    echo 'error';
                    exit();
                }
            }
            
            // Clear cart and total after successful order creation
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
                            error_log("XuLy_DonHang.php: ✅ Notification sent successfully for order #{$id_donhang}");
                        } else {
                            error_log("XuLy_DonHang.php: ⚠️ Notification function returned false for order #{$id_donhang}");
                        }
                    } else {
                        error_log("XuLy_DonHang.php: ❌ Function websocket_push_new_order not found");
                    }
                } catch (Exception $e) {
                    error_log("XuLy_DonHang.php: ❌ Exception sending notification: " . $e->getMessage());
                }
            } else {
                error_log("XuLy_DonHang.php: ❌ WebSocket_Push.php file not found at: {$wsPushFile}");
            }
            
            echo 'success';
            exit();
        } else {
            error_log("Failed to get order ID after insertion");
            echo 'error';
            exit();
        }
    }
    else{
        echo 'error';
    }
?>