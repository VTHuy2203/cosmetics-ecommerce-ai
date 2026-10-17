<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/config/security.php';
header('Content-Type: text/plain; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); exit('method_not_allowed');
}
if (empty($_SESSION['admin_id']) || !is_string($_SESSION['admin_user'] ?? null)
    || !in_array($_SESSION['admin_role'] ?? '', ['admin', 'manager'], true)) {
    http_response_code(403); exit('forbidden');
}
$csrf = $_POST['csrf_token'] ?? '';
if (!is_string($csrf) || !csrf_is_valid($csrf)) {
    http_response_code(403); exit('csrf_invalid');
}
$rawId = $_POST['IDComment'] ?? null;
$id = is_scalar($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
if (!$id || $id < 1) { http_response_code(422); exit('invalid_comment'); }
require_once __DIR__ . '/../DB_Connect.php';
try {
    $actor = $conn->prepare("SELECT 1 FROM khachhang WHERE ID_KhachHang = ? AND TenDangNhap = ? AND Role IN ('admin','manager')");
    $actorId = (int) $_SESSION['admin_id'];
    $actor->bind_param('is', $actorId, $_SESSION['admin_user']);
    $actor->execute();
    if (!$actor->get_result()->fetch_row()) { http_response_code(403); exit('forbidden'); }
    $delete = $conn->prepare('DELETE FROM binhluan WHERE ID_BinhLuan = ?');
    $delete->bind_param('i', $id);
    $delete->execute();
    if ($delete->affected_rows !== 1) { http_response_code(404); exit('not_found'); }
    echo 'xoathanhcong';
} catch (Throwable $error) {
    error_log('Comment deletion failed; code=' . (int) $error->getCode());
    http_response_code(500); echo 'error';
}
