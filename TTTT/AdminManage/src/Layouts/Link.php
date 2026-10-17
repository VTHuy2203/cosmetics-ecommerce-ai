<!-- Bootstrap core JavaScript-->
<script src="../../vendor/jquery/jquery.min.js"></script>
<script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../../js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="../../vendor/chart.js/Chart.min.js"></script>
<script src="../../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../../vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- DataTables initializer; chart pages initialize their own canvases. -->
<script src="../../js/demo/datatables-demo.js"></script>

<style>
    .order-action-toast {
        position: fixed;
        right: 1.25rem;
        top: 1.25rem;
        z-index: 11000;
        max-width: min(420px, calc(100vw - 2rem));
        border: 0;
        border-radius: .6rem;
        box-shadow: 0 .55rem 1.5rem rgba(31, 45, 61, .18);
    }

    #admin-live-notifications {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 2147483000;
        width: min(410px, calc(100vw - 2rem));
        pointer-events: none;
    }
    .admin-live-toast {
        display: flex;
        gap: .85rem;
        align-items: flex-start;
        margin-bottom: .75rem;
        padding: 1rem;
        color: #223047;
        background: #fff;
        border: 1px solid #d9e7e5;
        border-left: 5px solid #168c80;
        border-radius: .75rem;
        box-shadow: 0 .75rem 2rem rgba(25, 42, 70, .24);
        pointer-events: auto;
        animation: adminToastIn .22s ease-out;
    }
    .admin-live-toast__icon {
        display: grid;
        flex: 0 0 42px;
        width: 42px;
        height: 42px;
        place-items: center;
        color: #fff;
        background: #168c80;
        border-radius: 50%;
    }
    .admin-live-toast__body { flex: 1; min-width: 0; }
    .admin-live-toast__title { display: block; margin-bottom: .25rem; color: #172033; font-size: 1rem; }
    .admin-live-toast__message { margin: 0; color: #59657a; font-size: .9rem; line-height: 1.45; }
    .admin-live-toast__link { display: inline-block; margin-top: .55rem; color: #087c72; font-size: .84rem; font-weight: 700; }
    .admin-live-toast__close { border: 0; color: #7c8799; background: transparent; font-size: 1.25rem; line-height: 1; cursor: pointer; }
    @keyframes adminToastIn { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
</style>
<div id="admin-live-notifications" aria-live="assertive" aria-atomic="false"></div>
<script>
(function () {
    'use strict';

    function showOrderMessage(message, successful) {
        var oldToast = document.querySelector('.order-action-toast');
        if (oldToast) oldToast.remove();

        var toast = document.createElement('div');
        toast.className = 'alert ' + (successful ? 'alert-success' : 'alert-danger') + ' order-action-toast';
        toast.setAttribute('role', 'alert');
        toast.textContent = message;
        document.body.appendChild(toast);
        window.setTimeout(function () {
            if (toast.parentNode) toast.remove();
        }, 5500);
    }

    function removeOrderRow(row) {
        if (!row) return;
        var table = row.closest('table');
        if (window.jQuery && table && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(table)) {
            jQuery(table).DataTable().row(row).remove().draw(false);
            return;
        }
        row.remove();
    }

    function buildNextAction(cell, response, csrfInput) {
        cell.innerHTML = '';
        if (!response.next_action) {
            var badge = document.createElement('span');
            badge.className = response.status === 3 ? 'badge badge-success' : 'badge badge-info';
            badge.textContent = response.status_text;
            cell.appendChild(badge);
            return;
        }

        var form = document.createElement('form');
        form.method = 'post';
        form.action = 'Order_Status_Action.php';
        form.className = 'd-inline js-order-status-form';

        [
            [csrfInput.name, csrfInput.value],
            ['order_id', String(response.order_id)],
            ['action', response.next_action.action],
            ['return_to', 'all']
        ].forEach(function (item) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = item[0];
            input.value = item[1];
            form.appendChild(input);
        });

        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'btn ' + response.next_action.button_class;
        button.textContent = response.next_action.label;
        form.appendChild(button);
        cell.appendChild(form);
    }

    document.addEventListener('submit', async function (event) {
        var form = event.target.closest('.js-order-status-form, .js-order-payment-form');
        if (!form) return;
        event.preventDefault();

        var confirmation = form.dataset.confirm;
        if (confirmation && !window.confirm(confirmation)) return;

        var button = form.querySelector('button[type="submit"]');
        var originalText = button ? button.textContent : '';
        if (button) {
            button.disabled = true;
            button.textContent = 'Đang xử lý...';
        }

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin'
            });
            var responseText = await response.text();
            var data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                if (response.redirected || /<html|<!doctype/i.test(responseText)) {
                    throw new Error('Phiên quản trị đã hết hạn. Vui lòng đăng nhập lại và thử lại.');
                }
                console.error('Order API returned invalid JSON:', response.status, responseText);
                throw new Error('Máy chủ không thể xử lý yêu cầu. Chi tiết đã được ghi vào log.');
            }
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Không thể cập nhật đơn hàng.');
            }

            var row = form.closest('tr');
            var cell = form.closest('td');
            var csrfInput = form.querySelector('input[name="csrf_token"]');
            if (form.classList.contains('js-order-payment-form')) {
                if (cell) cell.innerHTML = '<span class="badge badge-success">Đã thanh toán</span>';
            } else if (data.remove_row) {
                removeOrderRow(row);
            } else if (cell && csrfInput) {
                buildNextAction(cell, data, csrfInput);
            }
            showOrderMessage(data.message, true);
        } catch (error) {
            showOrderMessage(error.message || 'Không thể kết nối máy chủ.', false);
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    });
})();
</script>

<script>
(function () {
    'use strict';
    var container = document.getElementById('admin-live-notifications');
    var storageKey = 'moshop_admin_seen_notifications_v1_' + <?php echo json_encode((string) ($_SESSION['admin_id'] ?? 'guest')); ?>;
    var storedSeen = [];
    try { storedSeen = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (error) { storedSeen = []; }
    var seen = new Set(Array.isArray(storedSeen) ? storedSeen : []);
    var latestId = 0;
    var firstPoll = true;

    function notificationKey(item) {
        var orderId = item.order_id || (item.data && item.data.order_id) || '';
        var type = String(item.type || '');
        if (orderId && (type === 'new_order' || type === 'order.created')) {
            return 'new-order|' + String(orderId);
        }
        var appointmentId = appointmentIdOf(item);
        if (appointmentId) {
            // The socket toast and the polled Notification Center row describe the same booking.
            return 'appointment|' + appointmentId;
        }
        return String(item.id || '') + '|' + type + '|' + String(orderId);
    }

    function appointmentIdOf(item) {
        var type = String(item.type || '');
        if (type === 'new_appointment') return String((item.data && item.data.appointment_id) || '');
        if (type === 'appointment.created') {
            var match = String(item.message || '').match(/#(\d+)/);
            return match ? match[1] : '';
        }
        return '';
    }

    function notificationLink(item, orderId) {
        var type = String(item.type || '');
        var data = item.data || {};
        if (orderId) return {href: '/TTTT/AdminManage/src/Oders/All_Oders.php', text: 'Xem đơn hàng →'};
        if (type === 'new_appointment' || type === 'appointment.created') {
            return {href: '/TTTT/AdminManage/src/Users/Admin_Bookings.php', text: 'Xem lịch hẹn →'};
        }
        if (type === 'chat_message' && data.is_guest && data.session_id) {
            return {href: '/TTTT/AdminManage/src/Users/Admin_Guest_Chat_History.php?session_id=' + encodeURIComponent(data.session_id), text: 'Xem chat →'};
        }
        if (type === 'chat_message' && data.user_id) {
            return {href: '/TTTT/AdminManage/src/Users/Customer_Detail.php?id=' + encodeURIComponent(data.user_id), text: 'Xem chat →'};
        }
        return null;
    }

    function showAdminNotification(item) {
        if (!container) return;
        var key = notificationKey(item);
        if (seen.has(key)) return;
        seen.add(key);
        while (seen.size > 100) seen.delete(seen.values().next().value);
        try { localStorage.setItem(storageKey, JSON.stringify(Array.from(seen))); } catch (error) {}

        var orderId = item.order_id || (item.data && item.data.order_id) || null;
        var link = notificationLink(item, orderId);
        var toast = document.createElement('section');
        toast.className = 'admin-live-toast';
        toast.innerHTML =
            '<span class="admin-live-toast__icon"><i class="fas fa-bell"></i></span>' +
            '<div class="admin-live-toast__body">' +
                '<strong class="admin-live-toast__title"></strong>' +
                '<p class="admin-live-toast__message"></p>' +
                (link ? '<a class="admin-live-toast__link"></a>' : '') +
            '</div>' +
            '<button type="button" class="admin-live-toast__close" aria-label="Đóng">&times;</button>';
        if (link) {
            var anchor = toast.querySelector('.admin-live-toast__link');
            anchor.href = link.href;
            anchor.textContent = link.text;
        }
        toast.querySelector('.admin-live-toast__title').textContent = item.title || 'Thông báo mới';
        toast.querySelector('.admin-live-toast__message').textContent = item.message || 'Hệ thống vừa có cập nhật mới.';
        toast.querySelector('.admin-live-toast__close').addEventListener('click', function () { toast.remove(); });
        container.appendChild(toast);
        window.setTimeout(function () { if (toast.parentNode) toast.remove(); }, 15000);
    }

    async function markNotificationsRead(ids) {
        ids = ids.filter(function (id) { return Number(id) > 0; });
        if (!ids.length) return;
        var body = new URLSearchParams();
        body.set('csrf_token', <?php echo json_encode(csrf_token(), JSON_UNESCAPED_SLASHES); ?>);
        ids.forEach(function (id) { body.append('ids[]', String(id)); });
        try {
            await fetch('/TTTT/AdminManage/src/Processing/Admin_Notifications.php', {
                method: 'POST', body: body, credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}
            });
        } catch (error) {
            console.warn('Could not mark Admin notifications as read:', error);
        }
    }

    window.addEventListener('moshop:realtime-notification', function (event) {
        var message = event.detail || {};
        showAdminNotification({
            type: message.type,
            title: message.title,
            message: message.message,
            order_id: message.data && message.data.order_id,
            data: message.data
        });
    });

    async function pollAdminNotifications() {
        try {
            var url = '/TTTT/AdminManage/src/Processing/Admin_Notifications.php?after_id=' + latestId + (firstPoll ? '&recent=1' : '');
            var response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) return;
            var data = await response.json();
            if (!data.ok) return;
            var items = data.items || [];
            items.forEach(showAdminNotification);
            markNotificationsRead(items.map(function (item) { return item.id; }));
            latestId = Math.max(latestId, Number(data.latest_id || 0));
            var badge = document.getElementById('admin-notification-count');
            if (badge) {
                var remaining = Math.max(0, Number(data.unread || 0) - items.length);
                badge.textContent = remaining > 99 ? '99+' : String(remaining);
                badge.style.display = remaining > 0 ? 'inline-block' : 'none';
            }
            firstPoll = false;
        } catch (error) {
            console.warn('Admin notification fallback is temporarily unavailable:', error);
        }
    }

    pollAdminNotifications();
    window.setInterval(pollAdminNotifications, 3000);
})();
</script>
