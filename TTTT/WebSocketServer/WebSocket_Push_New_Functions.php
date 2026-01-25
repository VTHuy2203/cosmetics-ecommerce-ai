<?php
/**
 * Hàm push thống báo cập nhật danh mục
 * Admin adds/edits/deletes category → Notify Staff and all Customers
 */
function websocket_push_category_update($category_id, $category_name, $update_type = 'update') {
    $messages = [
        'delete' => 'Danh mục đã bị xóa',
        'update' => 'Danh mục đã được cập nhật',
        'add' => 'Danh mục mới đã được thêm'
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
 * Hàm push thống báo cập nhật thương hiệu
 * Admin adds/edits/deletes brand → Notify Staff and all Customers
 */
function websocket_push_brand_update($brand_id, $brand_name, $update_type = 'update') {
    $messages = [
        'delete' => 'Thương hiệu đã bị xóa',
        'update' => 'Thương hiệu đã được cập nhật',
        'add' => 'Thương hiệu mới đã được thêm'
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
 * Hàm push thống báo cập nhật bài viết
 * Admin adds/edits/deletes news/post → Notify Staff and all Customers
 */
function websocket_push_news_update($news_id, $news_title, $update_type = 'update') {
    $messages = [
        'delete' => 'Bài viết đã bị xóa',
        'update' => 'Bài viết đã được cập nhật',
        'add' => 'Bài viết mới đã được thêm'
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
 * Hàm push thống báo cập nhật khách hàng
 * Admin edits/deletes customer (not shipper) → Notify Staff only
 */
function websocket_push_customer_update($customer_id, $customer_name, $update_type = 'update') {
    $messages = [
        'delete' => 'Khách hàng đã bị xóa',
        'update' => 'Thông tin khách hàng đã được cập nhật'
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
 * Hàm push thống báo duyệt tài khoản
 * Admin approves account → Notify the approved account
 */
function websocket_push_account_approved($user_id, $user_name) {
    return websocket_push(
        ['user_channel'],
        [
            'type' => 'account_approved',
            'title' => 'Tài khoản đã được duyệt',
            'message' => "Tài khoản của bạn ({$user_name}) đã được Admin duyệt thành công!",
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
 * Hàm push thống báo cập nhật thông tin khách hàng
 * Customer updates information → Notify Admin
 */
function websocket_push_customer_info_update($customer_id, $customer_name) {
    return websocket_push(
        ['admin_channel'],
        [
            'type' => 'customer_info_update',
            'title' => 'Khách hàng cập nhật thông tin',
            'message' => "Khách hàng {$customer_name} đã cập nhật thông tin cá nhân",
            'sound' => 'notification',
            'data' => [
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    );
}

