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
/* ── Seat map ── */
.seat-section { margin-top: 28px; }
.screen-bar {
  text-align: center;
  padding: 10px;
  margin-bottom: 24px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 2px;
  color: rgba(255,255,255,0.5);
  text-transform: uppercase;
  position: relative;
}
.screen-bar::after {
  content: '';
  display: block;
  height: 3px;
  margin: 8px auto 0;
  width: 60%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  border-radius: 2px;
}
.seat-wrapper { overflow-x: auto; padding-bottom: 10px; }
.seat-row {
  display: flex;
  justify-content: center;
  gap: 7px;
  margin-bottom: 7px;
  flex-wrap: nowrap;
}
.seat {
  width: 38px; height: 38px;
  border-radius: 8px 8px 4px 4px;
  border: none;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all .15s;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  color: #f1f5f9;
  font-family: 'Be Vietnam Pro', sans-serif;
  position: relative;
}
.seat::before {
  content: '';
  position: absolute;
  top: 0; left: 2px; right: 2px;
  height: 3px;
  background: rgba(255,255,255,0.2);
  border-radius: 0 0 2px 2px;
}
.seat:hover:not(.booked):not(.empty) {
  background: rgba(34,197,94,0.25);
  border-color: rgba(34,197,94,0.5);
  color: #86efac;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(34,197,94,0.2);
}
.seat.selected {
  background: linear-gradient(135deg, #e8192c, #c01020);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 14px rgba(232,25,44,0.4);
  transform: translateY(-2px);
}
.seat.booked {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.06);
  color: rgba(255,255,255,0.2);
  cursor: not-allowed;
}
.seat.empty { visibility: hidden; background: transparent; cursor: default; border: none; }

/* Legend */
.seat-legend {
  display: flex; gap: 20px; justify-content: center;
  margin: 20px 0; flex-wrap: wrap;
}
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #94a3b8; }
.legend-dot {
  width: 20px; height: 20px; border-radius: 5px;
}
.legend-dot.available { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); }
.legend-dot.selected-l { background: linear-gradient(135deg,#e8192c,#c01020); }
.legend-dot.booked-l { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }

/* Checkout */
.checkout-bar {
  position: sticky;
  bottom: 0;
  background: rgba(13,19,34,0.95);
  backdrop-filter: blur(16px);
  border-top: 1px solid rgba(255,255,255,0.08);
  padding: 16px 20px;
  margin: 0 -20px;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  z-index: 100;
}
.checkout-info { flex: 1; min-width: 200px; }
.checkout-seats { font-size: 13px; color: #94a3b8; margin-bottom: 4px; }
.checkout-seats strong { color: #f1f5f9; }
.checkout-total { font-size: 20px; font-weight: 900; color: #f5c518; }
.btn-checkout {
  padding: 13px 28px;
  background: linear-gradient(135deg,#e8192c,#c01020);
  color: #fff; border: none; border-radius: 12px;
  font-family: 'Be Vietnam Pro', sans-serif;
  font-size: 14px; font-weight: 800;
  cursor: pointer; transition: all .22s;
  box-shadow: 0 6px 20px rgba(232,25,44,.35);
  white-space: nowrap;
}
.btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(232,25,44,.5); }

@media (max-width: 480px) {
  .seat { width: 30px; height: 30px; font-size: 9px; }
  .checkout-bar { flex-direction: column; align-items: stretch; }
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

  <!-- Showtime info card -->
  <div style="background:linear-gradient(135deg,#111827,#0d1322);border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:18px 20px;margin-bottom:28px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
      <div style="font-size:18px;font-weight:800;color:#f1f5f9;margin-bottom:8px;"><?= htmlspecialchars($info['ten_phim']) ?></div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <span style="font-size:13px;color:#64748b;">📅 <strong style="color:#f1f5f9;"><?= date('d/m/Y', strtotime($info['ngay'])) ?></strong></span>
        <span style="font-size:13px;color:#64748b;">⏰ <strong style="color:#f1f5f9;"><?= substr($info['gio'],0,5) ?></strong></span>
        <span style="font-size:13px;color:#64748b;">🏢 <strong style="color:#f1f5f9;"><?= htmlspecialchars($info['ten_rap'] ?? '') ?></strong></span>
        <?php if (!empty($info['ten_phong'])): ?>
        <span style="font-size:13px;color:#64748b;">🚪 <strong style="color:#f1f5f9;"><?= htmlspecialchars($info['ten_phong']) ?></strong></span>
        <?php endif; ?>
      </div>
    </div>
    <div style="font-size:22px;font-weight:900;color:#f5c518;background:rgba(245,197,24,.1);border:1px solid rgba(245,197,24,.2);padding:8px 16px;border-radius:10px;white-space:nowrap;">
      <?= fmt_money($info['gia']) ?> / ghế
    </div>
  </div>

  <h2 style="font-size:16px;font-weight:800;color:#f1f5f9;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    🪑 Chọn ghế ngồi
    <span style="flex:1;height:1px;background:linear-gradient(90deg,rgba(232,25,44,.4),transparent);"></span>
  </h2>

  <section class="seat-section">
    <div class="screen-bar">Màn hình</div>

    <div class="seat-wrapper">
<?php
// build nested array of seats grouped by row letter
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rowChar = substr($row['ten_ghe'], 0, 1);
    $rows[$rowChar][] = $row;
}

// determine maximum count so every row can be padded
$maxCount = 0;
foreach ($rows as $r) {
    $maxCount = max($maxCount, count($r));
}

foreach ($rows as $rowChar => $rowSeats) {
    echo "<div class='seat-row'>";
    foreach ($rowSeats as $r) {
        $class = $r['da_dat'] ? 'seat booked' : 'seat';
        echo "<button 
                class='$class' 
                data-seat='{$r['ten_ghe']}'
                ".($r['da_dat'] ? 'disabled' : '').">
                {$r['ten_ghe']}
              </button>";
    }
    // pad with empty placeholders to reach maxCount
    $pad = $maxCount - count($rowSeats);
    for ($i = 0; $i < $pad; $i++) {
        echo "<div class='seat empty'></div>";
    }
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

  <!-- Movie info mini -->
  <div style="display:flex;align-items:flex-start;gap:16px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;margin-top:20px;margin-bottom:80px;">
    <img src="../assets/images/<?= htmlspecialchars($info['poster']) ?>" style="width:60px;height:84px;object-fit:cover;border-radius:8px;flex-shrink:0;" alt="">
    <p style="font-size:13px;line-height:1.7;color:rgba(241,245,249,.6);margin:0;">
      <?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'] ?? '', 0, 200, '...'))) ?>
    </p>
  </div>

  <!-- Sticky checkout bar -->
  <div class="checkout-bar">
    <div class="checkout-info">
      <div class="checkout-seats">Ghế đã chọn: <strong id="selected-seats">Chưa chọn ghế</strong></div>
      <div class="checkout-total" id="total">0₫</div>
    </div>
    <form action="payment.php" method="POST" style="margin:0;">
      <input type="hidden" name="ghe" id="seat-input">
      <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">
      <button type="submit" class="btn-checkout">Thanh toán →</button>
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
    
    const selectedSeatsDisplay = document.getElementById("selected-seats");
    const totalDisplay = document.getElementById("total");
    const seatInput = document.getElementById("seat-input");
    const seatWrapper = document.querySelector(".seat-wrapper");

    // Hàm cập nhật UI (danh sách ghế đã chọn và tổng tiền)
    function updateCheckoutUI() {
        selectedSeats.sort(); // Sắp xếp cho đẹp
        selectedSeatsDisplay.innerText = selectedSeats.length > 0 ? selectedSeats.join(", ") : "Chưa có ghế nào";
        totalDisplay.innerText = (selectedSeats.length * giaVe).toLocaleString("vi-VN") + " đ";
        seatInput.value = selectedSeats.join(",");
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

// Ngăn chặn việc thanh toán nếu chưa chọn ghế
    const checkoutForm = document.querySelector('.checkout form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (selectedSeats.length === 0) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất một ghế.');
            }
        });
    } else {
        console.error('Checkout form not found');
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

                // Hiệu ứng flash đỏ nhẹ để người dùng nhận ra
                if (!isInit) {
                    el.style.transition = 'background .15s';
                    el.style.background = 'rgba(232,25,44,.5)';
                    setTimeout(() => { el.style.background = ''; }, 400);
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