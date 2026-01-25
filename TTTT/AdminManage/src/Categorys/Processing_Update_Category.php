<?php
require_once "../DB_Connect.php";

// Kiểm tra form gửi là post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    // Lấy dữ liệu từ form
    $tenDanhMuc = $_POST['tenDanhMuc'];
    $category_id = $_POST['category_id'];

    // Kiểm tra xem người dùng đã chọn hình ảnh mới hay không
    $update_image = false;
    $target_file = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        //check file có phải là ảnh không
        $check = getimagesize($_FILES["image"]["tmp_name"]);

        if ($check !== false) {
            $update_image = true;

            $target_dir = "IMG_Category/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);

            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }
    }

    // Thực hiện truy vấn để cập nhật thông tin sản phẩm trong CSDL
    $update_sql = "UPDATE danhmuc SET TenDanhMuc='$tenDanhMuc'";

    if ($update_image) {
        // Nếu người dùng đã chọn hình ảnh mới, cập nhật đường dẫn hình ảnh
        $update_sql .= ", AnhDanhMuc='$target_file'";
    }

    $update_sql .= " WHERE IDDanhMuc='$category_id'";

    $result = mysqli_query($conn, $update_sql);

    if ($result) {
        // Send WebSocket notification to Staff and Customers
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_category_update')) {
                websocket_push_category_update($category_id, $tenDanhMuc, 'update');
            }
        }
    }

    header('Location: Manage_Category.php');
    exit();
} else {
    header('Location: Manage_Category.php');
    exit();
}

mysqli_close($conn);
?>