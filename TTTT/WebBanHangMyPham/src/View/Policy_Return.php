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
                        <h1 class="mb-4 text-center" style="color: #2D9596;">Chính Sách Đổi Trả Và Bảo Hành</h1>
                        
                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-exchange-alt me-2"></i>Chính Sách Đổi Trả</h3>
                            
                            <h5 class="mt-4 mb-3">1. Điều Kiện Đổi Trả</h5>
                            <p class="fs-6 mb-3">Mơ Shop cam kết hỗ trợ đổi trả sản phẩm trong các trường hợp sau:</p>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Sản phẩm bị lỗi:</strong> Sản phẩm bị hư hỏng, không đúng với mô tả, hoặc bị lỗi từ nhà sản xuất.</li>
                                <li class="mb-2"><strong>Sản phẩm không đúng đơn hàng:</strong> Giao nhầm sản phẩm, sai mẫu mã, sai màu sắc so với đơn hàng.</li>
                                <li class="mb-2"><strong>Sản phẩm hết hạn sử dụng:</strong> Sản phẩm đã hết hạn sử dụng hoặc gần hết hạn (dưới 3 tháng).</li>
                                <li class="mb-2"><strong>Sản phẩm bị thiếu:</strong> Thiếu số lượng hoặc thiếu phụ kiện đi kèm.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Thời Gian Đổi Trả</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Thời gian yêu cầu đổi trả: <strong>Trong vòng 7 ngày</strong> kể từ ngày nhận hàng.</li>
                                <li class="mb-2">Sản phẩm phải còn nguyên vẹn, chưa sử dụng, còn đầy đủ bao bì, nhãn mác.</li>
                                <li class="mb-2">Đối với sản phẩm đã mở nắp hoặc sử dụng, chỉ được đổi trả nếu có lỗi từ nhà sản xuất.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">3. Quy Trình Đổi Trả</h5>
                            <ol class="fs-6">
                                <li class="mb-2">Khách hàng liên hệ với Mơ Shop qua hotline hoặc email để thông báo yêu cầu đổi trả.</li>
                                <li class="mb-2">Cung cấp mã đơn hàng và lý do đổi trả kèm hình ảnh sản phẩm (nếu có).</li>
                                <li class="mb-2">Mơ Shop xác nhận và hướng dẫn quy trình đổi trả.</li>
                                <li class="mb-2">Khách hàng gửi lại sản phẩm về địa chỉ của Mơ Shop (chi phí vận chuyển do khách hàng chịu, trừ trường hợp lỗi từ phía cửa hàng).</li>
                                <li class="mb-2">Sau khi nhận và kiểm tra sản phẩm, Mơ Shop sẽ xử lý đổi trả trong vòng 3-5 ngày làm việc.</li>
                            </ol>

                            <h5 class="mt-4 mb-3">4. Các Trường Hợp Không Được Đổi Trả</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Sản phẩm đã qua sử dụng quá 50% dung tích (trừ trường hợp lỗi).</li>
                                <li class="mb-2">Sản phẩm bị hư hỏng do lỗi của khách hàng (rơi vỡ, để nơi ẩm ướt, tiếp xúc với nhiệt độ cao...).</li>
                                <li class="mb-2">Quá thời hạn 7 ngày kể từ ngày nhận hàng.</li>
                                <li class="mb-2">Sản phẩm không còn đầy đủ bao bì, nhãn mác, phụ kiện.</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h3 class="mb-3" style="color: #298b8d;"><i class="fas fa-shield-alt me-2"></i>Chính Sách Bảo Hành</h3>
                            
                            <h5 class="mt-4 mb-3">1. Phạm Vi Bảo Hành</h5>
                            <p class="fs-6 mb-3">Mơ Shop bảo hành các sản phẩm mỹ phẩm theo chính sách của nhà sản xuất:</p>
                            <ul class="fs-6">
                                <li class="mb-2"><strong>Bảo hành chất lượng:</strong> Đảm bảo sản phẩm chính hãng, không phải hàng giả, hàng nhái.</li>
                                <li class="mb-2"><strong>Bảo hành bao bì:</strong> Đảm bảo sản phẩm còn nguyên vẹn, không bị rò rỉ, hư hỏng bao bì.</li>
                                <li class="mb-2"><strong>Bảo hành hạn sử dụng:</strong> Đảm bảo sản phẩm còn hạn sử dụng ít nhất 6 tháng kể từ ngày mua.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">2. Thời Gian Bảo Hành</h5>
                            <ul class="fs-6">
                                <li class="mb-2">Thời gian bảo hành: <strong>30 ngày</strong> kể từ ngày nhận hàng.</li>
                                <li class="mb-2">Trong thời gian bảo hành, nếu phát hiện sản phẩm có lỗi, khách hàng được quyền đổi trả miễn phí.</li>
                            </ul>

                            <h5 class="mt-4 mb-3">3. Quy Trình Bảo Hành</h5>
                            <ol class="fs-6">
                                <li class="mb-2">Khách hàng phát hiện lỗi và liên hệ với Mơ Shop trong thời gian bảo hành.</li>
                                <li class="mb-2">Cung cấp thông tin đơn hàng, hình ảnh minh chứng lỗi sản phẩm.</li>
                                <li class="mb-2">Mơ Shop kiểm tra và xác nhận lỗi.</li>
                                <li class="mb-2">Thực hiện đổi trả hoặc hoàn tiền tùy theo yêu cầu của khách hàng.</li>
                            </ol>
                        </div>

                        <div class="alert alert-warning mt-4">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Lưu Ý Quan Trọng</h5>
                            <ul class="mb-0">
                                <li>Vui lòng kiểm tra kỹ sản phẩm ngay khi nhận hàng để phát hiện lỗi sớm nhất.</li>
                                <li>Giữ lại hóa đơn và bao bì sản phẩm để thuận tiện cho việc đổi trả.</li>
                                <li>Đối với sản phẩm mỹ phẩm, do tính chất đặc thù, việc đổi trả chỉ được thực hiện khi có lỗi từ nhà sản xuất.</li>
                            </ul>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h5 class="alert-heading"><i class="fas fa-headset me-2"></i>Hỗ Trợ Khách Hàng</h5>
                            <p class="mb-0">Mọi thắc mắc về chính sách đổi trả và bảo hành, vui lòng liên hệ:</p>
                            <ul class="mb-0 mt-2">
                                <li><i class="fas fa-phone me-2"></i>Hotline: 0559110474</li>
                                <li><i class="fas fa-envelope me-2"></i>Email: moshop.danang@gmail.com</li>
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

