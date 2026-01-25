<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");
?>

<body class="bg-light">
    <?php include("../Layouts/Link.php"); ?>
    <?php include("../Layouts/Banner_Header.php"); ?>
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-5">
                        <h1 class="mb-4 text-center" style="color: #2D9596;">Hướng Dẫn Mua Hàng Và Chính Sách Vận Chuyển</h1>
                        
                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-shopping-cart me-2"></i>Hướng Dẫn Mua Hàng</h3>
                            
                            <h5 class="mt-4 mb-3">1. Các Bước Mua Hàng</h5>
                            <ol class="fs-6">
                                <li class="mb-2"><strong>Tìm kiếm sản phẩm:</strong> Sử dụng thanh tìm kiếm hoặc duyệt theo danh mục để tìm sản phẩm mỹ phẩm bạn cần.</li>
                                <li class="mb-2"><strong>Xem chi tiết sản phẩm:</strong> Click vào sản phẩm để xem thông tin chi tiết, hình ảnh, giá cả và đánh giá từ khách hàng.</li>
                                <li class="mb-2"><strong>Thêm vào giỏ hàng:</strong> Chọn số lượng và click "Thêm vào giỏ hàng" hoặc "Mua ngay".</li>
                                <li class="mb-2"><strong>Kiểm tra giỏ hàng:</strong> Xem lại các sản phẩm đã chọn, điều chỉnh số lượng nếu cần.</li>
                                <li class="mb-2"><strong>Thanh toán:</strong> Chọn phương thức thanh toán (COD, Chuyển khoản, hoặc Số dư tài khoản) và điền thông tin giao hàng.</li>
                                <li class="mb-2"><strong>Xác nhận đơn hàng:</strong> Kiểm tra lại thông tin và xác nhận đặt hàng.</li>
                            </ol>

                            <h5 class="mt-4 mb-3">2. Lưu Ý Khi Mua Hàng</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Kiểm tra kỹ thông tin sản phẩm, giá cả và số lượng trước khi đặt hàng.</li>
                                <li class="mb-2">Đảm bảo số lượng sản phẩm không vượt quá số lượng tồn kho.</li>
                                <li class="mb-2">Điền đầy đủ và chính xác thông tin giao hàng để tránh phát sinh lỗi.</li>
                                <li class="mb-2">Lưu mã đơn hàng để theo dõi tình trạng đơn hàng.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-truck me-2"></i>Chính Sách Vận Chuyển</h3>
                            
                            <h5 class="mt-4 mb-3">1. Phạm Vi Giao Hàng</h5>
                            <p class="fs-6">Mơ Shop hiện tại giao hàng trong phạm vi tỉnh Phú Yên và các tỉnh thành lân cận. Đối với khu vực xã Ealy và huyện Sông Hinh, chúng tôi ưu tiên giao hàng nhanh chóng.</p>

                            <h5 class="mt-4 mb-3">2. Thời Gian Giao Hàng</h5>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Khu vực nội thành:</strong> 1-2 ngày làm việc</li>
                                <li class="mb-2"><strong>Khu vực ngoại thành:</strong> 2-3 ngày làm việc</li>
                                <li class="mb-2"><strong>Các tỉnh thành khác:</strong> 3-5 ngày làm việc</li>
                                <li class="mb-2">Thời gian giao hàng được tính từ khi đơn hàng được xác nhận và thanh toán thành công.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">3. Phí Vận Chuyển</h5>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Giao hàng COD:</strong> Phí vận chuyển sẽ được thông báo khi đặt hàng, tùy thuộc vào khoảng cách và trọng lượng đơn hàng.</li>
                                <li class="mb-2"><strong>Miễn phí vận chuyển:</strong> Áp dụng cho đơn hàng có giá trị trên 500.000đ trong khu vực nội thành.</li>
                                <li class="mb-2">Phí vận chuyển sẽ được hiển thị rõ ràng trong quá trình thanh toán.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">4. Quy Trình Giao Hàng</h5>
                            <ol class="fs-6">
                                <li class="mb-2">Đơn hàng được xác nhận và chuyển sang trạng thái "Chuẩn bị".</li>
                                <li class="mb-2">Shipper nhận đơn và bắt đầu giao hàng (trạng thái "Đang giao").</li>
                                <li class="mb-2">Shipper giao hàng đến địa chỉ khách hàng đã cung cấp.</li>
                                <li class="mb-2">Khách hàng kiểm tra hàng hóa và thanh toán (nếu COD).</li>
                                <li class="mb-2">Shipper xác nhận giao hàng thành công (trạng thái "Giao thành công").</li>
                            </ol>

                            <h5 class="mt-4 mb-3">5. Lưu Ý Khi Nhận Hàng</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Kiểm tra kỹ hàng hóa trước khi thanh toán.</li>
                                <li class="mb-2">Đảm bảo sản phẩm còn nguyên vẹn, không bị hư hỏng.</li>
                                <li class="mb-2">Kiểm tra số lượng, chủng loại sản phẩm có đúng với đơn hàng.</li>
                                <li class="mb-2">Nếu có bất kỳ vấn đề nào, vui lòng từ chối nhận hàng và liên hệ ngay với chúng tôi.</li>
                            </ul>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Thông Tin Liên Hệ</h5>
                            <p class="mb-0">Nếu bạn có bất kỳ thắc mắc nào về quá trình mua hàng hoặc vận chuyển, vui lòng liên hệ với chúng tôi:</p>
                            <ul class="mb-0 mt-2">
                                <li><i class="fas fa-phone me-2"></i>Hotline: 0559110474</li>
                                <li><i class="fas fa-envelope me-2"></i>Email: moshop.danang@gmail.com</li>
                                <li><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ: Thôn Tân Lập, xã Ealy, huyện Sông Hinh, tỉnh Phú Yên</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../Layouts/Footer.php"); ?>
</body>
</html>

