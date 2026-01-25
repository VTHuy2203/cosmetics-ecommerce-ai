<?php 
	include("../DB_Connect.php");
	session_start();

	function ensurePaidColumnExists($conn) {
		$checkSql = "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'DaThanhToan'";
		$res = mysqli_query($conn, $checkSql);
		if ($res) {
			$row = mysqli_fetch_assoc($res);
			if (intval($row['c']) === 0) {
				$alter = "ALTER TABLE `donhang` ADD `DaThanhToan` TINYINT(1) NOT NULL DEFAULT 0 AFTER `TrangThai`";
				@mysqli_query($conn, $alter);
			}
		}
	}
	
	function ensureNgayTaoColumnExists($conn) {
		$checkSql = "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donhang' AND COLUMN_NAME = 'NgayTao'";
		$res = mysqli_query($conn, $checkSql);
		if ($res) {
			$row = mysqli_fetch_assoc($res);
			if (intval($row['c']) === 0) {
				$alter = "ALTER TABLE `donhang` ADD `NgayTao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `ID_DonHang`";
				@mysqli_query($conn, $alter);
			}
		}
	}

	if(isset($_SESSION['user']) && isset($_SESSION['cart']) && !empty($_SESSION['cart']) && $_SERVER['REQUEST_METHOD'] === 'POST')
	{
		ensurePaidColumnExists($conn);
		ensureNgayTaoColumnExists($conn);

		$ghiChuClient = isset($_POST['GhiChu']) ? mysqli_real_escape_string($conn, $_POST['GhiChu']) : '';

		// Get customer
		$sql_inf_user = "select * from khachhang where TenDangNhap = '" . mysqli_real_escape_string($conn, $_SESSION['user']) . "'";
		$result = mysqli_query($conn, $sql_inf_user);
		$row = mysqli_fetch_array($result);
		
		if (!$row) {
			echo 'error: Không tìm thấy thông tin khách hàng.';
			exit();
		}
		
		$ID_KhachHang = $row['ID_KhachHang'];
		$HoVaTen = $row['HoVaTen'];
		$Email = $row['Email'];
		$Sdt = $row['SDT'];
		$DiaChi = $row['DiaChi'];

		// Recalculate total from cart to ensure accuracy
		$calculated_total = 0;
		if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
			for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
				if (isset($_SESSION['cart'][$i][4]) && isset($_SESSION['cart'][$i][3])) {
					$calculated_total += intval($_SESSION['cart'][$i][4]) * intval($_SESSION['cart'][$i][3]);
				}
			}
		}
		
		// Use calculated total or fallback to session total
		$subtotal = $calculated_total > 0 ? $calculated_total : (isset($_SESSION['sum_Total']) ? intval($_SESSION['sum_Total']) : 0);
		
		if ($subtotal <= 0) {
			error_log("Invalid total - User ID: $ID_KhachHang, Calculated: $calculated_total, Session: " . (isset($_SESSION['sum_Total']) ? $_SESSION['sum_Total'] : 'not set'));
			echo 'error: Tổng tiền không hợp lệ. Vui lòng kiểm tra lại giỏ hàng.';
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
		
		// Debug: Log total calculation
		error_log("Total calculation - User ID: $ID_KhachHang, Subtotal: $subtotal, Shipping: $shipping_fee, Final: $TongTien");

		// Check balance payment
		$payByBalance = isset($_POST['pay_by_balance']) && $_POST['pay_by_balance'] == '1';
		$paidFlag = 0;
		if ($payByBalance) {
			// Ensure SoDu column exists
			$checkCol = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
			$colExists = false;
			if ($checkCol) {
				$colRow = mysqli_fetch_assoc($checkCol);
				$colExists = intval($colRow['c']) > 0;
			}
			if (!$colExists) {
				@mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
			}
			
			// Get balance with proper error checking
			$balance_query = mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'");
			if (!$balance_query) {
				$error_msg = mysqli_error($conn);
				error_log("Balance query failed - User ID: $ID_KhachHang, Error: $error_msg");
				echo 'error: Lỗi khi truy vấn số dư. Vui lòng thử lại.';
				exit();
			}
			
			$u = mysqli_fetch_array($balance_query);
			if (!$u || !isset($u['SoDu'])) {
				error_log("Balance data not found - User ID: $ID_KhachHang");
				echo 'error: Không tìm thấy thông tin số dư. Vui lòng thử lại.';
				exit();
			}
			
			$soDu = isset($u['SoDu']) ? intval($u['SoDu']) : 0;
			
			// Debug: Log balance check
			error_log("Balance payment check - User ID: $ID_KhachHang, Balance: $soDu, Total: $TongTien, Sufficient: " . ($soDu >= $TongTien ? 'yes' : 'no'));
			
			if ($soDu >= $TongTien) {
				$newBal = $soDu - $TongTien;
				$update_result = mysqli_query($conn, "UPDATE khachhang SET SoDu=$newBal WHERE ID_KhachHang='$ID_KhachHang'");
				if ($update_result) {
					$paidFlag = 1;
				} else {
					$error_msg = mysqli_error($conn);
					error_log("Balance update failed - User ID: $ID_KhachHang, Error: $error_msg");
					echo 'error: Lỗi khi trừ số dư. Vui lòng thử lại.';
					exit();
				}
			} else {
				error_log("Insufficient balance - User ID: $ID_KhachHang, Balance: $soDu, Required: $TongTien");
				echo 'insufficient_balance';
				exit();
			}
		}

		// Build transfer description from cart
		$parts = [];
		for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
			$parts[] = $_SESSION['cart'][$i][1] . 'x' . intval($_SESSION['cart'][$i][3]);
		}
		$noiDungCK = substr(implode(', ', $parts), 0, 120);

		// Extend orders table with payment fields if exist; otherwise store in GhiChu
		$ghiChu = trim($ghiChuClient) === '' ? ('QR: ' . $noiDungCK) : ($ghiChuClient . ' | QR: ' . $noiDungCK);
		$ghiChu_escaped = mysqli_real_escape_string($conn, $ghiChu);
		
		// Escape all values for SQL
		$HoVaTen_escaped = mysqli_real_escape_string($conn, $HoVaTen);
		$Email_escaped = mysqli_real_escape_string($conn, $Email);
		$Sdt_escaped = mysqli_real_escape_string($conn, $Sdt);
		$DiaChi_escaped = mysqli_real_escape_string($conn, $DiaChi);

		$sql_insert_donhang = "INSERT INTO `donhang` (`ID_DonHang`, `ID_KhachHang`, `HoVaTen`, `Email`, `SDT`, `DiaChi`, `TongTien`, `TrangThai`, `GhiChu`, `DaThanhToan`) 
		VALUES (NULL, '$ID_KhachHang', '$HoVaTen_escaped', '$Email_escaped', '$Sdt_escaped', '$DiaChi_escaped', '$TongTien', '0', '$ghiChu_escaped', '$paidFlag')";

		$result_insert_donhang = mysqli_query($conn, $sql_insert_donhang);

		if ($result_insert_donhang){
			$id_donhang = mysqli_insert_id($conn);
			
			if (!$id_donhang) {
				// Refund balance if order ID is 0
				if ($payByBalance) {
					$u = mysqli_fetch_array(mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'"));
					$currentBal = isset($u['SoDu']) ? intval($u['SoDu']) : 0;
					mysqli_query($conn, "UPDATE khachhang SET SoDu=" . ($currentBal + $TongTien) . " WHERE ID_KhachHang='$ID_KhachHang'");
				}
				echo 'error: Không thể tạo đơn hàng. Vui lòng thử lại.';
				exit();
			}
			
			// Insert order details
			$order_detail_error = false;
			for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
				$product_id_safe = intval($_SESSION['cart'][$i][0]);
				$quantity_safe = intval($_SESSION['cart'][$i][3]);
				
				$sql_insert_donhang_chitiet = "INSERT INTO `donhangchitiet` (`ID_DonHangChiTiet`, `ID_DonHang`, `ID_SanPham`, `SoLuong`) 
								VALUES (NULL, '$id_donhang', '$product_id_safe', '$quantity_safe')";
				
				if (!mysqli_query($conn, $sql_insert_donhang_chitiet)) {
					$order_detail_error = true;
					break;
				}
			}
			
			if ($order_detail_error) {
				// Delete order and refund balance if order details failed
				mysqli_query($conn, "DELETE FROM donhang WHERE ID_DonHang='$id_donhang'");
				if ($payByBalance) {
					$u = mysqli_fetch_array(mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'"));
					$currentBal = isset($u['SoDu']) ? intval($u['SoDu']) : 0;
					mysqli_query($conn, "UPDATE khachhang SET SoDu=" . ($currentBal + $TongTien) . " WHERE ID_KhachHang='$ID_KhachHang'");
				}
				echo 'error: Lỗi khi tạo chi tiết đơn hàng. Vui lòng thử lại.';
				exit();
			}
			
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
							error_log("XuLy_DonHang_QR.php: ✅ Notification sent successfully for order #{$id_donhang}");
						} else {
							error_log("XuLy_DonHang_QR.php: ⚠️ Notification function returned false for order #{$id_donhang}");
						}
					} else {
						error_log("XuLy_DonHang_QR.php: ❌ Function websocket_push_new_order not found");
					}
				} catch (Exception $e) {
					error_log("XuLy_DonHang_QR.php: ❌ Exception sending notification: " . $e->getMessage());
				}
			} else {
				error_log("XuLy_DonHang_QR.php: ❌ WebSocket_Push.php file not found at: {$wsPushFile}");
			}
			
			echo 'success';
		}else{
			// Refund balance if order creation failed
			if ($payByBalance) {
				$u = mysqli_fetch_array(mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'"));
				$currentBal = isset($u['SoDu']) ? intval($u['SoDu']) : 0;
				mysqli_query($conn, "UPDATE khachhang SET SoDu=" . ($currentBal + $TongTien) . " WHERE ID_KhachHang='$ID_KhachHang'");
			}
			$error_msg = mysqli_error($conn);
			error_log("XuLy_DonHang_QR.php - Order creation failed: " . $error_msg);
			error_log("SQL: " . $sql_insert_donhang);
			echo 'error: Lỗi khi tạo đơn hàng. ' . ($error_msg ? htmlspecialchars($error_msg) : 'Vui lòng thử lại.');
		}
	}
	else{
		$missing_items = [];
		if (!isset($_SESSION['user'])) $missing_items[] = 'user session';
		if (!isset($_SESSION['cart'])) $missing_items[] = 'cart session';
		if (!isset($_SESSION['sum_Total'])) $missing_items[] = 'sum_Total session';
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') $missing_items[] = 'not POST request';
		error_log("XuLy_DonHang_QR.php condition failed - Missing: " . implode(', ', $missing_items));
		echo 'error: Thiếu thông tin cần thiết để tạo đơn hàng. Vui lòng thử lại.';
	}
?>
