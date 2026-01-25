<?php
require_once "../DB_Connect.php";

// Kiểm tra xem ID sản phẩm đã được truyền vào hay chưa
if (!isset($_GET['product_id'])) {
    header('Location: Manage_Product.php');
    exit();
}

$product_id = $_GET['product_id'];

// Lấy thông tin sản phẩm từ CSDL để hiển thị trên thông báo xác nhận xóa
$sql = "SELECT * FROM sanpham WHERE IDSanPham = '$product_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Kiểm tra xem sản phẩm có tồn tại hay không
if (!$row) {
    header('Location: Manage_Product.php');
    exit();
}

// Xử lý logic khi người dùng xác nhận xóa
if (isset($_POST['delete'])) {
    // Thực hiện truy vấn xóa sản phẩm trong CSDL
    $deleteSql = "DELETE FROM sanpham WHERE IDSanPham = '$product_id'";
    $deleteResult = mysqli_query($conn, $deleteSql);

    if ($deleteResult) {
        // Send WebSocket notification to users about product deletion
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_product_update')) {
                websocket_push_product_update($product_id, $row['TenSanPham'], 'delete');
            }
        }
        
        header('Location: Manage_Product.php');
        exit();
    } else {
        echo "Có lỗi xảy ra: " . mysqli_error($conn);
    }
}
?>

<?php include("../Layouts/headHTML.php"); ?>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <h2>Xóa Sản Phẩm</h2>
                <div class="alert alert-danger">
                    <p>Bạn có chắc chắn muốn xóa sản phẩm sau? Bạn sẽ không thể hoàn tác lại sản phẩm đã xóa!!!!</p>
                    <p><strong>Tên sản phẩm:</strong> <?php echo $row['TenSanPham']; ?></p>
                </div>

                <form method="POST">
                    <button type="submit" name="delete" class="btn btn-danger">Xóa</button>
                    <a href="Manage_Product.php" class="btn btn-secondary">Hủy</a>
                </form>
            </div>
            <div class="col-sm-3"></div>
        </div>
    </div>

</body>

</html>
