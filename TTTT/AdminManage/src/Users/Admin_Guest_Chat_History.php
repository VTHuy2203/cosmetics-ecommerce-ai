<?php
/**
 * Admin Guest Chat History Page
 * View chat history for a specific guest customer (non-logged-in)
 */

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webmipham1;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$session_id = $_GET['session_id'] ?? '';

if (!$session_id) {
    header('Location: Admin_Guest_Customers.php');
    exit();
}

// Get guest customer info
$guest_stmt = $pdo->prepare("SELECT * FROM guest_customers WHERE session_id = ? LIMIT 1");
$guest_stmt->execute([$session_id]);
$guest = $guest_stmt->fetch();

if (!$guest) {
    // Try to get from chat_appointments
    $appt_stmt = $pdo->prepare("SELECT guest_name, guest_phone, guest_address FROM chat_appointments WHERE session_id = ? AND user_id IS NULL LIMIT 1");
    $appt_stmt->execute([$session_id]);
    $appt = $appt_stmt->fetch();
    
    if ($appt) {
        $guest = [
            'name' => $appt['guest_name'] ?? 'N/A',
            'phone' => $appt['guest_phone'] ?? 'N/A',
            'address' => $appt['guest_address'] ?? 'N/A',
            'session_id' => $session_id
        ];
    } else {
        $guest = [
            'name' => 'Khách vãng lai',
            'phone' => 'N/A',
            'address' => 'N/A',
            'session_id' => $session_id
        ];
    }
}

// Get chat messages for this session
$messages_stmt = $pdo->prepare("SELECT id, message, direction, created_at 
                                FROM chat_messages 
                                WHERE session_id = ? AND user_id IS NULL 
                                ORDER BY created_at ASC");
$messages_stmt->execute([$session_id]);
$messages = $messages_stmt->fetchAll();

// Get customer analysis
$analysis_stmt = $pdo->prepare("SELECT * FROM customer_analysis WHERE session_id = ? AND user_id IS NULL LIMIT 1");
$analysis_stmt->execute([$session_id]);
$analysis = $analysis_stmt->fetch();

// Get appointments
$appointments_stmt = $pdo->prepare("SELECT * FROM chat_appointments WHERE session_id = ? AND user_id IS NULL ORDER BY appt_at DESC");
$appointments_stmt->execute([$session_id]);
$appointments = $appointments_stmt->fetchAll();
?>

<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>
        <div class="container-fluid">
          <h1 class="h3 mb-4 text-gray-800">💬 Lịch sử chat - Khách hàng vãng lai</h1>
          
          <!-- Guest Customer Info -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Thông tin khách hàng</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($guest['name']); ?></p>
                  <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($guest['phone']); ?></p>
                  <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($guest['address'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                  <p><strong>Session ID:</strong> <code><?php echo htmlspecialchars($session_id); ?></code></p>
                  <?php if ($analysis): ?>
                    <p><strong>Nhãn:</strong> 
                      <span class="badge badge-<?php 
                        echo $analysis['label'] === 'spam' ? 'danger' : 
                            ($analysis['label'] === 'follow-up' ? 'warning' : 'success'); 
                      ?>">
                        <?php echo ucfirst($analysis['label']); ?>
                      </span>
                    </p>
                    <p><strong>Số tin nhắn:</strong> <?php echo $analysis['message_count']; ?></p>
                    <p><strong>Có lịch hẹn:</strong> <?php echo $analysis['has_appointment'] ? 'Có' : 'Không'; ?></p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="mt-3">
                <a href="Admin_Guest_Customers.php" class="btn btn-secondary">Quay lại danh sách</a>
              </div>
            </div>
          </div>
          
          <!-- Appointments -->
          <?php if (!empty($appointments)): ?>
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">📅 Lịch hẹn tư vấn</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th>Thời gian hẹn</th>
                      <th>Ghi chú</th>
                      <th>Ngày tạo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($appointments as $appt): ?>
                      <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($appt['appt_at'])); ?></td>
                        <td><?php echo htmlspecialchars($appt['note'] ?? 'N/A'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($appt['created_at'])); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php endif; ?>
          
          <!-- Chat History -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">💬 Lịch sử chat (<?php echo count($messages); ?> tin nhắn)</h6>
            </div>
            <div class="card-body">
              <?php if (empty($messages)): ?>
                <p class="text-center text-muted">Không có tin nhắn nào.</p>
              <?php else: ?>
                <div class="chat-history" style="max-height: 600px; overflow-y: auto; padding: 15px; background: #f8f9fa; border-radius: 8px;">
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

                  foreach ($messages as $msg):
                    $msgTime = strtotime($msg['created_at']);
                    $dayOfWeek = date('l', $msgTime);
                    $dayNameVi = $dayNames[$dayOfWeek] ?? $dayOfWeek;
                    $dateStr = date('d/m/Y', $msgTime);
                    $timeStr = date('H:i:s', $msgTime);
                  ?>
                    <div class="mb-3 <?php echo $msg['direction'] === 'user' ? 'text-right' : 'text-left'; ?>">
                      <div class="d-inline-block p-3 rounded <?php echo $msg['direction'] === 'user' ? 'bg-primary text-white' : 'bg-white border'; ?>" style="max-width: 70%;">
                        <div class="mb-1">
                          <strong><?php echo $msg['direction'] === 'user' ? '👤 Khách hàng' : '🤖 Chatbot'; ?>:</strong>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                        <div class="mt-2" style="font-size: 11px; opacity: 0.7;">
                          📅 <strong><?php echo $dayNameVi; ?></strong>, <?php echo $dateStr; ?> lúc <?php echo $timeStr; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <?php include("../Layouts/Link.php"); ?>
</body>
</html>

