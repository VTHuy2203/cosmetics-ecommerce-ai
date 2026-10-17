<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");
require_once dirname(__DIR__) . '/Services/CustomerEventService.php';

if (isset($_GET['product_id'])) {

    $product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
    if (!$product_id) {
        header('Location: index.php');
        exit();
    }

    $sql = "SELECT sanpham.IDSanPham, sanpham.TenSanPham, sanpham.IDDanhMuc, sanpham.IDThuongHieu, sanpham.SoLuong, sanpham.GiaTien, sanpham.MoTaChiTiet, sanpham.AnhSanPham, sanpham.NoiBat, sanpham.ThoiGianTao, sanpham.ThoiGianCapNhat, danhmuc.TenDanhMuc, thuonghieu.TenThuongHieu from sanpham
    INNER JOIN danhmuc on sanpham.IDDanhMuc = danhmuc.IDDanhMuc
    INNER JOIN thuonghieu on sanpham.IDThuongHieu = thuonghieu.IDThuongHieu 
    Where sanpham.IDSanPham = ?";

    $productStatement = $conn->prepare($sql);
    $productStatement->bind_param('i', $product_id);
    $productStatement->execute();
    $result = $productStatement->get_result();
    $row = $result->fetch_array();

    // Kiểm tra nếu có lỗi trong quá trình truy vấn
    if (!$result) {
        die("Có lỗi xảy ra: " . mysqli_error($conn));
    }
    
    // Kiểm tra nếu sản phẩm không tồn tại
    if (!$row || empty($row)) {
        // Send notification via WebSocket if available
        $wsPushFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'WebSocketServer' . DIRECTORY_SEPARATOR . 'WebSocket_Push.php';
        if (file_exists($wsPushFile)) {
            require_once($wsPushFile);
            if (function_exists('websocket_push')) {
                websocket_push(
                    ['user_channel'],
                    [
                        'type' => 'product_not_found',
                        'title' => 'Sản phẩm không tồn tại',
                        'message' => 'Sản phẩm này không còn tồn tại trong cửa hàng.',
                        'sound' => 'notification',
                        'data' => [
                            'product_id' => $product_id,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ]
                );
            }
        }
        
        // Show error page
        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm không tồn tại</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="alert alert-danger text-center">
                    <h3>⚠️ Sản phẩm không tồn tại</h3>
                    <p>Sản phẩm này không còn tồn tại trong cửa hàng.</p>
                    <a href="index.php" class="btn btn-primary">Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
        exit();
    }

    CustomerEventService::recordMysqli(
        $conn,
        CustomerEventService::currentUserId(),
        CustomerEventService::sessionId(),
        'product_view',
        ['product_id' => (int) $row['IDSanPham']]
    );
} else {
    header('Location: index.php');
    exit();
}
?>

<body>
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

    <div class="container mt-4">
        <div class="row  py-3">
            <div class="col-md-5 mb-3">
                <div class="card border-0 rounded-4 shadow">
                    <!-- Main Image Display with Navigation -->
                    <div class="card-img position-relative" id="mainImageContainer" style="overflow: hidden;">
                        <img id="mainProductImage" src="../../../AdminManage/src/Products/<?php echo $row['AnhSanPham'] ?>" alt=""
                            class="img-fluid rounded-4 w-100" style="max-height: 500px; object-fit: contain;">
                        
                        <!-- Navigation Buttons (shown on hover) -->
                        <button id="prevImageBtn" class="image-nav-btn image-nav-prev" onclick="navigateImage(-1)" style="display: none;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextImageBtn" class="image-nav-btn image-nav-next" onclick="navigateImage(1)" style="display: none;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <!-- Additional Images Gallery -->
                    <?php
                    // Get all images (main + additional)
                    $all_images = [];
                    $all_images[] = $row['AnhSanPham']; // Main image first
                    
                    $sql_additional_images = "SELECT DuongDan FROM product_images WHERE ID_SanPham = '$product_id' ORDER BY ThuTu ASC";
                    $result_additional_images = mysqli_query($conn, $sql_additional_images);
                    $has_additional_images = false;
                    
                    if ($result_additional_images && mysqli_num_rows($result_additional_images) > 0) {
                        $has_additional_images = true;
                        while ($img_row = mysqli_fetch_array($result_additional_images)) {
                            $all_images[] = $img_row['DuongDan'];
                        }
                    }
                    
                    // Store images in JavaScript array
                    echo '<script>';
                    echo 'var productImages = [';
                    foreach ($all_images as $index => $img_path) {
                        echo '"../../../AdminManage/src/Products/' . $img_path . '"';
                        if ($index < count($all_images) - 1) echo ',';
                    }
                    echo '];';
                    echo 'var currentImageIndex = 0;';
                    echo 'var maxStockQuantity = ' . intval($row['SoLuong']) . ';'; // Store stock quantity
                    echo '</script>';
                    
                    if (count($all_images) > 1) {
                        echo '<div class="card-body">';
                        echo '<h6 class="mb-3">Hình ảnh khác:</h6>';
                        echo '<div class="row g-2" id="imageGallery">';
                        
                        // Display all images as thumbnails
                        foreach ($all_images as $index => $img_path) {
                            echo '<div class="col-3">';
                            echo '<img src="../../../AdminManage/src/Products/' . $img_path . '" alt=""';
                            echo ' class="img-thumbnail product-thumbnail" style="cursor: pointer; max-height: 100px; object-fit: contain; width: 100%;"';
                            echo ' onclick="changeMainImage(' . $index . ')" data-index="' . $index . '"';
                            echo ' data-image="../../../AdminManage/src/Products/' . $img_path . '">';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                    
                    <style>
                        .image-nav-btn {
                            position: absolute;
                            top: 50%;
                            transform: translateY(-50%);
                            background-color: rgba(45, 149, 150, 0.8);
                            color: white;
                            border: none;
                            width: 50px;
                            height: 50px;
                            border-radius: 50%;
                            cursor: pointer;
                            font-size: 20px;
                            z-index: 10;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        
                        .image-nav-btn:hover {
                            background-color: rgba(45, 149, 150, 1);
                            transform: translateY(-50%) scale(1.1);
                        }
                        
                        .image-nav-prev {
                            left: 15px;
                        }
                        
                        .image-nav-next {
                            right: 15px;
                        }
                        
                        #mainImageContainer:hover .image-nav-btn {
                            display: flex !important;
                        }
                        
                        .product-thumbnail.active {
                            border: 2px solid #2D9596 !important;
                        }
                    </style>
                    
                    <script>
                        function changeMainImage(index) {
                            if (index >= 0 && index < productImages.length) {
                                currentImageIndex = index;
                                document.getElementById('mainProductImage').src = productImages[index];
                                
                                // Update active thumbnail
                                var thumbnails = document.querySelectorAll('.product-thumbnail');
                                thumbnails.forEach(function(thumb, i) {
                                    if (i === index) {
                                        thumb.classList.add('active');
                                        thumb.style.border = '2px solid #2D9596';
                                    } else {
                                        thumb.classList.remove('active');
                                        thumb.style.border = '2px solid transparent';
                                    }
                                });
                                
                                // Update navigation buttons visibility
                                updateNavButtons();
                            }
                        }
                        
                        function navigateImage(direction) {
                            var newIndex = currentImageIndex + direction;
                            
                            if (newIndex < 0) {
                                newIndex = productImages.length - 1;
                            } else if (newIndex >= productImages.length) {
                                newIndex = 0;
                            }
                            
                            changeMainImage(newIndex);
                        }
                        
                        function updateNavButtons() {
                            // Show buttons only if there are multiple images
                            if (productImages.length > 1) {
                                document.getElementById('prevImageBtn').style.display = 'flex';
                                document.getElementById('nextImageBtn').style.display = 'flex';
                            }
                        }
                        
                        // Set active state for first thumbnail on load
                        document.addEventListener('DOMContentLoaded', function() {
                            if (productImages.length > 0) {
                                changeMainImage(0);
                                updateNavButtons();
                            }
                        });
                        
                        // Keyboard navigation
                        document.addEventListener('keydown', function(e) {
                            var container = document.getElementById('mainImageContainer');
                            if (container && container.matches(':hover')) {
                                if (e.key === 'ArrowLeft') {
                                    navigateImage(-1);
                                } else if (e.key === 'ArrowRight') {
                                    navigateImage(1);
                                }
                            }
                        });
                    </script>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card p-3 border-0 rounded-4">
                    <p style="font-weight: 400; font-size: 45px; line-height: 60px;"><?php echo $row['TenSanPham'] ?>
                    </p>
                    <div class="card-body">
                        <div class="card-text" style="font-size: 20px;">
                            <p>Danh mục: <a style="text-decoration: none; font-weight: 600; color: black;"
                                    href="../View/View_Product.php?category_id=<?php echo $row['IDDanhMuc'] ?>"><?php echo $row['TenDanhMuc'] ?></a>
                            </p>
                            <p>Thương Hiệu: <a style="text-decoration: none; font-weight: 600; color: black;"
                                    href="../View/View_Product.php?brand_id=<?php echo $row['IDThuongHieu'] ?>">
                                    <?php echo $row['TenThuongHieu'] ?></a></p>
                            <p class="mt-2">
                                <span style="font-weight: 600;">Số lượng tồn kho:</span> 
                                <span class="badge <?php echo intval($row['SoLuong']) > 0 ? 'bg-success' : 'bg-danger'; ?>" style="font-size: 16px; padding: 8px 12px;">
                                    <?php 
                                    $stock_quantity = intval($row['SoLuong']);
                                    if ($stock_quantity > 0) {
                                        echo number_format($stock_quantity, 0, ',', '.') . ' sản phẩm';
                                    } else {
                                        echo 'Hết hàng';
                                    }
                                    ?>
                                </span>
                            </p>
                        </div>
                        <div class="d-flex mt-4 justify-content-start">
                            <?php 
                            $stock_qty = intval($row['SoLuong']);
                            $is_out_of_stock = ($stock_qty <= 0);
                            ?>
                            <?php if ($is_out_of_stock): ?>
                                <div class="d-flex align-items-center"
                                    style="width: 110px; border-radius: 10px; border: 1px solid #dc3545;">
                                    <button class="btn btn-link px-2 btn-step" disabled>
                                        <i class="fas fa-minus"></i>
                                    </button>

                                    <input id="input-add-cart" min="0" name="quantity" value="0" type="number" class="" max="0" disabled />

                                    <button class="btn btn-link px-2 btn-step" disabled>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center"
                                    style="width: 110px; border-radius: 10px; border: 1px solid #2D9596;">
                                    <button class="btn btn-link px-2 btn-step" onclick="decreaseQuantity()">
                                        <i class="fas fa-minus"></i>
                                    </button>

                                    <input id="input-add-cart" min="1" name="quantity" value="1" type="number" class="" max="<?php echo $stock_qty; ?>" />

                                    <button class="btn btn-link px-2 btn-step" onclick="increaseQuantity()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="btn fs-5 bg-light p-2 mx-4 border-0 rounded-4">
                                <?php echo $row['GiaTien'] ?> vnđ
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php 
                        $stock_quantity = intval($row['SoLuong']);
                        $is_out_of_stock = ($stock_quantity <= 0);
                        ?>
                        <?php if ($is_out_of_stock): ?>
                            <button disabled
                                class="btn-detail-buy btn p-2 fs-5 bg-secondary bg-gradient text-white border-0" 
                                title="Sản phẩm đã hết hàng">Hết Hàng</button>
                            <button disabled
                                class="btn-detail-add-cart btn btn-info py-2 px-3 mx-2 fs-5 bg-secondary bg-gradient text-white border-0" 
                                title="Sản phẩm đã hết hàng"><i class="fa-solid fa-cart-shopping"></i></button>
                        <?php else: ?>
                            <button onclick="BuyNow(<?php echo $row['IDSanPham']; ?>)"
                                class="btn-detail-buy btn p-2 fs-5 bg-success bg-gradient text-white border-0">Mua
                                Ngay</button>
                            <button onclick="addToCart(<?php echo $row['IDSanPham']; ?>)"
                                class="btn-detail-add-cart btn btn-info py-2 px-3 mx-2 fs-5 bg-warning bg-gradient text-white border-0"><i
                                    class="fa-solid fa-cart-shopping"></i></button>
                        <?php endif; ?>
                        <button onclick="addToLike(<?php echo $row['IDSanPham']; ?>)"
                            class="btn-detail-add-like btn btn-info py-2 px-3 fs-5 bg-danger bg-gradient text-white border-0"><i
                                class="fa-regular fa-heart"></i></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-7 motachitiet mb-4">
                <div class="card rounded-4 text-start border-0 shadow">
                    <div class="card-body">
                        <h3 class="card-title">Thông Tin Sản Phẩm</h3>
                        <p class="card-text"><?php echo $row['MoTaChiTiet']; ?></p>

                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card text-start rounded-4 border-0 shadow">
                    <div class=" card-body">
                        <h3 class="card-title">Bình Luận & Đánh Giá</h3>
                    </div>
                    
                    <!-- Star Rating Section -->
                    <div class="card-body border-0 rounded-4 mx-3 mb-3">
                        <h5 class="mb-3">Đánh Giá Sản Phẩm</h5>
                        <?php
                        // Get average rating. Schema is provisioned by migrations.
                        $sql_avg_rating = "SELECT AVG(SoSao) as avg_rating, COUNT(*) as total_ratings FROM danhgia WHERE ID_SanPham = '$product_id'";
                        $result_avg_rating = mysqli_query($conn, $sql_avg_rating);
                        $avg_rating = 0;
                        $total_ratings = 0;
                        if ($result_avg_rating) {
                            $row_avg = mysqli_fetch_array($result_avg_rating);
                            $avg_rating = $row_avg['avg_rating'] ? round($row_avg['avg_rating'], 1) : 0;
                            $total_ratings = intval($row_avg['total_ratings']);
                        }
                        
                        // Display average rating
                        echo '<div class="mb-3">';
                        echo '<strong>Đánh giá trung bình: </strong>';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $avg_rating) {
                                echo '<i class="fas fa-star text-warning"></i>';
                            } else if ($i - 0.5 <= $avg_rating) {
                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                            } else {
                                echo '<i class="far fa-star text-warning"></i>';
                            }
                        }
                        echo ' <span class="text-muted">(' . $avg_rating . '/5 - ' . $total_ratings . ' đánh giá)</span>';
                        echo '</div>';
                        
                        // Check if user can rate (has purchased and received)
                        $can_rate = false;
                        $user_rating = 0;
                        if (isset($_SESSION['user'])) {
                            $name_user = $_SESSION['user'];
                            $sql_user = "SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = '$name_user'";
                            $result_user = mysqli_query($conn, $sql_user);
                            if ($result_user && mysqli_num_rows($result_user) > 0) {
                                $row_user = mysqli_fetch_array($result_user);
                                $id_user = $row_user['ID_KhachHang'];
                                
                                // Check if user has purchased and received
                                $sql_check_purchase = "SELECT d.ID_DonHang, d.GhiChu 
                                                      FROM donhang d
                                                      INNER JOIN donhangchitiet dct ON d.ID_DonHang = dct.ID_DonHang
                                                      WHERE d.ID_KhachHang = '$id_user' 
                                                      AND dct.ID_SanPham = '$product_id' 
                                                      AND d.TrangThai = 3";
                                $result_check_purchase = mysqli_query($conn, $sql_check_purchase);
                                if ($result_check_purchase && mysqli_num_rows($result_check_purchase) > 0) {
                                    // Check if any order has an approved return/refund for this product
                                    $has_approved_return = false;
                                    mysqli_data_seek($result_check_purchase, 0);
                                    while ($order_row = mysqli_fetch_array($result_check_purchase)) {
                                        $order_id = intval($order_row['ID_DonHang']);
                                        $ghi_chu = $order_row['GhiChu'] ?? '';
                                        
                                        // Check if order is marked as returned
                                        if (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false) {
                                            $has_approved_return = true;
                                            break;
                                        }
                                        
                                        // Check if there's an approved return/refund request for this order and product
                                        $sql_check_return = "SELECT * FROM return_refund_requests 
                                                            WHERE ID_DonHang = $order_id 
                                                            AND ID_KhachHang = '$id_user' 
                                                            AND ID_SanPham = '$product_id' 
                                                            AND TrangThai = 'approved'";
                                        $result_check_return = mysqli_query($conn, $sql_check_return);
                                        if ($result_check_return && mysqli_num_rows($result_check_return) > 0) {
                                            $has_approved_return = true;
                                            break;
                                        }
                                    }
                                    
                                    if (!$has_approved_return) {
                                        $can_rate = true;
                                        
                                        // Get user's existing rating
                                        $sql_user_rating = "SELECT SoSao FROM danhgia WHERE ID_KhachHang = '$id_user' AND ID_SanPham = '$product_id'";
                                        $result_user_rating = mysqli_query($conn, $sql_user_rating);
                                        if ($result_user_rating && mysqli_num_rows($result_user_rating) > 0) {
                                            $row_user_rating = mysqli_fetch_array($result_user_rating);
                                            $user_rating = intval($row_user_rating['SoSao']);
                                        }
                                    }
                                }
                            }
                        }
                        
                        if ($can_rate) {
                            echo '<div class="mb-3">';
                            echo '<label class="form-label">Đánh giá của bạn:</label><br>';
                            echo '<div class="star-rating" data-product-id="' . $product_id . '" data-user-rating="' . $user_rating . '">';
                            for ($i = 1; $i <= 5; $i++) {
                                $star_class = ($i <= $user_rating) ? 'fas fa-star text-warning' : 'far fa-star';
                                echo '<i class="star-icon ' . $star_class . '" data-rating="' . $i . '" style="font-size: 24px; cursor: pointer; margin-right: 5px;"></i>';
                            }
                            echo '</div>';
                            echo '<small class="text-muted" id="rating-message"></small>';
                            echo '</div>';
                        } else if (isset($_SESSION['user'])) {
                            // Check if user has approved return for this product
                            $has_return_message = false;
                            if (isset($has_approved_return) && $has_approved_return) {
                                echo '<div class="alert alert-warning">';
                                echo '<small>Bạn không thể đánh giá sản phẩm đã được hoàn trả.</small>';
                                echo '</div>';
                                $has_return_message = true;
                            }
                            if (!$has_return_message) {
                                echo '<div class="alert alert-info">';
                                echo '<small>Bạn cần mua và nhận hàng thành công để có thể đánh giá sản phẩm này.</small>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<small>Vui lòng <a href="Login.php">đăng nhập</a> và mua sản phẩm để đánh giá.</small>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <!-- Review Filter -->
                    <div class="card-body border-0 rounded-4 mx-3 mb-3">
                        <label class="form-label"><strong>Lọc đánh giá:</strong></label>
                        <select id="rating-filter" class="form-select form-select-sm" onchange="filterReviews()" style="width: auto; display: inline-block;">
                            <option value="all">Tất cả</option>
                            <option value="5">5 sao</option>
                            <option value="4">4 sao</option>
                            <option value="3">3 sao</option>
                            <option value="2">2 sao</option>
                            <option value="1">1 sao</option>
                        </select>
                    </div>
                    
                    <div class="" id="setbinhluan">
                    </div>
                    <div id="reviews-container">
                    <?php
                    // Get selected filter from URL or default to 'all'
                    $rating_filter = isset($_GET['rating_filter']) ? intval($_GET['rating_filter']) : 0;
                    
                    // Build query with rating filter
                    $sql_list_comment = "SELECT b.*, COALESCE(d.SoSao, 0) as rating
                                        FROM binhluan b
                                        LEFT JOIN danhgia d ON b.ID_KhachHang = d.ID_KhachHang AND b.ID_SanPham = d.ID_SanPham
                                        WHERE b.ID_SanPham = '$product_id'";
                    
                    if ($rating_filter > 0) {
                        $sql_list_comment .= " AND COALESCE(d.SoSao, 0) = $rating_filter";
                    }
                    
                    $sql_list_comment .= " ORDER BY b.ID_BinhLuan DESC";
                    
                    $result_list_comment = mysqli_query($conn, $sql_list_comment);
                    while ($row_list_comment = mysqli_fetch_array($result_list_comment)) {
                        $id_user_comment = $row_list_comment['ID_KhachHang'];
                        $NoiDungBinhLuan_comment = htmlspecialchars((string) $row_list_comment['NoiDungBinhLuan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                        //lấy họ tên ng comment
                        $sql_hoten_comment = "select * from khachhang where ID_KhachHang = '$id_user_comment'";
                        $result_hoten_comment = mysqli_query($conn, $sql_hoten_comment);
                        $row_hoten_comment = mysqli_fetch_array($result_hoten_comment);
                        $hoten_user_comment = htmlspecialchars((string) $row_hoten_comment['HoVaTen'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $rating = isset($row_list_comment['rating']) ? intval($row_list_comment['rating']) : 0;

                        // Only show reviews from verified purchasers
                        if ($row_list_comment['DaMuaHang'] == 1) {
                            echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light review-item" data-rating="' . $rating . '">';
                            echo '<h6 class="card-title">' . $hoten_user_comment . ' <span class="text-success"><i class="fa-solid fa-circle-check"></i> Đã mua hàng</span></h6>';
                            
                            // Display star rating if exists
                            if ($rating > 0) {
                                echo '<div class="mb-2">';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star text-warning"></i>';
                                    } else {
                                        echo '<i class="far fa-star text-warning"></i>';
                                    }
                                }
                                echo '</div>';
                            }
                            
                            echo '<p class="card-text mx-2 fs-6">' . $NoiDungBinhLuan_comment . '</p>';
                            echo '</div>';
                        }
                    }

                    //form bình luận - Only show if user has purchased
                    $can_review = false;
                    if (isset($_SESSION['user'])) {
                        $name_user = $_SESSION['user'];
                        $sql_user = "SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = '$name_user'";
                        $result_user = mysqli_query($conn, $sql_user);
                        if ($result_user && mysqli_num_rows($result_user) > 0) {
                            $row_user = mysqli_fetch_array($result_user);
                            $id_user = $row_user['ID_KhachHang'];
                            
                            // Check if user has purchased and received this product
                            $sql_check_purchase = "SELECT d.ID_DonHang, d.GhiChu 
                                                  FROM donhang d
                                                  INNER JOIN donhangchitiet dct ON d.ID_DonHang = dct.ID_DonHang
                                                  WHERE d.ID_KhachHang = '$id_user' 
                                                  AND dct.ID_SanPham = '$product_id' 
                                                  AND d.TrangThai = 3";
                            $result_check_purchase = mysqli_query($conn, $sql_check_purchase);
                            if ($result_check_purchase && mysqli_num_rows($result_check_purchase) > 0) {
                                // Check if any order has an approved return/refund for this product
                                $has_approved_return = false;
                                while ($order_row = mysqli_fetch_array($result_check_purchase)) {
                                    $order_id = intval($order_row['ID_DonHang']);
                                    $ghi_chu = $order_row['GhiChu'] ?? '';
                                    
                                    // Check if order is marked as returned
                                    if (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false) {
                                        $has_approved_return = true;
                                        break;
                                    }
                                    
                                    // Check if there's an approved return/refund request for this order and product
                                    $sql_check_return = "SELECT * FROM return_refund_requests 
                                                        WHERE ID_DonHang = $order_id 
                                                        AND ID_KhachHang = '$id_user' 
                                                        AND ID_SanPham = '$product_id' 
                                                        AND TrangThai = 'approved'";
                                    $result_check_return = mysqli_query($conn, $sql_check_return);
                                    if ($result_check_return && mysqli_num_rows($result_check_return) > 0) {
                                        $has_approved_return = true;
                                        break;
                                    }
                                }
                                
                                if (!$has_approved_return) {
                                    $can_review = true;
                                }
                            }
                        }
                    }
                    
                    if (!isset($_SESSION['user'])) {
                        echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">
                                <h6 class="card-title">Vui lòng đăng nhập để bình luận. <a href="Login.php">Đăng nhập tại đây</a></h6>
                            </div>';
                    } else if (!$can_review) {
                        // Check if user has approved return for this product
                        $comment_has_return = false;
                        if (isset($id_user)) {
                            $sql_check_comment_return = "SELECT d.ID_DonHang, d.GhiChu 
                                                          FROM donhang d
                                                          INNER JOIN donhangchitiet dct ON d.ID_DonHang = dct.ID_DonHang
                                                          WHERE d.ID_KhachHang = '$id_user' 
                                                          AND dct.ID_SanPham = '$product_id' 
                                                          AND d.TrangThai = 3";
                            $result_check_comment_return = mysqli_query($conn, $sql_check_comment_return);
                            if ($result_check_comment_return && mysqli_num_rows($result_check_comment_return) > 0) {
                                while ($order_row_comment = mysqli_fetch_array($result_check_comment_return)) {
                                    $order_id_comment = intval($order_row_comment['ID_DonHang']);
                                    $ghi_chu_comment = $order_row_comment['GhiChu'] ?? '';
                                    
                                    if (stripos($ghi_chu_comment, '[ĐÃ HOÀN TRẢ]') !== false) {
                                        $comment_has_return = true;
                                        break;
                                    }
                                    
                                    $sql_check_comment_return_req = "SELECT * FROM return_refund_requests 
                                                                    WHERE ID_DonHang = $order_id_comment 
                                                                    AND ID_KhachHang = '$id_user' 
                                                                    AND ID_SanPham = '$product_id' 
                                                                    AND TrangThai = 'approved'";
                                    $result_check_comment_return_req = mysqli_query($conn, $sql_check_comment_return_req);
                                    if ($result_check_comment_return_req && mysqli_num_rows($result_check_comment_return_req) > 0) {
                                        $comment_has_return = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if ($comment_has_return) {
                            echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">
                                    <h6 class="card-title">Bạn không thể bình luận về sản phẩm đã được hoàn trả.</h6>
                                </div>';
                        } else {
                            echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">
                                    <h6 class="card-title">Bạn cần mua và nhận hàng thành công để có thể bình luận về sản phẩm này.</h6>
                                </div>';
                        }
                    } else {
                        echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">
                                <div class="form-group mb-3">
                                    <textarea id="noidungbinhluan" style="padding: 20px;" class=" shadow-sm form-control border border-success border-opacity-75 rounded-5" rows="5" placeholder="Nội dung..."></textarea>
                                </div>
                                <button onclick="Comment(' . $product_id . ')" style="background-color: #2D9596; color: white;" type="submit" class="btn p-3 border-0 rounded-5 shadow">Bình Luận</button>
                            </div>';
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- footer -->
    <?php include("../Layouts/Footer.php"); ?>
    <!-- end footer -->

    <!-- js -->
    <script>
        function decreaseQuantity() {
            var inputElement = document.getElementById('input-add-cart');
            var currentValue = parseInt(inputElement.value);

            if (currentValue > 1) {
                inputElement.value = currentValue - 1;
            }
        }

        function increaseQuantity() {
            var inputElement = document.getElementById('input-add-cart');
            var currentValue = parseInt(inputElement.value);
            var maxStock = typeof maxStockQuantity !== 'undefined' ? maxStockQuantity : 999999;

            if (currentValue < maxStock) {
                inputElement.value = currentValue + 1;
            } else {
                alert('Số lượng không được vượt quá tồn kho (' + maxStock + ' sản phẩm)');
            }
        }
        
        // Validate quantity on input change
        document.addEventListener('DOMContentLoaded', function() {
            var inputElement = document.getElementById('input-add-cart');
            if (inputElement) {
                inputElement.addEventListener('change', function() {
                    var maxStock = typeof maxStockQuantity !== 'undefined' ? maxStockQuantity : 999999;
                    var currentValue = parseInt(this.value);
                    
                    if (currentValue > maxStock) {
                        alert('Số lượng không được vượt quá tồn kho (' + maxStock + ' sản phẩm)');
                        this.value = maxStock;
                    } else if (currentValue < 1) {
                        this.value = 1;
                    }
                });
            }
        });
    </script>

    <script>
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
                        // Handle plain text response (fallback)
                        try {
                            var parsed = JSON.parse(response);
                            if (parsed.status === 'error') {
                                alert(parsed.message || 'Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                            }
                        } catch(e) {
                            alert('Sản phẩm này không còn hàng. Vui lòng đợi Admin cập nhật.');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cart error:', status, error, xhr.responseText);
                    // Try to parse error response
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

        function addToLike(IDSanPham) {
            $.ajax({
                type: 'POST',
                url: '../Processing/XuLy_YeuThich.php',
                data: {
                    IDSanPham: IDSanPham,
                    csrf_token: <?php echo json_encode(csrf_token()); ?>
                },
                success: function(response) {
                    if (response == "tontai") {
                        alert('Bạn đã thêm vào danh sách yêu thích rồi!');
                    } else if (response == "success") {
                        alert('Thêm vào danh sách yêu thích thành công');
                    } else if (response == "xoathanhcong") {
                        alert('Xóa khỏi danh sách yêu thích thành công');
                    } else if (response == "chuadangnhap") {
                        alert('Vui lòng đăng nhập!!!');
                    }
                },
                error: function() {
                    alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                }
            });
        }

        function BuyNow(IDSanPham) {
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
                        window.location.href = "Cart.php";
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

        function Comment(IDSanPham) {
            var noidungbinhluan = document.getElementById('noidungbinhluan').value;

            if (noidungbinhluan == "") {
                alert('Hãy nhập nội dung vào bình luận!');
                return;
            }

            $.ajax({
                type: 'POST',
                url: '../Processing/XuLy_BinhLuan.php',
                data: {
                    IDSanPham: IDSanPham,
                    NoiDungBinhLuan: noidungbinhluan,
                    csrf_token: <?= json_encode(csrf_token()) ?>
                },
                success: function(response) {
                    response = response.trim();
                    if (response == "error") {
                        alert("Đã xảy ra lỗi!")
                    } else if (response == "not_verified_purchase") {
                        alert("Bạn cần mua và nhận hàng thành công để có thể bình luận về sản phẩm này.");
                    } else if (response == "return_approved") {
                        alert("Bạn không thể bình luận về sản phẩm đã được hoàn trả.");
                    } else {
                        // hiển thị comment
                        var setbinhluanDiv = document.getElementById("setbinhluan");
                        setbinhluanDiv.innerHTML = response;
                        //xóa text trong textarea
                        var textareaComment = document.getElementById("noidungbinhluan");
                        textareaComment.value = "";
                        // Reload page to show new comment
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }
                },
                error: function(xhr) {
                    var code = (xhr.responseText || '').trim();
                    var messages = {
                        login_required: 'Vui lòng đăng nhập để bình luận.',
                        csrf_invalid: 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.',
                        not_verified_purchase: 'Bạn cần mua và nhận hàng thành công để bình luận.',
                        return_approved: 'Bạn không thể bình luận về sản phẩm đã được hoàn trả.',
                        invalid_comment: 'Bình luận cần có từ 1 đến 1000 ký tự và sản phẩm hợp lệ.'
                    };
                    alert(messages[code] || 'Đã xảy ra lỗi. Vui lòng thử lại sau.');
                }
            });
        }
        
        // Star rating functionality
        $(document).ready(function() {
            $('.star-icon').hover(function() {
                var rating = $(this).data('rating');
                var productId = $(this).closest('.star-rating').data('product-id');
                highlightStars($(this).closest('.star-rating'), rating);
            });
            
            $('.star-rating').mouseleave(function() {
                var userRating = $(this).data('user-rating');
                highlightStars($(this), userRating);
            });
            
            $('.star-icon').click(function() {
                var rating = $(this).data('rating');
                var productId = $(this).closest('.star-rating').data('product-id');
                var starRating = $(this).closest('.star-rating');
                
                $.ajax({
                    type: 'POST',
                    url: '../Processing/XuLy_DanhGia.php',
                    data: {
                        IDSanPham: productId,
                        rating: rating,
                        csrf_token: <?php echo json_encode(csrf_token()); ?>
                    },
                    success: function(response) {
                        response = response.trim();
                        if (response === 'success' || response === 'updated') {
                            starRating.data('user-rating', rating);
                            highlightStars(starRating, rating);
                            $('#rating-message').text('Cảm ơn bạn đã đánh giá!').removeClass('text-danger').addClass('text-success');
                            // Reload page after 1 second to update average rating
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else if (response === 'not_verified_purchase') {
                            $('#rating-message').text('Bạn cần mua và nhận hàng thành công để đánh giá.').removeClass('text-success').addClass('text-danger');
                        } else if (response === 'return_approved') {
                            $('#rating-message').text('Bạn không thể đánh giá sản phẩm đã được hoàn trả.').removeClass('text-success').addClass('text-danger');
                        } else if (response === 'invalid_rating') {
                            $('#rating-message').text('Đánh giá không hợp lệ.').removeClass('text-success').addClass('text-danger');
                        } else {
                            $('#rating-message').text('Đã xảy ra lỗi. Vui lòng thử lại.').removeClass('text-success').addClass('text-danger');
                        }
                    },
                    error: function() {
                        $('#rating-message').text('Đã xảy ra lỗi. Vui lòng thử lại sau.').removeClass('text-success').addClass('text-danger');
                    }
                });
            });
            
            function highlightStars(container, rating) {
                container.find('.star-icon').each(function() {
                    var starRating = $(this).data('rating');
                    if (starRating <= rating) {
                        $(this).removeClass('far fa-star').addClass('fas fa-star text-warning');
                    } else {
                        $(this).removeClass('fas fa-star text-warning').addClass('far fa-star');
                    }
                });
            }
        });
        
        // Review filter function
        function filterReviews() {
            var selectedRating = document.getElementById('rating-filter').value;
            var url = new URL(window.location.href);
            if (selectedRating === 'all') {
                url.searchParams.delete('rating_filter');
            } else {
                url.searchParams.set('rating_filter', selectedRating);
            }
            window.location.href = url.toString();
        }
        
        // Set filter value on page load
        $(document).ready(function() {
            var urlParams = new URLSearchParams(window.location.search);
            var ratingFilter = urlParams.get('rating_filter');
            if (ratingFilter) {
                document.getElementById('rating-filter').value = ratingFilter;
            }
        });
    </script>

</body>

</html>
