<?php
include "../config/db.php";
session_start();

// ── Validate input ──────────────────────────────────────────
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    die("Lỗi: Dữ liệu không hợp lệ. Vui lòng thử lại.");
}

$user_id       = $_SESSION['user_id'] ?? 1;
$suat_chieu_id = (int)$_POST['suat_chieu_id'];
$ghe_array     = array_filter(explode(",", $_POST['ghe']));
$combos_json   = $_POST['combos_json']  ?? '[]';
$tong_combo    = (float)($_POST['tong_combo'] ?? 0);
$combos_chon   = json_decode($combos_json, true) ?: [];

if (empty($ghe_array)) die("Lỗi: Chưa chọn ghế nào.");

// ── Lấy phong_id ────────────────────────────────────────────
$phong_id = null;
$stmt = mysqli_prepare($conn, "SELECT phong_id FROM suat_chieu WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) $phong_id = (int)$row['phong_id'];
mysqli_stmt_close($stmt);
if (!$phong_id) die("Lỗi: Suất chiếu không tồn tại.");

// ── Transaction: Lưu vé ─────────────────────────────────────
$inserted_ve_ids = [];
mysqli_begin_transaction($conn);
try {
    $get_ghe   = mysqli_prepare($conn, "SELECT id FROM ghe WHERE ten_ghe = ? AND phong_id = ?");
    $ins_ve    = mysqli_prepare($conn, "INSERT INTO ve (user_id, ghe_id, suat_chieu_id) VALUES (?, ?, ?)");

    foreach ($ghe_array as $ten_ghe) {
        mysqli_stmt_bind_param($get_ghe, "si", $ten_ghe, $phong_id);
        mysqli_stmt_execute($get_ghe);
        $ghe_row = mysqli_fetch_assoc(mysqli_stmt_get_result($get_ghe));
        if (!$ghe_row) throw new Exception("Ghế '{$ten_ghe}' không tồn tại trong phòng này.");
        $ghe_id = (int)$ghe_row['id'];

        mysqli_stmt_bind_param($ins_ve, "iii", $user_id, $ghe_id, $suat_chieu_id);
        if (!mysqli_stmt_execute($ins_ve)) throw new Exception("Không thể đặt ghế '{$ten_ghe}'. Ghế có thể đã được người khác đặt.");
        $inserted_ve_ids[] = mysqli_insert_id($conn);
    }
    mysqli_stmt_close($get_ghe);
    mysqli_stmt_close($ins_ve);

    // ── Lưu combo (nếu có) ──────────────────────────────────
    if (!empty($combos_chon) && !empty($inserted_ve_ids)) {
        // Lấy giá thực từ DB (tránh giả mạo giá từ client)
        $ids_str = implode(',', array_map('intval', array_column($combos_chon, 'combo_id')));
        $gia_map = [];
        if ($ids_str) {
            $r_gia = mysqli_query($conn, "SELECT id, gia FROM combos WHERE id IN ($ids_str)");
            while ($row_g = mysqli_fetch_assoc($r_gia)) $gia_map[$row_g['id']] = $row_g['gia'];
        }

        $ins_combo = mysqli_prepare($conn,
            "INSERT INTO dat_ve_combo (ve_id, combo_id, so_luong, don_gia) VALUES (?, ?, ?, ?)"
        );
        $first_ve_id = $inserted_ve_ids[0]; // gắn combo vào vé đầu tiên của đơn
        foreach ($combos_chon as $item) {
            $cid  = (int)$item['combo_id'];
            $qty  = max(1, (int)$item['so_luong']);
            $dgia = $gia_map[$cid] ?? 0;
            mysqli_stmt_bind_param($ins_combo, "iiid", $first_ve_id, $cid, $qty, $dgia);
            mysqli_stmt_execute($ins_combo);
        }
        mysqli_stmt_close($ins_combo);

        // Tính lại tong_combo từ giá DB
        $tong_combo = 0;
        foreach ($combos_chon as $item) {
            $cid = (int)$item['combo_id'];
            $tong_combo += ($gia_map[$cid] ?? 0) * max(1, (int)$item['so_luong']);
        }
    }

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Đã xảy ra lỗi trong quá trình đặt vé: " . $e->getMessage());
}

// ── Lấy thông tin để hiển thị ────────────────────────────────
$sql_info = "
SELECT p.ten_phim, p.poster, p.the_loai, p.thoi_luong,
       sc.ngay, sc.gio, sc.gia,
       pc.ten_phong, r.ten_rap
FROM suat_chieu sc
JOIN phim p ON sc.phim_id = p.id
JOIN phong_chieu pc ON sc.phong_id = pc.id
JOIN rap r ON pc.rap_id = r.id
WHERE sc.id = $suat_chieu_id
";
$info = mysqli_fetch_assoc(mysqli_query($conn, $sql_info));

// Lấy tên combo để hiển thị
$combo_display = [];
if (!empty($combos_chon)) {
    $ids_str = implode(',', array_map('intval', array_column($combos_chon, 'combo_id')));
    $r_c = mysqli_query($conn, "SELECT id, ten_combo, gia FROM combos WHERE id IN ($ids_str)");
    $cn_map = [];
    while ($rc = mysqli_fetch_assoc($r_c)) $cn_map[$rc['id']] = $rc;
    foreach ($combos_chon as $item) {
        $cid = (int)$item['combo_id'];
        $qty = (int)$item['so_luong'];
        if (isset($cn_map[$cid])) {
            $combo_display[] = ['ten' => $cn_map[$cid]['ten_combo'], 'qty' => $qty, 'gia' => $cn_map[$cid]['gia']];
        }
    }
}

mysqli_close($conn);

// ── Format helpers ────────────────────────────────────────────
$seat_list     = implode(", ", array_map('trim', $ghe_array));
$so_ghe        = count($ghe_array);
$gia_ve        = (float)($info['gia'] ?? 0);
$tong_ve       = $so_ghe * $gia_ve;
$tong_thanh_toan = $tong_ve + $tong_combo;
$ngay_chieu    = $info ? date('d/m/Y', strtotime($info['ngay'])) : '';
$gio_chieu     = $info ? date('H:i', strtotime($info['gio'])) : '';
$ma_don        = 'CGV' . str_pad($inserted_ve_ids[0] ?? 0, 6, '0', STR_PAD_LEFT);
function fmt_m($n){ return number_format($n, 0, ',', '.') . '₫'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đặt vé thành công – CGV</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --red:#e50914; --gold:#f5c518; --bg:#0d1117; --surface:#161b22;
  --card:#1c2128; --border:#30363d; --text:#e6edf3; --muted:#8b949e;
  --green:#3fb950;
}
*,*::before,*::after{box-sizing:border-box;}

body.success-page{
  font-family:'DM Sans',sans-serif;
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
}

/* ── confetti ── */
.confetti{
  position:fixed;width:9px;height:9px;
  pointer-events:none;top:-10px;border-radius:2px;
  animation:fall 4.5s ease-in forwards;
}
@keyframes fall{
  0%  {transform:translateY(0) translateX(0) rotateZ(0deg);opacity:1}
  100%{transform:translateY(105vh) translateX(var(--tx)) rotateZ(720deg);opacity:0}
}

/* ── Success layout ── */
.success-wrap{
  max-width:780px;
  margin:48px auto;
  padding:0 16px 60px;
}

/* ── Ticket card ── */
.ticket-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:20px;
  overflow:hidden;
  box-shadow:0 24px 60px rgba(0,0,0,0.5);
  position:relative;
}

/* top glow bar */
.ticket-top-bar{
  height:5px;
  background:linear-gradient(90deg, var(--red), var(--gold), var(--green));
}

/* Header section */
.ticket-header{
  padding:28px 28px 20px;
  display:flex;
  align-items:center;
  gap:20px;
  border-bottom:1px dashed var(--border);
}
.success-icon{
  width:64px;height:64px;
  background:linear-gradient(135deg,#1a3a1a,#0d2b0d);
  border:2px solid var(--green);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:30px;
  flex-shrink:0;
  animation:popIn .5s cubic-bezier(.175,.885,.32,1.275) both;
}
@keyframes popIn{
  0%{transform:scale(0);opacity:0}
  100%{transform:scale(1);opacity:1}
}
.ticket-headline{flex:1;}
.ticket-headline h1{
  font-family:'Bebas Neue',sans-serif;
  font-size:32px;
  letter-spacing:2px;
  color:var(--green);
  margin:0 0 4px;
}
.ticket-headline p{color:var(--muted);margin:0;font-size:14px;}
.order-code{
  font-family:'Bebas Neue',sans-serif;
  font-size:18px;
  color:var(--gold);
  letter-spacing:3px;
  background:rgba(245,197,24,0.08);
  border:1px solid rgba(245,197,24,0.2);
  border-radius:8px;
  padding:6px 14px;
  white-space:nowrap;
}

/* Body grid */
.ticket-body{
  display:grid;
  grid-template-columns:auto 1fr;
  gap:0;
}
@media(max-width:600px){.ticket-body{grid-template-columns:1fr;}}

/* Poster strip */
.ticket-poster{
  width:150px;
  padding:20px 0 20px 20px;
  display:flex;flex-direction:column;align-items:center;gap:10px;
}
.ticket-poster img{
  width:110px;height:160px;
  object-fit:cover;border-radius:10px;
  box-shadow:0 8px 20px rgba(0,0,0,0.5);
}
@media(max-width:600px){
  .ticket-poster{width:100%;flex-direction:row;padding:16px;align-items:flex-start;}
  .ticket-poster img{width:80px;height:116px;}
}

/* Info section */
.ticket-info{
  padding:20px 24px;
  display:flex;flex-direction:column;gap:0;
}
.info-movie-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:24px;
  letter-spacing:1px;
  color:var(--text);
  margin:0 0 14px;
}
.info-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px 20px;
}
@media(max-width:480px){.info-grid{grid-template-columns:1fr;}}
.info-cell label{
  display:block;font-size:10px;font-weight:700;
  text-transform:uppercase;letter-spacing:1.5px;
  color:var(--muted);margin-bottom:3px;
}
.info-cell .val{
  font-size:15px;font-weight:700;color:var(--text);
}
.info-cell .val.seats{
  display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;
}
.seat-chip{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:6px;padding:3px 9px;
  font-size:13px;font-weight:700;
  color:var(--green);
}

/* Divider (perforation) */
.ticket-perforation{
  position:relative;
  border-top:2px dashed var(--border);
  margin:0 20px;
}
.ticket-perforation::before,
.ticket-perforation::after{
  content:'';
  position:absolute;top:-12px;
  width:24px;height:24px;
  background:var(--bg);
  border-radius:50%;
  border:1px solid var(--border);
}
.ticket-perforation::before{left:-30px;}
.ticket-perforation::after{right:-30px;}

/* Price section */
.ticket-price{
  padding:18px 24px;
}
.price-table{width:100%;}
.price-table tr td{
  padding:5px 0;
  font-size:13px;
  color:var(--muted);
}
.price-table tr td:last-child{
  text-align:right;font-weight:600;color:var(--text);
}
.price-table .total-row td{
  border-top:1px solid var(--border);
  padding-top:10px;
  font-size:16px;
  font-weight:700;
  color:var(--text);
}
.price-table .total-row td:last-child{
  font-size:22px;
  color:var(--gold);
  font-family:'Bebas Neue',sans-serif;
  letter-spacing:1px;
}

/* Combo section in ticket */
.combo-in-ticket{
  padding:0 24px 16px;
}
.combo-tag{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(229,9,20,0.1);
  border:1px solid rgba(229,9,20,0.3);
  border-radius:99px;
  padding:4px 12px;
  font-size:12px;font-weight:600;color:#ff6b6b;
  margin:3px 4px 3px 0;
}

/* Footer actions */
.ticket-actions{
  padding:20px 24px;
  display:flex;gap:12px;flex-wrap:wrap;
  border-top:1px solid var(--border);
  background:var(--surface);
}
.btn-home{
  flex:1;
  background:linear-gradient(135deg,var(--red),#c0060f);
  color:#fff;border:none;border-radius:12px;
  padding:13px 20px;font-family:'Bebas Neue',sans-serif;
  font-size:18px;letter-spacing:1.5px;cursor:pointer;
  text-decoration:none;text-align:center;
  transition:opacity .2s,transform .15s;
  box-shadow:0 6px 20px rgba(229,9,20,0.3);
}
.btn-home:hover{opacity:.9;transform:translateY(-1px);}
.btn-ve{
  flex:1;
  background:transparent;
  border:1px solid var(--border);color:var(--text);
  border-radius:12px;padding:13px 20px;
  font-size:14px;font-weight:600;cursor:pointer;
  text-decoration:none;text-align:center;
  transition:border-color .15s,background .15s;
}
.btn-ve:hover{border-color:var(--muted);background:rgba(255,255,255,0.04);}

/* Enjoy note */
.enjoy-note{
  margin-top:24px;
  text-align:center;
  color:var(--muted);
  font-size:13px;
  line-height:1.7;
}
.enjoy-note strong{color:var(--gold);}
</style>
</head>

<body class="success-page user-index">

<!-- Header -->
<header class="header">
  <div class="header-inner">
    <div class="logo">CGV</div>
    <nav class="menu">
      <a href="index.php" class="nav-link">🎬 PHIM</a>
      <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
        <a href="../admin/phim.php" class="nav-link admin">⚙️ QUẢN LÝ PHIM</a>
        <a href="../admin/suat_chieu.php" class="nav-link admin">⚙️ SUẤT CHIẾU</a>
      <?php endif; ?>
    </nav>
    <div class="actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <span class="hello">👋 Xin chào</span>
        <a href="ve_cua_toi.php" class="admin-btn">VÉ CỦA TÔI</a>
        <a href="../auth/logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?');">🚪 ĐĂNG XUẤT</a>
      <?php else: ?>
        <a href="../auth/login.php" class="open-login-modal">🔐 ĐĂNG NHẬP</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="success-wrap">
  <div class="ticket-card">
    <div class="ticket-top-bar"></div>

    <!-- Header -->
    <div class="ticket-header">
      <div class="success-icon">🎉</div>
      <div class="ticket-headline">
        <h1>Đặt vé thành công!</h1>
        <p>Cảm ơn bạn đã đặt vé tại CGV. Hẹn gặp bạn tại rạp!</p>
      </div>
      <div class="order-code"><?= $ma_don ?></div>
    </div>

    <!-- Body -->
    <div class="ticket-body">
      <!-- Poster -->
      <div class="ticket-poster">
        <img src="../assets/images/<?= htmlspecialchars($info['poster'] ?? '') ?>"
             alt="poster"
             onerror="this.src='../assets/images/avengers.jpg'">
      </div>

      <!-- Info -->
      <div class="ticket-info">
        <h2 class="info-movie-title"><?= htmlspecialchars($info['ten_phim'] ?? '') ?></h2>
        <div class="info-grid">
          <div class="info-cell">
            <label>📅 Ngày chiếu</label>
            <div class="val"><?= $ngay_chieu ?></div>
          </div>
          <div class="info-cell">
            <label>🕐 Giờ chiếu</label>
            <div class="val"><?= $gio_chieu ?></div>
          </div>
          <div class="info-cell">
            <label>🏠 Rạp</label>
            <div class="val"><?= htmlspecialchars($info['ten_rap'] ?? '') ?></div>
          </div>
          <div class="info-cell">
            <label>🎬 Phòng</label>
            <div class="val"><?= htmlspecialchars($info['ten_phong'] ?? '') ?></div>
          </div>
          <div class="info-cell" style="grid-column:1/-1">
            <label>💺 Ghế</label>
            <div class="val seats">
              <?php foreach ($ghe_array as $g): ?>
              <span class="seat-chip"><?= htmlspecialchars(trim($g)) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /ticket-body -->

    <!-- Combo tags -->
    <?php if (!empty($combo_display)): ?>
    <div class="combo-in-ticket">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:8px;">🍿 Combo đã chọn</div>
      <?php foreach ($combo_display as $cd): ?>
        <span class="combo-tag">
          <?= htmlspecialchars($cd['ten']) ?> ×<?= $cd['qty'] ?>
          &nbsp;—&nbsp;<?= fmt_m($cd['gia'] * $cd['qty']) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Perforation -->
    <div class="ticket-perforation"></div>

    <!-- Price breakdown -->
    <div class="ticket-price">
      <table class="price-table">
        <tr>
          <td>Vé xem phim (<?= $so_ghe ?> ghế × <?= fmt_m($gia_ve) ?>)</td>
          <td><?= fmt_m($tong_ve) ?></td>
        </tr>
        <?php if ($tong_combo > 0): ?>
        <tr>
          <td>Combo bắp nước</td>
          <td><?= fmt_m($tong_combo) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
          <td>Tổng thanh toán</td>
          <td><?= fmt_m($tong_thanh_toan) ?></td>
        </tr>
      </table>
    </div>

    <!-- Actions -->
    <div class="ticket-actions">
      <a href="index.php" class="btn-home">🏠 Về trang chủ</a>
      <a href="ve_cua_toi.php" class="btn-ve">🎟️ Xem vé của tôi</a>
    </div>
  </div><!-- /ticket-card -->

  <div class="enjoy-note">
    Vui lòng <strong>đến trước giờ chiếu 15 phút</strong> để nhận vé và combo.<br>
    Mã đơn: <strong><?= $ma_don ?></strong> — Chúc bạn xem phim vui vẻ! 🎬
  </div>
</main>

<script>
// Confetti animation
(function(){
  const colors = ['#e50914','#ff6b6b','#f5c518','#ffcc80','#fff59d','#3fb950','#80deea','#b3e5fc','#c5cae9','#e1bee7'];
  for (let i = 0; i < 120; i++) {
    const el   = document.createElement('div');
    el.className = 'confetti';
    el.style.left            = Math.random() * 100 + '%';
    el.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
    el.style.animationDelay  = (Math.random() * 0.8) + 's';
    el.style.animationDuration = (Math.random() * 2 + 3) + 's';
    el.style.width  = (Math.random() * 8 + 5) + 'px';
    el.style.height = el.style.width;
    el.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
    el.style.setProperty('--tx', ((Math.random() - 0.5) * 350) + 'px');
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 6000);
  }
})();
</script>
<script src="../assets/js/login-modal.js"></script>
</body>
</html>