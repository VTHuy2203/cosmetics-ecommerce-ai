<?php
declare(strict_types=1);
require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/config/catalog.php';
require_once '../Layouts/Admin_Access_Check.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit; }
require_csrf();
$id = (int) ($_POST['category_id'] ?? 0);
catalog_delete_redirect('Manage_Category.php', catalog_delete($conn, 'category', $id));
