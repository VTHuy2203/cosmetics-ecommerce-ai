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
      <div class="cw-header-actions">
        <button type="button" class="cw-new-chat" title="Bắt đầu cuộc trò chuyện mới" aria-label="Chat mới">
          <i class="fas fa-plus"></i><span>Chat mới</span>
        </button>
        <button type="button" class="cw-delete-chat" title="Xóa cuộc trò chuyện hiện tại" aria-label="Xóa chat">
          <i class="fas fa-trash-alt"></i>
        </button>
        <button type="button" id="chat-toggle" title="Thu nhỏ" aria-label="Thu nhỏ chatbot" style="background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:18px;cursor:pointer;padding:6px 10px;border-radius:8px;transition:background 0.2s;">−</button>
      </div>
    </div>

    <!-- Promotional Banner inside Chat -->
    <div class="cw-promo-banner" style="background:linear-gradient(90deg, #667eea 0%, #764ba2 100%);color:#fff;padding:10px 14px;font-size:12px;display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="window.location.href='View_Product.php'">
      <span style="font-size:16px;">🎁</span>
      <span><strong>Tư vấn mỹ phẩm</strong> theo loại da và nhu cầu của bạn</span>
      <span style="margin-left:auto;font-size:10px;opacity:0.8;">Xem ngay →</span>
    </div>

    <!-- Chat Body -->
    <div class="cw-body" role="log" aria-live="polite" aria-label="Nội dung cuộc trò chuyện" style="flex:1;overflow-y:auto;padding:16px;background:linear-gradient(180deg, #f0f4f8 0%, #e8ecf1 100%);">
      <!-- Messages will be rendered here -->
    </div>

    <div class="cw-quick-replies" aria-label="Gợi ý sản phẩm">
      <span class="cw-quick-label">Gợi ý nhanh</span>
      <div class="cw-quick-list">
        <button type="button" class="cw-quick-reply" data-message="Tư vấn son môi phù hợp với tôi">💄 Son môi</button>
        <button type="button" class="cw-quick-reply" data-message="Tư vấn kem chống nắng phù hợp với tôi">☀️ Kem chống nắng</button>
        <button type="button" class="cw-quick-reply" data-message="Tư vấn sữa rửa mặt phù hợp với tôi">🫧 Sữa rửa mặt</button>
      </div>
    </div>

    <!-- Chat Input - Enhanced -->
    <div class="cw-composer" style="padding:14px;background:#fff;border-top:1px solid #e5e7eb;display:flex;gap:10px;align-items:center;">
      <input type="text" class="cw-input" placeholder="Nhập tin nhắn..." style="flex:1;padding:12px 16px;border:2px solid #e2e8f0;border-radius:24px;font-size:14px;outline:none;transition:border-color 0.2s;">
      <button class="cw-send" type="button" aria-label="Gửi tin nhắn" style="background:linear-gradient(135deg, #2f9e91 0%, #25877a 100%);color:#fff;border:none;border-radius:50%;width:44px;height:44px;cursor:pointer;font-weight:500;display:flex;align-items:center;justify-content:center;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 2px 8px rgba(47,158,145,0.3);">
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

/* Booking form inside a bot bubble (the bubble itself is white-space: pre-wrap). */
#chat-widget .cw-booking {
  white-space: normal;
  margin-top: 10px;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

#chat-widget .cw-booking-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

#chat-widget .cw-booking-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin: 0;
  min-width: 0;
}

#chat-widget .cw-booking-field > span,
#chat-widget .cw-booking-title {
  font-size: 12px;
  font-weight: 600;
  line-height: 1.3;
  color: #475569;
}

#chat-widget .cw-booking-input {
  width: 100%;
  min-width: 0;
  height: 40px;
  margin: 0;
  padding: 8px 10px;
  border: 1.5px solid #2f9e91;
  border-radius: 8px;
  background: #fff !important;
  color: #1f2937 !important;
  font-size: 14px;
  line-height: 1.2;
  box-sizing: border-box;
}

#chat-widget .cw-booking-input:focus {
  outline: none;
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(47, 158, 145, .18);
}

#chat-widget .cw-booking-contact {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 10px;
  background: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 8px;
}

#chat-widget .cw-booking-title {
  color: #92400e;
}

#chat-widget .cw-book {
  width: 100%;
  padding: 10px 16px;
  border: 0;
  border-radius: 8px;
  background: #2f9e91;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background .2s;
}

#chat-widget .cw-book:hover { background: #25877a; }
#chat-widget .cw-book:disabled { opacity: .6; cursor: wait; }

@media (max-width: 360px) {
  #chat-widget .cw-booking-row { grid-template-columns: 1fr; }
}

/* Booking form styling - ensure date/time inputs are visible */
.cw-date, .cw-book-time {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-color: #fff !important;
  color: #333 !important;
  cursor: pointer;
}

.cw-date::-webkit-calendar-picker-indicator,
.cw-book-time::-webkit-calendar-picker-indicator {
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

<!-- CHAT_UI_V2_BEGIN: remove this block to restore the previous chat appearance. -->
<style id="chat-conversation-ui-v2">
#chat-widget {
  width: 420px !important;
  bottom: 22px !important;
  right: 22px !important;
  font-family: Inter, Nunito, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

#chat-widget > div {
  height: 590px !important;
  max-height: calc(100vh - 70px) !important;
  border: 1px solid rgba(15, 118, 110, .12);
  border-radius: 20px !important;
  box-shadow: 0 18px 55px rgba(15, 23, 42, .22) !important;
}

#chat-widget > div > div:first-child {
  min-height: 70px;
  padding: 13px 16px !important;
  background: linear-gradient(135deg, #14877c 0%, #0f766e 100%) !important;
}

#chat-widget > div > div:first-child strong {
  display: block;
  margin-bottom: 2px;
  letter-spacing: -.01em;
}

#chat-widget .cw-header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

#chat-widget .cw-header-actions button {
  height: 34px;
  border: 1px solid rgba(255, 255, 255, .2);
  border-radius: 9px;
  background: rgba(255, 255, 255, .14);
  color: #fff;
  cursor: pointer;
  transition: background .2s ease, transform .2s ease;
}

#chat-widget .cw-header-actions button:hover {
  background: rgba(255, 255, 255, .25);
  transform: translateY(-1px);
}

#chat-widget .cw-new-chat {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 0 9px;
  font-size: 11px;
  font-weight: 700;
}

#chat-widget .cw-delete-chat {
  width: 34px;
  padding: 0;
}

#chat-widget .cw-promo-banner {
  min-height: 40px;
  padding: 8px 14px !important;
  background: #f4f0ff !important;
  border-bottom: 1px solid #e9e2fb;
  color: #5b3d91 !important;
  line-height: 1.35;
}

#chat-widget .cw-body {
  padding: 18px 14px 20px !important;
  background: #eaf0f3 !important;
  scroll-behavior: smooth;
}

#chat-widget .cw-session-context {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 14px;
  padding: 8px 11px;
  border: 1px solid #d6e2e6;
  border-radius: 11px;
  background: rgba(255, 255, 255, .72);
  color: #52616d;
  font-size: 11px;
}

#chat-widget .cw-session-context-label {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 7px;
  color: #285e59;
  font-weight: 700;
}

#chat-widget .cw-session-context-label span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

#chat-widget .cw-session-context small {
  flex: 0 0 auto;
  color: #84929e;
  font-size: 10px;
}

#chat-widget .cw-appointment-notice {
  display: flex;
  align-items: center;
  gap: 9px;
  margin: 0 0 16px;
  padding: 10px 12px;
  border: 1px solid #f5d58b;
  border-radius: 12px;
  background: #fff8e8;
  color: #6f5314;
  font-size: 12px;
  line-height: 1.4;
}

#chat-widget .cw-appointment-notice i {
  color: #d99b14;
  font-size: 15px;
}

#chat-widget .cw-appointment-notice a {
  margin-left: auto;
  color: #87600b;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
}

#chat-widget .cw-empty-state {
  display: flex !important;
  min-height: 230px;
  align-items: center !important;
  justify-content: center !important;
  flex-direction: column !important;
  padding: 25px;
  color: #718096;
  text-align: center;
}

#chat-widget .cw-empty-state-icon {
  display: flex !important;
  width: 54px !important;
  height: 54px !important;
  align-items: center !important;
  justify-content: center !important;
  margin-bottom: 12px;
  border: 1px solid #cae4e0;
  border-radius: 50%;
  background: #e8f5f3;
  color: #14877c;
  font-size: 22px;
}

#chat-widget .cw-empty-state strong {
  display: block;
  margin-bottom: 4px;
  color: #344451;
  font-size: 14px;
}

#chat-widget .cw-empty-state > span {
  display: block;
  max-width: 260px;
  line-height: 1.5;
}

#chat-widget .cw-msg {
  display: flex !important;
  width: 100%;
  align-items: flex-end !important;
  gap: 8px;
  margin-bottom: 18px;
}

#chat-widget .cw-msg.bot {
  flex-direction: row !important;
  justify-content: flex-start !important;
}

#chat-widget .cw-msg.user {
  flex-direction: row-reverse !important;
  justify-content: flex-start !important;
}

#chat-widget .cw-avatar {
  width: 30px;
  height: 30px;
  margin-top: 19px;
  border: 2px solid #fff;
  font-size: 14px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, .12);
}

#chat-widget .cw-msg.user .cw-avatar {
  display: flex !important;
  margin-top: 0;
  border-color: #dbeafe;
  background: #2563eb !important;
  color: #fff;
}

#chat-widget .cw-msg.bot .cw-avatar {
  margin-top: 0;
  background: #fff !important;
  color: #14877c;
  border-color: #b9ddd8;
}

#chat-widget .cw-content {
  max-width: 84%;
}

#chat-widget .cw-msg.user .cw-content {
  max-width: 78%;
  align-items: flex-end !important;
}

#chat-widget .cw-sender {
  margin-bottom: 4px;
  padding: 0 5px;
  color: #64748b;
  font-size: 11px;
}

#chat-widget .cw-msg.user .cw-sender {
  display: block !important;
  color: #1d4ed8 !important;
  font-weight: 700;
}

#chat-widget .cw-msg.bot .cw-sender {
  color: #0f766e !important;
  font-weight: 700;
}

#chat-widget .cw-bubble {
  padding: 10px 13px !important;
  border-radius: 17px !important;
  font-size: 14px !important;
  line-height: 1.55 !important;
}

#chat-widget .cw-msg.bot .cw-bubble {
  background: #fff !important;
  border: 1px solid #b9dcd7 !important;
  border-bottom-left-radius: 4px !important;
  color: #25313c !important;
  box-shadow: 0 4px 13px rgba(15, 23, 42, .09);
}

#chat-widget .cw-msg.user .cw-bubble {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
  color: #fff !important;
  border: 1px solid #1d4ed8 !important;
  border-bottom-right-radius: 4px !important;
  box-shadow: 0 4px 12px rgba(37, 99, 235, .22);
}

#chat-widget .cw-msg.user .cw-message-time {
  align-self: flex-end;
  color: #64748b !important;
}

#chat-widget .cw-quick-replies {
  padding: 10px 14px 9px;
  border-top: 1px solid #dce5e9;
  background: #fff;
}

#chat-widget .cw-quick-label {
  display: block;
  margin-bottom: 7px;
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
}

#chat-widget .cw-quick-list {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding-bottom: 2px;
  scrollbar-width: thin;
}

#chat-widget .cw-quick-reply {
  flex: 0 0 auto;
  min-height: 32px;
  padding: 5px 10px;
  border: 1px solid #9ed3cc;
  border-radius: 999px;
  background: #f2fbf9;
  color: #0f766e;
  cursor: pointer;
  font-size: 11px;
  font-weight: 700;
  transition: background .16s ease, color .16s ease, transform .16s ease;
}

#chat-widget .cw-quick-reply:hover,
#chat-widget .cw-quick-reply:focus-visible {
  background: #14877c;
  color: #fff;
  outline: none;
  transform: translateY(-1px);
}

#chat-widget .cw-quick-reply:disabled {
  cursor: wait;
  opacity: .55;
  transform: none;
}

/* Message timestamp (the booking time input uses cw-book-time). */
#chat-widget .cw-message-time {
  width: auto;
  height: auto;
  margin-top: 4px;
  padding: 0 5px;
  background: transparent !important;
  color: #8a98a8 !important;
  cursor: default;
  font-size: 10px;
}

#chat-widget .cw-table {
  margin: 8px 0;
  border: 1px solid #e2e8f0;
  box-shadow: none;
}

#chat-widget .cw-typing {
  min-width: 56px;
  padding: 11px 13px;
}

#chat-widget .cw-typing span {
  width: 6px;
  height: 6px;
  background: #6f8c88;
}

#chat-widget .cw-composer {
  min-height: 70px;
  padding: 12px 14px !important;
  border-top: 1px solid #e7edf0 !important;
  box-shadow: 0 -4px 16px rgba(15, 23, 42, .035);
}

#chat-widget .cw-input {
  min-width: 0;
  height: 44px;
  padding: 10px 15px !important;
  border: 1px solid #d7e0e5 !important;
  background: #f9fbfc;
  color: #25313c;
}

#chat-widget .cw-input:focus {
  border-color: #159084 !important;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(21, 144, 132, .12);
}

#chat-widget .cw-send {
  width: 44px !important;
  height: 44px !important;
  flex: 0 0 44px;
  background: #14877c !important;
}

#chat-widget .cw-send:disabled {
  cursor: wait;
  opacity: .62;
  transform: none;
}

@media (max-width: 575.98px) {
  #chat-widget {
    width: calc(100vw - 20px) !important;
    max-width: none !important;
    right: 10px !important;
    bottom: 10px !important;
  }

  #chat-widget > div {
    height: min(650px, calc(100vh - 20px)) !important;
    max-height: calc(100vh - 20px) !important;
    border-radius: 18px !important;
  }

  #chat-widget .cw-new-chat span {
    display: none;
  }

  #chat-widget .cw-new-chat {
    width: 34px;
    justify-content: center;
    padding: 0;
  }
}
</style>
<!-- CHAT_UI_V2_END -->

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

