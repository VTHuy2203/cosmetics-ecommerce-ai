<!-- <?php
        include("../Layouts/headHTML.php");

        include("../DB_Connect.php");

        if (isset($_GET['News_Posts_id'])) {

            $News_Posts_id = $_GET['News_Posts_id'];

            $sql = "SELECT * from tintucbaiviet where ID_TinTucBaiViet = '$News_Posts_id'";

            $result = mysqli_query($conn, $sql);

            $row = mysqli_fetch_array($result);

            // Kiểm tra nếu có lỗi trong quá trình truy vấn
            if (!$result) {
                die("Có lỗi xảy ra: " . mysqli_error($conn));
            }
        } else {
            header('Location: Manage_News_Posts.php');
            exit();
        }
        ?> -->

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
                    <h1 class="h3 mb-2 text-gray-800">Bài Viết</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <p class="card-text"><?php echo $row['BaiViet']; ?></p>
                        </div>
                        <div class="card-body">
                            <a href="Manage_News_Posts.php" class="btn btn-primary">Quay lại</a>
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