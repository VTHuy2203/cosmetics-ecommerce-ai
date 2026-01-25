<?php 
session_start(); 
include("../Layouts/headHTML.php"); 

if (
    isset($_POST['tenDanhMuc']) && isset($_FILES['image'])
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    include("../DB_Connect.php");

    $tenDanhMuc = $_POST['tenDanhMuc'];

    $target_dir = "IMG_Category/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);

    //check file có phải là ảnh không
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    } else {
        echo "File is not an image.";
    }

    $sql = "INSERT INTO `danhmuc` (`IDDanhMuc`, `TenDanhMuc`, `AnhDanhMuc`) VALUES (null, '$tenDanhMuc', '$target_file')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $category_id = mysqli_insert_id($conn);
        
        // Send WebSocket notification to Staff and Customers
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_category_update')) {
                websocket_push_category_update($category_id, $tenDanhMuc, 'add');
            }
        }
        
        $_SESSION['success_message'] = 'Thêm danh mục thành công!';
        header("Location: Add_Category.php");
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
                    <h1 class="h3 mb-2 text-gray-800">Thêm Danh Mục</h1>

                    <!-- DataTales Example -->
                    <div class="container-fluid shadow mb-4">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <?php if (isset($_SESSION['success_message'])) : ?>
                                    <div class="alert alert-success m-4"><?php echo $_SESSION['success_message']; ?></div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="row">
                                <div class="col-xl-8 col-lg-8 col-md-8 col-sm-10">
                                    <div class="m-3 form-group">
                                        <label for="tenDanhMuc">Tên Danh Mục</label>
                                        <input type="text" class="w-100 form-control" name="tenDanhMuc" id="tenDanhMuc">
                                    </div>

                                    <div class="card shadow m-3">
                                        <div class="card-header">
                                            <h7>Hình Ảnh Danh Mục</h7>
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
                                                <input type="file" name="image" class="form-control-file" id="image" onchange="previewImage()" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary m-3">Thêm</button>
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