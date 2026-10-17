<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/uploads.php';
require_once '../Layouts/Admin_Access_Check.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { header('Location: Manage_Category.php'); exit; }
require_csrf();

$id = (int) ($_POST['category_id'] ?? 0);
$name = trim((string) ($_POST['tenDanhMuc'] ?? ''));
if ($id < 1 || $name === '' || mb_strlen($name) > 255) { http_response_code(422); exit('Dữ liệu không hợp lệ.'); }

$image = null;
if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $image = 'IMG_Category/' . store_public_image_upload($_FILES['image'], __DIR__ . '/IMG_Category');
}
if ($image !== null) {
    $statement = $conn->prepare('UPDATE danhmuc SET TenDanhMuc = ?, AnhDanhMuc = ? WHERE IDDanhMuc = ?');
    $statement->bind_param('ssi', $name, $image, $id);
} else {
    $statement = $conn->prepare('UPDATE danhmuc SET TenDanhMuc = ? WHERE IDDanhMuc = ?');
    $statement->bind_param('si', $name, $id);
}
$statement->execute();
header('Location: Manage_Category.php?updated=1');
exit;
