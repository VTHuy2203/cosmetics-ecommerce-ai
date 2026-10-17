<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #2D9596;">

    <?php
    // CRITICAL: Use ONLY admin session variables (completely ignore UI session)
    // Determine default link based on admin role via Admin_Login.php
    $admin_role = $_SESSION['admin_role'] ?? null;
    $is_staff_via_admin = ($admin_role === 'employee');
    $is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');
    
    $default_link = '../Oders/All_Oders.php';
    if ($is_staff_via_admin) {
        $default_link = '../Users/Staff_Dashboard.php';
    }
    ?>
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $default_link; ?>">
        <div class="sidebar-brand-icon ">
            <img src="/TTTT/imgs/logo/logoadmin.png" alt="" style="width: 100px;">
        </div>
        <div class="sidebar-brand-text mx-3">MƠ SHOP</div>
    </a>


    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Quản Lý
    </div>

    <?php
    // CRITICAL: Use ONLY admin session variables (completely ignore UI session)
    // Staff access is determined by admin_role via Admin_Login.php
    $admin_role = $_SESSION['admin_role'] ?? null;
    $is_staff_via_admin = ($admin_role === 'employee');
    $is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');
    
    // If staff (via admin login), show staff dashboard
    if ($is_staff_via_admin):
    ?>
        <!-- Nav Item - Staff Dashboard -->
        <li class="nav-item">
            <a class="nav-link" href="../Users/Staff_Dashboard.php">
                <i class="fas fa-fw fa-dashboard"></i>
                <span>Bảng Điều Khiển</span>
            </a>
        </li>
        <!-- Nav Item - Order Management (Staff) -->
        <li class="nav-item">
            <a class="nav-link" href="../Oders/Staff_Order_Management.php">
                <i class="fas fa-fw fa-clipboard-list"></i>
                <span>Quản Lý Đơn Hàng</span>
            </a>
        </li>
        <!-- Nav Item - Orders (Staff) - Pending Approval -->
        <li class="nav-item">
            <a class="nav-link" href="../Oders/Approve_Oders.php">
                <i class="fas fa-fw fa-shopping-cart"></i>
                <span>Đơn Hàng Cần Duyệt</span>
            </a>
        </li>
        <!-- Nav Item - Canceled Orders (Staff) -->
        <li class="nav-item">
            <a class="nav-link" href="../Oders/Staff_Canceled_Orders.php">
                <i class="fas fa-fw fa-times-circle"></i>
                <span>Đơn Hàng Đã Hủy</span>
            </a>
        </li>
        <!-- Nav Item - Return Orders (Staff) -->
        <li class="nav-item">
            <a class="nav-link" href="../Oders/Staff_Return_Refund_Requests.php">
                <i class="fas fa-fw fa-undo"></i>
                <span>Đơn Hàng Hoàn Trả</span>
            </a>
        </li>
        <!-- Nav Item - Khách Hàng (Staff) -->
        <li class="nav-item">
            <a class="nav-link" href="../Users/Manage_Users.php">
                <i class="fas fa-fw fa-users"></i>
                <span>Khách Hàng</span>
            </a>
        </li>
        <!-- Nav Item - Chat Messages (Staff) -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapse-Chatbot"
                aria-expanded="true" aria-controls="collapse-Chatbot">
                <i class="fas fa-fw fa-comments"></i>
                <span>Tin Nhắn</span>
            </a>
            <div id="collapse-Chatbot" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Tin Nhắn</h6>
                    <a class="collapse-item" href="../Users/Admin_Customer_Analysis.php">Phân Tích Khách Hàng</a>
                    <a class="collapse-item" href="../Users/Admin_Guest_Customers.php">Khách Hàng Vãng Lai</a>
                </div>
            </div>
        </li>
    <?php
    // Full admin menu for admin/manager
    else:
    ?>

    <!-- Nav Item - Đơn Hàng -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities"
            aria-expanded="true" aria-controls="collapseUtilities">
            <i class="fas fa-fw fa-cog"></i>
            <span>Đơn Hàng</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Đơn Hàng</h6>
                <a class="collapse-item" href="../Oders/All_Oders.php">Danh Sách Đơn Hàng</a>
                <a class="collapse-item" href="../Oders/Approve_Oders.php">Đơn Hàng Cần Duyệt</a>
                <a class="collapse-item" href="../Oders/Prepare_Oders.php">Đơn Hàng Đang Chuẩn bị</a>
                <a class="collapse-item" href="../Oders/Shipping_Oders.php">Đơn Hàng Đang Giao</a>
                <a class="collapse-item" href="../Oders/Success_Oders.php">Đơn Hàng Thành Công</a>
                <a class="collapse-item" href="../Oders/Admin_Return_Cancellation_Requests.php">Yêu Cầu Hoàn Trả / Hủy Đơn</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Sản Phẩm -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true"
            aria-controls="collapsePages">
            <i class="fas fa-fw fa-cog"></i>
            <span>Sản Phẩm</span>
        </a>
        <div id="collapsePages" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Sản Phẩm</h6>
                <a class="collapse-item" href="../Products/Add_Product.php">Thêm Sản Phẩm</a>
                <a class="collapse-item" href="../Products/Manage_Product.php">Danh Sách Sản Phẩm</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Thương Hiêu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseThuongHieu"
            aria-expanded="true" aria-controls="collapseThuongHieu">
            <i class="fas fa-fw fa-cog"></i>
            <span>Thương Hiệu</span>
        </a>
        <div id="collapseThuongHieu" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Thương Hiệu</h6>
                <a class="collapse-item" href="../Brands/Add_Brand.php">Thêm Thương Hiệu</a>
                <a class="collapse-item" href="../Brands/Manage_Brand.php">Quản Lý Thương Hiệu</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Danh Mục -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapse-Danh-Muc"
            aria-expanded="true" aria-controls="collapse-Danh-Muc">
            <i class="fas fa-fw fa-cog"></i>
            <span>Danh Mục</span>
        </a>
        <div id="collapse-Danh-Muc" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh Mục</h6>
                <a class="collapse-item" href="../Categorys/Add_Category.php">Thêm Danh Mục</a>
                <a class="collapse-item" href="../Categorys/Manage_Category.php">Quản Lý Danh Mục</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Tin Tức Bài viết -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapse-TinTuc-BaiViet"
            aria-expanded="true" aria-controls="collapse-TinTuc-BaiViet">
            <i class="fas fa-fw fa-cog"></i>
            <span>Tin Tức & Bài Viết</span>
        </a>
        <div id="collapse-TinTuc-BaiViet" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Tin Tức & Bài Viết</h6>
                <a class="collapse-item" href="../News_Posts/Add_News_Posts.php">Thêm tin tức & bài viết</a>
                <a class="collapse-item" href="../News_Posts/Manage_News_Posts.php">Quản Lý tin tức & bài viết</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - tài khoản -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapse-Khach-Hang"
            aria-expanded="true" aria-controls="collapse-Khach-Hang">
            <i class="fas fa-fw fa-cog"></i>
            <span>Khách Hàng</span>
        </a>
        <div id="collapse-Khach-Hang" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Khách Hàng</h6>
                <a class="collapse-item" href="../Users/Manage_Users.php">Quản Lý khách hàng</a>
                <a class="collapse-item" href="../Users/Admin_Shipper_Registration.php">Đăng Ký Shipper</a>
                <a class="collapse-item" href="../Users/Admin_Shipper_Statistics.php">Thống Kê Shipper</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - bình luận -->
    <li class="nav-item">
        <a class="nav-link" href="../Notifications/Notification_Center.php">
            <i class="fas fa-fw fa-bell"></i>
            <span>Trung Tâm Thông Báo</span>
        </a>
    </li>

    <!-- Nav Item - bình luận -->
    <li class="nav-item">
        <a class="nav-link" href="../Marketing/Manage_Marketing.php">
            <i class="fas fa-fw fa-bullhorn"></i>
            <span>Voucher & Thông Báo</span>
        </a>
    </li>

    <!-- Nav Item - bình luận -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapse-Binh-Luan"
            aria-expanded="true" aria-controls="collapse-Binh-Luan">
            <i class="fas fa-fw fa-cog"></i>
            <span>Bình Luận</span>
        </a>
        <div id="collapse-Binh-Luan" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Bình Luận</h6>
                <a class="collapse-item" href="../Comments/Manage_Comments.php">Quản Lý Bình Luận</a>
            </div>
        </div>
    </li>

    <?php 
    endif; // Close the if/else block for role-based menu
    // Only show chatbot menu for admin/manager (not staff)
    if ($is_admin_or_manager): ?>
    <!-- Nav Item - Chatbot -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapse-Chatbot"
            aria-expanded="true" aria-controls="collapse-Chatbot">
            <i class="fas fa-fw fa-comments"></i>
            <span>Chatbot</span>
        </a>
        <div id="collapse-Chatbot" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Chatbot</h6>
                <!-- <a class="collapse-item" href="../Users/Admin_Messages.php">Quản Lý Tin Nhắn</a>
                <a class="collapse-item" href="../Users/Admin_Bookings.php">Lịch Tư Vấn</a> -->
                <a class="collapse-item" href="../Users/Admin_Customer_Analysis.php">Phân Tích Khách Hàng</a>
                <a class="collapse-item" href="../Users/Admin_Guest_Customers.php">Khách Hàng Vãng Lai</a>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
    
    <!-- Logout -->
    <hr class="sidebar-divider d-none d-md-block">
    <div class="sidebar-heading">
        Tài khoản
    </div>
    <?php if ($is_admin_or_manager): ?>
    <li class="nav-item"><a class="nav-link" href="../Oders/Shipping_Settings.php"><i class="fas fa-fw fa-shipping-fast"></i><span>Phí vận chuyển</span></a></li>
    <li class="nav-item">
        <a class="nav-link" href="../Users/Admin_Profile.php">
            <i class="fas fa-fw fa-user-cog"></i>
            <span>Quản lý tài khoản</span>
        </a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
        <form method="post" action="../Processing/Admin_Logout.php" class="m-0">
            <?php echo csrf_field(); ?>
            <button type="submit" class="nav-link border-0 bg-transparent w-100 text-left">
                <i class="fas fa-fw fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </button>
        </form>
    </li>

</ul>
<!-- End of Sidebar -->
