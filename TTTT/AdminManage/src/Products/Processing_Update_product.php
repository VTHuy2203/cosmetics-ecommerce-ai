<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/uploads.php';
require_once dirname(__DIR__, 3) . '/config/catalog.php';
require_once '../Layouts/Admin_Access_Check.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: Manage_Product.php');
    exit;
}
require_csrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$name = trim((string) ($_POST['tenSanPham'] ?? ''));
$categoryId = (int) ($_POST['tenDanhMuc'] ?? 0);
$brandId = (int) ($_POST['tenThuongHieu'] ?? 0);
$quantityToAdd = (int) ($_POST['soLuongThem'] ?? 0);
$price = (int) ($_POST['giaTien'] ?? 0);
$description = (string) ($_POST['moTaChiTiet'] ?? '');

if ($productId < 1 || $categoryId < 1 || $brandId < 1
    || $name === '' || mb_strlen($name) > 5000
    || $quantityToAdd < 0 || $quantityToAdd > 1_000_000
    || $price < 0 || $price > 2_000_000_000
    || mb_strlen($description) > 10_000) {
    http_response_code(422);
    exit('Dữ liệu sản phẩm không hợp lệ.');
}

$imagePath = null;
try {
    $conn->begin_transaction();
    if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $fileName = store_public_image_upload($_FILES['image'], __DIR__ . '/IMG_Products');
        $imagePath = 'IMG_Products/' . $fileName;
    }

    if ($imagePath !== null) {
        $statement = $conn->prepare(
            'UPDATE sanpham SET TenSanPham = ?, IDDanhMuc = ?, IDThuongHieu = ?,
             SoLuong = SoLuong + ?, GiaTien = ?, MoTaChiTiet = ?, AnhSanPham = ? WHERE IDSanPham = ?'
        );
        $statement->bind_param(
            'siiiissi',
            $name,
            $categoryId,
            $brandId,
            $quantityToAdd,
            $price,
            $description,
            $imagePath,
            $productId
        );
    } else {
        $statement = $conn->prepare(
            'UPDATE sanpham SET TenSanPham = ?, IDDanhMuc = ?, IDThuongHieu = ?,
             SoLuong = SoLuong + ?, GiaTien = ?, MoTaChiTiet = ? WHERE IDSanPham = ?'
        );
        $statement->bind_param('siiiisi', $name, $categoryId, $brandId, $quantityToAdd, $price, $description, $productId);
    }
    $statement->execute();
    $exists = $conn->prepare('SELECT 1 FROM sanpham WHERE IDSanPham = ? LIMIT 1');
    $exists->bind_param('i', $productId);
    $exists->execute();
    if ($exists->get_result()->fetch_row() === null) {
        throw new RuntimeException('Product does not exist.');
    }
    catalog_enqueue_rag_event($conn, $productId, 'upsert');
    $conn->commit();
    header('Location: Manage_Product.php?updated=1');
    exit;
} catch (Throwable $error) {
    try { $conn->rollback(); } catch (Throwable) {}
    error_log('Product update failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Không thể cập nhật sản phẩm.');
}
