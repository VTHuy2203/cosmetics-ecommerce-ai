<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

//  Kiểm tra xem category_id có được truyền từ URL hay không
if (isset($_GET['category_id'])) {
    //tính tổng số lượng sản phẩm
    $category_id = $_GET['category_id'];
    $sql_tong_so_luong_sp = "select count(IDSanPham) as tongsoluongsanpham from sanpham where IDDanhMuc = '$category_id'";
    $result_tong_so_luong_sp = mysqli_query($conn, $sql_tong_so_luong_sp);
    $row_tong_so_luong_sp = mysqli_fetch_assoc($result_tong_so_luong_sp);
    $tongSoLuongSanPham = $row_tong_so_luong_sp['tongsoluongsanpham'];

    //lấy số trang hiện tại và set số sp trên mỗi trang
    $trangHienTai = isset($_GET['page']) ? $_GET['page'] : 1;
    $soSanPhamTrenMoiTrang = 8;

    //tổng số trang
    $tongSoTrang = ceil($tongSoLuongSanPham / $soSanPhamTrenMoiTrang);

    //giới hạn số trang
    if ($trangHienTai > $tongSoTrang) {
        $trangHienTai = $tongSoTrang;
    } else if ($trangHienTai < 1) {
        $trangHienTai = 1;
    }

    //
    $start = ($trangHienTai - 1) * $soSanPhamTrenMoiTrang;

    if($start <0)
    {
        $start = 0;
    }

    $sql_select_product_limit = "SELECT * FROM sanpham WHERE IDDanhMuc ='$category_id' LIMIT $start, $soSanPhamTrenMoiTrang";
    $result_select_product_limit = mysqli_query($conn, $sql_select_product_limit);

}
else if (isset($_GET['brand_id'])) {
    //tính tổng số lượng sản phẩm
    $brand_id = $_GET['brand_id'];
    $sql_tong_so_luong_sp = "select count(IDSanPham) as tongsoluongsanpham from sanpham where IDThuongHieu = '$brand_id'";
    $result_tong_so_luong_sp = mysqli_query($conn, $sql_tong_so_luong_sp);
    $row_tong_so_luong_sp = mysqli_fetch_assoc($result_tong_so_luong_sp);
    $tongSoLuongSanPham = $row_tong_so_luong_sp['tongsoluongsanpham'];

    //lấy số trang hiện tại và set số sp trên mỗi trang
    $trangHienTai = isset($_GET['page']) ? $_GET['page'] : 1;
    $soSanPhamTrenMoiTrang = 8;

    //tổng số trang
    $tongSoTrang = ceil($tongSoLuongSanPham / $soSanPhamTrenMoiTrang);

    //giới hạn số trang
    if ($trangHienTai > $tongSoTrang) {
        $trangHienTai = $tongSoTrang;
    } else if ($trangHienTai < 1) {
        $trangHienTai = 1;
    }

    //
    $start = ($trangHienTai - 1) * $soSanPhamTrenMoiTrang;

    if($start <0)
    {
        $start = 0;
    }

    $sql_select_product_limit = "SELECT * FROM sanpham WHERE IDThuongHieu ='$brand_id' LIMIT $start, $soSanPhamTrenMoiTrang";
    $result_select_product_limit = mysqli_query($conn, $sql_select_product_limit);

}
else if (isset($_GET['search_like'])) {
    //tính tổng số lượng sản phẩm
    $search_like = $_GET['search_like'];
    $sql_tong_so_luong_sp = "select count(IDSanPham) as tongsoluongsanpham from sanpham where TenSanPham LIKE '%$search_like%'";
    $result_tong_so_luong_sp = mysqli_query($conn, $sql_tong_so_luong_sp);
    $row_tong_so_luong_sp = mysqli_fetch_assoc($result_tong_so_luong_sp);
    $tongSoLuongSanPham = $row_tong_so_luong_sp['tongsoluongsanpham'];

    //lấy số trang hiện tại và set số sp trên mỗi trang
    $trangHienTai = isset($_GET['page']) ? $_GET['page'] : 1;
    $soSanPhamTrenMoiTrang = 8;

    //tổng số trang
    $tongSoTrang = ceil($tongSoLuongSanPham / $soSanPhamTrenMoiTrang);

    //giới hạn số trang
    if ($trangHienTai > $tongSoTrang) {
        $trangHienTai = $tongSoTrang;
    } else if ($trangHienTai < 1) {
        $trangHienTai = 1;
    }

    //
    $start = ($trangHienTai - 1) * $soSanPhamTrenMoiTrang;

    if($start <0)
    {
        $start = 0;
    }

    $sql_select_product_limit = "SELECT * FROM sanpham where TenSanPham LIKE '%$search_like%' LIMIT $start, $soSanPhamTrenMoiTrang";
    $result_select_product_limit = mysqli_query($conn, $sql_select_product_limit);

}
else if (isset($_GET['hot_product'])) {
    //tính tổng số lượng sản phẩm
    $sql_tong_so_luong_sp = "select count(IDSanPham) as tongsoluongsanpham from sanpham where NoiBat = '1'";
    $result_tong_so_luong_sp = mysqli_query($conn, $sql_tong_so_luong_sp);
    $row_tong_so_luong_sp = mysqli_fetch_assoc($result_tong_so_luong_sp);
    $tongSoLuongSanPham = $row_tong_so_luong_sp['tongsoluongsanpham'];

    //lấy số trang hiện tại và set số sp trên mỗi trang
    $trangHienTai = isset($_GET['page']) ? $_GET['page'] : 1;
    $soSanPhamTrenMoiTrang = 8;

    //tổng số trang
    $tongSoTrang = ceil($tongSoLuongSanPham / $soSanPhamTrenMoiTrang);

    //giới hạn số trang
    if ($trangHienTai > $tongSoTrang) {
        $trangHienTai = $tongSoTrang;
    } else if ($trangHienTai < 1) {
        $trangHienTai = 1;
    }

    //
    $start = ($trangHienTai - 1) * $soSanPhamTrenMoiTrang;

    if($start <0)
    {
        $start = 0;
    }

    $sql_select_product_limit = "SELECT * FROM sanpham WHERE NoiBat ='1' LIMIT $start, $soSanPhamTrenMoiTrang";
    $result_select_product_limit = mysqli_query($conn, $sql_select_product_limit);

}
else {
    //tính tổng số lượng sản phẩm
    $result_tong_so_luong_sp = mysqli_query($conn, 'select count(IDSanPham) as tongsoluongsanpham from sanpham');
    $row_tong_so_luong_sp = mysqli_fetch_assoc($result_tong_so_luong_sp);
    $tongSoLuongSanPham = $row_tong_so_luong_sp['tongsoluongsanpham'];

    //lấy số trang hiện tại và set số sp trên mỗi trang
    $trangHienTai = isset($_GET['page']) ? $_GET['page'] : 1;
    $soSanPhamTrenMoiTrang = 8;

    //tổng số trang
    $tongSoTrang = ceil($tongSoLuongSanPham / $soSanPhamTrenMoiTrang);

    //giới hạn số trang
    if ($trangHienTai > $tongSoTrang) {
        $trangHienTai = $tongSoTrang;
    } else if ($trangHienTai < 1) {
        $trangHienTai = 1;
    }

    //
    $start = ($trangHienTai - 1) * $soSanPhamTrenMoiTrang;

    $sql_select_product_limit = "SELECT * FROM sanpham LIMIT $start, $soSanPhamTrenMoiTrang";
    $result_select_product_limit = mysqli_query($conn, $sql_select_product_limit);
}
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

    <?php
    // VIP Purchase Progress Tracker for logged-in users
    if (isset($_SESSION['user']) && isset($_SESSION['IDNguoiDung'])) {
        try {
            $dsn = "mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4";
            $pdoVip = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Get customer info and weekly purchase total
            $stmt = $pdoVip->prepare("SELECT customer_label, total_weekly_purchase, discount_percent FROM khachhang WHERE ID_KhachHang = ?");
            $stmt->execute([$_SESSION['IDNguoiDung']]);
            $custData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get total successful payments this week
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            $stmtPaid = $pdoVip->prepare("SELECT COALESCE(SUM(TongTien), 0) as total_paid
                                           FROM donhang
                                           WHERE ID_KhachHang = ?
                                           AND TrangThaiThanhToan = 'paid'
                                           AND DATE(NgayDat) BETWEEN ? AND ?");
            $stmtPaid->execute([$_SESSION['IDNguoiDung'], $weekStart, $weekEnd]);
            $paidData = $stmtPaid->fetch(PDO::FETCH_ASSOC);
            $totalPaid = (int)($paidData['total_paid'] ?? 0);

            $customerLabel = $custData['customer_label'] ?? 'spam';
            $discountPercent = (int)($custData['discount_percent'] ?? 0);
            $targetAmount = 5000000;
            $progressPercent = min(100, ($totalPaid / $targetAmount) * 100);
            $amountNeeded = max(0, $targetAmount - $totalPaid);
            $isPotential = ($customerLabel === 'potential');

            echo '<div class="container mb-3">';
            echo '<div class="card" style="background: linear-gradient(135deg, ' . ($isPotential ? '#fff9e6' : '#f0f4ff') . ' 0%, ' . ($isPotential ? '#fff3cd' : '#e8ecf1') . ' 100%); border: 2px solid ' . ($isPotential ? '#ffd700' : '#667eea') . ';">';
            echo '<div class="card-body p-3">';

            if ($isPotential) {
                echo '<div class="d-flex align-items-center justify-content-between flex-wrap">';
                echo '<div class="d-flex align-items-center mb-2">';
                echo '<span style="font-size:28px;">⭐</span>';
                echo '<div class="ms-2">';
                echo '<strong style="color:#e85d04;font-size:16px;">Bạn là Khách hàng Tiềm năng!</strong>';
                echo '<p class="mb-0 small text-muted">Giảm <strong>' . $discountPercent . '%</strong> cho tất cả đơn hàng</p>';
                echo '</div></div>';
                echo '<div class="text-end">';
                echo '<span class="badge bg-warning text-dark" style="font-size:14px;padding:8px 16px;">🎉 VIP</span>';
                echo '</div></div>';
            } else {
                echo '<div class="row align-items-center">';
                echo '<div class="col-md-8">';
                echo '<div class="d-flex align-items-center mb-2">';
                echo '<span style="font-size:24px;">🎯</span>';
                echo '<strong class="ms-2" style="color:#667eea;font-size:16px;">Tiến trình VIP trong tuần</strong>';
                echo '</div>';
                echo '<p class="mb-2 small">Mua thêm <strong style="color:#e85d04;">' . number_format($amountNeeded, 0, ',', '.') . ' VND</strong> để trở thành Khách hàng Tiềm năng và được <strong>giảm 20%</strong>!</p>';
                echo '<div class="progress mb-1" style="height: 16px;border-radius:8px;">';
                echo '<div class="progress-bar" role="progressbar" style="width: ' . $progressPercent . '%; background: linear-gradient(90deg, #667eea, #764ba2);font-size:11px;" aria-valuenow="' . $progressPercent . '" aria-valuemin="0" aria-valuemax="100">' . round($progressPercent, 1) . '%</div>';
                echo '</div>';
                echo '<div class="d-flex justify-content-between small text-muted">';
                echo '<span>Đã thanh toán: ' . number_format($totalPaid, 0, ',', '.') . ' VND</span>';
                echo '<span>Mục tiêu: 5.000.000 VND</span>';
                echo '</div></div>';
                echo '<div class="col-md-4 text-center mt-3 mt-md-0">';
                echo '<div class="p-2 rounded" style="background:rgba(102,126,234,0.1);">';
                echo '<small class="text-muted">Tuần này</small>';
                echo '<h5 class="mb-0" style="color:#667eea;">' . number_format($totalPaid, 0, ',', '.') . ' VND</h5>';
                echo '</div></div></div>';
            }

            echo '</div></div></div>';
        } catch (Exception $e) {
            // Silently fail - don't break the page
        }
    }
    ?>

    <div class="container mb-4 bg-white rounded-4 shadow-sm" style="min-height: 300px;">
        <h4 class="pt-4 px-3">Danh Sách Sản Phẩm</h4>
        <div class="row">
            <!-- <div class="p-2 col-xl-3 col-md-4 col-sm-6">
                <div class="bg-white p-3 border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <img src="../../../AdminManage/src/Products/IMG_Products/Screenshot 2023-11-28 234413.png" alt="" class="img-fluid rounded-4">
                    </div>
                    <div class="card-body mt-3">
                        <h5 class="card-title">Card title</h5>
                    </div>
                    <div class="row">
                        <div class="col-6 p-2"><a href="#" class="btn btn-light mt-3 w-100">1000000d</a></div>
                        <div class="col-3 p-2"><a href="#" class="btn btn-warning mt-3 w-100"><i class="fa-solid fa-cart-shopping"></i></a></div>
                        <div class="col-3 p-2"><a href="#" class="btn btn-danger mt-3 w-100"><i class="fa-regular fa-heart"></i></a></div>
                    </div>
                </div>
            </div> -->

            <?php
            while ($row_select_product_limit = mysqli_fetch_assoc($result_select_product_limit)) {
                echo '<div class="p-2 col-xl-3 col-md-4 col-sm-6">
                    <div class="bg-white p-3 border-0 shadow-sm rounded-4 d-flex flex-column" style="height: 100%;">
                        <div class="card-body p-0 mb-3" style="height: 250px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="../../../AdminManage/src/Products/' . $row_select_product_limit['AnhSanPham'] . '" alt="" class="img-fluid rounded-4" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                        <div class="card-body mt-auto p-0" style="min-height: 60px; flex-grow: 1; display: flex; flex-direction: column;">
                            <a style="text-decoration: none;" href="Detail_Product.php?product_id=' . $row_select_product_limit['IDSanPham'] . '"><h5 class="card-title text-black" style="font-size: 1rem; line-height: 1.4; margin-bottom: 10px;">' . $row_select_product_limit['TenSanPham'] . '</h5></a>
                        </div>
                        <div class="row mt-auto">
                            <div class="col-6 p-2"><a href="#" class="btn btn-light mt-3 w-100">' . $row_select_product_limit['GiaTien'] . 'đ</a></div>
                            <div class="col-3 p-2"><button onclick="addToCart(' . $row_select_product_limit['IDSanPham'] . ')" class="btn btn-warning mt-3 w-100"><i class="fa-solid fa-cart-shopping"></i></button></div>
                            <div class="col-3 p-2"><a href="#" class="btn btn-danger mt-3 w-100"><i class="fa-regular fa-heart"></i></a></div>
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
                        echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="View_Product.php?page=' . ($trangHienTai - 1) . '">Trang Trước</a></li>';
                    }

                    // Lặp khoảng giữa
                    for ($i = 1; $i <= $tongSoTrang; $i++) {
                        // Nếu là trang hiện tại thì hiển thị thẻ span
                        // ngược lại hiển thị thẻ a
                        if ($i == $trangHienTai) {
                            echo '<li class="page-item"><a class="page-link" style="color: white; background-color: #2D9596;" href="">' . $i . '</a></li>';
                        } else {
                            echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="View_Product.php?page=' . $i . '">' . $i . '</a></li>';
                        }
                    }

                    // nếu current_page < $total_page và total_page > 1 mới hiển thị nút prev
                    if ($trangHienTai < $tongSoTrang && $tongSoTrang > 1) {
                        echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="View_Product.php?page=' . ($trangHienTai + 1) . '">Trang Tiếp</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </div>

    <!-- footer -->
    <?php include("../Layouts/Footer.php"); ?>
    <!-- end footer -->

    <!-- ajax -->
    <script>
        // Xử lý sự kiện nhấp vào nút "Add to Cart"
        function addToCart(IDSanPham) {
            $.ajax({
                type: 'POST',
                url: 'Cart.php',
                data: {
                    IDSanPham: IDSanPham,
                    addcart: 'addcart'
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 'success') {
                        alert(response.message || 'Sản phẩm đã được thêm vào giỏ hàng.');
                        // Reload page to update cart count
                        location.reload();
                    } else if (response && response.status === 'error') {
                        alert(response.message || 'Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                    } else {
                        alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cart error:', status, error, xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.message) {
                            alert(response.message);
                        } else {
                            alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                        }
                    } catch(e) {
                        alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                    }
                }
            });
        }
    </script>

    <!-- Promotional Banner for Potential Customer Program -->
    <?php include("../Layouts/Promo_Banner.php"); ?>
</body>

</html>