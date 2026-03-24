<?php
include "../config/db.php";
session_start();

// ── Validate input ──────────────────────────────────────────
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    die("Lỗi: Dữ liệu không hợp lệ. Vui lòng thử lại.");
}

$user_id       = $_SESSION['user_id'] ?? 1;
$suat_chieu_id = (int)$_POST['suat_chieu_id'];
$ghe_array = array_filter(explode(",", $_POST['ghe']));
$voucher_code = trim($_POST['voucher_code'] ?? '');
$combo_items_raw = $_POST['combo_items'] ?? '[]';
$combo_items = json_decode($combo_items_raw, true);
if (!is_array($combo_items)) $combo_items = [];

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

// --- Begin Transaction với SERIALIZABLE để chống double booking ---
mysqli_query($conn, "SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE");
$inserted_ve_ids = [];
mysqli_begin_transaction($conn);
try {
    // Chuẩn bị statements
    // SELECT FOR UPDATE: lock hàng ghế lại trong transaction
    // Nếu 2 request cùng lúc, request sau sẽ chờ request trước commit/rollback
    $lock_stmt = mysqli_prepare($conn,
        "SELECT g.id FROM ghe g
         WHERE g.ten_ghe = ? AND g.phong_id = ?
         AND NOT EXISTS (
             SELECT 1 FROM ve
             WHERE ve.ghe_id = g.id AND ve.suat_chieu_id = ?
         )
         FOR UPDATE"
    );
    $insert_ve_stmt = mysqli_prepare($conn,
        "INSERT INTO ve (user_id, ghe_id, suat_chieu_id) VALUES (?, ?, ?)"
    );

    foreach ($ghe_array as $ten_ghe) {
        $ten_ghe = trim($ten_ghe);
        if ($ten_ghe === '') continue;

        // Lock + kiểm tra ghế còn trống trong 1 query atomic
        mysqli_stmt_bind_param($lock_stmt, "sii", $ten_ghe, $phong_id, $suat_chieu_id);
        mysqli_stmt_execute($lock_stmt);
        $result_ghe = mysqli_stmt_get_result($lock_stmt);
        $ghe_row    = mysqli_fetch_assoc($result_ghe);

        if (!$ghe_row) {
            // Ghế không tồn tại HOẶC đã bị người khác đặt mất (race condition bị chặn!)
            throw new Exception("GHE_TAKEN::{$ten_ghe}");
        }
        $ghe_id = (int)$ghe_row['id'];

        // Insert vé
        mysqli_stmt_bind_param($insert_ve_stmt, "iii", $user_id, $ghe_id, $suat_chieu_id);
        if (!mysqli_stmt_execute($insert_ve_stmt)) {
            throw new Exception("Lỗi hệ thống khi đặt ghế '{$ten_ghe}'.");
        }
        $ve_id = (int)mysqli_insert_id($conn);
        if ($ve_id) $last_ve_id = $ve_id;
    }

    mysqli_commit($conn);
    mysqli_stmt_close($lock_stmt);
    mysqli_stmt_close($insert_ve_stmt);


// ── Cộng điểm TTVH sau khi mua vé ────────────────────────────────
// Tự động add column nếu chưa có
if (!column_exists($conn, "users", "points")) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN points INT DEFAULT 0 NOT NULL");
}

// Lấy giá suất chiếu để tính điểm (1.000đ = 1 điểm)
$gia_suat = 0;
$gs = mysqli_prepare($conn, "SELECT gia FROM suat_chieu WHERE id = ?");
mysqli_stmt_bind_param($gs, "i", $suat_chieu_id);
mysqli_stmt_execute($gs);
$gs_row = mysqli_fetch_assoc(mysqli_stmt_get_result($gs));
mysqli_stmt_close($gs);
if ($gs_row) $gia_suat = (int)$gs_row["gia"];
$earned_points = (int)floor($gia_suat * count($ghe_array) / 1000);
if ($earned_points > 0) {
    mysqli_query($conn, "UPDATE users SET points = points + $earned_points WHERE id = $user_id");
}

// ── Đánh dấu thẻ quà tặng đã dùng ─────────────────────────────────
$giftcard_code = trim($_POST["giftcard_code"] ?? "");
if ($giftcard_code !== "") {
    if (table_exists($conn, "gift_cards")) {
        $gcStmt = mysqli_prepare($conn, "UPDATE gift_cards SET used=1, used_by=?, used_at=NOW() WHERE code=? AND used=0");
        mysqli_stmt_bind_param($gcStmt, "is", $user_id, $giftcard_code);
        mysqli_stmt_execute($gcStmt);
        mysqli_stmt_close($gcStmt);
    }
}

} catch (Exception $e) {
    mysqli_rollback($conn);
    $msg = $e->getMessage();

    // Lỗi ghế đã bị đặt → redirect về chọn ghế với thông báo rõ ràng
    if (str_starts_with($msg, 'GHE_TAKEN::')) {
        $taken = str_replace('GHE_TAKEN::', '', $msg);
        $back  = "chon_ghe.php?suat_id={$suat_chieu_id}&taken=" . urlencode($taken);
        header("Location: $back");
        exit;
    }
    die("Đã xảy ra lỗi: " . htmlspecialchars($msg));
}

// --- Fetch enriched show information for the e-ticket ---
$info = null;
$sql = "SELECT p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia, r.ten_rap, pc.ten_phong
        FROM suat_chieu sc
        JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        WHERE sc.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$result_info = mysqli_stmt_get_result($stmt);
if ($result_info) $info = mysqli_fetch_assoc($result_info);
mysqli_stmt_close($stmt);

// --- Prepare data for the view ---
$seat_list = htmlspecialchars(implode(", ", $ghe_array));
$seat_count = count($ghe_array);
$price_per_seat = (isset($info['gia']) && $info['gia'] !== null) ? (int)$info['gia'] : 0;
$ticket_total = $seat_count * $price_per_seat;

// --- Combo total ---
$combo_total = 0;
$combo_validated = [];
if (table_exists($conn, 'combos') && !empty($combo_items)) {
    $ids = [];
    $qty_map = [];
    foreach ($combo_items as $it) {
        $cid = (int)($it['id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        if ($cid > 0 && $qty > 0) {
            $ids[] = $cid;
            $qty_map[$cid] = $qty;
        }
    }
    if ($ids) {
        $id_list = implode(',', array_unique($ids));
        $q = mysqli_query($conn, "SELECT id, ten, gia FROM combos WHERE active=1 AND id IN ($id_list)");
        while ($r = mysqli_fetch_assoc($q)) {
            $cid = (int)$r['id'];
            $qty = $qty_map[$cid] ?? 0;
            if ($qty > 0) {
                $gia = (int)$r['gia'];
                $combo_total += $gia * $qty;
                $combo_validated[] = ['id'=>$cid,'ten'=>$r['ten'],'qty'=>$qty,'gia'=>$gia];
            }
        }
    }
}

$sub_total = $ticket_total + $combo_total;

// --- Voucher validate ---
$voucher_discount = 0;
$voucher_id = null;
if ($voucher_code !== '' && table_exists($conn, 'vouchers') && table_exists($conn, 'voucher_usages')) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM vouchers WHERE code = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $voucher_code);
    mysqli_stmt_execute($stmt);
    $voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($voucher && (int)$voucher['active'] === 1) {
        $today = date('Y-m-d');
        $ok = true;
        if (!empty($voucher['start_date']) && $today < $voucher['start_date']) $ok = false;
        if (!empty($voucher['end_date']) && $today > $voucher['end_date']) $ok = false;
        if ($sub_total < (int)$voucher['min_total']) $ok = false;
        if (!is_null($voucher['usage_limit']) && (int)$voucher['used_count'] >= (int)$voucher['usage_limit']) $ok = false;

        if ($ok) {
            $uid = (int)$user_id;
            $chk = mysqli_prepare($conn, "SELECT id FROM voucher_usages WHERE voucher_id=? AND user_id=? LIMIT 1");
            mysqli_stmt_bind_param($chk, "ii", $voucher['id'], $uid);
            mysqli_stmt_execute($chk);
            $used = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
            mysqli_stmt_close($chk);
            if (!$used) {
                if ($voucher['discount_type'] === 'percent') {
                    $voucher_discount = (int)round($sub_total * ((int)$voucher['discount_value']) / 100);
                } else {
                    $voucher_discount = (int)$voucher['discount_value'];
                }
                if (!empty($voucher['max_discount']) && $voucher_discount > (int)$voucher['max_discount']) {
                    $voucher_discount = (int)$voucher['max_discount'];
                }
                if ($voucher_discount > $sub_total) $voucher_discount = $sub_total;
                if ($voucher_discount > 0) $voucher_id = (int)$voucher['id'];
            }
        }
    }
}

$total_amount = max(0, $sub_total - $voucher_discount);

// --- Save voucher usage ---
if ($voucher_id && $voucher_discount > 0 && table_exists($conn, 'voucher_usages')) {
    $uid = (int)$user_id;
    $stmt = mysqli_prepare($conn,
        "INSERT IGNORE INTO voucher_usages (voucher_id, user_id) VALUES (?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "ii", $voucher_id, $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);


    if (table_exists($conn, 'vouchers')) {
        mysqli_query($conn, "UPDATE vouchers SET used_count = used_count + 1 WHERE id = " . (int)$voucher_id);
    }
}

// --- Save combo orders ---
if (!empty($combo_validated) && table_exists($conn, 'combo_orders')) {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO combo_orders (user_id, suat_chieu_id, combo_id, so_luong)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($combo_validated as $cb) {
        $cid = (int)$cb['id'];
        $qty = (int)$cb['qty'];
        mysqli_stmt_bind_param($stmt, "iiii", $user_id, $suat_chieu_id, $cid, $qty);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

// --- Notification ---
if ($info && table_exists($conn, 'notifications')) {
    $title = "Đặt vé thành công";
    $body  = "Phim " . ($info['ten_phim'] ?? '') . " — " .
             date('d/m/Y', strtotime($info['ngay'])) . " " . date('H:i', strtotime($info['gio'])) .
             " | " . $seat_count . " ghế";
    $link  = "ve_cua_toi.php";
    $stmt  = mysqli_prepare($conn, "INSERT INTO notifications (user_id, title, body, link) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $body, $link);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$movie_name = $info['ten_phim'] ?? 'N/A';
$poster_path = (!empty($info['poster'])) ? "../assets/images/" . htmlspecialchars($info['poster']) : "https://via.placeholder.com/560x315.png?text=Movie+Poster";
$rap_name = $info['ten_rap'] ?? 'N/A';
$phong_name = $info['ten_phong'] ?? 'N/A';
$show_date = $info ? date('d/m/Y', strtotime($info['ngay'])) : 'N/A';
$show_time = $info ? date('H:i', strtotime($info['gio'])) : 'N/A';
$ticket_id_display = 'TTVH-' . str_pad($last_ve_id ?? 0, 8, '0', STR_PAD_LEFT);

// QR Code & Barcode data
$qr_data = "TicketID: $ticket_id_display | Movie: $movie_name | Seats: $seat_list | Total: $total_amount";
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode($qr_data);

// ── Map HEAD variables to HTML structure variables ───────────────────
$combo_display = [];
if (!empty($combo_validated)) {
    foreach ($combo_validated as $cb) {
        $combo_display[] = ['ten' => $cb['ten'], 'qty' => $cb['qty'], 'gia' => $cb['gia']];
    }
}
$tong_combo = $combo_total ?? 0;
$so_ghe = $seat_count ?? 0;
$gia_ve = $price_per_seat ?? 0;
$tong_ve = $ticket_total ?? 0;
$tong_thanh_toan = $total_amount ?? 0;
$ngay_chieu = $show_date ?? '';
$gio_chieu = $show_time ?? '';
$ma_don = $ticket_id_display ?? '';

if (!function_exists('fmt_m')) {
    function fmt_m($n){ return number_format($n, 0, ',', '.') . '₫'; }
}
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
.price-table {
  width: 100%;
  border-collapse: collapse;
  background: transparent !important;
}
.price-table tr td {
  padding: 8px 0 !important;
  font-size: 14px !important;
  color: var(--muted) !important;
  background: transparent !important;
  border: none !important;
  text-align: left !important;
}
.price-table tr td:last-child {
  text-align: right !important;
  font-weight: 600;
  color: var(--text) !important;
}
.price-table .total-row td {
  border-top: 1px dashed var(--border) !important;
  padding-top: 16px !important;
  margin-top: 8px !important;
  font-size: 16px !important;
  font-weight: 700;
  color: var(--text) !important;
}
.price-table .total-row td:last-child {
  font-size: 22px !important;
  color: var(--gold) !important;
  font-family: 'Bebas Neue', sans-serif;
  letter-spacing: 1px;
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
<?php $active_page = ''; include 'components/header.php'; ?>
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
        <img class="qr-code" style="width:100px; height: 100px; margin-top:20px" src="<?= $qr_code_url ?>" alt="Ticket QR Code">
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
        <?php if (isset($voucher_discount) && $voucher_discount > 0): ?>
        <tr>
          <td>Voucher (<?= htmlspecialchars($voucher_code) ?>)</td>
          <td style="color:var(--accent-red) !important">-<?= fmt_m($voucher_discount) ?></td>
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
