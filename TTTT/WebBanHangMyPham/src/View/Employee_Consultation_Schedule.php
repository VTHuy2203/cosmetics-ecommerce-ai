<?php
/**
 * Employee Consultation Schedule Page
 * Read-only consultation schedule view for employees
 */

include("../Layouts/Head.php");
include("../DB_Connect.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $dsn = "mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4";
    return new PDO($dsn, "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

$pdo = pdo_connect_chat();

// Get consultation bookings
$stmt = $pdo->query("
    SELECT 
        ca.id, ca.user_id, ca.session_id, ca.appt_at, ca.note,
        ca.message_content, ca.message_sent_at, ca.created_at,
        ca.guest_name, ca.guest_phone, ca.guest_address,
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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch tư vấn - Nhân viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-card {
            margin-bottom: 20px;
        }
        .consultation-table {
            margin-top: 20px;
        }
        .badge-upcoming {
            background-color: #28a745;
            color: white;
        }
        .badge-past {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <?php include("../Layouts/Header_View.php"); ?>
    <?php include("../Layouts/Navbar.php"); ?>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> Lịch tư vấn
                    </h2>
                    <a href="Employee_Customer_List.php" class="btn btn-primary">
                        <i class="fas fa-users"></i> Xem danh sách khách hàng
                    </a>
                </div>
                
                <!-- Statistics Cards - Clickable to filter -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card border-left-primary clickable-card" onclick="filterBookings('all')" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Tổng số lịch đặt</h5>
                                <h3 class="text-gray-800"><?php echo $total; ?></h3>
                                <small class="text-muted">Click để xem tất cả</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card border-left-success clickable-card" onclick="filterBookings('upcoming')" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                            <div class="card-body">
                                <h5 class="card-title text-success">Sắp tới</h5>
                                <h3 class="text-gray-800"><?php echo $upcoming; ?></h3>
                                <small class="text-muted">Click để xem lịch sắp tới</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card border-left-warning clickable-card" onclick="filterBookings('past')" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                            <div class="card-body">
                                <h5 class="card-title text-warning">Đã qua</h5>
                                <h3 class="text-gray-800"><?php echo $past; ?></h3>
                                <small class="text-muted">Click để xem lịch đã qua</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consultation Schedule Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Danh sách lịch đặt tư vấn</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Chưa có lịch đặt tư vấn nào.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover consultation-table">
                                    <thead class="table-dark">
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
                                    <tbody id="bookingsTableBody">
                                        <?php foreach ($bookings as $b): ?>
                                            <?php
                                            $appt_time = new DateTime($b['appt_at']);
                                            $is_upcoming = $appt_time > $now;
                                            
                                            // Determine customer info (User or Guest)
                                            if ($b['user_id']) {
                                                $customer_name = $b['HoVaTen'];
                                                $contact_info = [];
                                                if ($b['Email']) $contact_info[] = htmlspecialchars($b['Email']);
                                                if ($b['SDT']) $contact_info[] = htmlspecialchars($b['SDT']);
                                                $contact_str = !empty($contact_info) ? implode(' | ', $contact_info) : 'N/A';
                                                $address = ''; // Address usually in customer profile, not selected here yet
                                            } else {
                                                $customer_name = $b['guest_name'] ?: "Khách vãng lai #" . substr($b['session_id'], 0, 8);
                                                $contact_str = htmlspecialchars($b['guest_phone'] ?: 'N/A');
                                                $address = $b['guest_address'] ? '<br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($b['guest_address']) . '</small>' : '';
                                            }
                                            
                                            $booking_class = $is_upcoming ? 'booking-upcoming' : 'booking-past';
                                            ?>
                                            <tr class="booking-row <?php echo $booking_class; ?>" data-booking-type="<?php echo $is_upcoming ? 'upcoming' : 'past'; ?>">
                                                <td><?php echo htmlspecialchars($b['id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($customer_name); ?></strong>
                                                    <?php if (!$b['user_id']): ?>
                                                        <span class="badge bg-secondary" style="font-size: 0.7em;">Vãng lai</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $contact_str; ?>
                                                    </small>
                                                    <?php echo $address; ?>
                                                </td>
                                                <td><?php echo $appt_time->format('d/m/Y H:i'); ?></td>
                                                <td style="max-width: 300px; word-wrap: break-word;" title="<?php echo htmlspecialchars($b['message_content'] ?: 'N/A'); ?>">
                                                    <?php echo htmlspecialchars($b['message_content'] ?: 'N/A'); ?>
                                                </td>
                                                <td><?php echo $b['message_sent_at'] ? date('d/m/Y H:i', strtotime($b['message_sent_at'])) : 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars($b['note'] ?: '-'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $is_upcoming ? 'badge-upcoming' : 'badge-past'; ?>">
                                                        <?php echo $is_upcoming ? 'Sắp tới' : 'Đã qua'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../Layouts/Footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter bookings by type (all, upcoming, past)
        function filterBookings(filterType) {
            const rows = document.querySelectorAll('.booking-row');
            
            if (!rows || rows.length === 0) {
                console.log('No booking rows found');
                return;
            }
            
            rows.forEach(function(row) {
                const bookingType = row.getAttribute('data-booking-type');
                
                if (!bookingType) {
                    console.log('Row missing data-booking-type attribute');
                    return;
                }
                
                if (filterType === 'all') {
                    row.style.display = '';
                } else if (filterType === 'upcoming') {
                    row.style.display = (bookingType === 'upcoming') ? '' : 'none';
                } else if (filterType === 'past') {
                    row.style.display = (bookingType === 'past') ? '' : 'none';
                }
            });
            
            // Update card styles to show active filter
            const allCards = document.querySelectorAll('.stats-card');
            allCards.forEach(function(card) {
                card.style.borderLeft = '';
            });
            
            // Highlight active filter card
            try {
                if (filterType === 'all') {
                    const card = document.querySelector('.stats-card.border-left-primary');
                    if (card) card.style.borderLeft = '4px solid #007bff';
                } else if (filterType === 'upcoming') {
                    const card = document.querySelector('.stats-card.border-left-success');
                    if (card) card.style.borderLeft = '4px solid #28a745';
                } else if (filterType === 'past') {
                    const card = document.querySelector('.stats-card.border-left-warning');
                    if (card) card.style.borderLeft = '4px solid #ffc107';
                }
            } catch (e) {
                console.error('Error highlighting filter card:', e);
            }
        }
        
        // Initialize - show all bookings by default
        window.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for DOM to be fully ready
            setTimeout(function() {
                filterBookings('all');
            }, 100);
        });
        
        // Also call on page load (in case DOMContentLoaded already fired)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    filterBookings('all');
                }, 100);
            });
        } else {
            setTimeout(function() {
                filterBookings('all');
            }, 100);
        }
    </script>
</body>
</html>

