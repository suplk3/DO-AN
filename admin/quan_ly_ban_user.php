<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['vai_tro'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql_base = "SELECT v.id AS ve_id,
                    v.user_id,
                    u.ten AS ten_user,
                    u.email AS email_user,
                    p.ten_phim,
                    sc.ngay,
                    sc.gio,
                    sc.gia,
                    r.ten_rap,
                    pc.ten_phong,
                    g.ten_ghe
             FROM ve v
             LEFT JOIN users u ON v.user_id = u.id
             LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
             LEFT JOIN phim p ON sc.phim_id = p.id
             LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
             LEFT JOIN rap r ON pc.rap_id = r.id
             LEFT JOIN ghe g ON v.ghe_id = g.id";

if ($search !== '') {
    $search_param = '%' . $search . '%';
    $sql = $sql_base . " WHERE (u.ten LIKE ? OR u.email LIKE ?) ORDER BY sc.ngay DESC, sc.gio DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $search_param, $search_param);
} else {
    $sql = $sql_base . " ORDER BY sc.ngay DESC, sc.gio DESC";
    $stmt = mysqli_prepare($conn, $sql);
}
if (!$stmt) die(mysqli_error($conn));
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ves = mysqli_fetch_all($result, MYSQLI_ASSOC);

$users_list = [];
if ($search !== '') {
    $u_stmt = mysqli_prepare($conn, "SELECT id, ten, email FROM users WHERE vai_tro = 'user' AND is_banned = 1 AND (ten LIKE ? OR email LIKE ?) ORDER BY ten");
    mysqli_stmt_bind_param($u_stmt, 'ss', $search_param, $search_param);
    mysqli_stmt_execute($u_stmt);
    $u_res = mysqli_stmt_get_result($u_stmt);
} else {
    $u_res = mysqli_query($conn, "SELECT id, ten, email FROM users WHERE vai_tro = 'user' AND is_banned = 1 ORDER BY ten");
}

if ($u_res) {
    while ($u = mysqli_fetch_assoc($u_res)) {
        $users_list[(int)$u['id']] = $u;
    }
}
$ves_by_user = [];
foreach ($ves as $v) {
    if (isset($users_list[(int)$v['user_id']])) {
        $uid = (int)$v['user_id'];
        $ves_by_user[$uid][] = $v;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý User bị cấm — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/search.css">
<style>
.admin-search-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
.admin-search-bar input { flex: 1; min-width: 220px; padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.06); color: #f1f5f9; font-size: 13px; outline: none; }
.admin-search-bar input:focus { border-color: rgba(232,25,44,.5); box-shadow: 0 0 0 3px rgba(232,25,44,.1); }
.admin-search-bar .btn-search { padding: 10px 18px; border-radius: 10px; border: none; background: linear-gradient(135deg,#e8192c,#c01020); color: #fff; font-weight: 700; font-size: 13px; cursor: pointer; transition: all .2s; }
.admin-search-bar .btn-clear { padding: 10px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: transparent; color: rgba(241,245,249,.6); font-size: 13px; cursor: pointer; text-decoration: none; font-weight: 600; transition: all .2s; }

.btn-header {
  padding: 10px 18px; border-radius: 10px; border: none; color: #fff; font-weight: 700; font-size: 13px; cursor: pointer; transition: all .3s cubic-bezier(0.25, 0.8, 0.25, 1); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
}
.btn-header-secondary {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #e2e8f0; backdrop-filter: blur(5px);
}
.btn-header-secondary:hover {
  background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.user-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px,1fr)); gap: 18px; }
.user-card { background: linear-gradient(135deg,#111827,#0d1322); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; overflow: hidden; opacity: 1; }
.user-card-header { display: flex; align-items: center; gap: 12px; padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg,rgba(124,58,237,.4),rgba(79,70,229,.3)); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; border: 1px solid rgba(124,58,237,.3); }
.user-name { font-size: 14px; font-weight: 800; color: #f1f5f9; }
.user-email { font-size: 12px; color: #64748b; margin-top: 2px; }
.user-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.2); color: #7dd3fc; }
.banned-actions {
  padding: 12px 16px; display: flex; gap: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);
}
.btn-banned {
  flex: 1; padding: 10px 16px; font-size: 13px; font-weight: 700; letter-spacing: 0.3px;
  border-radius: 10px; text-decoration: none; text-align: center; color: #fff;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
  display: flex; align-items: center; justify-content: center; gap: 8px;
  border: none; position: relative; overflow: hidden;
}
.btn-banned::after {
  content: ''; position: absolute; top:0; left:-100%; width:50%; height:100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transform: skewX(-20deg); transition: 0.5s;
}
.btn-banned:hover::after { left: 150%; }

.btn-unban {
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
}
.btn-unban:hover {
  transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
  filter: brightness(1.1); color: #fff;
}

.btn-delete {
  background: linear-gradient(135deg, #ef4444, #b91c1c);
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
}
.btn-delete:hover {
  transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
  filter: brightness(1.1); color: #fff;
}
.ticket-list { padding: 0 16px 8px; max-height: 200px; overflow-y: auto; }
.ticket-item { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); display: flex; flex-direction: column; gap: 4px; }
.ticket-movie { font-size: 13px; font-weight: 700; color: #f1f5f9; }
.ticket-detail { font-size: 11px; color: #64748b; display: flex; gap: 10px; flex-wrap: wrap; }
.no-tickets { padding: 14px 0; font-size: 12px; color: #475569; font-style: italic; }
</style>
</head>
<body class="user-index">

<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="../user/index.php" class="nav-link"><span class="icon">🎬</span><span class="text">TRANG CHỦ</span></a>
      </div>
      <div class="header-nav-right">
        <span class="hello"><span class="icon">🛡️</span><span class="text">Admin</span></span>
        <a href="../auth/logout.php" class="btn btn-sm btn-outline" onclick="return confirm('Đăng xuất?')"><span class="icon">🚪</span><span class="text">ĐĂNG XUẤT</span></a>
      </div>
    </nav>
  </div>
</header>

<main class="container">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <h1 class="page-title" style="margin-bottom:0; color:#ef4444;">🚫 Danh sách bị cấm</h1>
    <a href="quan_ly_user.php" class="btn-header btn-header-secondary">← Quay lại QL User</a>
  </div>

  <form method="get" class="admin-search-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="🔍 Tìm tên hoặc email...">
    <button type="submit" class="btn-search">Tìm kiếm</button>
  </form>

  <?php if (empty($users_list)): ?>
    <div style="text-align:center;padding:60px 20px;color:#64748b;">
      <div style="font-size:40px;margin-bottom:12px;opacity:.5;">✅</div>
      <div style="font-size:16px;font-weight:700;color:#94a3b8;margin-bottom:6px;">
        Chưa có người dùng nào bị cấm.
      </div>
    </div>
  <?php else: ?>
  <div class="user-grid">
    <?php foreach ($users_list as $uid => $user):
      $list = $ves_by_user[$uid] ?? [];
    ?>
    <div class="user-card" style="border-color: rgba(239,68,68,0.2);">
      <div class="user-card-header" style="background: rgba(239,68,68,0.05); border-bottom-color: rgba(239,68,68,0.1);">
        <div class="user-avatar" style="background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.5); color:#fca5a5;">🚫</div>
        <div style="flex:1;">
          <div class="user-name" style="color:#fca5a5;"><?= htmlspecialchars($user['ten'] ?? 'Khách') ?></div>
          <div class="user-email" style="color:#ef4444; opacity:0.8;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>
        <span class="user-badge" title="Số vé đã mua"><?= count($list) ?> vé</span>
      </div>
      <div class="banned-actions">
        <a href="unban_user.php?id=<?= $uid ?>" class="btn-banned btn-unban" onclick="return confirm('Gỡ cấm cho người dùng này? Mọi tính năng sẽ hoạt động lại bình thường.');"><span>🔓</span> Gỡ cấm</a>
        <a href="xoa_user.php?id=<?= $uid ?>" class="btn-banned btn-delete" onclick="return confirm('Xóa vĩnh viễn người dùng này?\n\nMọi dữ liệu đặt vé đều sẽ bị xóa sạch theo. Hành động này không thể hoàn tác!');"><span>✕</span> Xóa vĩnh viễn</a>
      </div>
      <div class="ticket-list">
        <?php if (empty($list)): ?>
          <div class="no-tickets">Chưa đặt vé nào.</div>
        <?php else: foreach ($list as $ve): ?>
          <div class="ticket-item">
            <div class="ticket-movie"><?= htmlspecialchars($ve['ten_phim']) ?></div>
            <div class="ticket-detail">
              <span>📅 <?= date('d/m/Y', strtotime($ve['ngay'])) ?></span>
              <span>⏰ <?= substr($ve['gio'],0,5) ?></span>
              <span>🪑 <?= htmlspecialchars($ve['ten_ghe']) ?></span>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas — Admin Panel</div></footer>
</body>
</html>
