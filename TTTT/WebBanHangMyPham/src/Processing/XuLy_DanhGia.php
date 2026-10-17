<?php
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';

if (isset($_SESSION['user']) && isset($_POST['IDSanPham']) && isset($_POST['rating']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $IDSanPham = intval($_POST['IDSanPham']);
    $rating = intval($_POST['rating']);
    
    // Validate rating (1-5 stars)
    if ($rating < 1 || $rating > 5) {
        echo 'invalid_rating';
        exit();
    }
    
    // Get user ID
    $name_user = $_SESSION['user'];
    $sql_select_id_user = "SELECT * FROM khachhang WHERE TenDangNhap = '$name_user'";
    $result_select_id_user = mysqli_query($conn, $sql_select_id_user);
    
    if (!$result_select_id_user) {
        echo 'error';
        exit();
    }
    
    $row_select_id_user = mysqli_fetch_array($result_select_id_user);
    $id_user = $row_select_id_user['ID_KhachHang'];
    
    // Check if user has purchased and received this product (order status = 3)
    $can_rate = false;
    $sql_check_purchase = "SELECT d.ID_DonHang, d.GhiChu 
                          FROM donhang d
                          INNER JOIN donhangchitiet dct ON d.ID_DonHang = dct.ID_DonHang
                          WHERE d.ID_KhachHang = '$id_user' 
                          AND dct.ID_SanPham = '$IDSanPham' 
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
                                AND ID_SanPham = '$IDSanPham' 
                                AND TrangThai = 'approved'";
            $result_check_return = mysqli_query($conn, $sql_check_return);
            if ($result_check_return && mysqli_num_rows($result_check_return) > 0) {
                $has_approved_return = true;
                break;
            }
        }
        
        if (!$has_approved_return) {
            $can_rate = true;
        }
    }
    
    if (!$can_rate) {
        if (isset($has_approved_return) && $has_approved_return) {
            echo 'return_approved';
        } else {
            echo 'not_verified_purchase';
        }
        exit();
    }
    
    // Check if user already rated this product
    $sql_check_existing = "SELECT ID_DanhGia FROM danhgia WHERE ID_KhachHang = '$id_user' AND ID_SanPham = '$IDSanPham'";
    $result_check_existing = mysqli_query($conn, $sql_check_existing);
    
    if ($result_check_existing && mysqli_num_rows($result_check_existing) > 0) {
        // Update existing rating
        $sql_update_rating = "UPDATE danhgia SET SoSao = $rating WHERE ID_KhachHang = '$id_user' AND ID_SanPham = '$IDSanPham'";
        $result_update_rating = mysqli_query($conn, $sql_update_rating);
        
        if ($result_update_rating) {
            echo 'updated';
        } else {
            echo 'error';
        }
    } else {
        // Insert new rating
        $sql_insert_rating = "INSERT INTO `danhgia` (`ID_DanhGia`, `ID_KhachHang`, `ID_SanPham`, `SoSao`) 
                              VALUES (NULL, '$id_user', '$IDSanPham', '$rating')";
        $result_insert_rating = mysqli_query($conn, $sql_insert_rating);
        
        if ($result_insert_rating) {
            echo 'success';
        } else {
            echo 'error';
        }
    }
} else {
    echo 'error';
}
?>

