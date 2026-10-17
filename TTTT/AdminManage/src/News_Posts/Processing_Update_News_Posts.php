<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/uploads.php';
require_once '../Layouts/Admin_Access_Check.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { header('Location: Manage_News_Posts.php'); exit; }
require_csrf();

$id = (int) ($_POST['News_Posts_id'] ?? 0);
$title = trim((string) ($_POST['tieuDeBaiViet'] ?? ''));
$content = (string) ($_POST['baiViet'] ?? '');
if ($id < 1 || $title === '' || mb_strlen($title) > 500 || mb_strlen($content) > 100000) {
    http_response_code(422); exit('Dữ liệu không hợp lệ.');
}

$image = null;
if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $image = 'IMG_News_Posts/' . store_public_image_upload($_FILES['image'], __DIR__ . '/IMG_News_Posts');
}
if ($image !== null) {
    $statement = $conn->prepare(
        'UPDATE tintucbaiviet SET TieuDeBaiViet = ?, BaiViet = ?, AnhBaiViet = ? WHERE ID_TinTucBaiViet = ?'
    );
    $statement->bind_param('sssi', $title, $content, $image, $id);
} else {
    $statement = $conn->prepare('UPDATE tintucbaiviet SET TieuDeBaiViet = ?, BaiViet = ? WHERE ID_TinTucBaiViet = ?');
    $statement->bind_param('ssi', $title, $content, $id);
}
$statement->execute();
header('Location: Manage_News_Posts.php?updated=1');
exit;
