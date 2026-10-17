<?php
declare(strict_types=1);

include '../DB_Connect.php';
include '../Layouts/Head.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/ShippingService.php';

if (empty($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

$stmt = $conn->prepare('SELECT HoVaTen, Email, SDT, DiaChi FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
$stmt->bind_param('s', $_SESSION['user']);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer || empty($_SESSION['cart'])) {
    header('Location: Cart.php');
    exit;
}
$shippingRegion = strtoupper(trim((string) ($_GET['shipping_region'] ?? '')));
try { $shippingQuote = (new ShippingService($conn))->quoteCart($_SESSION['cart'], $shippingRegion); }
catch (ShippingException) { header('Location: Cart.php?error=shipping_region_required'); exit; }
?>
<body>
<?php include '../Layouts/Header_View.php'; include '../Layouts/Link.php'; ?>
<section class="min-vh-100 bg-light py-5">
    <div class="container pt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0" style="border-radius:1rem">
                    <div class="card-body p-4 p-lg-5">
                        <h4>Thanh toán chuyển khoản bằng VietQR</h4>
                        <p class="text-muted">
                            Giá, số lượng, phí vận chuyển và giảm giá được kiểm tra lại tại máy chủ.
                            Chỉ chuyển khoản sau khi mã QR và thông tin ngân hàng xuất hiện.
                        </p>
                        <div class="alert alert-info">Khu vực: <?php echo htmlspecialchars($shippingQuote['region_name'],ENT_QUOTES,'UTF-8'); ?> · Phí ship: <?php echo number_format($shippingQuote['fee']); ?> đ</div>

                        <div id="payment-create">
                            <label class="form-label" for="qr-note">Ghi chú đơn hàng</label>
                            <input type="text" id="qr-note" maxlength="255" class="form-control mb-3" placeholder="Ghi chú giao hàng (không bắt buộc)">
                            <button id="create-payment" type="button" class="btn btn-success btn-lg">
                                Tạo đơn và mã VietQR
                            </button>
                            <a href="Cart.php" class="btn btn-outline-secondary btn-lg">Quay lại giỏ</a>
                        </div>

                        <div id="payment-result" class="text-center d-none" aria-live="polite">
                            <div class="alert alert-warning">Không sửa số tiền hoặc nội dung chuyển khoản. Admin sẽ đối soát trước khi duyệt đơn.</div>
                            <img id="payment-qr" alt="Mã VietQR thanh toán" class="img-fluid border rounded" style="max-width:340px">
                            <dl class="row text-start mt-4">
                                <dt class="col-sm-5">Ngân hàng</dt><dd class="col-sm-7">MB Bank</dd>
                                <dt class="col-sm-5">Chủ tài khoản</dt><dd class="col-sm-7"><strong id="payment-account-name"></strong></dd>
                                <dt class="col-sm-5">Số tài khoản</dt><dd class="col-sm-7"><code id="payment-account-number"></code></dd>
                                <dt class="col-sm-5">Số tiền</dt><dd class="col-sm-7"><strong id="payment-amount"></strong></dd>
                                <dt class="col-sm-5">Nội dung chuyển khoản</dt><dd class="col-sm-7"><strong id="payment-content"></strong></dd>
                                <dt class="col-sm-5">Mã đơn hàng</dt><dd class="col-sm-7"><code id="payment-order-id"></code></dd>
                                <dt class="col-sm-5">Hết hạn</dt><dd class="col-sm-7" id="payment-expiry"></dd>
                            </dl>
                            <div id="payment-state" class="alert alert-info">Đang chờ Admin xác nhận chuyển khoản…</div>
                            <a href="Order_Tracking.php" class="btn btn-outline-primary">Theo dõi đơn hàng</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
(() => {
    const button = document.getElementById('create-payment');
    const csrfToken = <?php echo json_encode(csrf_token()); ?>;
    let pollTimer = null;

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'Đang tạo đơn…';
        try {
            const body = new URLSearchParams({
                csrf_token: csrfToken,
                ShippingRegion: <?php echo json_encode($shippingRegion); ?>,
                GhiChu: document.getElementById('qr-note').value
            });
            const response = await fetch('../Processing/XuLy_DonHang_QR.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body,
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Không thể tạo đơn thanh toán.');
            }

            const order = data.order;
            document.getElementById('payment-create').classList.add('d-none');
            document.getElementById('payment-result').classList.remove('d-none');
            document.getElementById('payment-qr').src = order.qr_url;
            document.getElementById('payment-account-name').textContent = order.account_name;
            document.getElementById('payment-account-number').textContent = order.account_number;
            document.getElementById('payment-amount').textContent = new Intl.NumberFormat('vi-VN').format(order.amount) + ' đ';
            document.getElementById('payment-content').textContent = order.transfer_content;
            document.getElementById('payment-order-id').textContent = '#' + order.order_id;
            document.getElementById('payment-expiry').textContent = order.expires_at;
            pollPayment(order.payment_code);
        } catch (error) {
            alert(error.message);
            button.disabled = false;
            button.textContent = 'Tạo đơn và mã VietQR';
        }
    });

    async function pollPayment(code) {
        clearTimeout(pollTimer);
        try {
            const response = await fetch('../Processing/Payment_Status.php?code=' + encodeURIComponent(code), {credentials: 'same-origin'});
            const data = await response.json();
            if (data.success && data.payment_status === 'paid') {
                const state = document.getElementById('payment-state');
                state.className = 'alert alert-success';
                state.textContent = 'Thanh toán thành công. Cảm ơn bạn!';
                return;
            }
            if (data.success && ['expired', 'failed'].includes(data.payment_status)) {
                const state = document.getElementById('payment-state');
                state.className = 'alert alert-danger';
                state.textContent = 'Mã thanh toán đã hết hạn hoặc không hợp lệ.';
                return;
            }
        } catch (_) {
            // A transient polling failure must not create a second payment.
        }
        pollTimer = setTimeout(() => pollPayment(code), 3000);
    }
})();
</script>
</body>
</html>
