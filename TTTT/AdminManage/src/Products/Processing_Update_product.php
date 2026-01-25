<?php
require_once "../DB_Connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $tenSanPham = $_POST['tenSanPham'];
    $tenDanhMuc = $_POST['tenDanhMuc'];
    $tenThuongHieu = $_POST['tenThuongHieu'];
    
    // Get current quantity and add quantity to increase
    $soLuongHienTai = isset($_POST['soLuong']) ? intval($_POST['soLuong']) : 0;
    $soLuongThem = isset($_POST['soLuongThem']) ? intval($_POST['soLuongThem']) : 0;
    $soLuong = $soLuongHienTai + $soLuongThem; // Increase quantity
    
    $giaTien = $_POST['giaTien'];
    $moTaChiTiet = $_POST['moTaChiTiet'];

    $update_image = false;
    $target_file = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $check = getimagesize($_FILES["image"]["tmp_name"]);

        if ($check !== false) {
            $update_image = true;
            $target_dir = "IMG_Products/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }
    }

    $update_sql = "UPDATE sanpham SET 
                   TenSanPham=?, 
                   IDDanhMuc=?, 
                   IDThuongHieu=?, 
                   SoLuong=?, 
                   GiaTien=?, 
                   MoTaChiTiet=?"
        . ($update_image ? ", AnhSanPham=?" : "")
        . " WHERE IDSanPham=?";

    $stmt = $conn->prepare($update_sql);

    if ($update_image) {
        $stmt->bind_param("sssssssi", $tenSanPham, $tenDanhMuc, $tenThuongHieu, $soLuong, $giaTien, $moTaChiTiet, $target_file, $product_id);
    } else {
        $stmt->bind_param("ssssssi", $tenSanPham, $tenDanhMuc, $tenThuongHieu, $soLuong, $giaTien, $moTaChiTiet, $product_id);
    }

    $stmt->execute();
    
    // Send WebSocket notification to users about product update
    $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
    if (file_exists($wsPushFile)) {
        require_once($wsPushFile);
        if (function_exists('websocket_push_product_update')) {
            websocket_push_product_update($product_id, $tenSanPham, 'update');
        }
    }
    
    header('Location: Manage_Product.php');
    exit();
} else {
    header('Location: Manage_Product.php');
    exit();
}

mysqli_close($conn);
