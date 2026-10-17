<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/environment.php';
$input = json_decode(stream_get_contents(STDIN), true, 16, JSON_THROW_ON_ERROR);
$targets = [
    'customer' => '/TTTT/WebBanHangMyPham/src/Processing/Return_Attachment.php',
    'admin' => '/TTTT/AdminManage/src/Users/Private_File.php',
];
if (!isset($targets[$input['target'] ?? ''])) { throw new RuntimeException('Invalid private-file target.'); }
$target = dirname(__DIR__, 2) . $targets[$input['target']];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = $targets[$input['target']];
$_GET = is_array($input['get'] ?? null) ? $input['get'] : [];
$_SESSION = is_array($input['session'] ?? null) ? $input['session'] : [];
http_response_code(200);
ob_start();
register_shutdown_function(static function (): void {
    $body = (string) ob_get_clean();
    echo json_encode([
        'status' => http_response_code(),
        'body_base64' => base64_encode($body),
        'length' => strlen($body),
    ], JSON_THROW_ON_ERROR);
});
chdir(dirname($target));
require $target;
