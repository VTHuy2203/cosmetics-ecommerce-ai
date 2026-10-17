<?php
declare(strict_types=1);
require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit; }
require_csrf();
if (!in_array($_SESSION['admin_role'] ?? null, ['admin', 'manager'], true)) { http_response_code(403); exit; }
$id = (int) ($_POST['id'] ?? 0);
$statement = $conn->prepare('UPDATE nap_tien SET TrangThai = 2 WHERE ID_Nap = ? AND TrangThai = 0');
$statement->bind_param('i', $id);
$statement->execute();
header('Location: Manage_Users.php');
exit;
