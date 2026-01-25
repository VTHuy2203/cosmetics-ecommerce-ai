<?php
/**
 * WebSocket Server Startup Script
 * Run this with: php server.php
 */

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use WebSocketServer\RealtimeServer;

// Configuration
$port = 8080;
$host = '0.0.0.0';

echo "Starting WebSocket Server on {$host}:{$port}...\n";
echo "Press Ctrl+C to stop the server\n\n";

$realtimeServer = new RealtimeServer();

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
