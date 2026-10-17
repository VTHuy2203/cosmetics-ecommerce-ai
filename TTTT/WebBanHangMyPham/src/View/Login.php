<?php
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';

if (isset($_POST['tenDangNhap']) && isset($_POST['matKhau']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    require_turnstile('customer_login');
    $loginName = trim((string) $_POST['tenDangNhap']);
    $plainPassword = (string) $_POST['matKhau'];
    if (!auth_rate_limit('customer-login:' . strtolower($loginName))) {
        http_response_code(429);
        $row_login = null;
        echo "<script>alert('Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau.');</script>";
    } else {
        $stmt = $conn->prepare('SELECT * FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
        $stmt->bind_param('s', $loginName);
        $stmt->execute();
        $row_login = $stmt->get_result()->fetch_assoc();
    }

    // Guest checkout accounts are passwordless records for orders, never a login.
    if ($row_login && (int) ($row_login['is_guest'] ?? 0) !== 1 && password_matches_and_upgrade(
        $conn,
        (int) $row_login['ID_KhachHang'],
        $plainPassword,
        (string) $row_login['MatKhau']
    )) {
        auth_rate_limit_reset('customer-login:' . strtolower($loginName));
        // Guest ids used by this browser, captured before the session id is regenerated.
        $guestSessionIds = [
            (string) ($_SESSION['chat_current_session_id'] ?? ''),
            (string) ($_SESSION['chat_session_id'] ?? ''),
            (string) session_id(),
        ];
        session_regenerate_id(true);
        $_SESSION['user'] = $row_login['TenDangNhap'];
        $_SESSION['IDNguoiDung'] = $row_login['ID_KhachHang'];

        // Keep the guest chat, booking and browsing history on the account (fail-open).
        try {
            require_once dirname(__DIR__) . '/Services/ChatSessionService.php';
            require_once dirname(__DIR__) . '/Services/CustomerScoringService.php';
            $loginUserId = (int) $row_login['ID_KhachHang'];
            if (chat_session_claim_guest_history(app_pdo(), $loginUserId, $guestSessionIds) > 0) {
                CustomerScoringService::refreshQuietly($loginUserId, null);
            }
        } catch (Throwable $claimError) {
            error_log('Login guest history merge failed: ' . $claimError->getMessage());
        }
        
        // Check if user is shipper and if approved
        $role = isset($row_login['Role']) ? $row_login['Role'] : 'customer';
        
        // If shipper, check if approved
        if ($role === 'shipper') {
            $shipperStmt = $conn->prepare('SELECT status FROM shipper_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
            $userId = (int) $row_login['ID_KhachHang'];
            $shipperStmt->bind_param('i', $userId);
            $shipperStmt->execute();
            $shipper_row = $shipperStmt->get_result()->fetch_assoc();
            
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
include("../Layouts/Head.php");
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
                                        <?php echo csrf_field(); ?>
                                        <?php echo turnstile_widget('customer_login'); ?>
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
