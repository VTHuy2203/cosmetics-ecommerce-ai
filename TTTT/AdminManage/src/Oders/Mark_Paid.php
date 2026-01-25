<?php
include("../DB_Connect.php");

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

if (isset($_GET['oder_id'])) {
	ensurePaidColumnExists($conn);
	$oder_id = intval($_GET['oder_id']);
	$sql = "UPDATE `donhang` SET `DaThanhToan` = '1' WHERE `ID_DonHang` = '$oder_id'";
	$result = mysqli_query($conn, $sql);
	
	if ($result) {
		// Get order info for notification
		$order_query = mysqli_query($conn, "SELECT ID_KhachHang, HoVaTen, TongTien FROM donhang WHERE ID_DonHang = '$oder_id'");
		$order_info = mysqli_fetch_array($order_query);
		$customer_name = $order_info['HoVaTen'] ?? 'Khách hàng';
		$customer_id = $order_info['ID_KhachHang'] ?? null;
		$total_amount = $order_info['TongTien'] ?? 0;
		
		// Get admin/staff name who marked as paid
		$marker_name = 'Admin';
		if (isset($_SESSION['admin_name'])) {
			$marker_name = $_SESSION['admin_name'];
		} elseif (isset($_SESSION['admin_username'])) {
			$marker_name = $_SESSION['admin_username'];
		}
		
		// Send WebSocket notification about payment status update
		$wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
		if (file_exists($wsPushFile)) {
			try {
				require_once($wsPushFile);
				
				if (function_exists('websocket_push')) {
					// Notify Admin and Staff - payment confirmed
					$result1 = websocket_push(
						['admin_channel', 'staff_channel'],
						[
							'type' => 'order_paid',
							'title' => 'Đơn hàng đã thanh toán',
							'message' => "Đơn hàng #{$oder_id} của khách hàng {$customer_name} đã được {$marker_name} đánh dấu là đã thanh toán. Tổng tiền: " . number_format($total_amount, 0, ',', '.') . " đ",
							'sound' => 'notification',
							'data' => [
								'order_id' => $oder_id,
								'customer_name' => $customer_name,
								'customer_id' => $customer_id,
								'total_amount' => $total_amount,
								'marker_name' => $marker_name,
								'timestamp' => date('Y-m-d H:i:s')
							]
						]
					);
					
					// Notify User - payment confirmed
					if ($customer_id) {
						$result2 = websocket_push(
							['user_channel'],
							[
								'type' => 'order_paid',
								'title' => 'Đơn hàng đã thanh toán',
								'message' => "Đơn hàng #{$oder_id} của bạn đã được xác nhận thanh toán",
								'sound' => 'notification',
								'data' => [
									'order_id' => $oder_id,
									'customer_id' => $customer_id,
									'total_amount' => $total_amount,
									'timestamp' => date('Y-m-d H:i:s')
								]
							]
						);
					}
					
					// Notify Shipper if order is assigned
					$result3 = websocket_push(
						['shipper_channel'],
						[
							'type' => 'order_paid',
							'title' => 'Đơn hàng đã thanh toán',
							'message' => "Đơn hàng #{$oder_id} của khách hàng {$customer_name} đã được xác nhận thanh toán",
							'sound' => 'notification',
							'data' => [
								'order_id' => $oder_id,
								'customer_name' => $customer_name,
								'total_amount' => $total_amount,
								'timestamp' => date('Y-m-d H:i:s')
							]
						]
					);
					
					if ($result1 || (isset($result2) && $result2) || $result3) {
						error_log("Mark_Paid.php: ✅ Notifications sent successfully for order #{$oder_id}");
					} else {
						error_log("Mark_Paid.php: ⚠️ Notification functions returned false for order #{$oder_id}");
					}
				} else {
					error_log("Mark_Paid.php: ❌ Function websocket_push not found");
				}
			} catch (Exception $e) {
				error_log("Mark_Paid.php: ❌ Exception sending WebSocket notification: " . $e->getMessage());
			}
		} else {
			error_log("Mark_Paid.php: ❌ WebSocket_Push.php file not found at: {$wsPushFile}");
		}
	}
	
	header('Location: All_Oders.php');
	exit();
}
?>
