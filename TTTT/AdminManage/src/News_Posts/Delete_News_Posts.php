<?php
declare(strict_types=1);
require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit; }
require_csrf();
$id = (int) ($_POST['News_Posts_id'] ?? 0);
$statement = $conn->prepare('DELETE FROM tintucbaiviet WHERE ID_TinTucBaiViet = ?');
$statement->bind_param('i', $id);
$statement->execute();
header('Location: Manage_News_Posts.php');
exit;
