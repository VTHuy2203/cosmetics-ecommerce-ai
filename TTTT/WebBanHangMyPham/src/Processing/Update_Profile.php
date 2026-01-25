<?php
include("../DB_Connect.php");
session_start();
if (!isset($_SESSION['user'])) { echo 'error'; exit; }

$userSql = "select * from khachhang where TenDangNhap='".$_SESSION['user']."'";
$res = mysqli_query($conn, $userSql);
$u = mysqli_fetch_array($res);
$id = $u['ID_KhachHang'];

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
	$action = $_POST['action'];
	if ($action === 'info') {
		$HoVaTen = mysqli_real_escape_string($conn, $_POST['HoVaTen']);
		$Email = mysqli_real_escape_string($conn, $_POST['Email']);
		$SDT = mysqli_real_escape_string($conn, $_POST['SDT']);
		$DiaChi = mysqli_real_escape_string($conn, $_POST['DiaChi']);
		$sql = "UPDATE khachhang SET HoVaTen='$HoVaTen', Email='$Email', SDT='$SDT', DiaChi='$DiaChi' WHERE ID_KhachHang='$id'";
		$result = mysqli_query($conn, $sql);
		
		if ($result) {
			// Send WebSocket notification to Admin
			$wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
			if (file_exists($wsPushFile)) {
				require_once($wsPushFile);
				if (function_exists('websocket_push_customer_info_updated')) {
					websocket_push_customer_info_updated($id, $HoVaTen);
				}
			}
		}
		
		header('Location: ../View/Personal.php');
		exit();
	} elseif ($action === 'avatar') {
		if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
			$folder = '../../Image_upload/';
			@mkdir($folder, 0777, true);
			$filename = time() . '_' . basename($_FILES['avatar']['name']);
			$path = $folder . $filename;
			move_uploaded_file($_FILES['avatar']['tmp_name'], $path);
			$rel = str_replace('..', '/TTTT/WebBanHangMyPham/src', $path); // keep simple web path if needed
			$sql = "UPDATE khachhang SET AnhDaiDien='".$path."' WHERE ID_KhachHang='$id'";
			mysqli_query($conn, $sql);
		}
		header('Location: ../View/Personal.php');
		exit();
	}
}
header('Location: ../View/Personal.php');
