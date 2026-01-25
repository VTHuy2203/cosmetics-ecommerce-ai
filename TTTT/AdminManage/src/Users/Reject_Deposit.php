<?php
include("../DB_Connect.php");
if (!isset($_GET['id'])) { header('Location: Manage_Users.php'); exit(); }
$id = $_GET['id'];
mysqli_query($conn, "UPDATE nap_tien SET TrangThai=2 WHERE ID_Nap='$id'");
header('Location: Manage_Users.php');
exit();
?>
