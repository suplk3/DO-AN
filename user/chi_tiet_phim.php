<?php
session_start();
include "../config/db.php";

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = mysqli_query($conn, "SELECT * FROM phim WHERE id = $id");
$phim   = mysqli_fetch_assoc($result);

if (!$phim) {
    http_response_code(404);
    echo "Không tìm thấy phim"; exit;
}

$suat_sql = "SELECT s.*, pc.ten_phong, r.ten_rap
             FROM suat_chieu s
             LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
             LEFT JOIN rap r ON pc.rap_id = r.id
             WHERE s.phim_id = $id ORDER BY s.ngay, s.gio";
$suat_result = mysqli_query($conn, $suat_sql);
$suats = [];
while ($r = mysqli_fetch_assoc($suat_result)) $suats[] = $r;

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($phim['ten_phim']) ?> — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/movie-detail.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
</head>
<body class="movie-detail-page">

<!-- ── Header ── -->
<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="index.php" class="nav-link">
          <span class="icon">🎬</span><span class="text">PHIM</span>
        </a>
        <a href="sap_chieu.php" class="nav-link">
          <span class="icon">🗓️</span><span class="text">SẮP CHIẾU</span>
        </a>
        <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
        <a href="../admin/phim.php" class="nav-link admin">
          <span class="icon">🎬</span><span class="text">QUẢN LÝ PHIM</span>
        </a>
        <a href="../admin/suat_chieu.php" class="nav-link admin">
          <span class="icon">🗓️</span><span class="text">QUẢN LÝ SUẤT CHIẾU</span>
        </a>
        <?php endif; ?>
      </div>
      <div class="header-nav-right">
        <?php if (isset($_SESSION['user_id'])):
          $is_admin = ($_SESSION['vai_tro'] ?? '') === 'admin'; ?>
          <span class="hello">
            <span class="icon">👋</span>
            <span class="text">Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? $_SESSION['ten'] ?? 'bạn') ?></span>
          </span>
          <a href="../user/ve_cua_toi.php" class="btn btn-sm">
            <span class="icon">🎟️</span><span class="text">VÉ CỦA TÔI</span>
          </a>
          <?php if ($is_admin): ?>
          <a href="../user/quan_ly_user.php" class="btn btn-sm">
            <span class="icon">🎫</span><span class="text">QUẢN LÝ USER</span>
          </a>
          <?php endif; ?>
          <a href="../auth/logout.php" class="btn btn-sm btn-outline"
             onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
            <span class="icon">🚪</span><span class="text">ĐĂNG XUẤT</span>
          </a>
        <?php else: ?>
          <a href="../auth/login.php" class="btn btn-sm open-login-modal">
            <span class="icon">🔐</span><span class="text">ĐĂNG NHẬP</span>
          </a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<!-- ── Main ── -->
<main class="md-container">
  <a class="back" href="javascript:history.back()">← Quay lại</a>

  <!-- Hero card -->
  <section class="md-card">
    <div class="md-poster">
      <img src="../assets/images/<?= htmlspecialchars($phim['poster']) ?>"
           alt="<?= htmlspecialchars($phim['ten_phim']) ?>" loading="eager">
    </div>

    <div class="md-info">
      <h1 class="md-title"><?= htmlspecialchars($phim['ten_phim']) ?></h1>

      <div class="md-meta">
        <?php if (!empty($phim['the_loai'])): ?>
        <span class="chip genre-chip">🎭 <?= htmlspecialchars($phim['the_loai']) ?></span>
        <?php endif; ?>
        <?php
          $dur = $phim['thoi_luong'] ?? ($phim['thoi_gian'] ?? '');
          if ($dur):
        ?>
        <span class="chip time-chip">⏱ <?= htmlspecialchars($dur) ?> phút</span>
        <?php endif; ?>
        <?php if (!empty($phim['dao_dien'])): ?>
        <span class="chip">🎬 <?= htmlspecialchars($phim['dao_dien']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <?php if (!empty($phim['mo_ta'])): ?>
      <?php
        $mo_ta_full  = htmlspecialchars($phim['mo_ta']);
        $need_expand = mb_strlen($phim['mo_ta']) > 280;
        $mo_ta_short = htmlspecialchars(mb_strimwidth($phim['mo_ta'], 0, 280, ''));
      ?>
      <div class="md-desc-wrap">
        <p class="md-short" id="descText">
          <?php if ($need_expand): ?>
            <span class="desc-short"><?= nl2br($mo_ta_short) ?>…</span>
            <span class="desc-full" style="display:none"><?= nl2br($mo_ta_full) ?></span>
          <?php else: ?>
            <?= nl2br($mo_ta_full) ?>
          <?php endif; ?>
        </p>
        <?php if ($need_expand): ?>
        <button class="desc-toggle" id="descToggle" type="button">
          <span class="desc-toggle-text">Xem thêm</span>
          <span class="desc-toggle-icon">▾</span>
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="md-divider"></div>

      <!-- Extra details -->
      <div class="md-details">
        <?php if (!empty($phim['ngay_khoi_chieu'])): ?>
        <div class="md-detail-row">
          <span class="md-detail-label">📅 Khởi chiếu</span>
          <span class="md-detail-value"><?= date('d/m/Y', strtotime($phim['ngay_khoi_chieu'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($phim['quoc_gia'])): ?>
        <div class="md-detail-row">
          <span class="md-detail-label">🌍 Quốc gia</span>
          <span class="md-detail-value"><?= htmlspecialchars($phim['quoc_gia']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($phim['dien_vien'])): ?>
        <div class="md-detail-row">
          <span class="md-detail-label">🌟 Diễn viên</span>
          <span class="md-detail-value"><?= htmlspecialchars($phim['dien_vien']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Actions -->
      <div class="md-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="chon_suat.php?phim_id=<?= (int)$phim['id'] ?>" class="btn-primary">🎟️ Đặt vé ngay</a>
        <?php else: ?>
          <a href="../auth/login.php" class="btn-outline open-login-modal">🔐 Đăng nhập để đặt vé</a>
        <?php endif; ?>
        <a href="#showtimes" class="btn-sm">🗓️ Xem suất chiếu</a>
      </div>
    </div>
  </section>

  <!-- Showtimes -->
  <section id="showtimes" class="showtimes-block">
    <h2 class="section-title">🗓️ Suất chiếu</h2>

    <?php if (empty($suats)): ?>
      <p class="muted">Hiện chưa có suất chiếu cho phim này.</p>
    <?php else: ?>
      <div class="showtimes-grid">
        <?php foreach ($suats as $s):
          $suat_id  = (int)$s['id'];
          $phong_id = (int)$s['phong_id'];
          $total    = null;
          if ($phong_id > 0) {
            $q = mysqli_query($conn,"SELECT COUNT(*) AS t FROM ghe WHERE phong_id = $phong_id");
            $total = (int)mysqli_fetch_assoc($q)['t'];
          }
          $bq = mysqli_query($conn,"SELECT COUNT(*) AS b FROM ve WHERE suat_chieu_id = $suat_id");
          $booked = (int)mysqli_fetch_assoc($bq)['b'];
          $avail  = is_null($total) ? null : max(0, $total - $booked);

          // Badge class
          $badge_class = 'available';
          if (!is_null($avail)) {
            if ($avail === 0) $badge_class = 'full';
            elseif ($total > 0 && $avail / $total < 0.25) $badge_class = 'few';
          }
        ?>
        <article class="showtime-card">
          <div class="st-top">
            <div class="st-datetime">
              <div class="st-date"><?= fmt_date($s['ngay']) ?></div>
              <div class="st-time"><?= fmt_time($s['gio']) ?></div>
            </div>
            <div class="st-price"><?= fmt_money($s['gia']) ?></div>
          </div>

          <div class="st-body">
            <div class="st-room">
              <?= htmlspecialchars($s['ten_rap'] ?: 'Rạp chưa đặt') ?>
              <?php if (!empty($s['ten_phong'])): ?> — <?= htmlspecialchars($s['ten_phong']) ?><?php endif; ?>
            </div>
            <div class="st-seats">
              <?php if (is_null($total) || $total == 0): ?>
                <span class="badge">Không xác định</span>
              <?php elseif ($avail === 0): ?>
                <span class="badge full">🔴 Hết ghế</span>
              <?php else: ?>
                <span class="badge <?= $badge_class ?>">
                  <?= $badge_class === 'few' ? '🟡' : '🟢' ?>
                  <?= $avail ?> / <?= $total ?> ghế trống
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="st-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
              <?php if (!is_null($avail) && $avail === 0): ?>
                <button class="btn-outline" disabled style="opacity:.5;cursor:not-allowed;width:100%;justify-content:center;">Hết ghế</button>
              <?php else: ?>
                <a href="chon_ghe.php?suat_id=<?= $suat_id ?>" class="btn-primary">Đặt ngay →</a>
              <?php endif; ?>
            <?php else: ?>
              <a href="../auth/login.php" class="btn-outline open-login-modal">🔐 Đăng nhập để đặt</a>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<footer class="footer">
  <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script>
(function () {
  /* Header shrink */
  const header = document.querySelector('.header');
  const body   = document.body;
  if (header) {
    const onScroll = () => {
      header.classList.toggle('shrink', window.scrollY > 50);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Desc expand/collapse */
  const toggle = document.getElementById('descToggle');
  if (toggle) {
    const short  = document.querySelector('.desc-short');
    const full   = document.querySelector('.desc-full');
    const label  = toggle.querySelector('.desc-toggle-text');
    const icon   = toggle.querySelector('.desc-toggle-icon');
    let expanded = false;
    toggle.addEventListener('click', () => {
      expanded = !expanded;
      short.style.display = expanded ? 'none'  : 'inline';
      full.style.display  = expanded ? 'inline': 'none';
      label.textContent   = expanded ? 'Thu gọn' : 'Xem thêm';
      icon.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
    });
  }

  /* Showtime card stagger fade-up */
  const cards = document.querySelectorAll('.showtime-card');
  if (!cards.length) return;
  const STAGGER = 70;
  let batch = 0, bTimer = null;
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const card = e.target;
      const delay = card.dataset.init === '1'
        ? Math.min(parseInt(card.dataset.idx) * STAGGER, 350)
        : batch * 60;
      if (card.dataset.init !== '1') {
        batch++;
        clearTimeout(bTimer);
        bTimer = setTimeout(() => { batch = 0; }, 300);
      }
      setTimeout(() => card.classList.add('is-visible'), delay);
      io.unobserve(card);
    });
  }, { threshold: 0, rootMargin: '0px 0px 250px 0px' });

  cards.forEach((c, i) => {
    c.dataset.idx = i;
    if (c.getBoundingClientRect().top < window.innerHeight + 250) c.dataset.init = '1';
    io.observe(c);
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/login-modal.js"></script>
</body>
</html>