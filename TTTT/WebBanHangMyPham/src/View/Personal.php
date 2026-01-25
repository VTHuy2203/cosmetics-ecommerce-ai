<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");
include("../Layouts/VietQR_Helper.php");

if (!isset($_SESSION['user'])) {
	header('Location: Login.php');
	exit();
}

// Ensure schema: add SoDu and AnhDaiDien columns; create nap_tien table
function ensurePersonalSchema($conn) {
	$colCheck = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='khachhang' AND COLUMN_NAME IN ('SoDu','AnhDaiDien')";
	$res = mysqli_query($conn, $colCheck);
	$have = [];
	while ($res && ($r = mysqli_fetch_assoc($res))) { $have[$r['COLUMN_NAME']] = true; }
	if (!isset($have['SoDu'])) {
		@mysqli_query($conn, "ALTER TABLE `khachhang` ADD `SoDu` BIGINT NOT NULL DEFAULT 0 AFTER `DiaChi`");
	}
	if (!isset($have['AnhDaiDien'])) {
		@mysqli_query($conn, "ALTER TABLE `khachhang` ADD `AnhDaiDien` VARCHAR(255) NULL AFTER `SoDu`");
	}
	@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `nap_tien` (
		`ID_Nap` INT NOT NULL AUTO_INCREMENT,
		`ID_KhachHang` INT NOT NULL,
		`SoTien` BIGINT NOT NULL,
		`NoiDung` VARCHAR(255) NOT NULL,
		`TrangThai` TINYINT(1) NOT NULL DEFAULT 0,
		`NgayTao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`ID_Nap`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensurePersonalSchema($conn);

// Load user
$sql = "select * from khachhang where TenDangNhap = '" . $_SESSION['user'] . "'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_array($result);
?>

<body>
	<?php include("../Layouts/Header_View.php"); ?>
	<?php include("../Layouts/Link.php"); ?>
	<div class="container mt-4">
		<h3 class="mb-3">Trang cá nhân</h3>
		<div class="row">
			<div class="col-md-4">
				<div class="card p-3">
					<h5>Ảnh đại diện</h5>
					<div class="mb-2">
						<img src="<?php echo ($user['AnhDaiDien'] ? $user['AnhDaiDien'] : '/TTTT/imgs/logo/login.png'); ?>" class="img-fluid rounded" style="max-width:180px;" alt="avatar"/>
					</div>
					<form action="../Processing/Update_Profile.php" method="post" enctype="multipart/form-data">
						<input type="hidden" name="action" value="avatar">
						<input type="file" name="avatar" class="form-control mb-2" accept="image/*">
						<button class="btn btn-primary btn-sm">Cập nhật ảnh</button>
					</form>
				</div>
				<div class="card p-3 mt-3">
					<h5>Số dư tài khoản</h5>
					<p class="fs-4 text-success"><strong><?php echo number_format($user['SoDu'], 0, ',', '.'); ?> đ</strong></p>
					<h6>Nạp tiền</h6>
					
					<!-- Recharge amount selection -->
					<div class="mb-3">
						<label class="form-label">Chọn mức nạp:</label>
						<select id="recharge-amount" class="form-select" onchange="updateQRCode()">
							<option value="">-- Chọn mức nạp --</option>
							<option value="10000">10,000 đ</option>
							<option value="20000">20,000 đ</option>
							<option value="50000">50,000 đ</option>
							<option value="100000">100,000 đ</option>
							<option value="200000">200,000 đ</option>
							<option value="500000">500,000 đ</option>
						</select>
					</div>
					
					
					<!-- QR Code display -->
					<div id="qr-code-container" class="mb-3" style="display: none;">
						<label class="form-label">Quét mã QR để nạp tiền:</label>
						<div class="text-center">
							<img id="qr-code-image" src="" alt="QR Code" class="img-fluid border rounded-3" style="max-width: 300px;">
							<p class="small text-muted mt-2">Nội dung chuyển khoản: <strong id="transfer-note"></strong></p>
							<p class="small text-muted">Quét mã bằng ứng dụng ngân hàng để tự động điền số tiền và nội dung.</p>
						</div>
					</div>
					
					<!-- Request Top-Up Button -->
					<div id="request-topup-container" class="mb-3" style="display: none;">
						<button type="button" id="btn-request-topup" class="btn btn-primary btn-block" onclick="requestTopUp()">
							<i class="fas fa-paper-plane"></i> Gửi yêu cầu nạp tiền
						</button>
						<p class="small text-muted mt-2">Sau khi quét QR và chuyển khoản, nhấn nút này để gửi yêu cầu. Admin sẽ xác nhận số tiền cho bạn.</p>
					</div>
					
					<!-- Submit form (hidden, using QR code instead) -->
					<form id="deposit-form" action="../Processing/Create_Deposit.php" method="post" style="display: none;">
						<input type="hidden" name="SoTien" id="form-amount">
						<input type="hidden" name="NoiDung" id="form-note">
					</form>
				</div>
				
				<script>
				function updateQRCode() {
					const select = document.getElementById('recharge-amount');
					const qrContainer = document.getElementById('qr-code-container');
					const requestContainer = document.getElementById('request-topup-container');
					const qrImage = document.getElementById('qr-code-image');
					const transferNote = document.getElementById('transfer-note');
					
					const amount = parseInt(select.value) || 0;
					
					if (amount >= 1000) {
						const accountName = '<?php echo htmlspecialchars($user['HoVaTen'] ?: $user['TenDangNhap']); ?>';
						const note = 'Nap tien tai khoan + ' + accountName;
						
						// Build VietQR URL
						const mbBin = '970422';
						const accountNo = '2232528623';
						const qrUrl = 'https://img.vietqr.io/image/' + mbBin + '-' + accountNo + '-compact.png?amount=' + amount + '&addInfo=' + encodeURIComponent(note);
						
						qrImage.src = qrUrl;
						transferNote.textContent = note;
						qrContainer.style.display = 'block';
						requestContainer.style.display = 'block';
						
						// Update form values
						document.getElementById('form-amount').value = amount;
						document.getElementById('form-note').value = note;
					} else {
						qrContainer.style.display = 'none';
						requestContainer.style.display = 'none';
					}
				}
				
				function requestTopUp() {
					const amount = document.getElementById('form-amount').value;
					const note = document.getElementById('form-note').value;
					
					if (!amount || parseInt(amount) < 1000) {
						alert('Vui lòng chọn số tiền nạp (tối thiểu 1,000 đ)');
						return;
					}
					
					const btn = document.getElementById('btn-request-topup');
					btn.disabled = true;
					btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
					
					// Submit form
					const form = document.getElementById('deposit-form');
					const formData = new FormData(form);
					
					fetch(form.action, {
						method: 'POST',
						body: formData
					})
					.then(res => {
						if (res.ok) {
							alert('Yêu cầu nạp tiền đã được gửi! Admin sẽ xác nhận số tiền sau khi bạn chuyển khoản.');
							location.reload();
						} else {
							throw new Error('Lỗi khi gửi yêu cầu');
						}
					})
					.catch(err => {
						console.error('Error:', err);
						alert('Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại.');
						btn.disabled = false;
						btn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi yêu cầu nạp tiền';
					});
				}
				</script>
			</div>
			<div class="col-md-8">
				<div class="card p-3">
					<h5>Cập nhật thông tin</h5>
					<form action="../Processing/Update_Profile.php" method="post">
						<input type="hidden" name="action" value="info">
						<div class="row g-2">
							<div class="col-md-6">
								<label class="form-label">Họ và tên</label>
								<input name="HoVaTen" class="form-control" value="<?php echo $user['HoVaTen']; ?>">
							</div>
							<div class="col-md-6">
								<label class="form-label">Email</label>
								<input name="Email" type="email" class="form-control" value="<?php echo $user['Email']; ?>">
							</div>
							<div class="col-md-6">
								<label class="form-label">Số điện thoại</label>
								<input name="SDT" class="form-control" value="<?php echo $user['SDT']; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;" title="Số điện thoại không thể thay đổi">
							</div>
							<div class="col-md-6">
								<label class="form-label">Địa chỉ</label>
								<input name="DiaChi" class="form-control" value="<?php echo $user['DiaChi']; ?>">
							</div>
						</div>
						<div class="mt-3">
							<button class="btn btn-primary">Lưu thay đổi</button>
						</div>
					</form>
				</div>
				<div class="card p-3 mt-3">
					<h5>Đơn hàng của tôi</h5>
					<a href="Order_Tracking.php" class="btn btn-outline-secondary">Xem lịch sử đơn hàng</a>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
