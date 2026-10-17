<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/config/returns.php';
require_once __DIR__ . '/../Layouts/Admin_Access_Check.php';

$adminRole = (string) ($_SESSION['admin_role'] ?? '');
if (!in_array($adminRole, ['admin', 'manager'], true)) {
    header('Location: ../View/Admin_Login.php');
    exit;
}

function return_admin_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function return_admin_redirect(string $type, string $message): never
{
    $_SESSION['return_admin_flash'] = [$type, $message];
    header('Location: Admin_Return_Cancellation_Requests.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action !== 'mark_returned') {
        return_admin_redirect('danger', 'Thao tác không hợp lệ.');
    }

    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $goodsStatus = (string) ($_POST['goods_status'] ?? '');
    if (!$orderId || !in_array($goodsStatus, ['returned', 'not_returned'], true)) {
        return_admin_redirect('danger', 'Dữ liệu đơn hàng không hợp lệ.');
    }

    try {
        $conn->begin_transaction();
        $orderStatement = $conn->prepare(
            'SELECT ID_KhachHang, HoVaTen, GhiChu FROM donhang WHERE ID_DonHang = ? FOR UPDATE'
        );
        $orderStatement->bind_param('i', $orderId);
        $orderStatement->execute();
        $order = $orderStatement->get_result()->fetch_assoc();
        if (!$order || stripos((string) ($order['GhiChu'] ?? ''), '[KHÁCH HÀNG TỪ CHỐI]') === false) {
            throw new DomainException('Đơn hàng không tồn tại hoặc không thuộc nhóm khách từ chối nhận.');
        }

        $note = (string) ($order['GhiChu'] ?? '');
        $wasReturned = stripos($note, '[ĐÃ TRẢ VỀ CỬA HÀNG]') !== false;
        if ($goodsStatus === 'returned' && !$wasReturned) {
            $note = trim($note . ' | [ĐÃ TRẢ VỀ CỬA HÀNG]');
        } elseif ($goodsStatus === 'not_returned' && $wasReturned) {
            $note = trim((string) preg_replace('/\s*\|?\s*\[ĐÃ TRẢ VỀ CỬA HÀNG\]/iu', '', $note));
        }
        $note = mb_substr($note, 0, 255);
        $update = $conn->prepare('UPDATE donhang SET GhiChu = ? WHERE ID_DonHang = ?');
        $update->bind_param('si', $note, $orderId);
        $update->execute();
        $conn->commit();

        if ($goodsStatus === 'returned' && !$wasReturned) {
            try {
                $pushFile = dirname(__DIR__, 3) . '/WebSocketServer/WebSocket_Push.php';
                if (is_file($pushFile)) {
                    require_once $pushFile;
                    websocket_push(['admin_channel', 'staff_channel'], [
                        'type' => 'goods_returned_to_shop',
                        'title' => 'Hàng đã hoàn về cửa hàng',
                        'message' => sprintf(
                            'Đơn hàng #%d của %s đã được xác nhận hoàn về cửa hàng.',
                            $orderId,
                            (string) ($order['HoVaTen'] ?? 'Khách hàng')
                        ),
                        'sound' => 'notification',
                        'data' => ['order_id' => $orderId, 'customer_id' => (int) $order['ID_KhachHang']],
                    ]);
                }
            } catch (Throwable $pushError) {
                error_log('Returned goods realtime notification failed: ' . $pushError->getMessage());
            }
        }
        return_admin_redirect(
            'success',
            $goodsStatus === 'returned' ? 'Đã xác nhận hàng hoàn về cửa hàng.' : 'Đã chuyển trạng thái sang chưa hoàn về cửa hàng.'
        );
    } catch (Throwable $error) {
        $conn->rollback();
        error_log('Update returned goods status failed: ' . $error->getMessage());
        return_admin_redirect('danger', 'Không thể cập nhật trạng thái hàng hoàn.');
    }
}

$requestResult = $conn->query(
    "SELECT r.*, d.TongTien, k.HoVaTen AS CustomerName,
            sp.TenSanPham, COALESCE(dct.DonGia, sp.GiaTien, 0) AS ProductPrice,
            COALESCE(dct.SoLuong, 0) AS ProductQuantity,
            COALESCE(dct.ThanhTienThucTra, 0) AS ThanhTienThucTra
     FROM return_refund_requests r
     LEFT JOIN donhang d ON r.ID_DonHang = d.ID_DonHang
     LEFT JOIN khachhang k ON r.ID_KhachHang = k.ID_KhachHang
     LEFT JOIN sanpham sp ON r.ID_SanPham = sp.IDSanPham
     LEFT JOIN donhangchitiet dct ON dct.ID_DonHang = r.ID_DonHang AND dct.ID_SanPham = r.ID_SanPham
     ORDER BY r.ThoiGianTao DESC"
);
$requests = $requestResult->fetch_all(MYSQLI_ASSOC);

$refusedResult = $conn->query(
    "SELECT d.*, k.HoVaTen, k.Email, k.SDT, s.HoVaTen AS ShipperName
     FROM donhang d
     LEFT JOIN khachhang k ON d.ID_KhachHang = k.ID_KhachHang
     LEFT JOIN khachhang s ON d.ID_Shipper = s.ID_KhachHang
     WHERE d.GhiChu LIKE '%[KHÁCH HÀNG TỪ CHỐI]%'
     ORDER BY d.ID_DonHang DESC"
);
$refusedOrders = $refusedResult->fetch_all(MYSQLI_ASSOC);

$statusMeta = [
    'pending' => ['Chờ xử lý', 'warning', 'fa-clock'],
    'approved' => ['Đã chấp nhận', 'success', 'fa-check-circle'],
    'rejected' => ['Đã từ chối', 'danger', 'fa-times-circle'],
];
$summary = ['all' => count($requests), 'pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($requests as $request) {
    $key = (string) ($request['TrangThai'] ?? 'pending');
    if (isset($summary[$key])) {
        $summary[$key]++;
    }
}
$flash = $_SESSION['return_admin_flash'] ?? null;
unset($_SESSION['return_admin_flash']);

include __DIR__ . '/../Layouts/headHTML.php';
?>
<style>
    .return-page { color: #3a3b45; }
    .return-page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.4rem; }
    .return-page-header h1 { font-size:1.7rem; font-weight:700; margin:0 0 .35rem; }
    .return-page-header p { margin:0; color:#858796; }
    .return-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .return-summary-card { background:#fff; border-radius:.8rem; padding:1rem 1.15rem; box-shadow:0 .15rem 1rem rgba(58,59,69,.08); border-left:4px solid #4e73df; }
    .return-summary-card.warning { border-left-color:#f6c23e; }
    .return-summary-card.success { border-left-color:#1cc88a; }
    .return-summary-card.danger { border-left-color:#e74a3b; }
    .return-summary-card small { display:block; color:#858796; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .return-summary-card strong { display:block; font-size:1.55rem; line-height:1.25; margin-top:.2rem; }
    .return-section { background:#fff; border-radius:.9rem; box-shadow:0 .15rem 1.35rem rgba(58,59,69,.09); margin-bottom:1.5rem; overflow:hidden; }
    .return-section-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid #eaecf4; }
    .return-section-header h2 { font-size:1.05rem; font-weight:800; margin:0; }
    .return-list { padding:1rem; display:grid; gap:.85rem; }
    .return-request-card { border:1px solid #e3e6f0; border-radius:.75rem; padding:1rem 1.1rem; transition:box-shadow .2s,border-color .2s; }
    .return-request-card:hover { border-color:#b8c5f2; box-shadow:0 .25rem .8rem rgba(78,115,223,.1); }
    .return-card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.85rem; }
    .return-identity { display:flex; align-items:center; flex-wrap:wrap; gap:.5rem; }
    .return-code { font-size:1rem; color:#4e73df; font-weight:800; }
    .return-date { color:#858796; font-size:.82rem; white-space:nowrap; }
    .return-card-grid { display:grid; grid-template-columns:minmax(180px,1.05fr) minmax(240px,1.8fr) minmax(150px,.75fr) auto; gap:1rem; align-items:center; }
    .return-field-label { display:block; color:#858796; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.035em; margin-bottom:.25rem; }
    .return-field-value { font-weight:600; overflow-wrap:anywhere; }
    .return-product { color:#5a5c69; line-height:1.4; }
    .return-reason { color:#5a5c69; line-height:1.45; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .return-money { font-size:1.05rem; font-weight:800; color:#1cc88a; white-space:nowrap; }
    .return-money small { display:block; color:#858796; font-size:.72rem; font-weight:500; white-space:normal; margin-top:.2rem; }
    .return-action { white-space:nowrap; }
    .return-empty { text-align:center; color:#858796; padding:3rem 1rem; }
    .return-empty i { display:block; font-size:2.4rem; color:#d1d3e2; margin-bottom:.8rem; }
    .refused-list { padding:1rem; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
    .refused-card { border:1px solid #e3e6f0; border-radius:.75rem; padding:1rem; }
    .refused-card-head { display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.8rem; }
    .refused-meta { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.8rem; }
    .refused-reason { background:#fff8e1; border-radius:.5rem; padding:.7rem .8rem; color:#7a6218; margin-bottom:.8rem; }
    .refused-actions { display:flex; flex-wrap:wrap; gap:.5rem; }
    @media (max-width:1200px) {
        .return-card-grid { grid-template-columns:1fr 1.5fr; }
        .return-action { white-space:normal; }
    }
    @media (max-width:768px) {
        .return-page-header { display:block; }
        .return-summary { grid-template-columns:repeat(2,1fr); }
        .return-card-top,.return-section-header { align-items:flex-start; }
        .return-card-grid,.refused-list,.refused-meta { grid-template-columns:1fr; }
        .return-date { white-space:normal; }
    }
</style>
<body id="page-top">
<div id="wrapper">
    <?php include __DIR__ . '/../Layouts/SlideBar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include __DIR__ . '/../Layouts/TopBar.php'; ?>
            <main class="container-fluid return-page pb-5">
                <div class="return-page-header">
                    <div>
                        <h1><i class="fas fa-undo-alt text-primary mr-2"></i>Yêu cầu hoàn trả & hủy đơn</h1>
                        <p>Theo dõi, kiểm tra và xử lý yêu cầu hậu mãi của khách hàng.</p>
                    </div>
                    <a class="btn btn-light border" href="All_Oders.php"><i class="fas fa-arrow-left mr-1"></i>Danh sách đơn hàng</a>
                </div>

                <?php if (is_array($flash)): ?>
                    <div class="alert alert-<?php echo return_admin_h($flash[0]); ?> alert-dismissible fade show" role="alert">
                        <?php echo return_admin_h($flash[1]); ?>
                        <button class="close" data-dismiss="alert" aria-label="Đóng"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="return-summary">
                    <div class="return-summary-card"><small>Tổng yêu cầu</small><strong><?php echo $summary['all']; ?></strong></div>
                    <div class="return-summary-card warning"><small>Chờ xử lý</small><strong><?php echo $summary['pending']; ?></strong></div>
                    <div class="return-summary-card success"><small>Đã chấp nhận</small><strong><?php echo $summary['approved']; ?></strong></div>
                    <div class="return-summary-card danger"><small>Đã từ chối</small><strong><?php echo $summary['rejected']; ?></strong></div>
                </div>

                <section class="return-section">
                    <div class="return-section-header">
                        <h2><i class="fas fa-box-open text-primary mr-2"></i>Yêu cầu hoàn trả</h2>
                        <span class="badge badge-light border px-3 py-2"><?php echo count($requests); ?> yêu cầu</span>
                    </div>
                    <?php if ($requests === []): ?>
                        <div class="return-empty"><i class="fas fa-inbox"></i>Chưa có yêu cầu hoàn trả nào.</div>
                    <?php else: ?>
                        <div class="return-list">
                        <?php foreach ($requests as $request):
                            $status = (string) ($request['TrangThai'] ?? 'pending');
                            [$statusText, $statusClass, $statusIcon] = $statusMeta[$status] ?? $statusMeta['pending'];
                            $productName = trim((string) ($request['TenSanPham'] ?? ''));
                            $customerName = trim((string) ($request['CustomerName'] ?? ''));
                            $reason = trim((string) ($request['RefundReason'] ?? ''));
                            $price = max(0, (int) ($request['ProductPrice'] ?? 0));
                            $quantity = max(0, (int) ($request['SoLuongYeuCau'] ?? 0));
                            $refundAmount = return_refund_preview_amount($request);
                            $createdAt = strtotime((string) ($request['ThoiGianTao'] ?? ''));
                        ?>
                            <article class="return-request-card">
                                <div class="return-card-top">
                                    <div class="return-identity">
                                        <span class="return-code">YC #<?php echo (int) $request['ID_Request']; ?></span>
                                        <span class="text-muted">•</span>
                                        <span class="font-weight-bold">Đơn #<?php echo (int) $request['ID_DonHang']; ?></span>
                                        <span class="badge badge-<?php echo $statusClass; ?> px-2 py-1"><i class="fas <?php echo $statusIcon; ?> mr-1"></i><?php echo $statusText; ?></span>
                                    </div>
                                    <time class="return-date"><i class="far fa-calendar-alt mr-1"></i><?php echo $createdAt ? date('d/m/Y H:i', $createdAt) : 'Chưa xác định'; ?></time>
                                </div>
                                <div class="return-card-grid">
                                    <div><span class="return-field-label">Khách hàng</span><div class="return-field-value"><i class="far fa-user text-muted mr-1"></i><?php echo return_admin_h($customerName !== '' ? $customerName : 'Không còn dữ liệu'); ?></div></div>
                                    <div><span class="return-field-label">Sản phẩm & lý do</span><div class="return-product"><?php echo return_admin_h($productName !== '' ? $productName : 'Sản phẩm không còn tồn tại'); ?></div><div class="return-reason mt-1" title="<?php echo return_admin_h($reason); ?>"><i class="far fa-comment-dots text-muted mr-1"></i><?php echo return_admin_h($reason !== '' ? $reason : 'Khách hàng chưa mô tả lý do'); ?></div></div>
                                    <div><span class="return-field-label">Số tiền dự kiến</span><div class="return-money"><?php echo number_format($refundAmount, 0, ',', '.'); ?> đ<small><?php echo number_format($price, 0, ',', '.'); ?> đ × <?php echo $quantity; ?></small></div></div>
                                    <div class="return-action"><a href="Admin_Return_Refund_Detail.php?request_id=<?php echo (int) $request['ID_Request']; ?>" class="btn btn-primary btn-sm px-3"><i class="fas fa-eye mr-1"></i>Xem chi tiết</a></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="return-section">
                    <div class="return-section-header">
                        <h2><i class="fas fa-truck-loading text-warning mr-2"></i>Khách từ chối nhận hàng</h2>
                        <span class="badge badge-warning px-3 py-2"><?php echo count($refusedOrders); ?> đơn</span>
                    </div>
                    <?php if ($refusedOrders === []): ?>
                        <div class="return-empty"><i class="fas fa-box"></i>Không có đơn hàng nào khách từ chối nhận.</div>
                    <?php else: ?>
                        <div class="refused-list">
                        <?php foreach ($refusedOrders as $order):
                            $note = (string) ($order['GhiChu'] ?? '');
                            $reason = 'Không có lý do cụ thể';
                            if (preg_match('/\[KHÁCH HÀNG TỪ CHỐI\]\s*Lý do:\s*(.+?)(?:\||$)/iu', $note, $matches)) {
                                $reason = trim($matches[1]);
                            }
                            $returned = stripos($note, '[ĐÃ TRẢ VỀ CỬA HÀNG]') !== false;
                        ?>
                            <article class="refused-card">
                                <div class="refused-card-head"><strong class="text-primary">Đơn #<?php echo (int) $order['ID_DonHang']; ?></strong><span class="badge badge-<?php echo $returned ? 'success' : 'warning'; ?>"><?php echo $returned ? 'Đã hoàn về shop' : 'Chưa hoàn về shop'; ?></span></div>
                                <div class="refused-meta">
                                    <div><span class="return-field-label">Khách hàng</span><div class="return-field-value"><?php echo return_admin_h($order['HoVaTen'] ?? 'Không còn dữ liệu'); ?></div><small class="text-muted"><?php echo return_admin_h($order['SDT'] ?? ''); ?></small></div>
                                    <div><span class="return-field-label">Shipper</span><div class="return-field-value"><?php echo return_admin_h($order['ShipperName'] ?? 'Chưa xác định'); ?></div></div>
                                </div>
                                <div class="refused-reason"><strong>Lý do:</strong> <?php echo return_admin_h($reason); ?></div>
                                <div class="refused-actions">
                                    <a href="View_Oder_Detail.php?oder_id=<?php echo (int) $order['ID_DonHang']; ?>&view=all" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>Chi tiết đơn</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo $returned ? 'Chuyển hàng về trạng thái chưa hoàn về shop?' : 'Xác nhận hàng đã được hoàn về cửa hàng?'; ?>');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="mark_returned">
                                        <input type="hidden" name="order_id" value="<?php echo (int) $order['ID_DonHang']; ?>">
                                        <input type="hidden" name="goods_status" value="<?php echo $returned ? 'not_returned' : 'returned'; ?>">
                                        <button class="btn btn-sm btn-<?php echo $returned ? 'outline-warning' : 'success'; ?>"><i class="fas <?php echo $returned ? 'fa-undo' : 'fa-check-circle'; ?> mr-1"></i><?php echo $returned ? 'Đánh dấu chưa hoàn' : 'Xác nhận đã hoàn'; ?></button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
</div>
<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<?php include __DIR__ . '/../Layouts/Link.php'; ?>
</body>
</html>
