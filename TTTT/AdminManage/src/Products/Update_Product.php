<?php
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Kiểm tra xem ID sản phẩm đã được truyền vào hay chưa
if (!isset($_GET['product_id'])) {
    header('Location: Manage_Product.php');
    exit();
}

$product_id = $_GET['product_id'];

// Lấy thông tin sản phẩm từ CSDL để hiển thị lên form (Sử dụng prepared statement để bảo mật)
$stmt = $conn->prepare("SELECT * FROM sanpham WHERE IDSanPham = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Kiểm tra xem sản phẩm có tồn tại hay không
if (!$row) {
    header('Location: Manage_Product.php');
    exit();
}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Sửa Sản Phẩm</h1>
                    <div class="card shadow mb-4">
                        <form method="post" action="Processing_Update_product.php" enctype="multipart/form-data">
                            <!-- Truyền product_id ẩn để xử lý trong file Processing_Update_product.php -->
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="m-3 form-group">
                                        <label for="tenSanPham">Tên Sản Phẩm</label>
                                        <input type="text" class="w-100 form-control" name="tenSanPham" id="tenSanPham"
                                            value="<?php echo htmlspecialchars($row['TenSanPham']); ?>">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="tenDanhMuc" class="form-label">Danh Mục</label>
                                        <select class="form-select form-control w-100" id="tenDanhMuc"
                                            name="tenDanhMuc">
                                            <?php
                                            $sql_danhmuc = "SELECT * FROM danhmuc";
                                            $result_danhmuc = mysqli_query($conn, $sql_danhmuc);

                                            while ($row_danhmuc = mysqli_fetch_array($result_danhmuc)) {
                                                if ($row['IDDanhMuc'] == $row_danhmuc['IDDanhMuc']) {
                                                    echo '<option value="' . $row_danhmuc['IDDanhMuc'] . '" selected >' . htmlspecialchars($row_danhmuc['TenDanhMuc']) . '</option>';
                                                } else {
                                                    echo '<option value="' . $row_danhmuc['IDDanhMuc'] . '">' . htmlspecialchars($row_danhmuc['TenDanhMuc']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="tenThuongHieu" class="form-label">Thương Hiệu</label>
                                        <select class="form-select form-control w-100" id="tenThuongHieu"
                                            name="tenThuongHieu">
                                            <?php
                                            $sql_thuonghieu = "SELECT * FROM thuonghieu";
                                            $result_thuonghieu = mysqli_query($conn, $sql_thuonghieu);

                                            while ($row_thuonghieu = mysqli_fetch_array($result_thuonghieu)) {
                                                if ($row['IDThuongHieu'] == $row_thuonghieu['IDThuongHieu']) {
                                                    echo '<option value="' . $row_thuonghieu['IDThuongHieu'] . '" selected >' . htmlspecialchars($row_thuonghieu['TenThuongHieu']) . '</option>';
                                                } else {
                                                    echo '<option value="' . $row_thuonghieu['IDThuongHieu'] . '">' . htmlspecialchars($row_thuonghieu['TenThuongHieu']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="soLuong">Số Lượng Hiện Tại</label>
                                        <input type="number" class="w-100 form-control" name="soLuongHienTai" id="soLuongHienTai"
                                            value="<?php echo htmlspecialchars($row['SoLuong']); ?>" readonly>
                                        <small class="text-muted">Số lượng hiện tại trong kho</small>
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="soLuongThem">Thêm Số Lượng (Để trống nếu không thêm)</label>
                                        <input type="number" class="w-100 form-control" name="soLuongThem" id="soLuongThem"
                                            value="0" min="0">
                                        <small class="text-muted">Nhập số lượng muốn thêm vào kho</small>
                                    </div>
                                    <input type="hidden" name="soLuong" id="soLuong" value="<?php echo htmlspecialchars($row['SoLuong']); ?>">
                                    <div class="m-3 form-group">
                                        <label for="giaTien">Giá Tiền</label>
                                        <input type="number" class="w-100 form-control" name="giaTien" id="giaTien"
                                            value="<?php echo htmlspecialchars($row['GiaTien']); ?>">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="moTaChiTiet">Mô Tả Chi Tiết</label>
                                        <textarea name="moTaChiTiet" cols="60" class="w-100 form-control"
                                            id="moTaChiTiet"><?php echo htmlspecialchars($row['MoTaChiTiet']); ?></textarea>
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
                                <div class="col-md-4">
                                    <div class="card shadow m-3">
                                        <div class="card-header">
                                            <h7>Hình Ảnh Sản Phẩm</h7>
                                        </div>
                                        <div class="card-body">
                                            <img id="imagePreview"
                                                src="<?php echo htmlspecialchars($row['AnhSanPham']); ?>"
                                                alt="Preview Image" class="w-50">

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
                                                            imagePreview.src =
                                                                "<?php echo htmlspecialchars($row['AnhSanPham']); ?>";
                                                        }
                                                    }
                                                </script>
                                                <input type="file" name="image" class="form-control-file" id="image"
                                                    onchange="previewImage()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row m-3">
                                <a class="btn btn-danger" href="Manage_Product.php">Hủy</a>
                                <button type="submit" class="btn btn-success ml-3">Lưu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include("../Layouts/Link.php"); ?>
</body>

</html>