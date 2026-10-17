<?php
namespace WebSocketServer;

require_once dirname(__DIR__, 2) . '/config/realtime.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

/**
 * Realtime WebSocket Server
 * Xử lý các vai trò: admin_channel, user_channel, staff_channel, shipper_channel
 */
class RealtimeServer implements MessageComponentInterface {
    protected $clients;
    protected $channels;
    
    protected $loop;
    protected $queue_file;
    protected string $ticket_secret = '';
    protected array $used_ticket_ids = [];
    
    public function __construct(?string $ticketSecret = null, ?string $queueFile = null) {
        $this->ticket_secret = $ticketSecret ?? (string) getenv('REALTIME_TICKET_SECRET');
        if (strlen($this->ticket_secret) < 32) {
            throw new \RuntimeException('REALTIME_TICKET_SECRET must contain at least 32 characters.');
        }
        $this->clients = new \SplObjectStorage;
        $this->channels = [
            'admin_channel' => [],
            'user_channel' => [],
            'staff_channel' => [],
            'shipper_channel' => []
        ];
        $this->queue_file = $queueFile ?? (__DIR__ . '/../message_queue.json');
        
        // Create queue file if it doesn't exist
        if (!file_exists($this->queue_file)) {
            file_put_contents($this->queue_file, json_encode([]));
        } else {
            // CRITICAL: Xóa tin nhắn cũ khi khởi động máy để tránh gửi thông báo đã lưu trong bộ nhớ đệm
            // giữ lại những tin mới nhất (dưới 10 giây)
            $queue = json_decode(file_get_contents($this->queue_file), true);
            if (is_array($queue) && !empty($queue)) {
                $current_time = time();
                $fresh_messages = [];
                foreach ($queue as $item) {
                    $message_age = $current_time - ($item['timestamp'] ?? 0);
                    // giữ lại tin nhắn mới hơn 10 giây
                    if ($message_age < 10) {
                        $fresh_messages[] = $item;
                    }
                }
                // xử lý nhận tin nhắn mới nhất hoặc mảng trống
                file_put_contents($this->queue_file, json_encode($fresh_messages));
                if (count($queue) > count($fresh_messages)) {
                    echo "Cleared " . (count($queue) - count($fresh_messages)) . " old messages from queue on startup\n";
                }
            }
        }
    }
    
    public function setLoop($loop) {
        $this->loop = $loop;
        
        // Thiết lập bộ hẹn giờ để kiểm tra hàng đợi tin nhắn mỗi 100ms
        $this->loop->addPeriodicTimer(0.1, function() {
            $this->processMessageQueue();
        });
        $this->loop->addPeriodicTimer(5, function() {
            $this->closeExpiredConnections();
        });
    }

    protected function closeExpiredConnections(): void {
        $now = time();
        foreach ($this->clients as $client) {
            if ((int) ($client->auth_expires_at ?? 0) <= $now) {
                $client->send(json_encode(['action' => 'error', 'code' => 'authorization_expired']));
                $client->close();
            }
        }
        foreach ($this->used_ticket_ids as $ticketId => $expiresAt) {
            if ($expiresAt <= $now) {
                unset($this->used_ticket_ids[$ticketId]);
            }
        }
    }
    
    protected $processed_messages = []; // Theo dõi tin nhắn đã xử lý để tránh trùng lặp
    
    protected function processMessageQueue() {
        if (!file_exists($this->queue_file)) {
            return;
        }
        
        $queue = json_decode(file_get_contents($this->queue_file), true);
        if (!is_array($queue) || empty($queue)) {
            return;
        }
        
        $current_time = time();
        $processed_any = false;
        $messages_to_keep = [];
        
        // Process messages - ONLY process FRESH messages (less than 30 seconds old)
        // This prevents old cached messages from being sent when server restarts
        foreach ($queue as $item) {
            if (!isset($item['channels']) || !isset($item['message'])) {
                continue;
            }
            
            // CRITICAL: Only process messages that are FRESH (less than 30 seconds old)
            // This prevents sending old cached messages when server starts
            $message_age = $current_time - ($item['timestamp'] ?? 0);
            if ($message_age > 30) {
                // Message is older than 30 seconds, skip it (too old to be relevant)
                continue;
            }
            
            // Create message ID that includes timestamp to allow same content at different times
            $message_id = $this->getMessageId($item);
            
            // Only skip if we processed this EXACT message (same content AND same timestamp) within last 5 seconds
            // This prevents true duplicates (same message sent multiple times rapidly)
            if (isset($this->processed_messages[$message_id])) {
                $last_processed = $this->processed_messages[$message_id];
                if ($current_time - $last_processed < 5) {
                    // Same message processed within last 5 seconds, skip (true duplicate)
                    continue;
                }
            }
            
            // Check if there are any clients in the target channels before processing
            $has_clients = false;
            foreach ($item['channels'] as $channel) {
                if (isset($this->channels[$channel]) && count($this->channels[$channel]) > 0) {
                    $has_clients = true;
                    break;
                }
            }
            
            // Only process if there are clients to receive the message
            if (!$has_clients) {
                continue;
            }
            
            // Process the message
            echo "📨 Processing message: " . ($item['message']['title'] ?? 'notification') . " to channels: " . implode(', ', $item['channels']) . "\n";
            $this->broadcastToChannels($item['channels'], $item['message']);
            $this->processed_messages[$message_id] = $current_time;
            $processed_any = true;
        }
        
        // Clean up old processed message IDs (older than 1 minute)
        foreach ($this->processed_messages as $msg_id => $processed_time) {
            if ($current_time - $processed_time > 60) {
                unset($this->processed_messages[$msg_id]);
            }
        }
        
        // Clear queue after processing
        file_put_contents($this->queue_file, json_encode([]));
    }
    
    /**
     * Generate unique message ID to prevent duplicates
     * Include timestamp rounded to nearest second to allow same content at different times
     */
    protected function getMessageId($item) {
        $message = $item['message'] ?? [];
        $channels = implode(',', $item['channels'] ?? []);
        $timestamp = $item['timestamp'] ?? time();
        
        // Create ID based on order_id, type, channels, message content, AND timestamp (rounded to second)
        // This allows same content at different times but prevents exact duplicates
        $order_id = $message['data']['order_id'] ?? null;
        $product_id = $message['data']['product_id'] ?? null;
        $type = $message['type'] ?? 'unknown';
        $title = $message['title'] ?? '';
        $msg_text = $message['message'] ?? '';
        
        // Round timestamp to nearest second to catch rapid duplicates
        $rounded_timestamp = floor($timestamp);
        
        // Create ID from content AND timestamp (rounded)
        return md5($channels . '|' . $type . '|' . $order_id . '|' . $product_id . '|' . $title . '|' . substr($msg_text, 0, 50) . '|' . $rounded_timestamp);
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->channel = null;
        $conn->role = null;
        $conn->user_id = null;
        $conn->auth_expires_at = 0;
        
        echo "New connection! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        if (!is_string($msg) || strlen($msg) > 4096) {
            $this->rejectConnection($from, 'invalid_message');
            return;
        }
        $data = json_decode($msg, true);
        
        if (!$data) {
            echo "Failed to decode JSON message from client {$from->resourceId}: {$msg}\n";
            return;
        }
        
        if (!isset($data['action'])) {
            echo "Message missing 'action' field from client {$from->resourceId}: " . json_encode($data) . "\n";
            return;
        }
        
        // Don't log ping messages - they're just keepalive heartbeats
        if ($data['action'] === 'ping') {
            if ((int) ($from->auth_expires_at ?? 0) <= time()) {
                $this->rejectConnection($from, 'authorization_required');
                return;
            }
            $from->send(json_encode(['action' => 'pong']));
            return;
        }
        
        echo "Received message from client {$from->resourceId}: {$msg}\n";
        echo "Processing action: {$data['action']}\n";
        
        switch ($data['action']) {
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;
            default:
                echo "Unknown action: {$data['action']}\n";
        }
    }
    
    protected function handleSubscribe(ConnectionInterface $conn, $data) {
        $ticket = is_string($data['ticket'] ?? null) ? $data['ticket'] : '';
        $origin = '';
        if (isset($conn->httpRequest) && method_exists($conn->httpRequest, 'getHeaderLine')) {
            $origin = (string) $conn->httpRequest->getHeaderLine('Origin');
        }
        $identity = realtime_ticket_verify($ticket, $this->ticket_secret, $origin, time());
        if ($identity === null || isset($this->used_ticket_ids[$identity['jti']])) {
            $this->rejectConnection($conn, 'authorization_failed');
            return;
        }
        $channel = (string) $identity['channel'];
        $role = (string) $identity['role'];
        $user_id = (int) $identity['sub'];
        
        // Remove from previous channel if exists
        if ($conn->channel && isset($this->channels[$conn->channel])) {
            $key = array_search($conn, $this->channels[$conn->channel], true);
            if ($key !== false) {
                unset($this->channels[$conn->channel][$key]);
                echo "  ↻ Removed client {$conn->resourceId} from previous channel: {$conn->channel}\n";
            }
        }
        
        // Add to new channel
        $conn->channel = $channel;
        $conn->role = $role;
        $conn->user_id = $user_id;
        $conn->auth_expires_at = (int) $identity['exp'];
        $this->used_ticket_ids[$identity['jti']] = (int) $identity['exp'];
        $this->channels[$channel][] = $conn;
        
        $conn->send(json_encode([
            'action' => 'subscribed',
            'channel' => $channel,
            'expires_at' => (int) $identity['exp'],
        ]));
        
        $clientCount = count($this->channels[$channel]);
        echo "✅ Client {$conn->resourceId} subscribed to {$channel} (Total clients in channel: {$clientCount})\n";
    }

    protected function rejectConnection(ConnectionInterface $conn, string $code): void {
        $conn->send(json_encode(['action' => 'error', 'code' => $code]));
        $conn->close();
    }
    
    public function onClose(ConnectionInterface $conn) {
        // Remove from channel
        if ($conn->channel && isset($this->channels[$conn->channel])) {
            $key = array_search($conn, $this->channels[$conn->channel], true);
            if ($key !== false) {
                unset($this->channels[$conn->channel][$key]);
            }
        }
        
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    /**
     * Broadcast message to a specific channel
     * For user_channel, if message contains customer_id, only send to matching user_id
     */
    public function broadcastToChannel($channel, $message) {
        if (!isset($this->channels[$channel])) {
            echo "⚠️ Channel '{$channel}' does not exist\n";
            return false;
        }
        
        $clientCount = count($this->channels[$channel]);
        echo "📢 Broadcasting to channel '{$channel}' ({$clientCount} clients)\n";
        
        if ($clientCount === 0) {
            echo "⚠️ WARNING: No clients in channel '{$channel}' to receive the message!\n";
            return false;
        }
        
        // CRITICAL FIX: For user_channel, filter by customer_id if present
        $target_customer_id = null;
        if ($channel === 'user_channel' && isset($message['data']['customer_id'])) {
            $target_customer_id = intval($message['data']['customer_id']);
            echo "🔍 Filtering user_channel notifications by customer_id: {$target_customer_id}\n";
        }
        
        $data = json_encode($message);
        $count = 0;
        $filtered_count = 0;
        
        foreach ($this->channels[$channel] as $client) {
            if ((int) ($client->auth_expires_at ?? 0) <= time()) {
                $client->close();
                continue;
            }
            // Filter: If this is user_channel and message has customer_id, only send to matching user_id
            if ($channel === 'user_channel' && $target_customer_id !== null) {
                $client_user_id = $client->user_id ? intval($client->user_id) : null;
                if ($client_user_id !== $target_customer_id) {
                    $filtered_count++;
                    echo "  ⊘ Filtered out client {$client->resourceId} (user_id: {$client_user_id}, target: {$target_customer_id})\n";
                    continue;
                }
                echo "  ✓ Matched client {$client->resourceId} (user_id: {$client_user_id})\n";
            }
            
            try {
                $client->send($data);
                $count++;
                echo "  ✓ Sent to client {$client->resourceId}\n";
            } catch (\Exception $e) {
                echo "  ✗ Error sending to client {$client->resourceId}: {$e->getMessage()}\n";
            }
        }
        
        if ($filtered_count > 0) {
            echo "✅ Broadcasted to {$count}/{$clientCount} clients in {$channel} (filtered out {$filtered_count})\n";
        } else {
            echo "✅ Broadcasted to {$count}/{$clientCount} clients in {$channel}\n";
        }
        return $count > 0;
    }
    
    /**
     * Broadcast message to multiple channels
     */
    public function broadcastToChannels($channels, $message) {
        $total = 0;
        foreach ($channels as $channel) {
            $total += $this->broadcastToChannel($channel, $message);
        }
        return $total;
    }
}

