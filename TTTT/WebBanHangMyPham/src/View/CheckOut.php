<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (isset($_SESSION['user'])) {
	$sql_inf_user = "select * from khachhang where TenDangNhap = '" . $_SESSION['user'] . "'";

	$result = mysqli_query($conn, $sql_inf_user);

	$row_inf_user = mysqli_fetch_array($result);

	$ID_KhachHang = $row_inf_user['ID_KhachHang'];
	$TenDangNhap = $row_inf_user['TenDangNhap'];
	$HoVaTen = $row_inf_user['HoVaTen'];
	$Email = $row_inf_user['Email'];
	$SDT = $row_inf_user['SDT'];
	$DiaChi = $row_inf_user['DiaChi'];
}
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
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-name">Họ và tên</label>
									<input type="text" id="form-checkout-name" class="form-control form-control-lg" value="<?php echo $HoVaTen; ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-email">Email</label>
									<input type="email" id="form-checkout-email" class="form-control form-control-lg" value="<?php echo $Email; ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-phone">Số điện thoại</label>
									<input type="text" id="form-checkout-phone" class="form-control form-control-lg" value="<?php echo $SDT; ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-adress">Địa chỉ</label>
									<input type="text" id="form-checkout-adress" class="form-control form-control-lg" value="<?php echo $DiaChi; ?>" />
								</div>
								<div class="p-3">
									<label class="form-label" for="form-checkout-note">Ghi Chú</label>
									<input type="text" id="form-checkout-note" class="form-control form-control-lg"/>
								</div>
								<div class="p-3">
									<input type="hidden" id="form-checkout-id" class="form-control form-control-lg" value="<?php echo $ID_KhachHang; ?>" />
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
											// Calculate shipping fee: 25,000 VND for orders under 500,000 VND
											$subtotal = isset($_SESSION['sum_Total']) ? intval($_SESSION['sum_Total']) : 0;
											$shipping_fee = 0;
											$free_shipping_threshold = 500000; // 500,000 VND
											
											if ($subtotal > 0 && $subtotal < $free_shipping_threshold) {
												$shipping_fee = 25000; // 25,000 VND shipping fee
											}
											
											$total_with_shipping = $subtotal + $shipping_fee;
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
		function MuaHang() {
			var ID_KhachHang = document.getElementById("form-checkout-id").value;
			var HoVaTen = document.getElementById("form-checkout-name").value;
			var Email = document.getElementById("form-checkout-email").value;
			var Sdt = document.getElementById("form-checkout-phone").value;
			var DiaChi = document.getElementById("form-checkout-adress").value;
			var GhiChu = document.getElementById("form-checkout-note").value;
			
			// Calculate shipping fee
			var subtotal = <?php echo $subtotal; ?>;
			var shippingFee = <?php echo $shipping_fee; ?>;
			
			$.ajax({
				type: 'POST',
				url: '../Processing/XuLy_DonHang.php',
				data: {
					ID_KhachHang: ID_KhachHang,
					HoVaTen: HoVaTen,
					Email: Email,
					Sdt: Sdt,
					DiaChi: DiaChi,
					GhiChu: GhiChu,
					shipping_fee: shippingFee
				},
				success: function(response) {
					// Trim response to handle whitespace and extract last line (in case of warnings)
					response = response.trim();
					// Get the last line in case there are warnings before success
					var lines = response.split('\n');
					var lastLine = lines[lines.length - 1].trim();
					
					if(lastLine == "success" || response.includes("success"))
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
					alert('Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.');
				}
			});
		}
	</script>
</body>

</html>