<?php
include("../DB_Connect.php");
session_start();

if (isset($_SESSION['user']) && isset($_POST['IDSanPham']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $IDSanPham = $_POST['IDSanPham'];

    //id khach hang
    $name_user = $_SESSION['user'];
    $sql_select_id_user = "select * from khachhang where TenDangNhap = '$name_user'";
    $result_select_id_user = mysqli_query($conn, $sql_select_id_user);

    if ($result_select_id_user) {
        $row_select_id_user = mysqli_fetch_array($result_select_id_user);
        $id_user = $row_select_id_user['ID_KhachHang'];

        //check tồn tại
        $sql_check_like = "select * from danhsachyeuthich where ID_KhachHang = '$id_user' AND ID_SanPham = $IDSanPham";
        $result_check_like = mysqli_query($conn, $sql_check_like);
        if (mysqli_num_rows($result_check_like) > 0) {
            echo 'tontai';
            return;
        }

        $sql_insert_list_like = "INSERT INTO `danhsachyeuthich` (`ID_DSYeuThich`, `ID_KhachHang`, `ID_SanPham`) 
                                VALUES (NULL, '$id_user', '$IDSanPham');";
        $result_insert_list_like = mysqli_query($conn, $sql_insert_list_like);

        if ($result_insert_list_like) {
            echo 'success';
            return;
        } else {
            echo 'error';
            return;
        }
    } else {
        echo 'error';
        return;
    }
} else if (isset($_POST['del_like_product_id']) && isset($_SESSION['user']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //id khach hang
    $name_user = $_SESSION['user'];
    $sql_select_id_user = "select * from khachhang where TenDangNhap = '$name_user'";
    $result_select_id_user = mysqli_query($conn, $sql_select_id_user);

    if ($result_select_id_user) {
        $row_select_id_user = mysqli_fetch_array($result_select_id_user);
        $id_user = $row_select_id_user['ID_KhachHang'];

        $del_like_product_id = $_POST['del_like_product_id'];
        $sql_insert_list_like = "delete from danhsachyeuthich where ID_KhachHang = '$id_user' AND ID_SanPham = '$del_like_product_id'";
        $result_insert_list_like = mysqli_query($conn, $sql_insert_list_like);

        if ($result_insert_list_like) {
            echo 'xoathanhcong';
            return;
        } else {
            echo 'error';
            return;
        }
    }
    else {
        echo 'error';
        return;
    }
} else {
    echo 'chuadangnhap';
    return;
}
