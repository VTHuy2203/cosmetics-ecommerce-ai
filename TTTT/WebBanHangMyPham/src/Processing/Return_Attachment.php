<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$requestId = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if ($requestId === null || $requestId === false) {
    $requestId = filter_var($_GET['request_id'] ?? null, FILTER_VALIDATE_INT);
}
$requestId = $requestId ?: 0;
$filename = basename((string) ($_GET['file'] ?? ''));
if ($requestId <= 0 || !preg_match('/^[a-f0-9]{40}\.(?:jpg|png|webp|mp4|mov)$/', $filename)) {
    http_response_code(404);
    exit;
}

$statement = $conn->prepare(
    'SELECT r.ID_KhachHang, r.Attachments, viewer.ID_KhachHang AS ViewerID, viewer.Role
     FROM return_refund_requests r
     INNER JOIN khachhang viewer ON viewer.TenDangNhap = ?
     WHERE r.ID_Request = ? LIMIT 1'
);
$username = (string) $_SESSION['user'];
$statement->bind_param('si', $username, $requestId);
$statement->execute();
$request = $statement->get_result()->fetch_assoc();
if (!$request || ((int) $request['ID_KhachHang'] !== (int) $request['ViewerID']
    && !in_array($request['Role'], ['employee', 'manager', 'admin'], true))) {
    http_response_code(403);
    exit;
}

$attachments = json_decode((string) $request['Attachments'], true);
$authorized = false;
foreach (is_array($attachments) ? $attachments : [] as $attachment) {
    if (is_array($attachment) && hash_equals((string) ($attachment['filename'] ?? ''), $filename)) {
        $authorized = true;
        break;
    }
}
if (!$authorized) {
    http_response_code(404);
    exit;
}

$base = dirname(__DIR__, 3) . '/storage/private/return_refunds';
$realBase = realpath($base);
$realFile = realpath($base . DIRECTORY_SEPARATOR . $filename);
if ($realBase === false || $realFile === false
    || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) || !is_file($realFile)) {
    http_response_code(404);
    exit;
}

$mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($realFile);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime'], true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realFile));
header('Content-Disposition: inline; filename="return-evidence"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($realFile);
