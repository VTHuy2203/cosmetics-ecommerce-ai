<?php
include("../Layouts/headHTML.php");

require_once "../DB_Connect.php";

// Kiểm tra xem ID user đã được truyền vào hay chưa
if (!isset($_GET['User_id'])) {
    header('Location: Manage_Users.php');
    exit();
}

$User_id = $_GET['User_id'];

// Lấy thông tin user từ CSDL để hiển thị lên form
$sql = "SELECT * FROM khachhang WHERE ID_KhachHang = '$User_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Kiểm tra xem user có tồn tại hay không
if (!$row) {
    header('Location: Manage_Users.php');
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
                    <h1 class="h3 mb-2 text-gray-800">Sửa Khách Hàng</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <form method="post" action="Processing_Update_User.php" enctype="multipart/form-data">
                            <div class="m-3 form-group">
                                <label for="hoVaTen">Họ Và tên</label>
                                <input type="text" class="w-100 form-control" name="hoVaTen" id="hoVaTen" value="<?php echo $row['HoVaTen']; ?>">
                            </div>
                            <div class="m-3 form-group">
                                <label for="email">Email</label>
                                <input type="text" class="w-100 form-control" name="email" id="email" value="<?php echo $row['Email']; ?>">
                            </div>
                            <div class="m-3 form-group">
                                <label for="SDT">Số Điện Thoại</label>
                                <input type="text" class="w-100 form-control" name="SDT" id="SDT" value="<?php echo $row['SDT']; ?>">
                            </div>
                            <div class="m-3 form-group">
                                <label for="diaChi">Địa Chỉ</label>
                                <input type="text" class="w-100 form-control" name="diaChi" id="diaChi" value="<?php echo $row['DiaChi']; ?>">
                            </div>
                            <div class="">
                                <input type="hidden" name="User_id" value="<?php echo $User_id; ?>">
                            </div>
                            <div class="row m-3">
                                <a class="btn btn-danger" href="Manage_Users.php">Hủy</a>
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