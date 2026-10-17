<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (!isset($_SESSION['user'])) {
	header('Location: Login.php');
	exit();
}

// Load user
$userStmt = $conn->prepare('SELECT * FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
$username = (string) $_SESSION['user'];
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) {
	unset(
		$_SESSION['user'],
		$_SESSION['IDNguoiDung'],
		$_SESSION['user_role'],
		$_SESSION['is_admin'],
		$_SESSION['HoTen'],
		$_SESSION['TenNguoiDung']
	);
	header('Location: Login.php');
	exit();
}

function personal_h($value): string
{
	return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function personal_chat_title(array $session): string
{
	$title = trim((string) ($session['title'] ?? $session['first_user_message'] ?? ''));
	$title = trim(preg_replace('/\s+/u', ' ', $title) ?? '');
	if ($title === '') return 'Cuộc trò chuyện mới';
	return mb_strlen($title) > 55 ? mb_substr($title, 0, 52) . '...' : $title;
}

$personalUserId = (int) $user['ID_KhachHang'];
$chatSessions = [];
$appointments = [];

$chatStatement = $conn->prepare(
	"SELECT cs.session_id, cs.title, cs.created_at, cs.updated_at, cs.last_message_at,
	        (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id = cs.session_id AND cm.user_id = cs.user_id) AS message_count,
	        (SELECT cm.message FROM chat_messages cm
	         WHERE cm.session_id = cs.session_id AND cm.user_id = cs.user_id AND cm.direction = 'user'
	         ORDER BY cm.created_at ASC, cm.id ASC LIMIT 1) AS first_user_message,
	        (SELECT cm.message FROM chat_messages cm
	         WHERE cm.session_id = cs.session_id AND cm.user_id = cs.user_id
	         ORDER BY cm.created_at DESC, cm.id DESC LIMIT 1) AS last_message
	 FROM chat_sessions cs
	 WHERE cs.user_id = ?
	 ORDER BY COALESCE(cs.last_message_at, cs.created_at) DESC
	 LIMIT 30"
);
$chatStatement->bind_param('i', $personalUserId);
$chatStatement->execute();
$chatSessions = $chatStatement->get_result()->fetch_all(MYSQLI_ASSOC);

$currentChatSessions = [];
$legacyChatSessions = [];
$modernSessionPattern = '/^u' . preg_quote((string) $personalUserId, '/') . '_[a-f0-9]{36}$/';
foreach ($chatSessions as $chatSession) {
	if (preg_match($modernSessionPattern, (string) $chatSession['session_id']) === 1) {
		$currentChatSessions[] = $chatSession;
	} else {
		$legacyChatSessions[] = $chatSession;
	}
}

$activeChatSessionId = (string) ($_GET['chat_session'] ?? $_COOKIE['chat_session_id'] ?? $_SESSION['chat_current_session_id'] ?? '');
$ownedSessionIds = array_column($chatSessions, 'session_id');
if (!in_array($activeChatSessionId, $ownedSessionIds, true)) {
	$activeChatSessionId = '';
}

$appointmentStatement = $conn->prepare(
	'SELECT id, session_id, appt_at, note, message_content, created_at
	 FROM chat_appointments WHERE user_id = ?
	 ORDER BY appt_at DESC LIMIT 50'
);
$appointmentStatement->bind_param('i', $personalUserId);
$appointmentStatement->execute();
$appointments = $appointmentStatement->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<style>
	.personal-history-card {
		border: 0;
		border-radius: 1rem;
		box-shadow: 0 .35rem 1.1rem rgba(26, 46, 56, .08);
	}

	.personal-history-list {
		display: grid;
		grid-template-columns: minmax(0, 1fr);
		gap: .75rem;
		max-height: 480px;
		overflow-y: auto;
		overflow-x: hidden;
		padding-right: .25rem;
	}

	.personal-history-item {
		display: grid;
		grid-template-columns: 42px minmax(0, 1fr) auto;
		align-items: center;
		gap: .85rem;
		padding: .9rem;
		border: 1px solid #e4ecef;
		border-radius: .85rem;
		background: #fff;
		transition: border-color .2s ease, box-shadow .2s ease;
		box-sizing: border-box;
		width: 100%;
		min-width: 0;
	}

	.personal-history-item:hover {
		border-color: #9fd2cb;
		box-shadow: 0 .25rem .8rem rgba(20, 135, 124, .09);
	}

	.personal-history-icon {
		display: flex;
		width: 42px;
		height: 42px;
		align-items: center;
		justify-content: center;
		flex: 0 0 42px;
		border-radius: 50%;
		background: #e8f5f3;
		color: #14877c;
	}

	.personal-history-content {
		min-width: 0;
		flex: 1;
	}

	.personal-history-action {
		justify-self: end;
		white-space: nowrap;
	}

	.personal-history-preview {
		overflow: hidden;
		margin: .2rem 0 0;
		color: #718096;
		font-size: .85rem;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.personal-history-meta {
		color: #8b98a5;
		font-size: .76rem;
	}

	.appointment-upcoming {
		border-left: 4px solid #e2a51b;
		background: #fffcf3;
	}

	.personal-history-item.is-active-chat {
		border-color: #4db6aa;
		background: #f2fbf9;
		box-shadow: 0 0 0 2px rgba(20, 135, 124, .08);
	}

	.chat-archive {
		margin-top: 1rem;
		border-top: 1px solid #e7edef;
		padding-top: 1rem;
	}

	.chat-archive summary {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: .75rem;
		color: #55636e;
		cursor: pointer;
		font-size: .88rem;
		font-weight: 600;
		list-style: none;
	}

	.chat-archive summary::-webkit-details-marker {
		display: none;
	}

	.chat-archive-note {
		margin: .7rem 0;
		padding: .7rem .8rem;
		border-radius: .7rem;
		background: #f7f9fa;
		color: #718096;
		font-size: .79rem;
		line-height: 1.45;
	}

	.chat-archive-list {
		display: grid;
		grid-template-columns: minmax(0, 1fr);
		gap: .65rem;
		max-height: 330px;
		overflow-y: auto;
		overflow-x: hidden;
		padding-right: .2rem;
	}

	@media (max-width: 575.98px) {
		.personal-history-item {
			grid-template-columns: 42px minmax(0, 1fr);
			align-items: start;
		}

		.personal-history-action {
			grid-column: 2;
			justify-self: start;
			width: auto;
			margin-left: 0;
		}
	}
</style>

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
						<img src="<?php echo htmlspecialchars($user['AnhDaiDien'] ?: '/TTTT/imgs/logo/login.png', ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded" style="max-width:180px;" alt="avatar"/>
					</div>
					<form action="../Processing/Update_Profile.php" method="post" enctype="multipart/form-data">
						<?php echo csrf_field(); ?>
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
					
					
					<!-- VietQR dùng tài khoản ngân hàng của cửa hàng; admin xác nhận thủ công. -->
					<div id="qr-code-container" class="mb-3" style="display: none;">
						<label class="form-label">Quét mã QR để nạp tiền:</label>
						<div class="text-center">
							<img id="qr-code-image" src="" alt="QR Code" class="img-fluid border rounded-3" style="max-width: 300px;">
							<p class="small text-muted mt-2">Nội dung chuyển khoản: <strong id="transfer-note"></strong></p>
							<p class="small text-muted mb-1">Chủ tài khoản: <strong id="transfer-account-name"></strong></p>
							<p class="small text-muted mb-1">Số tài khoản: <strong id="transfer-account-number"></strong></p>
							<p class="small text-danger">Yêu cầu còn hiệu lực <strong id="deposit-countdown"></strong>. Không sửa số tiền hoặc nội dung chuyển khoản.</p>
						</div>
					</div>
					
					<!-- Request Top-Up Button -->
					<div id="request-topup-container" class="mb-3" style="display: none;">
						<button type="button" id="btn-request-topup" class="btn btn-primary btn-block" onclick="requestTopUp()">
							<i class="fas fa-qrcode"></i> Tạo QR nạp tiền
						</button>
						<p class="small text-muted mt-2">Sau khi chuyển khoản, yêu cầu sẽ chờ Admin đối soát và cộng số dư cho bạn.</p>
					</div>
					
					<!-- Submit form (hidden, using QR code instead) -->
					<form id="deposit-form" action="../Processing/Create_Deposit.php" method="post" style="display: none;">
						<?php echo csrf_field(); ?>
						<input type="hidden" name="SoTien" id="form-amount">
					</form>
				</div>
				
				<script>
				function updateQRCode() {
					const select = document.getElementById('recharge-amount');
					const qrContainer = document.getElementById('qr-code-container');
					const requestContainer = document.getElementById('request-topup-container');
					const amount = parseInt(select.value) || 0;
					
					if (amount >= 10000) {
						qrContainer.style.display = 'none';
						requestContainer.style.display = 'block';
						document.getElementById('form-amount').value = amount;
					} else {
						qrContainer.style.display = 'none';
						requestContainer.style.display = 'none';
					}
				}
				
				function requestTopUp() {
					const amount = document.getElementById('form-amount').value;
					if (!amount || parseInt(amount) < 10000) {
						alert('Vui lòng chọn một mức nạp hợp lệ.');
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
						return res.json().then(data => {
							if (!res.ok || !data.success) throw new Error(data.message || 'Không thể tạo mã nạp tiền.');
							return data;
						});
					})
					.then(data => {
						document.getElementById('qr-code-image').src = data.qr_url;
						document.getElementById('transfer-note').textContent = 'NAP ' + data.payment_code;
						document.getElementById('transfer-account-name').textContent = data.account_name;
						document.getElementById('transfer-account-number').textContent = data.account_number;
						document.getElementById('qr-code-container').style.display = 'block';
						startDepositCountdown(Number(data.expires_in_seconds || 0));
						pollDepositStatus(Number(data.deposit_id), Number(data.expires_in_seconds || 0));
						btn.style.display = 'none';
					})
					.catch(err => {
						alert(err.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
						btn.disabled = false;
						btn.innerHTML = '<i class="fas fa-qrcode"></i> Tạo QR nạp tiền';
					});
				}

				function startDepositCountdown(seconds) {
					const target = document.getElementById('deposit-countdown');
					let timer = null;
					const render = () => {
						const minutes = Math.floor(Math.max(0, seconds) / 60);
						const remain = Math.max(0, seconds) % 60;
						target.textContent = minutes + ':' + String(remain).padStart(2, '0');
						if (seconds-- <= 0 && timer !== null) window.clearInterval(timer);
					};
					render();
					timer = window.setInterval(render, 1000);
				}

				function pollDepositStatus(depositId, expiresInSeconds) {
					const deadline = Date.now() + Math.max(0, expiresInSeconds) * 1000;
					const timer = window.setInterval(() => {
						fetch('../Processing/Deposit_Status.php?id=' + encodeURIComponent(depositId), {
							credentials: 'same-origin', headers: {'Accept': 'application/json'}
						})
						.then(response => response.ok ? response.json() : null)
						.then(data => {
							if (data && data.status === 'paid') {
								window.clearInterval(timer);
								alert('Nạp tiền thành công. Số dư của bạn đã được cập nhật.');
								location.reload();
							} else if ((data && data.status === 'expired') || Date.now() >= deadline) {
								window.clearInterval(timer);
							}
						})
						.catch(() => {});
					}, 3000);
				}
				</script>
			</div>
			<div class="col-md-8">
				<div class="card p-3">
					<h5>Cập nhật thông tin</h5>
					<form action="../Processing/Update_Profile.php" method="post">
						<?php echo csrf_field(); ?>
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

		<div class="row g-4 mt-1 mb-5" id="customer-history">
			<div class="col-lg-6" id="chat-history">
				<div class="card personal-history-card">
					<div class="card-body p-4">
						<div class="d-flex align-items-center justify-content-between mb-3">
							<div>
								<h5 class="mb-1"><i class="far fa-comments me-2 text-success"></i>Lịch sử trò chuyện</h5>
								<div class="small text-muted">Mở lại từng cuộc trò chuyện với Mơ Shop.</div>
							</div>
							<span class="badge bg-light text-dark border"><?php echo count($currentChatSessions); ?> cuộc trò chuyện</span>
						</div>

						<div class="personal-history-list">
							<?php if (!$currentChatSessions): ?>
								<div class="text-center text-muted py-5">
									<i class="far fa-comment-dots fs-2 d-block mb-2"></i>
									Bạn chưa có cuộc trò chuyện nào.
								</div>
							<?php endif; ?>
							<?php foreach ($currentChatSessions as $chatSession):
								$messageCount = (int) $chatSession['message_count'];
								$title = personal_chat_title($chatSession);
								$preview = trim((string) ($chatSession['last_message'] ?? ''));
								$activityAt = $chatSession['last_message_at'] ?: $chatSession['created_at'];
								$isActiveChat = $activeChatSessionId === (string) $chatSession['session_id'];
							?>
								<div class="personal-history-item <?php echo $isActiveChat ? 'is-active-chat' : ''; ?>">
									<div class="personal-history-icon"><i class="far fa-comment"></i></div>
									<div class="personal-history-content">
										<div class="d-flex align-items-center gap-2 flex-wrap">
											<span class="fw-semibold text-dark"><?php echo personal_h($title); ?></span>
											<?php if ($isActiveChat): ?><span class="badge bg-success">Đang mở</span><?php endif; ?>
										</div>
										<p class="personal-history-preview"><?php echo personal_h($preview !== '' ? $preview : 'Chưa có tin nhắn'); ?></p>
										<div class="personal-history-meta mt-1">
											<?php echo $messageCount; ?> tin nhắn · <?php echo date('d/m/Y H:i', strtotime((string) $activityAt)); ?>
										</div>
									</div>
									<a class="btn btn-sm btn-outline-success personal-history-action" href="Personal.php?chat_session=<?php echo rawurlencode((string) $chatSession['session_id']); ?>#chat-history">
										Mở chat
									</a>
								</div>
							<?php endforeach; ?>
						</div>

						<?php if ($legacyChatSessions): ?>
							<details class="chat-archive">
								<summary>
									<span><i class="fas fa-box-archive me-2"></i>Lưu trữ trước khi có tính năng “Chat mới”</span>
									<span class="badge bg-secondary"><?php echo count($legacyChatSessions); ?> phiên kỹ thuật cũ</span>
								</summary>
								<div class="chat-archive-note">
									Đây là lịch sử được hệ thống cũ tự chia theo cookie hoặc trình duyệt, không phải các cuộc trò chuyện bạn chủ động tạo. Dữ liệu vẫn được giữ để bạn xem lại.
								</div>
								<div class="chat-archive-list">
									<?php foreach ($legacyChatSessions as $chatSession):
										$messageCount = (int) $chatSession['message_count'];
										$title = personal_chat_title($chatSession);
										$preview = trim((string) ($chatSession['last_message'] ?? ''));
										$activityAt = $chatSession['last_message_at'] ?: $chatSession['created_at'];
										$isActiveChat = $activeChatSessionId === (string) $chatSession['session_id'];
									?>
										<div class="personal-history-item <?php echo $isActiveChat ? 'is-active-chat' : ''; ?>">
											<div class="personal-history-icon"><i class="fas fa-box-archive"></i></div>
											<div class="personal-history-content">
												<div class="d-flex align-items-center gap-2 flex-wrap">
													<span class="fw-semibold text-dark"><?php echo personal_h($title); ?></span>
													<?php if ($isActiveChat): ?><span class="badge bg-success">Đang mở</span><?php endif; ?>
												</div>
												<p class="personal-history-preview"><?php echo personal_h($preview !== '' ? $preview : 'Chưa có tin nhắn'); ?></p>
												<div class="personal-history-meta mt-1"><?php echo $messageCount; ?> tin nhắn · <?php echo date('d/m/Y H:i', strtotime((string) $activityAt)); ?></div>
											</div>
											<a class="btn btn-sm btn-outline-secondary personal-history-action" href="Personal.php?chat_session=<?php echo rawurlencode((string) $chatSession['session_id']); ?>#chat-history">Xem lại</a>
										</div>
									<?php endforeach; ?>
								</div>
							</details>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="col-lg-6" id="consultation-history">
				<div class="card personal-history-card">
					<div class="card-body p-4">
						<div class="d-flex align-items-center justify-content-between mb-3">
							<div>
								<h5 class="mb-1"><i class="far fa-calendar-check me-2 text-warning"></i>Lịch tư vấn</h5>
								<div class="small text-muted">Theo dõi lịch tư vấn sắp tới và đã qua.</div>
							</div>
							<span class="badge bg-light text-dark border"><?php echo count($appointments); ?> lịch</span>
						</div>

						<div class="personal-history-list">
							<?php if (!$appointments): ?>
								<div class="text-center text-muted py-5">
									<i class="far fa-calendar fs-2 d-block mb-2"></i>
									Bạn chưa có lịch tư vấn nào.
								</div>
							<?php endif; ?>
							<?php foreach ($appointments as $appointment):
								$isUpcoming = strtotime((string) $appointment['appt_at']) >= time();
								$appointmentNote = trim((string) ($appointment['note'] ?: $appointment['message_content']));
							?>
								<div class="personal-history-item <?php echo $isUpcoming ? 'appointment-upcoming' : ''; ?>">
									<div class="personal-history-icon"><i class="far fa-calendar-alt"></i></div>
									<div class="personal-history-content">
										<div class="d-flex align-items-center gap-2 flex-wrap">
											<span class="fw-semibold text-dark"><?php echo date('d/m/Y H:i', strtotime((string) $appointment['appt_at'])); ?></span>
											<span class="badge <?php echo $isUpcoming ? 'bg-warning text-dark' : 'bg-secondary'; ?>">
												<?php echo $isUpcoming ? 'Sắp tới' : 'Đã qua'; ?>
											</span>
										</div>
										<p class="personal-history-preview"><?php echo personal_h($appointmentNote !== '' ? $appointmentNote : 'Lịch tư vấn cùng nhân viên Mơ Shop'); ?></p>
										<div class="personal-history-meta mt-1">Đặt ngày <?php echo date('d/m/Y', strtotime((string) $appointment['created_at'])); ?></div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
