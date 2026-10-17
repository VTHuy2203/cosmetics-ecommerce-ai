/**
 * Chat Widget - Clean Rebuild
 * Handles chat UI, message display, and booking form
 */

(function() {
  'use strict';

  const CFG = {
    PHP: window.CHAT_PHP_BASE || '/TTTT/WebBanHangMyPham/src/Processing',
    UID: window.CURRENT_USER_ID || 0,
    NAME: window.CURRENT_USER_NAME || 'Bạn',
    CSRF: window.CHAT_CSRF_TOKEN || '',
    PERSONAL: window.CHAT_PERSONAL_URL || '/TTTT/WebBanHangMyPham/src/View/Personal.php'
  };

  const K_CACHE = 'chat_cache';
  const K_GREETED = 'chat_greeted';
  const K_SESSION = 'chat_session_id';

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
  }

  // Tạo ID phiên mới nếu user_id thay đổi (tài khoản mới đăng nhập)
  let storedUID = getCookie('chat_user_id');
  if (storedUID !== String(CFG.UID)) {
    // Ng dùng đã thay đổi, tạo session mới và xóa cache cũ
    setCookie(K_SESSION, '', -1); // Delete old session
    localStorage.removeItem(K_CACHE);
    localStorage.removeItem(K_GREETED);
    setCookie('chat_user_id', String(CFG.UID), 365);
  }

  const requestedSessionId = new URLSearchParams(window.location.search).get('chat_session');
  const validRequestedSession = /^[A-Za-z0-9_-]{8,128}$/.test(requestedSessionId || '')
    ? requestedSessionId
    : null;
  // The server creates new session ids. The URL is only a one-time selector
  // when reopening history; after it is loaded, the cookie becomes canonical.
  let sid = validRequestedSession || getCookie(K_SESSION) || '';
  let upcomingAppointment = null;
  let currentSessionInfo = null;

  function setActiveSession(sessionId) {
    if (!/^[A-Za-z0-9_-]{8,128}$/.test(sessionId || '')) return;
    sid = sessionId;
    setCookie(K_SESSION, sid, 365);
  }

  function clearActiveSession() {
    const previousCacheKey = cacheKey();
    sid = '';
    currentSessionInfo = null;
    setCookie(K_SESSION, '', -1);
    localStorage.removeItem(previousCacheKey);
  }

  if (sid && getCookie(K_SESSION) !== sid) {
    setActiveSession(sid);
  }

  function consumeSessionUrl() {
    const url = new URL(window.location.href);
    if (!url.searchParams.has('chat_session')) return;
    url.searchParams.delete('chat_session');
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
  }

  function cacheKey() {
    return `${K_CACHE}_${sid}`;
  }

  // Cache helpers
  function readCache() {
    try {
      return JSON.parse(localStorage.getItem(cacheKey()) || '[]');
    } catch {
      return [];
    }
  }

  function writeCache(arr) {
    localStorage.setItem(cacheKey(), JSON.stringify(arr || []));
  }

  function pushCache(entry) {
    const c = readCache();
    c.push(entry);
    writeCache(c);
    return c;
  }

  // Load chat history from database
  async function loadChatHistory() {
    try {
      const url = `${CFG.PHP}/Chat_History.php${sid ? `?sid=${encodeURIComponent(sid)}` : ''}`;
      console.log('[Chat] Loading history from:', url);
      
      const res = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-cache'
      });

      if (res.ok) {
        const data = await res.json();
        if (data.ok && Array.isArray(data.messages)) {
          if (data.session_id) setActiveSession(data.session_id);
          upcomingAppointment = data.upcoming_appointment || null;
          currentSessionInfo = data.session || null;
          consumeSessionUrl();
          console.log('[Chat] ✓ Loaded', data.messages.length, 'messages from database');
          const cached = data.messages.map(msg => ({
            message: msg.message || '',
            direction: msg.direction || 'bot',
            created_at: msg.created_at || new Date().toISOString().slice(0, 19).replace('T', ' ')
          })).filter(msg => msg.message && msg.message.trim().length > 0);
          
          writeCache(cached);
          return cached;
        }
      }
    } catch (e) {
      console.error('[Chat] Error loading history:', e);
      return readCache();
    }
    return [];
  }

  // DOM helpers
  function dom(tag, attrs, ...children) {
    const el = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(k => {
        if (k === 'style' && typeof attrs[k] === 'object') {
          Object.assign(el.style, attrs[k]);
        } else if (k === 'class') {
          el.className = attrs[k];
        } else if (k === 'for') {
          el.htmlFor = attrs[k];
        } else if (k.startsWith('aria-') || k.startsWith('data-')) {
          el.setAttribute(k, attrs[k]);
        } else {
          el[k] = attrs[k];
        }
      });
    }
    children.forEach(c => {
      if (typeof c === 'string') el.appendChild(document.createTextNode(c));
      else if (c) el.appendChild(c);
    });
    return el;
  }

  function fmtTime(ts) {
    if (!ts) {
      // Return current time if no timestamp provided
      const now = new Date();
      return now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }
    try {
      const d = new Date(ts.replace(' ', 'T'));
      const now = new Date();
      const isToday = d.toDateString() === now.toDateString();
      const yesterday = new Date(now);
      yesterday.setDate(yesterday.getDate() - 1);
      const isYesterday = d.toDateString() === yesterday.toDateString();

      const timeStr = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

      if (isToday) {
        return timeStr;
      } else if (isYesterday) {
        return 'Hôm qua ' + timeStr;
      } else {
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }) + ' ' + timeStr;
      }
    } catch {
      return '';
    }
  }

  function buildViDatetime(dateStr, timeStr) {
    if (!dateStr || !timeStr) return null;
    try {
      const [y, m, d] = dateStr.split('-');
      const [h, min] = timeStr.split(':');
      return `${d}/${m}/${y} ${h}:${min}`;
    } catch {
      return null;
    }
  }

  // Convert markdown to HTML (supports tables, bold, line breaks)
  function renderMarkdown(text) {
    if (!text) return '';

    // Kiểm tra xem văn bản có chứa bảng Markdown hay không (xử lý các bảng nội tuyến không có xuống dòng)
    const hasTable = /\|.+\|/.test(text) && /\|[-:]+\|/.test(text);

    if (hasTable) {
// Đầu tiên, chuẩn hóa văn bản: chuyển đổi định dạng bảng nội tuyến thành các dòng mới phù hợp
// Mẫu: ||---...---|| biểu thị dấu phân cách hàng của bảng mà không có dòng mới
      let normalizedText = text;

// Tách chuỗi theo mẫu | | (dấu phân cách hàng) nhưng vẫn giữ nguyên dấu gạch dọc
// Xử lý các trường hợp như: "văn bản ||----| hoặc |---|---||văn bản"
      normalizedText = normalizedText.replace(/\|\s*\|/g, '|\n|');

// Chia văn bản thành các phần: trước bảng, bảng, sau bảng
      const lines = normalizedText.split('\n');
      let result = [];
      let tableLines = [];
      let inTable = false;

      for (let line of lines) {
        const trimmedLine = line.trim();
        const isTableLine = /^\|.+\|$/.test(trimmedLine);

        if (isTableLine) {
          if (!inTable) {
            inTable = true;
          }
          tableLines.push(trimmedLine);
        } else {
          if (inTable) {
            // End of table, convert collected lines to HTML table
            result.push(convertTableToHTML(tableLines));
            tableLines = [];
            inTable = false;
          }
          if (trimmedLine) {
            result.push(renderTextLine(trimmedLine));
          }
        }
      }

      // If still in table at end of text
      if (inTable && tableLines.length > 0) {
        result.push(convertTableToHTML(tableLines));
      }

      return result.join('<br>');
    } else {
      // No table, just render text with bold and line breaks
      return renderTextLine(text);
    }
  }

  // Chuyển đổi các dòng bảng Markdown thành bảng HTML.
  function convertTableToHTML(tableLines) {
    if (tableLines.length < 2) return tableLines.map(l => escapeHTML(l)).join('<br>');

    const rows = [];
    let headerRow = null;
    let foundSeparator = false;

    for (let line of tableLines) {
      // Kiểm tra xem đây có phải là đường phân cách (|---|---|) hay không.
      if (/^\|[-:\s|]+\|$/.test(line)) {
        foundSeparator = true;
        continue;
      }

      // Phân tích cú pháp các ô - tách bằng dấu | và lọc các chuỗi rỗng
      const cells = line.split('|').map(c => c.trim()).filter(c => c !== '');
      if (cells.length === 0) continue;

      if (!foundSeparator && headerRow === null) {
        // This is the header row
        headerRow = cells;
      } else {
        // This is a data row
        const cellsHTML = cells.map(c => `<td>${escapeHTML(c)}</td>`).join('');
        rows.push(`<tr>${cellsHTML}</tr>`);
      }
    }

    if (!headerRow || rows.length === 0) {
      // Phương án dự phòng: chỉ hiển thị dưới dạng văn bản nếu quá trình phân tích cú pháp thất bại.
      return tableLines.map(l => escapeHTML(l)).join('<br>');
    }

    const headerHTML = headerRow.map(c => `<th>${escapeHTML(c)}</th>`).join('');
    return `<table class="cw-table"><thead><tr>${headerHTML}</tr></thead><tbody>${rows.join('')}</tbody></table>`;
  }

  // Hiển thị dòng văn bản với định dạng Markdown đậm.
  function renderTextLine(text) {
    if (!text) return '';

    // Escape HTML first
    let html = escapeHTML(text);

    // Convert **bold** to <strong>
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

    // Convert line breaks
    html = html.replace(/\n/g, '<br>');

    return html;
  }

  // Escape HTML special characters
  function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Render booking form
  function renderBookingForm(container, onSubmit) {
    const isLoggedIn = CFG.UID > 0;
    // Class-based markup: the bot bubble uses white-space:pre-wrap, so template indentation
    // must not reach it, and the time input must not reuse the message timestamp class.
    const wrap = dom('div', { class: 'cw-booking' });

    // Default time: next full hour.
    const now = new Date();
    const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
    nextHour.setMinutes(0, 0, 0);
    const defaultTime = nextHour.toTimeString().slice(0, 5); // HH:MM format

    const field = (label, input) => `<label class="cw-booking-field"><span>${label}</span>${input}</label>`;
    let formHTML = '<div class="cw-booking-row">'
      + field('📅 Ngày', '<input type="date" class="cw-date cw-booking-input" required>')
      + field('⏰ Giờ', `<input type="time" class="cw-book-time cw-booking-input" value="${defaultTime}" required>`)
      + '</div>';

    // Guests leave contact details so staff can confirm the appointment.
    if (!isLoggedIn) {
      formHTML += '<div class="cw-booking-contact"><div class="cw-booking-title">📝 Thông tin liên hệ (bắt buộc)</div>'
        + field('👤 Họ và tên', '<input type="text" class="cw-guest-name cw-booking-input" placeholder="Nhập họ và tên đầy đủ" autocomplete="name" required>')
        + field('📱 Số điện thoại', '<input type="tel" class="cw-guest-phone cw-booking-input" placeholder="Ví dụ: 0912345678" autocomplete="tel" inputmode="tel" required>')
        + field('📍 Địa chỉ', '<input type="text" class="cw-guest-address cw-booking-input" placeholder="Địa chỉ để nhân viên liên hệ" autocomplete="street-address" required>')
        + '</div>';
    }

    formHTML += '<button type="button" class="cw-book">Đặt lịch tư vấn</button>';

    wrap.innerHTML = formHTML;

    const btn = wrap.querySelector('.cw-book');
    const dateInput = wrap.querySelector('.cw-date');
    const timeInput = wrap.querySelector('.cw-book-time');

    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);

    btn.addEventListener('click', async () => {
      const d = dateInput.value;
      const t = timeInput.value;

      console.log('[Booking] Date value:', d);
      console.log('[Booking] Time value:', t);

      if (!d || !t) {
        alert('Vui lòng chọn đầy đủ Ngày/Tháng/Năm và Giờ/Phút');
        return;
      }

      // Validate guest fields if not logged in
      if (!isLoggedIn) {
        const guestName = wrap.querySelector('.cw-guest-name')?.value?.trim() || '';
        const guestPhone = wrap.querySelector('.cw-guest-phone')?.value?.trim() || '';
        const guestAddress = wrap.querySelector('.cw-guest-address')?.value?.trim() || '';

        console.log('[Booking] Guest Name:', guestName);
        console.log('[Booking] Guest Phone:', guestPhone);
        console.log('[Booking] Guest Address:', guestAddress);

        if (!guestName || !guestPhone || !guestAddress) {
          alert('Vui lòng điền đầy đủ thông tin: Họ tên, Số điện thoại và Địa chỉ');
          return;
        }

        // Same rule as the server (Chat_Send.php validateGuestContact): Vietnamese mobile numbers.
        const cleanPhone = guestPhone.replace(/[\s.\-()]/g, '');
        if (!/^(?:0|\+84)(?:3|5|7|8|9)\d{8}$/.test(cleanPhone)) {
          alert('Số điện thoại không hợp lệ. Vui lòng nhập số di động Việt Nam, ví dụ 0912345678');
          return;
        }
      }

      const s = buildViDatetime(d, t);
      console.log('[Booking] Built datetime:', s);

      if (!s) {
        alert('Vui lòng chọn ngày/giờ hợp lệ');
        return;
      }

      // Build message with guest info if not logged in
      let bookingMessage = s;
      if (!isLoggedIn) {
        const guestName = wrap.querySelector('.cw-guest-name').value.trim();
        const guestPhone = wrap.querySelector('.cw-guest-phone').value.trim();
        const guestAddress = wrap.querySelector('.cw-guest-address').value.trim();
        bookingMessage = `${s} | Tên: ${guestName} | SĐT: ${guestPhone} | Địa chỉ: ${guestAddress}`;
      }

      console.log('[Booking] Final message:', bookingMessage);
      
      // Disable button during submission
      btn.disabled = true;
      btn.textContent = 'Đang gửi...';
      
      try {
        // Call onSubmit which will send the message
        await onSubmit(bookingMessage);
        // Hide form after successful submission
        wrap.style.display = 'none';
      } catch (error) {
        console.error('Booking submission error:', error);
        alert('Có lỗi xảy ra khi đặt lịch. Vui lòng thử lại.');
        btn.disabled = false;
        btn.textContent = 'Đặt lịch tư vấn';
      }
    });

    dateInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') btn.click();
    });
    timeInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') btn.click();
    });

    container.appendChild(wrap);
  }

  function renderAppointmentNotice(body) {
    if (!upcomingAppointment || !upcomingAppointment.appt_at) return;

    const appointmentDate = new Date(String(upcomingAppointment.appt_at).replace(' ', 'T'));
    const formatted = Number.isNaN(appointmentDate.getTime())
      ? ''
      : appointmentDate.toLocaleString('vi-VN', {
          day: '2-digit', month: '2-digit', year: 'numeric',
          hour: '2-digit', minute: '2-digit'
        });
    const notice = dom('div', { class: 'cw-appointment-notice' });
    const icon = dom('i', { class: 'far fa-calendar-check' });
    const text = dom('span', null, formatted
      ? `Bạn đang có 1 lịch tư vấn lúc ${formatted}.`
      : 'Bạn đang có 1 lịch tư vấn.');
    const link = dom('a', { href: `${CFG.PERSONAL}#consultation-history` }, 'Xem ngay');
    notice.append(icon, text, link);
    body.append(notice);
  }

  function renderSessionContext(body, messageCount) {
    const context = dom('div', { class: 'cw-session-context' });
    const label = dom('div', { class: 'cw-session-context-label' });
    const icon = dom('i', { class: 'far fa-comment-dots' });
    const title = currentSessionInfo?.title || (messageCount > 0 ? 'Đoạn chat hiện tại' : 'Cuộc trò chuyện mới');
    label.append(icon, dom('span', null, title));
    const count = dom('small', null, `${messageCount} tin nhắn`);
    context.append(label, count);
    body.append(context);
  }

  function renderEmptyState(body) {
    const empty = dom('div', { class: 'cw-empty-state' });
    const icon = dom('div', { class: 'cw-empty-state-icon' });
    icon.innerHTML = '<i class="far fa-comments"></i>';
    empty.append(
      icon,
      dom('strong', null, 'Cuộc trò chuyện mới'),
      dom('span', null, 'Hãy nhập nhu cầu của bạn để Mơ Shop bắt đầu tư vấn.')
    );
    body.append(empty);
  }

  // Hiển thị tin nhắn với giao diện người dùng được cải tiến (ảnh đại diện, tên người gửi, dấu thời gian)
  function render(messages) {
    const body = document.querySelector('.cw-body');
    if (!body) return;

    body.innerHTML = '';
    renderSessionContext(body, Array.isArray(messages) ? messages.length : 0);
    renderAppointmentNotice(body);
    if (!Array.isArray(messages) || messages.length === 0) {
      renderEmptyState(body);
      body.scrollTop = 0;
      return;
    }
    messages.forEach(m => {
      const isBot = m.direction === 'bot';
      const isUser = !isBot;
      let text = m.message || '';

      // Trích xuất dấu hiệu mẫu đơn đặt chỗ
      const needForm = isBot && text.includes('[[BOOKING_FORM]]');
      if (needForm) text = text.replace('[[BOOKING_FORM]]', '').trim();

      // Create message row with avatar
      const row = dom('div', { class: `cw-msg ${isUser ? 'user' : 'bot'}` });

      // Avatar
      const avatar = dom('div', { class: 'cw-avatar' });
      avatar.textContent = isUser ? '👤' : '🌸';

      // Content wrapper (sender name + bubble + time)
      const content = dom('div', { class: 'cw-content' });

      // Sender name label
      const sender = dom('div', { class: 'cw-sender' });
      sender.textContent = isUser ? 'Bạn' : 'Mơ Shop AI';

      // Message bubble
      const bubble = dom('div', { class: 'cw-bubble' });

      // Hiển thị văn bản theo định dạng Markdown với hỗ trợ bảng.
      const renderedHTML = renderMarkdown(text);
      bubble.innerHTML = renderedHTML;

      // Timestamp
      const timeEl = dom('div', { class: 'cw-time cw-message-time' });
      timeEl.textContent = fmtTime(m.created_at || '');

      // Tập hợp nội dung
      content.append(sender, bubble, timeEl);

      // Ghép hàng (ảnh đại diện + nội dung)
      row.append(avatar, content);
      body.append(row);

      // Hiển thị mẫu đặt chỗ nếu cần
      if (needForm) {
        renderBookingForm(bubble, async (viDatetime) => {
          // Gửi trực tiếp ngày giờ dưới dạng tin nhắn.
          const input = document.querySelector('.cw-input');
          if (input) {
            input.value = viDatetime;
            await send(); // Wait for send to complete
          }
        });
      }
    });
    body.scrollTop = body.scrollHeight;
  }

  // CHAT_UI_V2_BEGIN: temporary visual indicator; safe to remove for rollback.
  function showTypingIndicator() {
    const body = document.querySelector('.cw-body');
    if (!body || body.querySelector('.cw-typing-row')) return;

    const row = dom('div', { class: 'cw-msg bot cw-typing-row' });
    const avatar = dom('div', { class: 'cw-avatar' }, '🌸');
    const content = dom('div', { class: 'cw-content' });
    const sender = dom('div', { class: 'cw-sender' }, 'Mơ Shop');
    const bubble = dom('div', { class: 'cw-bubble cw-typing' });
    bubble.setAttribute('aria-label', 'Mơ Shop đang trả lời');
    bubble.append(dom('span'), dom('span'), dom('span'));
    content.append(sender, bubble);
    row.append(avatar, content);
    body.append(row);
    body.scrollTop = body.scrollHeight;
  }

  function hideTypingIndicator() {
    document.querySelector('.cw-typing-row')?.remove();
  }
  // CHAT_UI_V2_END

  async function runSessionAction(action) {
    if (action === 'delete' && !window.confirm('Xóa vĩnh viễn cuộc trò chuyện này khỏi lịch sử? Lịch tư vấn vẫn được giữ lại.')) {
      return;
    }

    const buttons = document.querySelectorAll('.cw-new-chat, .cw-delete-chat, .cw-quick-reply');
    buttons.forEach(button => { button.disabled = true; });
    try {
      const response = await fetch(`${CFG.PHP}/Chat_Session_Action.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': CFG.CSRF
        },
        body: JSON.stringify({ action, session_id: sid })
      });
      const data = await response.json();
      if (!response.ok || !data.ok || (action === 'new' && !data.session_id)) {
        throw new Error(data.error || `HTTP ${response.status}`);
      }

      upcomingAppointment = data.upcoming_appointment || null;
      if (action === 'delete') {
        clearActiveSession();
      } else {
        setActiveSession(data.session_id);
        currentSessionInfo = data.session || { title: 'Cuộc trò chuyện mới' };
      }
      consumeSessionUrl();
      writeCache([]);
      render([]);
      if (action === 'delete' && document.getElementById('chat-history')) {
        window.setTimeout(() => window.location.reload(), 150);
        return;
      }
      document.querySelector('.cw-input')?.focus();
    } catch (error) {
      console.error('[Chat] Session action failed:', error);
      window.alert('Không thể thực hiện thao tác. Vui lòng tải lại trang và thử lại.');
    } finally {
      buttons.forEach(button => { button.disabled = false; });
    }
  }

  // Chào người dùng - chỉ khi nhấn nút "Tin nhắn"
  function greetNow() {
    const gNow = new Date().toISOString().slice(0, 19).replace('T', ' ');
    const today = new Date().toISOString().slice(0, 10);
    const greetedKey = K_GREETED + '_' + (CFG.UID || 0); // Per-user greeting key
    localStorage.setItem(greetedKey, today);
    
    // Nếu đã đăng nhập, hãy dùng tên người dùng, nếu không thì dùng "Xin chào".
    const greetingName = (CFG.UID > 0 && CFG.NAME !== 'Bạn' && CFG.NAME !== 'bạn') ? CFG.NAME : 'bạn';
    const greeting = (CFG.UID > 0 && CFG.NAME !== 'Bạn' && CFG.NAME !== 'bạn') 
      ? `Xin chào ${CFG.NAME}! Bạn đang tìm sản phẩm gì? Mình có thể giúp bạn.`
      : `Xin chào bạn! Bạn đang tìm sản phẩm gì? Mình có thể giúp bạn.`;
    
    render(pushCache({
      message: greeting,
      direction: 'bot',
      created_at: gNow
    }));
  }

  // Send message
  async function send() {
    const input = document.querySelector('.cw-input');
    if (!input) return;

    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    input.disabled = true;
    const sendButton = document.querySelector('.cw-send');
    if (sendButton) sendButton.disabled = true;
    const quickReplyButtons = document.querySelectorAll('.cw-quick-reply');
    quickReplyButtons.forEach(button => { button.disabled = true; });

    // Thêm thông báo người dùng vào giao diện người dùng
    const userMsg = {
      message: msg,
      direction: 'user',
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
    render(pushCache(userMsg));
    showTypingIndicator();

    try {
      const res = await fetch(`${CFG.PHP}/Chat_Send.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CHAT_CSRF_TOKEN || '' },
        body: JSON.stringify({
          message: msg,
          session_id: sid
        })
      });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }

      const data = await res.json();
      if (data.ok && data.reply) {
        if (data.session_id) setActiveSession(data.session_id);
        if (data.session) currentSessionInfo = data.session;
        if (data.upcoming_appointment) upcomingAppointment = data.upcoming_appointment;
        const botMsg = {
          message: data.reply,
          direction: 'bot',
          created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
        };
        render(pushCache(botMsg));
        
        // Kiểm tra xem có cần điền mẫu đặt chỗ không - hiển thị mẫu sau khi thông báo được hiển thị.
        if (data.has_booking_form) {
          setTimeout(() => {
            const lastBotBubble = document.querySelector('.cw-msg.bot:last-child .cw-bubble');
            if (lastBotBubble) {
              renderBookingForm(lastBotBubble, async (viDatetime) => {
                const input = document.querySelector('.cw-input');
                if (input) {
                  input.value = viDatetime;
                  await send();
                }
              });
            }
          }, 100);
        }
      } else {
        throw new Error('Invalid response format');
      }
    } catch (e) {
      console.error('[Chat] Error sending message:', e);
      const errorMsg = {
        message: 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.',
        direction: 'bot',
        created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
      };
      render(pushCache(errorMsg));
    } finally {
      hideTypingIndicator();
      input.disabled = false;
      if (sendButton) sendButton.disabled = false;
      quickReplyButtons.forEach(button => { button.disabled = false; });
      input.focus();
    }
  }

  // Khởi tạo cuộc trò chuyện - chỉ thiết lập trình lắng nghe sự kiện, không tải lịch sử cho đến khi nhấn nút "Tin nhắn"
  function initChat() {
    // Setup send button
    const btn = document.querySelector('.cw-send');
    const input = document.querySelector('.cw-input');
    if (btn) btn.addEventListener('click', send);
    if (input) {
      input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') send();
      });
    }

    document.querySelectorAll('.cw-quick-reply').forEach((button) => {
      button.addEventListener('click', async () => {
        const quickMessage = String(button.dataset.message || '').trim();
        if (!input || !quickMessage || input.disabled) return;
        input.value = quickMessage;
        await send();
      });
    });

    document.querySelector('.cw-new-chat')?.addEventListener('click', () => runSessionAction('new'));
    document.querySelector('.cw-delete-chat')?.addEventListener('click', () => runSessionAction('delete'));

    if (validRequestedSession) {
      window.setTimeout(() => document.getElementById('chat-toggle-btn')?.click(), 150);
    }
  }
  
  // Tải khung chat khi nhấn nút Tin nhắn
  async function loadChatOnClick() {
    // Tải lịch sử từ cơ sở dữ liệu (theo user_id)
    const history = await loadChatHistory();
    render(history);
    
    // Focus input
    const input = document.querySelector('.cw-input');
    if (input) input.focus();
  }

  // Make loadChatOnClick available globally
  window.loadChatOnClick = loadChatOnClick;

  // Tự động khởi tạo khi DOM sẵn sàng
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChat);
  } else {
    initChat();
  }
})();
