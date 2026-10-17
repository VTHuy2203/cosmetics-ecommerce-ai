<?php
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../DB_Connect.php';
require_once '../Layouts/Admin_Access_Check.php';
require_once dirname(__DIR__, 3) . '/config/catalog.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['noibat_product_id'], $_POST['noibat'])) {
    require_csrf();
    $productId = filter_var($_POST['noibat_product_id'], FILTER_VALIDATE_INT);
    $featured = filter_var($_POST['noibat'], FILTER_VALIDATE_INT);
    if ($productId === false || $productId < 1 || !in_array($featured, [0, 1], true)) {
        http_response_code(422);
        exit('invalid_featured_state');
    }
    $featuredStatement = $conn->prepare('UPDATE sanpham SET NoiBat = ? WHERE IDSanPham = ?');
    $featuredStatement->bind_param('ii', $featured, $productId);
    $featuredStatement->execute();
    header('Location: Manage_Product.php', true, 303);
    exit();
}

include("../Layouts/headHTML.php");

$sql = "SELECT sanpham.IDSanPham, sanpham.TenSanPham, sanpham.IDDanhMuc, sanpham.IDThuongHieu, sanpham.SoLuong, sanpham.GiaTien, sanpham.MoTaChiTiet, sanpham.AnhSanPham, sanpham.NoiBat, sanpham.ThoiGianTao, sanpham.ThoiGianCapNhat, danhmuc.TenDanhMuc, thuonghieu.TenThuongHieu from sanpham
INNER JOIN danhmuc on sanpham.IDDanhMuc = danhmuc.IDDanhMuc
INNER JOIN thuonghieu on sanpham.IDThuongHieu = thuonghieu.IDThuongHieu";

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

                    <?php echo catalog_delete_alert(); ?>

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Danh Sách Sản Phẩm</h1>

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
                                            <th>Nổi Bật</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Danh Mục</th>
                                            <th>Thương Hiệu</th>
                                            <th>Số Lượng</th>
                                            <th>Giá Tiền</th>
                                            <th>Hình Ảnh</th>
                                            <th>Mô tả Chi Tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = 1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo '<tr>';
                                            echo ' <td>' . $stt . '</td>';
                                            echo ' <td class="text-center"> <a href="Update_Product.php?product_id=' . $row['IDSanPham'] . '" class="btn btn-primary">Sửa</a>' . '</td>';
                                            echo '<td class="text-center">' . admin_delete_form('Delete_Product.php', 'product_id', (int) $row['IDSanPham']) . '</td>';
                                            if ($row['NoiBat'] == 0) {
                                                echo ' <td class="text-center"><form method="post">' . csrf_field() . '<input type="hidden" name="noibat_product_id" value="' . (int) $row['IDSanPham'] . '"><input type="hidden" name="noibat" value="1"><button type="submit" class="btn btn-info">Hiện</button></form></td>';
                                            } else {
                                                echo ' <td class="text-center"><form method="post">' . csrf_field() . '<input type="hidden" name="noibat_product_id" value="' . (int) $row['IDSanPham'] . '"><input type="hidden" name="noibat" value="0"><button type="submit" class="btn btn-success">Ẩn</button></form></td>';
                                            }
                                            echo ' <td>' . $row['TenSanPham'] . '</td>';
                                            echo ' <td>' . $row['TenDanhMuc'] . '</td>';
                                            echo ' <td>' . $row['TenThuongHieu'] . '</td>';
                                            echo ' <td>' . $row['SoLuong'] . '</td>';
                                            echo ' <td>' . $row['GiaTien'] . '</td>';

                                            echo ' <td class="text-center">';
                                            echo '     <div class="d-flex align-items-center justify-content-center">';
                                            echo '         <img src="' . $row['AnhSanPham'] . '" style="max-height: 100px;" class="img-fluid">';
                                            echo '     </div>';
                                            echo ' </td>';

                                            echo ' <td class="text-center"> <a href="Detail_Product.php?product_id=' . $row['IDSanPham'] . '" class="btn btn-info">Chi Tiết</a>' . '</td>';
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
