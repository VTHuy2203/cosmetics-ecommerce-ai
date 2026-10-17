<?php
declare(strict_types=1);

include '../DB_Connect.php';
include '../Layouts/Head.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (empty($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

$invoice = strtoupper(trim((string) ($_GET['invoice'] ?? '')));
if (!preg_match('/^[A-Z0-9_-]{6,100}$/', $invoice)) {
    http_response_code(400);
    exit('Mã giao dịch không hợp lệ.');
}

$stmt = $conn->prepare(
    "SELECT d.ID_DonHang, d.TongTien, d.TrangThaiThanhToan, d.ThanhToanLuc
     FROM donhang d
     INNER JOIN khachhang k ON k.ID_KhachHang = d.ID_KhachHang
     WHERE d.SePayInvoiceNumber = ? AND d.PhuongThucThanhToan = 'sepay_pg'
       AND k.TenDangNhap = ? LIMIT 1"
);
$stmt->bind_param('ss', $invoice, $_SESSION['user']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

$status = (string) $order['TrangThaiThanhToan'];
$statusText = [
    'pending' => 'Đang chờ SePay gửi xác nhận thanh toán…',
    'paid' => 'Thanh toán thành công.',
    'failed' => 'Giao dịch không thành công.',
    'expired' => 'Phiên thanh toán đã hết hạn.',
    'review' => 'Giao dịch đang được đối soát. Vui lòng không thanh toán lại.',
][$status] ?? 'Trạng thái giao dịch đang được cập nhật.';
?>
<body>
<?php include '../Layouts/Header_View.php'; include '../Layouts/Link.php'; ?>
<section class="min-vh-100 bg-light py-5">
    <div class="container pt-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0" style="border-radius:1rem">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <h3>Kết quả thanh toán</h3>
                        <p>Đơn hàng <strong>#<?php echo (int) $order['ID_DonHang']; ?></strong></p>
                        <p>Số tiền: <strong><?php echo number_format((int) $order['TongTien'], 0, ',', '.'); ?> đ</strong></p>
                        <div id="gateway-state" class="alert <?php echo $status === 'paid' ? 'alert-success' : ($status === 'review' ? 'alert-warning' : 'alert-info'); ?>">
                            <?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <p class="small text-muted">Kết quả trên trình duyệt không tự xác nhận đơn. Hệ thống chỉ ghi nhận khi nhận IPN hợp lệ từ SePay.</p>
                        <a href="Order_Tracking.php" class="btn btn-primary">Theo dõi đơn hàng</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if ($status === 'pending'): ?>
<script>
(() => {
    const invoice = <?php echo json_encode($invoice); ?>;
    const state = document.getElementById('gateway-state');
    let attempts = 0;
    async function poll() {
        attempts++;
        try {
            const response = await fetch('../Processing/Payment_Gateway_Status.php?invoice=' + encodeURIComponent(invoice), {
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const data = await response.json();
            if (data.success && data.payment_status === 'paid') {
                state.className = 'alert alert-success';
                state.textContent = 'Thanh toán thành công.';
                return;
            }
            if (data.success && data.payment_status === 'review') {
                state.className = 'alert alert-warning';
                state.textContent = 'Giao dịch đang được đối soát. Vui lòng không thanh toán lại.';
                return;
            }
            if (data.success && ['failed', 'expired'].includes(data.payment_status)) {
                state.className = 'alert alert-danger';
                state.textContent = 'Giao dịch không thành công hoặc đã hết hạn.';
                return;
            }
        } catch (_) {
            // A temporary network error must not cause a second checkout.
        }
        if (attempts < 60) {
            setTimeout(poll, 3000);
        }
    }
    poll();
})();
</script>
<?php endif; ?>
</body>
</html>
