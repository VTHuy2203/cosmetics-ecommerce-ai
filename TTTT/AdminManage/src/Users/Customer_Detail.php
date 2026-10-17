<?php include("../Layouts/headHTML.php"); ?>
<?php
  include("../DB_Connect.php");
  
  // Session is already started in headHTML.php, no need to start again
  
  // Access control is handled by Admin_Access_Check.php (included via headHTML.php)
  // Just read admin session info for display purposes
  $admin_role = $_SESSION['admin_role'] ?? null;
  $is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

  $uid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($uid <= 0) { die("<p class='m-3 text-danger'>Thiếu ID khách hàng.</p>"); }

  // Thông tin khách hàng
  $kh = mysqli_query($conn, "SELECT * FROM khachhang WHERE ID_KhachHang=$uid");
  $user = $kh ? mysqli_fetch_assoc($kh) : null;

  // Canonical explainable score.
  $lead = null;
  $needTxt = '';
  try {
    $pdo = app_pdo();
    $leadStmt = $pdo->prepare("SELECT sc.segment AS label,sc.intent_score AS score,sc.spam_risk_score,
                                      sc.value_score,sc.value_tier,sc.reason_codes,sc.calculated_at AS updated_at,
                                      ca.product_interest,ca.last_need
                               FROM customer_scores_current sc
                               LEFT JOIN customer_analysis ca ON ca.id=(SELECT ca2.id FROM customer_analysis ca2 WHERE ca2.user_id=sc.user_id ORDER BY ca2.updated_at DESC,ca2.id DESC LIMIT 1)
                               WHERE sc.user_id=? LIMIT 1");
    $leadStmt->execute([$uid]);
    $lead = $leadStmt->fetch() ?: null;
    $needTxt = $lead ? (string) ($lead['last_need'] ?: $lead['product_interest'] ?: '') : '';
  } catch (Exception $e) {
    error_log('Customer detail score error: ' . $e->getMessage());
  }

  // CRITICAL: Use PDO for chat_messages (not mysqli) - chat tables use PDO
  $pdo_chat = null;
  $msgs_data = [];
  try {
    $pdo_chat = app_pdo();
    
    // Get the latest session_id from chat_messages
    $session_id = null;
    $sid_stmt = $pdo_chat->prepare("SELECT session_id FROM chat_messages WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
    $sid_stmt->execute([":uid" => $uid]);
    $sid_row = $sid_stmt->fetch();
    if ($sid_row && !empty($sid_row['session_id'])) {
      $session_id = $sid_row['session_id'];
    }
    
    // Get all messages - prioritize user_id, then session_id
    if ($session_id) {
      // Load by both user_id and session_id to get complete history
      $msg_stmt = $pdo_chat->prepare("SELECT id, message, direction, created_at, session_id 
                                       FROM chat_messages 
                                       WHERE (user_id = :uid OR session_id = :sid)
                                       ORDER BY id ASC, created_at ASC
                                       LIMIT 1000");
      $msg_stmt->execute([":uid" => $uid, ":sid" => $session_id]);
      $msgs_data = $msg_stmt->fetchAll();
    } else {
      // Fallback: Load by user_id only
      $msg_stmt = $pdo_chat->prepare("SELECT id, message, direction, created_at, session_id 
                                       FROM chat_messages 
                                       WHERE user_id = :uid
                                       ORDER BY id ASC, created_at ASC
                                       LIMIT 1000");
      $msg_stmt->execute([":uid" => $uid]);
      $msgs_data = $msg_stmt->fetchAll();
    }
    
  } catch (Exception $e) {
    error_log("Customer_Detail.php: Error loading chat history: " . $e->getMessage());
    $msgs_data = [];
  }
  
  // Convert to mysqli-like format for compatibility with existing display code
  $msgs = $msgs_data; // Will be used as array in the display code

  // Lịch hẹn - Use PDO for chat_appointments (not mysqli)
  $appts_data = [];
  try {
    if (!$pdo_chat) {
      $pdo_chat = app_pdo();
    }
    
    if ($session_id) {
      $appt_stmt = $pdo_chat->prepare("SELECT id, appt_at, note, created_at, session_id
                                        FROM chat_appointments
                                        WHERE (user_id = :uid OR session_id = :sid)
                                        ORDER BY appt_at DESC, id DESC
                                        LIMIT 200");
      $appt_stmt->execute([":uid" => $uid, ":sid" => $session_id]);
      $appts_data = $appt_stmt->fetchAll();
    } else {
      $appt_stmt = $pdo_chat->prepare("SELECT id, appt_at, note, created_at, session_id
                                        FROM chat_appointments
                                        WHERE user_id = :uid
                                        ORDER BY appt_at DESC, id DESC
                                        LIMIT 200");
      $appt_stmt->execute([":uid" => $uid]);
      $appts_data = $appt_stmt->fetchAll();
    }
  } catch (Exception $e) {
    error_log("Customer_Detail.php: Error loading appointments: " . $e->getMessage());
    $appts_data = [];
  }
?>

<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>

        <div class="container-fluid">
          <h1 class="h3 mb-3 text-gray-800">Thông tin khách hàng</h1>

          <a class="btn btn-sm btn-secondary mb-3" href="Manage_Users.php">&laquo; Quay lại danh sách</a>

          <div class="row">
            <div class="col-md-5">
              <div class="card mb-4">
                <div class="card-header">Hồ sơ</div>
                <div class="card-body">
                  <?php if($user): ?>
                    <div><strong>Họ tên:</strong> <?php echo htmlspecialchars($user['HoVaTen']); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></div>
                    <div><strong>SDT:</strong> <?php echo htmlspecialchars($user['SDT']); ?></div>
                    <div><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($user['DiaChi']); ?></div>
                    <div><strong>Số dư:</strong> <?php echo number_format(isset($user['SoDu'])?$user['SoDu']:0,0,',','.'); ?> đ</div>
                  <?php else: ?>
                    <div class="text-danger">Không tìm thấy khách hàng.</div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header">Lead</div>
                <div class="card-body">
                  <?php
                    if ($lead) {
                      $label = $lead['label'] ?? 'normal';
                      if ($label === 'potential') {
                        $badge = '<span class="badge bg-success">Tiềm năng</span>';
                      } else if ($label === 'follow-up') {
                        $badge = '<span class="badge bg-warning text-dark">Đang theo dõi</span>';
                      } else if ($label === 'spam') {
                        $badge = '<span class="badge bg-danger">Spam</span>';
                      } else {
                        $badge = '<span class="badge bg-secondary">Bình thường</span>';
                      }
                      $score = isset($lead['score']) ? (int)$lead['score'] : 0;
                      echo "<div>$badge <strong>".$score."/100 điểm</strong></div>";
                      echo "<div class='small text-muted'>Spam: ".(int)($lead['spam_risk_score'] ?? 0)." · Giá trị: ".htmlspecialchars($lead['value_tier'] ?? 'new')."</div>";
                      echo "<div class='small text-muted mt-1'>Cập nhật: ".htmlspecialchars($lead['updated_at'] ?? 'N/A')."</div>";
                      $reasons = json_decode((string) ($lead['reason_codes'] ?? '[]'), true);
                      if (is_array($reasons) && $reasons) {
                        echo "<hr class='my-2'><strong>Lý do tính điểm:</strong><ul class='mb-2'>";
                        foreach ($reasons as $reason) {
                          echo '<li>'.sprintf('%+d', (int)($reason['points'] ?? 0)).' '.htmlspecialchars($reason['label'] ?? '').'</li>';
                        }
                        echo '</ul>';
                      }
                      if ($needTxt) {
                        echo "<hr class='my-2'><div><strong>Nhu cầu gần nhất:</strong><br>".nl2br(htmlspecialchars($needTxt))."</div>";
                      }
                    } else {
                      echo "<div class='text-muted'>Chưa có đánh giá lead.</div>";
                      if ($needTxt) {
                        echo "<hr class='my-2'><div><strong>Nhu cầu gần nhất:</strong><br>".nl2br(htmlspecialchars($needTxt))."</div>";
                      }
                    }
                  ?>
                </div>
              </div>
            </div>

            <div class="col-md-7">
              <div class="card mb-4">
                <div class="card-header">Lịch khách đã đặt</div>
                <div class="card-body">
                  <?php if (!empty($appts_data) && count($appts_data) > 0): ?>
                    <table class="table table-sm">
                      <thead>
                      <tr><th>Thời điểm</th><th>Ghi chú</th><th>Phiên</th><th>Tạo lúc</th></tr>
                      </thead>
                      <tbody>
                      <?php foreach($appts_data as $a): 
                        $appt_date = new DateTime($a['appt_at']);
                        $appt_formatted = $appt_date->format('d/m/Y H:i');
                      ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($appt_formatted); ?></strong></td>
                          <td><?php echo htmlspecialchars($a['note'] ?: 'N/A'); ?></td>
                          <td class="text-muted small"><?php echo htmlspecialchars(substr($a['session_id'], 0, 20)); ?>...</td>
                          <td class="text-muted small"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($a['created_at']))); ?></td>
                        </tr>
                      <?php endforeach; ?>
                      </tbody>
                    </table>
                  <?php else: ?>
                    <div class="text-muted">Chưa có lịch.</div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card">
                <div class="card-header">Đoạn chat gần đây</div>
                <div class="card-body" style="max-height:600px; overflow-y:auto; background:#f8f9fa;">
                  <?php if (!empty($msgs_data) && count($msgs_data) > 0): ?>
                    <div class="chat-messages" style="padding: 10px;">
                      <?php
                        // Vietnamese day names
                        $dayNames = [
                          'Sunday' => 'Chủ nhật',
                          'Monday' => 'Thứ hai',
                          'Tuesday' => 'Thứ ba',
                          'Wednesday' => 'Thứ tư',
                          'Thursday' => 'Thứ năm',
                          'Friday' => 'Thứ sáu',
                          'Saturday' => 'Thứ bảy'
                        ];

                        foreach($msgs_data as $m):
                        $isUser = $m['direction'] === 'user';
                        // User messages: Blue background, white text, right aligned
                        // Bot messages: Light gray background, dark text, left aligned
                        if ($isUser) {
                          $bgClass = 'text-white';
                          $bgColor = '#007bff'; // Blue
                          $alignClass = 'text-end';
                          $who = 'Khách hàng';
                          $textColor = 'white';
                        } else {
                          $bgClass = 'text-dark';
                          $bgColor = '#e9ecef'; // Light gray
                          $alignClass = 'text-start';
                          $who = 'Bot';
                          $textColor = '#212529';
                        }

                        // Get real timestamp info
                        $msgTime = strtotime($m['created_at']);
                        $dayOfWeek = date('l', $msgTime);
                        $dayNameVi = $dayNames[$dayOfWeek] ?? $dayOfWeek;
                        $dateStr = date('d/m/Y', $msgTime);
                        $timeStr = date('H:i:s', $msgTime);
                      ?>
                        <div class="mb-3 <?php echo $alignClass; ?>">
                          <div class="d-inline-block p-3 rounded" style="max-width:75%; background-color: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <div class="small fw-bold mb-2" style="opacity: 0.8;"><?php echo $who; ?></div>
                            <div style="line-height: 1.5;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                            <div class="small mt-2" style="opacity: 0.7; font-size:0.75rem;">
                              📅 <strong><?php echo $dayNameVi; ?></strong>, <?php echo $dateStr; ?> lúc <?php echo $timeStr; ?>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-muted text-center py-4">Chưa có tin nhắn.</div>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </div>

        </div><!-- /.container-fluid -->
      </div><!-- End Main Content -->

      <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div><!-- End wrapper -->
  </div>
  <?php include("../Layouts/Link.php"); ?>
</body>
</html>
