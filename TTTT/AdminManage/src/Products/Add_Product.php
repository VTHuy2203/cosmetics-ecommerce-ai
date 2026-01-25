<?php
session_start();

// Increase upload limits
@ini_set('upload_max_filesize', '20M');
@ini_set('post_max_size', '25M');
@ini_set('max_execution_time', '300');

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Create product_images table if not exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `product_images` (
    `ID_HinhAnh` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_SanPham` INT NOT NULL,
    `DuongDan` VARCHAR(255) NOT NULL,
    `ThuTu` INT NOT NULL DEFAULT 0,
    `ThoiGianTao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ID_SanPham`) REFERENCES `sanpham`(`IDSanPham`) ON DELETE CASCADE,
    INDEX idx_product (`ID_SanPham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tenSanPham'])
    && isset($_POST['tenDanhMuc'])
    && isset($_POST['tenThuongHieu'])
    && isset($_POST['soLuong'])
    && isset($_POST['giaTien'])
    && isset($_POST['moTaChiTiet'])
    && isset($_FILES['image'])
) {
    $tenSanPham = mysqli_real_escape_string($conn, $_POST['tenSanPham']);
    $tenDanhMuc = mysqli_real_escape_string($conn, $_POST['tenDanhMuc']);
    $tenThuongHieu = mysqli_real_escape_string($conn, $_POST['tenThuongHieu']);
    $soLuonng = mysqli_real_escape_string($conn, $_POST['soLuong']);
    $giaTien = mysqli_real_escape_string($conn, $_POST['giaTien']);
    $moTaChiTiet = mysqli_real_escape_string($conn, $_POST['moTaChiTiet']);

    $checkIssetIMG = false;
    $target_file = "";
    
    // Handle main image (first image)
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $checkIssetIMG = true;
            $target_dir = "IMG_Products/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }
    }

    if ($checkIssetIMG == false) {
        $target_file = "";
    }

    // Insert product
    $sql = "INSERT INTO `sanpham` (`IDSanPham`, `TenSanPham`, `IDDanhMuc`, `IDThuongHieu`, `SoLuong`, `GiaTien`, `NoiBat`, `MoTaChiTiet`, `AnhSanPham`) 
            VALUES (null, '$tenSanPham', '$tenDanhMuc', '$tenThuongHieu', '$soLuonng', '$giaTien', '0', '$moTaChiTiet', '$target_file')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $product_id = mysqli_insert_id($conn);
        
        // Send WebSocket notification to users about new product
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push_new_product')) {
                websocket_push_new_product($product_id, $tenSanPham);
            }
        }
        
        // Handle multiple images
        if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
            $target_dir = "IMG_Products/";
            $image_order = 1; // Start from 1 (0 is for main image)
            
            for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $check = getimagesize($_FILES["additional_images"]["tmp_name"][$i]);
                    if ($check !== false) {
                        $file_extension = pathinfo($_FILES["additional_images"]["name"][$i], PATHINFO_EXTENSION);
                        $new_filename = "product_" . $product_id . "_" . time() . "_" . $i . "." . $file_extension;
                        $target_file_path = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES["additional_images"]["tmp_name"][$i], $target_file_path)) {
                            // Insert image into product_images table
                            $sql_image = "INSERT INTO `product_images` (`ID_SanPham`, `DuongDan`, `ThuTu`) 
                                         VALUES ('$product_id', '$target_file_path', '$image_order')";
                            mysqli_query($conn, $sql_image);
                            $image_order++;
                        }
                    }
                }
            }
        }
        
        $_SESSION['success_message'] = 'Thêm Sản Phẩm thành công!';
        header("Location: Add_Product.php");
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
                    <h1 class="h3 mb-2 text-gray-800">Thêm Sản Phẩm</h1>

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
                                        <label for="tenSanPham">Tên Sản Phẩm</label>
                                        <input type="text" class="w-100 form-control" name="tenSanPham" id="tenSanPham">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="tenDanhMuc" class="form-label">Danh Mục</label>
                                        <select class="form-select form-control w-100" id="tenDanhMuc"
                                            name="tenDanhMuc">
                                            <?php
                                            $sql = "SELECT * FROM danhmuc";
                                            $result = mysqli_query($conn, $sql);

                                            while ($row = mysqli_fetch_array($result)) {
                                                echo '<option value="' . $row['IDDanhMuc'] . '">' . $row['TenDanhMuc'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="tenThuongHieu" class="form-label">Thương Hiệu</label>
                                        <select class="form-select form-control w-100" id="tenThuongHieu"
                                            name="tenThuongHieu">
                                            <?php
                                            $sql = "SELECT * FROM ThuongHieu";
                                            $result = mysqli_query($conn, $sql);

                                            while ($row = mysqli_fetch_array($result)) {
                                                echo '<option value="' . $row['IDThuongHieu'] . '">' . $row['TenThuongHieu'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="soLuong">Số Lượng</label>
                                        <input type="number" class="w-100 form-control" name="soLuong" id="soLuong">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="giaTien">Giá Tiền</label>
                                        <input type="number" class="w-100 form-control" name="giaTien" id="giaTien">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="moTaChiTiet">Mô Tả Chi Tiết</label>
                                        <textarea name="moTaChiTiet" cols="60" rows="5" class="w-100 form-control"
                                            id="moTaChiTiet"></textarea>
                                    </div>
                                    <script>
                                        ClassicEditor
                                            .create(document.querySelector('#moTaChiTiet'), {
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
                                            <h7>Hình Ảnh Chính</h7>
                                        </div>
                                        <img id="imagePreview" src="" alt="Preview Image" class="w-100" style="max-height: 300px; object-fit: contain;">
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="image">Hình ảnh chính (bắt buộc)</label>
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
                                                    onchange="previewImage()" accept="image/*" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card shadow m-4">
                                        <div class="card-header">
                                            <h7>Hình Ảnh Bổ Sung</h7>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="additional_images">Thêm nhiều hình ảnh (tùy chọn)</label>
                                                <input type="file" name="additional_images[]" class="form-control-file" id="additional_images"
                                                    accept="image/*" multiple onchange="previewAdditionalImages()">
                                                <small class="text-muted">Có thể chọn nhiều hình ảnh cùng lúc</small>
                                            </div>
                                            <div id="additionalImagesPreview" class="mt-3"></div>
                                        </div>
                                    </div>
                                    
                                    <script>
                                        function previewAdditionalImages() {
                                            var input = document.getElementById('additional_images');
                                            var previewContainer = document.getElementById('additionalImagesPreview');
                                            previewContainer.innerHTML = '';
                                            
                                            if (input.files && input.files.length > 0) {
                                                for (var i = 0; i < input.files.length; i++) {
                                                    var reader = new FileReader();
                                                    reader.onload = (function(file) {
                                                        return function(e) {
                                                            var div = document.createElement('div');
                                                            div.className = 'mb-2';
                                                            div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: contain;">';
                                                            previewContainer.appendChild(div);
                                                        };
                                                    })(input.files[i]);
                                                    reader.readAsDataURL(input.files[i]);
                                                }
                                            }
                                        }
                                    </script>
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