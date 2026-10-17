<?php
declare(strict_types=1);

require_once '../DB_Connect.php';
require_once '../Layouts/Admin_Access_Check.php';

$relative = str_replace('\\', '/', (string) ($_GET['path'] ?? ''));
if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, '/')) {
    http_response_code(404);
    exit;
}

if (str_starts_with($relative, 'shipper_id_cards/')) {
    if (($_SESSION['admin_role'] ?? null) === 'employee') {
        http_response_code(403);
        exit;
    }
    $base = dirname(__DIR__, 3) . '/storage/private';
    $candidate = $base . '/' . $relative;
} elseif (str_starts_with($relative, 'return_refunds/')) {
    $base = dirname(__DIR__, 3) . '/storage/private';
    $candidate = $base . '/' . $relative;
} elseif (str_starts_with($relative, 'uploads/shipper_id_cards/')) {
    // Read-only compatibility for files created by the legacy application.
    if (($_SESSION['admin_role'] ?? null) === 'employee') {
        http_response_code(403);
        exit;
    }
    $base = dirname(__DIR__, 3) . '/WebBanHangMyPham/src';
    $candidate = $base . '/' . $relative;
} else {
    http_response_code(404);
    exit;
}

$realBase = realpath($base);
$realFile = realpath($candidate);
if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) || !is_file($realFile)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($realFile);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime'], true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realFile));
header('Content-Disposition: inline; filename="private-evidence"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($realFile);
