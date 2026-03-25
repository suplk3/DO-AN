<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$uid = (int)$_SESSION['user_id'];
$active_page = 'notifications';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Thông báo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/user-index.css">
  <link rel="stylesheet" href="../assets/css/login-modal.css">
  <link rel="stylesheet" href="../assets/css/search.css">
  <link rel="stylesheet" href="../assets/css/user-menu.css">
  <link rel="stylesheet" href="../assets/css/theme-toggle.css">
  <style>
    body { background:var(--bg); color:var(--text); font-family: "DM Sans", system-ui, sans-serif; }
    .wrap { max-width: 900px; margin: 30px auto; padding: 0 16px 40px; }
    .title { font-size: 26px; font-weight: 800; margin-bottom: 16px; }
    .toolbar { display:flex; gap:10px; align-items:center; margin-bottom: 16px; }
    .btn { padding:8px 14px; border-radius:8px; border:1px solid var(--border); background:var(--surface); color:var(--text); cursor:pointer; font-weight:600; text-decoration: none; font-size: 14px; }
    .btn:hover { background: rgba(255,255,255,0.05); }
    .btn.primary { background:#e50914; border-color:#e50914; color:#fff; }
    .noti-list { display:flex; flex-direction:column; gap:10px; }
    .noti-item { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:12px 14px; display:flex; gap:12px; align-items:flex-start; }
    .noti-item.unread { border-color:#e50914; box-shadow:0 0 0 1px rgba(229,9,20,.25); }
    .noti-dot { width:8px; height:8px; border-radius:50%; background:#e50914; margin-top:6px; flex-shrink:0; }
    .noti-body { flex:1; }
    .noti-title { font-weight:700; margin-bottom:4px; font-size: 16px; }
    .noti-text { margin-bottom:4px; font-size: 14px; color: var(--muted); }
    .noti-meta { color:var(--muted); font-size:12px; }
    .noti-actions { display:flex; gap:8px; }
    .empty { padding:30px; text-align:center; color:var(--muted); border:1px dashed var(--border); border-radius:12px; }
    /* Fix header overlap */
    main { padding-top: 80px; }
  </style>
  <link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body>
  <?php include 'components/header.php'; ?>
  
  <main class="wrap">
    <div class="title">🔔 Thông báo của bạn</div>
    <div class="toolbar">
      <a href="index.php" class="btn">← Về trang chủ</a>
      <button class="btn primary" onclick="markAllRead()">Đánh dấu đã đọc tất cả</button>
    </div>

    <!-- The actual list will be rendered via JS listening to the header's polling event -->
    <div id="notiListWrapper">
      <div class="empty">Đang tải thông báo...</div>
    </div>
  </main>

<script>
window.addEventListener('notifications_polled', function(e) {
    const list = document.getElementById('notiListWrapper');
    if (!list) return;
    
    const notifs = e.detail.notifications;
    if (!notifs || notifs.length === 0) {
        list.innerHTML = '<div class="empty">Bạn chưa có thông báo nào.</div>';
        return;
    }
    
    let html = '<div class="noti-list" id="notiList">';
    notifs.forEach(n => {
        let unreadCls = (parseInt(n.is_read) === 0) ? 'unread' : '';
        let dot = (parseInt(n.is_read) === 0) ? '<div class="noti-dot"></div>' : '';
        let bodyHtml = n.body ? `<div class="noti-text">${n.body}</div>` : '';
        let linkHtml = n.link ? `<a class="btn primary" href="${n.link}">Mở xem</a>` : '';
        let readBtn = (parseInt(n.is_read) === 0) ? `<button class="btn" onclick="markRead(${n.id})">Đã đọc</button>` : '';
        
        html += `
          <div class="noti-item ${unreadCls}" id="noti-${n.id}">
            ${dot}
            <div class="noti-body">
              <div class="noti-title">${n.title}</div>
              ${bodyHtml}
              <div class="noti-meta">${n.time_ago}</div>
            </div>
            <div class="noti-actions">
              ${linkHtml}
              ${readBtn}
            </div>
          </div>
        `;
    });
    html += '</div>';
    list.innerHTML = html;
});

async function markRead(id) {
    fetch('../api/notif_poll_api.php?action=mark_one_read', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const el = document.getElementById('noti-' + id);
            if (el) {
                el.classList.remove('unread');
                el.querySelector('.noti-dot')?.remove();
                el.querySelectorAll('.btn').forEach(btn => {
                    if (btn.innerText === 'Đã đọc') btn.remove();
                });
            }
        }
    });
}

async function markAllRead() {
    fetch('../api/notif_poll_api.php?action=mark_read').then(r => r.json()).then(data => {
        if (data.success) {
            document.querySelectorAll('.noti-item').forEach(el => {
                el.classList.remove('unread');
                el.querySelector('.noti-dot')?.remove();
                el.querySelectorAll('.btn').forEach(btn => {
                    if (btn.innerText === 'Đã đọc') btn.remove();
                });
            });
        }
    });
}
</script>
<script src="../assets/js/search.js"></script>
<script src="../assets/js/login-modal.js"></script>
<script>
// User dropdown toggle
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', e => {
        e.stopPropagation();
        userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown.classList.remove('open'));
}
</script>
</body>
</html>
