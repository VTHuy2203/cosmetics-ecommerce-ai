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
                        <h1 class="mb-4 text-center" style="color: #2D9596;">Chính Sách Giải Quyết Khiếu Nại</h1>
                        
                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-comments me-2"></i>Nguyên Tắc Giải Quyết Khiếu Nại</h3>
                            <p class="fs-6">Mơ Shop cam kết giải quyết mọi khiếu nại của khách hàng một cách nhanh chóng, công bằng và minh bạch. Chúng tôi luôn đặt quyền lợi của khách hàng lên hàng đầu và sẵn sàng lắng nghe mọi ý kiến đóng góp.</p>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-list-check me-2"></i>Các Trường Hợp Khiếu Nại</h3>
                            
                            <h5 class="mt-4 mb-3">1. Khiếu Nại Về Sản Phẩm</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Sản phẩm không đúng với mô tả trên website</li>
                                <li class="mb-2">Sản phẩm bị hư hỏng, lỗi kỹ thuật</li>
                                <li class="mb-2">Sản phẩm hết hạn sử dụng hoặc gần hết hạn</li>
                                <li class="mb-2">Sản phẩm không phải hàng chính hãng</li>
                                <li class="mb-2">Thiếu sản phẩm trong đơn hàng</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Khiếu Nại Về Dịch Vụ</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Thái độ phục vụ không tốt của nhân viên</li>
                                <li class="mb-2">Thời gian giao hàng chậm trễ</li>
                                <li class="mb-2">Đóng gói sản phẩm không cẩn thận</li>
                                <li class="mb-2">Thông tin đơn hàng không chính xác</li>
                            </ul>

                            <h5 class="mt-4 mb-3">3. Khiếu Nại Về Thanh Toán</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Phí vận chuyển không đúng</li>
                                <li class="mb-2">Tính toán sai tổng tiền đơn hàng</li>
                                <li class="mb-2">Vấn đề về hoàn tiền</li>
                                <li class="mb-2">Lỗi trong quá trình thanh toán</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-route me-2"></i>Quy Trình Giải Quyết Khiếu Nại</h3>
                            
                            <h5 class="mt-4 mb-3">Bước 1: Tiếp Nhận Khiếu Nại</h5>
                            <p class="fs-6 mb-3">Khách hàng có thể gửi khiếu nại qua các kênh sau:</p>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Hotline:</strong> 0559110474 (Giờ làm việc: 8:00 - 20:00 hàng ngày)</li>
                                <li class="mb-2"><strong>Email:</strong> moshop.danang@gmail.com</li>
                                <li class="mb-2"><strong>Trực tiếp tại cửa hàng:</strong> Thôn Tân Lập, xã Ealy, huyện Sông Hinh, tỉnh Phú Yên</li>
                                <li class="mb-2"><strong>Qua website:</strong> Sử dụng form liên hệ trên trang web</li>
                            </ul>

                            <h5 class="mt-4 mb-3">Bước 2: Xác Nhận Và Phân Loại</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Mơ Shop sẽ xác nhận tiếp nhận khiếu nại trong vòng <strong>24 giờ</strong> kể từ khi nhận được.</li>
                                <li class="mb-2">Phân loại mức độ nghiêm trọng và ưu tiên xử lý.</li>
                                <li class="mb-2">Gán mã số khiếu nại để khách hàng dễ dàng theo dõi.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">Bước 3: Điều Tra Và Xử Lý</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Thời gian điều tra: <strong>3-5 ngày làm việc</strong> tùy theo mức độ phức tạp.</li>
                                <li class="mb-2">Thu thập thông tin, chứng cứ từ cả hai phía.</li>
                                <li class="mb-2">Đưa ra phương án giải quyết phù hợp và công bằng.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">Bước 4: Thông Báo Kết Quả</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Thông báo kết quả giải quyết cho khách hàng qua email hoặc điện thoại.</li>
                                <li class="mb-2">Thực hiện các biện pháp khắc phục đã cam kết.</li>
                                <li class="mb-2">Lưu trữ hồ sơ khiếu nại để cải thiện dịch vụ.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-handshake me-2"></i>Cam Kết Giải Quyết</h3>
                            
                            <h5 class="mt-4 mb-3">1. Thời Gian Xử Lý</h5>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Khiếu nại đơn giản:</strong> Giải quyết trong vòng 24-48 giờ</li>
                                <li class="mb-2"><strong>Khiếu nại phức tạp:</strong> Giải quyết trong vòng 3-5 ngày làm việc</li>
                                <li class="mb-2"><strong>Khiếu nại nghiêm trọng:</strong> Giải quyết trong vòng 7-10 ngày làm việc</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Các Biện Pháp Khắc Phục</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Đổi trả sản phẩm lỗi bằng sản phẩm mới</li>
                                <li class="mb-2">Hoàn tiền 100% nếu khách hàng yêu cầu</li>
                                <li class="mb-2">Bồi thường thiệt hại (nếu có)</li>
                                <li class="mb-2">Giảm giá hoặc tặng voucher cho lần mua tiếp theo</li>
                                <li class="mb-2">Xin lỗi chính thức và cam kết cải thiện</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-info-circle me-2"></i>Thông Tin Cần Cung Cấp Khi Khiếu Nại</h3>
                            <ul class="fs-6">
                                <li class="mb-2">Mã đơn hàng (nếu có)</li>
                                <li class="mb-2">Tên sản phẩm và số lượng</li>
                                <li class="mb-2">Mô tả chi tiết vấn đề gặp phải</li>
                                <li class="mb-2">Hình ảnh minh chứng (nếu có)</li>
                                <li class="mb-2">Thông tin liên hệ: Họ tên, số điện thoại, email</li>
                                <li class="mb-2">Thời gian và địa điểm xảy ra vấn đề</li>
                            </ul>
                        </div>

                        <div class="alert alert-success mt-4">
                            <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Cam Kết Của Mơ Shop</h5>
                            <p class="mb-0">Chúng tôi cam kết:</p>
                            <ul class="mb-0 mt-2">
                                <li>Lắng nghe và tôn trọng mọi ý kiến của khách hàng</li>
                                <li>Giải quyết khiếu nại một cách công bằng, minh bạch</li>
                                <li>Rút kinh nghiệm và cải thiện dịch vụ liên tục</li>
                                <li>Bảo vệ quyền lợi chính đáng của khách hàng</li>
                            </ul>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h5 class="alert-heading"><i class="fas fa-headset me-2"></i>Liên Hệ Khiếu Nại</h5>
                            <ul class="mb-0">
                                <li><i class="fas fa-phone me-2"></i>Hotline: 0559110474 (8:00 - 20:00 hàng ngày)</li>
                                <li><i class="fas fa-envelope me-2"></i>Email: support@moshop.com</li>
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

