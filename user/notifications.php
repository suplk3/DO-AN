<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$uid = (int)$_SESSION['user_id'];

$notifications = [];
if (table_exists($conn, 'notifications')) {
    $noti_rs = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC");
    while ($row = mysqli_fetch_assoc($noti_rs)) $notifications[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Thông báo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { background:#0b0f1a; color:#e2e8f0; font-family: "DM Sans", system-ui, sans-serif; }
    .wrap { max-width: 900px; margin: 30px auto; padding: 0 16px 40px; }
    .title { font-size: 26px; font-weight: 800; margin-bottom: 16px; }
    .toolbar { display:flex; gap:10px; align-items:center; margin-bottom: 16px; }
    .btn { padding:8px 14px; border-radius:8px; border:1px solid #334155; background:#111827; color:#e2e8f0; cursor:pointer; font-weight:600; }
    .btn.primary { background:#2563eb; border-color:#2563eb; }
    .noti-list { display:flex; flex-direction:column; gap:10px; }
    .noti-item { background:#0f172a; border:1px solid #1f2937; border-radius:12px; padding:12px 14px; display:flex; gap:12px; align-items:flex-start; }
    .noti-item.unread { border-color:#60a5fa; box-shadow:0 0 0 1px rgba(96,165,250,.25); }
    .noti-dot { width:8px; height:8px; border-radius:50%; background:#60a5fa; margin-top:6px; flex-shrink:0; }
    .noti-body { flex:1; }
    .noti-title { font-weight:700; margin-bottom:4px; }
    .noti-meta { color:#94a3b8; font-size:12px; }
    .noti-actions { display:flex; gap:8px; }
    .empty { padding:30px; text-align:center; color:#94a3b8; border:1px dashed #334155; border-radius:12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="title">🔔 Thông báo của bạn</div>
    <div class="toolbar">
      <a href="index.php" class="btn">← Về trang chủ</a>
      <button class="btn primary" onclick="markAllRead()">Đánh dấu đã đọc tất cả</button>
    </div>

    <?php if (empty($notifications)): ?>
      <div class="empty">Bạn chưa có thông báo nào.</div>
    <?php else: ?>
      <div class="noti-list" id="notiList">
        <?php foreach ($notifications as $n): ?>
          <div class="noti-item <?= (int)$n['is_read'] === 0 ? 'unread' : '' ?>" id="noti-<?= (int)$n['id'] ?>">
            <?php if ((int)$n['is_read'] === 0): ?><div class="noti-dot"></div><?php endif; ?>
            <div class="noti-body">
              <div class="noti-title"><?= htmlspecialchars($n['title']) ?></div>
              <?php if (!empty($n['body'])): ?>
                <div class="noti-text"><?= htmlspecialchars($n['body']) ?></div>
              <?php endif; ?>
              <div class="noti-meta"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></div>
            </div>
            <div class="noti-actions">
              <?php if (!empty($n['link'])): ?>
                <a class="btn" href="<?= htmlspecialchars($n['link']) ?>">Mở</a>
              <?php endif; ?>
              <?php if ((int)$n['is_read'] === 0): ?>
                <button class="btn" onclick="markRead(<?= (int)$n['id'] ?>)">Đã đọc</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

<script>
async function markRead(id) {
  const res = await fetch('notification_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'read', id})
  });
  const data = await res.json();
  if (data.ok) {
    const el = document.getElementById('noti-' + id);
    if (el) el.classList.remove('unread');
  }
}

async function markAllRead() {
  const res = await fetch('notification_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'read_all'})
  });
  const data = await res.json();
  if (data.ok) {
    document.querySelectorAll('.noti-item').forEach(el => el.classList.remove('unread'));
  }
}
</script>
</body>
</html>
