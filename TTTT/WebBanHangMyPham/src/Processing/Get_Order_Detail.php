<?php
include("../DB_Connect.php");

if (!isset($_SESSION['user']) || !isset($_GET['order_id'])) {
    echo '<div class="alert alert-danger">Không có quyền truy cập.</div>';
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['IDNguoiDung'] ?? 0;

// Get user ID from username
$sql_user = "SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = '" . $_SESSION['user'] . "'";
$result_user = mysqli_query($conn, $sql_user);
$row_user = mysqli_fetch_array($result_user);
$idKH = $row_user['ID_KhachHang'];

// Get order info
$sql_order = "SELECT * FROM donhang WHERE ID_DonHang = '$order_id' AND ID_KhachHang = '$idKH'";
$result_order = mysqli_query($conn, $sql_order);

if (!$result_order || mysqli_num_rows($result_order) == 0) {
    echo '<div class="alert alert-danger">Đơn hàng không tồn tại hoặc bạn không có quyền xem.</div>';
    exit();
}

$order = mysqli_fetch_array($result_order);

$st = intval($order['TrangThai']);
$ghi_chu = $order['GhiChu'] ?? '';

// Check if order is returned
$is_returned = (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false);
if (!$is_returned) {
    // Check if there's an approved return/refund request for this order
    $sql_check_return = "SELECT * FROM return_refund_requests WHERE ID_DonHang = '$order_id' AND ID_KhachHang = '$idKH' AND TrangThai = 'approved' LIMIT 1";
    $result_check_return = mysqli_query($conn, $sql_check_return);
    $is_returned = ($result_check_return && mysqli_num_rows($result_check_return) > 0);
}

if ($is_returned) {
    $status_text = 'Đã hoàn trả';
    $status_class = 'warning';
} else {
    $status_text = ($st==0? 'Cần duyệt' : ($st==1? 'Chuẩn bị' : ($st==2? 'Đang giao' : 'Giao thành công')));
    $status_class = ($st==0? 'warning' : ($st==1? 'info' : ($st==2? 'primary' : 'success')));
}

// Get order details
$sql_details = "SELECT dct.*, sp.TenSanPham, sp.AnhSanPham, sp.GiaTien, sp.IDSanPham
               FROM donhangchitiet dct 
               INNER JOIN sanpham sp ON dct.ID_SanPham = sp.IDSanPham 
               WHERE dct.ID_DonHang = '$order_id'";
$result_details = mysqli_query($conn, $sql_details);

// Check which products are unrated (for delivered orders)
// Exclude products with approved returns
$unrated_products = [];
if ($st == 3) {
    // Check if order has approved return
    $ghi_chu = $order['GhiChu'] ?? '';
    $is_returned = (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false);
    
    $sql_check_rated = "SELECT dct.ID_SanPham 
                       FROM donhangchitiet dct
                       LEFT JOIN danhgia dg ON dg.ID_KhachHang = '$idKH' AND dg.ID_SanPham = dct.ID_SanPham
                       WHERE dct.ID_DonHang = '$order_id' AND dg.ID_DanhGia IS NULL";
    $result_check_rated = mysqli_query($conn, $sql_check_rated);
    if ($result_check_rated) {
        while ($row_rated = mysqli_fetch_array($result_check_rated)) {
            $product_id = intval($row_rated['ID_SanPham']);
            
            // Skip if order is returned or product has approved return request
            if (!$is_returned) {
                $sql_check_return = "SELECT * FROM return_refund_requests 
                                    WHERE ID_DonHang = '$order_id' 
                                    AND ID_KhachHang = '$idKH' 
                                    AND ID_SanPham = $product_id 
                                    AND TrangThai = 'approved'";
                $result_check_return = mysqli_query($conn, $sql_check_return);
                if (!$result_check_return || mysqli_num_rows($result_check_return) == 0) {
                    $unrated_products[] = $product_id;
                }
            }
        }
    }
}

$order_date = isset($order['NgayTao']) ? date('d/m/Y H:i', strtotime($order['NgayTao'])) : 'N/A';
?>

<div class="order-detail">
    <div class="mb-4">
        <h5>Thông Tin Đơn Hàng</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">Mã đơn hàng:</th>
                <td><strong>#<?php echo $order['ID_DonHang']; ?></strong></td>
            </tr>
            <tr>
                <th>Ngày đặt:</th>
                <td><?php echo $order_date; ?></td>
            </tr>
            <tr>
                <th>Trạng thái:</th>
                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
            </tr>
            <tr>
                <th>Thanh toán:</th>
                <td>
                    <?php if (isset($order['DaThanhToan']) && intval($order['DaThanhToan'])==1): ?>
                        <span class="badge bg-success">Đã thanh toán</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Chưa thanh toán</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="mb-4">
        <h5>Thông Tin Giao Hàng</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">Họ và tên:</th>
                <td><?php echo htmlspecialchars($order['HoVaTen']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($order['Email']); ?></td>
            </tr>
            <tr>
                <th>Số điện thoại:</th>
                <td><?php echo htmlspecialchars($order['SDT']); ?></td>
            </tr>
            <tr>
                <th>Địa chỉ:</th>
                <td><?php echo htmlspecialchars($order['DiaChi']); ?></td>
            </tr>
            <tr>
                <th>Ghi chú:</th>
                <td><?php echo htmlspecialchars($order['GhiChu'] ?: 'Không có'); ?></td>
            </tr>
        </table>
    </div>
    
    <?php if ($st == 3 && count($unrated_products) > 0): ?>
        <div class="alert alert-info mb-4">
            <h6 class="alert-heading"><i class="fas fa-star me-2"></i>Đánh Giá Sản Phẩm</h6>
            <p class="mb-2">Đơn hàng của bạn đã được giao thành công! Vui lòng đánh giá các sản phẩm sau:</p>
            <ul class="mb-0">
                <?php 
                mysqli_data_seek($result_details, 0);
                while ($detail = mysqli_fetch_array($result_details)): 
                    if (in_array(intval($detail['IDSanPham']), $unrated_products)):
                ?>
                    <li>
                        <a href="../View/Detail_Product.php?product_id=<?php echo $detail['IDSanPham']; ?>" class="alert-link">
                            <?php echo htmlspecialchars($detail['TenSanPham']); ?>
                        </a>
                    </li>
                <?php 
                    endif;
                endwhile; 
                ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <h5>Chi Tiết Sản Phẩm</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    mysqli_data_seek($result_details, 0);
                    while ($detail = mysqli_fetch_array($result_details)): 
                        $quantity = intval($detail['SoLuong']);
                        $price = intval($detail['GiaTien']);
                        $subtotal = $quantity * $price;
                        $total += $subtotal;
                        $product_id = intval($detail['IDSanPham']);
                        $is_unrated = in_array($product_id, $unrated_products);
                    ?>
                        <tr>
                            <td>
                                <img src="../../../AdminManage/src/Products/<?php echo $detail['AnhSanPham']; ?>" 
                                     alt="" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                            </td>
                            <td>
                                <a href="../View/Detail_Product.php?product_id=<?php echo $product_id; ?>" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <?php echo htmlspecialchars($detail['TenSanPham']); ?>
                                </a>
                                <?php if ($st == 3 && $is_unrated): ?>
                                    <span class="badge bg-warning ms-2" title="Chưa đánh giá">
                                        <i class="fas fa-star"></i> Chưa đánh giá
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $quantity; ?></td>
                            <td><?php echo number_format($price, 0, ',', '.'); ?> đ</td>
                            <td><strong><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</strong></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Tổng tiền:</th>
                        <th><?php echo number_format($order['TongTien'], 0, ',', '.'); ?> đ</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

