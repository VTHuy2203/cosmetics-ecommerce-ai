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
?>

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
					<h1 class="h3 mb-2 text-gray-800">Tất Cả Đơn Hàng</h1>

					<!-- DataTales Example -->
					<div class="card shadow mb-4">
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
									<thead>
										<tr>
											<th>STT</th>
											<th>Trạng Thái</th>
											<th>Xem Chi Tiết</th>
											<th>Hủy Đơn</th>
											<th>Thanh Toán</th>
											<th>Tên Khách Hàng</th>
											<th>SDT</th>
											<th>Ghi Chú</th>
											<th>Tổng Tiền</th>
											<th>Địa Chỉ</th>
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
											echo ' <td>' . $stt . '</td>';
											
											// Check if order is canceled or customer refused
											if ($row['TrangThai'] == -1 || $is_canceled) {
												if ($is_customer_refused) {
													echo ' <td class="text-center"><span class="badge bg-danger">Đơn đã bị hủy (Khách từ chối)</span></td>';
												} else {
													echo ' <td class="text-center"><span class="badge bg-danger">Đơn đã bị hủy</span></td>';
												}
											} else if ($row['TrangThai'] == 0) {
												// Staff (via admin login), Admin, and Manager can approve orders
												if ($is_admin_or_manager || $is_staff_via_admin) {
													echo ' <td class="text-center"> <a href="Approve_Oders.php?oder_id='.$row['ID_DonHang'].'&approve=all" class="btn btn-danger">Cần Duyệt</a>' . '</td>';
												} else {
													echo ' <td class="text-center"><span class="text-muted">Cần Duyệt</span></td>';
												}
											} else if ($row['TrangThai'] == 1) {
												// Only Admin/Manager can prepare orders (not staff)
												if ($is_admin_or_manager) {
													echo ' <td class="text-center"> <a href="Prepare_Oders.php?oder_id='.$row['ID_DonHang'].'&prepare=all" class="btn btn-info">Chuẩn Bị</a>' . '</td>';
												} else {
													echo ' <td class="text-center"><span class="text-muted">Chuẩn Bị</span></td>';
												}
											} else if ($row['TrangThai'] == 2) {
												// Check if customer refused - don't show "Out for Delivery" if refused
												if ($is_customer_refused) {
													echo ' <td class="text-center"><span class="badge bg-danger">Đơn đã bị hủy (Khách từ chối)</span></td>';
												} else {
													// Only Admin/Manager can mark as shipping (not staff)
													if ($is_admin_or_manager) {
														echo ' <td class="text-center"> <a href="Shipping_Oders.php?oder_id='.$row['ID_DonHang'].'&shipping=all" class="btn btn-warning">Đang Giao</a>' . '</td>';
													} else {
														echo ' <td class="text-center"><span class="text-muted">Đang Giao</span></td>';
													}
												}
											} else if ($row['TrangThai'] == 3) {
												echo ' <td class="text-center"> <a class="btn btn-success">Giao Thành Công</a>' . '</td>';
											}
											echo ' <td class="text-center"> <a href="View_Oder_Detail.php?oder_id='.$row['ID_DonHang'].'&view=all" class="btn btn-primary">Xem Chi Tiết</a>' . '</td>';
											
											// Show action buttons based on role and order status
											echo ' <td class="text-center">';
											
											// If customer refused, show "Returned to Shop" buttons for Staff/Admin
											if ($is_customer_refused) {
												if ($is_admin_or_manager || $is_staff_via_admin) {
													if (!$returned_to_shop) {
														echo ' <a href="Staff_Canceled_Orders.php?mark_returned='.$row['ID_DonHang'].'&status=returned" class="btn btn-sm btn-success mb-1" onclick="return confirm(\'Xác nhận shipper đã trả hàng về cửa hàng?\');"><i class="fas fa-check-circle"></i> Đã hoàn về shop</a><br>';
														echo ' <a href="Staff_Canceled_Orders.php?mark_returned='.$row['ID_DonHang'].'&status=not_returned" class="btn btn-sm btn-warning" onclick="return confirm(\'Xác nhận hàng chưa được trả về cửa hàng?\');"><i class="fas fa-times-circle"></i> Chưa hoàn về shop</a>';
													} else {
														echo ' <span class="badge bg-success">Đã hoàn về shop</span>';
														// Don't show "Not Yet Returned" button when already returned
													}
												} else {
													echo ' <span class="text-muted">-</span>';
												}
											} else {
												// Only Admin/Manager can cancel orders (not staff)
												if ($is_admin_or_manager) {
													echo ' <a href="Delete_Oder.php?del_oder_id='.$row['ID_DonHang'].'&view=all" class="btn btn-danger" onclick="return confirm(\'Bạn có chắc chắn muốn hủy đơn hàng #'.$row['ID_DonHang'].'? Hành động này không thể hoàn tác.\');">Hủy</a>';
												} else {
													echo ' <span class="text-muted">-</span>';
												}
											}
											echo '</td>';
											$paid = isset($row['DaThanhToan']) && intval($row['DaThanhToan'])==1;
											echo ' <td class="text-center">' . ($paid ? '<span class="badge badge-success">Đã thanh toán</span>' : '<a href="Mark_Paid.php?oder_id='.$row['ID_DonHang'].'" class="btn btn-outline-success">Đánh dấu đã thanh toán</a>') . '</td>';
											echo ' <td>' . $row['HoVaTen'] . '</td>';
											echo ' <td>' . $row['SDT'] . '</td>';
											echo ' <td>' . $row['GhiChu'] . '</td>';
											echo ' <td>' . $row['TongTien'] . '</td>';
											echo ' <td>' . $row['DiaChi'] . '</td>';
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