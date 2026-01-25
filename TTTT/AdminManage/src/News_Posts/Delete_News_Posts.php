<?php
require_once "../DB_Connect.php";

// Kiểm tra xem ID bai viet đã được truyền vào hay chưa
if (!isset($_GET['News_Posts_id'])) {
    header('Location: Manage_News_Posts.php');
    exit();
}

$News_Posts_id = $_GET['News_Posts_id'];

// Lấy thông tin bai viet từ CSDL để hiển thị trên thông báo xác nhận xóa
$sql = "SELECT * FROM tintucbaiviet WHERE ID_TinTucBaiViet = '$News_Posts_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Kiểm tra xem bai viet có tồn tại hay không
if (!$row) {
    header('Location: Manage_News_Posts.php');
    exit();
}

// Xử lý logic khi người dùng xác nhận xóa
if (isset($_POST['delete'])) {
    // Thực hiện truy vấn xóa bai viet trong CSDL
    $deleteSql = "DELETE FROM tintucbaiviet WHERE ID_TinTucBaiViet = '$News_Posts_id'";
    $deleteResult = mysqli_query($conn, $deleteSql);

    if ($deleteResult) {
        // Send WebSocket notification to Staff and Customers
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_news_update')) {
                websocket_push_news_update($News_Posts_id, $row['TieuDeBaiViet'], 'delete');
            }
        }
        
        header('Location: Manage_News_Posts.php');
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
                <h2>Xóa Bài Viết</h2>
                <div class="alert alert-danger">
                    <p>Bạn có chắc chắn muốn xóa bài viết sau? Bạn sẽ không thể hoàn tác lại bài viết đã xóa!!!!</p>
                    <p><strong>Tên bài viết:</strong> <?php echo $row['TieuDeBaiViet']; ?></p>
                </div>

                <form method="POST">
                    <button type="submit" name="delete" class="btn btn-danger">Xóa</button>
                    <a href="Manage_News_Posts.php" class="btn btn-secondary">Hủy</a>
                </form>
            </div>
            <div class="col-sm-3"></div>
        </div>
    </div>

</body>

</html>
