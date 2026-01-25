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

// Set charset to utf8mb4 to support emoji and special characters
$conn->set_charset("utf8mb4");
