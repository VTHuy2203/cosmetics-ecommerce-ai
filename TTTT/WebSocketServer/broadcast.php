<?php
/**
 * HTTP Endpoint for Broadcasting Messages to WebSocket Server
 * This allows PHP backend to send messages to WebSocket clients
 */

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Factory;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['channels']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$channels = $input['channels'];
$message = $input['message'];

// Since we can't directly access the running WebSocket server instance,
// we'll use a shared memory/file-based queue or ZeroMQ
// For simplicity, we'll use a file-based queue that the WebSocket server polls

$queue_file = __DIR__ . '/message_queue.json';

// Read existing queue
$queue = [];
if (file_exists($queue_file)) {
    $queue = json_decode(file_get_contents($queue_file), true) ?: [];
}

// Add new message to queue
$queue[] = [
    'channels' => $channels,
    'message' => $message,
    'timestamp' => time()
];

// Keep only last 100 messages
if (count($queue) > 100) {
    $queue = array_slice($queue, -100);
}

// Write queue back
file_put_contents($queue_file, json_encode($queue));

echo json_encode(['success' => true, 'queued' => true]);

