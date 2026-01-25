<?php
/**
 * WebSocket Push Helper Function
 * Sends messages to WebSocket server for realtime updates
 */

function websocket_push($channels, $event_type, $data) {
    // WebSocket server configuration
    $ws_host = '127.0.0.1';
    $ws_port = 8080;
    
    // Ensure channels is an array
    if (!is_array($channels)) {
        $channels = [$channels];
    }
    
    // Prepare message
    $message = [
        'type' => $event_type,
        'data' => $data,
        'timestamp' => time()
    ];
    
    // Try to connect to WebSocket server via HTTP endpoint (if available)
    // Or use ZeroMQ, Redis pub/sub, or file-based queue
    
    // Method 1: HTTP endpoint to WebSocket server (recommended)
    $url = "http://{$ws_host}:{$ws_port}/broadcast";
    
    $post_data = json_encode([
        'channels' => $channels,
        'message' => $message
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 second timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    
    // Execute in background (non-blocking)
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    
    $result = @curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log errors but don't break the main flow
    if ($error) {
        error_log("WebSocket push error: {$error}");
    }
    
    return true;
}

/**
 * Helper functions for specific event types
 */

function websocket_notify_new_order($order_id, $customer_name, $total_amount) {
    return websocket_push(
        ['admin_channel', 'staff_channel'],
        'new_order',
        [
            'order_id' => $order_id,
            'customer_name' => $customer_name,
            'total_amount' => $total_amount,
            'message' => "Đơn hàng mới #{$order_id} từ {$customer_name} - " . number_format($total_amount, 0, ',', '.') . " đ"
        ]
    );
}

function websocket_notify_order_status_change($order_id, $status, $status_text, $user_id = null) {
    $channels = ['admin_channel'];
    
    // Notify shipper if order is approved
    if ($status == 1) {
        $channels[] = 'shipper_channel';
    }
    
    // Notify user if they own the order
    if ($user_id) {
        $channels[] = 'user_channel';
    }
    
    return websocket_push(
        $channels,
        'order_status_change',
        [
            'order_id' => $order_id,
            'status' => $status,
            'status_text' => $status_text,
            'user_id' => $user_id,
            'message' => "Đơn hàng #{$order_id} đã được cập nhật: {$status_text}"
        ]
    );
}

function websocket_notify_new_product($product_id, $product_name, $price) {
    return websocket_push(
        ['user_channel'],
        'new_product',
        [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'price' => $price,
            'message' => "Sản phẩm mới: {$product_name}"
        ]
    );
}

function websocket_notify_product_deleted($product_id) {
    return websocket_push(
        ['user_channel'],
        'product_deleted',
        [
            'product_id' => $product_id,
            'message' => "Sản phẩm đã bị xóa"
        ]
    );
}

function websocket_notify_product_updated($product_id, $product_name, $changes) {
    return websocket_push(
        ['user_channel'],
        'product_updated',
        [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'changes' => $changes,
            'message' => "Sản phẩm {$product_name} đã được cập nhật"
        ]
    );
}

function websocket_notify_new_user($user_id, $user_name) {
    return websocket_push(
        ['admin_channel'],
        'new_user',
        [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'message' => "Người dùng mới đăng ký: {$user_name}"
        ]
    );
}

function websocket_notify_new_chat_message($user_id, $user_name, $message_preview) {
    return websocket_push(
        ['admin_channel'],
        'new_chat_message',
        [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'message_preview' => $message_preview,
            'message' => "Tin nhắn mới từ {$user_name}"
        ]
    );
}

