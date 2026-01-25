<?php
// Thông tin kết nối CSDL
$servername = "localhost";
$username = "root";
$password = "";
$database = "webmipham1";

// Tạo kết nối đến CSDL
$conn = new mysqli($servername, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối đến CSDL thất bại: " . $conn->connect_error);
}

// ====== BỔ SUNG HÀM db() TƯƠNG THÍCH ======
if (!function_exists('db')) {
    function db() {
        global $conn;
        return $conn;
    }
}
