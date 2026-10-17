<?php
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/VoucherService.php';
require_once dirname(__DIR__) . '/Services/ShippingService.php';

if (empty($_SESSION['cart'])) {
	header('Location: Cart.php');
	exit;
}

	// Guests check out with COD by typing their name, phone and address; members get them prefilled.
	$isGuest = empty($_SESSION['user']);
	$row_inf_user = ['ID_KhachHang' => 0, 'TenDangNhap' => '', 'HoVaTen' => '', 'Email' => '', 'SDT' => '', 'DiaChi' => ''];
	if (!$isGuest) {
		$userStatement = $conn->prepare('SELECT * FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
		$username = (string) $_SESSION['user'];
		$userStatement->bind_param('s', $username);
		$userStatement->execute();
		$row_inf_user = $userStatement->get_result()->fetch_assoc();
		if (!$row_inf_user) {
			header('Location: Login.php');
			exit;
		}
	}

	$ID_KhachHang = $row_inf_user['ID_KhachHang'];
	$TenDangNhap = $row_inf_user['TenDangNhap'];
	$HoVaTen = $row_inf_user['HoVaTen'];
	$Email = $row_inf_user['Email'];
	$SDT = $row_inf_user['SDT'];
	$DiaChi = $row_inf_user['DiaChi'];
	$shippingRegion = strtoupper(trim((string) ($_GET['shipping_region'] ?? '')));
	try { $shippingQuote = (new ShippingService($conn))->quoteCart($_SESSION['cart'], $shippingRegion); }
	catch (ShippingException) { header('Location: Cart.php?error=shipping_region_required'); exit; }
include("../Layouts/Head.php");
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

							<div class="col-md-5 p-3">
								<div class="pt-3 px-3">
									<h5>Thông tin người mua</h5>
									<?php if ($isGuest): ?>
									<small class="text-muted d-block">Bạn đang mua hàng không cần tài khoản (thanh toán khi nhận hàng). Nhân viên sẽ gọi theo số điện thoại để xác nhận đơn. <a href="Login.php">Đăng nhập</a> để dùng voucher và theo dõi đơn.</small>
									<?php endif; ?>
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-name">Họ và tên <span class="text-danger">*</span></label>
									<input type="text" id="form-checkout-name" class="form-control form-control-lg" maxlength="50" autocomplete="name" required value="<?php echo htmlspecialchars((string) $HoVaTen, ENT_QUOTES, 'UTF-8'); ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-email">Email<?php echo $isGuest ? ' <small class="text-muted">(không bắt buộc)</small>' : ' <span class="text-danger">*</span>'; ?></label>
									<input type="email" id="form-checkout-email" class="form-control form-control-lg" maxlength="100" autocomplete="email" value="<?php echo htmlspecialchars((string) $Email, ENT_QUOTES, 'UTF-8'); ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-phone">Số điện thoại <span class="text-danger">*</span></label>
									<input type="tel" id="form-checkout-phone" class="form-control form-control-lg" maxlength="20" autocomplete="tel" inputmode="tel" placeholder="Ví dụ: 0912345678" required value="<?php echo htmlspecialchars((string) $SDT, ENT_QUOTES, 'UTF-8'); ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-adress">Địa chỉ nhận hàng <span class="text-danger">*</span></label>
									<input type="text" id="form-checkout-adress" class="form-control form-control-lg" maxlength="255" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" required value="<?php echo htmlspecialchars((string) $DiaChi, ENT_QUOTES, 'UTF-8'); ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-note">Ghi Chú</label>
									<input type="text" id="form-checkout-note" class="form-control form-control-lg"/>
								</div>
								<div class="p-3">
									<input type="hidden" id="form-checkout-id" class="form-control form-control-lg" value="<?php echo (int) $ID_KhachHang; ?>" />
								</div>
							</div>
							<div class="col-md-7 p-3">
								<div class="pt-3 px-3">
									<h5>Danh sách sản phẩm mua</h5>
								</div>
								<div class="table-responsive rounded-3 border border-grey border-opacity-25 p-3 mt-4">
									<table class="table">
										<thead class="table-light">
											<tr>
												<th scope="col">STT</th>
												<th scope="col">Ảnh</th>
												<th scope="col">Tên sản phẩm</th>
												<th scope="col">Số lượng</th>
												<th scope="col">Giá tiền</th>
											</tr>
										</thead>
										<tbody>
											<?php
											if (isset($_SESSION['cart']) && (is_array($_SESSION['cart']))) {
												if (sizeof($_SESSION['cart']) > 0) {
													for ($i = 0; $i < sizeof($_SESSION['cart']); $i++) {
														$stt = $i + 1;
														echo '<tr>
															<th class="align-middle" scope="row">' . $stt . '</th>
															<td class="align-middle"><img src="../../../AdminManage/src/Products/' . $_SESSION['cart'][$i][2] . '" class="img-fluid rounded-3" style="max-height: 50px"></td>
															<td class="align-middle">' . $_SESSION['cart'][$i][1] . '</td>
															<td class="align-middle">' . $_SESSION['cart'][$i][3] . '</td>
															<td class="align-middle">' . $_SESSION['cart'][$i][4] . 'đ</td>
														</tr>';
													}
												} else {
													echo '<tr>
														<td colspan="5" class="align-middle">Vui lòng thêm sản phẩm để mua hàng</td>
													</tr>';
												}
											} else {
												echo '<tr>
													<td colspan="5" class="align-middle">Vui lòng thêm sản phẩm để mua hàng</td>
												</tr>';
											}
											?>
										</tbody>
										<tfoot>
											<?php
											$subtotal = (int) $shippingQuote['subtotal'];
											$shipping_fee = (int) $shippingQuote['fee'];
											
											$voucher_discount = 0;
											$checkout_voucher = null;
											if (!$isGuest && !empty($_SESSION['voucher_code']) && $subtotal > 0) {
												try {
													$checkout_voucher = (new VoucherService($conn))->validate(
														(string) $_SESSION['voucher_code'],
														(int) $ID_KhachHang,
														$subtotal
													);
													$voucher_discount = (int) $checkout_voucher['discount_amount'];
												} catch (VoucherException) {
													unset($_SESSION['voucher_code']);
												}
											}
											$total_with_shipping = max(0, $subtotal - $voucher_discount) + $shipping_fee;
											?>
											<tr>
												<th scope="col">Tổng tiền sản phẩm</th>
												<th scope="col"></th>
												<th scope="col"></th>
												<th scope="col"></th>
												<th scope="col"><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</th>
											</tr>
											<?php if ($shipping_fee > 0): ?>
											<tr>
												<th scope="col">Phí vận chuyển</th>
												<th scope="col"></th>
												<th scope="col"></th>
												<th scope="col"></th>
												<th scope="col"><?php echo number_format($shipping_fee, 0, ',', '.'); ?> đ</th>
											</tr>
											<?php else: ?>
											<tr>
												<th scope="col" colspan="4" class="text-success">
													<i class="fas fa-check-circle me-2"></i>Miễn Phí Vận Chuyển
												</th>
												<th scope="col" class="text-success">Miễn phí</th>
											</tr>
											<?php endif; ?>
											<?php if ($checkout_voucher !== null): ?>
											<tr class="text-success">
												<th scope="col" colspan="4">Giảm giá (<?php echo htmlspecialchars((string) $checkout_voucher['code'], ENT_QUOTES, 'UTF-8'); ?>)</th>
												<th scope="col">-<?php echo number_format($voucher_discount, 0, ',', '.'); ?> đ</th>
											</tr>
											<?php endif; ?>
											<tr class="table-active">
												<th scope="col"><strong>Tổng cộng</strong></th>
												<th scope="col"></th>
												<th scope="col"></th>
												<th scope="col"></th>
												<th scope="col"><strong><?php echo number_format($total_with_shipping, 0, ',', '.'); ?> đ</strong></th>
											</tr>
										</tfoot>
									</table>
								</div>
							</div>
						</div>
						<div class="row g-0">
							<div class="pb-4 px-3">
								<button onclick="MuaHang()" class="btn btn-success btn-lg btn-block px-3 mx-3">Mua Hàng (COD)</button>
								<a href="Cart.php" class="btn btn-danger btn-lg btn-block px-3">Hủy</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ajax -->
	<script>
		// Xử lý sự kiện nhấp vào nút "mua hang"
		const isGuestCheckout = <?php echo $isGuest ? 'true' : 'false'; ?>;
		function MuaHang() {
			var ID_KhachHang = document.getElementById("form-checkout-id").value;
			var HoVaTen = document.getElementById("form-checkout-name").value.trim();
			var Email = document.getElementById("form-checkout-email").value.trim();
			var Sdt = document.getElementById("form-checkout-phone").value.trim();
			var DiaChi = document.getElementById("form-checkout-adress").value.trim();
			var GhiChu = document.getElementById("form-checkout-note").value;

			// Same rules as CheckoutService: name, Vietnamese mobile number and address are required.
			if (!HoVaTen) { alert('Vui lòng nhập họ và tên.'); return; }
			if (!/^(?:0|\+84|84)(?:3|5|7|8|9)\d{8}$/.test(Sdt.replace(/[\s.\-()]/g, ''))) {
				alert('Số điện thoại không hợp lệ. Vui lòng nhập số di động Việt Nam, ví dụ 0912345678.');
				return;
			}
			if (DiaChi.length < 10) { alert('Vui lòng nhập địa chỉ nhận hàng đầy đủ.'); return; }
			if (!isGuestCheckout && !Email) { alert('Vui lòng nhập email.'); return; }

			$.ajax({
				type: 'POST',
				url: '../Processing/XuLy_DonHang.php',
				data: {
					csrf_token: <?php echo json_encode(csrf_token()); ?>,
					HoVaTen: HoVaTen,
					Email: Email,
					Sdt: Sdt,
					DiaChi: DiaChi,
					ShippingRegion: <?php echo json_encode($shippingRegion); ?>,
					GhiChu: GhiChu
				},
				success: function(response) {
					// Trim response to handle whitespace and extract last line (in case of warnings)
					response = response.trim();
					// Get the last line in case there are warnings before success
					var lines = response.split('\n');
					var lastLine = lines[lines.length - 1].trim();
					
					if (lastLine.indexOf("success:") === 0)
					{
						alert('Đặt hàng thành công! Mã đơn của bạn là #' + lastLine.substring(8) + '. Nhân viên Mơ Shop sẽ gọi ' + Sdt + ' để xác nhận.');
						window.location.href = "index.php";
					}
					else if(lastLine == "success" || response.includes("success"))
					{
						alert('Mua hàng thành công!');
						window.location.href = "Cart.php";
					}
					else if(lastLine == "error" || (response.includes("error") && !response.includes("success")))
					{
						alert('Mua hàng không thành công! Vui lòng thử lại');
					}
					else if(response.includes("stock_error:"))
					{
						var errorMsg = response.substring(response.indexOf("stock_error:") + "stock_error:".length).trim();
						alert('Lỗi: ' + errorMsg);
					}
					else if(response.includes("error_total"))
					{
						alert('Tổng tiền không hợp lệ. Vui lòng kiểm tra lại giỏ hàng.');
					}
					else if(response.includes("insufficient_balance"))
					{
						alert('Số dư tài khoản không đủ để thanh toán đơn hàng này.');
					}
					else
					{
						// If response contains success anywhere, treat as success
						if (response.toLowerCase().includes('success')) {
							alert('Mua hàng thành công!');
							window.location.href = "Cart.php";
						} else {
							// Log unexpected response for debugging
							console.log('Unexpected response:', response);
							alert('Đã xảy ra lỗi không xác định. Vui lòng thử lại sau.');
						}
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);
					// Validation failures come back as 4xx with "error:<lý do>" or "stock_error:<lý do>".
					var text = (xhr.responseText || '').trim();
					var match = text.match(/(?:stock_)?error:(.+)$/);
					alert(match ? match[1].trim() : 'Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.');
				}
			});
		}
	</script>
</body>

</html>
