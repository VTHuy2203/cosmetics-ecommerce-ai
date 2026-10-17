<?php
/**
 * WebSocket Server Startup Script
 * Run this with: php server.php
 */

require __DIR__ . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use WebSocketServer\RealtimeServer;

// Configuration
$port = filter_var(app_env('REALTIME_WS_PORT', '8080'), FILTER_VALIDATE_INT);
$host = app_env('REALTIME_WS_HOST', '0.0.0.0') ?? '0.0.0.0';
if (!$port || $port < 1 || $port > 65535 || !in_array($host, ['0.0.0.0', '127.0.0.1'], true)) {
    fwrite(STDERR, "REALTIME_WS_HOST or REALTIME_WS_PORT is invalid.\n");
    exit(1);
}

echo "Starting WebSocket Server on {$host}:{$port}...\n";
echo "Press Ctrl+C to stop the server\n\n";

$realtimeSecret = app_env('REALTIME_TICKET_SECRET', '') ?? '';
if (strlen($realtimeSecret) < 32) {
    fwrite(STDERR, "REALTIME_TICKET_SECRET must contain at least 32 characters.\n");
    exit(1);
}
$queueFile = app_env('REALTIME_QUEUE_FILE', '') ?: null;
$realtimeServer = new RealtimeServer($realtimeSecret, $queueFile);

$server = IoServer::factory(
    new HttpServer(
        new WsServer($realtimeServer)
    ),
    $port,
    $host
);

// Set event loop for message queue polling
$realtimeServer->setLoop($server->loop);

$server->run();
