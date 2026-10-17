<?php
declare(strict_types=1);

include '../Layouts/headHTML.php';
include '../DB_Connect.php';

if (!in_array((string) ($_SESSION['admin_role'] ?? ''), ['admin', 'manager'], true)) {
    header('Location: ../Users/Staff_Dashboard.php?error=unauthorized');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'mark_all_read') {
        $conn->query('UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0');
    } elseif ($action === 'mark_read') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $statement = $conn->prepare('UPDATE admin_notifications SET is_read = 1 WHERE id = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
        }
    }
    header('Location: Notification_Center.php');
    exit;
}

$filter = (string) ($_GET['status'] ?? 'all');
if (!in_array($filter, ['all', 'unread', 'read'], true)) {
    $filter = 'all';
}
$where = $filter === 'unread' ? 'WHERE is_read = 0' : ($filter === 'read' ? 'WHERE is_read = 1' : '');
$notifications = $conn->query(
    "SELECT id, type, message, order_id, user_id, is_read, created_at
     FROM admin_notifications {$where} ORDER BY id DESC LIMIT 200"
);
$counts = $conn->query(
    'SELECT COUNT(*) AS total, SUM(is_read = 0) AS unread, SUM(is_read = 1) AS `read` FROM admin_notifications'
)->fetch_assoc();

function admin_notification_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<style>
.notification-page-header { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
.notification-filters { display:flex; flex-wrap:wrap; gap:.5rem; }
.notification-item { display:grid; grid-template-columns:48px minmax(0,1fr) auto; gap:1rem; align-items:start; padding:1rem 1.1rem; border:1px solid #e3e8ef; border-radius:.75rem; margin-bottom:.75rem; background:#fff; }
.notification-item.is-unread { border-left:5px solid #168c80; background:#f3fbfa; }
.notification-icon { display:grid; width:44px; height:44px; place-items:center; color:#fff; background:#168c80; border-radius:50%; }
.notification-message { margin:0 0 .4rem; color:#354052; line-height:1.5; }
.notification-meta { color:#8791a3; font-size:.8rem; }
.notification-actions { display:flex; gap:.5rem; align-items:center; }
@media(max-width:767px){ .notification-page-header{align-items:flex-start;flex-direction:column}.notification-item{grid-template-columns:42px 1fr}.notification-actions{grid-column:2} }
</style>

<body id="page-top">
<div id="wrapper">
    <?php include '../Layouts/SlideBar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../Layouts/TopBar.php'; ?>
            <div class="container-fluid">
                <div class="notification-page-header">
                    <div>
                        <h1 class="h3 mb-1 text-gray-800"><i class="fas fa-bell mr-2 text-info"></i>Trung Tâm Thông Báo</h1>
                        <p class="text-muted mb-0">Theo dõi đơn hàng và các sự kiện quan trọng của cửa hàng.</p>
                    </div>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="mark_all_read">
                        <button class="btn btn-outline-success" type="submit"><i class="fas fa-check-double mr-1"></i>Đánh dấu tất cả đã đọc</button>
                    </form>
                </div>

                <div class="card shadow-sm mb-4"><div class="card-body d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem">
                    <div><strong><?php echo number_format((int) ($counts['total'] ?? 0)); ?></strong> thông báo · <strong class="text-danger"><?php echo number_format((int) ($counts['unread'] ?? 0)); ?></strong> chưa đọc</div>
                    <div class="notification-filters">
                        <a class="btn btn-sm <?php echo $filter === 'all' ? 'btn-info' : 'btn-outline-secondary'; ?>" href="?status=all">Tất cả</a>
                        <a class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-info' : 'btn-outline-secondary'; ?>" href="?status=unread">Chưa đọc</a>
                        <a class="btn btn-sm <?php echo $filter === 'read' ? 'btn-info' : 'btn-outline-secondary'; ?>" href="?status=read">Đã đọc</a>
                    </div>
                </div></div>

                <?php if ($notifications->num_rows === 0): ?>
                    <div class="card shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="far fa-bell-slash fa-3x mb-3"></i><p class="mb-0">Chưa có thông báo trong mục này.</p></div></div>
                <?php else: ?>
                    <?php while ($item = $notifications->fetch_assoc()): ?>
                    <article class="notification-item <?php echo (int) $item['is_read'] === 0 ? 'is-unread' : ''; ?>">
                        <span class="notification-icon"><i class="fas <?php echo match ((string) $item['type']) { 'order.created' => 'fa-shopping-bag', 'appointment.created' => 'fa-calendar-check', default => 'fa-bell' }; ?>"></i></span>
                        <div>
                            <p class="notification-message"><?php echo admin_notification_h($item['message']); ?></p>
                            <span class="notification-meta"><?php echo admin_notification_h($item['created_at']); ?> · <?php echo (int) $item['is_read'] === 0 ? 'Chưa đọc' : 'Đã đọc'; ?></span>
                        </div>
                        <div class="notification-actions">
                            <?php if (!empty($item['order_id'])): ?><a class="btn btn-sm btn-info" href="../Oders/View_Oder_Detail.php?oder_id=<?php echo (int) $item['order_id']; ?>&view=all">Xem đơn</a><?php endif; ?>
                            <?php if ((int) $item['is_read'] === 0): ?><form method="post" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><button class="btn btn-sm btn-outline-success" type="submit">Đã đọc</button></form><?php endif; ?>
                        </div>
                    </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
</div>
<?php include '../Layouts/Link.php'; ?>
</body>
</html>
