<?php include("../Layouts/headHTML.php"); ?>

<?php
    include("../DB_Connect.php");

    $sql = "SELECT * FROM danhmuc";

    $result = mysqli_query($conn, $sql);

    // Kiểm tra nếu có lỗi trong quá trình truy vấn
    if (!$result) {
        die("Có lỗi xảy ra: " . mysqli_error($conn));
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
                    <h1 class="h3 mb-2 text-gray-800">Danh Sách Danh Mục</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Sửa</th>
                                            <th>Xóa</th>
                                            <th>Tên Danh Mục</th>
                                            <th>Hình Ảnh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = 1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo '<tr>';
                                            echo ' <td>' . $stt . '</td>';
                                            echo ' <td class="text-center"> <a href="Update_Category.php?category_id='.$row['IDDanhMuc'].'" class="btn btn-primary">Sửa</a>' . '</td>';
                                            echo ' <td class="text-center"> <a href="Delete_Category.php?category_id='.$row['IDDanhMuc'].'" class="btn btn-danger">Xóa</a>' . '</td>';
                                            echo ' <td>' . $row['TenDanhMuc'] . '</td>';
                                            echo ' <td class="text-center">';
                                            echo '     <div class="d-flex align-items-center justify-content-center">';
                                            echo '         <img src="' . $row['AnhDanhMuc'] . '" style="max-height: 100px;" class="img-fluid">';
                                            echo '     </div>';
                                            echo ' </td>';
                                            echo '</tr>';
                                            $stt++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
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