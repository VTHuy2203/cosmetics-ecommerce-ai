<?php
include("../Layouts/headHTML.php");

include("../DB_Connect.php");

// CRITICAL: For staff (employee role via admin login), show only newest orders
// Admin and Manager see all orders
$admin_role = $_SESSION['admin_role'] ?? null;
$is_staff = ($admin_role === 'employee');

if ($is_staff) {
    // Staff only sees newest orders (last 30 days, ordered by newest first)
    $sql = "SELECT * FROM donhang 
            WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY NgayTao DESC";
} else {
    // Admin and Manager see all orders
    $sql = "SELECT * FROM donhang ORDER BY NgayTao DESC";
}

$result = mysqli_query($conn, $sql);

// Kiểm tra nếu có lỗi trong quá trình truy vấn
if (!$result) {
	die("Có lỗi xảy ra: " . mysqli_error($conn));
}

function all_orders_h($value): string
{
	return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$order_count = mysqli_num_rows($result);
?>

<style>
	.orders-page-heading {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		gap: 1rem;
		margin-bottom: 1rem;
	}

	.orders-page-heading p {
		margin: .3rem 0 0;
		font-size: .88rem;
	}

	.orders-count {
		white-space: nowrap;
		font-size: .82rem;
		font-weight: 600;
	}

	.orders-table {
		min-width: 1320px;
		table-layout: fixed;
		margin-bottom: 0;
	}

	.orders-table thead th {
		background: #f8fafc;
		border-bottom-width: 2px;
		color: #4a5568;
		font-size: .76rem;
		font-weight: 700;
		letter-spacing: .025em;
		text-transform: uppercase;
		vertical-align: middle;
		white-space: nowrap;
	}

	.orders-table tbody td {
		padding: .65rem .55rem;
		color: #5a6275;
		font-size: .84rem;
		line-height: 1.45;
		vertical-align: middle;
	}

	.orders-table tbody tr:nth-child(even) {
		background: #fbfcfe;
	}

	.orders-table tbody tr:hover {
		background: #f2f8f8;
	}

	.orders-table th:nth-child(1) { width: 55px; }
	.orders-table th:nth-child(2) { width: 155px; }
	.orders-table th:nth-child(3) { width: 90px; }
	.orders-table th:nth-child(4) { width: 125px; }
	.orders-table th:nth-child(5) { width: 125px; }
	.orders-table th:nth-child(6) { width: 115px; }
	.orders-table th:nth-child(7) { width: 110px; }
	.orders-table th:nth-child(8) { width: 300px; }
	.orders-table th:nth-child(9) { width: 115px; }
	.orders-table th:nth-child(10) { width: 190px; }

	.orders-table form {
		margin: 0;
	}

	.orders-table .btn {
		border-radius: .4rem;
		font-size: .78rem;
		line-height: 1.25;
		padding: .42rem .55rem;
		white-space: normal;
	}

	.orders-table .badge {
		display: inline-block;
		font-size: .72rem;
		line-height: 1.3;
		padding: .35rem .48rem;
		white-space: normal;
	}

	.order-summary,
	.order-address {
		display: -webkit-box;
		overflow: hidden;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
	}

	.order-money {
		color: #2d3748;
		font-weight: 700;
		white-space: nowrap;
	}

	.order-customer {
		color: #2d3748;
		font-weight: 600;
	}

	@media (max-width: 767.98px) {
		.orders-page-heading {
			align-items: flex-start;
			flex-direction: column;
		}
	}
</style>

<body id="page-top">
	<!-- Page Wrapper -->
	<div id="wrapper">

		<!-- Sidebar -->
		<?php include("../Layouts/SlideBar.php"); ?>
		<!-- End of Sidebar -->

		<!-- Content Wrapper -->
		<div id="content-wrapper" class="d-flex flex-column">

			<!-- Main Content -->
			<div id="content">

				<!-- Topbar -->
				<?php include("../Layouts/TopBar.php"); ?>
				<!-- End of Topbar -->

				<!-- Begin Page Content -->
				<div class="container-fluid">

					<!-- Page Heading -->
					<div class="orders-page-heading">
						<div>
							<h1 class="h3 mb-0 text-gray-800">Tất Cả Đơn Hàng</h1>
							<p class="text-muted">Theo dõi trạng thái, thanh toán và xử lý đơn hàng.</p>
						</div>
						<span class="badge badge-light border orders-count"><?php echo number_format($order_count, 0, ',', '.'); ?> đơn hàng</span>
					</div>

					<!-- DataTales Example -->
					<div class="card shadow mb-4">
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-bordered orders-table" id="dataTable" width="100%" cellspacing="0">
									<thead>
										<tr>
											<th scope="col">STT</th>
											<th scope="col">Trạng thái</th>
											<th scope="col">Chi tiết</th>
											<th scope="col">Thao tác</th>
											<th scope="col">Thanh toán</th>
											<th scope="col">Khách hàng</th>
											<th scope="col">SĐT</th>
											<th scope="col">Thông tin đơn</th>
											<th scope="col">Tổng tiền</th>
											<th scope="col">Địa chỉ</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$stt = 1;
										// CRITICAL: Use ONLY admin session variables (completely ignore UI session)
										// Staff access is determined by admin_role via Admin_Login.php
										$admin_role = $_SESSION['admin_role'] ?? null;
										$is_staff_via_admin = ($admin_role === 'employee');
										$is_admin_or_manager = ($admin_role === 'admin' || $admin_role === 'manager');
										
										while ($row = mysqli_fetch_array($result)) {
											$ghi_chu = $row['GhiChu'] ?? '';
											$is_canceled = ($row['TrangThai'] == -1) || (stripos($ghi_chu, '[ĐÃ HỦY]') !== false);
											$is_customer_refused = (stripos($ghi_chu, '[KHÁCH HÀNG TỪ CHỐI]') !== false);
											$returned_to_shop = (stripos($ghi_chu, '[ĐÃ TRẢ VỀ CỬA HÀNG]') !== false);
											
											echo '<tr>';
											echo ' <td class="text-center">' . $stt . '</td>';
											
											// Check if order is canceled or customer refused
											if ($row['TrangThai'] == -1 || $is_canceled) {
												if ($is_customer_refused) {
													echo ' <td class="text-center"><span class="badge badge-danger">Đơn đã bị hủy (Khách từ chối)</span></td>';
												} else {
													echo ' <td class="text-center"><span class="badge badge-danger">Đơn đã bị hủy</span></td>';
												}
											} else if ($row['TrangThai'] == 0) {
												// Staff (via admin login), Admin, and Manager can approve orders
												if ($is_admin_or_manager || $is_staff_via_admin) {
											echo '<td class="text-center">' . admin_order_transition_form((int) $row['ID_DonHang'], 'approve', 'Duyệt', 'btn-danger', 'all') . '</td>';
												} else {
													echo ' <td class="text-center"><span class="text-muted">Cần Duyệt</span></td>';
												}
											} else if ($row['TrangThai'] == 1) {
												// Only Admin/Manager can prepare orders (not staff)
												if ($is_admin_or_manager) {
											echo '<td class="text-center">' . admin_order_transition_form((int) $row['ID_DonHang'], 'prepare', 'Chuẩn bị', 'btn-info', 'all') . '</td>';
												} else {
													echo ' <td class="text-center"><span class="text-muted">Chuẩn Bị</span></td>';
												}
											} else if ($row['TrangThai'] == 2) {
												// Check if customer refused - don't show "Out for Delivery" if refused
												if ($is_customer_refused) {
													echo ' <td class="text-center"><span class="badge badge-danger">Đơn đã bị hủy (Khách từ chối)</span></td>';
												} else {
													// Only Admin/Manager can mark as shipping (not staff)
													if ($is_admin_or_manager) {
												echo '<td class="text-center">' . admin_order_transition_form((int) $row['ID_DonHang'], 'shipping', 'Hoàn tất giao', 'btn-warning', 'all') . '</td>';
													} else {
														echo ' <td class="text-center"><span class="text-muted">Đang Giao</span></td>';
													}
												}
											} else if ($row['TrangThai'] == 3) {
											echo ' <td class="text-center"><span class="badge badge-success">Giao thành công</span></td>';
											}
											echo ' <td class="text-center"><a href="View_Oder_Detail.php?oder_id=' . (int) $row['ID_DonHang'] . '&view=all" class="btn btn-sm btn-primary"><i class="fas fa-eye mr-1"></i>Chi tiết</a></td>';
											
											// Show action buttons based on role and order status
											echo ' <td class="text-center">';
											
											// If customer refused, show "Returned to Shop" buttons for Staff/Admin
											if ($is_customer_refused) {
												if ($is_admin_or_manager || $is_staff_via_admin) {
												if (!$returned_to_shop) {
														echo '<form method="post" action="Staff_Canceled_Orders.php" class="d-inline" onsubmit="return confirm(\'Xác nhận shipper đã trả hàng về cửa hàng?\');">' . csrf_field() . '<input type="hidden" name="mark_returned" value="' . (int) $row['ID_DonHang'] . '"><input type="hidden" name="status" value="returned"><button type="submit" class="btn btn-sm btn-success mb-1"><i class="fas fa-check-circle"></i> Đã hoàn về shop</button></form>';
													} else {
															echo ' <span class="badge badge-success">Đã hoàn về shop</span>';
														// Don't show "Not Yet Returned" button when already returned
													}
												} else {
													echo ' <span class="text-muted">-</span>';
												}
											} else {
												// Only Admin/Manager can cancel orders (not staff)
												if ($is_admin_or_manager) {
											echo admin_cancel_order_form((int) $row['ID_DonHang'], 'all');
												} else {
													echo ' <span class="text-muted">-</span>';
												}
											}
											echo '</td>';
											$paid = isset($row['DaThanhToan']) && intval($row['DaThanhToan'])==1;
											$paymentMethod = (string) ($row['PhuongThucThanhToan'] ?? 'cod');
											$paymentStatus = (string) ($row['TrangThaiThanhToan'] ?? '');
											$paymentNotExpired = empty($row['HanThanhToan']) || strtotime((string) $row['HanThanhToan']) >= time();
											$canConfirmPayment = ($paymentMethod === 'cod' && (int) $row['TrangThai'] === 3)
												|| ($paymentMethod === 'qr' && (int) $row['TrangThai'] === 0 && $paymentStatus === 'pending' && $paymentNotExpired);
											echo '<td class="text-center">' . ($paid
												? '<span class="badge badge-success">Đã thanh toán</span>'
												: ($canConfirmPayment ? admin_mark_paid_form((int) $row['ID_DonHang']) : '<span class="badge badge-warning">Chưa thanh toán</span>')) . '</td>';
											$customer_name = trim((string) ($row['HoVaTen'] ?? ''));
											$phone = trim((string) ($row['SDT'] ?? ''));
											$note = trim((string) ($row['GhiChu'] ?? ''));
											$address = trim((string) ($row['DiaChi'] ?? ''));
											$total = (float) ($row['TongTien'] ?? 0);

											echo ' <td><span class="order-customer">' . all_orders_h($customer_name !== '' ? $customer_name : '—') . '</span></td>';
											echo ' <td class="text-nowrap">' . all_orders_h($phone !== '' ? $phone : '—') . '</td>';
											echo ' <td><div class="order-summary" title="' . all_orders_h($note) . '">' . all_orders_h($note !== '' ? $note : 'Không có ghi chú') . '</div></td>';
											echo ' <td><span class="order-money">' . number_format($total, 0, ',', '.') . ' đ</span></td>';
											echo ' <td><div class="order-address" title="' . all_orders_h($address) . '">' . all_orders_h($address !== '' ? $address : '—') . '</div></td>';
											echo '</tr>';
											$stt++;
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

				</div>
				<!-- /.container-fluid -->

			</div>
			<!-- End of Main Content -->
			
			<script>
			// Real-time order list update functions for WebSocket
			window.highlightNewOrder = function(orderId) {
				// Find the order row and highlight it
				const rows = document.querySelectorAll('#dataTable tbody tr');
				rows.forEach(row => {
					const viewLink = row.querySelector('a[href*="View_Oder_Detail"]');
					if (viewLink && viewLink.href.includes('oder_id=' + orderId)) {
						row.style.backgroundColor = '#fff3cd';
						row.style.transition = 'background-color 0.3s';
						row.style.animation = 'pulse 2s ease-in-out';
						setTimeout(() => {
							row.style.backgroundColor = '';
							row.style.animation = '';
						}, 5000);
					}
				});
			};
			
			window.refreshOrderList = function() {
				// Reload the order list table
				location.reload();
			};
			
			window.updateOrderStatus = function(orderId, status, statusText) {
				// Update order status in the table
				const rows = document.querySelectorAll('#dataTable tbody tr');
				rows.forEach(row => {
					const viewLink = row.querySelector('a[href*="View_Oder_Detail"]');
					if (viewLink && viewLink.href.includes('oder_id=' + orderId)) {
						const statusCell = row.cells[1]; // Status is in second column
						if (statusCell) {
							statusCell.innerHTML = '<span class="badge badge-info">' + statusText + '</span>';
							row.style.backgroundColor = '#d1ecf1';
							setTimeout(() => {
								row.style.backgroundColor = '';
							}, 3000);
						}
					}
				});
			};
			
			// Add pulse animation CSS
			if (!document.getElementById('realtime-order-styles')) {
				const style = document.createElement('style');
				style.id = 'realtime-order-styles';
				style.textContent = `
					@keyframes pulse {
						0%, 100% { opacity: 1; }
						50% { opacity: 0.7; }
					}
				`;
				document.head.appendChild(style);
			}
			
			// Display error messages if any
			<?php
			if (isset($_GET['error'])) {
				$error = urldecode($_GET['error']);
				if ($error === 'unauthorized') {
					echo "alert('Bạn không có quyền thực hiện thao tác này. Chỉ Admin và Manager mới có thể hủy đơn hàng.');";
				} else if ($error === 'missing_params') {
					echo "alert('Thiếu thông tin cần thiết để xóa đơn hàng.');";
				} else if (strpos($error, 'Không thể xóa') !== false || strpos($error, 'Không tìm thấy') !== false || strpos($error, 'không tồn tại') !== false) {
					echo "alert('" . addslashes($error) . "');";
				} else {
					echo "alert('Lỗi: " . addslashes($error) . "');";
				}
			}
			?>
			</script>
			<!-- Scroll to Top Button-->
			<a class="scroll-to-top rounded" href="#page-top">
				<i class="fas fa-angle-up"></i>
			</a>
		</div>
		<!-- End of Page Wrapper -->
		<?php include("../Layouts/Link.php"); ?>
</body>

</html>
