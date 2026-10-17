<?php
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/uploads.php';
if (!isset($_SESSION['user'])) { echo 'error'; exit; }

$userStatement = $conn->prepare('SELECT * FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
$sessionUsername = (string) $_SESSION['user'];
$userStatement->bind_param('s', $sessionUsername);
$userStatement->execute();
$u = $userStatement->get_result()->fetch_assoc();
if (!$u) { echo 'error'; exit; }
$id = $u['ID_KhachHang'];

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
	require_csrf();
	$action = $_POST['action'];
	if ($action === 'info') {
		$HoVaTen = trim((string) ($_POST['HoVaTen'] ?? ''));
		$Email = strtolower(trim((string) ($_POST['Email'] ?? '')));
		$SDT = trim((string) ($_POST['SDT'] ?? ''));
		$DiaChi = trim((string) ($_POST['DiaChi'] ?? ''));
		if ($HoVaTen === '' || mb_strlen($HoVaTen) > 50
			|| !filter_var($Email, FILTER_VALIDATE_EMAIL) || mb_strlen($Email) > 100
			|| !preg_match('/^[0-9+().\s-]{8,20}$/', $SDT)
			|| $DiaChi === '' || mb_strlen($DiaChi) > 255) {
			header('Location: ../View/Personal.php?error=invalid_profile');
			exit();
		}
		$updateProfile = $conn->prepare(
			'UPDATE khachhang SET HoVaTen = ?, Email = ?, SDT = ?, DiaChi = ? WHERE ID_KhachHang = ?'
		);
		$updateProfile->bind_param('ssssi', $HoVaTen, $Email, $SDT, $DiaChi, $id);
		$result = $updateProfile->execute();
		
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
			try {
				$uploadDirectory = dirname(__DIR__, 2) . '/Image_upload/avatars';
				$filename = store_public_image_upload($_FILES['avatar'], $uploadDirectory, 3 * 1024 * 1024);
				$path = '/TTTT/WebBanHangMyPham/Image_upload/avatars/' . $filename;
				$update = $conn->prepare('UPDATE khachhang SET AnhDaiDien = ? WHERE ID_KhachHang = ?');
				$update->bind_param('si', $path, $id);
				$update->execute();
			} catch (Throwable $error) {
				error_log('Avatar upload failed: ' . $error->getMessage());
				header('Location: ../View/Personal.php?error=invalid_avatar');
				exit();
			}
		}
		header('Location: ../View/Personal.php');
		exit();
	}
}
header('Location: ../View/Personal.php');
