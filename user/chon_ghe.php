<?php
include '../config/db.php';

// Giải phóng session lock sớm nhất có thể
// Nếu không, EventSource (SSE) cùng trình duyệt sẽ bị block
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$suat_chieu_id = $_GET['suat_id'] ?? 0;

if ($suat_chieu_id == 0) {
    die("Thiếu suất chiếu");
}

$sql = "
SELECT 
    ghe.id,
    ghe.ten_ghe,
    EXISTS (
        SELECT 1 
        FROM ve 
        WHERE ve.ghe_id = ghe.id 
        AND ve.suat_chieu_id = $suat_chieu_id
    ) AS da_dat
FROM ghe
WHERE ghe.phong_id = (SELECT phong_id FROM suat_chieu WHERE id = $suat_chieu_id LIMIT 1)
-- order by row letter then seat number for natural ordering
ORDER BY
    LEFT(ghe.ten_ghe, 1),
    CAST(SUBSTRING(ghe.ten_ghe, 2) AS UNSIGNED)
";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi SQL: " . mysqli_error($conn));
}

// Lấy thông tin phim và suất chiếu
$info_sql = "
SELECT 
    p.id AS phim_id, p.ten_phim, p.poster, p.the_loai, p.thoi_luong, p.mo_ta,
    s.ngay, s.gio, s.gia,
    pc.ten_phong, r.ten_rap
FROM suat_chieu s
LEFT JOIN phim p ON s.phim_id = p.id
LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
LEFT JOIN rap r ON pc.rap_id = r.id
WHERE s.id = $suat_chieu_id
";
$info_result = mysqli_query($conn, $info_sql);
$info = mysqli_fetch_assoc($info_result);

if (!$info) {
    die("Không tìm thấy suất chiếu");
}

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }

// Đếm tổng ghế và ghế đã đặt cho stats bar
$count_sql = "
    SELECT
        COUNT(*) AS tong_ghe,
        SUM(EXISTS(SELECT 1 FROM ve WHERE ve.ghe_id = ghe.id AND ve.suat_chieu_id = $suat_chieu_id)) AS da_dat
    FROM ghe
    WHERE ghe.phong_id = (SELECT phong_id FROM suat_chieu WHERE id = $suat_chieu_id LIMIT 1)
";
$count_row = mysqli_fetch_assoc(mysqli_query($conn, $count_sql));
$tong_ghe  = (int)($count_row['tong_ghe'] ?? 0);
$ghe_dat   = (int)($count_row['da_dat']   ?? 0);
$ghe_trong = $tong_ghe - $ghe_dat;
$pct_trong = $tong_ghe > 0 ? round($ghe_trong / $tong_ghe * 100) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chọn ghế — <?= htmlspecialchars($info['ten_phim']) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/movie-detail.css">
<link rel="stylesheet" href="../assets/css/search.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700;800;900&display=swap');

/* ── Layout ── */
.seat-section { margin-top: 24px; }

/* ── Screen ── */
.screen-bar {
  text-align: center;
  padding: 10px 20px 16px;
  margin-bottom: 28px;
  position: relative;
  color: rgba(255,255,255,0.45);
  font-size: 11px; font-weight: 700; letter-spacing: 2.5px;
  text-transform: uppercase;
}
.screen-bar::after {
  content: '';
  display: block;
  height: 4px; width: 55%; margin: 8px auto 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
  border-radius: 2px;
  box-shadow: 0 2px 12px rgba(255,255,255,0.1);
}

/* ── Seat wrapper ── */
.seat-wrapper { overflow-x: auto; padding-bottom: 8px; }

/* ── Row with label ── */
.seat-row-wrap {
  display: flex; align-items: center;
  gap: 10px; margin-bottom: 8px;
}
.row-label {
  width: 22px; flex-shrink: 0;
  font-size: 11px; font-weight: 800;
  color: rgba(255,255,255,0.3);
  text-align: center; letter-spacing: 0.5px;
  font-family: 'Be Vietnam Pro', sans-serif;
}
.seat-row {
  display: flex;
  justify-content: center;
  gap: 6px;
  flex-wrap: nowrap;
  flex: 1;
}

/* ── Base seat ── */
.seat {
  width: 36px; height: 36px;
  border-radius: 7px 7px 4px 4px;
  border: 1px solid rgba(255,255,255,0.1);
  font-size: 9px; font-weight: 800;
  cursor: pointer;
  transition: transform .12s, box-shadow .12s, background .12s, border-color .12s;
  background: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.6);
  font-family: 'Be Vietnam Pro', sans-serif;
  position: relative;
  flex-shrink: 0;
}
/* headrest detail */
.seat::before {
  content: '';
  position: absolute;
  top: 0; left: 3px; right: 3px; height: 3px;
  background: rgba(255,255,255,0.18);
  border-radius: 0 0 3px 3px;
}
.seat:hover:not(.booked):not(.empty):not(:disabled) {
  background: rgba(34,197,94,0.22);
  border-color: rgba(34,197,94,0.55);
  color: #86efac;
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 6px 16px rgba(34,197,94,0.25);
  z-index: 2;
}
.seat.selected {
  background: linear-gradient(135deg,#e8192c,#c01020);
  border-color: rgba(232,25,44,0.6);
  color: #fff;
  box-shadow: 0 5px 16px rgba(232,25,44,0.45);
  transform: translateY(-3px) scale(1.05);
  z-index: 2;
}
.seat.selected::before { background: rgba(255,255,255,0.35); }

/* Đã đặt */
.seat.booked {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.05);
  color: rgba(255,255,255,0.15);
  cursor: not-allowed;
}
.seat.booked::before { background: rgba(255,255,255,0.05); }



.seat.empty { visibility: hidden; background: transparent; cursor: default; border: none; }

/* ── Seat stats bar ── */
.seat-stats {
  display: flex; gap: 12px; flex-wrap: wrap;
  align-items: center;
  margin-bottom: 16px;
  padding: 12px 16px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px;
}
.stat-item { display: flex; align-items: center; gap: 6px; font-size: 12px; }
.stat-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.stat-dot.av  { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); }
.stat-dot.bk  { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); }
.stat-dot.sl  { background: linear-gradient(135deg,#e8192c,#c01020); }

.stat-val { font-weight: 800; color: #f1f5f9; }
.stat-lbl { color: #64748b; }

/* Progress bar */
.seat-progress {
  flex: 1; min-width: 120px;
  height: 6px; background: rgba(255,255,255,0.06);
  border-radius: 999px; overflow: hidden; position: relative;
}
.seat-progress-fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(90deg,#e8192c,#ff6b4a);
  transition: width .4s ease;
}
.seat-progress-lbl {
  font-size: 11px; color: #64748b; white-space: nowrap;
}

/* ── Legend ── */
.seat-legend {
  display: flex; gap: 16px; justify-content: center;
  margin: 16px 0 8px; flex-wrap: wrap;
}
.legend-item { display: flex; align-items: center; gap: 7px; font-size: 12px; color: #94a3b8; }
.legend-dot { width: 18px; height: 18px; border-radius: 5px; }
.legend-dot.available { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); }

.legend-dot.selected-l{ background: linear-gradient(135deg,#e8192c,#c01020); }
.legend-dot.booked-l  { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); }

/* ── Checkout bar ── */
.checkout-bar {
  position: sticky; bottom: 0;
  background: rgba(8,11,20,0.96);
  backdrop-filter: blur(20px) saturate(160%);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(255,255,255,0.08);
  padding: 14px 20px;
  margin: 0 -20px;
  display: flex; align-items: center; gap: 16px;
  flex-wrap: wrap; z-index: 100;
}
.checkout-info { flex: 1; min-width: 180px; }
.checkout-label { font-size: 11px; font-weight: 700; letter-spacing: 0.6px; color: #475569; text-transform: uppercase; margin-bottom: 4px; }
.checkout-seats-list {
  display: flex; flex-wrap: wrap; gap: 5px;
  min-height: 26px; align-items: center;
}
.seat-tag {
  display: inline-flex; align-items: center; gap: 4px;
  background: rgba(232,25,44,0.15); border: 1px solid rgba(232,25,44,0.3);
  color: #fca5a5; font-size: 12px; font-weight: 700;
  padding: 3px 9px; border-radius: 6px;
  animation: tagPop .2s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes tagPop { from{transform:scale(0.7);opacity:0} to{transform:scale(1);opacity:1} }

.checkout-empty { font-size: 13px; color: #475569; font-style: italic; }

.checkout-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.checkout-subtotal { font-size: 12px; color: #64748b; }
.checkout-total { font-size: 22px; font-weight: 900; color: #f5c518; line-height: 1; }
.btn-checkout {
  padding: 13px 32px;
  background: linear-gradient(135deg,#e8192c,#c01020);
  color: #fff; border: none; border-radius: 12px;
  font-family: 'Be Vietnam Pro', sans-serif;
  font-size: 14px; font-weight: 800; letter-spacing: 0.3px;
  cursor: pointer; transition: all .22s;
  box-shadow: 0 6px 20px rgba(232,25,44,.35);
  white-space: nowrap; position: relative; overflow: hidden;
}
.btn-checkout::before {
  content: ''; position: absolute; top: -50%; left: -60%;
  width: 40%; height: 200%;
  background: rgba(255,255,255,.18); transform: skewX(-20deg);
  transition: left .5s;
}
.btn-checkout:hover::before { left: 130%; }
.btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(232,25,44,.5); }
.btn-checkout:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Realtime status badge ── */
#sseStatus {
  position: fixed; top: 72px; right: 16px;
  font-size: 11px; font-weight: 700;
  padding: 4px 12px; border-radius: 999px;
  z-index: 1000; transition: all .3s;
  pointer-events: none;
}

@media (max-width: 640px) {
  .seat { width: 30px; height: 30px; font-size: 8px; }
  .seat-row-wrap { gap: 6px; }
  .row-label { width: 16px; font-size: 10px; }
  .checkout-bar { flex-direction: column; align-items: stretch; }
  .checkout-right { flex-direction: row; align-items: center; justify-content: space-between; }
  .btn-checkout { width: 100%; text-align: center; }
}
</style>
</head>
<body class="movie-detail-page">

<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="index.php" class="nav-link"><span class="icon">🎬</span><span class="text">PHIM</span></a>
      </div>
      <div class="search-wrap" id="searchWrap">
        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm phim..." autocomplete="off">
        <span class="search-icon">🔍</span>
        <span class="search-spinner"></span>
        <div class="search-dropdown" id="searchDropdown"></div>
      </div>
      <div class="header-nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="../user/ve_cua_toi.php" class="btn btn-sm"><span class="icon">🎟️</span><span class="text">VÉ CỦA TÔI</span></a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<main class="md-container">
  <a class="back" href="chi_tiet_phim.php?id=<?= $info['phim_id'] ?? 0 ?>">← Quay lại chi tiết phim</a>

  <?php if (!empty($_GET['taken'])): ?>
  <div id="takenAlert" style="
    display:flex; align-items:center; gap:12px;
    background:rgba(232,25,44,.12); border:1px solid rgba(232,25,44,.35);
    color:#fca5a5; padding:14px 18px; border-radius:14px; margin-bottom:20px;
    font-size:13px; font-weight:600; animation:toastIn .3s ease;
  ">
    <span style="font-size:20px;">⚠️</span>
    <div>
      <div style="color:#fff;font-weight:800;margin-bottom:3px;">Ghế đã được người khác đặt!</div>
      Ghế <strong style="color:#ff6b6b;"><?= htmlspecialchars($_GET['taken']) ?></strong>
      vừa bị đặt trước khi bạn thanh toán. Vui lòng chọn ghế khác.
    </div>
    <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;color:#fca5a5;cursor:pointer;font-size:18px;">×</button>
  </div>
  <?php endif; ?>

  <!-- Showtime info card -->
  <div style="background:linear-gradient(135deg,#111827,#0d1322);border:1px solid rgba(255,255,255,0.07);border-radius:18px;padding:20px 22px;margin-bottom:24px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(232,25,44,.4),transparent);"></div>
    <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
      <!-- Poster -->
      <img src="../assets/images/<?= htmlspecialchars($info['poster']) ?>"
           style="width:54px;height:76px;object-fit:cover;border-radius:8px;flex-shrink:0;box-shadow:0 4px 14px rgba(0,0,0,.5);" alt="">
      <!-- Info -->
      <div style="flex:1;min-width:0;">
        <div style="font-size:17px;font-weight:800;color:#f1f5f9;margin-bottom:10px;line-height:1.2;"><?= htmlspecialchars($info['ten_phim']) ?></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);padding:4px 10px;border-radius:8px;color:#94a3b8;">
            📅 <strong style="color:#f1f5f9;"><?= date('d/m/Y', strtotime($info['ngay'])) ?></strong>
          </span>
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;background:rgba(232,25,44,.1);border:1px solid rgba(232,25,44,.2);padding:4px 10px;border-radius:8px;color:#fca5a5;">
            ⏰ <strong style="color:#fff;"><?= substr($info['gio'],0,5) ?></strong>
          </span>
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);padding:4px 10px;border-radius:8px;color:#94a3b8;">
            🏢 <?= htmlspecialchars($info['ten_rap'] ?? 'Rạp') ?>
          </span>
          <?php if (!empty($info['ten_phong'])): ?>
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);padding:4px 10px;border-radius:8px;color:#94a3b8;">
            🚪 <?= htmlspecialchars($info['ten_phong']) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <!-- Price -->
      <div style="text-align:right;flex-shrink:0;">
        <div style="font-size:10px;font-weight:700;letter-spacing:.8px;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Giá vé</div>
        <div style="font-size:22px;font-weight:900;color:#f5c518;line-height:1;"><?= fmt_money($info['gia']) ?></div>
        <div style="font-size:11px;color:#64748b;margin-top:2px;">/ ghế</div>
      </div>
    </div>
  </div>

  <h2 style="font-size:15px;font-weight:800;color:#f1f5f9;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    🪑 Chọn ghế ngồi
    <span style="flex:1;height:1px;background:linear-gradient(90deg,rgba(232,25,44,.4),transparent);"></span>
  </h2>

  <section class="seat-section">
    <!-- Stats bar -->
    <div class="seat-stats">
      <div class="stat-item">
        <div class="stat-dot av"></div>
        <span class="stat-val" id="statTrong"><?= $ghe_trong ?></span>
        <span class="stat-lbl">ghế trống</span>
      </div>
      <div class="stat-item">
        <div class="stat-dot bk"></div>
        <span class="stat-val"><?= $ghe_dat ?></span>
        <span class="stat-lbl">đã đặt</span>
      </div>

      <div class="stat-item" style="flex:1;flex-direction:column;align-items:flex-end;gap:4px;">
        <div class="seat-progress-lbl"><span id="statPct"><?= $pct_trong ?></span>% còn trống</div>
        <div class="seat-progress">
          <div class="seat-progress-fill" id="statBar" style="width:<?= $pct_trong ?>%"></div>
        </div>
      </div>
    </div>

    <div class="screen-bar">Màn hình</div>

    <div class="seat-wrapper">
<?php
// build nested array of seats grouped by row letter
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rowChar = substr($row['ten_ghe'], 0, 1);
    $rows[$rowChar][] = $row;
}

$maxCount = 0;
foreach ($rows as $r) {
    $maxCount = max($maxCount, count($r));
}

foreach ($rows as $rowChar => $rowSeats) {
    echo "<div class='seat-row-wrap'>";
    echo "<div class='row-label'>" . htmlspecialchars($rowChar) . "</div>";
    echo "<div class='seat-row'>";
    foreach ($rowSeats as $r) {
        $class = $r['da_dat'] ? 'seat booked' : 'seat';
        $disabled = $r['da_dat'] ? 'disabled' : '';
        echo "<button class='$class' data-seat='{$r['ten_ghe']}' $disabled>
                {$r['ten_ghe']}
              </button>";
    }
    $pad = $maxCount - count($rowSeats);
    for ($i = 0; $i < $pad; $i++) {
        echo "<div class='seat empty'></div>";
    }
    echo "</div>";
    echo "</div>";
}
?>
        </div>
    </div><!-- end seat-wrapper -->

    <!-- Legend -->
    <div class="seat-legend">
      <div class="legend-item"><div class="legend-dot available"></div>Còn trống</div>

      <div class="legend-item"><div class="legend-dot selected-l"></div>Đang chọn</div>
      <div class="legend-item"><div class="legend-dot booked-l"></div>Đã đặt</div>
    </div>
  </section>

  <!-- Sticky checkout bar -->
  <div class="checkout-bar">
    <div class="checkout-info">
      <div class="checkout-label">Ghế đã chọn</div>
      <div class="checkout-seats-list" id="seatTagList">
        <span class="checkout-empty" id="emptyMsg">Chưa chọn ghế nào</span>
      </div>
    </div>
    <div class="checkout-right">
      <div class="checkout-subtotal" id="subtotal"></div>
      <div class="checkout-total" id="total">0₫</div>
    </div>
    <form action="payment.php" method="POST" style="margin:0;">
      <input type="hidden" name="ghe" id="seat-input">
      <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">
      <button type="submit" class="btn-checkout" id="btnCheckout" disabled>Thanh toán →</button>
    </form>
  </div>
</main>

<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas</div></footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- START OF COMBINED SCRIPT LOGIC ---

    const suatChieuId = <?= json_encode($suat_chieu_id) ?>;
    const giaVe = <?= json_encode($info['gia']) ?>;
    
    let selectedSeats = []; // Mảng chứa các ghế đang được chọn bởi người dùng hiện tại
    
    // removed - using tag system
    const totalDisplay = document.getElementById("total");
    const seatInput = document.getElementById("seat-input");
    const seatWrapper = document.querySelector(".seat-wrapper");

    // ── Cập nhật checkout bar với seat tags ──
    function updateCheckoutUI() {
        selectedSeats.sort();
        seatInput.value = selectedSeats.join(",");

        const tagList  = document.getElementById('seatTagList');
        const emptyMsg = document.getElementById('emptyMsg');
        const subtotal = document.getElementById('subtotal');
        const btnCO    = document.getElementById('btnCheckout');

        // Xoá tags cũ (giữ emptyMsg)
        tagList.querySelectorAll('.seat-tag').forEach(t => t.remove());

        if (selectedSeats.length === 0) {
            emptyMsg.style.display = '';
            totalDisplay.innerText = '0₫';
            subtotal.textContent   = '';
            btnCO.disabled = true;
        } else {
            emptyMsg.style.display = 'none';
            btnCO.disabled = false;

            selectedSeats.forEach(name => {
                const tag = document.createElement('span');
                tag.className = 'seat-tag';
                tag.textContent = name;
                tagList.appendChild(tag);
            });

            const total = selectedSeats.length * giaVe;
            totalDisplay.innerText = total.toLocaleString('vi-VN') + '₫';
            subtotal.textContent   = selectedSeats.length + ' ghế × ' + giaVe.toLocaleString('vi-VN') + '₫';
        }
    }

    // ── Cập nhật stats bar (ghế trống, progress) ──
    function updateStats(delta) {
        const el = document.getElementById('statTrong');
        const bar = document.getElementById('statBar');
        const pct = document.getElementById('statPct');
        if (!el) return;
        const cur = Math.max(0, (parseInt(el.textContent) || 0) + delta);
        el.textContent = cur;
        const total = <?= $tong_ghe ?>;
        const p = total > 0 ? Math.round(cur / total * 100) : 0;
        if (bar) bar.style.width = p + '%';
        if (pct) pct.textContent = p;
    }

    // Xử lý khi người dùng click chọn ghế
    seatWrapper.addEventListener("click", function (e) {
        const seatButton = e.target.closest('.seat');
        
        if (!seatButton || seatButton.classList.contains("booked")) {
            return; // Bỏ qua nếu không phải ghế hoặc ghế đã được đặt
        }

        const seatName = seatButton.dataset.seat;
        seatButton.classList.toggle("selected");

        if (selectedSeats.includes(seatName)) {
            // Nếu ghế đã có trong danh sách, loại bỏ nó
            selectedSeats = selectedSeats.filter(s => s !== seatName);
        } else {
            // Nếu chưa có, thêm nó vào
            selectedSeats.push(seatName);
        }

        updateCheckoutUI(); // Cập nhật lại UI
    });

// Ngăn chặn thanh toán nếu chưa chọn ghế
    const checkoutForm = document.querySelector('form[action="payment.php"]');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (selectedSeats.length === 0) {
                e.preventDefault();
                // Flash checkout bar
                const bar = document.querySelector('.checkout-bar');
                if (bar) {
                    bar.style.borderTopColor = 'rgba(232,25,44,.6)';
                    bar.style.boxShadow = '0 -4px 20px rgba(232,25,44,.2)';
                    setTimeout(() => {
                        bar.style.borderTopColor = '';
                        bar.style.boxShadow = '';
                    }, 800);
                }
            }
        });
    }

    // ── Hàm xử lý chung khi nhận danh sách ghế thay đổi ──
    function applySeatsUpdate(seats, isInit) {
        let selectionChanged = false;

        seats.forEach(seat => {
            const el = seatWrapper.querySelector(`[data-seat='${seat.ten_ghe}']`);
            if (!el) return;

            if (seat.da_dat === 1 && !el.classList.contains('booked')) {
                // Ghế vừa bị người khác đặt → khóa ngay
                el.classList.add('booked');
                el.classList.remove('selected');
                el.disabled = true;

                if (selectedSeats.includes(seat.ten_ghe)) {
                    selectedSeats = selectedSeats.filter(s => s !== seat.ten_ghe);
                    selectionChanged = true;
                }

                // Hiệu ứng flash đỏ nhẹ + update stats
                if (!isInit) {
                    el.style.transition = 'background .15s, transform .15s';
                    el.style.background = 'rgba(232,25,44,.5)';
                    el.style.transform  = 'scale(0.9)';
                    setTimeout(() => { el.style.background = ''; el.style.transform = ''; }, 500);
                    updateStats(-1);  // giảm 1 ghế trống
                }
            }
        });

        if (selectionChanged) {
            updateCheckoutUI();
            showSeatTakenToast();
        }
    }

    // ── Toast thông báo thay vì alert() cứng ──
    function showSeatTakenToast() {
        let toast = document.getElementById('seatToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'seatToast';
            toast.style.cssText = `
                position:fixed; bottom:90px; left:50%; transform:translateX(-50%);
                background:#111827; border:1px solid rgba(232,25,44,.4); color:#fca5a5;
                padding:12px 20px; border-radius:12px; font-size:13px; font-weight:600;
                box-shadow:0 8px 24px rgba(0,0,0,.5); z-index:9999;
                animation:toastIn .3s ease;
            `;
            document.head.insertAdjacentHTML('beforeend',
                '<style>@keyframes toastIn{from{opacity:0;transform:translateX(-50%) translateY(10px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}</style>');
            document.body.appendChild(toast);
        }
        toast.textContent = '⚠️ Ghế bạn chọn vừa bị người khác đặt!';
        toast.style.display = 'block';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 4000);
    }

// ── Connection status indicator ──
    function showConnectionStatus(state) {
        let el = document.getElementById('sseStatus');
        if (!el) {
            el = document.createElement('div');
            el.id = 'sseStatus';
            document.body.appendChild(el);
        }
        const map = {
            connecting: ['⟳ Đang kết nối...', 'rgba(245,185,66,.15)', '1px solid rgba(245,185,66,.4)', '#f5b942'],
            connected:  ['● Realtime',        'rgba(34,197,94,.12)',  '1px solid rgba(34,197,94,.3)', '#86efac'],
            error:      ['● Polling',         'rgba(148,163,184,.1)','1px solid rgba(148,163,184,.2)','#94a3b8'],
        };
        const [text, bg, border, color] = map[state] || map.error;
        Object.assign(el.style, {background:bg, border, color});
        el.textContent = text;
    }

// ── Kết nối SSE — server đẩy ngay khi có thay đổi ──
    function connectSSE() {
      console.log('Connecting SSE to seat_events.php?suat_id=', suatChieuId);
        if (!window.EventSource) {
            // Fallback: trình duyệt cũ dùng polling 2s
            setInterval(async () => {
                try {
                    const r = await fetch(`../get_seats.php?suat_id=${suatChieuId}&_=${Date.now()}`);
                    const seats = await r.json();
                    applySeatsUpdate(seats, false);
                } catch(e) {}
            }, 2000);
            return;
        }

const sse = new EventSource(`seat_events.php?suat_id=${suatChieuId}`);

        // Nhận snapshot đầy đủ khi mới kết nối
        sse.addEventListener('init', e => {
            console.log('SSE init:', e.data);
            applySeatsUpdate(JSON.parse(e.data), true);
        });

        // Nhận chỉ những ghế thay đổi — gần như tức thì
        sse.addEventListener('seats_update', e => {
            console.log('SSE update:', e.data);
            applySeatsUpdate(JSON.parse(e.data), false);
        });

        // Server đóng → EventSource tự reconnect sau ~3s
        sse.addEventListener('reconnect', () => sse.close());

        sse.onerror = () => {
            // Nếu SSE lỗi liên tục (server không hỗ trợ), fallback polling
            if (sse.readyState === EventSource.CLOSED) {
                setTimeout(connectSSE, 3000);
            }
        };
    }

    // --- END OF COMBINED SCRIPT LOGIC ---

    // Khởi chạy
    updateCheckoutUI();
    console.log('DOM loaded, starting SSE for suat_id', suatChieuId);
    connectSSE();   // Thay setInterval bằng SSE
});
</script>
<script src="../assets/js/search.js"></script>
<script>
(function(){
  const h=document.querySelector('.header');
  if(!h) return;
  window.addEventListener('scroll',()=>h.classList.toggle('shrink',scrollY>50),{passive:true});
})();
</script>
</body>
</html>