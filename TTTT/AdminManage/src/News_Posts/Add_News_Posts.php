<?php
session_start();
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tieuDeBaiViet'])
    && isset($_POST['baiViet'])
    && isset($_FILES['image'])
) {
    $tieuDeBaiViet = $_POST['tieuDeBaiViet'];
    $baiViet = $_POST['baiViet'];

    $checkIssetIMG = false;
    //check file có phải là ảnh không
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        $checkIssetIMG = true;

        $target_dir = "IMG_News_Posts/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);

        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    if ($checkIssetIMG == false) {
        $target_file = "";
    }

    $sql = "INSERT INTO `tintucbaiviet` (`ID_TinTucBaiViet`, `TieuDeBaiViet`, `BaiViet`, `AnhBaiViet`) 
            VALUES (null, '$tieuDeBaiViet', '$baiViet', '$target_file')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $news_id = mysqli_insert_id($conn);
        
        // Send WebSocket notification to Staff and Customers
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_news_update')) {
                websocket_push_news_update($news_id, $tieuDeBaiViet, 'add');
            }
        }
        
        $_SESSION['success_message'] = 'Thêm bài viết thành công!';
        header("Location: Add_News_Posts.php");
        exit();
    } else {
        echo "Có lỗi xảy ra: " . mysqli_error($conn);
    }
}
?>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include("../Layouts/SlideBar.php"); ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include("../Layouts/TopBar.php"); ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Tin Tức & Bài Viết</h1>

                    <!-- DataTales Example -->
                    <div class="container-fluid shadow mb-4">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <?php if (isset($_SESSION['success_message'])) : ?>
                                <div class="alert alert-success m-4"><?php echo $_SESSION['success_message']; ?></div>
                                <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="m-3 form-group">
                                        <label for="tieuDeBaiViet">Tiêu Đề Bài Viết</label>
                                        <input type="text" class="w-100 form-control" name="tieuDeBaiViet"
                                            id="tieuDeBaiViet">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="baiViet">Bài Viết</label>
                                        <textarea name="baiViet" cols="60" rows="5" class="w-100 form-control"
                                            id="baiViet"></textarea>
                                    </div>
                                    <script>
                                    ClassicEditor
                                        .create(document.querySelector('#baiViet'), {
                                            ckfinder: {
                                                uploadUrl: 'File_Upload.php'
                                            }
                                        })
                                        .then(editor => {
                                            console.log(editor);
                                        })
                                        .catch(error => {
                                            console.error(error);
                                        });
                                    </script>
                                </div>
                                <div class="col-md-5">
                                    <div class="card shadow m-4">
                                        <div class="card-header">
                                            <h7>Ảnh Bài Viết</h7>
                                        </div>
                                        <img id="imagePreview" src="" alt="Preview Image" class="w-100">
                                        <div class="card-body">
                                            <div class="form-group">
                                                <script>
                                                function previewImage() {
                                                    var input = document.getElementById('image');
                                                    var imagePreview = document.getElementById('imagePreview');

                                                    if (input.files && input.files[0]) {
                                                        var reader = new FileReader();
                                                        reader.onload = function(e) {
                                                            imagePreview.src = e.target.result;
                                                        };
                                                        reader.readAsDataURL(input.files[0]);
                                                    } else {
                                                        imagePreview.src = "";
                                                    }
                                                }
                                                </script>
                                                <input type="file" name="image" class="form-control-file" id="image"
                                                    onchange="previewImage()" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mb-3">Thêm</button>
                        </form>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->
            <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Page Wrapper -->
        <?php include("../Layouts/Link.php"); ?>
</body>

</html>