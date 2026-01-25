<footer class="container-fluid pt-5 pb-4" style="background-color:#298b8d">
    <div class="container  text-md-left">
        <div class="row  text-md-left">
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3 text-white">
                <img class="h-100 w-100 rounded-circle" src="/TTTT/imgs/logo/Mơ shop (1).png" alt="">
            </div>

            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3 text-center text-md-start">
                <h5 class="text-uppercase mb-4 font-weight-bold text-white">Danh Mục</h5>
                <?php
                $sql_footer_danhmuc = "select * from danhmuc LIMIT 6";
                $result_footer_danhmuc = mysqli_query($conn, $sql_footer_danhmuc);

                while ($row_footer_danhmuc = mysqli_fetch_array($result_footer_danhmuc)) {
                    echo '<p>
                        <a href="../View/View_Product.php?category_id=' . $row_footer_danhmuc['IDDanhMuc'] . '" class=" text-white" style="text-decoration: none;">' . $row_footer_danhmuc['TenDanhMuc'] . '</a>
                    </p>';
                }
                ?>
            </div>

            <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mt-3 text-center text-md-start">
                <h5 class="text-uppercase mb-4 font-weight-bold text-white">Hỗ Trợ & Liên Hệ</h5>
                <p>
                    <a href="../View/Policy_Shipping.php" class="text-white" style="text-decoration: none;">Hướng dẫn mua hàng và chính sách vận chuyển</a>
                </p>
                <p>
                    <a href="../View/Policy_Return.php" class="text-white" style="text-decoration: none;">Chính sách đổi trả và bảo hành</a>
                </p>
                <p>
                    <a href="../View/Policy_Complaint.php" class="text-white" style="text-decoration: none;">Chính sách giải quyết khiếu nại</a>
                </p>
                <p>
                    <a href="../View/Policy_Regulations.php" class="text-white" style="text-decoration: none;">Quy chế hoạt động</a>
                </p>
            </div>

            <div class="col-md-4 col-lg-2 col-xl-2 mx-auto mt-3 text-center text-md-start">
                <h5 class="text-uppercase mb-4 font-weight-bold text-white">Thông Tin</h5>
                <p>
                    <a href="../View/View_info.php" class="text-white" style="text-decoration: none;">Giới thiệu về Mơ Shop</a>
                </p>
                <p>
                    <a href="../View/View_contact.php" class="text-white" style="text-decoration: none;">Liên hệ với chúng tôi</a>
                </p>
                <p>
                    <a href="../View/View_News_Posts.php" class="text-white" style="text-decoration: none;">Tin tức & Bài viết</a>
                </p>
                <p>
                    <a href="../View/View_Product.php" class="text-white" style="text-decoration: none;">Tất cả sản phẩm</a>
                </p>
            </div>
        </div>

        <hr class="mb-4">

        <div class="row align-items-center">
            <div class="col-md-7 col-lg-8 text-center text-white">
                <p>© 2025. CỬA HÀNG MĨ PHẨM TỪ THIÊN NHIÊN MƠ SHOP
                    <!-- <a href="" style="text-decoration: none;"><strong>jhfbvsfv</strong></a> -->
                </p>
            </div>

            <div class="col-md-5 col-lg-4">
                <div class="text-center text-md-right">
                    <ul class="list-unstyled list-inline">
                        <li class="list-inline-item">
                            <a href="https://www.facebook.com/Huyv.2203/" class="btn-floating btn-sm text-white"
                                style="font-size: 23px; text-decoration: none;"><i class="fab fa-facebook"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="https://www.instagram.com/tr.gh_y/" class="btn-floating btn-sm text-white"
                                style="font-size: 23px; text-decoration: none;"><i
                                    class="fa-brands fa-instagram"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="https://www.youtube.com/" class="btn-floating btn-sm text-white"
                                style="font-size: 23px; text-decoration: none;"><i class="fa-brands fa-youtube"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="https://www.tiktok.com/@huy_vuive" class="btn-floating btn-sm text-white"
                                style="font-size: 23px; text-decoration: none;"><i class="fa-brands fa-tiktok"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white"
                                style="font-size: 23px; text-decoration: none;"><i
                                    class="fa-brands fa-linkedin"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</footer>