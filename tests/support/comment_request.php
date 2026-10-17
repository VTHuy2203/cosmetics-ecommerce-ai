<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/environment.php';
$input = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
$targets = [
    'create' => '/TTTT/WebBanHangMyPham/src/Processing/XuLy_BinhLuan.php',
    'delete' => '/TTTT/AdminManage/src/Comments/Delete_Comment.php',
];
if (!isset($targets[$input['target'] ?? ''])) { throw new RuntimeException('Invalid fixture target.'); }
$target = dirname(__DIR__, 2) . $targets[$input['target']];
$_SERVER['REQUEST_METHOD'] = $input['method'] ?? 'POST';
$_SERVER['PHP_SELF'] = $targets[$input['target']];
$_POST = $input['post'] ?? [];
$_SESSION = $input['session'] ?? [];
http_response_code(200);
ob_start();
register_shutdown_function(static function (): void {
    $body = (string) ob_get_clean();
    echo json_encode(['status' => http_response_code(), 'body' => $body], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
});
chdir(dirname($target));
require $target;
