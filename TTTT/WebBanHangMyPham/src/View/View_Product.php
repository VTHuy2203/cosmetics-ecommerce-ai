<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

$pdoProducts = app_pdo();
$whereSql = '';
$queryParams = [];
$paginationParams = [];
$searchTerm = '';

if (isset($_GET['category_id'])) {
    $categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
    if ($categoryId && $categoryId > 0) {
        $whereSql = ' WHERE IDDanhMuc = :category_id';
        $queryParams['category_id'] = $categoryId;
        $paginationParams['category_id'] = $categoryId;
    }
} elseif (isset($_GET['brand_id'])) {
    $brandId = filter_input(INPUT_GET, 'brand_id', FILTER_VALIDATE_INT);
    if ($brandId && $brandId > 0) {
        $whereSql = ' WHERE IDThuongHieu = :brand_id';
        $queryParams['brand_id'] = $brandId;
        $paginationParams['brand_id'] = $brandId;
    }
} elseif (isset($_GET['search_like'])) {
    $searchTerm = trim((string) $_GET['search_like']);
    $searchTerm = mb_substr($searchTerm, 0, 100, 'UTF-8');
    if ($searchTerm !== '') {
        $whereSql = ' WHERE TenSanPham LIKE :search_like';
        $queryParams['search_like'] = '%' . $searchTerm . '%';
        $paginationParams['search_like'] = $searchTerm;
    }
} elseif (isset($_GET['hot_product'])) {
    $whereSql = ' WHERE NoiBat = 1';
    $paginationParams['hot_product'] = 1;
}

$countStatement = $pdoProducts->prepare('SELECT COUNT(IDSanPham) FROM sanpham' . $whereSql);
$countStatement->execute($queryParams);
$tongSoLuongSanPham = (int) $countStatement->fetchColumn();
$soSanPhamTrenMoiTrang = 8;
$tongSoTrang = (int) ceil($tongSoLuongSanPham / $soSanPhamTrenMoiTrang);
$trangHienTai = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
if ($tongSoTrang > 0) {
    $trangHienTai = min($trangHienTai, $tongSoTrang);
}
$start = ($trangHienTai - 1) * $soSanPhamTrenMoiTrang;

$productStatement = $pdoProducts->prepare(
    'SELECT * FROM sanpham' . $whereSql . ' ORDER BY IDSanPham DESC LIMIT :limit OFFSET :offset'
);
foreach ($queryParams as $key => $value) {
    $productStatement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$productStatement->bindValue(':limit', $soSanPhamTrenMoiTrang, PDO::PARAM_INT);
$productStatement->bindValue(':offset', $start, PDO::PARAM_INT);
$productStatement->execute();
$products = $productStatement->fetchAll(PDO::FETCH_ASSOC);

function productPageUrl(int $page, array $params): string
{
    $params['page'] = $page;
    return 'View_Product.php?' . http_build_query($params);
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
            $pdoVip = app_pdo();

            // Get customer info and weekly purchase total
            $stmt = $pdoVip->prepare("SELECT customer_label,customer_value_tier FROM khachhang WHERE ID_KhachHang = ?");
            $stmt->execute([$_SESSION['IDNguoiDung']]);
            $custData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get total successful payments this week
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            $stmtPaid = $pdoVip->prepare("SELECT COALESCE(SUM(TongTien), 0) as total_paid
                                           FROM donhang
                                           WHERE ID_KhachHang = ?
                                           AND TrangThaiThanhToan = 'paid'
                                           AND DATE(NgayTao) BETWEEN ? AND ?");
            $stmtPaid->execute([$_SESSION['IDNguoiDung'], $weekStart, $weekEnd]);
            $paidData = $stmtPaid->fetch(PDO::FETCH_ASSOC);
            $totalPaid = (int)($paidData['total_paid'] ?? 0);

            $customerLabel = $custData['customer_label'] ?? 'normal';
            $valueTier = htmlspecialchars((string)($custData['customer_value_tier'] ?? 'new'));
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
                echo '<p class="mb-0 small text-muted">Nhu cầu hiện tại của bạn được ưu tiên hỗ trợ</p>';
                echo '</div></div>';
                echo '<div class="text-end">';
                echo '<span class="badge bg-warning text-dark" style="font-size:14px;padding:8px 16px;">Đang quan tâm</span>';
                echo '</div></div>';
            } else {
                echo '<div class="row align-items-center">';
                echo '<div class="col-md-8">';
                echo '<div class="d-flex align-items-center mb-2">';
                echo '<span style="font-size:24px;">🎯</span>';
                echo '<strong class="ms-2" style="color:#667eea;font-size:16px;">Hạng thành viên: ' . $valueTier . '</strong>';
                echo '</div>';
                echo '<p class="mb-2 small">Điểm thành viên được tính từ lịch sử mua hàng và tách biệt với mức độ quan tâm hiện tại.</p>';
                echo '<div class="d-flex justify-content-between small text-muted">';
                echo '<span>Đã thanh toán: ' . number_format($totalPaid, 0, ',', '.') . ' VND</span>';
                echo '<span>Đơn đã thanh toán trong tuần</span>';
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

            <?php if ($products === []): ?>
                <div class="col-12">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center px-3 py-5" style="min-height:240px;">
                        <div class="d-flex align-items-center justify-content-center rounded-circle mb-3"
                            style="width:72px;height:72px;background:#eaf7f5;color:#2D9596;font-size:30px;">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <h5 class="mb-2">Hiện tại chưa có mặt hàng này</h5>
                        <p class="text-muted mb-3">Vui lòng đợi Admin cập nhật nhé.</p>
                        <?php if ($searchTerm !== ''): ?>
                            <div class="small text-muted mb-3">Từ khóa: <strong><?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <?php endif; ?>
                        <a href="View_Product.php" class="btn btn-outline-success">Xem tất cả sản phẩm</a>
                    </div>
                </div>
            <?php else: ?>
            <?php
            foreach ($products as $row_select_product_limit) {
                $productName = htmlspecialchars((string) $row_select_product_limit['TenSanPham'], ENT_QUOTES, 'UTF-8');
                $productImage = htmlspecialchars((string) $row_select_product_limit['AnhSanPham'], ENT_QUOTES, 'UTF-8');
                echo '<div class="p-2 col-xl-3 col-md-4 col-sm-6">
                    <div class="bg-white p-3 border-0 shadow-sm rounded-4 d-flex flex-column" style="height: 100%;">
                        <div class="card-body p-0 mb-3" style="height: 250px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="../../../AdminManage/src/Products/' . $productImage . '" alt="' . $productName . '" class="img-fluid rounded-4" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                        <div class="card-body mt-auto p-0" style="min-height: 60px; flex-grow: 1; display: flex; flex-direction: column;">
                            <a style="text-decoration: none;" href="Detail_Product.php?product_id=' . (int) $row_select_product_limit['IDSanPham'] . '"><h5 class="card-title text-black" style="font-size: 1rem; line-height: 1.4; margin-bottom: 10px;">' . $productName . '</h5></a>
                        </div>
                        <div class="row mt-auto">
                            <div class="col-6 p-2"><span class="btn btn-light mt-3 w-100">' . number_format((int) $row_select_product_limit['GiaTien'], 0, ',', '.') . ' đ</span></div>
                            <div class="col-3 p-2"><button onclick="addToCart(' . (int) $row_select_product_limit['IDSanPham'] . ')" class="btn btn-warning mt-3 w-100"><i class="fa-solid fa-cart-shopping"></i></button></div>
                            <div class="col-3 p-2"><a href="#" class="btn btn-danger mt-3 w-100"><i class="fa-regular fa-heart"></i></a></div>
                        </div>
                    </div>
                </div>';
            }
            ?>
            <?php endif; ?>
        </div>

        <!-- phân trang -->
        <div class="row mt-3">
            <nav aria-label="Page navigation example" class="d-flex justify-content-center align-items-center">
                <ul class="pagination">
                    <?php
                    // nếu current_page > 1 và total_page > 1 mới hiển thị nút prev
                    if ($trangHienTai > 1 && $tongSoTrang > 1) {
                        echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="' . htmlspecialchars(productPageUrl($trangHienTai - 1, $paginationParams), ENT_QUOTES, 'UTF-8') . '">Trang Trước</a></li>';
                    }

                    // Lặp khoảng giữa
                    for ($i = 1; $i <= $tongSoTrang; $i++) {
                        // Nếu là trang hiện tại thì hiển thị thẻ span
                        // ngược lại hiển thị thẻ a
                        if ($i == $trangHienTai) {
                            echo '<li class="page-item active" aria-current="page"><span class="page-link" style="color: white; background-color: #2D9596;">' . $i . '</span></li>';
                        } else {
                            echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="' . htmlspecialchars(productPageUrl($i, $paginationParams), ENT_QUOTES, 'UTF-8') . '">' . $i . '</a></li>';
                        }
                    }

                    // nếu current_page < $total_page và total_page > 1 mới hiển thị nút prev
                    if ($trangHienTai < $tongSoTrang && $tongSoTrang > 1) {
                        echo '<li class="page-item"><a class="page-link" style="color: #2D9596;" href="' . htmlspecialchars(productPageUrl($trangHienTai + 1, $paginationParams), ENT_QUOTES, 'UTF-8') . '">Trang Tiếp</a></li>';
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
                    addcart: 'addcart',
                    csrf_token: <?php echo json_encode(csrf_token()); ?>
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
