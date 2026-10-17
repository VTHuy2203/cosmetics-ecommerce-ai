<?php
declare(strict_types=1);

$root = dirname(__DIR__) . '/TTTT';
$api = (string) file_get_contents($root . '/AdminManage/src/Oders/Order_Status_Action.php');
$server = (string) file_get_contents($root . '/WebSocketServer/src/RealtimeServer.php');
$layout = (string) file_get_contents($root . '/AdminManage/src/Layouts/Link.php');
$checkout = (string) file_get_contents($root . '/WebBanHangMyPham/src/Services/CheckoutService.php');
$websocketClient = (string) file_get_contents($root . '/WebBanHangMyPham/JS/websocket_client.js');
$adminNotifications = (string) file_get_contents($root . '/AdminManage/src/Processing/Admin_Notifications.php');

$checks = [
    [$api, "['user_channel']", 'Customer notification channel is missing.'],
    [$api, "'customer_id' => \$customerId", 'Customer notification is not targeted.'],
    [$api, 'INSERT INTO customer_notifications', 'Persistent customer notification is missing.'],
    [$server, 'client_user_id !== $target_customer_id', 'WebSocket server does not filter customer recipients.'],
    [$layout, "event.target.closest('.js-order-status-form, .js-order-payment-form')", 'AJAX form interception is missing.'],
    [$layout, 'await fetch(form.action', 'AJAX order update request is missing.'],
    [$checkout, '$this->notifyNewOrder($orderId, $name, $amount, $paymentMethod)', 'Checkout does not notify Admin after commit.'],
    [$checkout, 'websocket_push_new_order($orderId, $customerName, $amount)', 'New order realtime push is missing.'],
    [$websocketClient, "new CustomEvent('moshop:realtime-notification'", 'Browser realtime event is missing.'],
    [$layout, "window.addEventListener('moshop:realtime-notification'", 'Admin on-screen realtime listener is missing.'],
    [$layout, 'pollAdminNotifications', 'Admin notification fallback polling is missing.'],
    [$adminNotifications, 'FROM admin_notifications', 'Admin persistent notification endpoint is missing.'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "order_realtime_targeting_smoke=ok\n";
