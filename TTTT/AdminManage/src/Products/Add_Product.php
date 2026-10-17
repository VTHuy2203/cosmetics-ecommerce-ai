<?php
ob_start();

// Increase upload limits
@ini_set('upload_max_filesize', '20M');
@ini_set('post_max_size', '25M');
@ini_set('max_execution_time', '300');

include("../Layouts/headHTML.php");
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/uploads.php';
require_once dirname(__DIR__, 3) . '/config/catalog.php';

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
    require_csrf();
    $tenSanPham = trim((string) $_POST['tenSanPham']);
    $tenDanhMuc = (int) $_POST['tenDanhMuc'];
    $tenThuongHieu = (int) $_POST['tenThuongHieu'];
    $soLuonng = (int) $_POST['soLuong'];
    $giaTien = (int) $_POST['giaTien'];
    $moTaChiTiet = (string) $_POST['moTaChiTiet'];
    if ($tenSanPham === '' || mb_strlen($tenSanPham) > 5000 || $tenDanhMuc < 1 || $tenThuongHieu < 1
        || $soLuonng < 0 || $soLuonng > 1_000_000 || $giaTien < 0 || $giaTien > 2_000_000_000
        || mb_strlen($moTaChiTiet) > 10_000) {
        http_response_code(422);
        exit('Dữ liệu sản phẩm không hợp lệ.');
    }

    $checkIssetIMG = false;
    $target_file = "";
    
    // Handle main image (first image)
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        try {
            $checkIssetIMG = true;
            $target_file = 'IMG_Products/' . store_public_image_upload($_FILES['image'], __DIR__ . '/IMG_Products');
        } catch (Throwable $error) {
            error_log('Product image upload failed: ' . $error->getMessage());
            http_response_code(422);
            exit('Ảnh sản phẩm không hợp lệ.');
        }
    }

    if ($checkIssetIMG == false) {
        $target_file = "";
    }

    // Product and its durable RAG event commit together.
    $conn->begin_transaction();
    try {
    $insertProduct = $conn->prepare(
        'INSERT INTO sanpham (TenSanPham, IDDanhMuc, IDThuongHieu, SoLuong, GiaTien, NoiBat, MoTaChiTiet, AnhSanPham)
         VALUES (?, ?, ?, ?, ?, 0, ?, ?)'
    );
    $insertProduct->bind_param('siiiiss', $tenSanPham, $tenDanhMuc, $tenThuongHieu, $soLuonng, $giaTien, $moTaChiTiet, $target_file);
    $insertProduct->execute();
    {
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
            $image_order = 1; // Start from 1 (0 is for main image)
            $additionalCount = min(count($_FILES['additional_images']['name']), 8);
            $insertImage = $conn->prepare('INSERT INTO product_images (ID_SanPham, DuongDan, ThuTu) VALUES (?, ?, ?)');
            for ($i = 0; $i < $additionalCount; $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['additional_images']['name'][$i],
                        'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                        'error' => $_FILES['additional_images']['error'][$i],
                        'size' => $_FILES['additional_images']['size'][$i],
                    ];
                    $target_file_path = 'IMG_Products/' . store_public_image_upload($file, __DIR__ . '/IMG_Products');
                    $insertImage->bind_param('isi', $product_id, $target_file_path, $image_order);
                    $insertImage->execute();
                    $image_order++;
                }
            }
        }
        catalog_enqueue_rag_event($conn, $product_id, 'upsert');
        $conn->commit();

        $_SESSION['success_message'] = 'Thêm Sản Phẩm thành công!';
        header("Location: Add_Product.php");
        exit();
    }
    } catch (Throwable $error) {
        try { $conn->rollback(); } catch (Throwable) {}
        error_log('Product creation failed: ' . $error->getMessage());
        http_response_code(500);
        exit('Không thể thêm sản phẩm.');
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
                            <?php echo csrf_field(); ?>
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
                                                    uploadUrl: 'File_Upload.php?csrf_token=<?php echo rawurlencode(csrf_token()); ?>'
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
