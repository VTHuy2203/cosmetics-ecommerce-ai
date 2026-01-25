<?php
include("../DB_Connect.php");
session_start();

// Create contact_messages table if not exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `contact_messages` (
    `ID_LienHe` INT AUTO_INCREMENT PRIMARY KEY,
    `HoTen` VARCHAR(255) NOT NULL,
    `Email` VARCHAR(255) NOT NULL,
    `SoDienThoai` VARCHAR(20) NULL,
    `NoiDung` TEXT NOT NULL,
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Chưa xử lý, 1: Đã xử lý',
    `ThoiGianTao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trangthai (`TrangThai`),
    INDEX idx_thoigian (`ThoiGianTao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ho_ten']) && isset($_POST['email']) && isset($_POST['noi_dung'])) {
    $ho_ten = mysqli_real_escape_string($conn, $_POST['ho_ten']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sdt = isset($_POST['sdt']) ? mysqli_real_escape_string($conn, $_POST['sdt']) : '';
    $noi_dung = mysqli_real_escape_string($conn, $_POST['noi_dung']);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../View/View_contact.php?error=1');
        exit();
    }
    
    // Insert contact message
    $sql = "INSERT INTO `contact_messages` (`HoTen`, `Email`, `SoDienThoai`, `NoiDung`) 
            VALUES ('$ho_ten', '$email', '$sdt', '$noi_dung')";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        header('Location: ../View/View_contact.php?success=1');
        exit();
    } else {
        header('Location: ../View/View_contact.php?error=1');
        exit();
    }
} else {
    header('Location: ../View/View_contact.php?error=1');
    exit();
}
?>

