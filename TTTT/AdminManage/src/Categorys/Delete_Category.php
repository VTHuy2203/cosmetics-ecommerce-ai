<?php
require_once "../DB_Connect.php";

// Kiểm tra xem ID danh mục đã được truyền vào hay chưa
if (!isset($_GET['category_id'])) {
    header('Location: Manage_Category.php');
    exit();
}

$category_id = $_GET['category_id'];

// Lấy thông tin danh mục từ CSDL để hiển thị trên thông báo xác nhận xóa
$sql = "SELECT * FROM danhmuc WHERE IDDanhMuc = '$category_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Kiểm tra xem danh mục có tồn tại hay không
if (!$row) {
    header('Location: Manage_Category.php');
    exit();
}

// Xử lý logic khi người dùng xác nhận xóa
if (isset($_POST['delete'])) {
    // Thực hiện truy vấn xóa danh mục trong CSDL
    $deleteSql = "DELETE FROM danhmuc WHERE IDDanhMuc = '$category_id'";
    $deleteResult = mysqli_query($conn, $deleteSql);

    if ($deleteResult) {
        // Send WebSocket notification to Staff and Customers
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_category_update')) {
                websocket_push_category_update($category_id, $row['TenDanhMuc'], 'delete');
            }
        }
        
        header('Location: Manage_Category.php');
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
                <h2>Xóa danh mục</h2>
                <div class="alert alert-danger">
                    <p>Bạn có chắc chắn muốn xóa danh mục sau? Bạn sẽ không thể hoàn tác lại danh mục đã xóa!!!!</p>
                    <p><strong>Tên danh mục:</strong> <?php echo $row['TenDanhMuc']; ?></p>
                </div>

                <form method="POST">
                    <button type="submit" name="delete" class="btn btn-danger">Xóa</button>
                    <a href="Manage_Category.php" class="btn btn-secondary">Hủy</a>
                </form>
            </div>
            <div class="col-sm-3"></div>
        </div>
    </div>

</body>

</html>
