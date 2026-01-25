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
                        <h1 class="mb-4 text-center" style="color: #2D9596;">Quy Chế Hoạt Động</h1>
                        
                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-building me-2"></i>Giới Thiệu</h3>
                            <p class="fs-6">Quy chế hoạt động này được xây dựng nhằm đảm bảo quyền lợi của khách hàng và tạo môi trường mua sắm minh bạch, công bằng. Mơ Shop cam kết tuân thủ và thực hiện đầy đủ các quy định trong quy chế này.</p>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-user-check me-2"></i>Quy Định Về Tài Khoản</h3>
                            
                            <h5 class="mt-4 mb-3">1. Đăng Ký Tài Khoản</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Khách hàng cần cung cấp thông tin chính xác, đầy đủ khi đăng ký tài khoản.</li>
                                <li class="mb-2">Mỗi số điện thoại chỉ được đăng ký một tài khoản.</li>
                                <li class="mb-2">Khách hàng chịu trách nhiệm bảo mật thông tin tài khoản của mình.</li>
                                <li class="mb-2">Mơ Shop không chịu trách nhiệm về các giao dịch phát sinh do lỗi bảo mật từ phía khách hàng.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Sử Dụng Tài Khoản</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Khách hàng không được chia sẻ, cho mượn tài khoản cho người khác.</li>
                                <li class="mb-2">Không được sử dụng tài khoản để thực hiện các hành vi gian lận, lừa đảo.</li>
                                <li class="mb-2">Mơ Shop có quyền khóa tài khoản nếu phát hiện vi phạm quy chế.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-shopping-bag me-2"></i>Quy Định Về Đặt Hàng</h3>
                            
                            <h5 class="mt-4 mb-3">1. Thông Tin Đặt Hàng</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Khách hàng cần cung cấp đầy đủ, chính xác thông tin: Họ tên, số điện thoại, địa chỉ giao hàng.</li>
                                <li class="mb-2">Thông tin sai lệch có thể dẫn đến giao hàng chậm trễ hoặc thất bại.</li>
                                <li class="mb-2">Mơ Shop không chịu trách nhiệm nếu đơn hàng không đến được do thông tin sai.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Xác Nhận Đơn Hàng</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Đơn hàng chỉ được xác nhận sau khi khách hàng hoàn tất thanh toán (đối với thanh toán trước) hoặc xác nhận đặt hàng (đối với COD).</li>
                                <li class="mb-2">Mơ Shop có quyền từ chối đơn hàng nếu sản phẩm hết hàng hoặc có vấn đề về giá cả.</li>
                                <li class="mb-2">Khách hàng sẽ được thông báo ngay khi đơn hàng bị từ chối.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">3. Hủy Đơn Hàng</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Khách hàng có thể hủy đơn hàng trước khi đơn hàng được xác nhận "Chuẩn bị".</li>
                                <li class="mb-2">Sau khi đơn hàng đã được xác nhận, việc hủy đơn cần liên hệ trực tiếp với Mơ Shop.</li>
                                <li class="mb-2">Đơn hàng đã giao không thể hủy, chỉ có thể thực hiện đổi trả theo chính sách.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-money-bill-wave me-2"></i>Quy Định Về Thanh Toán</h3>
                            
                            <h5 class="mt-4 mb-3">1. Phương Thức Thanh Toán</h5>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Thanh toán khi nhận hàng (COD):</strong> Khách hàng thanh toán khi nhận được hàng.</li>
                                <li class="mb-2"><strong>Chuyển khoản qua VietQR:</strong> Thanh toán trước khi giao hàng.</li>
                                <li class="mb-2"><strong>Thanh toán bằng số dư tài khoản:</strong> Sử dụng số dư trong tài khoản Mơ Shop.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Giá Cả Và Phí</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Giá sản phẩm được niêm yết trên website là giá cuối cùng (đã bao gồm VAT nếu có).</li>
                                <li class="mb-2">Phí vận chuyển sẽ được tính và hiển thị rõ ràng trong quá trình thanh toán.</li>
                                <li class="mb-2">Mơ Shop có quyền thay đổi giá sản phẩm nhưng cam kết không ảnh hưởng đến đơn hàng đã xác nhận.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-star me-2"></i>Quy Định Về Đánh Giá Và Bình Luận</h3>
                            
                            <h5 class="mt-4 mb-3">1. Quyền Đánh Giá</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Chỉ khách hàng đã mua và nhận hàng thành công mới được đánh giá sản phẩm.</li>
                                <li class="mb-2">Đánh giá phải trung thực, khách quan, không chứa nội dung phản cảm, xúc phạm.</li>
                                <li class="mb-2">Mơ Shop có quyền xóa đánh giá vi phạm quy định.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Quyền Bình Luận</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Chỉ khách hàng đã mua và nhận hàng thành công mới được bình luận về sản phẩm.</li>
                                <li class="mb-2">Bình luận phải liên quan đến sản phẩm, không spam, không quảng cáo.</li>
                                <li class="mb-2">Mơ Shop có quyền kiểm duyệt và xóa bình luận không phù hợp.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-ban me-2"></i>Hành Vi Bị Nghiêm Cấm</h3>
                            <ul class="fs-6">
                                <li class="mb-2">Đặt hàng giả, đặt hàng để test hệ thống mà không có ý định mua.</li>
                                <li class="mb-2">Sử dụng thông tin giả mạo khi đăng ký tài khoản hoặc đặt hàng.</li>
                                <li class="mb-2">Cố ý làm hỏng sản phẩm để đổi trả.</li>
                                <li class="mb-2">Đánh giá, bình luận sai sự thật nhằm mục đích xấu.</li>
                                <li class="mb-2">Sử dụng công cụ, phần mềm để can thiệp vào hệ thống website.</li>
                                <li class="mb-2">Mua hàng với mục đích bán lại không được phép.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-shield-alt me-2"></i>Bảo Mật Thông Tin</h3>
                            <ul class="fs-6">
                                <li class="mb-2">Mơ Shop cam kết bảo mật thông tin cá nhân của khách hàng theo quy định pháp luật.</li>
                                <li class="mb-2">Thông tin khách hàng chỉ được sử dụng cho mục đích phục vụ giao dịch và cải thiện dịch vụ.</li>
                                <li class="mb-2">Mơ Shop không chia sẻ thông tin khách hàng cho bên thứ ba mà không có sự đồng ý.</li>
                                <li class="mb-2">Khách hàng có quyền yêu cầu xóa hoặc cập nhật thông tin cá nhân của mình.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-gavel me-2"></i>Giải Quyết Tranh Chấp</h3>
                            <ul class="fs-6">
                                <li class="mb-2">Mọi tranh chấp sẽ được ưu tiên giải quyết thông qua thương lượng, hòa giải.</li>
                                <li class="mb-2">Nếu không thể thương lượng, sẽ được giải quyết theo quy định pháp luật Việt Nam.</li>
                                <li class="mb-2">Tòa án có thẩm quyền giải quyết là Tòa án nhân dân tỉnh Phú Yên.</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning mt-4">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Lưu Ý</h5>
                            <p class="mb-0">Quy chế này có thể được cập nhật, bổ sung theo thời gian. Khách hàng vui lòng thường xuyên kiểm tra để nắm được các thay đổi mới nhất. Việc tiếp tục sử dụng dịch vụ sau khi quy chế được cập nhật được xem như khách hàng đã đồng ý với các thay đổi đó.</p>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h5 class="alert-heading"><i class="fas fa-calendar me-2"></i>Hiệu Lực</h5>
                            <p class="mb-0">Quy chế này có hiệu lực kể từ ngày được công bố trên website. Mọi giao dịch sau thời điểm này đều tuân theo quy chế mới.</p>
                        </div>

                        <div class="text-center mt-5">
                            <p class="text-muted">Cập nhật lần cuối: <?php echo date('d/m/Y'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../Layouts/Footer.php"); ?>
</body>
</html>

