<?php
/**
 * Employee Customer List Page with Consultation Schedule
 * Combined page showing customer information at top and their appointments below
 */

include("../Layouts/Head.php");
include("../DB_Connect.php");

// Check if user is logged in and is an employee
if (!isset($_SESSION['user']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: Login.php");
    exit();
}

// Check if employee is approved
$user_id = $_SESSION['IDNguoiDung'];
$employee_check = mysqli_query($conn, "SELECT status FROM shipper_requests WHERE user_id = $user_id AND status = 'approved' ORDER BY created_at DESC LIMIT 1");
if (!$employee_check || mysqli_num_rows($employee_check) == 0) {
    echo "<script>alert('Tài khoản nhân viên của bạn chưa được phê duyệt. Vui lòng đợi Admin phê duyệt.'); window.location.href='index.php';</script>";
    exit();
}

// Use PDO for chat tables
function pdo_connect_chat() {
    return app_pdo();
}

$pdo = pdo_connect_chat();

// Get all customers (Fixed: Removed non-existent columns)
$sql = "SELECT k.*
        FROM khachhang k
        WHERE k.Role = 'customer' OR k.Role IS NULL
        ORDER BY k.ID_KhachHang DESC";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Có lỗi xảy ra: " . mysqli_error($conn));
}

// Load lead information
$leadMap = [];
$appointmentsMap = []; // Map customer ID to their appointments

try {
    // Get canonical current scores and the most recent stated need.
    try {
        $caStmt = $pdo->query("SELECT sc.user_id,sc.segment AS label,sc.intent_score AS score,
                                      sc.spam_risk_score,sc.value_tier,ca.product_interest,ca.last_need
                               FROM customer_scores_current sc
                               LEFT JOIN customer_analysis ca ON ca.id=(SELECT ca2.id FROM customer_analysis ca2 WHERE ca2.user_id=sc.user_id ORDER BY ca2.updated_at DESC,ca2.id DESC LIMIT 1)
                               WHERE sc.user_id IS NOT NULL");
        while($ca = $caStmt->fetch()) {
            $uid = (int)$ca['user_id'];
            if (!isset($leadMap[$uid])) {
                $leadMap[$uid] = [
                    'label' => $ca['label'] ?? 'normal',
                    'score' => (int) ($ca['score'] ?? 0),
                    'spam_risk_score' => (int) ($ca['spam_risk_score'] ?? 0),
                    'value_tier' => $ca['value_tier'] ?? 'new',
                    'last_need' => $ca['last_need'] ?? null,
                    'product_interest' => $ca['product_interest'] ?? null
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Employee_Customer_List.php: Error fetching customer_analysis: " . $e->getMessage());
    }
    
    // Get all appointments grouped by customer
    $apptStmt = $pdo->query("
        SELECT 
            ca.id, ca.user_id, ca.session_id, ca.appt_at, ca.note,
            ca.message_content, ca.message_sent_at, ca.created_at,
            ca.guest_name, ca.guest_phone, ca.guest_address
        FROM chat_appointments ca
        ORDER BY ca.appt_at DESC, ca.created_at DESC
    ");
    
    $guestAppointments = [];

    while ($appt = $apptStmt->fetch()) {
        if ($appt['user_id']) {
            $uid = (int)$appt['user_id'];
            if (!isset($appointmentsMap[$uid])) {
                $appointmentsMap[$uid] = [];
            }
            $appointmentsMap[$uid][] = $appt;
        } else {
            $guestAppointments[] = $appt;
        }
    }
} catch (Exception $e) {
    error_log("Employee_Customer_List.php: PDO connection failed: " . $e->getMessage());
}

// Get pending orders (TrangThai = '0' or 0 - not yet approved)
$sql_pending_orders = "SELECT * FROM donhang WHERE (TrangThai = '0' OR TrangThai = 0) ORDER BY NgayTao DESC LIMIT 20";
$result_pending_orders = mysqli_query($conn, $sql_pending_orders);
if (!$result_pending_orders) {
    error_log("Employee_Customer_List.php: Query error: " . mysqli_error($conn));
    $result_pending_orders = false;
    $total_pending_orders = 0;
} else {
    $sql_total_pending = "SELECT COUNT(*) as total FROM donhang WHERE TrangThai = '0' OR TrangThai = 0";
    $result_total_pending = mysqli_query($conn, $sql_total_pending);
    if ($result_total_pending) {
        $total_pending_row = mysqli_fetch_array($result_total_pending);
        $total_pending_orders = isset($total_pending_row['total']) ? intval($total_pending_row['total']) : 0;
    } else {
        $total_pending_orders = 0;
    }
}

// Get pending return/refund requests count
$sql_total_return_requests = "SELECT COUNT(*) as total FROM return_refund_requests WHERE TrangThai = 'pending'";
$result_total_return = mysqli_query($conn, $sql_total_return_requests);
$total_return_requests = 0;
if ($result_total_return && mysqli_num_rows($result_total_return) > 0) {
    $total_return_row = mysqli_fetch_array($result_total_return);
    $total_return_requests = isset($total_return_row['total']) ? intval($total_return_row['total']) : 0;
}

// Get filter
$filterLead = isset($_GET['lead']) ? $_GET['lead'] : '';

// Get guest customers count (walk-in customers)
$guestCustomers = [];
$totalGuestCustomers = 0;
try {
    $guestStmt = $pdo->query("
        SELECT gc.*,
               COALESCE(sc.segment, ca.label, 'normal') AS label,
               ca.message_count,
               ca.has_appointment,
               (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id=gc.session_id AND cm.user_id IS NULL) AS total_messages
        FROM guest_customers gc
        LEFT JOIN customer_analysis ca ON gc.session_id = ca.session_id AND ca.user_id IS NULL
        LEFT JOIN customer_scores_current sc ON sc.subject_key = CONCAT('session:', gc.session_id)
        ORDER BY gc.updated_at DESC
        LIMIT 20
    ");
    $guestCustomers = $guestStmt->fetchAll();

    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM guest_customers");
    $totalGuestCustomers = (int)$countStmt->fetch()['total'];
} catch (Exception $e) {
    error_log("Employee_Customer_List.php: Error fetching guest customers: " . $e->getMessage());
}

// Calculate statistics
$total_appointments = 0;
$upcoming_appointments = 0;
$past_appointments = 0;
$now = new DateTime();
foreach ($appointmentsMap as $appts) {
    foreach ($appts as $appt) {
        $total_appointments++;
        $appt_time = new DateTime($appt['appt_at']);
        if ($appt_time > $now) {
            $upcoming_appointments++;
        } else {
            $past_appointments++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách khách hàng & Lịch tư vấn - Nhân viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .customer-table {
            margin-top: 20px;
        }
        .lead-badge {
            font-size: 0.85em;
        }
        .appointments-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }
        .customer-appointments {
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .badge-upcoming {
            background-color: #28a745;
            color: white;
        }
        .badge-past {
            background-color: #dc3545;
            color: white;
        }
        .stats-card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-users"></i> Danh sách khách hàng & Lịch tư vấn
                </h2>
                
                <!-- Statistics Cards - Clickable for filtering appointments -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stats-card border-left-primary clickable-card" onclick="filterAppointments('all')" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Tổng số lịch đặt</h5>
                                <h3 class="text-gray-800"><?php echo $total_appointments; ?></h3>
                                <small class="text-muted">Click để xem tất cả</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-left-success clickable-card" onclick="filterAppointments('upcoming')" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                            <div class="card-body">
                                <h5 class="card-title text-success">Sắp tới</h5>
                                <h3 class="text-gray-800"><?php echo $upcoming_appointments; ?></h3>
                                <small class="text-muted">Click để xem lịch sắp tới</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-left-warning clickable-card" onclick="filterAppointments('past')" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                            <div class="card-body">
                                <h5 class="card-title text-warning">Đã qua</h5>
                                <h3 class="text-gray-800"><?php echo $past_appointments; ?></h3>
                                <small class="text-muted">Click để xem lịch đã qua</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-left-danger clickable-card" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';" onclick="document.getElementById('ordersSection').scrollIntoView({behavior: 'smooth'});">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Đơn hàng cần duyệt</h5>
                                <h3 class="text-gray-800"><?php echo number_format($total_pending_orders); ?></h3>
                                <small class="text-muted">Click để xem đơn hàng</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-left-info clickable-card" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';" onclick="window.location.href='Employee_Return_Orders.php';">
                            <div class="card-body">
                                <h5 class="card-title text-info">Đơn hàng hoàn trả</h5>
                                <h3 class="text-gray-800"><?php echo number_format($total_return_requests); ?></h3>
                                <small class="text-muted">Click để xem đơn hoàn trả</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-left-secondary clickable-card" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';" onclick="document.getElementById('guestSection').scrollIntoView({behavior: 'smooth'});">
                            <div class="card-body">
                                <h5 class="card-title text-secondary">Khách vãng lai</h5>
                                <h3 class="text-gray-800"><?php echo number_format($totalGuestCustomers); ?></h3>
                                <small class="text-muted">Click để xem khách vãng lai</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Section for Staff -->
                <div id="ordersSection" class="card mb-4" style="margin-top: 30px;">
                    <div class="card-header bg-danger text-white"></div>
                        <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Đơn Hàng Cần Duyệt</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_pending_orders && mysqli_num_rows($result_pending_orders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>STT</th>
                                            <th>Tên Khách Hàng</th>
                                            <th>SDT</th>
                                            <th>Tổng Tiền</th>
                                            <th>Địa Chỉ</th>
                                            <th>Ngày Đặt</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $stt = 1;
                                        mysqli_data_seek($result_pending_orders, 0);
                                        while ($order = mysqli_fetch_array($result_pending_orders)): 
                                        ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td><?php echo htmlspecialchars($order['HoVaTen']); ?></td>
                                                <td><?php echo htmlspecialchars($order['SDT']); ?></td>
                                                <td><?php echo number_format($order['TongTien'], 0, ',', '.') . ' ₫'; ?></td>
                                                <td><?php echo htmlspecialchars(substr($order['DiaChi'], 0, 50)) . (strlen($order['DiaChi']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo isset($order['NgayTao']) ? date('d/m/Y H:i', strtotime($order['NgayTao'])) : 'N/A'; ?></td>
                                                <td class="text-center">
                                                    <form method="post" action="../Processing/Employee_Approve_Order.php"
                                                          onsubmit="return confirm('Bạn có chắc chắn muốn duyệt đơn hàng này?')">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="order_id" value="<?php echo (int) $order['ID_DonHang']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Duyệt</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php 
                                        endwhile;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($total_pending_orders > 20): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> Hiển thị 20 đơn hàng gần nhất. Tổng số: <?php echo $total_pending_orders; ?> đơn hàng cần duyệt.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Không có đơn hàng nào cần duyệt. Tất cả đơn hàng đã được duyệt.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Guest Customers Section -->
                <div id="guestSection" class="card mb-4" style="margin-top: 30px;">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-clock"></i> Khách hàng vãng lai (Walk-in Customers)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($guestCustomers)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>STT</th>
                                            <th>Họ tên</th>
                                            <th>Điện thoại</th>
                                            <th>Địa chỉ</th>
                                            <th>Phân loại</th>
                                            <th>Số tin nhắn</th>
                                            <th>Có lịch hẹn</th>
                                            <th>Ghi chú</th>
                                            <th>Cập nhật lần cuối</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt_guest = 1;
                                        foreach ($guestCustomers as $guest):
                                            $label = $guest['label'] ?? 'unlabeled';
                                        $labelColors = ['normal' => 'secondary', 'spam' => 'danger', 'follow-up' => 'warning', 'potential' => 'success', 'unlabeled' => 'secondary'];
                                        $labelNames = ['normal' => 'Bình thường', 'spam' => 'Spam', 'follow-up' => 'Theo dõi', 'potential' => 'Tiềm năng', 'unlabeled' => 'Chưa phân loại'];
                                        ?>
                                            <tr>
                                                <td><?php echo $stt_guest++; ?></td>
                                                <td><?php echo htmlspecialchars($guest['name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($guest['address'] ?? 'N/A'); ?></td>
                                                <td><span class="badge bg-<?php echo $labelColors[$label] ?? 'secondary'; ?>"><?php echo $labelNames[$label] ?? ucfirst($label); ?></span></td>
                                                <td><?php echo $guest['total_messages'] ?? 0; ?></td>
                                                <td>
                                                    <?php if ($guest['has_appointment']): ?>
                                                        <span class="badge bg-success">Có</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Không</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($guest['notes'] ?? ''); ?></small></td>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($guest['updated_at'])); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($totalGuestCustomers > 20): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> Hiển thị 20 khách hàng gần nhất. Tổng số: <?php echo $totalGuestCustomers; ?> khách vãng lai.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Chưa có khách hàng vãng lai nào.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filter and Export -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <form method="get" class="d-inline">
                                    <label for="lead_filter" class="form-label">Lọc theo Lead:</label>
                                    <select id="lead_filter" name="lead" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                        <option value="">Tất cả</option>
                                        <option value="potential" <?php echo ($filterLead === 'potential') ? 'selected' : ''; ?>>Tiềm năng</option>
                                        <option value="follow-up" <?php echo ($filterLead === 'follow-up') ? 'selected' : ''; ?>>Đang theo dõi</option>
                                        <option value="normal" <?php echo ($filterLead === 'normal') ? 'selected' : ''; ?>>Bình thường</option>
                                        <option value="spam" <?php echo ($filterLead === 'spam') ? 'selected' : ''; ?>>Spam</option>
                                    </select>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="Customer_Weekly_Stats.php" class="btn btn-info me-2">
                                    <i class="fas fa-chart-bar"></i> Thống kê tuần
                                </a>
                                <a href="../Processing/Export_Employee_Customers_CSV.php<?php
                                    $params = [];
                                    if ($filterLead !== '') $params['lead'] = $filterLead;
                                    echo !empty($params) ? '?' . http_build_query($params) : '';
                                ?>" class="btn btn-success">
                                    <i class="fas fa-download"></i> Xuất CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Thông tin khách hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover customer-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>STT</th>
                                        <th>Họ và Tên</th>
                                        <th>Số Điện Thoại</th>
                                        <th>Email</th>
                                        <th>Địa Chỉ</th>
                                        <th>Điểm lead</th>
                                        <th>Phân loại</th>
                                        <th>Hạng giá trị</th>
                                        <th>Nhu Cầu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stt = 1;
                                    mysqli_data_seek($result, 0); // Reset result pointer
                                    while ($row = mysqli_fetch_array($result)) {
                                        $uid = (int)$row['ID_KhachHang'];

                                        // Get new customer classification
                                        $lead = isset($leadMap[$uid]) ? $leadMap[$uid] : null;
                                        
                                        $customerLabel = 'normal';
                                        if ($lead && isset($lead['label'])) {
                                            $customerLabel = $lead['label'];
                                        }

                                        if ($filterLead !== '') {
                                            if ($customerLabel !== $filterLead) {
                                                continue;
                                            }
                                        }

                                        // Customer label badge with star for potential
                                        $badge = '';
                                        $starIcon = '';
                                        if ($customerLabel === 'potential') {
                                            $starIcon = '<span style="color:#ffd700;font-size:1.3em;margin-right:4px;">⭐</span>';
                                            $badge = '<span class="badge" style="background:linear-gradient(135deg,#ffd700 0%,#ffb347 100%);color:#333;">Tiềm năng</span>';
                                        } else if ($customerLabel === 'follow-up') {
                                            $badge = '<span class="badge" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;">Theo dõi</span>';
                                        } else if ($customerLabel === 'spam') {
                                            $badge = '<span class="badge bg-danger">Spam</span>';
                                        } else {
                                            $badge = '<span class="badge bg-secondary">Bình thường</span>';
                                        }

                                        // Get last need
                                        $need = '';
                                        if ($lead && isset($lead['last_need']) && $lead['last_need']) {
                                            $need = htmlspecialchars($lead['last_need']);
                                        } elseif ($lead && isset($lead['product_interest']) && $lead['product_interest']) {
                                            $need = htmlspecialchars($lead['product_interest']);
                                        }

                                        $purchaseDisplay = (int)($lead['score'] ?? 0) . '/100';
                                        $discountDisplay = '<span class="badge bg-info text-dark">' . htmlspecialchars((string)($lead['value_tier'] ?? 'new')) . '</span>';

                                        echo '<tr' . ($customerLabel === 'potential' ? ' style="background:rgba(255,215,0,0.1);"' : '') . '>';
                                        echo '<td>' . $stt . '</td>';
                                        echo '<td>' . $starIcon . '<strong>' . htmlspecialchars($row['HoVaTen']) . '</strong></td>';
                                        echo '<td>' . htmlspecialchars($row['SDT']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['DiaChi']) . '</td>';
                                        echo '<td>' . $purchaseDisplay . '</td>';
                                        echo '<td>' . $badge . '</td>';
                                        echo '<td>' . $discountDisplay . '</td>';
                                        echo '<td><small class="text-muted">' . ($need ?: 'N/A') . '</small></td>';
                                        echo '</tr>';

                                        $stt++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Consultation Schedule Section -->
                <div class="appointments-section">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Lịch tư vấn của khách hàng</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // 1. Build Customer Map
                            $customerMap = [];
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_array($result)) {
                                $customerMap[(int)$row['ID_KhachHang']] = $row;
                            }
                            
                            // 2. Process and Sort Appointments for each User
                            $displayList = [];
                            $now = new DateTime();
                            
                            foreach ($appointmentsMap as $uid => $appts) {
                                if (!isset($customerMap[$uid])) continue;
                                
                                // Filter Lead
                                $lead = isset($leadMap[$uid]) ? $leadMap[$uid] : null;
                                if ($filterLead !== '' && ($lead === null || $lead['label'] !== $filterLead)) {
                                    continue;
                                }

                                // Separate and Sort Appointments
                                $upcoming = [];
                                $past = [];
                                foreach ($appts as $appt) {
                                    $apptTime = new DateTime($appt['appt_at']);
                                    if ($apptTime > $now) {
                                        $upcoming[] = $appt;
                                    } else {
                                        $past[] = $appt;
                                    }
                                }
                                
                                // Sort Upcoming ASC (Earliest first)
                                usort($upcoming, function($a, $b) {
                                    return strtotime($a['appt_at']) - strtotime($b['appt_at']);
                                });
                                
                                // Sort Past DESC (Latest first)
                                usort($past, function($a, $b) {
                                    return strtotime($b['appt_at']) - strtotime($a['appt_at']);
                                });
                                
                                // Re-assign sorted appointments to map (merged)
                                $appointmentsMap[$uid] = array_merge($upcoming, $past);
                                
                                // Determine User Rank
                                // Rank Type: 1 = Has Upcoming, 2 = Only Past
                                // Rank Date: If Type 1 => Earliest Upcoming Date. If Type 2 => Latest Past Date
                                
                                $type = 2;
                                $sortDate = 0;
                                
                                if (count($upcoming) > 0) {
                                    $type = 1;
                                    $sortDate = strtotime($upcoming[0]['appt_at']);
                                } elseif (count($past) > 0) {
                                    $type = 2;
                                    $sortDate = strtotime($past[0]['appt_at']);
                                } else {
                                    continue; // No appointments
                                }
                                
                                $displayList[] = [
                                    'uid' => $uid,
                                    'type' => $type,
                                    'date' => $sortDate
                                ];
                            }
                            
                            // 3. Sort Users
                            usort($displayList, function($a, $b) {
                                if ($a['type'] != $b['type']) {
                                    return $a['type'] - $b['type']; // Type 1 (Upcoming) before Type 2 (Past)
                                }
                                
                                if ($a['type'] == 1) {
                                    // Upcoming: Earliest date first (ASC)
                                    return $a['date'] - $b['date'];
                                } else {
                                    // Past: Latest date first (DESC)
                                    return $b['date'] - $a['date'];
                                }
                            });
                            
                            // 4. Loop and Display
                            $has_appointments = false;
                            
                            foreach ($displayList as $item) {
                                $uid = $item['uid'];
                                $row = $customerMap[$uid];
                                $customer_appointments = $appointmentsMap[$uid];
                                $has_appointments = true;
                                
                                echo '<div class="customer-appointments p-3 border rounded mb-4 shadow-sm bg-white">';
                                
                                // Customer Header Info
                                echo '<div class="row mb-3 pb-2 border-bottom">';
                                echo '<div class="col-md-4">';
                                echo '<h6 class="text-primary fw-bold mb-1"><i class="fas fa-user me-2"></i>' . htmlspecialchars($row['HoVaTen']) . '</h6>';
                                echo '<small class="text-muted">ID: ' . $uid . '</small>';
                                echo '</div>';
                                echo '<div class="col-md-8">';
                                echo '<div class="row">';
                                if (!empty($row['SDT'])) {
                                    echo '<div class="col-md-6 mb-1"><small><i class="fas fa-phone me-2 text-secondary"></i>' . htmlspecialchars($row['SDT']) . '</small></div>';
                                }
                                if (!empty($row['Email'])) {
                                    echo '<div class="col-md-6 mb-1"><small><i class="fas fa-envelope me-2 text-secondary"></i>' . htmlspecialchars($row['Email']) . '</small></div>';
                                }
                                if (!empty($row['DiaChi'])) {
                                    echo '<div class="col-12"><small><i class="fas fa-map-marker-alt me-2 text-secondary"></i>' . htmlspecialchars($row['DiaChi']) . '</small></div>';
                                }
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';

                                echo '<div class="table-responsive">';
                                echo '<table class="table table-sm table-bordered">';
                                echo '<thead class="table-light">';
                                echo '<tr>';
                                echo '<th>ID</th>';
                                echo '<th>Thời gian đặt</th>';
                                echo '<th>Nội dung tin nhắn</th>';
                                echo '<th>Thời gian gửi</th>';
                                echo '<th>Ghi chú</th>';
                                echo '<th>Trạng thái</th>';
                                echo '<th>Thao tác</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody class="appointment-rows" data-customer-id="' . $uid . '">';
                                
                                foreach ($customer_appointments as $appt) {
                                    $appt_time = new DateTime($appt['appt_at']);
                                    $is_upcoming = $appt_time > $now;
                                    $appointment_type = $is_upcoming ? 'upcoming' : 'past';
                                    
                                    echo '<tr class="appointment-row appointment-' . $appointment_type . '" data-appointment-type="' . $appointment_type . '">';
                                    echo '<td>' . htmlspecialchars($appt['id']) . '</td>';
                                    echo '<td>' . $appt_time->format('d/m/Y H:i') . '</td>';
                                    echo '<td style="max-width: 300px; word-wrap: break-word;">' . htmlspecialchars($appt['message_content'] ?: 'N/A') . '</td>';
                                    echo '<td>' . ($appt['message_sent_at'] ? date('d/m/Y H:i', strtotime($appt['message_sent_at'])) : 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($appt['note'] ?: '-') . '</td>';
                                    echo '<td>';
                                    echo '<span class="badge ' . ($is_upcoming ? 'badge-upcoming' : 'badge-past') . '">';
                                    echo $is_upcoming ? 'Sắp tới' : 'Đã qua';
                                    echo '</span>';
                                    echo '</td>';
                                    echo '<td>';
                                    if (!$is_upcoming) {
                                        echo '<button class="btn btn-danger btn-sm" onclick="deleteAppointment(' . $appt['id'] . ')"><i class="fas fa-trash"></i></button>';
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            if (!$has_appointments && empty($guestAppointments)) {
                                echo '<div class="alert alert-info">';
                                echo '<i class="fas fa-info-circle"></i> Chưa có lịch đặt tư vấn nào.';
                                echo '</div>';
                            }
                            
                            // Display Guest Appointments (Sorted similarly)
                            if (!empty($guestAppointments)) {
                                $upcomingGuest = [];
                                $pastGuest = [];
                                foreach ($guestAppointments as $appt) {
                                    $apptTime = new DateTime($appt['appt_at']);
                                    if ($apptTime > $now) {
                                        $upcomingGuest[] = $appt;
                                    } else {
                                        $pastGuest[] = $appt;
                                    }
                                }
                                usort($upcomingGuest, function($a, $b) { return strtotime($a['appt_at']) - strtotime($b['appt_at']); });
                                usort($pastGuest, function($a, $b) { return strtotime($b['appt_at']) - strtotime($a['appt_at']); });
                                $guestAppointments = array_merge($upcomingGuest, $pastGuest);
                                
                                echo '<div class="customer-appointments mt-4 pt-3 border-top">';
                                echo '<h6 class="mb-3 text-info"><i class="fas fa-user-secret"></i> Khách vãng lai (Chưa đăng ký thành viên)</h6>';
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-sm table-bordered">';
                                echo '<thead class="table-light">';
                                echo '<tr>';
                                echo '<th>ID</th>';
                                echo '<th>Khách hàng</th>';
                                echo '<th>Thời gian đặt</th>';
                                echo '<th>Nội dung tin nhắn</th>';
                                echo '<th>Thời gian gửi</th>';
                                echo '<th>Ghi chú</th>';
                                echo '<th>Trạng thái</th>';
                                echo '<th>Thao tác</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody class="appointment-rows guest-rows">';
                                
                                foreach ($guestAppointments as $appt) {
                                    $appt_time = new DateTime($appt['appt_at']);
                                    $is_upcoming = $appt_time > $now;
                                    $appointment_type = $is_upcoming ? 'upcoming' : 'past';
                                    
                                    $guestName = $appt['guest_name'] ?: "Khách vãng lai";
                                    $guestPhone = $appt['guest_phone'] ?: "";
                                    $guestAddr = $appt['guest_address'] ?: "";
                                    
                                    echo '<tr class="appointment-row appointment-' . $appointment_type . '" data-appointment-type="' . $appointment_type . '">';
                                    echo '<td>' . htmlspecialchars($appt['id']) . '</td>';
                                    echo '<td>';
                                    echo '<strong>' . htmlspecialchars($guestName) . '</strong><br>';
                                    if ($guestPhone) echo '<small class="text-muted"><i class="fas fa-phone"></i> ' . htmlspecialchars($guestPhone) . '</small><br>';
                                    if ($guestAddr) echo '<small class="text-muted"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($guestAddr) . '</small>';
                                    echo '</td>';
                                    echo '<td>' . $appt_time->format('d/m/Y H:i') . '</td>';
                                    echo '<td style="max-width: 300px; word-wrap: break-word;">' . htmlspecialchars($appt['message_content'] ?: 'N/A') . '</td>';
                                    echo '<td>' . ($appt['message_sent_at'] ? date('d/m/Y H:i', strtotime($appt['message_sent_at'])) : 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($appt['note'] ?: '-') . '</td>';
                                    echo '<td>';
                                    echo '<span class="badge ' . ($is_upcoming ? 'badge-upcoming' : 'badge-past') . '">';
                                    echo $is_upcoming ? 'Sắp tới' : 'Đã qua';
                                    echo '</span>';
                                    echo '</td>';
                                    echo '<td>';
                                    if (!$is_upcoming) {
                                        echo '<button class="btn btn-danger btn-sm" onclick="deleteAppointment(' . $appt['id'] . ')"><i class="fas fa-trash"></i></button>';
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../Layouts/Footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter appointments by type (all, upcoming, past)
        function filterAppointments(filterType) {
            const rows = document.querySelectorAll('.appointment-row');
            const customerSections = document.querySelectorAll('.customer-appointments');
            
            // First, show all rows and sections
            if (filterType === 'all') {
                rows.forEach(function(row) {
                    row.style.display = '';
                });
                customerSections.forEach(function(section) {
                    section.style.display = '';
                });
            } else {
                // Hide all rows first
                rows.forEach(function(row) {
                    row.style.display = 'none';
                });
                
                // Show only matching rows
                rows.forEach(function(row) {
                    const appointmentType = row.getAttribute('data-appointment-type');
                    if (appointmentType === filterType) {
                        row.style.display = '';
                    }
                });
                
                // Show/hide customer sections based on visible appointments
                customerSections.forEach(function(section) {
                    const visibleRows = section.querySelectorAll('.appointment-row[style=""], .appointment-row:not([style*="display: none"])');
                    if (visibleRows.length > 0) {
                        section.style.display = '';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
            
            // Update card styles to show active filter
            document.querySelectorAll('.stats-card.clickable-card').forEach(function(card) {
                card.style.borderLeft = '';
            });
            
            // Highlight active filter card
            try {
                if (filterType === 'all') {
                    const card = document.querySelector('.stats-card.border-left-primary.clickable-card');
                    if (card) card.style.borderLeft = '4px solid #007bff';
                } else if (filterType === 'upcoming') {
                    const card = document.querySelector('.stats-card.border-left-success.clickable-card');
                    if (card) card.style.borderLeft = '4px solid #28a745';
                } else if (filterType === 'past') {
                    const card = document.querySelector('.stats-card.border-left-warning.clickable-card');
                    if (card) card.style.borderLeft = '4px solid #ffc107';
                }
            } catch (e) {
                console.error('Error highlighting filter card:', e);
            }
        }
        
        // Delete Appointment Function
        function deleteAppointment(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa lịch hẹn đã qua này không? Hành động này không thể hoàn tác.')) {
                return;
            }
            
            fetch('../Processing/Employee_Delete_Appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    id: String(id),
                    csrf_token: <?php echo json_encode(csrf_token(), JSON_UNESCAPED_SLASHES); ?>
                }).toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.error || 'Không thể xóa lịch hẹn'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi xóa lịch hẹn.');
            });
        }
        
        // Initialize - show all appointments by default
        window.addEventListener('DOMContentLoaded', function() {
            filterAppointments('all');
            
            // Show success/error messages (only once, then remove from URL)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'order_approved') {
                const orderId = urlParams.get('order_id');
                // Show notification only once, then remove URL parameters to prevent re-showing on refresh
                if (orderId && !sessionStorage.getItem('order_approved_' + orderId)) {
                    alert('Đơn hàng #' + orderId + ' đã được duyệt thành công! Shipper có thể nhận hàng.');
                    sessionStorage.setItem('order_approved_' + orderId, 'true');
                    // Remove URL parameters to prevent showing again on refresh
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }
            } else if (urlParams.get('error') === 'order_not_found') {
                if (!sessionStorage.getItem('error_order_not_found')) {
                    alert('Không tìm thấy đơn hàng hoặc đơn hàng đã được duyệt.');
                    sessionStorage.setItem('error_order_not_found', 'true');
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }
            } else if (urlParams.get('error') === 'approval_failed') {
                if (!sessionStorage.getItem('error_approval_failed')) {
                    alert('Lỗi khi duyệt đơn hàng. Vui lòng thử lại.');
                    sessionStorage.setItem('error_approval_failed', 'true');
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }
            }
        });
    </script>
</body>
</html>
