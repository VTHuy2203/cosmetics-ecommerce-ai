<?php
// Admin_Bookings.php - View consultation bookings
include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Session is already started in headHTML.php, no need to start again
// Access control is handled by Admin_Access_Check.php (included via headHTML.php)
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

// Use PDO for chat tables (they use PDO, not mysqli)
function pdo_connect_chat() {
    return app_pdo();
}

$pdo = pdo_connect_chat();

$stmt = $pdo->query("
    SELECT 
        ca.id, ca.user_id, ca.session_id, ca.appt_at, ca.note,
        ca.message_content, ca.message_sent_at, ca.created_at,
        kh.HoVaTen, kh.Email, kh.SDT
    FROM chat_appointments ca
    LEFT JOIN khachhang kh ON ca.user_id = kh.ID_KhachHang
    ORDER BY ca.appt_at DESC, ca.created_at DESC
    LIMIT 500
");
$bookings = $stmt->fetchAll();

$total = count($bookings);
$upcoming = 0;
$past = 0;
$now = new DateTime();
foreach ($bookings as $b) {
    $appt_time = new DateTime($b['appt_at']);
    if ($appt_time > $now) $upcoming++; else $past++;
}
?>
<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>
        <div class="container-fluid">
          <h1 class="h3 mb-4 text-gray-800">📅 Quản lý đặt lịch tư vấn</h1>
          
          <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng số lịch đặt</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total; ?></div>
                </div>
              </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sắp tới</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $upcoming; ?></div>
                </div>
              </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Đã qua</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $past; ?></div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">Danh sách lịch đặt</h6>
              <a href="Export_Bookings_CSV.php" class="btn btn-sm btn-success">Xuất CSV</a>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Khách hàng</th>
                      <th>Thời gian đặt</th>
                      <th>Nội dung tin nhắn</th>
                      <th>Thời gian gửi</th>
                      <th>Ghi chú</th>
                      <th>Trạng thái</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bookings as $b): ?>
                      <?php
                      $appt_time = new DateTime($b['appt_at']);
                      $is_upcoming = $appt_time > $now;
                      $customer_name = $b['HoVaTen'] ?: "Khách vãng lai";
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($b['id']); ?></td>
                        <td>
                          <strong><?php echo htmlspecialchars($customer_name); ?></strong><br>
                          <small><?php echo htmlspecialchars($b['Email'] ?: ($b['SDT'] ?: 'N/A')); ?></small>
                        </td>
                        <td><?php echo $appt_time->format('d/m/Y H:i'); ?></td>
                        <td style="max-width: 300px; word-wrap: break-word;" title="<?php echo htmlspecialchars($b['message_content'] ?: 'N/A'); ?>">
                          <?php echo htmlspecialchars($b['message_content'] ?: 'N/A'); ?>
                        </td>
                        <td><?php echo $b['message_sent_at'] ? date('d/m/Y H:i', strtotime($b['message_sent_at'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($b['note'] ?: '-'); ?></td>
                        <td>
                          <span class="badge badge-<?php echo $is_upcoming ? 'success' : 'danger'; ?>">
                            <?php echo $is_upcoming ? 'Sắp tới' : 'Đã qua'; ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include("../Layouts/Link.php"); ?>
    </div>
  </div>
</body>
</html>
