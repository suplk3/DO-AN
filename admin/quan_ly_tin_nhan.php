<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý Tin nhắn</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body.admin-dark { background: radial-gradient(circle at 10% 20%, rgba(239, 68, 68, 0.1), transparent 40%), linear-gradient(135deg, #050816 0%, #0a1024 40%, #081226 100%); color: #e2e8f0; font-family: 'Trebuchet MS', 'Segoe UI', sans-serif; margin: 0; min-height: 100vh;}
    .chat-container { display: flex; height: calc(100vh - 60px); max-width: 1200px; margin: 30px auto; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(59,130,246,0.2); box-shadow: 0 15px 40px rgba(0,0,0,0.6); overflow: hidden;}
    .chat-sidebar { width: 320px; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; background: rgba(0,0,0,0.2); }
    .chat-main { flex: 1; display: flex; flex-direction: column; }
    .chat-header { padding: 20px; background: rgba(0,0,0,0.3); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;}
    .chat-user-list { list-style: none; margin: 0; padding: 0; overflow-y: auto; flex: 1; }
    .chat-user-list::-webkit-scrollbar { width: 4px; }
    .chat-user-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    .chat-user-item { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.02); cursor: pointer; transition: background 0.2s;}
    .chat-user-item:hover { background: rgba(255,255,255,0.05); }
    .chat-user-item.active { background: rgba(59,130,246,0.2); border-left: 4px solid #3b82f6; }
    .chat-user-item h4 { margin: 0 0 6px 0; font-size: 16px; color: #fff;}
    .chat-user-item p { margin: 0; font-size: 13px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
    
    .messages-area { flex: 1; padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; }
    .messages-area::-webkit-scrollbar { width: 6px; }
    .messages-area::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    
    .msg { max-width: 65%; padding: 12px 16px; border-radius: 16px; color: #fff; font-size: 15px; line-height: 1.5; box-shadow: 0 2px 8px rgba(0,0,0,0.2);}
    .msg.admin { align-self: flex-end; background: #3b82f6; border-bottom-right-radius: 4px; border: 1px solid rgba(59,130,246,0.5); }
    .msg.user { align-self: flex-start; background: rgba(255,255,255,0.1); border-bottom-left-radius: 4px; border: 1px solid rgba(255,255,255,0.05); }
    .msg .time { display: block; font-size: 11px; margin-top: 8px; opacity: 0.6; text-align: right;}
    
    .empty-chat { flex: 1; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 16px; flex-direction: column; gap: 10px;}
    
    .chat-input-area { padding: 20px; background: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.05); display: flex; gap: 14px; align-items: center;}
    .chat-input-area input { flex: 1; padding: 14px 20px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: white; outline: none; font-size: 15px; transition: border-color 0.2s;}
    .chat-input-area input:focus { border-color: #3b82f6; background: rgba(255,255,255,0.08); }
    .chat-input-area button { padding: 14px 28px; border-radius: 30px; border: none; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(59,130,246,0.3);}
    .chat-input-area button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,0.4); }
    
    .btn-back { color: #93c5fd; text-decoration: none; font-size: 14px; border: 1px solid rgba(147,197,253,0.3); padding: 8px 14px; border-radius: 8px; transition: all 0.2s;}
    .btn-back:hover { background: rgba(147,197,253,0.1); border-color: #93c5fd; }
  </style>
</head>
<body class="admin-dark">
<div class="chat-container">
  <div class="chat-sidebar">
    <div class="chat-header">
       <h3 style="margin:0; font-size: 20px; color: #fff; font-weight: 800;">Tất cả tin nhắn</h3>
    </div>
    <ul class="chat-user-list" id="userList">
       <!-- render qua ajax -->
       <div style="padding: 20px; color: #94a3b8; text-align: center;">Đang tải...</div>
    </ul>
  </div>
  
  <div class="chat-main" id="chatMain" style="display:none;">
    <div class="chat-header">
        <h3 style="margin:0; font-size: 18px; color: #fff;" id="currentChatName">Chọn một người dùng</h3>
        <a href="dashboard.php" class="btn-back">⬅ Dashboard</a>
    </div>
    <div class="messages-area" id="messagesArea"></div>
    <div class="chat-input-area">
        <input type="text" id="adminInput" placeholder="Nhập tin nhắn trả lời..." onkeypress="handleKey(event)">
        <button onclick="sendAdminMsg()">GỬI</button>
    </div>
  </div>
  
  <div class="chat-main" id="chatEmpty">
    <div class="chat-header">
        <h3 style="margin:0; font-size: 18px; color: #fff;">Tin nhắn</h3>
        <a href="dashboard.php" class="btn-back">⬅ Dashboard</a>
    </div>
    <div class="empty-chat">
        <span style="font-size: 40px;">💬</span>
        <span>Chọn một cuộc hội thoại bên trái để bắt đầu.</span>
    </div>
  </div>
</div>

<script>
let lastMsgCount = 0;

function loadUsers() {
    fetch('../api/ho_tro_api.php?action=get_conversations')
    .then(r=>r.json())
    .then(res => {
        if(res.success) {
            if(res.data.length === 0) {
                document.getElementById('userList').innerHTML = '<div style="padding: 20px; color: #94a3b8; text-align: center;">Chưa có tin nhắn nào.</div>';
                return;
            }
            const html = res.data.map(u => `
                <li class="chat-user-item ${currentUserId == u.id ? 'active' : ''}" onclick="selectUser(${u.id}, '${u.ten}')">
                    <h4>${u.ten}</h4>
                    <p>${u.last_msg} - <i>${u.last_time}</i></p>
                </li>
            `).join('');
            document.getElementById('userList').innerHTML = html;
        }
    });
}

function selectUser(id, name) {
    currentUserId = id;
    lastMsgCount = 0;
    document.getElementById('currentChatName').innerText = name;
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatMain').style.display = 'flex';
    document.querySelectorAll('.chat-user-item').forEach(el => el.classList.remove('active'));
    loadMessages();
}

function loadMessages() {
    if(!currentUserId) return;
    fetch('../api/ho_tro_api.php?action=get_messages&user_id='+currentUserId)
    .then(r=>r.json())
    .then(res => {
        if(res.success && res.data.length !== lastMsgCount) {
            lastMsgCount = res.data.length;
            const html = res.data.map(m => `
                <div class="msg ${m.is_admin === 1 ? 'admin' : 'user'}">
                    ${m.message}
                    <span class="time">${m.created_at}</span>
                </div>
            `).join('');
            const box = document.getElementById('messagesArea');
            box.innerHTML = html;
            box.scrollTop = box.scrollHeight;
        }
    });
}

function sendAdminMsg() {
    if(!currentUserId) return;
    const inp = document.getElementById('adminInput');
    const msg = inp.value.trim();
    if(!msg) return;
    inp.value = '';
    
    const fd = new FormData();
    fd.append('message', msg);
    fd.append('user_id', currentUserId);
    fetch('../api/ho_tro_api.php?action=send', {method:'POST', body:fd})
    .then(() => { loadMessages(); loadUsers(); });
}

function handleKey(e) { if(e.key === 'Enter') sendAdminMsg(); }

// Tracking mechanism to poll for new chats and current active chat
setInterval(() => {
    loadUsers();
    if(currentUserId !== 0) loadMessages();
}, 10000);

let currentUserId = new URLSearchParams(window.location.search).get('user_id') || 0;
if (currentUserId > 0) {
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatMain').style.display = 'flex';
    document.getElementById('currentChatName').innerText = "Hỗ trợ ID: " + currentUserId;
    loadMessages();
}

loadUsers();
</script>
</body>
</html>
