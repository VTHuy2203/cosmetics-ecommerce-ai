<?php
include("../DB_Connect.php");
session_start();

if (
    isset($_SESSION['user']) &&
    isset($_POST['IDSanPham']) &&
    isset($_POST['NoiDungBinhLuan']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $IDSanPham = $_POST['IDSanPham'];
    $NoiDungBinhLuan = $_POST['NoiDungBinhLuan'];

    //lấy id user
    $name_user = $_SESSION['user'];
    $sql_select_id_user = "select * from khachhang where TenDangNhap = '$name_user'";
    $result_select_id_user = mysqli_query($conn, $sql_select_id_user);
    if ($result_select_id_user) {
        $row_select_id_user = mysqli_fetch_array($result_select_id_user);
        $id_user = $row_select_id_user['ID_KhachHang'];

        //check mua hàng chưa - Only allow review if purchased and received (TrangThai = 3)
        $check_muahang = 0;
        $has_approved_return = false;
        $sql_check_muahang = "SELECT d.ID_DonHang, d.GhiChu 
                             FROM donhang d
                             INNER JOIN donhangchitiet dct ON d.ID_DonHang = dct.ID_DonHang
                             WHERE d.ID_KhachHang = '$id_user' 
                             AND dct.ID_SanPham = '$IDSanPham' 
                             AND d.TrangThai = 3";
        $result_check_muahang = mysqli_query($conn, $sql_check_muahang);
        $count_check_muahang = mysqli_num_rows($result_check_muahang);
        if ($count_check_muahang > 0) {
            // Check if any order has an approved return/refund for this product
            while ($order_row = mysqli_fetch_array($result_check_muahang)) {
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
                $check_muahang = 1;
            }
        }
        
        if ($check_muahang == 0) {
            // User hasn't purchased this product or has approved return - don't allow review
            if ($has_approved_return) {
                echo 'return_approved';
            } else {
                echo 'not_verified_purchase';
            }
            return;
        }
    } else {
        echo 'error';
        return;
    }

    //thêm comment
    $sql_insert_comment = "INSERT INTO `binhluan` (`ID_BinhLuan`, `ID_KhachHang`, `ID_SanPham`, `NoiDungBinhLuan`, `DaMuaHang`) VALUES (NULL, '$id_user', '$IDSanPham', '$NoiDungBinhLuan', '$check_muahang');";

    $result_insert_comment = mysqli_query($conn, $sql_insert_comment);

    if ($result_insert_comment) {
        //lấy id commnet mới tạo
        $id_comment = mysqli_insert_id($conn);

        $sql_select_comment = "select * from binhluan where ID_BinhLuan = '$id_comment'";
        $result_select_comment = mysqli_query($conn, $sql_select_comment);

        if ($result_select_comment) {
            $row_select_comment = mysqli_fetch_array($result_select_comment);

            $id_user_comment = $row_select_comment['ID_KhachHang'];
            $NoiDungBinhLuan_comment = $row_select_comment['NoiDungBinhLuan'];

            //lấy họ tên ng comment
            $sql_hoten_comment = "select * from khachhang where ID_KhachHang = '$id_user_comment'";
            $result_hoten_comment = mysqli_query($conn, $sql_hoten_comment);
            $row_hoten_comment = mysqli_fetch_array($result_hoten_comment);
            $hoten_user_comment = $row_hoten_comment['HoVaTen'];
            
            if ($row_select_comment['DaMuaHang'] == 1) {
                echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">
                        <h6 class="card-title">'.$hoten_user_comment.' <span class="text-success"><i class="fa-solid fa-circle-check"></i> Đã mua hàng</span></h6>
                        <p class="card-text mx-2 fs-6">'.$NoiDungBinhLuan_comment.'</p>
                    </div>';
            }
            else
            {
                echo '<div class="card-body border-0 rounded-4 mx-3 mb-3 bg-light">
                        <h6 class="card-title">'.$hoten_user_comment.'</h6>
                        <p class="card-text mx-2 fs-6">'.$NoiDungBinhLuan_comment.'</p>
                    </div>';
            }
        }
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
