/**
 * WebSocket Client Library for Realtime Updates
 * Xử lý kết nối, thông báo và cập nhật giao diện người dùng
 */

// Ngăn chặn khai báo lớp trùng lặp
if (typeof RealtimeWebSocket === 'undefined') {
    class RealtimeWebSocket {
    constructor(config = {}) {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.reconnectDelay = 3000;
        this.heartbeatInterval = 30000; // 30 seconds
        this.heartbeatTimer = null;
        
        // Configuration
        this.wsUrl = config.wsUrl || 'ws://localhost:8080';
        this.role = config.role || 'user'; // admin, user, staff, shipper
        this.userId = config.userId || null;
        // Sử dụng kênh từ cấu hình nếu được cung cấp, nếu không thì sử dụng thông tin từ vai trò.
        this.channel = config.channel || this.getChannelForRole(this.role);
        
        console.log('🔧 RealtimeWebSocket initialized:', {
            role: this.role,
            channel: this.channel,
            userId: this.userId,
            wsUrl: this.wsUrl
        });
        
        // Callbacks
        this.onConnect = config.onConnect || null;
        this.onDisconnect = config.onDisconnect || null;
        this.onMessage = config.onMessage || null;
        this.onError = config.onError || null;
        
        // Notification system
        this.notificationContainer = null;
        this.soundEnabled = true;
        this.notificationPermission = 'default'; // 'default', 'granted', 'denied'
        
        // Theo dõi các thông báo đã xử lý để tránh trùng lặp (sử dụng chức năng Bản đồ để theo dõi thời gian).
        this.processedNotifications = new Map();
        this.nativeNotificationsEnabled = true;
        
        // Track deleted products
        this.deletedProducts = new Set(); // Lưu trữ ID sản phẩm đã xóa
        
        // Track subscription status
        this.isSubscribed = false;
        this.subscriptionCheckInterval = null;
        
        this.init();
    }
    
    getChannelForRole(role) {
        const channelMap = {
            'admin': 'admin_channel',
            'user': 'user_channel',
            'customer': 'user_channel',
            'staff': 'staff_channel',
            'employee': 'staff_channel',
            'shipper': 'shipper_channel'
        };
        return channelMap[role] || 'user_channel';
    }
    
    init() {
        // CRITICAL: Create notification container FIRST, before anything else
        // Điều này đảm bảo thông báo luôn có thể được hiển thị
        this.createNotificationContainer();
        this.loadNotificationSound();
        this.setupPermissionRequestOnInteraction();
        this.setupProductClickInterceptor();
        
        // Đảm bảo container vẫn tồn tại ngay cả khi hàm createNotificationContainer thất bại.
        if (!this.notificationContainer) {
            console.error('❌ Notification container creation failed, retrying...');
            setTimeout(() => {
                if (!this.notificationContainer) {
                    this.createNotificationContainer();
                }
            }, 100);
        }
        
        this.connect();
    }
    
    /**
     * Thiết lập bộ chặn cho các lần nhấp vào liên kết sản phẩm để kiểm tra xem sản phẩm đã bị xóa hay chưa.
     */
    setupProductClickInterceptor() {
        // Intercept clicks on product links
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href*="Detail_Product.php"], a[href*="product_id"]');
            if (link) {
                const href = link.getAttribute('href') || link.href;
                const productIdMatch = href.match(/product_id[=:](\d+)/);
                if (productIdMatch) {
                    const productId = parseInt(productIdMatch[1]);
                    if (this.deletedProducts.has(productId)) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.showToast(
                            'Sản phẩm không tồn tại',
                            'Sản phẩm này không còn tồn tại trong cửa hàng.',
                            'error'
                        );
                        // Also show native notification
                        this.showNativeNotification(
                            'Sản phẩm không tồn tại',
                            'Sản phẩm này không còn tồn tại trong cửa hàng.',
                            'error'
                        );
                        return false;
                    }
                }
            }
        }, true); // Sử dụng giai đoạn bắt giữ để chặn trước khi điều hướng.
    }
    
    /**
     * Thiết lập yêu cầu cấp quyền khi người dùng tương tác
    * Chrome và CocCoc yêu cầu người dùng tương tác trước khi hiển thị lời nhắc cấp quyền
     */
    setupPermissionRequestOnInteraction() {
        // Check if permission is already granted or denied
        if (Notification.permission !== 'default') {
            this.notificationPermission = Notification.permission;
            if (Notification.permission === 'denied') {
                this.nativeNotificationsEnabled = false;
            }
            return;
        }
        
        // Yêu cầu quyền khi người dùng nhấp chuột/tương tác lần đầu
// Điều này đảm bảo nó hoạt động trên Chrome, CocCoc và các trình duyệt nghiêm ngặt khác
        const requestPermissionOnClick = async () => {
            if (Notification.permission === 'default') {
                await this.requestNotificationPermission();
                // Xóa trình lắng nghe sau lần tương tác đầu tiên
                document.removeEventListener('click', requestPermissionOnClick);
                document.removeEventListener('touchstart', requestPermissionOnClick);
            } else {
                // Quyền đã được thiết lập, hãy xóa người nghe.
                document.removeEventListener('click', requestPermissionOnClick);
                document.removeEventListener('touchstart', requestPermissionOnClick);
            }
        };
        
        // Thêm trình lắng nghe cho thao tác nhấp chuột và chạm (hỗ trợ thiết bị di động)
        document.addEventListener('click', requestPermissionOnClick, { once: true });
        document.addEventListener('touchstart', requestPermissionOnClick, { once: true });
    }
    
    connect() {
        try {
            this.ws = new WebSocket(this.wsUrl);
            
            this.ws.onopen = (event) => {
                console.log('✅ WebSocket connected successfully');
                console.log('Role:', this.role, 'Channel:', this.channel, 'User ID:', this.userId);
                this.reconnectAttempts = 0;
                
                // Đăng ký ngay lập tức sau khi kết nối
                // Sử dụng độ trễ ngắn để đảm bảo kết nối đã sẵn sàng
                // Cũng đăng ký lại sau một độ trễ dài hơn như một phương án dự phòng
                setTimeout(() => {
                    console.log('🔄 Attempting to subscribe to channel:', this.channel);
                    this.subscribe();
                }, 100);
                
                // Phương án dự phòng: Đăng ký lại sau 500ms để đảm bảo quá trình diễn ra.
                setTimeout(() => {
                    if (this.ws && this.ws.readyState === WebSocket.OPEN && this.channel) {
                        console.log('🔄 Fallback: Re-subscribing to channel:', this.channel);
                        this.subscribe();
                    }
                }, 500);
                
                // Kiểm tra đăng ký định kỳ - đăng ký lại nếu không được xác nhận trong vòng 3 giây.
                if (this.subscriptionCheckInterval) {
                    clearInterval(this.subscriptionCheckInterval);
                }
                this.subscriptionCheckInterval = setInterval(() => {
                    if (this.ws && this.ws.readyState === WebSocket.OPEN && this.channel && !this.isSubscribed) {
                        console.log('🔄 Subscription not confirmed, resubscribing...');
                        this.subscribe();
                    }
                }, 3000);
                
                this.startHeartbeat();
                
                // Yêu cầu quyền nhận thông báo khi kết nối WebSocket (nếu chưa được cấp/từ chối)
                // Điều này tạo cơ hội tốt để chủ động yêu cầu quyền
                if (Notification.permission === 'default' && document.visibilityState === 'visible') {
                    // Request permission after a short delay to ensure page is fully loaded
                    setTimeout(() => {
                        this.requestNotificationPermission();
                    }, 1000);
                }
                
                if (this.onConnect) this.onConnect(event);
            };
            
            this.ws.onmessage = (event) => {
                console.log('📨 Received WebSocket message:', event.data);
                try {
                    const message = JSON.parse(event.data);
                    
                    // Xử lý xác nhận đăng ký
                    if (message.action === 'subscribed') {
                        console.log(`✅ Successfully subscribed to ${message.channel}`);
                        this.isSubscribed = true;
                        if (message.channel === this.channel) {
                            console.log('✅ Subscription confirmed for correct channel:', this.channel);
                        }
                        return;
                    }
                    
                    // Xử lý phản hồi pong
                    if (message.action === 'pong') {
                        return;
                    }
                    
                    // Handle error
                    if (message.action === 'error') {
                        console.error('WebSocket error:', message.message);
                        return;
                    }
                    
                    // Xử lý thông báo thời gian thực - ĐIỀU NÀY RẤT QUAN TRỌNG
                    if (message.type) {
                        console.log('🔔 Notification message received with type:', message.type);
                        console.log('🔔 Full message:', JSON.stringify(message, null, 2));
                        // Call handleNotification directly
                        this.handleNotification(message);
                        if (this.onMessage) this.onMessage(message);
                    } else if (message.action) {
                        // Handle other actions via handleMessage
                        this.handleMessage(event.data);
                    } else {
                        console.log('⚠️ Message received but no type or action field:', message);
                        // Try to show as notification anyway if it has title/message
                        if (message.title || message.message) {
                            console.log('⚠️ Attempting to show as notification...');
                            this.handleNotification(message);
                        }
                    }
                } catch (error) {
                    console.error('❌ Error parsing WebSocket message:', error);
                    // Try to show notification even if parsing fails
                    try {
                        const message = JSON.parse(event.data);
                        if (message.type || message.title || message.message) {
                            console.log('⚠️ Attempting to show notification despite error...');
                            this.showToastDirect(
                                message.title || 'Thông báo',
                                message.message || JSON.stringify(message),
                                message.type || 'info'
                            );
                            this.playNotificationSound();
                        }
                    } catch (e) {
                        console.error('❌ Could not parse message at all:', e);
                    }
                }
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                if (this.onError) this.onError(error);
            };
            
            this.ws.onclose = (event) => {
                console.log('WebSocket disconnected');
                this.stopHeartbeat();
                this.isSubscribed = false;
                if (this.subscriptionCheckInterval) {
                    clearInterval(this.subscriptionCheckInterval);
                    this.subscriptionCheckInterval = null;
                }
                if (this.onDisconnect) this.onDisconnect(event);
                this.attemptReconnect();
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.attemptReconnect();
        }
    }
    
    subscribe() {
        if (!this.ws) {
            console.error('❌ Cannot subscribe: WebSocket is null');
            return;
        }
        
        if (this.ws.readyState !== WebSocket.OPEN) {
            console.warn('⚠️ Cannot subscribe: WebSocket is not OPEN. State:', this.ws.readyState);
            // Retry after a short delay
            setTimeout(() => {
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    this.subscribe();
                }
            }, 100);
            return;
        }
        
        const subscribeMessage = {
            action: 'subscribe',
            channel: this.channel,
            role: this.role,
            user_id: this.userId
        };
        
        console.log('📤 Sending subscribe message:', subscribeMessage);
        
        try {
            this.ws.send(JSON.stringify(subscribeMessage));
            console.log('✅ Subscribe message sent successfully');
        } catch (error) {
            console.error('❌ Error sending subscribe message:', error);
        }
    }
    
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({ action: 'ping' }));
            }
        }, this.heartbeatInterval);
    }
    
    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }
    
    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }
    
    handleMessage(data) {
        try {
            const message = JSON.parse(data);
            
            // Handle pong response
            if (message.action === 'pong') {
                return;
            }
            
            // Handle subscription confirmation
            if (message.action === 'subscribed') {
                console.log(`✅ Successfully subscribed to ${message.channel}`);
                this.isSubscribed = true;
                // Verify we're actually subscribed by checking if channel matches
                if (message.channel === this.channel) {
                    console.log('✅ Subscription confirmed for correct channel:', this.channel);
                } else {
                    console.warn('⚠️ Subscription confirmed but channel mismatch. Expected:', this.channel, 'Got:', message.channel);
                }
                return;
            }
            
            // Handle error
            if (message.action === 'error') {
                console.error('WebSocket error:', message.message);
                return;
            }
            
            // Handle realtime notifications
            if (message.type) {
                console.log('🔔 Notification message received with type:', message.type);
                console.log('🔔 Full message:', JSON.stringify(message, null, 2));
                this.handleNotification(message);
                if (this.onMessage) this.onMessage(message);
            } else {
                console.log('⚠️ Message received but no type field:', message);
            }
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }
    
    handleNotification(message) {
        console.log('🔔 handleNotification called with:', message);
        
        const { type, title, message: msg, data, sound, timestamp } = message;
        
        // Create unique notification ID to prevent duplicates
        const notificationId = this.getNotificationId(message);
        
        // Check if we've already processed this notification (but be VERY lenient - only skip exact duplicates within 1 second)
        if (this.processedNotifications.has(notificationId)) {
            const lastProcessed = this.processedNotifications.get(notificationId);
            const timeSinceLastProcessed = Date.now() - lastProcessed;
            // Only skip if processed within last 1 second (very short window to prevent true duplicates)
            if (timeSinceLastProcessed < 1000) {
                console.log('⚠️ Duplicate notification ignored (processed', timeSinceLastProcessed, 'ms ago):', notificationId);
                return;
            }
        }
        
        // Mark as processed with timestamp
        this.processedNotifications.set(notificationId, Date.now());
        
        // Clean up old notification IDs (keep only last 50)
        if (this.processedNotifications.size > 50) {
            const now = Date.now();
            for (const [id, processedTime] of this.processedNotifications.entries()) {
                if (now - processedTime > 30000) { // Older than 30 seconds
                    this.processedNotifications.delete(id);
                }
            }
        }
        
        console.log('📨 Processing notification:', {
            type: type,
            title: title,
            message: msg,
            sound: sound,
            soundEnabled: this.soundEnabled,
            notificationId: notificationId
        });
        
        // CRITICAL: Show notifications IMMEDIATELY - use direct method first for reliability
        // Don't wait for anything, just show it
        try {
            console.log('🔔 Showing toast notification (direct method)...');
            this.showToastDirect(title || 'Thông báo', msg || '', type);
            console.log('✅ Toast notification displayed via direct method');
        } catch (e) {
            console.error('❌ Direct toast failed, trying regular method:', e);
            try {
                this.showToast(title || 'Thông báo', msg || '', type);
            } catch (e2) {
                console.error('❌ Regular toast also failed, using alert:', e2);
                alert(`${title || 'Thông báo'}: ${msg || ''}`);
            }
        }
        
        // Play sound IMMEDIATELY - try multiple methods
        try {
            const shouldPlaySound = sound !== false && (sound === 'notification' || sound === true || sound === undefined);
            if (shouldPlaySound) {
                console.log('🔔 Playing notification sound...');
                // Try primary method
                this.playNotificationSound();
                // Also try fallback immediately
                setTimeout(() => {
                    try {
                        this.playNotificationSoundFallback();
                    } catch (e) {
                        console.error('❌ Fallback sound failed:', e);
                    }
                }, 10);
            }
        } catch (e) {
            console.error('❌ Error playing sound:', e);
            try {
                this.playNotificationSoundFallback();
            } catch (e2) {
                console.error('❌ All sound methods failed:', e2);
            }
        }
        
        // Show native notification (optional, works if permission granted)
        try {
            this.showNativeNotification(title || 'Thông báo', msg || '', type);
        } catch (e) {
            // Native notification is optional, don't fail if it errors
        }
        
        // Handle specific notification types
        switch (type) {
            case 'new_order':
                console.log('🆕 Handling new order:', data);
                this.handleNewOrder(data);
                break;
            case 'order_status_update':
                console.log('📦 Handling order status update:', data);
                this.handleOrderStatusUpdate(data);
                break;
            case 'new_product':
                console.log('🆕 Handling new product:', data);
                this.handleNewProduct(data);
                break;
            case 'product_update':
            case 'product_delete':
            case 'delete':
                console.log('🔄 Handling product update:', type, data);
                // Extract update_type from data if available
                const updateType = data.update_type || type.replace('product_', '');
                this.handleProductUpdate(data, updateType);
                break;
            case 'new_user':
                console.log('👤 Handling new user:', data);
                this.handleNewUser(data);
                break;
            case 'chat_message':
                console.log('💬 Handling chat message:', data);
                this.handleChatMessage(data);
                break;
            case 'order_deleted':
                console.log('🗑️ Handling order deletion:', data);
                this.handleOrderDeleted(data);
                break;
            case 'order_confirmed':
                console.log('✅ Handling order confirmation:', data);
                this.showOrderConfirmationNotification(data.order_id, data.status_text || 'Đã được xác nhận');
                this.updateUserOrderStatus(data.order_id, data.status || 1, data.status_text || 'Đã duyệt');
                break;
            case 'order_approved':
                console.log('📦 Handling order approved notification:', data);
                // This is for shippers - order is ready to be accepted
                break;
            case 'order_accepted_by_shipper':
                console.log('🚚 Handling order accepted by shipper:', data);
                // Update order status with shipper name
                if (data.shipper_name) {
                    this.updateUserOrderStatus(data.order_id, data.status || 2, `${data.status_text} bởi ${data.shipper_name}`);
                }
                break;
            case 'order_delivered':
                console.log('✅ Handling order delivered:', data);
                // Update order status with shipper name
                if (data.shipper_name) {
                    this.updateUserOrderStatus(data.order_id, data.status || 3, `${data.status_text} bởi ${data.shipper_name}`);
                }
                break;
            case 'order_approved_by_staff':
                console.log('👤 Handling order approved by staff:', data);
                // Staff notification - order was approved by another staff member
                break;
            case 'order_paid':
                console.log('💰 Handling order paid notification:', data);
                // Order payment confirmed
                break;
            case 'order_paid_by_shipper':
                console.log('💰 Handling order paid by shipper:', data);
                // Shipper confirmed payment
                break;
            case 'product_not_found':
                console.log('⚠️ Product not found:', data);
                // Track as deleted product
                if (data.product_id) {
                    this.deletedProducts.add(parseInt(data.product_id));
                }
                break;
            case 'deposit_request':
                console.log('💰 Handling deposit request:', data);
                // Refresh deposit list if function exists
                if (typeof window.refreshDepositList === 'function') {
                    window.refreshDepositList();
                }
                break;
            case 'customer_info_updated':
            case 'customer_self_update':
                console.log('👤 Handling customer info update:', data);
                // Refresh customer list if function exists
                if (typeof window.refreshCustomerList === 'function') {
                    window.refreshCustomerList();
                }
                break;
            default:
                console.log('⚠️ Unknown notification type:', type);
                // Try to handle as generic notification
                if (data && data.order_id) {
                    // Might be an order-related update
                    this.handleOrderStatusUpdate(data);
                } else if (data && data.product_id) {
                    // Might be a product-related update
                    this.handleProductUpdate(data, type);
                }
        }
    }
    
    handleNewOrder(data) {
        // Add new order to admin/staff dashboard table
        this.addNewOrderToTable(data);
        
        // Highlight new order in admin/staff dashboard
        if (typeof window.highlightNewOrder === 'function') {
            window.highlightNewOrder(data.order_id);
        }
        
        // Refresh order list if function exists
        if (typeof window.refreshOrderList === 'function') {
            window.refreshOrderList();
        }
    }
    
    addNewOrderToTable(data) {
        // Find the orders table - try multiple selectors
        const table = document.querySelector('#dataTable tbody') || 
                     document.querySelector('table tbody') ||
                     document.querySelector('.table-responsive tbody') ||
                     document.querySelector('tbody');
        
        if (!table) {
            console.log('Order table not found, will refresh page');
            // If table not found, trigger page refresh after showing notification
            setTimeout(() => {
                if (confirm('Đơn hàng mới đã được tạo! Tải lại trang để xem?')) {
                    window.location.reload();
                }
            }, 2000);
            return;
        }
        
        // Get current row count for numbering
        const currentRowCount = table.querySelectorAll('tr').length;
        
        // Create new row for the order
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', data.order_id);
        row.style.backgroundColor = '#fff3cd';
        row.style.animation = 'pulse 2s ease-in-out';
        row.style.transition = 'background-color 0.3s';
        
        // Format order data
        const formattedTotal = typeof data.total_amount !== 'undefined' 
            ? new Intl.NumberFormat('vi-VN').format(data.total_amount) 
            : '0';
        
        const customerName = this.escapeHtml(data.customer_name || 'Khách hàng');
        
        // Create row HTML matching the table structure
        row.innerHTML = `
            <td>${currentRowCount + 1}</td>
            <td class="text-center">
                <a href="Approve_Oders.php?oder_id=${data.order_id}&approve=all" class="btn btn-danger">Cần Duyệt</a>
            </td>
            <td class="text-center">
                <a href="View_Oder_Detail.php?oder_id=${data.order_id}&view=all" class="btn btn-primary">Xem Chi Tiết</a>
            </td>
            <td class="text-center">
                <a href="Delete_Oder.php?del_oder_id=${data.order_id}&view=all" class="btn btn-danger">Hủy</a>
            </td>
            <td class="text-center">
                <a href="Mark_Paid.php?oder_id=${data.order_id}" class="btn btn-outline-success">Đánh dấu đã thanh toán</a>
            </td>
            <td>${customerName}</td>
            <td>-</td>
            <td>-</td>
            <td>${formattedTotal}đ</td>
            <td>-</td>
        `;
        
        // Insert at the top of the table
        table.insertBefore(row, table.firstChild);
        
        // Scroll to the new order if possible
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Remove highlight after 5 seconds
        setTimeout(() => {
            row.style.backgroundColor = '';
            row.style.animation = '';
        }, 5000);
        
        // Show success notification
        this.showToast('Đơn hàng mới', `Đơn hàng #${data.order_id} từ ${customerName}`, 'success');
    }
    
    handleOrderStatusUpdate(data) {
        const orderId = data.order_id;
        const status = data.status;
        const statusText = data.status_text || '';
        
        // Special handling for order confirmation (status = 1)
        if (status == 1 || statusText.includes('duyệt') || statusText.includes('Duyệt') || statusText.includes('xác nhận')) {
            // Show prominent notification to user
            this.showOrderConfirmationNotification(orderId, statusText);
            
            // Update order status in user's order tracking page if exists
            this.updateUserOrderStatus(orderId, status, statusText);
        } else {
            // Regular status update
            this.updateUserOrderStatus(orderId, status, statusText);
        }
        
        // Update order status in admin/staff dashboard
        if (typeof window.updateOrderStatus === 'function') {
            window.updateOrderStatus(orderId, status, statusText);
        }
        
        // Refresh order list if function exists
        if (typeof window.refreshOrderList === 'function') {
            window.refreshOrderList();
        }
    }
    
    showOrderConfirmationNotification(orderId, statusText) {
        // Show a prominent, persistent notification for order confirmation
        const notification = document.createElement('div');
        notification.id = `order-confirmation-${orderId}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10001;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            min-width: 400px;
            max-width: 600px;
            animation: slideDown 0.5s ease-out;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 40px;">✅</div>
                <div style="flex: 1;">
                    <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                        Đơn hàng đã được xác nhận!
                    </div>
                    <div style="font-size: 14px; opacity: 0.95;">
                        Đơn hàng #${orderId} của bạn đã được ${statusText || 'xác nhận'}
                    </div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: rgba(255,255,255,0.2); border: none; color: white; 
                               width: 30px; height: 30px; border-radius: 50%; cursor: pointer; 
                               font-size: 20px; line-height: 1;">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideUp 0.5s ease-out';
                setTimeout(() => notification.remove(), 500);
            }
        }, 10000);
        
        // Play special confirmation sound
        this.playConfirmationSound();
    }
    
    updateUserOrderStatus(orderId, status, statusText) {
        // Update order status in user's order tracking page
        const orderElements = document.querySelectorAll(`[data-order-id="${orderId}"], [href*="order_id=${orderId}"], [href*="oder_id=${orderId}"]`);
        
        orderElements.forEach(el => {
            const statusElement = el.closest('.order-item, .order-card, tr, .card')?.querySelector('.order-status, .status, [class*="status"]');
            if (statusElement) {
                statusElement.textContent = statusText;
                statusElement.style.color = status == 1 ? '#28a745' : status == 2 ? '#ffc107' : status == 3 ? '#17a2b8' : '#6c757d';
                
                // Highlight the update
                statusElement.style.transition = 'all 0.3s';
                statusElement.style.fontWeight = 'bold';
                statusElement.style.animation = 'pulse 2s ease-in-out';
                setTimeout(() => {
                    statusElement.style.fontWeight = '';
                    statusElement.style.animation = '';
                }, 2000);
            }
        });
    }
    
    playConfirmationSound() {
        // Play a more pleasant confirmation sound (two-tone)
        if (this.audioContext) {
            try {
                // First tone
                const osc1 = this.audioContext.createOscillator();
                const gain1 = this.audioContext.createGain();
                osc1.connect(gain1);
                gain1.connect(this.audioContext.destination);
                osc1.frequency.value = 523.25; // C5
                osc1.type = 'sine';
                gain1.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.2);
                osc1.start(this.audioContext.currentTime);
                osc1.stop(this.audioContext.currentTime + 0.2);
                
                // Second tone (after short delay)
                setTimeout(() => {
                    const osc2 = this.audioContext.createOscillator();
                    const gain2 = this.audioContext.createGain();
                    osc2.connect(gain2);
                    gain2.connect(this.audioContext.destination);
                    osc2.frequency.value = 659.25; // E5
                    osc2.type = 'sine';
                    gain2.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
                    osc2.start(this.audioContext.currentTime);
                    osc2.stop(this.audioContext.currentTime + 0.3);
                }, 200);
            } catch (e) {
                console.log('Could not play confirmation sound:', e);
                this.playNotificationSound(); // Fallback to regular sound
            }
        } else {
            this.playNotificationSound(); // Fallback
        }
    }
    
    handleOrderDeleted(data) {
        const orderId = data.order_id;
        const errorMessage = data.error_message || 'Sản phẩm không còn khả dụng, vui lòng tải lại trang.';
        console.log('Removing order from UI:', orderId);
        
        // Show error message to user if this is a user page
        const isUserPage = window.location.pathname.includes('/View/') || 
                          window.location.pathname.includes('Order_Tracking') ||
                          window.location.pathname.includes('Personal');
        
        if (isUserPage) {
            // Show prominent error message
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 10002;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                max-width: 500px;
                text-align: center;
            `;
            errorDiv.innerHTML = `
                <div style="font-size: 48px; color: #dc3545; margin-bottom: 20px;">⚠️</div>
                <h3 style="color: #dc3545; margin-bottom: 15px;">Đơn hàng đã bị hủy</h3>
                <p style="color: #666; margin-bottom: 20px;">${this.escapeHtml(errorMessage)}</p>
                <button onclick="window.location.reload()" style="
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                ">Tải lại trang</button>
            `;
            document.body.appendChild(errorDiv);
            
            // Auto-reload after 5 seconds if user doesn't click
            setTimeout(() => {
                if (errorDiv.parentElement) {
                    window.location.reload();
                }
            }, 5000);
        }
        
        // Find and remove order from table (for admin/staff pages)
        const table = document.querySelector('#dataTable tbody') || 
                     document.querySelector('table tbody') ||
                     document.querySelector('.table-responsive tbody') ||
                     document.querySelector('tbody');
        
        if (table) {
            // Find row by order ID
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                // Check if row contains this order ID
                const viewLink = row.querySelector('a[href*="View_Oder_Detail"]');
                const approveLink = row.querySelector('a[href*="Approve_Oders"]');
                const deleteLink = row.querySelector('a[href*="Delete_Oder"]');
                
                let found = false;
                if (viewLink && viewLink.href.includes(`oder_id=${orderId}`)) found = true;
                if (approveLink && approveLink.href.includes(`oder_id=${orderId}`)) found = true;
                if (deleteLink && deleteLink.href.includes(`del_oder_id=${orderId}`)) found = true;
                
                if (found) {
                    // Fade out and remove
                    row.style.transition = 'opacity 0.5s ease-out';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Update row numbers
                        const remainingRows = table.querySelectorAll('tr');
                        remainingRows.forEach((r, index) => {
                            const firstCell = r.querySelector('td:first-child');
                            if (firstCell) {
                                firstCell.textContent = index + 1;
                            }
                        });
                    }, 500);
                }
            });
        }
        
        // Also try custom handler if exists
        if (typeof window.removeOrder === 'function') {
            window.removeOrder(orderId);
        }
        
        // Show toast notification
        const toastMessage = isUserPage ? errorMessage : `Đơn hàng #${orderId} đã được xóa khỏi danh sách`;
        this.showToast('Đơn hàng đã hủy', toastMessage, 'error');
    }
    
    handleNewProduct(data) {
        // Fetch product details and add to UI
        this.fetchAndAddProduct(data.product_id);
        
        // Also try custom handler if exists
        if (typeof window.addNewProduct === 'function') {
            window.addNewProduct(data);
        }
    }
    
    async fetchAndAddProduct(productId) {
        try {
            // Try multiple possible paths for the API endpoint
            const possiblePaths = [
                '../Processing/Get_Product_Detail.php',
                '../../Processing/Get_Product_Detail.php',
                '/TTTT/WebBanHangMyPham/src/Processing/Get_Product_Detail.php',
                'src/Processing/Get_Product_Detail.php'
            ];
            
            let product = null;
            for (const path of possiblePaths) {
                try {
                    const response = await fetch(`${path}?product_id=${productId}`);
                    if (response.ok) {
                        const data = await response.json();
                        if (!data.error) {
                            product = data;
                            break;
                        }
                    }
                } catch (e) {
                    // Try next path
                    continue;
                }
            }
            
            if (product) {
                this.addProductToDOM(product);
            } else {
                throw new Error('Could not fetch product');
            }
        } catch (error) {
            console.log('Could not fetch product details, will refresh page:', error);
            // Fallback: refresh the page after a short delay
            setTimeout(() => {
                this.showToast('Sản phẩm mới', 'Sản phẩm mới đã được thêm! Đang tải lại trang...', 'success');
                setTimeout(() => window.location.reload(), 1000);
            }, 1000);
        }
    }
    
    addProductToDOM(product) {
        // Find product containers (sliders and product lists)
        const containers = [];
        
        // Check for Slick slider containers
        const newProductSlider = document.querySelector('.slider_new_product');
        const topProductSlider = document.querySelector('.slider');
        
        // Check for product list page (View_Product.php)
        const productListRow = document.querySelector('.container .row');
        
        if (newProductSlider) containers.push({ el: newProductSlider, type: 'slick', name: 'new_product' });
        if (topProductSlider) containers.push({ el: topProductSlider, type: 'slick', name: 'top_product' });
        if (productListRow && productListRow.querySelector('.p-2')) {
            containers.push({ el: productListRow, type: 'grid', name: 'product_list' });
        }
        
        if (containers.length === 0) {
            // If no container found, trigger page refresh
            this.showToast('Sản phẩm mới', 'Sản phẩm mới đã được thêm! Đang tải lại trang...', 'success');
            setTimeout(() => window.location.reload(), 1000);
            return;
        }
        
        // Create product card HTML
        const productCard = this.createProductCard(product);
        
        // Add to all available containers
        containers.forEach(containerInfo => {
            const container = containerInfo.el;
            const wrapper = document.createElement('div');
            
            if (containerInfo.type === 'slick') {
                // For Slick sliders, use the same structure as existing items
                wrapper.className = 'p-2 mb-3';
            } else {
                // For grid layout (View_Product.php), use col classes
                wrapper.className = 'p-2 col-xl-3 col-md-4 col-sm-6';
            }
            
            wrapper.setAttribute('data-product-id', product.IDSanPham);
            wrapper.innerHTML = productCard;
            wrapper.style.animation = 'fadeIn 0.5s ease-in';
            wrapper.style.opacity = '0';
            
            // Insert at the beginning
            container.insertBefore(wrapper, container.firstChild);
            
            // Trigger fade-in
            setTimeout(() => {
                wrapper.style.opacity = '1';
            }, 10);
            
            // If it's a Slick slider, refresh it
            if (containerInfo.type === 'slick' && typeof $ !== 'undefined' && $(container).hasClass('slick-initialized')) {
                try {
                    $(container).slick('slickAdd', wrapper, 0);
                } catch (e) {
                    // If slickAdd fails, just refresh the slider
                    $(container).slick('refresh');
                }
            }
        });
        
        this.showToast('Sản phẩm mới', `Sản phẩm "${product.TenSanPham}" đã được thêm!`, 'success');
    }
    
    createProductCard(product) {
        const imagePath = product.AnhSanPham ? `../../../AdminManage/src/Products/${product.AnhSanPham}` : '';
        return `
            <div class="bg-white p-3 border-0 shadow-sm rounded-4 d-flex flex-column" style="height: 100%; border: 2px solid #28a745 !important;">
                <div class="card-body p-0 mb-3" style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                    <img src="${imagePath}" alt="${this.escapeHtml(product.TenSanPham)}" class="img-fluid rounded-4" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                </div>
                <div class="card-body mt-auto p-0" style="min-height: 50px; flex-grow: 1; display: flex; flex-direction: column;">
                    <a style="text-decoration: none;" href="Detail_Product.php?product_id=${product.IDSanPham}">
                        <h5 class="card-title text-black" style="font-size: 0.95rem; line-height: 1.3; margin-bottom: 10px;">${this.escapeHtml(product.TenSanPham)}</h5>
                    </a>
                </div>
                <div class="row mt-auto">
                    <div class="col-6 p-2">
                        <button class="btn btn-light mt-3 w-100" style="font-size: 0.85rem;">${product.GiaTien}đ</button>
                    </div>
                    <div class="col-3 p-2">
                        <button onclick="addToCart(${product.IDSanPham})" class="btn btn-warning mt-3 w-100">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </button>
                    </div>
                    <div class="col-3 p-2">
                        <button onclick="addToLike(${product.IDSanPham})" class="btn btn-danger mt-3 w-100">
                            <i class="fa-regular fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    handleProductUpdate(data, type) {
        const productId = data.product_id || data.IDSanPham;
        
        if (type === 'product_delete' || type === 'delete') {
            // Track deleted product
            if (productId) {
                this.deletedProducts.add(parseInt(productId));
                console.log('🗑️ Product deleted, tracking ID:', productId);
            }
            
            // Find and remove product from all possible locations
            let removed = false;
            
            // Method 1: Find by data-product-id attribute
            const elementsById = document.querySelectorAll(`[data-product-id="${productId}"]`);
            elementsById.forEach(el => {
                el.style.animation = 'fadeOut 0.5s ease-out';
                el.style.opacity = '0';
                setTimeout(() => {
                    el.remove();
                    removed = true;
                }, 500);
            });
            
            // Method 2: Find by onclick attribute in buttons
            document.querySelectorAll('[onclick*="addToCart"], [onclick*="addToLike"]').forEach(btn => {
                const onclick = btn.getAttribute('onclick') || '';
                if (onclick.includes(`(${productId})`) || onclick.includes(`('${productId}')`) || onclick.includes(`("${productId}")`)) {
                    const card = btn.closest('.p-2, .col-xl-3, .col-md-4, .col-sm-6');
                    if (card && !card.hasAttribute('data-removing')) {
                        card.setAttribute('data-removing', 'true');
                        card.style.animation = 'fadeOut 0.5s ease-out';
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            removed = true;
                        }, 500);
                    }
                }
            });
            
            // Method 3: Find by product link href
            document.querySelectorAll(`a[href*="product_id=${productId}"], a[href*="product_id=${productId}"]`).forEach(link => {
                const card = link.closest('.p-2, .col-xl-3, .col-md-4, .col-sm-6');
                if (card && !card.hasAttribute('data-removing')) {
                    card.setAttribute('data-removing', 'true');
                    card.style.animation = 'fadeOut 0.5s ease-out';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        removed = true;
                    }, 500);
                }
            });
            
            // Refresh Slick sliders if they exist
            if (typeof $ !== 'undefined') {
                $('.slider_new_product.slick-initialized, .slider.slick-initialized').each(function() {
                    try {
                        $(this).slick('refresh');
                    } catch (e) {
                        console.log('Could not refresh slider:', e);
                    }
                });
            }
            
            if (removed) {
                this.showToast('Sản phẩm đã xóa', `Sản phẩm "${data.product_name || 'N/A'}" đã được xóa khỏi danh sách`, 'info');
            }
        } else {
            // Update product price/stock in UI
            const productElements = document.querySelectorAll(`[data-product-id="${productId}"]`);
            
            // Also find by onclick/href
            const allProductCards = [];
            document.querySelectorAll('[onclick*="addToCart"], a[href*="product_id="]').forEach(el => {
                const onclick = el.getAttribute('onclick') || '';
                const href = el.getAttribute('href') || '';
                if (onclick.includes(`(${productId})`) || href.includes(`product_id=${productId}`)) {
                    const card = el.closest('.p-2, .col-xl-3, .col-md-4, .col-sm-6');
                    if (card) allProductCards.push(card);
                }
            });
            
            const allElements = [...productElements, ...allProductCards];
            
            if (allElements.length > 0) {
                allElements.forEach(el => {
                    // Highlight the updated product
                    el.style.border = '2px solid #ffc107';
                    el.style.transition = 'all 0.3s';
                    el.style.boxShadow = '0 0 10px rgba(255, 193, 7, 0.5)';
                    setTimeout(() => {
                        el.style.border = '';
                        el.style.boxShadow = '';
                    }, 3000);
                });
                
                this.showToast('Sản phẩm đã cập nhật', `Sản phẩm "${data.product_name || 'N/A'}" đã được cập nhật`, 'info');
            } else {
                // Product not found in current view, suggest refresh
                setTimeout(() => {
                    if (confirm('Sản phẩm đã được cập nhật! Tải lại trang để xem thay đổi?')) {
                        window.location.reload();
                    }
                }, 1000);
            }
        }
    }
    
    handleNewUser(data) {
        // Refresh user list in admin dashboard
        if (typeof window.refreshUserList === 'function') {
            window.refreshUserList();
        }
    }
    
    handleChatMessage(data) {
        // Show chat notification
        if (typeof window.showChatNotification === 'function') {
            window.showChatNotification(data);
        }
    }
    
    createNotificationContainer() {
        if (document.getElementById('realtime-notifications')) {
            this.notificationContainer = document.getElementById('realtime-notifications');
            console.log('📦 Using existing notification container');
            return;
        }
        
        console.log('📦 Creating new notification container...');
        const container = document.createElement('div');
        container.id = 'realtime-notifications';
        container.style.cssText = `
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 999999 !important;
            max-width: 400px !important;
            pointer-events: auto !important;
            visibility: visible !important;
            display: block !important;
        `;
        
        // Ensure body exists
        if (!document.body) {
            console.error('❌ document.body does not exist yet!');
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    document.body.appendChild(container);
                    this.notificationContainer = container;
                    console.log('✅ Notification container created after DOM ready');
                });
                return;
            }
        }
        
        document.body.appendChild(container);
        this.notificationContainer = container;
        console.log('✅ Notification container created and appended to body');
        console.log('📦 Container element:', container);
        console.log('📦 Container parent:', container.parentElement);
    }
    
    // Direct toast method that bypasses all checks - MOST RELIABLE METHOD
    showToastDirect(title, message, type = 'info') {
        console.log('🎨 showToastDirect called (bypass method)', { title, message, type });
        
        // Ensure body exists
        if (!document.body) {
            console.error('❌ document.body does not exist! Waiting...');
            setTimeout(() => this.showToastDirect(title, message, type), 50);
            return;
        }
        
        // Get or create container
        let container = document.getElementById('realtime-notifications');
        if (!container) {
            console.log('📦 Creating notification container (direct method)...');
            container = document.createElement('div');
            container.id = 'realtime-notifications';
            container.style.cssText = `
                position: fixed !important;
                top: 20px !important;
                right: 20px !important;
                z-index: 999999 !important;
                max-width: 400px !important;
                pointer-events: auto !important;
                visibility: visible !important;
                display: block !important;
            `;
            document.body.appendChild(container);
            console.log('✅ Container created and appended');
        }
        
        // Create toast element
        const toast = document.createElement('div');
        const colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        const color = colors[type] || colors['info'];
        
        // Escape HTML to prevent XSS
        const safeTitle = String(title || 'Thông báo').replace(/[<>]/g, '');
        const safeMessage = String(message || '').replace(/[<>]/g, '');
        
        toast.style.cssText = `
            background: white !important;
            border-left: 4px solid ${color} !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
            border-radius: 4px !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1000000 !important;
            min-width: 300px !important;
            max-width: 400px !important;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 4px; color: #333; font-size: 16px; font-weight: bold;">${safeTitle}</strong>
                    <div style="color: #666; font-size: 14px; word-wrap: break-word;">${safeMessage}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; margin-left: 12px; padding: 0; line-height: 1; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
        `;
        
        // Append to container
        try {
            container.appendChild(toast);
            console.log('✅ Toast appended to container');
            
            // Verify it's in DOM
            if (!document.body.contains(toast)) {
                console.error('❌ Toast not in DOM! Appending directly to body...');
                toast.style.position = 'fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                document.body.appendChild(toast);
            }
            
            // Force visibility
            toast.style.display = 'block';
            toast.style.visibility = 'visible';
            toast.style.opacity = '1';
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toast && toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
            
            console.log('✅ Direct toast created and visible');
        } catch (e) {
            console.error('❌ Error appending toast:', e);
            // Last resort: alert
            alert(`${safeTitle}: ${safeMessage}`);
        }
    }
    
    showToast(title, message, type = 'info') {
        console.log('🎨 showToast called:', { title, message, type });
        
        // Ensure document.body exists
        if (!document.body) {
            console.error('❌ document.body does not exist! Using direct method...');
            this.showToastDirect(title, message, type);
            return;
        }
        
        // Always ensure container exists
        if (!this.notificationContainer) {
            console.log('📦 Creating notification container...');
            this.createNotificationContainer();
        }
        
        // If still no container, use direct method
        if (!this.notificationContainer) {
            console.error('❌ Container creation failed, using direct method...');
            this.showToastDirect(title, message, type);
            return;
        }
        
        console.log('📦 Notification container exists:', this.notificationContainer);
        
        const toast = document.createElement('div');
        toast.className = 'realtime-toast';
        const color = this.getColorForType(type);
        toast.style.cssText = `
            background: white !important;
            border-left: 4px solid ${color} !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1000000 !important;
            min-width: 300px !important;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 4px; color: #333;">${this.escapeHtml(title)}</strong>
                    <div style="color: #666; font-size: 14px;">${this.escapeHtml(message)}</div>
                </div>
                <button class="toast-close" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; margin-left: 12px;">&times;</button>
            </div>
        `;
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.removeToast(toast);
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            this.removeToast(toast);
        }, 5000);
        
        console.log('📦 Appending toast to container...');
        try {
            this.notificationContainer.appendChild(toast);
            console.log('✅ Toast appended to container. Container children:', this.notificationContainer.children.length);
            
            // Force visibility - multiple methods
            toast.style.display = 'block';
            toast.style.visibility = 'visible';
            toast.style.opacity = '1';
            toast.style.position = 'relative';
            toast.style.zIndex = '1000000';
            
            // Verify it's actually in the DOM
            if (!document.body.contains(toast)) {
                console.error('❌ Toast was not appended to DOM! Trying direct append...');
                document.body.appendChild(toast);
                toast.style.position = 'fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.maxWidth = '400px';
            }
            
            // Log final state
            console.log('✅ Toast created. Visible:', window.getComputedStyle(toast).display);
            console.log('✅ Toast in DOM:', document.body.contains(toast));
            console.log('✅ Toast parent:', toast.parentElement);
        } catch (e) {
            console.error('❌ Error appending toast:', e);
            // Last resort: append directly to body
            try {
                toast.style.position = 'fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.maxWidth = '400px';
                document.body.appendChild(toast);
                console.log('✅ Toast appended directly to body as fallback');
            } catch (e2) {
                console.error('❌ Complete failure to show toast:', e2);
                alert(`${title}: ${message}`);
            }
        }
        
        // Add animation
        if (!document.getElementById('realtime-toast-styles')) {
            const style = document.createElement('style');
            style.id = 'realtime-toast-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
                @keyframes fadeOut {
                    from {
                        opacity: 1;
                        transform: scale(1);
                    }
                    to {
                        opacity: 0;
                        transform: scale(0.9);
                    }
                }
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateX(-50%) translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                }
                @keyframes slideUp {
                    from {
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(-50%) translateY(-20px);
                    }
                }
                .realtime-toast {
                    transition: all 0.3s ease-out;
                }
                .realtime-toast:hover {
                    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    removeToast(toast) {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
    
    getColorForType(type) {
        const colors = {
            'new_order': '#007bff',
            'order_status_update': '#28a745',
            'new_product': '#17a2b8',
            'product_update': '#ffc107',
            'product_delete': '#dc3545',
            'new_user': '#6f42c1',
            'chat_message': '#20c997',
            'info': '#17a2b8',
            'success': '#28a745',
            'warning': '#ffc107',
            'error': '#dc3545'
        };
        return colors[type] || colors['info'];
    }
    
    getNotificationId(message) {
        // Create unique ID based on type, order_id, message content, AND timestamp
        // Include timestamp (rounded to second) to allow same content at different times
        const { type, data, title, message: msg, timestamp } = message;
        const orderId = data?.order_id || null;
        const productId = data?.product_id || null;
        const msgPreview = msg ? msg.substring(0, 50) : '';
        const titleText = title || '';
        
        // Get timestamp and round to nearest second
        let timeStamp = Date.now();
        if (timestamp) {
            try {
                timeStamp = new Date(timestamp).getTime();
            } catch (e) {
                // Use current time if invalid
            }
        }
        const roundedTimestamp = Math.floor(timeStamp / 1000); // Round to second
        
        // Create a unique identifier based on content AND timestamp (rounded)
        return `${type}_${orderId || productId || 'no_id'}_${titleText}_${msgPreview}_${roundedTimestamp}`;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    loadNotificationSound() {
        // Create audio context for notification sound (beep/ting)
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                this.audioContext = new AudioContextClass();
                console.log('✅ AudioContext created successfully');
            } else {
                console.warn('⚠️ AudioContext not available in this browser');
                this.audioContext = null;
            }
        } catch (e) {
            console.error('❌ Failed to create AudioContext:', e);
            this.audioContext = null;
        }
    }
    
    /**
     * Request notification permission from the browser
     * Works across Chrome, Edge, CocCoc, Firefox, Safari, etc.
     */
    async requestNotificationPermission() {
        // Check if Notification API is supported
        if (!('Notification' in window)) {
            console.log('Browser does not support notifications');
            this.nativeNotificationsEnabled = false;
            return;
        }
        
        // Check current permission status
        this.notificationPermission = Notification.permission;
        
        // If permission is already granted or denied, return
        if (this.notificationPermission === 'granted') {
            console.log('✅ Notification permission already granted');
            return;
        }
        
        if (this.notificationPermission === 'denied') {
            console.log('⚠️ Notification permission denied by user');
            this.nativeNotificationsEnabled = false;
            return;
        }
        
        // Request permission - this works in modern browsers even without direct user click
        // if the page is visible and has focus (which it should when WebSocket connects)
        try {
            const permission = await Notification.requestPermission();
            this.notificationPermission = permission;
            
            if (permission === 'granted') {
                console.log('✅ Notification permission granted by user');
            } else if (permission === 'denied') {
                console.log('⚠️ Notification permission denied by user');
                this.nativeNotificationsEnabled = false;
            } else {
                console.log('📢 Notification permission is still default');
            }
        } catch (error) {
            console.log('Error requesting notification permission:', error);
            // Some browsers may not allow permission request without direct user interaction
            // In that case, we'll request it when the first notification arrives
        }
    }
    
    /**
     * Show native browser notification
     * Works across all modern browsers (Chrome, Edge, CocCoc, Firefox, Safari)
     */
    showNativeNotification(title, message, type = 'info') {
        // Check if native notifications are enabled
        if (!this.nativeNotificationsEnabled) {
            return;
        }
        
        // Check if Notification API is supported
        if (!('Notification' in window)) {
            console.log('Browser does not support native notifications');
            return;
        }
        
        // Check permission status
        if (Notification.permission === 'denied') {
            console.log('Notification permission denied');
            this.nativeNotificationsEnabled = false;
            return;
        }
        
        // If permission is default, request it (but only if page is visible)
        if (Notification.permission === 'default') {
            // Request permission - this must be triggered by user interaction
            // We'll try to request it, but if it fails, we'll just show toast
            if (document.visibilityState === 'visible') {
                Notification.requestPermission().then(permission => {
                    this.notificationPermission = permission;
                    if (permission === 'granted') {
                        // Retry showing notification after permission is granted
                        this.createNativeNotification(title, message, type);
                    } else {
                        this.nativeNotificationsEnabled = false;
                    }
                }).catch(err => {
                    console.log('Error requesting notification permission:', err);
                    this.nativeNotificationsEnabled = false;
                });
            }
            return;
        }
        
        // Permission is granted, show notification
        if (Notification.permission === 'granted') {
            this.createNativeNotification(title, message, type);
        }
    }
    
    /**
     * Create and display a native browser notification
     */
    createNativeNotification(title, message, type = 'info') {
        try {
            // Create notification options
            const options = {
                body: message,
                icon: this.getNotificationIcon(type),
                badge: this.getNotificationIcon(type),
                tag: `notification-${type}-${Date.now()}`, // Unique tag to prevent duplicates
                requireInteraction: false, // Auto-close after a few seconds
                silent: false, // Play system sound
                timestamp: Date.now()
            };
            
            // Create and show notification
            const notification = new Notification(title, options);
            
            // Auto-close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);
            
            // Handle click on notification
            notification.onclick = function(event) {
                event.preventDefault();
                window.focus(); // Focus the window
                notification.close();
            };
            
            // Handle errors
            notification.onerror = function(error) {
                console.error('Notification error:', error);
            };
            
            console.log('✅ Native notification shown:', title);
        } catch (error) {
            console.error('Error showing native notification:', error);
            // Fallback to toast only if native notification fails
        }
    }
    
    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        // You can customize these icons later
        // For now, return a data URI or use a default icon
        const iconMap = {
            'new_order': 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiMwMDdiZmYiLz4KPHBhdGggZD0iTTMyIDIwTDIwIDMyTDMyIDQ0TDQ0IDMyTDM2IDIwWiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+',
            'order_status_update': 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiMyOGE3NDUiLz4KPHBhdGggZD0iTTI2IDMyTDMwIDM2TDM4IDI4IiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjQiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4=',
            'success': 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiMyOGE3NDUiLz4KPHBhdGggZD0iTTI2IDMyTDMwIDM2TDM4IDI4IiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjQiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4=',
            'error': 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiNkYzM1NDUiLz4KPHBhdGggZD0iTTIwIDIwTDQ0IDQ0TTQ0IDIwTDIwIDQ0IiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjQiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4=',
            'warning': 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiNmZmMwMDciLz4KPHBhdGggZD0iTTMyIDIwVjQwTTMyIDQ0SDMyIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjQiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4=',
            'info': 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiMxN2EyYjgiLz4KPHBhdGggZD0iTTMyIDIwVjI4TTMyIDQwSDMyIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjQiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4='
        };
        
        return iconMap[type] || iconMap['info'];
    }
    
    playNotificationSound() {
        console.log('🔊 playNotificationSound called, audioContext:', this.audioContext);
        
        if (this.audioContext) {
            try {
                // Create a simple beep sound (ting)
                const oscillator = this.audioContext.createOscillator();
                const gainNode = this.audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(this.audioContext.destination);
                
                oscillator.frequency.value = 800; // Higher pitch for "ting"
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
                
                oscillator.start(this.audioContext.currentTime);
                oscillator.stop(this.audioContext.currentTime + 0.3);
                console.log('✅ Audio oscillator sound played');
            } catch (e) {
                console.error('❌ Could not play notification sound:', e);
                // Try fallback
                this.playNotificationSoundFallback();
            }
        } else {
            console.log('⚠️ AudioContext not available, using fallback');
            this.playNotificationSoundFallback();
        }
    }
    
    playNotificationSoundFallback() {
        try {
            // Create a more reliable beep using Web Audio API
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                const ctx = new AudioContext();
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                
                oscillator.start(ctx.currentTime);
                oscillator.stop(ctx.currentTime + 0.3);
                console.log('✅ Fallback audio sound played');
            } else {
                // Last resort: Use HTML5 audio with data URI
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fLZfzMGHm+98OSbUQ8OUKXh8LZjHAY4kdfyznksBSR3x/DdkEAKFF606euoVRQKRp/g8r5sIQUxh9Hy2X8zBh5vvfDkm1EPDlCl4fC2YxwGOJHX8s55LAUkd8fw3ZBAC');
                audio.volume = 0.5;
                audio.play().then(() => {
                    console.log('✅ HTML5 audio fallback sound played');
                }).catch((e) => {
                    console.error('❌ Could not play HTML5 audio fallback:', e);
                });
            }
        } catch (e) {
            console.error('❌ All sound methods failed:', e);
        }
    }
    
    disconnect() {
        this.stopHeartbeat();
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }
    
    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }
    
    // Test function to manually trigger a notification
    testNotification(title = 'Test Notification', message = 'This is a test notification', type = 'info') {
        console.log('🧪 Testing notification display...');
        const testMessage = {
            type: 'test',
            title: title,
            message: message,
            data: {},
            sound: 'notification',
            timestamp: new Date().toISOString()
        };
        this.handleNotification(testMessage);
    }
    } // End of RealtimeWebSocket class
    
    // Make it globally available
    window.RealtimeWebSocket = RealtimeWebSocket;
} // End of if (typeof RealtimeWebSocket === 'undefined')

// Global instance
let realtimeWS = null;

// Initialize WebSocket connection based on user role
// Prevent multiple simultaneous initializations
let isInitializing = false;

function initRealtimeWebSocket(role = 'user', userId = null) {
    // Prevent multiple simultaneous initializations
    if (isInitializing) {
        console.log('⚠️ WebSocket initialization already in progress, skipping...');
        return realtimeWS;
    }
    
    // If already connected with same role, don't reconnect
    if (realtimeWS && realtimeWS.isConnected() && realtimeWS.role === role) {
        console.log('✅ WebSocket already connected with role:', role, '- skipping reinit');
        return realtimeWS;
    }
    
    isInitializing = true;
    
    // Disconnect existing connection if role changed
    if (realtimeWS) {
        console.log('🔄 Disconnecting existing WebSocket connection...');
        realtimeWS.disconnect();
        realtimeWS = null;
    }
    
    // Determine role from page context if not provided
    if (!role || role === 'null' || role === '') {
        // Try to detect role from page
        if (window.location.pathname.includes('/AdminManage/')) {
            role = 'admin';
        } else if (window.location.pathname.includes('/Staff/')) {
            role = 'staff';
        } else if (window.location.pathname.includes('Shipper_Orders')) {
            role = 'shipper';
        } else {
            role = 'user';
        }
    }
    
    // Determine channel based on role
    let channel = 'user_channel';
    if (role === 'admin' || role === 'manager') {
        channel = 'admin_channel';
    } else if (role === 'employee' || role === 'staff') {
        channel = 'staff_channel';
    } else if (role === 'shipper') {
        channel = 'shipper_channel';
    }
    
    // WebSocket URL - try to detect from current location
    let wsUrl = 'ws://localhost:8080';
    
    // Try to use same host/port as current page
    if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        // Use same hostname but port 8080
        wsUrl = `ws://${window.location.hostname}:8080`;
    }
    
    // Allow override via global variable
    if (typeof window.WEBSOCKET_URL !== 'undefined') {
        wsUrl = window.WEBSOCKET_URL;
    }
    
    console.log('🔌 Initializing WebSocket connection:', {
        url: wsUrl,
        channel: channel,
        role: role,
        userId: userId
    });
    
    try {
        if (typeof RealtimeWebSocket === 'undefined') {
            console.error('❌ RealtimeWebSocket class is not defined!');
            isInitializing = false;
            return null;
        }
        
        console.log('🔧 Creating RealtimeWebSocket instance with:', {
            wsUrl: wsUrl,
            channel: channel,
            role: role,
            userId: userId
        });
        
        realtimeWS = new RealtimeWebSocket({
            wsUrl: wsUrl,
            channel: channel,
            role: role,
            userId: userId,
            onConnect: () => {
                console.log('✅ Realtime WebSocket connected successfully');
                console.log('📊 Connection details:', {
                    role: realtimeWS.role,
                    channel: realtimeWS.channel,
                    userId: realtimeWS.userId,
                    connected: realtimeWS.isConnected()
                });
                isInitializing = false;
            },
            onDisconnect: () => {
                console.log('❌ Realtime WebSocket disconnected');
                isInitializing = false;
            },
            onError: (error) => {
                console.error('⚠️ Realtime WebSocket error:', error);
                isInitializing = false;
                // Show user-friendly error
                console.warn('⚠️ WebSocket connection failed. Real-time updates may not work. Please ensure the WebSocket server is running on port 8080.');
            }
        });
    } catch (error) {
        console.error('❌ Error creating WebSocket instance:', error);
        isInitializing = false;
        return null;
    }
    
    // Auto-reconnect on page visibility change (only once)
    if (!window._wsVisibilityHandlerAdded) {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && (!realtimeWS || !realtimeWS.isConnected())) {
                console.log('🔄 Page visible, reconnecting WebSocket...');
                setTimeout(() => initRealtimeWebSocket(role, userId), 500);
            }
        });
        window._wsVisibilityHandlerAdded = true;
    }
    
    // Reconnect on focus (only once)
    if (!window._wsFocusHandlerAdded) {
        window.addEventListener('focus', () => {
            if (!realtimeWS || !realtimeWS.isConnected()) {
                console.log('🔄 Window focused, reconnecting WebSocket...');
                setTimeout(() => initRealtimeWebSocket(role, userId), 500);
            }
        });
        window._wsFocusHandlerAdded = true;
    }
    
    // Periodic connection check (only once)
    if (!window._wsIntervalHandlerAdded) {
        setInterval(() => {
            if (realtimeWS && !realtimeWS.isConnected() && !isInitializing) {
                console.log('🔄 WebSocket disconnected, attempting reconnect...');
                initRealtimeWebSocket(role, userId);
            }
        }, 10000); // Check every 10 seconds
        window._wsIntervalHandlerAdded = true;
    }
    
    return realtimeWS;
}

// Global test function - can be called from browser console
// Usage: testNotification('Title', 'Message', 'success')
window.testNotification = function(title = 'Test Notification', message = 'This is a test notification', type = 'info') {
    console.log('🧪 testNotification called from console');
    if (realtimeWS) {
        console.log('✅ Using WebSocket instance');
        realtimeWS.testNotification(title, message, type);
    } else {
        console.error('❌ WebSocket not initialized. Creating test notification directly...');
        // Fallback: Create notification directly
        let container = document.getElementById('realtime-notifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'realtime-notifications';
            container.style.cssText = `
                position: fixed !important;
                top: 20px !important;
                right: 20px !important;
                z-index: 999999 !important;
                max-width: 400px !important;
            `;
            document.body.appendChild(container);
        }
        
        // Create toast directly
        const toast = document.createElement('div');
        const colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        const color = colors[type] || colors['info'];
        toast.style.cssText = `
            background: white !important;
            border-left: 4px solid ${color} !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
            border-radius: 4px !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1000000 !important;
        `;
        toast.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 4px; color: #333;">${title}</strong>
                    <div style="color: #666; font-size: 14px;">${message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; margin-left: 12px;">&times;</button>
            </div>
        `;
        container.appendChild(toast);
        
        // Play sound
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                const ctx = new AudioContext();
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                oscillator.start(ctx.currentTime);
                oscillator.stop(ctx.currentTime + 0.3);
                console.log('✅ Test sound played');
            }
        } catch (e) {
            console.error('❌ Could not play test sound:', e);
        }
        
        setTimeout(() => toast.remove(), 5000);
        console.log('✅ Test notification created');
    }
};

// Don't auto-initialize - let the page scripts call initRealtimeWebSocket() with proper role/userId
// This prevents double initialization and ensures correct role is passed

