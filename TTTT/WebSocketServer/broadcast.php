<?php
declare(strict_types=1);

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ok' => false, 'error' => 'endpoint_disabled', 'message' => 'WebSocket queue accepts writes only through server-side helpers']);
