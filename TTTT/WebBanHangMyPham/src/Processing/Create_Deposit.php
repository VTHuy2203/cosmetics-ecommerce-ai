<?php
include("../DB_Connect.php");
session_start();
if (!isset($_SESSION['user'])) { header('Location: ../View/Login.php'); exit(); }

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['SoTien'])) {
	$SoTien = intval($_POST['SoTien']);
	$NoiDung = isset($_POST['NoiDung']) ? mysqli_real_escape_string($conn, $_POST['NoiDung']) : 'Nạp tiền vào tài khoản';
	$u = mysqli_fetch_array(mysqli_query($conn, "select * from khachhang where TenDangNhap='".$_SESSION['user']."'"));
	$id = $u['ID_KhachHang'];
	$customerName = $u['HoVaTen'] ?? 'Khách hàng';

	// table ensured in Personal.php
	$sql = "INSERT INTO nap_tien (ID_KhachHang, SoTien, NoiDung, TrangThai) VALUES ('$id', '$SoTien', '$NoiDung', 0)";
	$result = mysqli_query($conn, $sql);

	// Send WebSocket notification to Admin about deposit request
	if ($result) {
		$wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
		error_log("Create_Deposit.php: WebSocket push file path: " . $wsPushFile);
		error_log("Create_Deposit.php: File exists: " . (file_exists($wsPushFile) ? 'YES' : 'NO'));

		if (file_exists($wsPushFile)) {
			$old_error_reporting = error_reporting(E_ALL);
			$old_display_errors = ini_get('display_errors');
			ini_set('display_errors', 0);
			try {
				require_once($wsPushFile);
				error_log("Create_Deposit.php: Function exists: " . (function_exists('websocket_push_deposit_request') ? 'YES' : 'NO'));

				if (function_exists('websocket_push_deposit_request')) {
					$push_result = websocket_push_deposit_request($id, $customerName, $SoTien, $NoiDung);
					error_log("Create_Deposit.php: Push result: " . ($push_result ? 'SUCCESS' : 'FAILED'));
				} else {
					error_log("Create_Deposit.php: websocket_push_deposit_request function NOT FOUND");
				}
			} catch (Exception $e) {
				error_log("Create_Deposit.php: Error sending WebSocket notification: " . $e->getMessage());
			}
			error_reporting($old_error_reporting);
			ini_set('display_errors', $old_display_errors);
		} else {
			error_log("Create_Deposit.php: WebSocket push file NOT FOUND at: " . $wsPushFile);
		}
	}

	header('Location: ../View/Personal.php');
	exit();
}
header('Location: ../View/Personal.php');
