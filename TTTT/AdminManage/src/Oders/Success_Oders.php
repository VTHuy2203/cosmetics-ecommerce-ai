<?php
include("../Layouts/headHTML.php");

include("../DB_Connect.php");

$sql = "SELECT * from donhang where TrangThai = '3'";

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
                    <h1 class="h3 mb-2 text-gray-800">Tất Cả Đơn Hàng</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Trạng Thái</th>
                                            <th>Xem Chi Tiết</th>
                                            <th>Hủy Đơn</th>
                                            <th>Tên Khách Hàng</th>
                                            <th>SDT</th>
                                            <th>Ghi Chú</th>
                                            <th>Tổng Tiền</th>
                                            <th>Địa Chỉ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = 1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo '<tr>';
                                            echo ' <td>' . $stt . '</td>';
                                            echo ' <td class="text-center"> <a class="btn btn-success">Thành Công</a>' . '</td>';
                                            echo ' <td class="text-center"> <a href="View_Oder_Detail.php?oder_id='.$row['ID_DonHang'].'&view=success" class="btn btn-primary">Xem Chi Tiết</a>' . '</td>';
                                            echo ' <td class="text-center"> <a href="Delete_Oder.php?del_oder_id='.$row['ID_DonHang'].'&view=success" class="btn btn-danger">Hủy</a>' . '</td>';
                                            echo ' <td>' . $row['HoVaTen'] . '</td>';
                                            echo ' <td>' . $row['SDT'] . '</td>';
                                            echo ' <td>' . $row['GhiChu'] . '</td>';
                                            echo ' <td>' . $row['TongTien'] . '</td>';
                                            echo ' <td>' . $row['DiaChi'] . '</td>';
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