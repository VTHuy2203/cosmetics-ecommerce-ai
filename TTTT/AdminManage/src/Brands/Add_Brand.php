<?php
include("../Layouts/headHTML.php");
require_once dirname(__DIR__, 3) . '/config/uploads.php';

if (
    isset($_POST['tenThuongHieu'])  && isset($_FILES['image'])
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    require_csrf();
    include("../DB_Connect.php");

    $tenThuongHieu = trim((string) $_POST['tenThuongHieu']);
    if ($tenThuongHieu === '' || mb_strlen($tenThuongHieu) > 255) {
        http_response_code(422); exit('Tên thương hiệu không hợp lệ.');
    }

    $checkIssetIMG = false;
    //check file có phải là ảnh không
    try {
        $checkIssetIMG = true;
        $target_file = 'IMG_Brand/' . store_public_image_upload($_FILES['image'], __DIR__ . '/IMG_Brand');
    } catch (Throwable $error) {
        $checkIssetIMG = false;
    }

    $sql='';
    if($checkIssetIMG==true)
    {
        $statement = $conn->prepare('INSERT INTO thuonghieu (TenThuongHieu, AnhThuongHieu) VALUES (?, ?)');
        $statement->bind_param('ss', $tenThuongHieu, $target_file);
    }
    else
    {
        $statement = $conn->prepare('INSERT INTO thuonghieu (TenThuongHieu) VALUES (?)');
        $statement->bind_param('s', $tenThuongHieu);
    }

    $result = $statement->execute();

    if ($result) {
        $brand_id = mysqli_insert_id($conn);
        
        // Send WebSocket notification to Staff and Customers
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_brand_update')) {
                websocket_push_brand_update($brand_id, $tenThuongHieu, 'add');
            }
        }
        
        $_SESSION['success_message'] = 'Thêm Thương Hiệu thành công!';
        header("Location: Add_Brand.php");
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
                    <h1 class="h3 mb-2 text-gray-800">Thêm Thương Hiệu</h1>

                    <!-- DataTales Example -->
                    <div class="container-fluid shadow mb-4">
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="row">
                                <?php if (isset($_SESSION['success_message'])) : ?>
                                    <div class="alert alert-success m-4"><?php echo $_SESSION['success_message']; ?></div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="row">
                                <div class="col-xl-8 col-lg-8 col-md-8 col-sm-10">
                                    <div class="m-3 form-group">
                                        <label for="tenThuongHieu">Tên Thương Hiệu</label>
                                        <input type="text" class="w-100 form-control" name="tenThuongHieu" id="tenThuongHieu">
                                    </div>

                                    <div class="card shadow m-3">
                                        <div class="card-header">
                                            <h7>Hình Ảnh Thương Hiệu</h7>
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
