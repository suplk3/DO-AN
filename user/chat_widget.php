<?php if (isset($_SESSION['user_id']) && (!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'admin')): ?>
<div id="chat-widget" class="chat-widget">
    <div class="chat-header" onclick="toggleChat()">
        <span>💬 Trò chuyện với Admin</span>
        <span class="chat-close" id="chatCloseIcon">▲</span>
    </div>
    <div class="chat-body" id="chatBody"></div>
    <div class="chat-footer">
        <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." onkeypress="handleChatKey(event)">
        <button onclick="sendChatMessage()">Gửi</button>
    </div>
</div>

<style>
/* Glassmorphism cho Chat widget */
.chat-widget {
    position: fixed; bottom: 20px; right: 20px; width: 330px;
    background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.6); z-index: 9999;
    display: flex; flex-direction: column; overflow: hidden;
    transform: translateY(calc(100% - 50px)); transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    font-family: "Segoe UI", sans-serif;
}
.chat-widget.open { transform: translateY(0); }
.chat-header {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white; padding: 14px 18px; cursor: pointer;
    font-weight: 700; font-size: 15px; letter-spacing: 0.5px;
    display: flex; justify-content: space-between; align-items: center;
}
.chat-body {
    height: 320px; padding: 16px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 10px;
}
.chat-body::-webkit-scrollbar { width: 6px; }
.chat-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
.chat-msg {
    max-width: 82%; padding: 10px 14px; border-radius: 18px; font-size: 14px; color: white; line-height: 1.4;
}
.chat-msg.admin {
    align-self: flex-start; background: rgba(255,255,255,0.1); border-bottom-left-radius: 4px;
}
.chat-msg.user {
    align-self: flex-end; background: #3b82f6; border-bottom-right-radius: 4px; border: 1px solid rgba(59,130,246,0.5);
}
.chat-time { display: block; font-size: 11px; opacity: 0.6; margin-top: 6px; text-align: right; }
.chat-footer {
    display: flex; padding: 14px; border-top: 1px solid rgba(255,255,255,0.05); gap: 10px; background: rgba(0,0,0,0.2);
}
.chat-footer input {
    flex: 1; padding: 10px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05); color: white; outline: none; font-family: inherit;
}
.chat-footer input:focus { border-color: rgba(59,130,246,0.6); background: rgba(255,255,255,0.1); }
.chat-footer button {
    padding: 10px 18px; border-radius: 20px; border: none;
    background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; cursor: pointer; font-weight: 700;
    transition: transform 0.2s, box-shadow 0.2s;
}
.chat-footer button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
</style>
<script>
let chatOpen = false;
let lastMsgCount = 0;

function toggleChat() {
    const w = document.getElementById('chat-widget');
    const icon = document.getElementById('chatCloseIcon');
    chatOpen = !chatOpen;
    w.classList.toggle('open', chatOpen);
    icon.innerText = chatOpen ? '▼' : '▲';
    if(chatOpen) fetchChatMessages();
}

function fetchChatMessages() {
    fetch('../api/ho_tro_api.php?action=get_messages')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                if(res.data.length !== lastMsgCount) {
                    renderChat(res.data);
                    lastMsgCount = res.data.length;
                    const b = document.getElementById('chatBody');
                    b.scrollTop = b.scrollHeight;
                }
            }
        });
}

function renderChat(msgs) {
    if (msgs.length === 0) {
        document.getElementById('chatBody').innerHTML = '<div style="color:rgba(255,255,255,0.5); text-align:center; margin-top:20px; font-size:13px;">Hãy gửi tin nhắn để bắt đầu trò chuyện.</div>';
        return;
    }
    const html = msgs.map(m => `
        <div class="chat-msg ${m.is_admin === 1 ? 'admin' : 'user'}">
            ${m.message}
            <span class="chat-time">${m.created_at}</span>
        </div>
    `).join('');
    document.getElementById('chatBody').innerHTML = html;
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if(!msg) return;
    input.value = '';
    
    const fd = new FormData();
    fd.append('message', msg);
    
    fetch('../api/ho_tro_api.php?action=send', { method: 'POST', body: fd })
        .then(() => fetchChatMessages());
}

function handleChatKey(e) {
    if(e.key === 'Enter') sendChatMessage();
}

// Auto poll when open
setInterval(() => {
    if(chatOpen) fetchChatMessages();
}, 2500);
</script>
<?php endif; ?>
