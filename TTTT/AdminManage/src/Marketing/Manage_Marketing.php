<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once __DIR__ . '/../Layouts/Admin_Access_Check.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/WebBanHangMyPham/src/Services/VoucherService.php';
require_once dirname(__DIR__, 3) . '/WebBanHangMyPham/src/Services/MarketingService.php';

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$adminRole = (string) ($_SESSION['admin_role'] ?? '');
if ($adminId < 1 || !in_array($adminRole, ['admin', 'manager'], true)) {
    http_response_code(403);
    exit('Bạn không có quyền quản lý chương trình khuyến mãi.');
}

function marketing_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function marketing_redirect(string $type, string $message): never
{
    $_SESSION['marketing_flash'] = [$type, $message];
    header('Location: /TTTT/AdminManage/src/Marketing/Manage_Marketing.php');
    exit;
}

function marketing_datetime(string $value, string $label): string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        throw new InvalidArgumentException($label . ' không hợp lệ.');
    }
    return $date->format('Y-m-d H:i:s');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'create_voucher') {
            $code = VoucherService::normalizeCode((string) ($_POST['code'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $percent = (int) ($_POST['discount_percent'] ?? 0);
            $quantity = (int) ($_POST['quantity_total'] ?? 0);
            $perUser = (int) ($_POST['per_user_limit'] ?? 1);
            $minimum = max(0, (int) ($_POST['min_order_amount'] ?? 0));
            $maximumRaw = trim((string) ($_POST['max_discount_amount'] ?? ''));
            $maximum = $maximumRaw === '' ? null : max(0, (int) $maximumRaw);
            $startsAt = marketing_datetime((string) ($_POST['starts_at'] ?? ''), 'Thời gian bắt đầu');
            $endsAt = marketing_datetime((string) ($_POST['ends_at'] ?? ''), 'Thời gian kết thúc');
            $announce = isset($_POST['announce_on_publish']) ? 1 : 0;
            $announcementTitle = trim((string) ($_POST['announcement_title'] ?? ''));
            $announcementMessage = trim((string) ($_POST['announcement_message'] ?? ''));

            if ($code === '') {
                throw new InvalidArgumentException('Mã voucher phải có 2–32 ký tự, chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới. Có thể nhập dấu # ở đầu.');
            }
            if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
                throw new InvalidArgumentException('Tên chương trình phải có từ 3 đến 150 ký tự.');
            }
            if ($percent < 1 || $percent > 100 || $quantity < 1 || $quantity > 1000000 || $perUser < 1 || $perUser > 100) {
                throw new InvalidArgumentException('Phần trăm, số lượng hoặc giới hạn mỗi khách không hợp lệ.');
            }
            if (strtotime($endsAt) <= strtotime($startsAt) || strtotime($endsAt) <= time()) {
                throw new InvalidArgumentException('Hạn sử dụng phải sau thời gian bắt đầu và chưa kết thúc.');
            }
            if (mb_strlen($description) > 500 || mb_strlen($announcementTitle) > 255 || mb_strlen($announcementMessage) > 1000) {
                throw new InvalidArgumentException('Nội dung vượt quá độ dài cho phép.');
            }

            $status = strtotime($startsAt) <= time() ? 'active' : 'scheduled';
            $insert = $conn->prepare(
                'INSERT INTO vouchers
                 (code,name,description,discount_percent,quantity_total,per_user_limit,min_order_amount,max_discount_amount,
                  starts_at,ends_at,status,announce_on_publish,announcement_title,announcement_message,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $insert->bind_param(
                'sssiiiiisssissi',
                $code, $name, $description, $percent, $quantity, $perUser, $minimum, $maximum,
                $startsAt, $endsAt, $status, $announce, $announcementTitle, $announcementMessage, $adminId
            );
            $insert->execute();
            $voucherId = (int) $conn->insert_id;
            if ($status === 'active') {
                (new MarketingService($conn))->publishVoucher($voucherId);
            }
            marketing_redirect('success', 'Đã tạo voucher ' . $code . '.');
        }

        if ($action === 'create_broadcast') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $message = trim((string) ($_POST['message'] ?? ''));
            $type = (string) ($_POST['type'] ?? 'sale');
            $scheduledAt = marketing_datetime((string) ($_POST['scheduled_at'] ?? ''), 'Thời gian gửi');
            if ($title === '' || mb_strlen($title) > 255 || $message === '' || mb_strlen($message) > 1000
                || !in_array($type, ['sale', 'voucher', 'system'], true)) {
                throw new InvalidArgumentException('Thông báo không hợp lệ hoặc vượt quá độ dài cho phép.');
            }
            $insert = $conn->prepare(
                "INSERT INTO broadcast_notifications(title,message,type,scheduled_at,status,created_by)
                 VALUES (?,?,?,?,'scheduled',?)"
            );
            $insert->bind_param('ssssi', $title, $message, $type, $scheduledAt, $adminId);
            $insert->execute();
            $broadcastId = (int) $conn->insert_id;
            if (strtotime($scheduledAt) <= time()) {
                (new MarketingService($conn))->publishBroadcast($broadcastId);
            }
            marketing_redirect('success', 'Đã lưu lịch gửi thông báo.');
        }

        if ($action === 'voucher_status') {
            $voucherId = (int) ($_POST['voucher_id'] ?? 0);
            $target = (string) ($_POST['status'] ?? '');
            if ($voucherId < 1 || !in_array($target, ['paused', 'active'], true)) {
                throw new InvalidArgumentException('Thao tác voucher không hợp lệ.');
            }
            $status = $target;
            if ($target === 'active') {
                $check = $conn->prepare('SELECT starts_at, ends_at FROM vouchers WHERE id = ? LIMIT 1');
                $check->bind_param('i', $voucherId);
                $check->execute();
                $row = $check->get_result()->fetch_assoc();
                if (!$row || strtotime((string) $row['ends_at']) <= time()) {
                    throw new InvalidArgumentException('Voucher đã hết hạn hoặc không tồn tại.');
                }
                $status = strtotime((string) $row['starts_at']) > time() ? 'scheduled' : 'active';
            }
            $update = $conn->prepare("UPDATE vouchers SET status = ? WHERE id = ? AND status <> 'expired'");
            $update->bind_param('si', $status, $voucherId);
            $update->execute();
            if ($status === 'active') {
                (new MarketingService($conn))->publishVoucher($voucherId);
            }
            marketing_redirect('success', $status === 'paused' ? 'Đã tạm dừng voucher.' : 'Đã kích hoạt voucher.');
        }

        if ($action === 'cancel_broadcast') {
            $broadcastId = (int) ($_POST['broadcast_id'] ?? 0);
            $update = $conn->prepare("UPDATE broadcast_notifications SET status = 'cancelled' WHERE id = ? AND status = 'scheduled'");
            $update->bind_param('i', $broadcastId);
            $update->execute();
            marketing_redirect('success', 'Đã hủy lịch thông báo.');
        }

        throw new InvalidArgumentException('Yêu cầu không hợp lệ.');
    } catch (mysqli_sql_exception $error) {
        error_log('Marketing admin database error: ' . $error->getMessage());
        $message = $error->getCode() === 1062 ? 'Mã voucher đã tồn tại.' : 'Không thể lưu dữ liệu marketing.';
        marketing_redirect('danger', $message);
    } catch (InvalidArgumentException $error) {
        marketing_redirect('danger', $error->getMessage());
    } catch (Throwable $error) {
        error_log('Marketing admin failed: ' . $error->getMessage());
        marketing_redirect('danger', 'Không thể xử lý yêu cầu marketing lúc này.');
    }
}

$flash = $_SESSION['marketing_flash'] ?? null;
unset($_SESSION['marketing_flash']);
$vouchers = $conn->query(
    "SELECT v.*,
            (SELECT COUNT(*) FROM voucher_claims c WHERE c.voucher_id = v.id AND c.status = 'claimed') AS quantity_claimed
     FROM vouchers v ORDER BY v.created_at DESC LIMIT 100"
)->fetch_all(MYSQLI_ASSOC);
$broadcasts = $conn->query('SELECT * FROM broadcast_notifications ORDER BY created_at DESC LIMIT 50')->fetch_all(MYSQLI_ASSOC);
$nowInput = date('Y-m-d\TH:i');
$tomorrowInput = date('Y-m-d\TH:i', time() + 86400);

include __DIR__ . '/../Layouts/headHTML.php';
?>
<body id="page-top">
<div id="wrapper">
    <?php include __DIR__ . '/../Layouts/SlideBar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include __DIR__ . '/../Layouts/TopBar.php'; ?>
            <div class="container-fluid pb-5">
                <h1 class="h3 mb-1 text-gray-800">Voucher & thông báo</h1>
                <p class="text-muted mb-4">Phát hành ưu đãi có giới hạn và gửi thông báo realtime đến khách hàng.</p>
                <?php if (is_array($flash)): ?>
                    <div class="alert alert-<?php echo marketing_h($flash[0]); ?> alert-dismissible fade show">
                        <?php echo marketing_h($flash[1]); ?><button class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-7 mb-4">
                        <div class="card shadow"><div class="card-header font-weight-bold text-primary">Tạo voucher</div><div class="card-body">
                            <form method="post">
                                <?php echo csrf_field(); ?><input type="hidden" name="action" value="create_voucher">
                                <div class="form-row">
                                    <div class="form-group col-md-4"><label>Mã voucher</label><input name="code" class="form-control text-uppercase" maxlength="33" pattern="#?[A-Za-z0-9_-]{2,32}" placeholder="VD: #01 hoặc SALE50" required><small class="form-text text-muted">Dấu # đầu mã là tùy chọn.</small></div>
                                    <div class="form-group col-md-8"><label>Tên chương trình</label><input name="name" class="form-control" maxlength="150" required></div>
                                </div>
                                <div class="form-group"><label>Mô tả</label><input name="description" class="form-control" maxlength="500"></div>
                                <div class="form-row">
                                    <div class="form-group col-md-3"><label>Giảm (%)</label><input type="number" name="discount_percent" class="form-control" min="1" max="100" required></div>
                                    <div class="form-group col-md-3"><label>Số lượng</label><input type="number" name="quantity_total" class="form-control" min="1" max="1000000" required></div>
                                    <div class="form-group col-md-3"><label>Lượt/khách</label><input type="number" name="per_user_limit" class="form-control" min="1" max="100" value="1" required></div>
                                    <div class="form-group col-md-3"><label>Đơn tối thiểu</label><input type="number" name="min_order_amount" class="form-control" min="0" value="0"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4"><label>Giảm tối đa (đ, tùy chọn)</label><input type="number" name="max_discount_amount" class="form-control" min="0"></div>
                                    <div class="form-group col-md-4"><label>Bắt đầu</label><input type="datetime-local" name="starts_at" class="form-control" value="<?php echo $nowInput; ?>" required></div>
                                    <div class="form-group col-md-4"><label>Hết hạn</label><input type="datetime-local" name="ends_at" class="form-control" value="<?php echo $tomorrowInput; ?>" required></div>
                                </div>
                                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="announce_on_publish" id="announce" checked><label class="form-check-label" for="announce">Thông báo cho tất cả khách khi voucher bắt đầu</label></div>
                                <div class="form-row">
                                    <div class="form-group col-md-5"><label>Tiêu đề thông báo (tùy chọn)</label><input name="announcement_title" class="form-control" maxlength="255"></div>
                                    <div class="form-group col-md-7"><label>Nội dung thông báo (tùy chọn)</label><input name="announcement_message" class="form-control" maxlength="1000"></div>
                                </div>
                                <button class="btn btn-primary"><i class="fas fa-ticket-alt mr-1"></i>Phát hành voucher</button>
                            </form>
                        </div></div>
                    </div>
                    <div class="col-xl-5 mb-4">
                        <div class="card shadow"><div class="card-header font-weight-bold text-success">Gửi thông báo</div><div class="card-body">
                            <form method="post">
                                <?php echo csrf_field(); ?><input type="hidden" name="action" value="create_broadcast">
                                <div class="form-group"><label>Loại</label><select name="type" class="form-control"><option value="sale">Sale</option><option value="voucher">Voucher</option><option value="system">Hệ thống</option></select></div>
                                <div class="form-group"><label>Tiêu đề</label><input name="title" class="form-control" maxlength="255" required></div>
                                <div class="form-group"><label>Nội dung</label><textarea name="message" class="form-control" rows="4" maxlength="1000" required></textarea></div>
                                <div class="form-group"><label>Gửi lúc</label><input type="datetime-local" name="scheduled_at" class="form-control" value="<?php echo $nowInput; ?>" required></div>
                                <button class="btn btn-success"><i class="fas fa-bullhorn mr-1"></i>Gửi / lên lịch</button>
                            </form>
                        </div></div>
                    </div>
                </div>

                <div class="card shadow mb-4"><div class="card-header font-weight-bold">Danh sách voucher</div><div class="card-body table-responsive">
                    <table class="table table-bordered table-hover"><thead><tr><th>Mã / tên</th><th>Mức giảm</th><th>Đã nhận / dùng</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
                    <?php foreach ($vouchers as $voucher): ?>
                        <tr><td><strong><?php echo marketing_h($voucher['code']); ?></strong><br><small><?php echo marketing_h($voucher['name']); ?></small></td>
                            <td><?php echo (int) $voucher['discount_percent']; ?>%<?php echo $voucher['max_discount_amount'] ? '<br><small>tối đa ' . number_format((int) $voucher['max_discount_amount'], 0, ',', '.') . ' đ</small>' : ''; ?></td>
                            <td><?php echo (int) $voucher['quantity_claimed']; ?> đã nhận<br><small><?php echo (int) $voucher['quantity_used']; ?> đã dùng / <?php echo (int) $voucher['quantity_total']; ?> tổng</small></td>
                            <td><small><?php echo marketing_h(date('d/m/Y H:i', strtotime((string) $voucher['starts_at']))); ?><br>→ <?php echo marketing_h(date('d/m/Y H:i', strtotime((string) $voucher['ends_at']))); ?></small></td>
                            <td><span class="badge badge-<?php echo $voucher['status'] === 'active' ? 'success' : ($voucher['status'] === 'paused' ? 'warning' : 'secondary'); ?>"><?php echo marketing_h($voucher['status']); ?></span></td>
                            <td><?php if ($voucher['status'] !== 'expired'): ?><form method="post" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="action" value="voucher_status"><input type="hidden" name="voucher_id" value="<?php echo (int) $voucher['id']; ?>"><input type="hidden" name="status" value="<?php echo $voucher['status'] === 'paused' ? 'active' : 'paused'; ?>"><button class="btn btn-sm btn-outline-<?php echo $voucher['status'] === 'paused' ? 'success' : 'warning'; ?>"><?php echo $voucher['status'] === 'paused' ? 'Kích hoạt' : 'Tạm dừng'; ?></button></form><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($vouchers === []): ?><tr><td colspan="6" class="text-center text-muted">Chưa có voucher.</td></tr><?php endif; ?>
                    </tbody></table>
                </div></div>

                <div class="card shadow"><div class="card-header font-weight-bold">Lịch thông báo gần đây</div><div class="card-body table-responsive">
                    <table class="table table-bordered"><thead><tr><th>Tiêu đề</th><th>Nội dung</th><th>Lịch gửi</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                    <?php foreach ($broadcasts as $broadcast): ?><tr><td><?php echo marketing_h($broadcast['title']); ?></td><td><?php echo marketing_h($broadcast['message']); ?></td><td><?php echo marketing_h(date('d/m/Y H:i', strtotime((string) $broadcast['scheduled_at']))); ?></td><td><?php echo marketing_h($broadcast['status']); ?></td><td><?php if ($broadcast['status'] === 'scheduled'): ?><form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="cancel_broadcast"><input type="hidden" name="broadcast_id" value="<?php echo (int) $broadcast['id']; ?>"><button class="btn btn-sm btn-outline-danger">Hủy lịch</button></form><?php endif; ?></td></tr><?php endforeach; ?>
                    <?php if ($broadcasts === []): ?><tr><td colspan="5" class="text-center text-muted">Chưa có thông báo.</td></tr><?php endif; ?>
                    </tbody></table>
                </div></div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../Layouts/Link.php'; ?>
