<?php
/**
 * Admin Shipper Registration Approval Page
 * Allows Admin to view and approve/reject shipper registration requests
 */

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

// Session is already started in headHTML.php, no need to start again

// Access control is handled by Admin_Access_Check.php (included via headHTML.php)
// Just read admin session info for display purposes
$admin_role = $_SESSION['admin_role'] ?? null;
$is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

// Handle approval/rejection/delete/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve', 'reject', 'delete', 'update'
    
    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        // Update shipper request status
        $update_sql = "UPDATE shipper_requests SET status = '$status', updated_at = NOW() WHERE id = $request_id";
        mysqli_query($conn, $update_sql);
        
        // If approved, update user role based on their registration
        if ($action === 'approve') {
            $request_info = mysqli_fetch_array(mysqli_query($conn, "SELECT user_id FROM shipper_requests WHERE id = $request_id"));
            if ($request_info) {
                $user_id = $request_info['user_id'];
                // Get user's current role to determine if shipper or employee
                $user_info = mysqli_fetch_array(mysqli_query($conn, "SELECT Role FROM khachhang WHERE ID_KhachHang = $user_id"));
                $target_role = ($user_info && $user_info['Role'] === 'employee') ? 'employee' : 'shipper';
                mysqli_query($conn, "UPDATE khachhang SET Role = '$target_role' WHERE ID_KhachHang = $user_id");
            }
        }
        
        header("Location: Admin_Shipper_Registration.php?msg=" . ($action === 'approve' ? 'approved' : 'rejected'));
        exit();
    } elseif ($action === 'delete') {
        // Delete shipper/staff account
        $request_info = mysqli_fetch_array(mysqli_query($conn, "SELECT user_id FROM shipper_requests WHERE id = $request_id"));
        if ($request_info) {
            $user_id = $request_info['user_id'];
            // Delete shipper request
            mysqli_query($conn, "DELETE FROM shipper_requests WHERE id = $request_id");
            // Reset user role to customer
            mysqli_query($conn, "UPDATE khachhang SET Role = 'customer' WHERE ID_KhachHang = $user_id");
        }
        header("Location: Admin_Shipper_Registration.php?msg=deleted");
        exit();
    } elseif ($action === 'update') {
        // Handle edit form submission
        $license = mysqli_real_escape_string($conn, $_POST['license'] ?? '');
        $vehicle = mysqli_real_escape_string($conn, $_POST['vehicle'] ?? '');
        $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
        $hometown = mysqli_real_escape_string($conn, $_POST['hometown'] ?? '');
        $permanent_address = mysqli_real_escape_string($conn, $_POST['permanent_address'] ?? '');
        $temporary_address = mysqli_real_escape_string($conn, $_POST['temporary_address'] ?? '');
        
        $update_sql = "UPDATE shipper_requests SET 
                      license = '$license',
                      vehicle = '$vehicle',
                      note = '$note',
                      hometown = '$hometown',
                      permanent_address = '$permanent_address',
                      temporary_address = '$temporary_address',
                      updated_at = NOW()
                      WHERE id = $request_id";
        mysqli_query($conn, $update_sql);
        
        header("Location: Admin_Shipper_Registration.php?msg=updated");
        exit();
    }
}


// Get filter
$filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($filter !== 'all') {
    $where[] = "sr.status = '" . mysqli_real_escape_string($conn, $filter) . "'";
}

if ($search) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where[] = "(k.HoVaTen LIKE '%$search_escaped%' OR k.Email LIKE '%$search_escaped%' OR k.SDT LIKE '%$search_escaped%' OR sr.license LIKE '%$search_escaped%')";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get shipper requests and also get approved shippers/staff for management
$sql = "SELECT sr.*, k.HoVaTen, k.Email, k.SDT, k.DiaChi, k.Role, k.ID_KhachHang
        FROM shipper_requests sr
        LEFT JOIN khachhang k ON sr.user_id = k.ID_KhachHang
        $where_clause
        ORDER BY sr.created_at DESC";

$result = mysqli_query($conn, $sql);
$requests = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
}

// Get statistics
$stats_sql = "SELECT status, COUNT(*) as count FROM shipper_requests GROUP BY status";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
if ($stats_result) {
    while ($row = mysqli_fetch_assoc($stats_result)) {
        $stats[$row['status']] = intval($row['count']);
    }
}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("../Layouts/SlideBar.php"); ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("../Layouts/TopBar.php"); ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Quản Lý Shipper & Nhân Viên</h1>
                    <p class="mb-4">Quản lý và phê duyệt các yêu cầu đăng ký shipper/nhân viên</p>
                    
                    <!-- Information Note -->
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle"></i> Yêu cầu đăng ký</h5>
                        <p class="mb-0"><strong>Lưu ý:</strong> Người dùng có thể đăng ký làm nhân viên hoặc đăng ký làm shipper. Vui lòng xem xét và phê duyệt các yêu cầu đăng ký bên dưới.</p>
                    </div>

                    <?php if (isset($_GET['msg'])): ?>
                        <?php
                        $msg = $_GET['msg'];
                        $msg_text = [
                            'approved' => 'Đã phê duyệt yêu cầu shipper thành công!',
                            'rejected' => 'Đã từ chối yêu cầu shipper!',
                            'deleted' => 'Đã xóa tài khoản shipper/staff thành công!',
                            'updated' => 'Đã cập nhật thông tin thành công!'
                        ];
                        $msg_class = [
                            'approved' => 'success',
                            'rejected' => 'warning',
                            'deleted' => 'info',
                            'updated' => 'success'
                        ];
                        ?>
                        <div class="alert alert-<?php echo $msg_class[$msg] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $msg_text[$msg] ?? 'Thao tác thành công!'; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chờ phê duyệt</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đã phê duyệt</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['approved']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Đã từ chối</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['rejected']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="get" class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Lọc theo trạng thái:</label>
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Chờ phê duyệt</option>
                                        <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Đã phê duyệt</option>
                                        <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Đã từ chối</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Tìm kiếm:</label>
                                    <input type="text" name="search" class="form-control" placeholder="Tên, Email, SĐT, CMND/CCCD..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Tìm kiếm</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Shipper Requests Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Danh sách yêu cầu đăng ký shipper</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Thông tin người đăng ký</th>
                                            <th>Số CMND/CCCD</th>
                                            <th>Quê quán</th>
                                            <th>Địa chỉ thường trú</th>
                                            <th>Địa chỉ tạm trú</th>
                                            <th>Phương tiện</th>
                                            <th>Ảnh CMND/CCCD</th>
                                            <th>Ghi chú</th>
                                            <th>Hình thức làm việc</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày đăng ký</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($requests)): ?>
                                            <tr>
                                                <td colspan="12" class="text-center">Không có yêu cầu nào</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($requests as $index => $req): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($req['HoVaTen']); ?></strong><br>
                                                        <small>Email: <?php echo htmlspecialchars($req['Email']); ?></small><br>
                                                        <small>SĐT: <?php echo htmlspecialchars($req['SDT']); ?></small><br>
                                                        <?php
                                                        // Display role intention
                                                        $user_role = $req['Role'] ?? 'customer';
                                                        $role_intention = '';
                                                        $role_badge_class = '';
                                                        if ($user_role === 'shipper') {
                                                            $role_intention = 'Registering as a Shipper';
                                                            $role_badge_class = 'info';
                                                        } elseif ($user_role === 'employee') {
                                                            $role_intention = 'Registering as an Employee';
                                                            $role_badge_class = 'primary';
                                                        } else {
                                                            $role_intention = 'Customer';
                                                            $role_badge_class = 'secondary';
                                                        }
                                                        ?>
                                                        <small class="text-muted">
                                                            <strong>* Role Intention:</strong> 
                                                            <span class="badge badge-<?php echo $role_badge_class; ?>"><?php echo htmlspecialchars($role_intention); ?></span>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($req['license']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['hometown'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($req['permanent_address'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($req['temporary_address'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($req['vehicle']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $id_card_front = isset($req['id_card_front']) && !empty($req['id_card_front']) ? $req['id_card_front'] : null;
                                                        $id_card_back = isset($req['id_card_back']) && !empty($req['id_card_back']) ? $req['id_card_back'] : null;
                                                        
                                                        // Construct correct paths - images are stored as 'uploads/shipper_id_cards/filename'
                                                        // Use absolute URL path from web root
                                                        $front_path = null;
                                                        $back_path = null;
                                                        
                                                        if ($id_card_front) {
                                                            // Path stored in DB: 'uploads/shipper_id_cards/filename'
                                                            // Convert to web-accessible URL
                                                            $stored_path = htmlspecialchars($id_card_front);
                                                            // Remove any leading slashes
                                                            $stored_path = ltrim($stored_path, '/');
                                                            // Build absolute path from web root
                                                            $front_path = '/TTTT/WebBanHangMyPham/src/' . $stored_path;
                                                        }
                                                        
                                                        if ($id_card_back) {
                                                            $stored_path = htmlspecialchars($id_card_back);
                                                            $stored_path = ltrim($stored_path, '/');
                                                            $back_path = '/TTTT/WebBanHangMyPham/src/' . $stored_path;
                                                        }
                                                        ?>
                                                        <div class="d-flex flex-column gap-2">
                                                            <?php if ($front_path): ?>
                                                                <div>
                                                                    <small class="d-block mb-1 text-muted">Mặt trước:</small>
                                                                    <img src="<?php echo $front_path; ?>" 
                                                                         alt="CMND/CCCD Mặt trước" 
                                                                         class="img-thumbnail id-card-thumbnail" 
                                                                         style="max-width: 100px; max-height: 60px; cursor: pointer; object-fit: cover;"
                                                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'60\'%3E%3Crect width=\'100\' height=\'60\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImage not found%3C/text%3E%3C/svg%3E';"
                                                                         onclick="openImageModal('<?php echo $front_path; ?>', 'CMND/CCCD Mặt trước')">
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if ($back_path): ?>
                                                                <div>
                                                                    <small class="d-block mb-1 text-muted">Mặt sau:</small>
                                                                    <img src="<?php echo $back_path; ?>" 
                                                                         alt="CMND/CCCD Mặt sau" 
                                                                         class="img-thumbnail id-card-thumbnail" 
                                                                         style="max-width: 100px; max-height: 60px; cursor: pointer; object-fit: cover;"
                                                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'60\'%3E%3Crect width=\'100\' height=\'60\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImage not found%3C/text%3E%3C/svg%3E';"
                                                                         onclick="openImageModal('<?php echo $back_path; ?>', 'CMND/CCCD Mặt sau')">
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!$front_path && !$back_path): ?>
                                                                <span class="text-muted">Chưa có</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($req['note'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php 
                                                        $work_location = $req['work_location'] ?? '';
                                                        if ($work_location === 'in-store') {
                                                            echo '<span class="badge badge-success">Làm việc tại cửa hàng</span>';
                                                        } elseif ($work_location === 'remote') {
                                                            echo '<span class="badge badge-info">Làm việc online</span>';
                                                        } else {
                                                            echo '<span class="text-muted">N/A</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'pending' => 'warning',
                                                            'approved' => 'success',
                                                            'rejected' => 'danger'
                                                        ];
                                                        $status_text = [
                                                            'pending' => 'Chờ phê duyệt',
                                                            'approved' => 'Đã phê duyệt',
                                                            'rejected' => 'Đã từ chối'
                                                        ];
                                                        $status = $req['status'];
                                                        ?>
                                                        <span class="badge badge-<?php echo $status_class[$status]; ?>">
                                                            <?php echo $status_text[$status]; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($req['status'] === 'pending'): ?>
                                                            <!-- Pending: Show Approve and Reject buttons -->
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                <input type="hidden" name="action" value="approve">
                                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Bạn có chắc chắn muốn phê duyệt yêu cầu này?');">
                                                                    <i class="fas fa-check"></i> Đồng ý
                                                                </button>
                                                            </form>
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn từ chối yêu cầu này?');">
                                                                    <i class="fas fa-times"></i> Từ chối
                                                                </button>
                                                            </form>
                                                        <?php elseif ($req['status'] === 'approved'): ?>
                                                            <!-- Approved: Show Edit and Delete buttons -->
                                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal<?php echo $req['id']; ?>">
                                                                <i class="fas fa-edit"></i> Sửa
                                                            </button>
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa yêu cầu này?');">
                                                                    <i class="fas fa-trash"></i> Xóa
                                                                </button>
                                                            </form>
                                                        <?php elseif ($req['status'] === 'rejected'): ?>
                                                            <!-- Rejected: Show only Delete button -->
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa yêu cầu này?');">
                                                                    <i class="fas fa-trash"></i> Xóa
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <?php include("../Layouts/Link.php"); ?>
    
    <!-- Edit Modals for Approved Requests -->
    <?php foreach ($requests as $req): ?>
        <?php if ($req['status'] === 'approved'): ?>
            <div class="modal fade" id="editModal<?php echo $req['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $req['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?php echo $req['id']; ?>">Sửa thông tin</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <input type="hidden" name="action" value="update">
                                
                                <div class="form-group">
                                    <label for="license<?php echo $req['id']; ?>">Số CMND/CCCD</label>
                                    <input type="text" class="form-control" id="license<?php echo $req['id']; ?>" name="license" value="<?php echo htmlspecialchars($req['license'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="vehicle<?php echo $req['id']; ?>">Phương tiện</label>
                                    <input type="text" class="form-control" id="vehicle<?php echo $req['id']; ?>" name="vehicle" value="<?php echo htmlspecialchars($req['vehicle'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="hometown<?php echo $req['id']; ?>">Quê quán</label>
                                    <input type="text" class="form-control" id="hometown<?php echo $req['id']; ?>" name="hometown" value="<?php echo htmlspecialchars($req['hometown'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="permanent_address<?php echo $req['id']; ?>">Địa chỉ thường trú</label>
                                    <textarea class="form-control" id="permanent_address<?php echo $req['id']; ?>" name="permanent_address" rows="2"><?php echo htmlspecialchars($req['permanent_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="temporary_address<?php echo $req['id']; ?>">Địa chỉ tạm trú</label>
                                    <textarea class="form-control" id="temporary_address<?php echo $req['id']; ?>" name="temporary_address" rows="2"><?php echo htmlspecialchars($req['temporary_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="note<?php echo $req['id']; ?>">Ghi chú</label>
                                    <textarea class="form-control" id="note<?php echo $req['id']; ?>" name="note" rows="3"><?php echo htmlspecialchars($req['note'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <!-- Image Modal for ID Card Viewing -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Xem ảnh CMND/CCCD</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Image" class="img-fluid" style="max-height: 80vh; width: auto;"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect width=\'400\' height=\'300\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'18\'%3EImage not found%3C/text%3E%3C/svg%3E';">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openImageModal(imageSrc, imageTitle) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModalLabel').textContent = imageTitle;
            $('#imageModal').modal('show');
        }
        
        // Close modal on ESC key
        $(document).keydown(function(e) {
            if (e.keyCode == 27) { // ESC key
                $('#imageModal').modal('hide');
            }
        });
    </script>
    
    <style>
        .id-card-thumbnail {
            transition: all 0.2s ease;
        }
        .id-card-thumbnail:hover {
            opacity: 0.8;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .gap-2 > div {
            margin-bottom: 8px;
        }
    </style>
</body>
</html>

