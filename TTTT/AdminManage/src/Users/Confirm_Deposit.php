<?php
include("../DB_Connect.php");
if (!isset($_GET['id'])) { header('Location: Manage_Users.php'); exit(); }
$id = $_GET['id'];
$dep = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM nap_tien WHERE ID_Nap='$id'"));
if ($dep && intval($dep['TrangThai'])===0) {
	$uid = $dep['ID_KhachHang'];
	$soTien = intval($dep['SoTien']);
	mysqli_query($conn, "UPDATE nap_tien SET TrangThai=1 WHERE ID_Nap='$id'");
	mysqli_query($conn, "UPDATE khachhang SET SoDu = SoDu + $soTien WHERE ID_KhachHang='$uid'");
}
header('Location: Manage_Users.php');
exit();
?>
