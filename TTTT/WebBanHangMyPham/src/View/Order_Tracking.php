<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");

if (!isset($_SESSION['user'])) {
	header('Location: Login.php');
	exit();
}

$sql_inf_user = "select * from khachhang where TenDangNhap = '" . $_SESSION['user'] . "'";
$result_user = mysqli_query($conn, $sql_inf_user);
$row_user = mysqli_fetch_array($result_user);
$idKH = $row_user['ID_KhachHang'];

$sql = "SELECT * FROM donhang WHERE ID_KhachHang = '$idKH' ORDER BY ID_DonHang DESC";
$result = mysqli_query($conn, $sql);

// Check for delivered orders with unrated products
// Exclude products with approved returns
$sql_unrated = "SELECT DISTINCT d.ID_DonHang, dct.ID_SanPham, sp.TenSanPham, d.GhiChu
                FROM donhang d
                INNER JOIN donhangchitiet dct ON d.ID_DonHang = dct.ID_DonHang
                INNER JOIN sanpham sp ON dct.ID_SanPham = sp.IDSanPham
                LEFT JOIN danhgia dg ON dg.ID_KhachHang = '$idKH' AND dg.ID_SanPham = dct.ID_SanPham
                WHERE d.ID_KhachHang = '$idKH' 
                AND d.TrangThai = 3
                AND dg.ID_DanhGia IS NULL
                ORDER BY d.ID_DonHang DESC";
$result_unrated = mysqli_query($conn, $sql_unrated);
$unrated_products = [];
$unrated_orders = [];
if ($result_unrated && mysqli_num_rows($result_unrated) > 0) {
    while ($row_unrated = mysqli_fetch_array($result_unrated)) {
        $order_id = $row_unrated['ID_DonHang'];
        $product_id = $row_unrated['ID_SanPham'];
        $ghi_chu = $row_unrated['GhiChu'] ?? '';
        
        // Skip if order is returned
        if (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false) {
            continue;
        }
        
        // Check if product has approved return request
        $sql_check_return = "SELECT * FROM return_refund_requests 
                            WHERE ID_DonHang = $order_id 
                            AND ID_KhachHang = '$idKH' 
                            AND ID_SanPham = $product_id 
                            AND TrangThai = 'approved'";
        $result_check_return = mysqli_query($conn, $sql_check_return);
        if ($result_check_return && mysqli_num_rows($result_check_return) > 0) {
            continue; // Skip products with approved returns
        }
        
        if (!isset($unrated_orders[$order_id])) {
            $unrated_orders[$order_id] = [];
        }
        $unrated_orders[$order_id][] = [
            'ID_SanPham' => $product_id,
            'TenSanPham' => $row_unrated['TenSanPham']
        ];
    }
}
?>

<body class="bg-light">
	<?php include("../Layouts/Banner_Header.php"); ?>
	<?php include("../Layouts/Header_View.php"); ?>
	<?php include("../Layouts/Navbar.php"); ?>
	<?php include("../Layouts/Link.php"); ?>
	
	<div class="container my-5">
		<div class="row">
			<div class="col-12">
				<div class="d-flex justify-content-between align-items-center mb-4">
					<h2 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Theo Dõi Đơn Hàng</h2>
					<a href="My_Return_Requests.php" class="btn btn-info">
						<i class="fas fa-undo me-2"></i>Yêu Cầu Hoàn Trả Của Tôi
					</a>
				</div>
				
				<?php if (count($unrated_orders) > 0): ?>
					<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
						<h5 class="alert-heading"><i class="fas fa-star me-2"></i>Đánh Giá Sản Phẩm</h5>
						<p class="mb-2">Bạn có <strong><?php echo count($unrated_orders); ?> đơn hàng</strong> đã được giao thành công nhưng chưa đánh giá sản phẩm.</p>
						<p class="mb-2">Vui lòng đánh giá sản phẩm để giúp chúng tôi cải thiện dịch vụ!</p>
						<ul class="mb-0">
							<?php foreach ($unrated_orders as $order_id => $products): ?>
								<li>
									Đơn hàng <strong>#<?php echo $order_id; ?></strong>: 
									<?php 
									$product_names = array_map(function($p) {
										return '<a href="Detail_Product.php?product_id=' . $p['ID_SanPham'] . '" class="alert-link">' . htmlspecialchars($p['TenSanPham']) . '</a>';
									}, $products);
									echo implode(', ', $product_names);
									?>
								</li>
							<?php endforeach; ?>
						</ul>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php endif; ?>
				
				<?php if (mysqli_num_rows($result) > 0): ?>
					<div class="card shadow-sm border-0 rounded-4">
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-hover mb-0">
									<thead class="table-light">
										<tr>
											<th>Mã ĐH</th>
											<th>Ngày Đặt</th>
											<th>Sản Phẩm</th>
											<th>Tổng Tiền</th>
											<th>Trạng Thái</th>
											<th>Thanh Toán</th>
											<th>Thao Tác</th>
										</tr>
									</thead>
									<tbody>
										<?php 
										mysqli_data_seek($result, 0);
										while ($row = mysqli_fetch_array($result)): 
											// Get order details
											$order_id = $row['ID_DonHang'];
											$sql_details = "SELECT dct.ID_SanPham, dct.SoLuong, sp.TenSanPham, sp.AnhSanPham 
															FROM donhangchitiet dct 
															INNER JOIN sanpham sp ON dct.ID_SanPham = sp.IDSanPham 
															WHERE dct.ID_DonHang = '$order_id' 
															LIMIT 3";
											$result_details = mysqli_query($conn, $sql_details);
											$product_count = mysqli_num_rows($result_details);
											
											// Get total products count
											$sql_total = "SELECT COUNT(*) as total FROM donhangchitiet WHERE ID_DonHang = '$order_id'";
											$result_total = mysqli_query($conn, $sql_total);
											$row_total = mysqli_fetch_array($result_total);
											$total_products = intval($row_total['total']);
											
											$st = intval($row['TrangThai']);
											
											// Determine payment method from GhiChu
											$ghi_chu = isset($row['GhiChu']) ? $row['GhiChu'] : '';
											
											// Check if order is cancelled (status = -1 or GhiChu contains [ĐÃ HỦY])
											$is_cancelled = ($st == -1) || (stripos($ghi_chu, '[ĐÃ HỦY]') !== false);
											if ($is_cancelled) {
												$status_text = 'Đã hủy';
												$status_class = 'danger';
											} else {
												// Check if order has been returned/refunded
												$is_returned = (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false);
												if (!$is_returned) {
													// Check if there's an approved return/refund request
													$sql_check_return = "SELECT * FROM return_refund_requests WHERE ID_DonHang = $order_id AND ID_KhachHang = $idKH AND TrangThai = 'approved' LIMIT 1";
													$result_check_return = mysqli_query($conn, $sql_check_return);
													$is_returned = ($result_check_return && mysqli_num_rows($result_check_return) > 0);
												}
												
												if ($is_returned) {
													$status_text = 'Đã hoàn trả';
													$status_class = 'warning';
												} else {
													$status_text = ($st==0? 'Cần duyệt' : ($st==1? 'Chuẩn bị' : ($st==2? 'Đang giao' : 'Giao thành công')));
													$status_class = ($st==0? 'warning' : ($st==1? 'info' : ($st==2? 'primary' : 'success')));
												}
											}
											
											// Check if order can be cancelled
											$id_shipper = isset($row['ID_Shipper']) ? intval($row['ID_Shipper']) : 0;
											$can_cancel = !$is_cancelled && (($st == 0) || ($st == 1 && ($id_shipper == 0 || is_null($row['ID_Shipper']))));
											$payment_method = 'cod'; // default
											if (stripos($ghi_chu, 'Thanh toán bằng số dư tài khoản') !== false) {
												$payment_method = 'balance';
											} elseif (stripos($ghi_chu, 'QR:') !== false) {
												$payment_method = 'qr';
											}
											
											// Get order date
											$order_date = '';
											if (isset($row['NgayTao'])) {
												$order_date = date('d/m/Y', strtotime($row['NgayTao']));
											} else {
												// Try to get from other date fields
												$order_date = date('d/m/Y');
											}
										?>
											<tr>
												<td><strong>#<?php echo $order_id; ?></strong></td>
												<td><?php echo $order_date; ?></td>
												<td>
													<div class="d-flex align-items-center">
														<?php 
														$detail_count = 0;
														mysqli_data_seek($result_details, 0);
														while (($detail = mysqli_fetch_array($result_details)) && $detail_count < 2): 
															$detail_count++;
														?>
															<img src="../../../AdminManage/src/Products/<?php echo $detail['AnhSanPham']; ?>" 
																 alt="" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
														<?php endwhile; ?>
														<div>
															<small class="text-muted">
																<?php 
																mysqli_data_seek($result_details, 0);
																$first_detail = mysqli_fetch_array($result_details);
																if ($first_detail) {
																	$first_product_id = $first_detail['ID_SanPham'];
																	$first_product_name = htmlspecialchars($first_detail['TenSanPham']);
																	echo '<a href="Detail_Product.php?product_id=' . $first_product_id . '" class="text-decoration-none text-dark fw-semibold">' . $first_product_name . '</a>';
																	if ($total_products > 1) {
																		echo ' và ' . ($total_products - 1) . ' sản phẩm khác';
																	}
																}
																?>
															</small>
														</div>
													</div>
												</td>
												<td><strong><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> đ</strong></td>
												<td>
													<span class="badge bg-<?php echo $status_class; ?>">
														<?php echo $status_text; ?>
													</span>
												</td>
												<td>
													<?php if (isset($row['DaThanhToan']) && intval($row['DaThanhToan'])==1): ?>
														<span class="badge bg-success">Đã thanh toán</span>
													<?php else: ?>
														<span class="badge bg-warning">Chưa thanh toán</span>
													<?php endif; ?>
												</td>
												<td>
													<button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetail(<?php echo $order_id; ?>)">
														<i class="fas fa-eye"></i> Chi tiết
													</button>
													<?php if ($can_cancel): ?>
														<button class="btn btn-sm btn-outline-danger ms-1" onclick="openCancelModal(<?php echo $order_id; ?>, '<?php echo $payment_method; ?>', <?php echo $st == 1 && $id_shipper > 0 ? 'true' : 'false'; ?>, <?php echo isset($row['DaThanhToan']) && intval($row['DaThanhToan']) == 1 ? 'true' : 'false'; ?>, <?php echo $row['TongTien']; ?>)">
															<i class="fas fa-times"></i> Hủy đơn
														</button>
													<?php endif; ?>
													<?php 
													// Show "Request Return and Refund" or "Buy Again" button for successfully delivered and paid orders
													$is_delivered_and_paid = ($st == 3 && isset($row['DaThanhToan']) && intval($row['DaThanhToan']) == 1);
													if ($is_delivered_and_paid): 
														// Check if order has been returned (either via approved refund request or marked in GhiChu)
														$is_returned = (stripos($ghi_chu, '[ĐÃ HOÀN TRẢ]') !== false);
														
														// Also check if there's an approved return/refund request for this order
														$sql_check_approved = "SELECT * FROM return_refund_requests WHERE ID_DonHang = $order_id AND ID_KhachHang = $idKH AND TrangThai = 'approved'";
														$result_check_approved = mysqli_query($conn, $sql_check_approved);
														$has_approved_refund = ($result_check_approved && mysqli_num_rows($result_check_approved) > 0);
														
														// Show "Buy Again" if order has been returned/refunded
														if ($is_returned || $has_approved_refund):
															// Get first product from this order for "Buy Again"
															// Use the already fetched result_details if available, otherwise query
															$buy_again_product_id = null;
															if (isset($first_detail) && isset($first_detail['ID_SanPham'])) {
																$buy_again_product_id = intval($first_detail['ID_SanPham']);
															} else {
																$sql_order_products = "SELECT dct.ID_SanPham 
																						FROM donhangchitiet dct 
																						WHERE dct.ID_DonHang = $order_id 
																						LIMIT 1";
																$result_order_products = mysqli_query($conn, $sql_order_products);
																if ($result_order_products && mysqli_num_rows($result_order_products) > 0) {
																	$first_product = mysqli_fetch_array($result_order_products);
																	$buy_again_product_id = intval($first_product['ID_SanPham']);
																}
															}
															
															if ($buy_again_product_id):
													?>
																<a href="Detail_Product.php?product_id=<?php echo $buy_again_product_id; ?>" class="btn btn-sm btn-success ms-1">
																	<i class="fas fa-shopping-cart"></i> Mua lại
																</a>
													<?php 
															endif;
														else:
															// Show "Request Return and Refund" button if order hasn't been returned
													?>
															<a href="Return_Refund_Request.php?order_id=<?php echo $order_id; ?>" class="btn btn-sm btn-warning ms-1">
																<i class="fas fa-undo"></i> Hoàn hàng trả tiền
															</a>
													<?php 
														endif;
													endif; 
													?>
												</td>
											</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				<?php else: ?>
					<div class="card shadow-sm border-0 rounded-4">
						<div class="card-body text-center py-5">
							<i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
							<h4 class="text-muted">Bạn chưa có đơn hàng nào</h4>
							<p class="text-muted">Hãy bắt đầu mua sắm ngay để có những sản phẩm mỹ phẩm tuyệt vời!</p>
							<a href="index.php" class="btn btn-primary mt-3">
								<i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	
	<!-- Order Detail Modal -->
	<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="orderDetailModalLabel">Chi Tiết Đơn Hàng</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body" id="orderDetailContent">
					<div class="text-center">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading...</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Cancel Order Modal -->
	<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header bg-danger text-white">
					<h5 class="modal-title" id="cancelOrderModalLabel">
						<i class="fas fa-exclamation-triangle me-2"></i>Hủy Đơn Hàng
					</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div id="cancelOrderContent">
						<!-- Content will be dynamically loaded -->
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- QR Refund Confirmation Modal -->
	<div class="modal fade" id="qrRefundModal" tabindex="-1" aria-labelledby="qrRefundModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header bg-warning">
					<h5 class="modal-title" id="qrRefundModalLabel">
						<i class="fas fa-exclamation-triangle me-2"></i>Xác Nhận Hoàn Tiền
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="alert alert-warning">
						<p class="mb-0"><strong>Lưu ý:</strong> Số tiền bạn đã thanh toán qua QR code sẽ được cộng vào số dư tài khoản của bạn.</p>
					</div>
					<div class="d-flex justify-content-end gap-2 mt-3">
						<button type="button" class="btn btn-secondary" onclick="$('#qrRefundModal').modal('hide')">
							Không đồng ý
						</button>
						<button type="button" class="btn btn-primary" id="confirmQrRefundBtn">
							Đồng ý
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		let currentCancelOrderId = null;
		let currentCancelPaymentMethod = null;
		let currentCancelReason = '';
		
		function viewOrderDetail(orderId) {
			$('#orderDetailModal').modal('show');
			$('#orderDetailContent').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
			
			$.ajax({
				url: '../Processing/Get_Order_Detail.php',
				type: 'GET',
				data: { order_id: orderId },
				success: function(response) {
					$('#orderDetailContent').html(response);
				},
				error: function() {
					$('#orderDetailContent').html('<div class="alert alert-danger">Không thể tải chi tiết đơn hàng. Vui lòng thử lại sau.</div>');
				}
			});
		}
		
		function openCancelModal(orderId, paymentMethod, shipperAccepted, isPaid, orderAmount) {
			currentCancelOrderId = orderId;
			currentCancelPaymentMethod = paymentMethod;
			
			// Check if COD and shipper has accepted
			if (paymentMethod === 'cod' && shipperAccepted) {
				$('#cancelOrderContent').html(`
					<div class="alert alert-warning">
						<i class="fas fa-info-circle me-2"></i>
						Đơn hàng đang được giao và không thể hủy tại thời điểm này.
					</div>
				`);
				$('#cancelOrderModal').modal('show');
				return;
			}
			
			// Check if QR Code and payment is confirmed
			if (paymentMethod === 'qr' && isPaid) {
				// Show QR refund confirmation first
				$('#qrRefundModal').modal('show');
				$('#confirmQrRefundBtn').off('click').on('click', function() {
					$('#qrRefundModal').modal('hide');
					showCancelReasonForm(orderId, paymentMethod, orderAmount, true, isPaid);
				});
				$('#qrRefundModal .btn-secondary').off('click').on('click', function() {
					$('#qrRefundModal').modal('hide');
					showCancelReasonForm(orderId, paymentMethod, orderAmount, false, isPaid);
				});
				return;
			}
			
			// Show cancel reason form
			showCancelReasonForm(orderId, paymentMethod, orderAmount, false, isPaid);
		}
		
		function showCancelReasonForm(orderId, paymentMethod, orderAmount, qrRefundAgreed, isPaid) {
			let refundInfo = '';
			if (paymentMethod === 'balance') {
				refundInfo = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Số tiền đã thanh toán sẽ được hoàn lại vào số dư tài khoản của bạn.</div>';
			} else if (paymentMethod === 'qr' && qrRefundAgreed) {
				refundInfo = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Số tiền đã thanh toán qua QR code sẽ được cộng vào số dư tài khoản của bạn.</div>';
			} else if (paymentMethod === 'qr' && !qrRefundAgreed && isPaid) {
				refundInfo = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Bạn có thể từ chối nhận hàng và hoàn tiền sẽ được trả lại bằng tiền mặt bởi Shipper.</div>';
			} else if (paymentMethod === 'qr' && !isPaid) {
				refundInfo = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Đơn hàng chưa được thanh toán.</div>';
			}
			
			$('#cancelOrderContent').html(`
				${refundInfo}
				<form id="cancelOrderForm">
					<input type="hidden" id="cancelOrderId" value="${orderId}">
					<input type="hidden" id="cancelPaymentMethod" value="${paymentMethod}">
					<input type="hidden" id="cancelQrRefundAgreed" value="${qrRefundAgreed ? '1' : '0'}">
					<div class="mb-3">
						<label for="cancelReason" class="form-label">Lý do hủy đơn hàng <span class="text-danger">*</span></label>
						<textarea class="form-control" id="cancelReason" rows="4" placeholder="Vui lòng nhập lý do hủy đơn hàng..." required></textarea>
					</div>
					<div class="d-flex justify-content-end gap-2">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
						<button type="submit" class="btn btn-danger">
							<i class="fas fa-times me-2"></i>Xác nhận hủy đơn
						</button>
					</div>
				</form>
			`);
			
			$('#cancelOrderForm').on('submit', function(e) {
				e.preventDefault();
				currentCancelReason = $('#cancelReason').val();
				if (!currentCancelReason.trim()) {
					alert('Vui lòng nhập lý do hủy đơn hàng.');
					return;
				}
				submitCancelOrder(orderId, paymentMethod, currentCancelReason, qrRefundAgreed);
			});
			
			$('#cancelOrderModal').modal('show');
		}
		
		function submitCancelOrder(orderId, paymentMethod, reason, qrRefundAgreed) {
			$.ajax({
				url: '../Processing/Cancel_Order.php',
				type: 'POST',
				data: {
					order_id: orderId,
					payment_method: paymentMethod,
					cancel_reason: reason,
					qr_refund_agreed: qrRefundAgreed ? '1' : '0'
				},
				success: function(response) {
					if (response.trim() === 'success') {
						$('#cancelOrderModal').modal('hide');
						alert('Đơn hàng đã được hủy thành công!');
						location.reload();
					} else {
						alert('Lỗi: ' + response);
					}
				},
				error: function() {
					alert('Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại sau.');
				}
			});
		}
	</script>
	
	<?php include("../Layouts/Footer.php"); ?>
</body>
</html>

