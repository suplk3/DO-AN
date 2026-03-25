<?php
include '../config/db.php';
session_start();

// Bảo vệ: phải POST từ chon_ghe.php
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    header('Location: index.php');
    exit;
}

// Lưu tạm dữ liệu ghế vào session để dùng sau
$_SESSION['pending_suat_chieu_id'] = (int)$_POST['suat_chieu_id'];
$_SESSION['pending_ghe']           = $_POST['ghe'];

$suat_chieu_id = $_SESSION['pending_suat_chieu_id'];
$ghe_list      = $_SESSION['pending_ghe'];

// Lấy thông tin suất chiếu + phim
$info_sql = "
SELECT p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia, pc.ten_phong, r.ten_rap
FROM suat_chieu sc
LEFT JOIN phim p ON sc.phim_id = p.id
LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
LEFT JOIN rap r ON pc.rap_id = r.id
WHERE sc.id = $suat_chieu_id
";
$info = mysqli_fetch_assoc(mysqli_query($conn, $info_sql));
if (!$info) { header('Location: index.php'); exit; }

// Lấy danh sách combo
$combos = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM combos WHERE trang_thai = 1 ORDER BY gia ASC"), MYSQLI_ASSOC);

$ghe_array  = array_filter(explode(',', $ghe_list));
$so_ghe     = count($ghe_array);
$gia_ve     = (float)$info['gia'];
$tong_ve    = $so_ghe * $gia_ve;

function fmt_money($n){ return number_format($n,0,',','.') . '₫'; }
function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chọn Combo – <?= htmlspecialchars($info['ten_phim']) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/movie-detail.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ── Combo page variables ── */
:root {
  --c-bg:       #0d1117;
  --c-surface:  #161b22;
  --c-card:     #1c2128;
  --c-border:   #30363d;
  --c-red:      #e50914;
  --c-red-glow: rgba(229,9,20,0.25);
  --c-gold:     #f5c518;
  --c-text:     #e6edf3;
  --c-muted:    #8b949e;
  --c-green:    #3fb950;
}

*, *::before, *::after { box-sizing: border-box; }

body.combo-page {
  font-family: 'DM Sans', sans-serif;
  background: var(--c-bg);
  color: var(--c-text);
  min-height: 100vh;
}

/* ── Sticky Progress Bar ── */
.progress-bar {
  position: sticky;
  top: 0;
  z-index: 200;
  background: rgba(13,17,23,0.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--c-border);
  padding: 12px 20px;
}
.progress-inner {
  max-width: 1100px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 0;
}
.step {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--c-muted);
}
.step.done  { color: var(--c-green); }
.step.active{ color: var(--c-gold); }
.step-num {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: var(--c-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700;
}
.step.done  .step-num { background: var(--c-green);  color: #000; }
.step.active .step-num { background: var(--c-gold);  color: #000; }
.step-line {
  flex: 1;
  height: 2px;
  background: var(--c-border);
  margin: 0 10px;
  border-radius: 2px;
  max-width: 80px;
}
.step-line.done { background: var(--c-green); }

/* ── Main layout ── */
.combo-wrap {
  max-width: 1100px;
  margin: 32px auto;
  padding: 0 16px 80px;
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}
@media(max-width:900px){ .combo-wrap{ grid-template-columns:1fr; } }

/* ── Section heading ── */
.section-head {
  margin-bottom: 20px;
}
.section-head h2 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 32px;
  letter-spacing: 2px;
  color: var(--c-text);
  margin: 0 0 4px;
}
.section-head p { color: var(--c-muted); margin: 0; font-size: 14px; }

/* ── Combo Cards Grid ── */
.combo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.combo-card {
  background: var(--c-card);
  border: 2px solid var(--c-border);
  border-radius: 14px;
  overflow: hidden;
  transition: border-color .2s, box-shadow .2s, transform .2s;
  cursor: pointer;
}
.combo-card:hover {
  border-color: var(--c-red);
  box-shadow: 0 0 20px var(--c-red-glow);
  transform: translateY(-3px);
}
.combo-card.selected {
  border-color: var(--c-gold);
  box-shadow: 0 0 24px rgba(245,197,24,0.2);
}
.combo-img-wrap {
  position: relative;
  height: 150px;
  overflow: hidden;
  background: #0a0a0a;
}
.combo-img-wrap img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform .3s;
}
.combo-card:hover .combo-img-wrap img { transform: scale(1.06); }

/* Fallback emoji background nếu không có ảnh */
.combo-img-wrap .emoji-bg {
  display: flex; align-items: center; justify-content: center;
  height: 100%;
  font-size: 56px;
  background: linear-gradient(135deg, #1a0a00, #2d1600);
  user-select: none;
}

.combo-badge {
  position: absolute; top: 10px; right: 10px;
  background: var(--c-red);
  color: #fff;
  font-size: 11px; font-weight: 700;
  padding: 3px 8px;
  border-radius: 99px;
}
.combo-body {
  padding: 14px;
}
.combo-name {
  font-weight: 700;
  font-size: 15px;
  margin: 0 0 4px;
}
.combo-desc {
  font-size: 12px;
  color: var(--c-muted);
  margin: 0 0 12px;
  line-height: 1.5;
}
.combo-price {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 22px;
  color: var(--c-gold);
  letter-spacing: 1px;
  margin-bottom: 12px;
}

/* Quantity control */
.qty-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.qty-ctrl {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 99px;
  padding: 4px 12px;
}
.qty-btn {
  width: 26px; height: 26px;
  border: none;
  background: transparent;
  color: var(--c-text);
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
  border-radius: 50%;
  transition: background .15s;
  display: flex; align-items: center; justify-content: center;
}
.qty-btn:hover { background: var(--c-border); }
.qty-val {
  font-weight: 700;
  font-size: 16px;
  min-width: 20px;
  text-align: center;
  color: var(--c-text);
}
.qty-subtotal {
  font-size: 13px;
  font-weight: 600;
  color: var(--c-muted);
}
.qty-subtotal.has-val { color: var(--c-gold); }

/* ── Skip option ── */
.skip-option {
  margin-top: 8px;
  padding: 14px 18px;
  background: var(--c-surface);
  border: 2px dashed var(--c-border);
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  color: var(--c-muted);
  font-size: 14px;
  transition: border-color .2s, color .2s;
}
.skip-option:hover { border-color: var(--c-muted); color: var(--c-text); }

/* ── Order Summary (sidebar) ── */
.order-summary {
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 20px;
  position: sticky;
  top: 80px;
}
.order-summary h3 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 22px;
  letter-spacing: 1.5px;
  margin: 0 0 16px;
  color: var(--c-text);
  border-bottom: 1px solid var(--c-border);
  padding-bottom: 12px;
}

/* Movie mini info */
.movie-mini {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--c-border);
}
.movie-mini img {
  width: 60px; height: 88px;
  object-fit: cover;
  border-radius: 8px;
  flex-shrink: 0;
}
.movie-mini-info { flex: 1; }
.movie-mini-info .title {
  font-weight: 700;
  font-size: 14px;
  line-height: 1.3;
  margin-bottom: 6px;
}
.movie-mini-info .meta {
  font-size: 12px;
  color: var(--c-muted);
  line-height: 1.7;
}

/* Price rows */
.price-rows { margin-bottom: 16px; }
.price-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  padding: 5px 0;
  color: var(--c-muted);
}
.price-row .val { color: var(--c-text); font-weight: 600; }
.price-row.total {
  border-top: 1px solid var(--c-border);
  margin-top: 8px;
  padding-top: 12px;
  font-size: 16px;
  font-weight: 700;
  color: var(--c-text);
}
.price-row.total .val { color: var(--c-gold); font-size: 20px; }

/* Combo mini list in summary */
.combo-summary-list { margin-bottom: 16px; }
.combo-summary-item {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  padding: 3px 0;
  color: var(--c-muted);
}
.combo-summary-item .name { color: var(--c-text); }

/* CTA Button */
.btn-pay {
  width: 100%;
  background: linear-gradient(135deg, var(--c-red), #c0060f);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 14px;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 20px;
  letter-spacing: 2px;
  cursor: pointer;
  transition: opacity .2s, transform .15s, box-shadow .2s;
  box-shadow: 0 6px 20px rgba(229,9,20,0.3);
  margin-bottom: 10px;
}
.btn-pay:hover {
  opacity: .92;
  transform: translateY(-1px);
  box-shadow: 0 10px 28px rgba(229,9,20,0.4);
}
.btn-back {
  width: 100%;
  background: transparent;
  color: var(--c-muted);
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 10px;
  font-size: 13px;
  cursor: pointer;
  transition: color .15s, border-color .15s;
}
.btn-back:hover { color: var(--c-text); border-color: var(--c-muted); }

/* Selected seats chips */
.seats-chips {
  display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;
}
.seat-chip {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--c-green);
}

/* Toast notification */
.toast {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(60px);
  background: #1c2128;
  border: 1px solid var(--c-green);
  color: var(--c-text);
  padding: 10px 20px;
  border-radius: 99px;
  font-size: 14px;
  font-weight: 600;
  z-index: 999;
  opacity: 0;
  transition: all .3s ease;
  white-space: nowrap;
}
.toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
</style>
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="combo-page movie-detail-page">

<!-- Header (giống các trang khác) -->
<?php $active_page = ''; include 'components/header.php'; ?>
<!-- Progress Steps -->
<div class="progress-bar">
  <div class="progress-inner">
    <div class="step done">
      <div class="step-num">✓</div>
      <span>Chọn ghế</span>
    </div>
    <div class="step-line done"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <span>Chọn Combo</span>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-num">3</div>
      <span>Xác nhận</span>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="combo-wrap">

  <!-- LEFT: Combo selection -->
  <div class="combo-left">
    <div class="section-head">
      <h2>🍿 Chọn Combo Bắp Nước</h2>
      <p>Thêm combo để trải nghiệm thêm phần thú vị — hoàn toàn tùy chọn!</p>
    </div>

    <?php if (empty($combos)): ?>
      <div style="background:var(--c-card);border:1px solid var(--c-border);border-radius:12px;padding:40px;text-align:center;color:var(--c-muted);">
        Hiện chưa có combo nào. Bạn có thể tiếp tục thanh toán.
      </div>
    <?php else: ?>
    <div class="combo-grid">
      <?php foreach ($combos as $c): ?>
      <div class="combo-card" id="card-<?= $c['id'] ?>" onclick="toggleCard(<?= $c['id'] ?>)">
        <div class="combo-img-wrap">
          <?php if (!empty($c['hinh_anh'])): ?>
            <img src="../assets/images/combos/<?= htmlspecialchars($c['hinh_anh']) ?>"
                 alt="<?= htmlspecialchars($c['ten_combo']) ?>"
                 onerror="this.parentElement.innerHTML='<div class=emoji-bg>🍿</div>'">
          <?php else: ?>
            <div class="emoji-bg">🍿</div>
          <?php endif; ?>
          <?php if ($c['id'] == 2): ?>
          <div class="combo-badge">PHỔ BIẾN</div>
          <?php endif; ?>
        </div>
        <div class="combo-body">
          <div class="combo-name"><?= htmlspecialchars($c['ten_combo']) ?></div>
          <div class="combo-desc"><?= htmlspecialchars($c['mo_ta']) ?></div>
          <div class="combo-price"><?= fmt_money($c['gia']) ?></div>
          <div class="qty-row" onclick="event.stopPropagation()">
            <div class="qty-ctrl">
              <button class="qty-btn" onclick="changeQty(<?= $c['id'] ?>, -1)">−</button>
              <span class="qty-val" id="qty-<?= $c['id'] ?>">0</span>
              <button class="qty-btn" onclick="changeQty(<?= $c['id'] ?>, 1)">+</button>
            </div>
            <div class="qty-subtotal" id="sub-<?= $c['id'] ?>">0₫</div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="skip-option" onclick="skipCombo()">
      Bỏ qua, không cần combo →
    </div>
  </div>

  <!-- RIGHT: Order summary -->
  <div class="order-summary">
    <h3>📋 Tóm tắt đơn hàng</h3>

    <!-- Movie info mini -->
    <div class="movie-mini">
      <img src="../assets/images/<?= htmlspecialchars($info['poster']) ?>"
           alt="poster"
           onerror="this.src='../assets/images/avengers.jpg'">
      <div class="movie-mini-info">
        <div class="title"><?= htmlspecialchars($info['ten_phim']) ?></div>
        <div class="meta">
          📅 <?= fmt_date($info['ngay']) ?><br>
          🕐 <?= fmt_time($info['gio']) ?><br>
          🏠 <?= htmlspecialchars($info['ten_rap']) ?><br>
          🎬 <?= htmlspecialchars($info['ten_phong']) ?>
        </div>
      </div>
    </div>

    <!-- Selected seats -->
    <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--c-border);">
      <div style="font-size:12px;color:var(--c-muted);margin-bottom:6px;">GHẾ ĐÃ CHỌN:</div>
      <div class="seats-chips">
        <?php foreach ($ghe_array as $g): ?>
        <div class="seat-chip"><?= htmlspecialchars(trim($g)) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Price breakdown -->
    <div class="price-rows">
      <div class="price-row">
        <span>Vé (<?= $so_ghe ?> ghế × <?= fmt_money($gia_ve) ?>)</span>
        <span class="val" id="sum-ve"><?= fmt_money($tong_ve) ?></span>
      </div>
      <div class="combo-summary-list" id="combo-summary-list"></div>
      <div class="price-row">
        <span>Combo</span>
        <span class="val" id="sum-combo">0₫</span>
      </div>
      <div class="price-row total">
        <span>TỔNG CỘNG</span>
        <span class="val" id="sum-total"><?= fmt_money($tong_ve) ?></span>
      </div>
    </div>

    <!-- Submit form -->
    <form method="POST" action="dat_ve.php" id="frm-checkout">
      <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">
      <input type="hidden" name="ghe"           value="<?= htmlspecialchars($ghe_list) ?>">
      <input type="hidden" name="combos_json"   id="inp-combos">
      <input type="hidden" name="tong_combo"    id="inp-tong-combo">
      <button type="submit" class="btn-pay">XÁC NHẬN &amp; THANH TOÁN</button>
    </form>
    <button class="btn-back" onclick="history.back()">← Quay lại chọn ghế</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const GIA_COMBO = {
  <?php foreach ($combos as $c): ?>
  <?= $c['id'] ?>: { gia: <?= (float)$c['gia'] ?>, ten: <?= json_encode($c['ten_combo']) ?> },
  <?php endforeach; ?>
};

const TONG_VE    = <?= $tong_ve ?>;
const soLuong    = {};   // combo_id → qty

function fmt(n) {
  return n.toLocaleString('vi-VN') + '₫';
}

function changeQty(id, delta) {
  soLuong[id] = Math.max(0, (soLuong[id] || 0) + delta);
  document.getElementById('qty-' + id).textContent = soLuong[id];

  // Cập nhật subtotal dưới card
  const sub = soLuong[id] * GIA_COMBO[id].gia;
  const subEl = document.getElementById('sub-' + id);
  subEl.textContent = fmt(sub);
  subEl.className   = 'qty-subtotal' + (soLuong[id] > 0 ? ' has-val' : '');

  // Highlight card
  const card = document.getElementById('card-' + id);
  card.classList.toggle('selected', soLuong[id] > 0);

  updateSummary();
  if (soLuong[id] > 0) showToast('✅ Đã thêm ' + GIA_COMBO[id].ten);
}

function toggleCard(id) {
  // Click vào card = toggle 1 sản phẩm nếu qty đang = 0
  if ((soLuong[id] || 0) === 0) changeQty(id, 1);
}

function updateSummary() {
  let tongCombo = 0;
  let html = '';

  for (const [id, qty] of Object.entries(soLuong)) {
    if (qty > 0) {
      const sub = qty * GIA_COMBO[id].gia;
      tongCombo += sub;
      html += `<div class="combo-summary-item">
        <span class="name">${GIA_COMBO[id].ten} ×${qty}</span>
        <span>${fmt(sub)}</span>
      </div>`;
    }
  }

  document.getElementById('combo-summary-list').innerHTML = html;
  document.getElementById('sum-combo').textContent  = fmt(tongCombo);
  document.getElementById('sum-total').textContent  = fmt(TONG_VE + tongCombo);
  document.getElementById('inp-tong-combo').value   = tongCombo;

  // Build JSON for server
  const arr = [];
  for (const [id, qty] of Object.entries(soLuong)) {
    if (qty > 0) arr.push({ combo_id: parseInt(id), so_luong: qty });
  }
  document.getElementById('inp-combos').value = JSON.stringify(arr);
}

function skipCombo() {
  document.getElementById('inp-combos').value    = '[]';
  document.getElementById('inp-tong-combo').value = 0;
  document.getElementById('frm-checkout').submit();
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._tid);
  t._tid = setTimeout(() => t.classList.remove('show'), 2000);
}

// Init
updateSummary();
</script>

<footer class="footer">
  <div>© <?= date('Y') ?> CGV Cinemas — Mọi quyền được bảo lưu.</div>
</footer>
</body>
</html>