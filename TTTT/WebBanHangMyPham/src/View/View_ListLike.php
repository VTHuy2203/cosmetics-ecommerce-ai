<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (isset($_SESSION['user'])) {

    //lấy id khach hang
    $name_khachhang_like = $_SESSION['user'];
    $sql_khachhang_like = "select * from khachhang where TenDangNhap = '$name_khachhang_like'";
    $result_khachhang_like = mysqli_query($conn, $sql_khachhang_like);
    $row_khachhang_like = mysqli_fetch_array($result_khachhang_like);
    $id_khachhang_like = $row_khachhang_like['ID_KhachHang'];

    //tính tổng số lượng sản phẩm
    $sql_tong_so_luong_sp = "select count(ID_KhachHang) as tongsoluongsanpham from danhsachyeuthich where ID_KhachHang = '$id_khachhang_like'";
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

    $sql_select_product_limit = "SELECT sanpham.*
                                FROM danhsachyeuthich
                                JOIN sanpham ON danhsachyeuthich.ID_SanPham = sanpham.IDSanPham
                                WHERE danhsachyeuthich.ID_KhachHang = '$id_khachhang_like'
                                LIMIT $start, $soSanPhamTrenMoiTrang";
    $result_select_product_limit = mysqli_query($conn, $sql_select_product_limit);

}
else{
    header("Location: Login.php");
    exit();
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

    <div class="container mb-4 bg-white rounded-4 shadow-sm" style="min-height: 300px;">
        <h4 class="pt-4 px-3">Danh Sách Sản Phẩm</h4>
        <div class="row">
            <?php
            while ($row_select_product_limit = mysqli_fetch_assoc($result_select_product_limit)) {
                echo '<div class="p-2 col-xl-3 col-md-4 col-sm-6">
                    <div class="bg-white p-3 border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <img src="../../../AdminManage/src/Products/' . $row_select_product_limit['AnhSanPham'] . '" alt="" class="img-fluid rounded-4">
                        </div>
                        <div class="card-body mt-3" style="min-height: 50px">
                            <a style="text-decoration: none;" href="Detail_Product.php?product_id=' . $row_select_product_limit['IDSanPham'] . '"><h5 class="card-title text-black">' . $row_select_product_limit['TenSanPham'] . '</h5></a>
                        </div>
                        <div class="row">
                            <div class="col-6 p-2"><button class="btn btn-light mt-3 w-100">' . $row_select_product_limit['GiaTien'] . 'đ</button></div>
                            <div class="col-3 p-2"><button onclick="addToCart(' . $row_select_product_limit['IDSanPham'] . ')" class="btn btn-warning mt-3 w-100"><i class="fa-solid fa-cart-shopping"></i></button></div>
                            <div class="col-3 p-2"><button onclick="addToDelLike(' . $row_select_product_limit['IDSanPham'] . ')" class="btn btn-danger mt-3 w-100"><i class="fa-regular fa-trash-can"></i></button></div>
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
        function addToDelLike(IDSanPham) {
            $.ajax({
                type: 'POST',
                url: '../Processing/XuLy_YeuThich.php',
                data: {
                    del_like_product_id: IDSanPham,
                    csrf_token: <?php echo json_encode(csrf_token()); ?>
                },
                success: function(response) {
                    if(response == "tontai")
                    {
                        alert('Bạn đã thêm vào danh sách yêu thích rồi!');
                    }
                    else if(response == "success")
                    {
                        alert('Thêm vào danh sách yêu thích thành công');
                    }
                    else if(response == "xoathanhcong")
                    {
                        alert('Xóa khỏi danh sách yêu thích thành công');
                        window.location.href = "View_ListLike.php";
                    }
                    else if(response == "chuadangnhap")
                    {
                        alert('Vui lòng đăng nhập!!!');
                    }
                },
                error: function() {
                    alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                }
            });
        }

        function addToCart(IDSanPham) {
            $.ajax({
                type: 'POST',
                url: 'Cart.php',
                data: {
                    IDSanPham: IDSanPham,
                    addcart: 'addcart',
                    csrf_token: <?php echo json_encode(csrf_token()); ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 'success') {
                        alert(response.message || 'Sản phẩm đã được thêm vào giỏ hàng.');
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
</body>

</html>
