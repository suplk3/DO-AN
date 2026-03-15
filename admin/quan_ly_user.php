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
    $u_stmt = mysqli_prepare($conn, "SELECT id, ten, email FROM users WHERE vai_tro = 'user' AND (ten LIKE ? OR email LIKE ?) ORDER BY ten");
    mysqli_stmt_bind_param($u_stmt, 'ss', $search_param, $search_param);
    mysqli_stmt_execute($u_stmt);
    $u_res = mysqli_stmt_get_result($u_stmt);
} else {
    $u_res = mysqli_query($conn, "SELECT id, ten, email FROM users WHERE vai_tro = 'user' ORDER BY ten");
}
if ($u_res) {
    while ($u = mysqli_fetch_assoc($u_res)) {
        $users_list[(int)$u['id']] = $u;
    }
}
$ves_by_user = [];
foreach ($ves as $v) {
    $uid = (int)$v['user_id'];
    $ves_by_user[$uid][] = $v;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý User — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/search.css">
<style>
.admin-search-bar {
  display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; align-items: center;
}
.admin-search-bar input {
  flex: 1; min-width: 220px;
  padding: 10px 14px; border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.06); color: #f1f5f9;
  font-family: 'Be Vietnam Pro', system-ui, sans-serif;
  font-size: 13px; outline: none;
}
.admin-search-bar input:focus {
  border-color: rgba(232,25,44,.5);
  box-shadow: 0 0 0 3px rgba(232,25,44,.1);
}
.admin-search-bar input::placeholder { color: rgba(255,255,255,.3); }
.admin-search-bar .btn-search {
  padding: 10px 18px; border-radius: 10px; border: none;
  background: linear-gradient(135deg,#e8192c,#c01020); color: #fff;
  font-family: 'Be Vietnam Pro', sans-serif; font-weight: 700;
  font-size: 13px; cursor: pointer; transition: all .2s;
}
.admin-search-bar .btn-search:hover { filter: brightness(1.1); }
.admin-search-bar .btn-clear {
  padding: 10px 16px; border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.12);
  background: transparent; color: rgba(241,245,249,.6);
  font-size: 13px; cursor: pointer; text-decoration: none;
  font-family: 'Be Vietnam Pro', sans-serif; font-weight: 600;
  transition: all .2s;
}
.admin-search-bar .btn-clear:hover { background: rgba(255,255,255,.06); color: #fff; }

.user-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px,1fr));
  gap: 18px;
}
.user-card {
  background: linear-gradient(135deg,#111827,#0d1322);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px; overflow: hidden;
  opacity: 0; transform: translateY(24px);
}
.user-card.is-visible {
  animation: cardFadeUp .5s cubic-bezier(0.22,1,0.36,1) forwards;
}
@keyframes cardFadeUp {
  from{opacity:0;transform:translateY(24px)}
  to{opacity:1;transform:translateY(0)}
}
.user-card-header {
  display: flex; align-items: center; gap: 12px;
  padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.06);
}
.user-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: linear-gradient(135deg,rgba(124,58,237,.4),rgba(79,70,229,.3));
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0;
  border: 1px solid rgba(124,58,237,.3);
}
.user-name { font-size: 14px; font-weight: 800; color: #f1f5f9; }
.user-email { font-size: 12px; color: #64748b; margin-top: 2px; }
.user-badge {
  margin-left: auto; font-size: 11px; font-weight: 700;
  padding: 3px 10px; border-radius: 999px;
  background: rgba(232,25,44,.12); border: 1px solid rgba(232,25,44,.2); color: #fca5a5;
}
.ticket-list { padding: 0 16px 8px; }
.ticket-item {
  padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
  display: flex; flex-direction: column; gap: 4px;
}
.ticket-item:last-child { border-bottom: none; }
.ticket-movie { font-size: 13px; font-weight: 700; color: #f1f5f9; }
.ticket-detail { font-size: 11px; color: #64748b; display: flex; gap: 10px; flex-wrap: wrap; }
.ticket-detail span { display: flex; align-items: center; gap: 3px; }
.no-tickets { padding: 14px 0; font-size: 12px; color: #475569; font-style: italic; }

.stats-bar {
  display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;
}
.stat-box {
  background: linear-gradient(135deg,#111827,#0d1322);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 12px; padding: 14px 20px;
  display: flex; flex-direction: column; gap: 4px;
  min-width: 120px;
}
.stat-num { font-size: 24px; font-weight: 900; color: #f1f5f9; }
.stat-label { font-size: 12px; color: #64748b; font-weight: 600; }
</style>
</head>
<body class="user-index">

<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="../user/index.php" class="nav-link"><span class="icon">🎬</span><span class="text">TRANG CHỦ</span></a>
        <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
        <a href="../admin/phim.php" class="nav-link admin"><span class="icon">🎬</span><span class="text">QUẢN LÝ PHIM</span></a>
        <?php endif; ?>
      </div>
      <div class="search-wrap" id="searchWrap">
        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm phim..." autocomplete="off">
        <span class="search-icon">🔍</span>
        <span class="search-spinner"></span>
        <div class="search-dropdown" id="searchDropdown"></div>
      </div>
      <div class="header-nav-right">
        <span class="hello"><span class="icon">🛡️</span><span class="text">Admin</span></span>
        <a href="../auth/logout.php" class="btn btn-sm btn-outline" onclick="return confirm('Đăng xuất?')"><span class="icon">🚪</span><span class="text">ĐĂNG XUẤT</span></a>
      </div>
    </nav>
  </div>
</header>

<main class="container">
  <h1 class="page-title">👥 Quản lý người dùng</h1>

  <!-- Stats -->
  <div class="stats-bar">
    <div class="stat-box">
      <div class="stat-num"><?= count($users_list) ?></div>
      <div class="stat-label">Người dùng</div>
    </div>
    <div class="stat-box">
      <div class="stat-num"><?= count($ves) ?></div>
      <div class="stat-label">Tổng vé đặt</div>
    </div>
  </div>

  <!-- Search -->
  <form method="get" class="admin-search-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
           placeholder="🔍 Tìm tên hoặc email người dùng...">
    <button type="submit" class="btn-search">Tìm kiếm</button>
    <?php if ($search !== ''): ?>
      <a href="quan_ly_user.php" class="btn-clear">✕ Xóa lọc</a>
    <?php endif; ?>
  </form>

  <?php if (empty($users_list)): ?>
    <div style="text-align:center;padding:60px 20px;color:#64748b;">
      <div style="font-size:40px;margin-bottom:12px;opacity:.5;">👥</div>
      <div style="font-size:16px;font-weight:700;color:#94a3b8;margin-bottom:6px;">
        <?= $search ? 'Không tìm thấy người dùng nào' : 'Chưa có người dùng nào' ?>
      </div>
    </div>
  <?php else: ?>
  <div class="user-grid">
    <?php foreach ($users_list as $uid => $user):
      $list = $ves_by_user[$uid] ?? [];
    ?>
    <div class="user-card">
      <div class="user-card-header">
        <div class="user-avatar">👤</div>
        <div>
          <div class="user-name"><?= htmlspecialchars($user['ten'] ?? 'Khách') ?></div>
          <div class="user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>
        <span class="user-badge"><?= count($list) ?> vé</span>
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
              <span>💰 <?= number_format($ve['gia']) ?>đ</span>
            </div>
            <div style="font-size:11px;color:#475569;"><?= htmlspecialchars($ve['ten_rap']) ?> — <?= htmlspecialchars($ve['ten_phong']) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas — Admin Panel</div></footer>

<script>
const io3 = new IntersectionObserver(entries=>{
  entries.forEach((e,i)=>{
    if(!e.isIntersecting) return;
    setTimeout(()=>e.target.classList.add('is-visible'), i*60);
    io3.unobserve(e.target);
  });
},{threshold:0,rootMargin:'0px 0px 200px 0px'});
document.querySelectorAll('.user-card').forEach(c=>io3.observe(c));

const h=document.querySelector('.header'),b=document.querySelector('body.user-index');
if(h&&b){const fn=()=>{h.classList.toggle('shrink',scrollY>50);b.classList.toggle('header-shrink',scrollY>50);};window.addEventListener('scroll',fn,{passive:true});fn();}
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>