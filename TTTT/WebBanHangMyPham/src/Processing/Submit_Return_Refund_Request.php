<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Phương thức không hợp lệ.');
}
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Vui lòng đăng nhập lại.');
}
require_csrf();

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT) ?: 0;
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
$requestedQuantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 0;
$contactName = trim((string) ($_POST['contact_name'] ?? ''));
$contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));
$contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
$contactAddress = trim((string) ($_POST['contact_address'] ?? ''));
$refundReason = trim((string) ($_POST['refund_reason'] ?? ''));

if ($orderId <= 0 || $productId <= 0 || $requestedQuantity <= 0
    || mb_strlen($contactName) < 2 || mb_strlen($contactName) > 255
    || !preg_match('/^[0-9+ .()-]{8,20}$/', $contactPhone)
    || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)
    || mb_strlen($contactAddress) < 5 || mb_strlen($contactAddress) > 1000
    || mb_strlen($refundReason) < 10 || mb_strlen($refundReason) > 4000) {
    http_response_code(422);
    exit('Thông tin yêu cầu hoàn trả không hợp lệ.');
}

$userStmt = $conn->prepare('SELECT ID_KhachHang FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
$username = (string) $_SESSION['user'];
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) {
    http_response_code(401);
    exit('Tài khoản không tồn tại.');
}
$customerId = (int) $user['ID_KhachHang'];

/** @var array<int, string> $uploadedPaths */
$uploadedPaths = [];
$attachments = [];
$uploadRoot = dirname(__DIR__, 3) . '/storage/private/return_refunds';
$allowedMime = [
    'image/jpeg' => ['type' => 'image', 'extension' => 'jpg', 'max' => 5 * 1024 * 1024],
    'image/png' => ['type' => 'image', 'extension' => 'png', 'max' => 5 * 1024 * 1024],
    'image/webp' => ['type' => 'image', 'extension' => 'webp', 'max' => 5 * 1024 * 1024],
    'video/mp4' => ['type' => 'video', 'extension' => 'mp4', 'max' => 10 * 1024 * 1024],
    'video/quicktime' => ['type' => 'video', 'extension' => 'mov', 'max' => 10 * 1024 * 1024],
];

try {
    if (isset($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
        if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0750, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('Cannot create private return evidence directory.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $imageCount = 0;
        $videoCount = 0;
        $totalSize = 0;
        $names = $_FILES['attachments']['name'];
        if (count($names) > 7) {
            throw new DomainException('Chỉ được tải tối đa 5 ảnh và 2 video.');
        }

        foreach ($names as $index => $originalName) {
            $error = (int) ($_FILES['attachments']['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new DomainException('Một tệp đính kèm tải lên không thành công.');
            }

            $temporaryPath = (string) ($_FILES['attachments']['tmp_name'][$index] ?? '');
            $size = (int) ($_FILES['attachments']['size'][$index] ?? 0);
            if (!is_uploaded_file($temporaryPath) || $size <= 0) {
                throw new DomainException('Tệp đính kèm không hợp lệ.');
            }
            $mime = (string) $fileInfo->file($temporaryPath);
            if (!isset($allowedMime[$mime]) || $size > $allowedMime[$mime]['max']) {
                throw new DomainException('Chỉ chấp nhận JPG, PNG, WebP, MP4 hoặc MOV đúng giới hạn dung lượng.');
            }

            $kind = $allowedMime[$mime]['type'];
            $imageCount += $kind === 'image' ? 1 : 0;
            $videoCount += $kind === 'video' ? 1 : 0;
            $totalSize += $size;
            if ($imageCount > 5 || $videoCount > 2 || $totalSize > 10 * 1024 * 1024) {
                throw new DomainException('Tổng tệp vượt quá giới hạn cho phép.');
            }

            $storedName = bin2hex(random_bytes(20)) . '.' . $allowedMime[$mime]['extension'];
            $destination = $uploadRoot . DIRECTORY_SEPARATOR . $storedName;
            if (!move_uploaded_file($temporaryPath, $destination)) {
                throw new RuntimeException('Could not store return evidence.');
            }
            @chmod($destination, 0640);
            $uploadedPaths[] = $destination;
            $attachments[] = [
                'type' => $kind,
                'filename' => $storedName,
                'original_name' => mb_substr(basename((string) $originalName), 0, 180),
                'mime' => $mime,
                'size' => $size,
            ];
        }
    }

    $conn->begin_transaction();
    $orderStmt = $conn->prepare(
        "SELECT d.ID_DonHang, d.GiaoHangLuc, dc.SoLuong, dc.ThanhTienThucTra
         FROM donhang d
         INNER JOIN donhangchitiet dc ON dc.ID_DonHang = d.ID_DonHang AND dc.ID_SanPham = ?
         WHERE d.ID_DonHang = ? AND d.ID_KhachHang = ? AND d.TrangThai = 3
           AND d.TrangThaiThanhToan = 'paid'
           AND d.GiaoHangLuc IS NOT NULL AND d.GiaoHangLuc >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         FOR UPDATE"
    );
    $orderStmt->bind_param('iii', $productId, $orderId, $customerId);
    $orderStmt->execute();
    $eligibleLine = $orderStmt->get_result()->fetch_assoc();
    if (!$eligibleLine) {
        throw new DomainException('Đơn hàng không thuộc thời hạn hoặc điều kiện hoàn trả.');
    }

    // The detail row above is locked, so two concurrent requests for the same
    // order line cannot reserve more units than were purchased.
    $reservedStmt = $conn->prepare(
        "SELECT COALESCE(SUM(SoLuongYeuCau), 0) AS reserved_quantity
         FROM return_refund_requests
         WHERE ID_DonHang = ? AND ID_KhachHang = ? AND ID_SanPham = ?
           AND TrangThai IN ('pending', 'approved')"
    );
    $reservedStmt->bind_param('iii', $orderId, $customerId, $productId);
    $reservedStmt->execute();
    $reserved = (int) ($reservedStmt->get_result()->fetch_assoc()['reserved_quantity'] ?? 0);
    if ($requestedQuantity + $reserved > (int) $eligibleLine['SoLuong']) {
        throw new DomainException('Số lượng yêu cầu vượt quá số lượng còn có thể hoàn.');
    }

    $attachmentsJson = json_encode($attachments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $insert = $conn->prepare(
        "INSERT INTO return_refund_requests
         (ID_DonHang, ID_KhachHang, ID_SanPham, SoLuongYeuCau, ContactName, ContactPhone, ContactEmail,
          ContactAddress, RefundReason, Attachments, TrangThai)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $insert->bind_param(
        'iiiissssss',
        $orderId,
        $customerId,
        $productId,
        $requestedQuantity,
        $contactName,
        $contactPhone,
        $contactEmail,
        $contactAddress,
        $refundReason,
        $attachmentsJson
    );
    $insert->execute();
    $requestId = (int) $conn->insert_id;

    $payload = json_encode(
        ['request_id' => $requestId, 'order_id' => $orderId, 'customer_id' => $customerId,
         'product_id' => $productId, 'quantity' => $requestedQuantity],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $event = $conn->prepare(
        "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
         VALUES ('return.requested', 'return_request', ?, ?)"
    );
    $aggregateId = (string) $requestId;
    $event->bind_param('ss', $aggregateId, $payload);
    $event->execute();
    $conn->commit();

    header('Location: ../View/Order_Tracking.php?msg=return_request_submitted');
    exit;
} catch (DomainException $error) {
    if ($conn->errno === 0) {
        // no-op: mysqli does not expose transaction state consistently
    }
    try { $conn->rollback(); } catch (Throwable) {}
    foreach ($uploadedPaths as $path) {
        if (is_file($path)) { @unlink($path); }
    }
    http_response_code(422);
    exit(htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'));
} catch (Throwable $error) {
    try { $conn->rollback(); } catch (Throwable) {}
    foreach ($uploadedPaths as $path) {
        if (is_file($path)) { @unlink($path); }
    }
    error_log('Return request failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Không thể tạo yêu cầu hoàn trả.');
}
