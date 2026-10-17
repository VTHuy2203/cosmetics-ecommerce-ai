<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once __DIR__ . '/../Layouts/Admin_Access_Check.php';
require_once dirname(__DIR__, 3) . '/config/uploads.php';
require_once dirname(__DIR__, 3) . '/config/admin_activity.php';

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$adminRole = (string) ($_SESSION['admin_role'] ?? '');
if ($adminId < 1 || !in_array($adminRole, ['admin', 'manager'], true)) {
    http_response_code(403);
    exit('Bạn không có quyền quản lý tài khoản quản trị.');
}

function admin_profile_redirect(string $type, string $code, string $anchor): never
{
    header(
        'Location: /TTTT/AdminManage/src/Users/Admin_Profile.php?'
        . rawurlencode($type) . '=' . rawurlencode($code) . '#' . rawurlencode($anchor)
    );
    exit;
}

function admin_profile_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_profile_delete_avatar(string $publicPath): void
{
    $prefix = '/TTTT/Image_upload/admin-avatars/';
    if (!str_starts_with($publicPath, $prefix)) {
        return;
    }

    $fileName = basename($publicPath);
    if ($fileName === '' || $fileName === '.' || $fileName === '..') {
        return;
    }
    $absolutePath = dirname(__DIR__, 3) . '/Image_upload/admin-avatars/' . $fileName;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function load_admin_account(mysqli $conn, int $adminId): array
{
    $statement = $conn->prepare(
        "SELECT ID_KhachHang, TenDangNhap, MatKhau, HoVaTen, Email, SDT, DiaChi, Role,
                AnhDaiDien, ThoiGianTao, ThoiGianCapNhat
         FROM khachhang
         WHERE ID_KhachHang = ? AND Role IN ('admin', 'manager')
         LIMIT 1"
    );
    $statement->bind_param('i', $adminId);
    $statement->execute();
    $account = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$account) {
        unset($_SESSION['admin_user'], $_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_is_admin']);
        header('Location: /TTTT/AdminManage/src/View/Admin_Login.php');
        exit;
    }

    return $account;
}

$adminAccount = load_admin_account($conn, $adminId);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if ($fullName === '' || mb_strlen($fullName) > 50
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100
            || ($phone !== '' && !preg_match('/^[0-9+().\s-]{8,20}$/', $phone))
            || mb_strlen($address) > 255) {
            admin_profile_redirect('error', 'invalid_profile', 'profile');
        }

        $emailCheck = $conn->prepare(
            'SELECT ID_KhachHang FROM khachhang WHERE Email = ? AND ID_KhachHang <> ? LIMIT 1'
        );
        $emailCheck->bind_param('si', $email, $adminId);
        $emailCheck->execute();
        $emailExists = $emailCheck->get_result()->fetch_assoc() !== null;
        $emailCheck->close();
        if ($emailExists) {
            admin_profile_redirect('error', 'email_exists', 'profile');
        }

        $changedFields = [];
        foreach ([
            'HoVaTen' => $fullName,
            'Email' => $email,
            'SDT' => $phone,
            'DiaChi' => $address,
        ] as $field => $newValue) {
            if ((string) ($adminAccount[$field] ?? '') !== $newValue) {
                $changedFields[] = $field;
            }
        }

        if ($changedFields !== []) {
            $conn->begin_transaction();
            try {
                $update = $conn->prepare(
                    'UPDATE khachhang SET HoVaTen = ?, Email = ?, SDT = ?, DiaChi = ? WHERE ID_KhachHang = ?'
                );
                $update->bind_param('ssssi', $fullName, $email, $phone, $address, $adminId);
                $update->execute();
                $update->close();
                admin_activity_log(
                    $conn,
                    $adminId,
                    'profile_updated',
                    'Đã cập nhật thông tin hồ sơ quản trị.',
                    ['fields' => $changedFields]
                );
                $conn->commit();
            } catch (Throwable $error) {
                $conn->rollback();
                error_log('Admin profile update failed: ' . $error->getMessage());
                admin_profile_redirect('error', 'update_failed', 'profile');
            }
        }
        admin_profile_redirect('success', $changedFields === [] ? 'no_changes' : 'profile_updated', 'profile');
    }

    if ($action === 'avatar') {
        $avatar = $_FILES['avatar'] ?? null;
        if (!is_array($avatar)) {
            admin_profile_redirect('error', 'invalid_avatar', 'avatar');
        }

        $uploadDirectory = dirname(__DIR__, 3) . '/Image_upload/admin-avatars';
        $newAbsolutePath = null;
        try {
            $fileName = store_public_image_upload($avatar, $uploadDirectory, 3 * 1024 * 1024);
            $newAbsolutePath = $uploadDirectory . '/' . $fileName;
            $newPublicPath = '/TTTT/Image_upload/admin-avatars/' . $fileName;
            $oldPublicPath = (string) ($adminAccount['AnhDaiDien'] ?? '');

            $update = $conn->prepare('UPDATE khachhang SET AnhDaiDien = ? WHERE ID_KhachHang = ?');
            $update->bind_param('si', $newPublicPath, $adminId);
            $update->execute();
            $update->close();
            admin_activity_log($conn, $adminId, 'avatar_updated', 'Đã cập nhật ảnh đại diện quản trị.');
            admin_profile_delete_avatar($oldPublicPath);
        } catch (Throwable $error) {
            if (is_string($newAbsolutePath) && is_file($newAbsolutePath)) {
                @unlink($newAbsolutePath);
            }
            error_log('Admin avatar update failed: ' . $error->getMessage());
            admin_profile_redirect('error', 'invalid_avatar', 'avatar');
        }
        admin_profile_redirect('success', 'avatar_updated', 'avatar');
    }

    if ($action === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!password_matches_and_upgrade(
            $conn,
            $adminId,
            $currentPassword,
            (string) $adminAccount['MatKhau']
        )) {
            admin_profile_redirect('error', 'current_password_invalid', 'security');
        }
        if ($newPassword !== $confirmPassword) {
            admin_profile_redirect('error', 'password_mismatch', 'security');
        }
        if (strlen($newPassword) < 12 || strlen($newPassword) > 128
            || !preg_match('/[a-z]/', $newPassword)
            || !preg_match('/[A-Z]/', $newPassword)
            || !preg_match('/[0-9]/', $newPassword)
            || !preg_match('/[^A-Za-z0-9]/', $newPassword)
            || stripos($newPassword, (string) $adminAccount['TenDangNhap']) !== false) {
            admin_profile_redirect('error', 'weak_password', 'security');
        }
        if (hash_equals($currentPassword, $newPassword)) {
            admin_profile_redirect('error', 'password_reused', 'security');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE khachhang SET MatKhau = ? WHERE ID_KhachHang = ?');
        $update->bind_param('si', $passwordHash, $adminId);
        $update->execute();
        $update->close();
        admin_activity_log($conn, $adminId, 'password_changed', 'Đã thay đổi mật khẩu quản trị.');
        session_regenerate_id(true);
        admin_profile_redirect('success', 'password_changed', 'security');
    }

    admin_profile_redirect('error', 'invalid_action', 'profile');
}

$adminAccount = load_admin_account($conn, $adminId);
$activityStatement = $conn->prepare(
    'SELECT action, description, ip_address, created_at
     FROM admin_account_activity
     WHERE admin_id = ?
     ORDER BY id DESC
     LIMIT 30'
);
$activityStatement->bind_param('i', $adminId);
$activityStatement->execute();
$activities = $activityStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$activityStatement->close();

$successMessages = [
    'profile_updated' => 'Thông tin tài khoản đã được cập nhật.',
    'avatar_updated' => 'Ảnh đại diện đã được cập nhật.',
    'password_changed' => 'Mật khẩu đã được thay đổi an toàn.',
    'no_changes' => 'Không có thông tin nào thay đổi.',
];
$errorMessages = [
    'invalid_profile' => 'Thông tin hồ sơ không hợp lệ. Vui lòng kiểm tra lại các trường.',
    'email_exists' => 'Email này đã được sử dụng bởi tài khoản khác.',
    'update_failed' => 'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
    'invalid_avatar' => 'Ảnh không hợp lệ. Chỉ dùng JPEG, PNG, WebP hoặc GIF, tối đa 3 MB.',
    'current_password_invalid' => 'Mật khẩu hiện tại không chính xác.',
    'password_mismatch' => 'Mật khẩu xác nhận không khớp.',
    'weak_password' => 'Mật khẩu mới phải có 12–128 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; không chứa tên đăng nhập.',
    'password_reused' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
    'invalid_action' => 'Yêu cầu không hợp lệ.',
];
$successCode = (string) ($_GET['success'] ?? '');
$errorCode = (string) ($_GET['error'] ?? '');
$avatarPath = (string) ($adminAccount['AnhDaiDien'] ?? '');
if (!str_starts_with($avatarPath, '/TTTT/')) {
    $avatarPath = '/TTTT/AdminManage/img/undraw_profile.svg';
}
$activityStyles = [
    'login' => ['Đăng nhập', 'fa-sign-in-alt', 'success'],
    'profile_updated' => ['Hồ sơ', 'fa-user-edit', 'primary'],
    'avatar_updated' => ['Ảnh đại diện', 'fa-camera', 'info'],
    'password_changed' => ['Mật khẩu', 'fa-key', 'warning'],
];

include __DIR__ . '/../Layouts/headHTML.php';
?>
<body id="page-top">
<div id="wrapper">
    <?php include __DIR__ . '/../Layouts/SlideBar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include __DIR__ . '/../Layouts/TopBar.php'; ?>
            <div class="container-fluid pb-5">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 class="h3 mb-1 text-gray-800">Quản lý tài khoản quản trị</h1>
                        <p class="mb-0 text-muted">Cập nhật hồ sơ, bảo mật và theo dõi hoạt động tài khoản.</p>
                    </div>
                    <span class="badge badge-success px-3 py-2 mt-3 mt-sm-0">
                        <i class="fas fa-shield-alt mr-1"></i><?php echo admin_profile_h(strtoupper((string) $adminAccount['Role'])); ?>
                    </span>
                </div>

                <?php if (isset($successMessages[$successCode])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><?php echo admin_profile_h($successMessages[$successCode]); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($errorMessages[$errorCode])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo admin_profile_h($errorMessages[$errorCode]); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-4 mb-4">
                        <div class="card shadow h-100" id="avatar">
                            <div class="card-body text-center p-4">
                                <div class="position-relative d-inline-block mb-3">
                                    <img id="avatarPreview" src="<?php echo admin_profile_h($avatarPath); ?>"
                                         class="rounded-circle border shadow-sm" alt="Ảnh đại diện admin"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                    <span class="position-absolute badge badge-success rounded-circle"
                                          style="right: 8px; bottom: 8px; width: 24px; height: 24px; border: 3px solid white;">&nbsp;</span>
                                </div>
                                <h4 class="font-weight-bold text-gray-900 mb-1"><?php echo admin_profile_h($adminAccount['HoVaTen']); ?></h4>
                                <p class="text-muted mb-1">@<?php echo admin_profile_h($adminAccount['TenDangNhap']); ?></p>
                                <p class="small text-muted mb-4"><?php echo admin_profile_h($adminAccount['Email']); ?></p>

                                <form method="post" enctype="multipart/form-data" class="text-left">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="avatar">
                                    <label class="font-weight-bold small text-uppercase text-gray-600" for="avatarInput">Đổi ảnh đại diện</label>
                                    <div class="custom-file mb-2">
                                        <input type="file" class="custom-file-input" id="avatarInput" name="avatar"
                                               accept="image/jpeg,image/png,image/webp,image/gif" required>
                                        <label class="custom-file-label text-truncate" for="avatarInput">Chọn ảnh...</label>
                                    </div>
                                    <small class="form-text text-muted mb-3">JPEG, PNG, WebP hoặc GIF. Tối đa 3 MB.</small>
                                    <button class="btn btn-info btn-block" type="submit">
                                        <i class="fas fa-camera mr-2"></i>Cập nhật ảnh
                                    </button>
                                </form>
                            </div>
                            <div class="card-footer bg-light small text-muted">
                                <div class="d-flex justify-content-between mb-1"><span>Ngày tạo</span><strong><?php echo admin_profile_h((string) $adminAccount['ThoiGianTao']); ?></strong></div>
                                <div class="d-flex justify-content-between"><span>Cập nhật gần nhất</span><strong><?php echo admin_profile_h((string) $adminAccount['ThoiGianCapNhat']); ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card shadow mb-4" id="profile">
                            <div class="card-header py-3 d-flex align-items-center">
                                <i class="fas fa-user-circle text-primary mr-2"></i>
                                <h6 class="m-0 font-weight-bold text-primary">Thông tin tài khoản</h6>
                            </div>
                            <div class="card-body">
                                <form method="post" autocomplete="on">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="profile">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="adminUsername">Tên đăng nhập</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                                                <input id="adminUsername" class="form-control" value="<?php echo admin_profile_h($adminAccount['TenDangNhap']); ?>" disabled>
                                            </div>
                                            <small class="form-text text-muted">Tên đăng nhập admin được bảo vệ và không thể thay đổi.</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="adminRole">Quyền tài khoản</label>
                                            <input id="adminRole" class="form-control" value="<?php echo admin_profile_h((string) $adminAccount['Role']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="fullName">Họ và tên <span class="text-danger">*</span></label>
                                            <input id="fullName" name="full_name" class="form-control" maxlength="50" required
                                                   value="<?php echo admin_profile_h($adminAccount['HoVaTen']); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="adminEmail">Gmail / Email <span class="text-danger">*</span></label>
                                            <input id="adminEmail" name="email" type="email" class="form-control" maxlength="100" required
                                                   value="<?php echo admin_profile_h($adminAccount['Email']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="adminPhone">Số điện thoại</label>
                                            <input id="adminPhone" name="phone" class="form-control" maxlength="20"
                                                   pattern="[0-9+(). -]{8,20}" value="<?php echo admin_profile_h($adminAccount['SDT']); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="adminAddress">Địa chỉ</label>
                                            <input id="adminAddress" name="address" class="form-control" maxlength="255"
                                                   value="<?php echo admin_profile_h($adminAccount['DiaChi']); ?>">
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="reset" class="btn btn-light mr-2">Khôi phục</button>
                                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-2"></i>Lưu thay đổi</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card shadow mb-4" id="security">
                            <div class="card-header py-3 d-flex align-items-center">
                                <i class="fas fa-shield-alt text-warning mr-2"></i>
                                <h6 class="m-0 font-weight-bold text-warning">Bảo mật và mật khẩu</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light border small">
                                    <i class="fas fa-info-circle text-info mr-2"></i>
                                    Mật khẩu mới phải dài ít nhất 12 ký tự và có chữ hoa, chữ thường, số, ký tự đặc biệt.
                                </div>
                                <form method="post" autocomplete="off">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="password">
                                    <div class="form-group">
                                        <label for="currentPassword">Mật khẩu hiện tại</label>
                                        <input id="currentPassword" name="current_password" type="password" class="form-control" required autocomplete="current-password">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="newPassword">Mật khẩu mới</label>
                                            <input id="newPassword" name="new_password" type="password" class="form-control"
                                                   minlength="12" maxlength="128" required autocomplete="new-password">
                                            <div class="progress mt-2" style="height: 5px;"><div id="passwordStrength" class="progress-bar" style="width: 0;"></div></div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="confirmPassword">Nhập lại mật khẩu mới</label>
                                            <input id="confirmPassword" name="confirm_password" type="password" class="form-control"
                                                   minlength="12" maxlength="128" required autocomplete="new-password">
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-warning px-4"><i class="fas fa-key mr-2"></i>Đổi mật khẩu</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow" id="activity">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <div><i class="fas fa-history text-secondary mr-2"></i><h6 class="d-inline m-0 font-weight-bold text-secondary">Nhật ký hoạt động tài khoản</h6></div>
                        <span class="badge badge-light">30 hoạt động gần nhất</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($activities === []): ?>
                            <div class="text-center text-muted py-5"><i class="fas fa-clipboard-list fa-2x mb-3 d-block"></i>Chưa có hoạt động nào được ghi nhận.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light"><tr><th>Hoạt động</th><th>Mô tả</th><th>IP</th><th>Thời gian</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($activities as $activity):
                                        $style = $activityStyles[$activity['action']] ?? ['Hệ thống', 'fa-circle', 'secondary']; ?>
                                        <tr>
                                            <td><span class="badge badge-<?php echo admin_profile_h($style[2]); ?> px-2 py-2"><i class="fas <?php echo admin_profile_h($style[1]); ?> mr-1"></i><?php echo admin_profile_h($style[0]); ?></span></td>
                                            <td><?php echo admin_profile_h($activity['description']); ?></td>
                                            <td><code><?php echo admin_profile_h($activity['ip_address'] ?: '—'); ?></code></td>
                                            <td class="text-nowrap"><?php echo admin_profile_h((string) $activity['created_at']); ?></td>
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
</div>
<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<?php include __DIR__ . '/../Layouts/Link.php'; ?>
<script>
(function () {
    var avatarInput = document.getElementById('avatarInput');
    var avatarPreview = document.getElementById('avatarPreview');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            var label = this.nextElementSibling;
            if (!file) return;
            if (label) label.textContent = file.name;
            if (file.size > 3 * 1024 * 1024) {
                alert('Ảnh vượt quá dung lượng 3 MB.');
                this.value = '';
                if (label) label.textContent = 'Chọn ảnh...';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (event) { avatarPreview.src = event.target.result; };
            reader.readAsDataURL(file);
        });
    }

    var passwordInput = document.getElementById('newPassword');
    var strength = document.getElementById('passwordStrength');
    if (passwordInput && strength) {
        passwordInput.addEventListener('input', function () {
            var value = this.value;
            var score = 0;
            if (value.length >= 12) score++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
            strength.style.width = (score * 25) + '%';
            strength.className = 'progress-bar ' + (score < 2 ? 'bg-danger' : (score < 4 ? 'bg-warning' : 'bg-success'));
        });
    }
})();
</script>
</body>
</html>
