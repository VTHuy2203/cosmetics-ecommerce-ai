<?php
include("../Layouts/headHTML.php");

include("../DB_Connect.php");

if (isset($_GET['oder_id']) && isset($_GET['view'])) {

    $oder_id = $_GET['oder_id'];

    $sql = "SELECT * from donhang where ID_DonHang = '$oder_id'";

    $result = mysqli_query($conn, $sql);

    $row = mysqli_fetch_array($result);

    // Kiểm tra nếu có lỗi trong quá trình truy vấn
    if (!$result) {
        die("Có lỗi xảy ra: " . mysqli_error($conn));
    }

    $url = 'All_Oders.php';
    if ($_GET['view'] == 'all') {
        $url = 'All_Oders.php';
    } else if ($_GET['view'] == 'approve') {
        $url = 'Approve_Oders.php';
    }else if ($_GET['view'] == 'prepare') {
        $url = 'Prepare_Oders.php';
    }else if ($_GET['view'] == 'shipping') {
        $url = 'Shipping_Oders.php';
    }else if ($_GET['view'] == 'success') {
        $url = 'Success_Oders.php';
    }
} else {
    header('Location: All_Oders.php');
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
                    <h1 class="h3 mb-2 text-gray-800">Tất Cả Đơn Hàng</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <p class="card-text">Họ Và Tên: <?php echo $row['HoVaTen']; ?></p>
                            <p class="card-text">Email: <?php echo $row['Email']; ?></p>
                            <p class="card-text">Sdt: <?php echo $row['SDT']; ?></p>
                            <p class="card-text">Địa Chỉ: <?php echo $row['DiaChi']; ?></p>
                            <p class="card-text">Trạng Thái:
                                <?php
                                $ghi_chu = $row['GhiChu'] ?? '';
                                $is_returned = (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false);
                                
                                if ($is_returned) {
                                    echo '<span class="badge bg-danger">Đã Hoàn Trả</span>';
                                } else if ($row['TrangThai'] == 0) {
                                    echo 'Cần Duyệt';
                                } else if ($row['TrangThai'] == 1) {
                                    echo 'Chuẩn Bị Hàng';
                                } else if ($row['TrangThai'] == 2) {
                                    echo 'Đang Giao';
                                } else if ($row['TrangThai'] == 3) {
                                    echo 'Giao Thành Công';
                                }
                                ?>
                            </p>
                            <p class="card-text">Ghi Chú: <?php echo $row['GhiChu']; ?></p>
                            <p class="card-text">
                                <strong>Tổng Tiền:</strong> 
                                <span class="text-primary"><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</span>
                                <small class="text-muted">(Đã bao gồm phí vận chuyển nếu có)</small>
                            </p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive rounded-3 border border-grey border-opacity-25">
                                <table class="table">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">STT</th>
                                            <th scope="col">Ảnh</th>
                                            <th scope="col">Tên sản phẩm</th>
                                            <th scope="col">Số lượng</th>
                                            <th scope="col">Giá tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql_select_oder_detail = "select * from donhangchitiet where ID_DonHang = '$oder_id'";

                                        $result_select_oder_detail = mysqli_query($conn, $sql_select_oder_detail);

                                        $stt = 1;
                                        $product_subtotal = 0; // Calculate product subtotal
                                        
                                        while ($oder_detail = mysqli_fetch_array($result_select_oder_detail)) {

                                            $id_product = $oder_detail['ID_SanPham'];

                                            $sql_select_product = "select * from sanpham where IDSanPham = '$id_product'";

                                            $result_select_product = mysqli_query($conn, $sql_select_product);

                                            $product_info = mysqli_fetch_array($result_select_product);
                                            
                                            // Calculate product line total
                                            $product_price = intval($product_info['GiaTien']);
                                            $product_quantity = intval($oder_detail['SoLuong']);
                                            $line_total = $product_price * $product_quantity;
                                            $product_subtotal += $line_total;

                                            echo '<tr>
                                                    <th class="align-middle" scope="row">' . $stt . '</th>
                                                    <td class="align-middle"><img src="../../../AdminManage/src/Products/' . $product_info['AnhSanPham'] . '" class="img-fluid rounded-3" style="max-height: 50px"></td>
                                                    <td class="align-middle">' . $product_info['TenSanPham'] . '</td>
                                                    <td class="align-middle">' . $oder_detail['SoLuong'] . '</td>
                                                    <td class="align-middle">' . number_format($product_info['GiaTien'], 0, ',', '.') . ' đ</td>
                                                </tr>';
                                            $stt++;
                                        }
                                        
                                        // Calculate shipping fee
                                        $free_shipping_threshold = 500000; // 500,000 VND
                                        $shipping_fee = 0;
                                        if ($product_subtotal > 0 && $product_subtotal < $free_shipping_threshold) {
                                            $shipping_fee = 25000; // 25,000 VND shipping fee
                                        }
                                        
                                        // Total amount (should match TongTien in database)
                                        $calculated_total = $product_subtotal + $shipping_fee;
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Tổng tiền sản phẩm:</th>
                                            <th class="text-end"><?php echo number_format($product_subtotal, 0, ',', '.'); ?> đ</th>
                                        </tr>
                                        <?php if ($shipping_fee > 0): ?>
                                        <tr>
                                            <th colspan="4" class="text-end">Phí vận chuyển:</th>
                                            <th class="text-end"><?php echo number_format($shipping_fee, 0, ',', '.'); ?> đ</th>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <th colspan="4" class="text-end text-success">
                                                <i class="fas fa-check-circle me-2"></i>Miễn Phí Vận Chuyển:
                                            </th>
                                            <th class="text-end text-success">0 đ</th>
                                        </tr>
                                        <?php endif; ?>
                                        <tr class="table-active">
                                            <th colspan="4" class="text-end"><strong>Tổng cộng:</strong></th>
                                            <th class="text-end"><strong><?php echo number_format($calculated_total, 0, ',', '.'); ?> đ</strong></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo $url; ?>" class="btn btn-primary">Quay lại</a>
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