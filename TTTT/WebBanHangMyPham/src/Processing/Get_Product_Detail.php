<?php
/**
 * Get Product Detail API
 * Returns product information as JSON for WebSocket realtime updates
 */

header('Content-Type: application/json; charset=utf-8');

include("../DB_Connect.php");

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'Product ID required']);
    exit();
}

$product_id = intval($_GET['product_id']);

$sql = "SELECT IDSanPham, TenSanPham, GiaTien, AnhSanPham, SoLuong, MoTaChiTiet 
        FROM sanpham 
        WHERE IDSanPham = '$product_id' 
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
    echo json_encode($product);
} else {
    echo json_encode(['error' => 'Product not found']);
}

