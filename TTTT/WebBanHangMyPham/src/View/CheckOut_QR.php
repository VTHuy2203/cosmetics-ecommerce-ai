<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");
include("../Layouts/VietQR_Helper.php");

if (!isset($_SESSION['user'])) {
	header('Location: Login.php');
	exit();
}

$sql_inf_user = "select * from khachhang where TenDangNhap = '" . $_SESSION['user'] . "'";
$result = mysqli_query($conn, $sql_inf_user);
$row_inf_user = mysqli_fetch_array($result);
$ID_KhachHang = $row_inf_user['ID_KhachHang'];
$HoVaTen = $row_inf_user['HoVaTen'];
$Email = $row_inf_user['Email'];
$SDT = $row_inf_user['SDT'];
$DiaChi = $row_inf_user['DiaChi'];

// Calculate shipping fee: 25,000 VND for orders under 500,000 VND
$subtotal = isset($_SESSION['sum_Total']) ? intval($_SESSION['sum_Total']) : 0;
$free_shipping_threshold = 500000; // 500,000 VND
$shipping_fee = 0;
if ($subtotal > 0 && $subtotal < $free_shipping_threshold) {
	$shipping_fee = 25000; // 25,000 VND shipping fee
}
$totalAmount = $subtotal + $shipping_fee;

// Build VietQR
$mbBin = '970422';
$accountNo = '2232528623';
$itemsDesc = '';
if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && sizeof($_SESSION['cart']) > 0) {
	$parts = [];
	for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
		$parts[] = $_SESSION['cart'][$i][1] . 'x' . intval($_SESSION['cart'][$i][3]);
	}
	$itemsDesc = implode(', ', $parts);
}
$qrUrl = getVietQRUrl($accountNo, $mbBin, $totalAmount, $itemsDesc);
?>

<body>
	<?php
	include("../Layouts/Header_View.php");
	include("../Layouts/Link.php");
	?>
	<section class="vh-100 bg-light">
		<div class="container pt-5">
			<div class="row d-flex justify-content-center pt-5">
				<div class="col col-xl-10">
					<div class="card" style="border-radius: 1rem;">
						<div class="row g-0">
							<div class="col-md-7 p-3">
								<div class="pt-3 px-3">
									<h5>Thanh toán bằng QR</h5>
								</div>
								<div class="p-3">
									<p>Tổng tiền sản phẩm: <strong><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</strong></p>
									<?php if ($shipping_fee > 0): ?>
									<p>Phí vận chuyển: <strong><?php echo number_format($shipping_fee, 0, ',', '.'); ?> đ</strong></p>
									<?php else: ?>
									<p class="text-success"><i class="fas fa-check-circle me-2"></i>Miễn Phí Vận Chuyển</p>
									<?php endif; ?>
									<p><strong>Tổng cộng: <?php echo number_format($totalAmount, 0, ',', '.'); ?> đ</strong></p>
									<p>Nội dung chuyển khoản: <strong><?php echo htmlspecialchars($itemsDesc); ?></strong></p>
									<img src="<?php echo $qrUrl; ?>" alt="VietQR" class="img-fluid border rounded-3" style="max-width: 320px;"/>
									<div class="small text-muted mt-2">Quét mã bằng ứng dụng ngân hàng để tự động điền số tiền và nội dung.</div>
								</div>
							</div>
							<div class="col-md-5 p-3">
								<div class="pt-3 px-3">
									<h5>Xác nhận đơn hàng</h5>
								</div>
								<?php
								// Get account balance - check if column exists first
								$checkCol = mysqli_query($conn, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'khachhang' AND COLUMN_NAME = 'SoDu'");
								$colExists = false;
								if ($checkCol) {
									$colRow = mysqli_fetch_assoc($checkCol);
									$colExists = intval($colRow['c']) > 0;
								}
								if (!$colExists) {
									@mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
								}
								$balance_result = mysqli_query($conn, "SELECT SoDu FROM khachhang WHERE ID_KhachHang='$ID_KhachHang'");
								$balance_row = mysqli_fetch_array($balance_result);
								$account_balance = isset($balance_row['SoDu']) ? intval($balance_row['SoDu']) : 0;
								?>
								<div class="p-3">
									<label class="form-label">Phương thức thanh toán</label>
									<div class="form-check mb-2">
										<input class="form-check-input" type="radio" name="payment_method" id="payment_qr" value="qr" checked>
										<label class="form-check-label" for="payment_qr">
											Thanh toán bằng QR Code
										</label>
									</div>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="payment_method" id="payment_balance_qr" value="balance" <?php echo ($account_balance >= $totalAmount && $totalAmount > 0) ? '' : 'disabled'; ?>>
										<label class="form-check-label" for="payment_balance_qr">
											Thanh toán bằng số dư tài khoản
											<?php if ($account_balance >= $totalAmount && $totalAmount > 0): ?>
												<span class="badge badge-success">(Số dư: <?php echo number_format($account_balance, 0, ',', '.'); ?> đ)</span>
											<?php else: ?>
												<span class="badge badge-danger">(Số dư không đủ: <?php echo number_format($account_balance, 0, ',', '.'); ?> đ)</span>
											<?php endif; ?>
										</label>
									</div>
								</div>
								<div class="p-3">
									<label class="form-label">Ghi chú</label>
									<input type="text" id="qr-note" class="form-control form-control-lg" placeholder="Ví dụ: Đã chuyển khoản"/>
								</div>
								<div class="pb-4 px-3">
									<button onclick="PlaceOrderQR()" class="btn btn-success btn-lg btn-block px-3 mx-1">Đặt hàng</button>
									<a href="Cart.php" class="btn btn-outline-secondary btn-lg btn-block px-3">Quay lại giỏ</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<script>
		function PlaceOrderQR() {
			var note = document.getElementById('qr-note').value;
			var paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
			var payByBalance = (paymentMethod === 'balance') ? '1' : '0';
			
			$.ajax({
				type: 'POST',
				url: '../Processing/XuLy_DonHang_QR.php',
				data: { 
					GhiChu: note,
					pay_by_balance: payByBalance,
					shipping_fee: <?php echo $shipping_fee; ?>
				},
				success: function(resp) {
					resp = resp.trim();
					// Get the last line in case there are warnings before success
					var lines = resp.split('\n');
					var lastLine = lines[lines.length - 1].trim();
					console.log('Order response:', resp);
					
					if (lastLine === 'success' || resp.includes('success')) {
						alert('Đặt hàng thành công! Đơn hàng đã được tạo.');
						window.location.href = 'Order_Tracking.php';
					} else if (resp.includes('insufficient_balance')) {
						alert('Số dư tài khoản không đủ để thanh toán. Vui lòng chọn phương thức thanh toán khác hoặc nạp thêm tiền vào tài khoản.');
					} else if (resp.includes('stock_error:')) {
						var errorMsg = resp.substring(resp.indexOf('stock_error:') + 'stock_error:'.length).trim();
						alert('Lỗi: ' + errorMsg);
					} else if (resp.includes('error:')) {
						var errorMsg = resp.substring(resp.indexOf('error:') + 'error:'.length).trim();
						alert('Có lỗi khi tạo đơn hàng: ' + errorMsg);
					} else if (resp.includes('error') && !resp.includes('success')) {
						alert('Có lỗi khi tạo đơn hàng. Vui lòng thử lại.');
					} else {
						// If response contains success anywhere, treat as success
						if (resp.toLowerCase().includes('success')) {
							alert('Đặt hàng thành công! Đơn hàng đã được tạo.');
							window.location.href = 'Order_Tracking.php';
						} else {
							console.error('Unknown response:', resp);
							alert('Có lỗi khi tạo đơn hàng. Vui lòng thử lại.');
						}
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);
					alert('Đã xảy ra lỗi khi kết nối đến server. Vui lòng thử lại sau.\n\nLỗi: ' + error);
				}
			});
		}
	</script>
</body>

</html>
