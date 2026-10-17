<?php
$topbarAdminUsername = (string) ($_SESSION['admin_user'] ?? 'Admin');
$topbarAdminAvatar = '/TTTT/AdminManage/img/undraw_profile.svg';
$topbarUnreadNotifications = 0;
if (isset($conn) && $conn instanceof mysqli && !empty($_SESSION['admin_id'])) {
    try {
        $topbarAdminId = (int) $_SESSION['admin_id'];
        $topbarStatement = $conn->prepare(
            "SELECT TenDangNhap, AnhDaiDien FROM khachhang
             WHERE ID_KhachHang = ? AND Role IN ('admin', 'manager', 'employee') LIMIT 1"
        );
        $topbarStatement->bind_param('i', $topbarAdminId);
        $topbarStatement->execute();
        $topbarAccount = $topbarStatement->get_result()->fetch_assoc();
        $topbarStatement->close();
        if ($topbarAccount) {
            $topbarAdminUsername = (string) $topbarAccount['TenDangNhap'];
            $candidateAvatar = (string) ($topbarAccount['AnhDaiDien'] ?? '');
            if (str_starts_with($candidateAvatar, '/TTTT/')) {
                $topbarAdminAvatar = $candidateAvatar;
            }
        }
        if (in_array((string) ($_SESSION['admin_role'] ?? ''), ['admin', 'manager'], true)) {
            $notificationCount = $conn->query('SELECT COUNT(*) AS total FROM admin_notifications WHERE is_read = 0')->fetch_assoc();
            $topbarUnreadNotifications = (int) ($notificationCount['total'] ?? 0);
        }
    } catch (Throwable $topbarError) {
        error_log('Admin topbar profile failed: ' . $topbarError->getMessage());
    }
}
?>
<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

<!-- Sidebar Toggle (Topbar) -->
<button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" style="color: #2D9596;">
    <i class="fa fa-bars"></i>
</button>

<!-- Topbar Search -->
<form
    class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
    <div class="input-group">
        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
            aria-label="Search" aria-describedby="basic-addon2">
        <div class="input-group-append">
            <button class="btn" style="background-color: #2D9596;" type="button">
                <i class="fas fa-search fa-sm" style="color: white;"></i>
            </button>
        </div>
    </div>
</form>

<!-- Topbar Navbar -->
<ul class="navbar-nav ml-auto">

    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
    <li class="nav-item dropdown no-arrow d-sm-none">
        <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-search fa-fw"></i>
        </a>
        <!-- Dropdown - Messages -->
        <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
            aria-labelledby="searchDropdown">
            <form class="form-inline mr-auto w-100 navbar-search">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small"
                        placeholder="Search for..." aria-label="Search"
                        aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </li>

    <?php if (in_array((string) ($_SESSION['admin_role'] ?? ''), ['admin', 'manager'], true)): ?>
    <li class="nav-item no-arrow mx-1">
        <a class="nav-link position-relative" href="/TTTT/AdminManage/src/Notifications/Notification_Center.php" title="Trung tâm thông báo" aria-label="Trung tâm thông báo">
            <i class="fas fa-bell fa-fw"></i>
            <span id="admin-notification-count" class="badge badge-danger badge-counter" style="<?php echo $topbarUnreadNotifications > 0 ? '' : 'display:none;'; ?>">
                <?php echo $topbarUnreadNotifications > 99 ? '99+' : $topbarUnreadNotifications; ?>
            </span>
        </a>
    </li>
    <?php endif; ?>


    <!-- Nav Item - User Information -->
    <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($topbarAdminUsername, ENT_QUOTES, 'UTF-8'); ?></span>
            <img class="img-profile rounded-circle"
                src="<?php echo htmlspecialchars($topbarAdminAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                alt="Ảnh đại diện quản trị" style="object-fit: cover;">
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="userDropdown">
            <a class="dropdown-item" href="/TTTT/AdminManage/src/Users/Admin_Profile.php#profile">
                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                Hồ sơ
            </a>
            <a class="dropdown-item" href="/TTTT/AdminManage/src/Users/Admin_Profile.php#security">
                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                Bảo mật
            </a>
            <a class="dropdown-item" href="/TTTT/AdminManage/src/Users/Admin_Profile.php#activity">
                <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                Nhật ký hoạt động
            </a>
            <div class="dropdown-divider"></div>
            <form method="post" action="/TTTT/AdminManage/src/Processing/Admin_Logout.php" class="m-0">
                <?php echo csrf_field(); ?>
                <button type="submit" class="dropdown-item border-0 bg-transparent w-100 text-left">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Đăng xuất
                </button>
            </form>
        </div>
    </li>

</ul>

</nav>
<!-- End of Topbar -->
