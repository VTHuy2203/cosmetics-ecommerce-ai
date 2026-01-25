<?php
include("../Layouts/headHTML.php");

require_once "../DB_Connect.php";

// Kiểm tra xem ID danh mục đã được truyền vào hay chưa
if (!isset($_GET['category_id'])) {
    header('Location: Manage_Category.php');
    exit();
}

$category_id = $_GET['category_id'];

// Lấy thông tin danh mục từ CSDL để hiển thị lên form
$sql = "SELECT * FROM danhmuc WHERE IDDanhMuc = '$category_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Kiểm tra xem danh mục có tồn tại hay không
if (!$row) {
    header('Location: Manage_Category.php');
    exit();
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
                    <h1 class="h3 mb-2 text-gray-800">Sửa Danh Mục</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <form method="post" action="Processing_Update_Category.php" enctype="multipart/form-data">
                            <div class="m-3 form-group">
                                <label for="tenDanhMuc">Tên Danh Mục</label>
                                <input type="text" class="w-100 form-control" name="tenDanhMuc" id="tenDanhMuc" value="<?php echo $row['TenDanhMuc']; ?>">
                            </div>
                            <div class="">
                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                            </div>
                            <div class="card shadow m-3">
                                <div class="card-header">
                                    <h7>Hình Ảnh Danh Mục</h7>
                                </div>
                                <div class="card-body">
                                    <img id="imagePreview" src="<?php echo $row['AnhDanhMuc']; ?>" alt="Preview Image" class="fluid">
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
                                        <input type="file" name="image" class="form-control-file" id="image" onchange="previewImage()">
                                    </div>
                                </div>
                            </div>
                            <div class="row m-3">
                                <a class="btn btn-danger" href="Manage_Category.php">Hủy</a>
                                <button type="submit" class="btn btn-success  ml-3">Lưu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- End of Main Content -->
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    </div>
    <!-- End of Page Wrapper -->
    <?php include("../Layouts/Link.php"); ?>
</body>

</html>