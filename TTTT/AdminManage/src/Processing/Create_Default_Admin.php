<?php
/**
 * Create Default Admin Account
 * Run this once to create the default admin account
 * Username: admin
 * Password: 123admin
 */

include("../DB_Connect.php");

// Ensure Role column exists
@mysqli_query($conn, "ALTER TABLE `khachhang` ADD `Role` ENUM('customer','shipper','manager','admin') NOT NULL DEFAULT 'customer' AFTER `DiaChi`");

// Check if admin account already exists
$check_admin = mysqli_query($conn, "SELECT * FROM khachhang WHERE TenDangNhap = 'admin'");
if (mysqli_num_rows($check_admin) > 0) {
    // Update existing admin account
    $update_sql = "UPDATE khachhang SET 
                    MatKhau = '123admin',
                    Role = 'admin',
                    HoVaTen = 'Administrator',
                    Email = 'admin@moshop.com',
                    SDT = '0000000000',
                    DiaChi = 'Admin Office'
                    WHERE TenDangNhap = 'admin'";
    mysqli_query($conn, $update_sql);
    echo "Default admin account updated successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: 123admin<br>";
} else {
    // Create new admin account
    $insert_sql = "INSERT INTO khachhang (TenDangNhap, MatKhau, HoVaTen, Email, SDT, DiaChi, Role) 
                    VALUES ('admin', '123admin', 'Administrator', 'admin@moshop.com', '0000000000', 'Admin Office', 'admin')";
    if (mysqli_query($conn, $insert_sql)) {
        echo "Default admin account created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: 123admin<br>";
    } else {
        echo "Error creating admin account: " . mysqli_error($conn);
    }
}

echo "<br><a href='../View/Admin_Login.php'>Go to Admin Login</a>";
?>

