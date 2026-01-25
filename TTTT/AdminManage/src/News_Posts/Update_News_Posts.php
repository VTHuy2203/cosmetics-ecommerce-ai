<?php
include("../Layouts/headHTML.php");

include("../DB_Connect.php");

// Kiểm tra xem ID bài viết đã được truyền vào hay chưa
if (!isset($_GET['News_Posts_id'])) {
    header('Location: Manage_News_Posts.php');
    exit();
}

$News_Posts_id = $_GET['News_Posts_id'];

// Lấy thông tin bài viết từ CSDL để hiển thị lên form
$sql = "SELECT * FROM tintucbaiviet WHERE ID_TinTucBaiViet = '$News_Posts_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Kiểm tra xem bài viết có tồn tại hay không
if (!$row) {
    header('Location: Manage_News_Posts.php');
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
                    <h1 class="h3 mb-2 text-gray-800">Sửa Bài Viết</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <form method="post" action="Processing_Update_News_Posts.php" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="m-3 form-group">
                                        <label for="tieuDeBaiViet">Tiêu Đề Bài Viết</label>
                                        <input type="text" class="w-100 form-control" name="tieuDeBaiViet" id="tieuDeBaiViet" value="<?php echo $row['TieuDeBaiViet'] ?>">
                                    </div>
                                    <div class="m-3 form-group">
                                        <label for="baiViet">Mô Tả Chi Tiết</label>
                                        <textarea name="baiViet" cols="60" class="w-100 form-control" id="baiViet"><?php echo $row['BaiViet'] ?></textarea>
                                    </div>
                                    <div class="">
                                        <input type="hidden" name="News_Posts_id" value="<?php echo $News_Posts_id; ?>">
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
                                <div class="col-md-4">
                                    <div class="card shadow m-3">
                                        <div class="card-header">
                                            <h7>Ảnh Bài Viết</h7>
                                        </div>
                                        <div class="card-body">
                                            <img id="imagePreview" src="<?php echo $row['AnhBaiViet']; ?>" alt="Preview Image" class="w-50">
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
                                </div>
                            </div>
                            <div class="row m-3">
                                <a class="btn btn-danger" href="Manage_News_Posts.php">Hủy</a>
                                <button type="submit" class="btn btn-success  ml-3">Lưu</button>
                            </div>
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
        </div>
        <!-- End of Page Wrapper -->
        <?php include("../Layouts/Link.php"); ?>
</body>

</html>