<div class="container-fluid bg-white">
    <div class="container py-3">
        <div class="row align-items-center">
            <div class="col-md-3" id="header-logo">
                <a href="../View/index.php"><img src="/TTTT/imgs/logo/Mơ_shop__3_-removebg-preview.png"
                        style="height : 100px;" class="img-fluid" alt="logo"></a>
            </div>
            <div class="col-md-7" id="header-search-inf">
                <div class="row" id="none-search-about">
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="input-group mt-3 mt-md-0 w-100">
                            <input id="timkiemsanpham" type="text" class="form-control search-header" placeholder="Tìm"
                                aria-describedby="basic-addon2">
                            <span onclick="TimKiem()" class="input-group-text icon-search-header" id="basic-addon2"><i
                                    class="fa-solid fa-magnifying-glass"></i></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row align-items-center">
                            <div class="col-3 d-flex justify-content-center">
                                <?php
                                // Fetch avatar path
                                $avatarPath = '';
                                if (isset($_SESSION['user'])) {
                                    $sqlAvatar = "select AnhDaiDien from khachhang where TenDangNhap='".$_SESSION['user']."'";
                                    $resAvatar = mysqli_query($conn, $sqlAvatar);
                                    if ($resAvatar) { $rowA = mysqli_fetch_array($resAvatar); $avatarPath = $rowA['AnhDaiDien']; }
                                }
                                $avatarSrc = $avatarPath ? $avatarPath : '/TTTT/imgs/logo/logologin.png';
                                echo '<a href="'. (isset($_SESSION['user']) ? '../View/Personal.php' : '../View/Login.php') .'" class="d-inline-block">';
                                echo '<img src="'.$avatarSrc.'" alt="avatar" class="rounded-circle" style="width:42px;height:42px;object-fit:cover;">';
                                echo '</a>';
                                ?>
                            </div>
                            <div class="col-9">
                                <?php
                                if (isset($_SESSION['user'])) {
                                    echo $_SESSION['user'];
                                } else {
                                    echo 'VUI LÒNG ';
                                }
                                ?>
                                <br>
                                <strong class="color-logo">
                                    <?php
                                    if (isset($_SESSION['user'])) {
                                        echo '<form method="POST" action="../View/index.php" class="m-0 p-0">                                                    
                                        <input type="hidden" name="KetThucSession" value = "1">                                                    
                                        <a class="color-logo" href="#" onclick="this.closest(\'form\').submit(); return false;">Đăng xuất</a>                                               
                                        </form>';
                                    } else {
                                        echo '<a class="color-logo" href="../View/Login.php">Đăng nhập</a>';
                                    }
                                    ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 d-flex justify-content-end" id="header-cart-like">
                <div class="row w-100 justify-content-end align-items-center">
                    <?php
                    // Show Orders button for shippers
                    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shipper') {
                        echo '<div class="col-auto">';
                        echo '<a type="button" class="btn position-relative color-logo" href="../View/Shipper_Orders.php" title="Đơn hàng">';
                        echo '<i class="fs-4 fa-solid fa-clipboard-list"></i>';
                        echo '</a>';
                        echo '</div>';
                    }
                    // Show Customer List button for employees (combined page with appointments)
                    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee') {
                        echo '<div class="col-auto">';
                        echo '<a type="button" class="btn position-relative color-logo" href="../View/Employee_Customer_List.php" title="Danh sách khách hàng & Lịch tư vấn">';
                        echo '<i class="fs-4 fa-solid fa-users"></i>';
                        echo '</a>';
                        echo '</div>';
                    }
                    ?>
                    <?php
                    // Notification bell for logged-in customers
                    if (isset($_SESSION['user']) && isset($_SESSION['IDNguoiDung'])) {
                        $userId = $_SESSION['IDNguoiDung'];
                        echo '<div class="col-auto position-relative">';
                        echo '<button type="button" class="btn position-relative color-logo" id="notificationBell" onclick="toggleNotifications()" title="Thông báo">';
                        echo '<i class="fs-4 fa-solid fa-bell"></i>';
                        echo '<span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;font-size:10px;">0</span>';
                        echo '</button>';
                        echo '<div id="notificationDropdown" class="position-absolute bg-white shadow rounded" style="display:none;right:0;top:100%;width:320px;max-height:400px;overflow-y:auto;z-index:1050;border:1px solid #dee2e6;">';
                        echo '<div class="p-2 border-bottom d-flex justify-content-between align-items-center">';
                        echo '<strong>Thông báo</strong>';
                        echo '<button class="btn btn-sm btn-link" onclick="markAllRead()">Đánh dấu đã đọc</button>';
                        echo '</div>';
                        echo '<div id="notificationList" class="p-2"><div class="text-center text-muted py-3">Đang tải...</div></div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                    <div class="col-auto">
                        <a type="button" class="btn position-relative color-logo" href="Cart.php">
                            <i class="fs-4 fa-solid fa-cart-shopping"></i>
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="View_ListLike.php" type="button" class="btn position-relative color-logo">
                            <i class="fs-4 fa-regular fa-heart"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function TimKiem() {
        var key_search = document.getElementById("timkiemsanpham").value;

        if (key_search == "") {
            alert("Vui Lòng Nhập Từ Khóa!");
        } else {
            window.location.href = "View_Product.php?search_like=" + key_search;
        }
    }

    // Notification system
    const NOTIF_USER_ID = <?php echo isset($_SESSION['IDNguoiDung']) ? (int)$_SESSION['IDNguoiDung'] : 0; ?>;
    let notifDropdownOpen = false;

    function toggleNotifications() {
        const dropdown = document.getElementById('notificationDropdown');
        notifDropdownOpen = !notifDropdownOpen;
        dropdown.style.display = notifDropdownOpen ? 'block' : 'none';
        if (notifDropdownOpen) loadNotifications();
    }

    function loadNotifications() {
        if (!NOTIF_USER_ID) return;
        fetch('../Processing/Customer_Notifications.php?user_id=' + NOTIF_USER_ID)
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    updateNotifBadge(data.unread_count);
                    renderNotifications(data.notifications);
                    if (data.customer_status) {
                        updateCustomerStatus(data.customer_status);
                    }
                }
            }).catch(e => console.error('Notification error:', e));
    }

    function updateNotifBadge(count) {
        const badge = document.getElementById('notifBadge');
        if (badge) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    function renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;
        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-3">Không có thông báo</div>';
            return;
        }
        list.innerHTML = notifications.map(n => `
            <div class="p-2 border-bottom ${n.is_read ? '' : 'bg-light'}" onclick="markRead(${n.id})" style="cursor:pointer;">
                <div class="fw-bold small">${n.title}</div>
                <div class="small text-muted">${n.message}</div>
                <div class="text-end small text-muted">${new Date(n.created_at).toLocaleDateString('vi-VN')}</div>
            </div>
        `).join('');
    }

    function updateCustomerStatus(status) {
        // Could display customer status badge somewhere if needed
        if (status.customer_label === 'potential') {
            console.log('Customer is POTENTIAL with ' + status.discount_percent + '% discount');
        }
    }

    function markRead(notifId) {
        fetch('../Processing/Customer_Notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({notification_id: notifId})
        }).then(() => loadNotifications());
    }

    function markAllRead() {
        fetch('../Processing/Customer_Notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: NOTIF_USER_ID, mark_all_read: true})
        }).then(() => loadNotifications());
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        if (bell && dropdown && !bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
            notifDropdownOpen = false;
        }
    });

    // Load notifications on page load
    if (NOTIF_USER_ID) {
        setTimeout(loadNotifications, 1000);
    }
</script>