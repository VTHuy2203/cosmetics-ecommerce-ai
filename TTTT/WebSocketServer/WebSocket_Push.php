<?php
/**
 * WebSocket Push Helper Function
 * Use this function to send realtime notifications to WebSocket clients
 * 
 * Usage:
 *   websocket_push(['admin_channel', 'staff_channel'], [
 *       'type' => 'new_order',
 *       'data' => ['order_id' => 123, 'customer_name' => 'John Doe']
 *   ]);
 */

function websocket_push($channels, $message_data) {
    // Ensure channels is an array
    if (!is_array($channels)) {
        $channels = [$channels];
    }
    
    // Build the message payload
    $message = [
        'type' => isset($message_data['type']) ? $message_data['type'] : 'notification',
        'data' => isset($message_data['data']) ? $message_data['data'] : $message_data,
        'timestamp' => time()
    ];
    
    // Add notification details if provided
    if (isset($message_data['title'])) {
        $message['title'] = $message_data['title'];
    }
    if (isset($message_data['message'])) {
        $message['message'] = $message_data['message'];
    }
    // Always set sound to 'notification' by default if not specified
    $message['sound'] = isset($message_data['sound']) ? $message_data['sound'] : 'notification';
    
    // Determine broadcast endpoint URL
    // Adjust this path based on your server configuration
    $broadcast_url = 'http://localhost:8080/broadcast.php';
    
    // Try to use the broadcast.php endpoint if available
    if (file_exists(__DIR__ . '/broadcast.php')) {
        // Use local file-based queue (more reliable)
        $queue_file = __DIR__ . '/message_queue.json';
        
        // Read existing queue
        $queue = [];
        if (file_exists($queue_file)) {
            $queue = json_decode(file_get_contents($queue_file), true) ?: [];
        }
        
        // Add new message to queue with unique ID to prevent duplicates
        $queue[] = [
            'channels' => $channels,
            'message' => $message,
            'timestamp' => time(),
            'id' => md5(json_encode($message) . time() . implode(',', $channels)) // Unique ID
        ];
        
        // Keep only last 50 messages (reduced to prevent old messages from accumulating)
        if (count($queue) > 50) {
            $queue = array_slice($queue, -50);
        }
        
        // Write queue back - use LOCK_EX to ensure atomic write
        $result = file_put_contents($queue_file, json_encode($queue, JSON_UNESCAPED_UNICODE), LOCK_EX);
        
        // Verify write was successful
        if ($result === false) {
            error_log("WebSocket_Push.php: ERROR - Failed to write to queue file: {$queue_file}");
            return false;
        }
        
        // Force flush to ensure data is written to disk immediately
        if (function_exists('fflush')) {
            $fp = fopen($queue_file, 'r+');
            if ($fp) {
                fflush($fp);
                fclose($fp);
            }
        }
        
        error_log("WebSocket_Push.php: ✅ Message queued successfully - type: {$message['type']}, channels: " . implode(', ', $channels));
        
        return true;
    }
    
    // Fallback: Use HTTP POST to broadcast endpoint
    $post_data = json_encode([
        'channels' => $channels,
        'message' => $message
    ]);
    
    $ch = curl_init($broadcast_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 second timeout (non-blocking)
    
    // Execute asynchronously (don't wait for response)
    curl_exec($ch);
    curl_close($ch);
    
    return true;
}

/**
 * Helper function to push new order notification
 */
function websocket_push_new_order($order_id, $customer_name, $total_amount) {
    return websocket_push(
        ['admin_channel', 'staff_channel'],
        [
            'type' => 'new_order',
            'title' => 'Đơn hàng mới',
            'message' => "Đơn hàng #{$order_id} từ khách hàng {$customer_name} - Tổng tiền: " . number_format($total_amount, 0, ',', '.') . " đ",
            'sound' => 'notification',
            'data' => [
                'order_id' => $order_id,
                'customer_name' => $customer_name,
                'total_amount' => $total_amount,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push order status update
 */
function websocket_push_order_status($order_id, $status, $status_text, $channels = null) {
    if ($channels === null) {
        // Default: notify admin and user
        $channels = ['admin_channel', 'user_channel'];
    }
    
    return websocket_push(
        $channels,
        [
            'type' => 'order_status_update',
            'title' => 'Cập nhật đơn hàng',
            'message' => "Đơn hàng #{$order_id}: {$status_text}",
            'sound' => 'notification',
            'data' => [
                'order_id' => $order_id,
                'status' => $status,
                'status_text' => $status_text,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push new product notification
 * Admin adds product → Notify Staff and all Customers
 */
function websocket_push_new_product($product_id, $product_name) {
    return websocket_push(
        ['staff_channel', 'user_channel'],
        [
            'type' => 'new_product',
            'title' => 'Sản phẩm mới',
            'message' => "Sản phẩm mới: {$product_name}",
            'sound' => 'notification',
            'data' => [
                'product_id' => $product_id,
                'product_name' => $product_name,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push product update notification
 * Admin edits/deletes product → Notify Staff and all Customers
 */
function websocket_push_product_update($product_id, $product_name, $update_type = 'update') {
    $messages = [
        'delete' => 'Sản phẩm đã bị xóa',
        'update' => 'Sản phẩm đã được cập nhật',
        'stock' => 'Tồn kho sản phẩm đã thay đổi',
        'price' => 'Giá sản phẩm đã thay đổi'
    ];
    
    $title = isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật sản phẩm';
    $message_text = (isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật') . ': ' . $product_name;
    
    return websocket_push(
        ['staff_channel', 'user_channel'],
        [
            'type' => 'product_' . $update_type,
            'title' => $title,
            'message' => $message_text,
            'sound' => 'notification',
            'data' => [
                'product_id' => $product_id,
                'product_name' => $product_name,
                'update_type' => $update_type,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push new user registration notification
 */
function websocket_push_new_user($user_id, $user_name) {
    return websocket_push(
        ['admin_channel'],
        [
            'type' => 'new_user',
            'title' => 'Người dùng mới',
            'message' => "Người dùng mới đăng ký: {$user_name}",
            'sound' => 'notification',
            'data' => [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push chat message notification
 */
function websocket_push_chat_message($user_id, $user_name, $message_preview) {
    return websocket_push(
        ['admin_channel'],
        [
            'type' => 'chat_message',
            'title' => 'Tin nhắn mới',
            'message' => "{$user_name}: " . mb_substr($message_preview, 0, 50) . '...',
            'sound' => 'notification',
            'data' => [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'message_preview' => $message_preview,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push category notification
 * Admin adds/edits/deletes categories → Notify Staff and all Customers
 */
function websocket_push_category_update($category_id, $category_name, $update_type = 'update') {
    $messages = [
        'add' => 'Danh mục mới đã được thêm',
        'update' => 'Danh mục đã được cập nhật',
        'delete' => 'Danh mục đã bị xóa'
    ];
    
    $title = isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật danh mục';
    $message_text = (isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật') . ': ' . $category_name;
    
    return websocket_push(
        ['staff_channel', 'user_channel'],
        [
            'type' => 'category_' . $update_type,
            'title' => $title,
            'message' => $message_text,
            'sound' => 'notification',
            'data' => [
                'category_id' => $category_id,
                'category_name' => $category_name,
                'update_type' => $update_type,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push brand notification
 * Admin adds/edits/deletes brands → Notify Staff and all Customers
 */
function websocket_push_brand_update($brand_id, $brand_name, $update_type = 'update') {
    $messages = [
        'add' => 'Thương hiệu mới đã được thêm',
        'update' => 'Thương hiệu đã được cập nhật',
        'delete' => 'Thương hiệu đã bị xóa'
    ];
    
    $title = isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật thương hiệu';
    $message_text = (isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật') . ': ' . $brand_name;
    
    return websocket_push(
        ['staff_channel', 'user_channel'],
        [
            'type' => 'brand_' . $update_type,
            'title' => $title,
            'message' => $message_text,
            'sound' => 'notification',
            'data' => [
                'brand_id' => $brand_id,
                'brand_name' => $brand_name,
                'update_type' => $update_type,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push news/post notification
 * Admin adds/edits/deletes news/posts → Notify Staff and all Customers
 */
function websocket_push_news_update($news_id, $news_title, $update_type = 'update') {
    $messages = [
        'add' => 'Bài viết mới đã được thêm',
        'update' => 'Bài viết đã được cập nhật',
        'delete' => 'Bài viết đã bị xóa'
    ];
    
    $title = isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật bài viết';
    $message_text = (isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật') . ': ' . $news_title;
    
    return websocket_push(
        ['staff_channel', 'user_channel'],
        [
            'type' => 'news_' . $update_type,
            'title' => $title,
            'message' => $message_text,
            'sound' => 'notification',
            'data' => [
                'news_id' => $news_id,
                'news_title' => $news_title,
                'update_type' => $update_type,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push customer edit/delete notification
 * Admin edits/deletes customers (only customers, not shippers) → Notify Staff
 */
function websocket_push_customer_update($customer_id, $customer_name, $update_type = 'update') {
    $messages = [
        'update' => 'Thông tin khách hàng đã được cập nhật',
        'delete' => 'Khách hàng đã bị xóa'
    ];
    
    $title = isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật khách hàng';
    $message_text = (isset($messages[$update_type]) ? $messages[$update_type] : 'Cập nhật') . ': ' . $customer_name;
    
    return websocket_push(
        ['staff_channel'],
        [
            'type' => 'customer_' . $update_type,
            'title' => $title,
            'message' => $message_text,
            'sound' => 'notification',
            'data' => [
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'update_type' => $update_type,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push account approval notification
 * Admin approves account → Notify the approved account
 */
function websocket_push_account_approved($user_id, $user_name) {
    return websocket_push(
        ['user_channel'],
        [
            'type' => 'account_approved',
            'title' => 'Tài khoản đã được duyệt',
            'message' => "Tài khoản của bạn đã được Admin duyệt. Bạn có thể sử dụng đầy đủ các tính năng!",
            'sound' => 'notification',
            'data' => [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push customer info update notification
 * Customer updates information → Notify Admin
 */
function websocket_push_customer_info_updated($customer_id, $customer_name) {
    return websocket_push(
        ['admin_channel'],
        [
            'type' => 'customer_info_updated',
            'title' => 'Khách hàng đã cập nhật thông tin',
            'message' => "Khách hàng {$customer_name} đã cập nhật thông tin tài khoản",
            'sound' => 'notification',
            'data' => [
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

/**
 * Helper function to push deposit/top-up request notification
 * Customer requests to top-up/add funds → Notify Admin
 */
function websocket_push_deposit_request($customer_id, $customer_name, $amount, $note = '') {
    error_log("websocket_push_deposit_request: Called with customer_id={$customer_id}, name={$customer_name}, amount={$amount}");

    $result = websocket_push(
        ['admin_channel'],
        [
            'type' => 'deposit_request',
            'title' => 'Yêu cầu nạp tiền mới',
            'message' => "Khách hàng {$customer_name} yêu cầu nạp " . number_format($amount, 0, ',', '.') . " đ",
            'sound' => 'notification',
            'data' => [
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'amount' => $amount,
                'note' => $note,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );

    error_log("websocket_push_deposit_request: Push result = " . ($result ? 'SUCCESS' : 'FAILED'));
    return $result;
}

/**
 * Helper function to push customer self-update notification
 * Customer updates their own information → Notify Admin
 */
function websocket_push_customer_self_update($customer_id, $customer_name, $updated_fields = []) {
    $fields_text = !empty($updated_fields) ? implode(', ', $updated_fields) : 'thông tin cá nhân';
    return websocket_push(
        ['admin_channel'],
        [
            'type' => 'customer_self_update',
            'title' => 'Khách hàng cập nhật thông tin',
            'message' => "Khách hàng {$customer_name} đã cập nhật {$fields_text}",
            'sound' => 'notification',
            'data' => [
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'updated_fields' => $updated_fields,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

