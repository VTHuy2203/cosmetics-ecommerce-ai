<?php
include("../Layouts/Head.php");
include("../DB_Connect.php");

if (isset($_POST['tenDangNhap']) && isset($_POST['matKhau']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql_login = "select * from KhachHang where TenDangNhap = '" . $_POST['tenDangNhap'] . "' and MatKhau = '" . $_POST['matKhau'] . "'";

    $result = mysqli_query($conn, $sql_login);

    $row_login = mysqli_fetch_array($result);

    $count = mysqli_num_rows($result);

    if ($count > 0) {
        $_SESSION['user'] = $row_login['TenDangNhap'];
        $_SESSION['IDNguoiDung'] = $row_login['ID_KhachHang'];
        
        // Check role and redirect accordingly - check if Role column exists first
        $checkCol = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'Role'");
        $colExists = false;
        if ($checkCol) {
            $colRow = mysqli_fetch_assoc($checkCol);
            $colExists = intval($colRow['c']) > 0;
        }
        if (!$colExists) {
            @mysqli_query($conn, "ALTER TABLE `khachhang` ADD `Role` ENUM('customer','shipper','manager','admin') NOT NULL DEFAULT 'customer' AFTER `DiaChi`");
        }
        
        // Check if user is shipper and if approved
        $role = isset($row_login['Role']) ? $row_login['Role'] : 'customer';
        
        // If shipper, check if approved
        if ($role === 'shipper') {
            @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `shipper_requests` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `license` VARCHAR(50) NOT NULL,
                `vehicle` VARCHAR(50) NOT NULL,
                `note` TEXT NULL,
                `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $shipper_check = mysqli_query($conn, "SELECT status FROM shipper_requests WHERE user_id = " . $row_login['ID_KhachHang'] . " ORDER BY created_at DESC LIMIT 1");
            $shipper_row = mysqli_fetch_array($shipper_check);
            
            if (!$shipper_row || $shipper_row['status'] !== 'approved') {
                echo "<script>alert('Tài khoản shipper của bạn chưa được phê duyệt. Vui lòng đợi Admin phê duyệt.');</script>";
                exit();
            }
        }
        
        $_SESSION['user_role'] = $role;
        
        // CRITICAL: UI Login should NEVER set admin session variables or redirect to admin panel
        // Admin/Manager must login separately via Admin_Login.php to access admin panel
        // UI Login is ONLY for customer UI (including customers, shippers, staff browsing as customers)
        // Staff accessing admin features must use Admin_Login.php with separate admin session
        
        // All users logging in via UI go to customer homepage
        // This includes: customers, shippers, staff, and even admin/manager browsing as customers
        header("Location:index.php");
        exit();
    } else {
        echo "<script>alert('Đăng nhập thất bại.');</script>";
    }
}
?>

<body>
    <?php
    include("../Layouts/Header_View.php");
    ?>
    <section class="vh-100 bg-light">
        <div class="container-fluid pt-5">
            <div class="row d-flex justify-content-center pt-5">
                <div class="col col-xl-10">
                    <div class="card" style="border-radius: 1rem;">
                        <div class="row g-0">
                            <div class="col-md-6 col-lg-5 d-none d-md-block">
                                <img src="/TTTT/imgs/logo/log1.png" alt="login form" class="img-fluid"
                                    style="border-radius: 1rem 0 0 1rem; height: 100%; ; object-fit: cover;" />
                            </div>
                            <div class="col-md-6 col-lg-7 d-flex align-items-center">
                                <div class="card-body p-4 p-lg-5 text-black">

                                    <div class="d-flex align-items-center mb-5 pb-0">
                                        <img src="/TTTT/imgs/logo/Mơ_shop__3_-removebg-preview.png"
                                            class="img-fluid mx-auto" style="height: 50px;" />
                                    </div>

                                    <h5 class="fw-normal mb-3 pb-3" style="letter-spacing: 1px;">Đăng nhập vào tài khoản
                                        của bạn</h5>
                                    <form method="POST">
                                        <div class="form-outline mb-4">
                                            <label class="form-label" for="tenDangNhap">Tên đăng nhập</label>
                                            <input type="text" id="tenDangNhap" name="tenDangNhap"
                                                class="form-control form-control-lg" />
                                        </div>

                                        <div class="form-outline mb-4">
                                            <label class="form-label" for="matKhau">Mật khẩu</label>
                                            <input type="password" id="matKhau" name="matKhau"
                                                class="form-control form-control-lg" />
                                        </div>

                                        <div class="pt-1 mb-4">
                                            <button class="btn btn-dark btn-lg btn-block" type="submit">Đăng
                                                nhập</button>
                                        </div>
                                    </form>
                                    <p class="mb-5 pb-lg-2">Bạn chưa có tài khoản? <a href="Register.php"
                                            class="text-decoration-underline">Đăng kí tại đây</a></p>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>