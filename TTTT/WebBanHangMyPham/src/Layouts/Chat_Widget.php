<!-- Chat Widget HTML Structure - Green Theme -->
<div id="chat-widget" style="position:fixed;bottom:20px;right:20px;z-index:9999;width:400px;max-width:calc(100vw - 40px);display:none;">
  <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;display:flex;flex-direction:column;height:520px;max-height:calc(100vh - 100px);">
    <!-- Chat Header - Green Theme -->
    <div style="background:linear-gradient(135deg, #2f9e91 0%, #1a7a6e 100%);color:#fff;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;">🌸</div>
        <div>
          <strong style="font-size:16px;">Mơ Shop Chat</strong>
          <div style="font-size:12px;opacity:0.9;display:flex;align-items:center;gap:4px;">
            <span style="width:8px;height:8px;background:#4ade80;border-radius:50%;display:inline-block;"></span>
            Trợ lý tư vấn trực tuyến
          </div>
        </div>
      </div>
      <button id="chat-toggle" style="background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:18px;cursor:pointer;padding:6px 10px;border-radius:8px;transition:background 0.2s;">−</button>
    </div>

    <!-- Promotional Banner inside Chat -->
    <div class="cw-promo-banner" style="background:linear-gradient(90deg, #667eea 0%, #764ba2 100%);color:#fff;padding:10px 14px;font-size:12px;display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="window.location.href='View_Product.php'">
      <span style="font-size:16px;">🎁</span>
      <span><strong>Mua trên 5 triệu</strong> → Thành VIP, giảm 20%!</span>
      <span style="margin-left:auto;font-size:10px;opacity:0.8;">Xem ngay →</span>
    </div>

    <!-- Chat Body -->
    <div class="cw-body" style="flex:1;overflow-y:auto;padding:16px;background:linear-gradient(180deg, #f0f4f8 0%, #e8ecf1 100%);">
      <!-- Messages will be rendered here -->
    </div>

    <!-- Chat Input - Enhanced -->
    <div style="padding:14px;background:#fff;border-top:1px solid #e5e7eb;display:flex;gap:10px;align-items:center;">
      <input type="text" class="cw-input" placeholder="Nhập tin nhắn..." style="flex:1;padding:12px 16px;border:2px solid #e2e8f0;border-radius:24px;font-size:14px;outline:none;transition:border-color 0.2s;">
      <button class="cw-send" style="background:linear-gradient(135deg, #2f9e91 0%, #25877a 100%);color:#fff;border:none;border-radius:50%;width:44px;height:44px;cursor:pointer;font-weight:500;display:flex;align-items:center;justify-content:center;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 2px 8px rgba(47,158,145,0.3);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
      </button>
    </div>
  </div>
</div>

<!-- Chat Toggle Button (Floating) - Green Theme -->
<button id="chat-toggle-btn" style="position:fixed;bottom:20px;right:20px;z-index:9998;min-width:120px;height:50px;border-radius:25px;background:#2f9e91;color:#fff;border:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);cursor:pointer;font-size:16px;font-weight:500;display:flex;align-items:center;justify-content:center;padding:0 20px;">
  💬 Message
</button>

<style>
/* Chat Widget - Green Theme with Messenger Layout */
.cw-msg {
  margin-bottom: 8px;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 10px;
}

/* User messages on RIGHT side */
.cw-msg.user {
  flex-direction: row-reverse;
}

/* Bot messages on LEFT side */
.cw-msg.bot {
  flex-direction: row;
}

/* Avatar styling */
.cw-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.cw-msg.user .cw-avatar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.cw-msg.bot .cw-avatar {
  background: linear-gradient(135deg, #2f9e91 0%, #25877a 100%);
}

/* Message content wrapper */
.cw-content {
  display: flex;
  flex-direction: column;
  max-width: 70%;
}

.cw-msg.user .cw-content {
  align-items: flex-end;
}

.cw-msg.bot .cw-content {
  align-items: flex-start;
}

/* Sender name label */
.cw-sender {
  font-size: 11px;
  font-weight: 600;
  color: #666;
  margin-bottom: 3px;
  padding: 0 4px;
}

.cw-msg.user .cw-sender {
  color: #764ba2;
}

.cw-msg.bot .cw-sender {
  color: #2f9e91;
}

/* Message bubble */
.cw-bubble {
  padding: 12px 16px;
  border-radius: 18px;
  word-wrap: break-word;
  font-size: 14px;
  line-height: 1.5;
  position: relative;
}

/* User bubble - Purple gradient on RIGHT */
.cw-msg.user .cw-bubble {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border-bottom-right-radius: 6px;
  box-shadow: 0 3px 12px rgba(102, 126, 234, 0.35);
}

/* Bot bubble - White/Green on LEFT */
.cw-msg.bot .cw-bubble {
  background: #fff;
  color: #333;
  border: 1px solid #e2e8f0;
  border-bottom-left-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  white-space: pre-wrap;
}

/* Timestamp styling */
.cw-time {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 4px;
  padding: 0 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.cw-msg.user .cw-time {
  justify-content: flex-end;
}

.cw-msg.bot .cw-time {
  justify-content: flex-start;
}

/* Input focus state */
.cw-input:focus {
  border-color: #2f9e91 !important;
  box-shadow: 0 0 0 3px rgba(47, 158, 145, 0.15);
}

/* Booking form styling - ensure date/time inputs are visible */
.cw-date, .cw-time {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-color: #fff !important;
  color: #333 !important;
  cursor: pointer;
}

.cw-date::-webkit-calendar-picker-indicator,
.cw-time::-webkit-calendar-picker-indicator {
  cursor: pointer;
  background-color: transparent;
  padding: 5px;
  opacity: 1;
}

/* Send button hover */
.cw-send:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(47, 158, 145, 0.4);
}

/* Scrollbar styling */
.cw-body::-webkit-scrollbar {
  width: 6px;
}

.cw-body::-webkit-scrollbar-track {
  background: transparent;
}

.cw-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.cw-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Typing indicator animation */
.cw-typing {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
}

.cw-typing span {
  width: 8px;
  height: 8px;
  background: #94a3b8;
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out;
}

.cw-typing span:nth-child(2) { animation-delay: 0.2s; }
.cw-typing span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
  0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
  40% { transform: scale(1); opacity: 1; }
}

/* Table styling for product comparison */
.cw-table {
  width: 100%;
  border-collapse: collapse;
  margin: 10px 0;
  font-size: 12px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.cw-table thead {
  background: linear-gradient(135deg, #2f9e91 0%, #25877a 100%);
  color: #fff;
}

.cw-table th {
  padding: 10px 8px;
  text-align: left;
  font-weight: 600;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cw-table td {
  padding: 8px;
  border-bottom: 1px solid #e5e7eb;
  background: #fff;
  vertical-align: top;
}

.cw-table tbody tr:last-child td {
  border-bottom: none;
}

.cw-table tbody tr:nth-child(even) td {
  background: #f8fafb;
}

.cw-table tbody tr:hover td {
  background: #e8f5f3;
}

/* Make table responsive in chat bubble */
.cw-bubble .cw-table {
  max-width: 100%;
  display: block;
  overflow-x: auto;
}
</style>

<script>
// Chat Widget Toggle
(function() {
  const widget = document.getElementById('chat-widget');
  const toggleBtn = document.getElementById('chat-toggle-btn');
  const toggle = document.getElementById('chat-toggle');
  
  if (toggleBtn && widget && toggle) {
    let chatLoaded = false;
    
    toggleBtn.addEventListener('click', () => {
      if (widget.style.display === 'none' || widget.style.display === '') {
        // Show chat widget
        widget.style.display = 'flex';
        toggleBtn.style.display = 'none';
        
        // Load chat history only on first click
        if (!chatLoaded) {
          chatLoaded = true;
          // Call loadChatOnClick if it exists (from chat_widget.js)
          if (typeof loadChatOnClick === 'function') {
            setTimeout(() => {
              loadChatOnClick();
            }, 100); // Small delay to ensure DOM is ready
          }
        }
      } else {
        // Hide chat widget
        widget.style.display = 'none';
        toggleBtn.style.display = 'flex';
      }
    });
    
    toggle.addEventListener('click', () => {
      widget.style.display = 'none';
      toggleBtn.style.display = 'flex';
    });
  }
})();
</script>

