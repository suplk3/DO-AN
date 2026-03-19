<?php
session_start();
include "../config/db.php";

$phim_id = isset($_GET['phim_id']) ? intval($_GET['phim_id']) : 0;

if ($phim_id <= 0) {
    die("Không tìm thấy phim");
}

// Lấy danh sách ngày chiếu
$sql_ngay = "
SELECT DISTINCT ngay 
FROM suat_chieu 
WHERE phim_id = $phim_id
";
$q_ngay = mysqli_query($conn, $sql_ngay);

// chuyển các ngày vào mảng để dễ xử lý và xác định giá trị mặc định
$ngay_list = [];
if ($q_ngay) {
    while ($row = mysqli_fetch_assoc($q_ngay)) {
        $ngay_list[] = $row['ngay'];
    }
}

// chọn ngày hiện tại từ query string nếu hợp lệ, ngược lại lấy phần tử đầu của danh sách
$ngay_chon = null;
if (isset($_GET['ngay']) && in_array($_GET['ngay'], $ngay_list, true)) {
    $ngay_chon = $_GET['ngay'];
} elseif (!empty($ngay_list)) {
    $ngay_chon = $ngay_list[0];
} else {
    // không có ngày nào trong database -> sử dụng ngày hôm nay để hiển thị thông báo
    $ngay_chon = date('Y-m-d');
}

// Lấy suất chiếu theo ngày kèm thông tin rạp
// nếu danh sách ngày rỗng thì $ngay_chon đã được đặt thành ngày hiện tại ở phía trên
$ngay_esc = mysqli_real_escape_string($conn, $ngay_chon);
$sql_suat = "
SELECT sc.*, pc.rap_id, pc.ten_phong, r.ten_rap, r.dia_chi, r.thanh_pho
FROM suat_chieu sc
LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
LEFT JOIN rap r ON pc.rap_id = r.id
WHERE sc.phim_id = $phim_id 
AND sc.ngay = '$ngay_esc'
ORDER BY sc.gio
";
$q_suat = mysqli_query($conn, $sql_suat);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chọn suất chiếu — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<link rel="stylesheet" href="../assets/css/search.css">
<style>
/* Responsive tweaks for chon_suat.php */
.movie-info-card {
  display: flex; align-items: center; gap: 16px;
  background: linear-gradient(135deg,#111827,#0d1322);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px; padding: 16px 20px;
  margin-bottom: 28px;
}
.date-scroll-container {
  display: flex; gap: 10px; margin-bottom: 32px;
  overflow-x: auto; padding-bottom: 8px;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
}
.date-scroll-container::-webkit-scrollbar { height: 6px; }
.date-scroll-container::-webkit-scrollbar-track { background: transparent; }
.date-scroll-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 6px; }
.date-scroll-container > a { scroll-snap-align: start; flex-shrink: 0; min-width: 70px; }

.showtime-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px; margin-bottom: 40px;
}
@media (max-width: 480px) {
  .movie-info-card { flex-direction: row; align-items: flex-start; gap: 12px; }
  .movie-info-card img { width: 70px; height: 100px; object-fit: cover; }
  .showtime-grid { grid-template-columns: 1fr; gap: 12px; }
}
</style>
</head>
<body class="user-index">

<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="index.php" class="nav-link"><span class="icon">🎬</span><span class="text">PHIM</span></a>
        <a href="sap_chieu.php" class="nav-link"><span class="icon">🗓️</span><span class="text">SẮP CHIẾU</span></a>
      </div>
      <div class="search-wrap" id="searchWrap">
        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm phim..." autocomplete="off">
        <span class="search-icon">🔍</span>
        <span class="search-spinner"></span>
        <div class="search-dropdown" id="searchDropdown"></div>
      </div>
      <div class="header-nav-right">
        <?php if (isset($_SESSION['user_id'])):
          $is_admin = ($_SESSION['vai_tro'] ?? '') === 'admin'; ?>
          <span class="hello"><span class="icon">👋</span><span class="text">Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? $_SESSION['ten'] ?? 'bạn') ?></span></span>
          <a href="../user/ve_cua_toi.php" class="btn btn-sm"><span class="icon">🎟️</span><span class="text">VÉ CỦA TÔI</span></a>
          <a href="../auth/logout.php" class="btn btn-sm btn-outline" onclick="return confirm('Đăng xuất?')"><span class="icon">🚪</span><span class="text">ĐĂNG XUẤT</span></a>
        <?php else: ?>
          <a href="../auth/login.php" class="btn btn-sm open-login-modal"><span class="icon">🔐</span><span class="text">ĐĂNG NHẬP</span></a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<main class="container">
  <a href="chi_tiet_phim.php?id=<?= $phim_id ?>" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:20px;">← Quay lại chi tiết phim</a>

  <?php
  $pi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ten_phim,poster,the_loai,thoi_luong FROM phim WHERE id=$phim_id"));
  if ($pi): ?>
  <div class="movie-info-card">
    <img src="../assets/images/<?= htmlspecialchars($pi['poster']) ?>" style="width:52px;height:74px;object-fit:cover;border-radius:8px;flex-shrink:0;" alt="">
    <div>
      <div style="font-size:17px;font-weight:800;color:#f1f5f9;margin-bottom:6px;"><?= htmlspecialchars($pi['ten_phim']) ?></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($pi['the_loai']): ?><span style="font-size:11px;font-weight:600;color:#a78bfa;background:rgba(124,58,237,0.12);border:1px solid rgba(124,58,237,0.2);padding:2px 9px;border-radius:999px;"><?= htmlspecialchars($pi['the_loai']) ?></span><?php endif; ?>
        <?php if ($pi['thoi_luong']): ?><span style="font-size:11px;color:#64748b;">⏱ <?= htmlspecialchars($pi['thoi_luong']) ?> phút</span><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <h1 class="page-title" style="text-align:left;font-size:20px;margin-bottom:20px;">🗓️ Chọn ngày chiếu</h1>

  <div class="date-scroll-container">
    <?php if (empty($ngay_list)): ?>
      <p style="color:#64748b;">Không có ngày chiếu nào.</p>
    <?php else: foreach ($ngay_list as $d):
      $active = ($d == $ngay_chon);
      $ts = strtotime($d);
      $thu = ['CN','T2','T3','T4','T5','T6','T7'][date('w',$ts)];
    ?>
      <a href="?phim_id=<?= $phim_id ?>&ngay=<?= $d ?>"
         style="display:flex;flex-direction:column;align-items:center;padding:10px 18px;border-radius:12px;text-decoration:none;transition:all .2s;
         <?= $active ? 'background:linear-gradient(135deg,#e8192c,#c01020);color:#fff;box-shadow:0 6px 18px rgba(232,25,44,.35);border:1px solid transparent;'
                     : 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;' ?>">
        <span style="font-size:10px;font-weight:600;letter-spacing:.5px;"><?= $thu ?></span>
        <span style="font-size:20px;font-weight:800;"><?= date('d',$ts) ?></span>
        <span style="font-size:11px;"><?= date('m/Y',$ts) ?></span>
      </a>
    <?php endforeach; endif; ?>
  </div>

  <h2 style="font-size:16px;font-weight:800;color:#f1f5f9;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    ⏰ Suất chiếu
    <span style="flex:1;height:1px;background:linear-gradient(90deg,rgba(232,25,44,.4),transparent);"></span>
  </h2>

  <?php if (mysqli_num_rows($q_suat) === 0): ?>
    <div style="text-align:center;padding:60px 20px;color:#64748b;">
      <div style="font-size:42px;margin-bottom:12px;opacity:.5;">🎬</div>
      <div style="font-size:16px;font-weight:700;color:#94a3b8;margin-bottom:6px;">Không có suất chiếu trong ngày này</div>
      <div style="font-size:13px;">Hãy chọn ngày khác</div>
    </div>
  <?php else: ?>
  <div class="showtime-grid">
    <?php while ($s = mysqli_fetch_assoc($q_suat)):
      $gio   = substr($s['gio'], 0, 5);
      $gia   = number_format($s['gia'], 0, ',', '.');
      $rap   = $s['ten_rap'] ?: 'Rạp';
      $phong = $s['ten_phong'] ?: '';
    ?>
    <a href="chon_ghe.php?suat_id=<?= $s['id'] ?>"
       style="display:flex;flex-direction:column;gap:12px;padding:18px;background:linear-gradient(135deg,#111827,#0d1322);border:1px solid rgba(255,255,255,0.07);border-radius:14px;text-decoration:none;transition:all .22s;position:relative;overflow:hidden;"
       onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(232,25,44,.4)';this.style.boxShadow='0 14px 32px rgba(0,0,0,.5)'"
       onmouseout="this.style.transform='';this.style.borderColor='rgba(255,255,255,0.07)';this.style.boxShadow=''">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div style="font-size:30px;font-weight:900;color:#f1f5f9;letter-spacing:-1px;line-height:1;"><?= $gio ?></div>
        <div style="font-size:12px;font-weight:800;color:#f5c518;background:rgba(245,197,24,.1);border:1px solid rgba(245,197,24,.2);padding:3px 10px;border-radius:8px;white-space:nowrap;"><?= $gia ?>₫</div>
      </div>
      <div style="font-size:12px;font-weight:600;color:#64748b;"><?= htmlspecialchars($rap) ?><?= $phong ? ' — '.htmlspecialchars($phong) : '' ?></div>
      <div style="text-align:center;padding:8px;background:rgba(232,25,44,.15);border-radius:8px;border:1px solid rgba(232,25,44,.2);">
        <span style="font-size:12px;font-weight:700;color:#ff6b6b;">Chọn ghế →</span>
      </div>
    </a>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</main>

<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas</div></footer>

<script>
(function(){
  const h=document.querySelector('.header'),b=document.querySelector('body.user-index');
  if(!h||!b) return;
  const fn=()=>{h.classList.toggle('shrink',scrollY>50);b.classList.toggle('header-shrink',scrollY>50);};
  window.addEventListener('scroll',fn,{passive:true});fn();
})();
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/login-modal.js"></script>
</body>
</html>