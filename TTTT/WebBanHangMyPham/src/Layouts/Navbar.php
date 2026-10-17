<!-- nav -->
<section class="mb-3 py-2 bg-white">
    <nav class="navbar navbar-expand-md">
        <div class="container d-flex justify-content-center">
            <div class="row w-100" id="nav-respon-btn-search">
                <div class="col-9 nav-search d-none" id="nav-respon-search">
                    <form class="input-group" method="get" action="View_Product.php" role="search">
                        <input id="timkiemsanpham_mini" name="search_like" type="search" class="form-control search-header"
                            value="<?php echo htmlspecialchars(trim((string) ($_GET['search_like'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Tìm sản phẩm" aria-label="Tìm sản phẩm" maxlength="100" required>
                        <button class="input-group-text icon-search-header" type="submit" aria-label="Tìm kiếm">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </form>
                </div>
                <!-- toggle btn -->
                <div class="col-3 d-flex justify-content-end">
                    <button class="navbar-toggler shadow-none border-0" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </div>

            <!-- slide bar -->
            <div class="slidebar offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                aria-labelledby="offcanvasNavbarLabel">
                <!-- slidebar header  -->
                <div class="offcanvas-header">
                    <div class="d-flex align-items-center justify-content-center">
                        <img src="/TTTT/imgs/logo/lalogo.png" alt="" class="img-fluid" style="width: 60px;">
                        <h5>Mơ Shop</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <!-- slidebar body -->
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-center align-items-center fs-5 flex-grow-1 pe-3">
                        <li class="nav-item mx-4">
                            <a class="nav-link fw-medium" style="color: #2D9596;" aria-current="page"
                                href="../View/index.php">TRANG CHỦ</a>
                        </li>
                        <li class="nav-item mx-4 dropdown">

                            <a class="nav-link dropdown-toggle fw-medium" style="color: #2D9596;" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                SẢN PHẨM
                            </a>
                            <ul class="dropdown-menu border-0 rounded-3 shadow">
                                <?php
                                $sql_nav_category = "select * from danhmuc";
                                $result_nav_category = mysqli_query($conn, $sql_nav_category);
                                while ($row_nav_category = mysqli_fetch_array($result_nav_category)) {
                                    echo '<li><a class="dropdown-item" style="color: #2D9596;" href="/TTTT/WebBanHangMyPham/src/View/View_Product.php?category_id=' . $row_nav_category['IDDanhMuc'] . '">' . $row_nav_category['TenDanhMuc'] . '</a></li>';
                                }
                                ?>
                            </ul>
                        </li>
                        <li class="nav-item mx-4">
                            <a class="nav-link fw-medium" style="color: #2D9596;"
                                href="../View/View_Product.php?hot_product=1">HOT</a>
                        </li>
                        <li class="nav-item mx-4">
                            <a class="nav-link fw-medium" style="color: #2D9596;" href="../View/View_News_Posts.php">TIN
                                TỨC</a>
                        </li>
                        <li class="nav-item mx-4">
                            <a class="nav-link fw-medium" style="color: #2D9596;" href="../View/View_info.php">GIỚI
                                THIỆU</a>
                        </li>
                        <li class="nav-item mx-4">
                            <a class="nav-link fw-medium" style="color: #2D9596;" href="../View/View_contact.php">LIÊN
                                HỆ</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</section>
