<?php include("../Layouts/headHTML.php"); ?>

<?php
include("../DB_Connect.php");

$sql = "SELECT
    binhluan.ID_BinhLuan,
    khachhang.HoVaTen,
    sanpham.TenSanPham,
    binhluan.NoiDungBinhLuan,
    binhluan.DaMuaHang
    FROM
        binhluan
    JOIN
        khachhang ON binhluan.ID_KhachHang = khachhang.ID_KhachHang
    JOIN
        sanpham ON binhluan.ID_SanPham = sanpham.IDSanPham;";

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
                    <h1 class="h3 mb-2 text-gray-800">Danh Sách Bình Luận</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Xóa</th>
                                            <th>Họ Và Tên</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Nội Dung</th>
                                            <th>Mua Hàng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = 1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo '<tr>';
                                            echo ' <td>' . $stt . '</td>';
                                            echo ' <td class="text-center"> <button onclick="DelComment(' . $row['ID_BinhLuan'] . ')" class="btn btn-danger">Xóa</button>' . '</td>';
                                            echo ' <td>' . $row['HoVaTen'] . '</td>';
                                            echo ' <td>' . $row['TenSanPham'] . '</td>';
                                            echo ' <td>' . $row['NoiDungBinhLuan'] . '</td>';
                                            if ($row['DaMuaHang'] == 1) {
                                                echo ' <td>Đã mua hàng</td>';
                                            } else {
                                                echo ' <td>Chưa mua hàng</td>';
                                            }
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

        <!-- ajax -->
        <script>
            function DelComment(IDComment) {
                $.ajax({
                    type: 'POST',
                    url: 'Delete_Comment.php',
                    data: {
                        IDComment: IDComment
                    },
                    success: function(response) {
                        if(response == "xoathanhcong")
                        {
                            alert('Xóa bình luận thành công!');
                            window.location.href = "Manage_Comments.php";
                        }
                        else if(response == "error")
                        {
                            alert('Không thành công');
                        }
                    },
                    error: function() {
                        alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                    }
                });
            }
        </script>
</body>

</html>