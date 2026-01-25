<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

//tính tổng số lượng bài viết
$result_tong_so_luong_baiviet = mysqli_query($conn, 'select count(ID_TinTucBaiViet) as tongsoluongbaiviet from tintucbaiviet');
$row_tong_so_luong_baiviet = mysqli_fetch_assoc($result_tong_so_luong_baiviet);
$tongSoLuongBaiViet = $row_tong_so_luong_baiviet['tongsoluongbaiviet'];

//lấy số trang hiện tại và set số sp trên mỗi trang
$trangHienTai = isset($_GET['page']) ? $_GET['page'] : 1;
$soBaiVietTrenMoiTrang = 4;

//tổng số trang
$tongSoTrang = ceil($tongSoLuongBaiViet / $soBaiVietTrenMoiTrang);

//giới hạn số trang
if ($trangHienTai > $tongSoTrang) {
    $trangHienTai = $tongSoTrang;
} else if ($trangHienTai < 1) {
    $trangHienTai = 1;
}

//số bắt đầu
$start = ($trangHienTai - 1) * $soBaiVietTrenMoiTrang;

if ($start < 0) {
    $start = 0;
}

$sql_select_baiviet_limit = "SELECT * FROM tintucbaiviet LIMIT $start, $soBaiVietTrenMoiTrang";
$result_select_baiviet_limit = mysqli_query($conn, $sql_select_baiviet_limit);
?>

<body class="bg-light">
    <!-- link scrip -->
    <?php include("../Layouts/Link.php"); ?>

    <!-- Banner header -->
    <?php include("../Layouts/Banner_Header.php"); ?>
    <!-- end Banner header -->

    <!-- header -->
    <?php include("../Layouts/Header_View.php"); ?>
    <!-- end header -->

    <!-- Navbar -->
    <?php include("../Layouts/Navbar.php"); ?>
    <!-- End Navbar -->

    <div class="container mb-4 bg-white rounded-4 shadow-sm" style="min-height: 300px;">
        <h4 class="pt-4 px-3">Tin Tức & Bài Viết</h4>
        <div class="row">
            <?php
            while ($row_select_baiviet_limit = mysqli_fetch_assoc($result_select_baiviet_limit)) {
                echo '<div class="p-3 col-md-6">
                        <div class="bg-white p-3 border-0 shadow-sm rounded-4">
                            <div class="card-body">
                                <img src="../../../AdminManage/src/News_Posts/' . $row_select_baiviet_limit['AnhBaiViet'] . '" alt="" class="img-fluid rounded-4 w-100">
                            </div>
                            <div class="card-body text-center mt-3">
                                <a style="text-decoration: none;" href="Detail_News_Posts.php?News_Posts_id=' . $row_select_baiviet_limit['ID_TinTucBaiViet'] . '"><h5 class="card-title text-black">' . $row_select_baiviet_limit['TieuDeBaiViet'] . '</h5></a>
                            </div>
                        </div>
                    </div>';
            }
            ?>
        </div>

        <!-- phân trang -->
        <div class="row mt-3">
            <nav aria-label="Page navigation example" class="d-flex justify-content-center align-items-center">
                <ul class="pagination">
                    <?php
                    // nếu current_page > 1 và total_page > 1 mới hiển thị nút prev
                    if ($trangHienTai > 1 && $tongSoTrang > 1) {
                        echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="View_News_Posts.php?page=' . ($trangHienTai - 1) . '">Trang Trước</a></li>';
                    }

                    // Lặp khoảng giữa
                    for ($i = 1; $i <= $tongSoTrang; $i++) {
                        if ($i == $trangHienTai) {
                            echo '<li class="page-item"><a class="page-link" style="color: white; background-color: #2D9596;" href="">' . $i . '</a></li>';
                        } else {
                            echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="View_News_Posts.php?page=' . $i . '">' . $i . '</a></li>';
                        }
                    }

                    // nếu current_page < $total_page và total_page > 1 mới hiển thị nút prev
                    if ($trangHienTai < $tongSoTrang && $tongSoTrang > 1) {
                        echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="View_News_Posts.php?page=' . ($trangHienTai + 1) . '">Trang Tiếp</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </div>

    <!-- footer -->
    <?php include("../Layouts/Footer.php"); ?>
    <!-- end footer -->

</body>

</html>