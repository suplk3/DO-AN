<?php
include "../config/db.php";
session_start();

// data from previous step (chon_ghe)
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    die("Dữ liệu không hợp lệ. Vui lòng chọn ghế trước khi thanh toán.");
}

$suat_chieu_id = (int)$_POST['suat_chieu_id'];
$ghe_list = array_filter(explode(",", $_POST['ghe']));

if (empty($ghe_list)) {
    die("Chưa có ghế nào được chọn.");
}

// fetch show and movie info
$sql = "SELECT p.ten_phim, p.poster, p.mo_ta, sc.ngay, sc.gio, sc.gia,
        r.ten_rap, pc.ten_phong
        FROM suat_chieu sc
        JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        WHERE sc.id = $suat_chieu_id";
$res = mysqli_query($conn, $sql);
$info = mysqli_fetch_assoc($res);

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }

$seat_list = htmlspecialchars(implode(", ", $ghe_list));
$seat_count = count($ghe_list);
$Price = isset($info['gia']) ? (int)$info['gia'] : 0;
$total_amount = $Price * $seat_count;

// combos (if table exists)
$combos = [];
if (table_exists($conn, 'combos')) {
    $combo_rs = mysqli_query($conn, "SELECT * FROM combos WHERE active=1 ORDER BY id ASC");
    if ($combo_rs) {
        while ($c = mysqli_fetch_assoc($combo_rs)) $combos[] = $c;
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thanh toán vé - <?= htmlspecialchars($info['ten_phim'] ?? '') ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/theme-toggle.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap');

:root {
    --red: #e8192c;
    --red-glow: rgba(232,25,44,0.35);
    --red-dark: #a8121e;
    --red-bright: #ff3347;
    --black: #050508;
    --surface-0: #0a0a10;
    --surface-1: #0f0f18;
    --surface-2: #14141f;
    --surface-3: #1a1a28;
    --border: rgba(255,255,255,0.07);
    --border-red: rgba(232,25,44,0.3);
    --text: #f0f0f5;
    --text-muted: #6b7280;
    --text-dim: #9ca3af;
}

* { box-sizing: border-box; }

body {
    background: var(--black);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
    background-image:
        radial-gradient(ellipse 80% 40% at 20% -10%, rgba(232,25,44,0.12) 0%, transparent 60%),
        radial-gradient(ellipse 60% 30% at 80% 100%, rgba(232,25,44,0.06) 0%, transparent 60%);
}

body::after {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.015'%3E%3Cpath d='M0 0h40v1H0zm0 20h40v1H0zM0 0v40h1V0zm20 0v40h1V0z'/%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.header {
    background: rgba(5,5,8,0.92) !important;
    backdrop-filter: blur(24px) !important;
    border-bottom: 1px solid var(--border-red) !important;
    box-shadow: 0 1px 0 rgba(232,25,44,0.15) !important;
}
.logo { color: var(--red) !important; font-family: 'Orbitron', sans-serif !important; letter-spacing: 3px !important; }
.nav-link, .link { color: rgba(255,255,255,0.65) !important; }
.nav-link:hover, .link:hover { color: var(--red-bright) !important; }
.container {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    max-width: 1260px;
    margin: 36px auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}
.left { flex: 1 1 58%; }
.right { flex: 1 1 36%; }

/* ── Panels ── */
.panel {
    background: linear-gradient(135deg, var(--surface-2), var(--surface-1));
    border: 1px solid var(--border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,0.6);
    position: relative;
}
.panel::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--red), transparent);
}
.left .panel { padding: 28px; }

/* ── Movie Summary ── */
.movie-summary {
    background: linear-gradient(135deg, rgba(232,25,44,0.08), rgba(14,14,24,0.95));
    border: 1px solid var(--border-red);
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 20px;
    overflow: hidden;
    display: flex;
    gap: 18px;
    align-items: flex-start;
    position: relative;
    box-shadow: 0 4px 30px rgba(232,25,44,0.1);
}
.movie-summary::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, var(--red), var(--red-bright), var(--red));
}
.movie-summary h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 17px; color: #fff; letter-spacing: 1px; margin-bottom: 8px;
}
.left-poster {
    width: 90px; min-width: 90px; border-radius: 12px; float: none; margin: 0;
    border: 2px solid var(--border-red);
    box-shadow: 0 6px 20px rgba(232,25,44,0.25);
}
.left-desc { font-size: 13px; color: var(--text-dim); line-height: 1.7; margin-top: 8px; }

/* ── Section Title ── */
.section-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase;
    color: var(--red); margin-bottom: 18px; padding-bottom: 12px;
    border-bottom: 1px solid var(--border-red);
    display: flex; align-items: center; gap: 10px;
}
.section-title::before {
    content: ''; width: 4px; height: 14px;
    background: var(--red); border-radius: 2px; box-shadow: 0 0 8px var(--red);
}
.section { margin-bottom: 32px; }

/* ── Inputs ── */
.input-group { margin-bottom: 14px; }
.input-group label {
    display: block; font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: var(--text-muted); margin-bottom: 7px;
}
.input-group input[type="text"],
.input-group select {
    width: 100%; padding: 12px 16px; border: 1px solid var(--border);
    border-radius: 10px; background: var(--surface-3); color: var(--text);
    font-family: 'Inter', sans-serif; font-size: 14px; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.input-group input[type="text"]:focus,
.input-group select:focus {
    border-color: var(--red); box-shadow: 0 0 0 3px rgba(232,25,44,0.15);
    background: rgba(232,25,44,0.04);
}
.input-group input::placeholder { color: var(--text-muted); }

/* ── Voucher ── */
.voucher-row { display: flex; gap: 10px; }
.btn-apply {
    background: linear-gradient(135deg, var(--red), var(--red-dark));
    color: #fff; border: none; padding: 12px 18px;
    border-radius: 10px; font-family: 'Orbitron', sans-serif;
    font-size: 11px; font-weight: 700; letter-spacing: 1px;
    cursor: pointer; white-space: nowrap; transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(232,25,44,0.3);
}
.btn-apply:hover { transform: translateY(-2px); box-shadow: 0 6px 22px rgba(232,25,44,0.45); }
.voucher-msg { margin-top: 8px; font-size: 12px; color: #4ade80; }
.voucher-msg.error { color: #f87171; }

/* ── Combo ── */
.combo-list { display: flex; flex-direction: column; gap: 9px; }
.combo-item {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 12px 16px; background: var(--surface-3);
    border: 1px solid var(--border); border-radius: 12px;
    transition: border-color 0.2s, background 0.2s;
}
.combo-item:hover { border-color: var(--border-red); background: rgba(232,25,44,0.04); }
.combo-name { font-weight: 600; font-size: 14px; color: var(--text); }
.combo-desc { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.combo-price { font-weight: 700; font-size: 14px; color: var(--red-bright); letter-spacing: 0.5px; }
.qty-control {
    display: flex; align-items: center; background: var(--surface-1);
    border-radius: 10px; border: 1px solid var(--border); overflow: hidden;
}
.qty-btn {
    background: transparent; color: var(--text-dim); border: none;
    width: 34px; height: 34px; font-size: 18px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: all 0.15s;
}
.qty-btn:hover { background: rgba(232,25,44,0.15); color: var(--red-bright); }
.combo-qty {
    width: 42px; height: 34px; text-align: center;
    background: transparent; border: none; color: #fff; font-size: 15px; font-weight: 700;
}
.combo-qty[type=number]::-webkit-inner-spin-button,
.combo-qty[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

/* ── Payment Methods ── */
.payment-methods {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px;
}
.payment-option {
    border: 1px solid var(--border); border-radius: 14px; padding: 16px 12px;
    background: var(--surface-3); cursor: pointer;
    transition: all 0.25s ease; position: relative; overflow: hidden;
}
.payment-option::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(232,25,44,0.08), transparent);
    opacity: 0; transition: opacity 0.25s;
}
.payment-option:hover {
    border-color: var(--border-red); transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}
.payment-option:hover::after { opacity: 1; }
.payment-option.selected {
    border-color: var(--red); background: rgba(232,25,44,0.08);
    box-shadow: 0 0 18px rgba(232,25,44,0.25), 0 6px 20px rgba(0,0,0,0.3);
}
.payment-option.selected::after { opacity: 1; }
.payment-option input[type="radio"] { display: none; }
.payment-label {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 8px; color: var(--text-dim);
    font-weight: 600; font-size: 12px; text-align: center; position: relative; z-index: 1;
}
.payment-option.selected .payment-label { color: #fff; }
.payment-methods .icon { height: 28px; max-width: 70px; object-fit: contain; }
#qr-payment .qr-container {
    background: #fff; padding: 18px; border-radius: 14px;
    display: inline-block; margin-top: 14px;
    box-shadow: 0 8px 30px rgba(232,25,44,0.2);
}
#qr-payment p { color: var(--text-dim); font-size: 14px; margin-top: 14px; }
#qr-message { color: #111; font-weight: 600; }

/* ── Right panel ── */
.right .panel { padding: 0; }
.summary-poster-wrap {
    position: relative; width: 100%;
    border-radius: 20px 20px 0 0; overflow: hidden; max-height: 280px;
}
.summary-poster-wrap img { width: 100%; height: 280px; object-fit: cover; display: block; }
.summary-poster-wrap::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 120px;
    background: linear-gradient(to top, var(--surface-2), transparent);
}
.summary-movie-title {
    font-family: 'Orbitron', sans-serif; font-size: 14px; font-weight: 700;
    color: #fff; letter-spacing: 1px; padding: 16px 22px 0;
}
.summary-body { padding: 16px 22px 22px; }
.summary-row {
    display: flex; justify-content: space-between;
    padding: 9px 0; font-size: 14px; border-bottom: 1px solid var(--border); color: var(--text-dim);
}
.summary-row span:last-child { color: var(--text); font-weight: 600; }
.summary-row.total {
    border-bottom: none; border-top: 2px solid var(--border-red);
    margin-top: 8px; padding-top: 16px; color: var(--text);
    font-family: 'Orbitron', sans-serif;
}
.summary-row.total span:last-child {
    font-size: 20px; color: var(--red-bright);
    text-shadow: 0 0 12px var(--red-glow);
}
h2, h3 { color: #fff; }
.right div { color: var(--text); }

/* ── Countdown ── */
.countdown {
    text-align: center; margin: 18px 22px 0; padding: 12px;
    background: rgba(232,25,44,0.08); border: 1px solid var(--border-red);
    border-radius: 12px; font-family: 'Orbitron', sans-serif;
    font-size: 12px; color: var(--red-bright); letter-spacing: 1px;
}
#time { font-weight: 900; font-size: 18px; letter-spacing: 3px; }

/* ── Agreement ── */
.agreement-group {
    display: flex; align-items: center; gap: 12px; margin: 24px 0 16px;
}
.agreement-group input[type="checkbox"] {
    width: 18px; height: 18px; flex-shrink: 0;
    accent-color: var(--red); cursor: pointer;
}
.agreement-group label {
    font-size: 13px; line-height: 1.6; color: var(--text-dim);
    font-weight: 400; text-transform: none; width: auto; cursor: pointer;
}

/* ── Submit Button ── */
.btn-next {
    background: linear-gradient(135deg, var(--red), var(--red-dark));
    color: #fff; padding: 16px; border: none; border-radius: 12px;
    cursor: pointer; font-family: 'Orbitron', sans-serif;
    font-size: 12px; font-weight: 700; letter-spacing: 2px;
    width: 100%; margin-top: 8px; transition: all 0.25s;
    position: relative; overflow: hidden;
    box-shadow: 0 6px 24px rgba(232,25,44,0.35);
}
.btn-next::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: left 0.5s;
}
.btn-next:hover:not(:disabled)::before { left: 100%; }
.btn-next:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(232,25,44,0.5); }
.btn-next:disabled {
    background: var(--surface-3); color: var(--text-muted);
    cursor: not-allowed; box-shadow: none; border: 1px solid var(--border);
}
/* Override CSS variables for light mode */
body.user-index[data-theme="light"] {
    --black: #f4f5f7; --surface-0: #f9fafb; --surface-1: #f3f4f6;
    --surface-2: #ffffff; --surface-3: #f9fafb;
    --border: rgba(0,0,0,0.08); --border-red: rgba(232,25,44,0.2);
    --text: #111827; --text-muted: #9ca3af; --text-dim: #4b5563;
}
/* ═══ PAYMENT PAGE – LIGHT MODE ═══ */
body.user-index[data-theme="light"] {
    background: #f4f5f7 !important; background-image: none !important;
    color: #111827 !important; font-family: 'Inter', sans-serif !important;
}
body.user-index[data-theme="light"]::after { display: none !important; }
body.user-index[data-theme="light"] .header { background: rgba(255,255,255,0.96) !important; border-bottom: 1px solid rgba(232,25,44,0.2) !important; box-shadow: 0 1px 12px rgba(0,0,0,0.08) !important; }
body.user-index[data-theme="light"] .logo { color: #e8192c !important; }
body.user-index[data-theme="light"] .nav-link, body.user-index[data-theme="light"] .link { color: #374151 !important; }
body.user-index[data-theme="light"] .movie-summary { background: linear-gradient(135deg, rgba(232,25,44,0.06), #ffffff) !important; border: 1px solid rgba(232,25,44,0.25) !important; }
body.user-index[data-theme="light"] .movie-summary h2 { color: #111827 !important; }
body.user-index[data-theme="light"] .movie-summary p, body.user-index[data-theme="light"] .left-desc { color: #4b5563 !important; }
body.user-index[data-theme="light"] .panel { background: #ffffff !important; border: 1px solid #e5e7eb !important; box-shadow: 0 4px 20px rgba(0,0,0,0.06) !important; }
body.user-index[data-theme="light"] .section-title { color: #e8192c !important; border-bottom: 1px solid rgba(232,25,44,0.2) !important; }
body.user-index[data-theme="light"] .input-group label { color: #6b7280 !important; }
body.user-index[data-theme="light"] .input-group input[type="text"], body.user-index[data-theme="light"] .input-group select { background: #f9fafb !important; border: 1px solid #d1d5db !important; color: #111827 !important; }
body.user-index[data-theme="light"] .input-group input[type="text"]:focus, body.user-index[data-theme="light"] .input-group select:focus { border-color: #e8192c !important; box-shadow: 0 0 0 3px rgba(232,25,44,0.1) !important; }
body.user-index[data-theme="light"] .combo-item { background: #f9fafb !important; border: 1px solid #e5e7eb !important; }
body.user-index[data-theme="light"] .combo-name { color: #111827 !important; }
body.user-index[data-theme="light"] .combo-desc { color: #6b7280 !important; }
body.user-index[data-theme="light"] .qty-control { background: #f3f4f6 !important; border: 1px solid #d1d5db !important; }
body.user-index[data-theme="light"] .qty-btn { color: #374151 !important; }
body.user-index[data-theme="light"] .combo-qty { color: #111827 !important; }
body.user-index[data-theme="light"] .payment-option { background: #f9fafb !important; border: 1px solid #e5e7eb !important; }
body.user-index[data-theme="light"] .payment-option.selected { border-color: #e8192c !important; background: rgba(232,25,44,0.05) !important; box-shadow: 0 0 16px rgba(232,25,44,0.15) !important; }
body.user-index[data-theme="light"] .payment-label { color: #4b5563 !important; }
body.user-index[data-theme="light"] .payment-option.selected .payment-label { color: #e8192c !important; }
body.user-index[data-theme="light"] .right .panel { background: #ffffff !important; border: 1px solid #e5e7eb !important; }
body.user-index[data-theme="light"] .summary-movie-title { color: #111827 !important; }
body.user-index[data-theme="light"] .summary-poster-wrap::after { background: linear-gradient(to top, #ffffff, transparent) !important; }
body.user-index[data-theme="light"] .summary-row { color: #4b5563 !important; border-bottom-color: #f3f4f6 !important; }
body.user-index[data-theme="light"] .summary-row span:last-child { color: #111827 !important; }
body.user-index[data-theme="light"] .summary-row.total { border-top: 2px solid rgba(232,25,44,0.3) !important; color: #111827 !important; }
body.user-index[data-theme="light"] .summary-row.total span:last-child { color: #e8192c !important; text-shadow: none !important; }
body.user-index[data-theme="light"] .countdown { background: rgba(232,25,44,0.06) !important; border-color: rgba(232,25,44,0.2) !important; }
body.user-index[data-theme="light"] .agreement-group label { color: #4b5563 !important; }
body.user-index[data-theme="light"] .btn-next:disabled { background: #f3f4f6 !important; color: #9ca3af !important; border: 1px solid #e5e7eb !important; box-shadow: none !important; }
label { width: auto !important; }
</style>
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="user-index">
<?php $active_page = ''; include 'components/header.php'; ?>
<main class="container">
    <div class="left">
        <div class="movie-summary">
            <?php if(!empty($info['poster'])): ?>
                <img class="left-poster" src="../assets/images/<?=htmlspecialchars($info['poster'])?>" alt="<?=htmlspecialchars($info['ten_phim'])?>">
            <?php endif; ?>
            <div style="flex:1">
                <h2><?= htmlspecialchars($info['ten_phim'] ?? '') ?></h2>
                <?php if(!empty($info['mo_ta'])): ?>
                    <p class="left-desc"><?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'],0,250,'...'))) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel">
        <form action="dat_ve.php" method="POST" id="payment-form">
            <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">
            <input type="hidden" name="ghe" value="<?= htmlspecialchars(implode(",", $ghe_list)) ?>">
            <input type="hidden" name="voucher_code" id="voucher_code">
            <input type="hidden" name="voucher_discount" id="voucher_discount" value="0">
            <input type="hidden" name="combo_items" id="combo_items" value="[]">
            <input type="hidden" name="points_discount" id="points_discount" value="0">
            <input type="hidden" name="points_used" id="points_used" value="0">
            <input type="hidden" name="giftcard_code" id="giftcard_code" value="">
            <input type="hidden" name="giftcard_discount" id="giftcard_discount" value="0">

            <div class="section">
                <div class="section-title">Mã giảm giá & Điểm thưởng</div>
                <div class="input-group voucher-row">
                    <input type="text" id="voucher" name="voucher_input" placeholder="Nhập mã voucher...">
                    <button type="button" class="btn-apply" id="btnApplyVoucher">ÁP DỤNG</button>
                </div>
                <div class="voucher-msg" id="voucherMessage"></div>
                <!-- Điểm TTVH -->
                <div style="margin-top:12px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <span style="font-size:12px;color:var(--text-dim);font-weight:600">⭐ Điểm TTVH</span>
                        <span class="pts-badge" id="pts-badge" style="font-size:11px;background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:2px 8px;border-radius:12px"></span>
                        <span style="font-size:11px;color:var(--text-muted);cursor:help" title="Cách nhận điểm TTVH:&#10;• Mua vé: 1.000đ = 1 điểm&#10;• 100 điểm = giảm 10.000đ&#10;• Điểm cộng dồn tự động sau mỗi lần mua">❓</span>
                    </div>
                    <div class="input-group voucher-row">
                        <input type="number" id="ttvh_points_input" placeholder="Nhập số điểm muốn dùng..." min="1">
                        <button type="button" class="btn-apply" id="btnApplyPoints">DÙNG ĐIỂM</button>
                    </div>
                    <div class="voucher-msg" id="pointsMessage"></div>
                </div>
            </div>

            <div class="section">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
                    <div class="section-title" style="margin-bottom:0">Thẻ quà tặng</div>
                    <span style="font-size:11px;color:var(--text-muted);cursor:help" title="Cách có thẻ quà tặng:&#10;• Admin tặng nhân dịp sinh nhật, sự kiện&#10;• Mua thẻ tại quầy rạp&#10;• Nhận qua chương trình khuyến mãi&#10;Mỗi thẻ chỉ dùng được 1 lần">❓</span>
                </div>
                <div class="input-group voucher-row">
                    <input type="text" id="gift_card_input" placeholder="Nhập mã thẻ quà tặng..." style="text-transform:uppercase">
                    <button type="button" class="btn-apply" id="btnApplyGiftCard">ÁP DỤNG</button>
                </div>
                <div class="voucher-msg" id="giftCardMessage"></div>
            </div>

            <div class="section">
                <div class="section-title">Combo &amp; Bắp Nước</div>
                <div class="combo-list">
                    <?php if (empty($combos)): ?>
                        <div style="color:var(--text-muted);font-size:13px;">Hiện chưa có combo.</div>
                    <?php else: foreach ($combos as $cb): ?>
                        <div class="combo-item" data-combo-id="<?= (int)$cb['id'] ?>" data-combo-price="<?= (int)$cb['gia'] ?>">
                            <div>
                                <div class="combo-name"><?= htmlspecialchars($cb['ten']) ?></div>
                                <?php if (!empty($cb['mo_ta'])): ?>
                                    <div class="combo-desc"><?= htmlspecialchars($cb['mo_ta']) ?></div>
                                <?php endif; ?>
                                <div class="combo-price"><?= number_format((int)$cb['gia'], 0, ',', '.') ?>₫</div>
                            </div>
                            <div class="qty-control">
                                <button type="button" class="qty-btn minus-btn">−</button>
                                <input type="number" class="combo-qty" min="0" value="0" readonly>
                                <button type="button" class="qty-btn plus-btn">+</button>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Hình thức thanh toán</div>
                <div class="payment-methods">
                    <div class="payment-option">
                        <input type="radio" id="pay-card" name="payment_method" value="card">
                        <label for="pay-card" class="payment-label">
                            <img src="https://img.icons8.com/color/96/000000/atm.png" alt="ATM" class="icon">
                            ATM / Banking
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-visa" name="payment_method" value="visa">
                        <label for="pay-visa" class="payment-label">
                            <img src="https://img.icons8.com/color/96/000000/visa.png" alt="Visa" class="icon">
                            Visa / Mastercard
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-zalopay" name="payment_method" value="zalopay">
                        <label for="pay-zalopay" class="payment-label">
                            <img src="https://img.icons8.com/color/96/zalo.png" alt="ZaloPay" class="icon">
                            ZaloPay
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-vnpay" name="payment_method" value="vnpay">
                        <label for="pay-vnpay" class="payment-label">
                            <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPAY" class="icon">
                            VNPAY
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-momo" name="payment_method" value="momo">
                        <label for="pay-momo" class="payment-label">
                            <img src="https://img.mservice.io/momo-payment/icon/images/logo512.png" alt="MoMo" class="icon">
                            MoMo
                        </label>
                    </div>
                </div>

                <div id="payment-details" style="display:none;margin-top:20px;">
                    <div id="qr-payment" style="display:none;text-align:center;">
                        <p>Quét mã QR để hoàn tất thanh toán</p>
                        <div class="qr-container">
                            <img src="" alt="QR Code" id="qr-code-image" style="max-width:240px;display:block;">
                        </div>
                        <p id="qr-message" style="margin-top:15px;font-weight:500;"></p>
                    </div>
                    <div id="card-payment" style="display:none;">
                        <div class="input-group"><input type="text" id="card_number" name="card_number" placeholder="Số thẻ"></div>
                        <div class="input-group"><input type="text" id="card_name" name="card_name" placeholder="Tên chủ thẻ (không dấu)"></div>
                        <div style="display:flex;gap:10px;">
                            <div class="input-group" style="flex:1;"><input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY"></div>
                            <div class="input-group" style="flex:1;"><input type="text" id="card_cvv" name="card_cvv" placeholder="CVV"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="agreement-group">
                <input type="checkbox" id="agree" name="agree">
                <label for="agree">Tôi đồng ý với điều khoản sử dụng và xác nhận mua vé cho người có độ tuổi phù hợp.</label>
            </div>

            <div id="validation-message" style="display:none;">Vui lòng đồng ý điều khoản và chọn hình thức thanh toán.</div>
            <button type="submit" id="btn-submit" class="btn-next" disabled>⚡ HOÀN TẤT THANH TOÁN</button>
        </form>
        </div><!-- /.panel -->
    </div>

    <div class="right">
        <div class="panel">
            <?php if (!empty($info['poster'])): ?>
            <div class="summary-poster-wrap">
                <img src="../assets/images/<?= htmlspecialchars($info['poster']) ?>" alt="<?= htmlspecialchars($info['ten_phim']) ?>">
            </div>
            <?php endif; ?>
            <div class="summary-movie-title"><?= htmlspecialchars($info['ten_phim'] ?? '') ?></div>
            <div class="summary-body">
                <?php if ($info): ?>
                <div class="summary-row"><span>📅 Thời gian</span><span><?= fmt_date($info['ngay']) ?> &middot; <?= fmt_time($info['gio']) ?></span></div>
                <div class="summary-row"><span>🏠 Rạp</span><span><?= htmlspecialchars($info['ten_rap'] ?? '') ?></span></div>
                <div class="summary-row"><span>🎬 Phòng</span><span><?= htmlspecialchars($info['ten_phong'] ?? '') ?></span></div>
                <?php endif; ?>
                <div class="summary-row"><span>💺 Ghế</span><span style="color:var(--red-bright);"><?= $seat_list ?></span></div>
                <div class="summary-row"><span>🎫 Giá vé</span><span><?= fmt_money($Price) ?></span></div>
                <div class="summary-row"><span>🍿 Combo</span><span id="combo-subtotal"><?= fmt_money(0) ?></span></div>
                <div class="summary-row"><span>🏷️ Tạm tính</span><span id="ticket-subtotal"><?= fmt_money($total_amount) ?></span></div>
                <div class="summary-row"><span>🎁 Voucher</span><span id="voucher-discount">—</span></div>
                <div class="summary-row" id="row-points-discount" style="display:none"><span>⭐ Điểm TTVH</span><span id="points-discount-display" style="color:#f59e0b">-</span></div>
                <div class="summary-row" id="row-giftcard-discount" style="display:none"><span>🎁 Thẻ quà tặng</span><span id="giftcard-discount-display" style="color:#22c55e">-</span></div>
                <div class="summary-row total"><span>TỔNG CỘNG</span><span id="final-total"><?= fmt_money($total_amount) ?></span></div>
            </div>
            <div class="countdown" id="countdown">⏳ Giữ vé: <span id="time">10:00</span></div>
            <div style="height:22px;"></div>
        </div>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — All Rights Reserved.</div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const agree = document.getElementById('agree');
    const btn = document.getElementById('btn-submit');
    const paymentOptions = document.querySelectorAll('.payment-option');
    const validationMessage = document.getElementById('validation-message');
    let hasInteracted = false;
    const baseTotal = <?= (int)$total_amount ?>;
    const seatCount = <?= (int)$seat_count ?>;
    const suatId = <?= (int)$suat_chieu_id ?>;

    const voucherInput = document.getElementById('voucher');
    const voucherBtn = document.getElementById('btnApplyVoucher');
    const voucherMsg = document.getElementById('voucherMessage');
    const voucherCodeInput = document.getElementById('voucher_code');
    const voucherDiscountInput = document.getElementById('voucher_discount');
    const comboItemsInput = document.getElementById('combo_items');

    const comboItems = document.querySelectorAll('.combo-item');
    const comboSubtotalEl = document.getElementById('combo-subtotal');
    const ticketSubtotalEl = document.getElementById('ticket-subtotal');
    const voucherDiscountEl = document.getElementById('voucher-discount');
    const finalTotalEl = document.getElementById('final-total');
    let currentDiscount = 0;
    let voucherApplied = false;

    function fmtMoney(n) {
        return n.toLocaleString('vi-VN') + '₫';
    }
    function getComboItems() {
        const items = [];
        comboItems.forEach(item => {
            const id = parseInt(item.dataset.comboId || item.dataset.comboid || 0);
            const qty = parseInt(item.querySelector('.combo-qty').value || 0);
            if (id > 0 && qty > 0) items.push({id, qty});
        });
        return items;
    }
    function getComboTotal() {
        let sum = 0;
        comboItems.forEach(item => {
            const price = parseInt(item.dataset.comboPrice || 0);
            const qty = parseInt(item.querySelector('.combo-qty').value || 0);
            if (qty > 0) sum += price * qty;
        });
        return sum;
    }
    function updateTotals() {
        const comboTotal = getComboTotal();
        const sub = baseTotal + comboTotal;
        const finalTotal = Math.max(0, sub - currentDiscount);

        if (ticketSubtotalEl) ticketSubtotalEl.textContent = fmtMoney(baseTotal);
        if (comboSubtotalEl) comboSubtotalEl.textContent = fmtMoney(comboTotal);
        if (voucherDiscountEl) voucherDiscountEl.textContent = currentDiscount > 0 ? ('-' + fmtMoney(currentDiscount)) : '-';
        if (finalTotalEl) finalTotalEl.textContent = fmtMoney(finalTotal);

        comboItemsInput.value = JSON.stringify(getComboItems());
    }

    async function applyVoucher() {
        const code = (voucherInput.value || '').trim();
        if (!code) {
            voucherApplied = false;
            currentDiscount = 0;
            voucherCodeInput.value = '';
            voucherDiscountInput.value = 0;
            voucherMsg.textContent = '';
            voucherMsg.classList.remove('error');
            updateTotals();
            return;
        }
        const payload = {
            code,
            suat_chieu_id: suatId,
            seat_count: seatCount,
            combo_items: getComboItems()
        };
        const res = await fetch('apply_voucher.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.ok) {
            voucherApplied = true;
            currentDiscount = parseInt(data.discount || 0);
            voucherCodeInput.value = code;
            voucherDiscountInput.value = currentDiscount;
            voucherMsg.textContent = data.message || 'Áp dụng voucher thành công.';
            voucherMsg.classList.remove('error');
        } else {
            voucherApplied = false;
            currentDiscount = 0;
            voucherCodeInput.value = '';
            voucherDiscountInput.value = 0;
            voucherMsg.textContent = data.message || 'Voucher không hợp lệ.';
            voucherMsg.classList.add('error');
        }
        updateTotals();
    }

    function validate() {
        const isAgreed = agree.checked;
        const paymentMethodSelected = document.querySelector('input[name="payment_method"]:checked') !== null;
        const isValid = isAgreed && paymentMethodSelected;
        
        btn.disabled = !isValid;

        if (hasInteracted) {
            validationMessage.style.display = isValid ? 'none' : 'block';
        }
    }

    function handleInteraction() {
        if (!hasInteracted) hasInteracted = true;
        validate();
    }
    
    let seconds = 600;
    const timeEl = document.getElementById('time');
    function updateTimer() {
        const m = Math.floor(seconds/60);
        const s = seconds % 60;
        timeEl.textContent = m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
        if (seconds <= 0) {
            clearInterval(timer);
            alert('Thời gian đặt vé đã hết. Vui lòng thực hiện lại.');
            window.location.href = 'index.php';
        }
        seconds--;
    }
    const timer = setInterval(updateTimer, 1000);
    updateTimer();

    const paymentDetailsContainer = document.getElementById('payment-details');
    const qrPayment = document.getElementById('qr-payment');
    const cardPayment = document.getElementById('card-payment');
    const qrCodeImage = document.getElementById('qr-code-image');
    const qrMessage = document.getElementById('qr-message');

    const qrSources = {
        zalopay: {
            src: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=zalopay-payment-for-total-<?= $total_amount ?>',
            message: 'Mở ứng dụng ZaloPay và quét mã QR để hoàn tất thanh toán.'
        },
        vnpay: {
            src: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=vnpay-payment-for-total-<?= $total_amount ?>',
            message: 'Mở ứng dụng ngân hàng hỗ trợ VNPAY QR và quét mã để hoàn tất thanh toán.'
        },
        momo: {
            src: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=momo-payment-for-total-<?= $total_amount ?>',
            message: 'Mở ứng dụng MoMo và quét mã QR để hoàn tất thanh toán.'
        }
    };

    agree.addEventListener('change', handleInteraction);

    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Unselect all others
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            // Select this one
            this.classList.add('selected');
            // Check the hidden radio button
            this.querySelector('input[type="radio"]').checked = true;

            handleInteraction();

            const selectedMethod = this.querySelector('input[type="radio"]').value;
            
            paymentDetailsContainer.style.display = 'block';
            qrPayment.style.display = 'none';
            cardPayment.style.display = 'none';

            if (qrSources[selectedMethod]) {
                qrPayment.style.display = 'block';
                qrCodeImage.src = qrSources[selectedMethod].src;
                qrMessage.textContent = qrSources[selectedMethod].message;
            } else if (selectedMethod === 'card' || selectedMethod === 'visa') {
                cardPayment.style.display = 'block';
            } else {
                paymentDetailsContainer.style.display = 'none';
            }
        });
    });

    if (voucherBtn) {
        voucherBtn.addEventListener('click', applyVoucher);
    }
    if (voucherInput) {
        voucherInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyVoucher();
            }

    // ── Points TTVH ──────────────────────────────────
    const pointsInput   = document.getElementById('ttvh_points_input');
    const pointsBtn     = document.getElementById('btnApplyPoints');
    const pointsMsg     = document.getElementById('pointsMessage');
    const pointsDiscEl  = document.getElementById('points-discount-display');
    const rowPts        = document.getElementById('row-points-discount');
    const ptsBadge      = document.getElementById('pts-badge');
    const pointsDiscInput  = document.getElementById('points_discount');
    const pointsUsedInput  = document.getElementById('points_used');
    let pointsDiscount  = 0;

    // ── Gift Card ────────────────────────────────────
    const gcInput       = document.getElementById('gift_card_input');
    const gcBtn         = document.getElementById('btnApplyGiftCard');
    const gcMsg         = document.getElementById('giftCardMessage');
    const gcDiscEl      = document.getElementById('giftcard-discount-display');
    const rowGc         = document.getElementById('row-giftcard-discount');
    const gcCodeInput   = document.getElementById('giftcard_code');
    const gcDiscInput   = document.getElementById('giftcard_discount');
    let gcDiscount      = 0;

    // Override updateTotals to include all discounts
    const _origUpdateTotals = updateTotals;
    function updateTotalsAll() {
        const comboTotal = getComboTotal();
        const sub = baseTotal + comboTotal;
        const totalDiscount = currentDiscount + pointsDiscount + gcDiscount;
        const finalTotal = Math.max(0, sub - totalDiscount);
        if (ticketSubtotalEl) ticketSubtotalEl.textContent = fmtMoney(baseTotal);
        if (comboSubtotalEl) comboSubtotalEl.textContent = fmtMoney(comboTotal);
        if (voucherDiscountEl) voucherDiscountEl.textContent = currentDiscount > 0 ? ('-' + fmtMoney(currentDiscount)) : '-';
        if (pointsDiscEl) pointsDiscEl.textContent = pointsDiscount > 0 ? ('-' + fmtMoney(pointsDiscount)) : '-';
        if (gcDiscEl) gcDiscEl.textContent = gcDiscount > 0 ? ('-' + fmtMoney(gcDiscount)) : '-';
        if (finalTotalEl) finalTotalEl.textContent = fmtMoney(finalTotal);
        comboItemsInput.value = JSON.stringify(getComboItems());
    }
    // Monkey-patch: replace updateTotals globally
    window.updateTotals = updateTotalsAll;

    async function applyPoints() {
        const pts = parseInt(pointsInput.value || 0);
        if (!pts || pts <= 0) {
            pointsDiscount = 0; pointsDiscInput.value = 0; pointsUsedInput.value = 0;
            pointsMsg.textContent = ''; rowPts && (rowPts.style.display = 'none');
            updateTotalsAll(); return;
        }
        const payload = { points: pts, suat_chieu_id: suatId, seat_count: seatCount, combo_items: getComboItems() };
        try {
            const res = await fetch('apply_points.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data.ok) {
                pointsDiscount = parseInt(data.discount || 0);
                pointsDiscInput.value = pointsDiscount;
                pointsUsedInput.value = data.points_used || pts;
                pointsMsg.textContent = data.message;
                pointsMsg.className = 'voucher-msg';
                rowPts && (rowPts.style.display = '');
            } else {
                pointsDiscount = 0; pointsDiscInput.value = 0; pointsUsedInput.value = 0;
                pointsMsg.textContent = data.message;
                pointsMsg.className = 'voucher-msg error';
                rowPts && (rowPts.style.display = 'none');
            }
        } catch(e) { pointsMsg.textContent = 'Lỗi kết nối.'; pointsMsg.className = 'voucher-msg error'; }
        updateTotalsAll();
    }

    async function applyGiftCard() {
        const code = (gcInput.value || '').trim().toUpperCase();
        if (!code) {
            gcDiscount = 0; gcCodeInput.value = ''; gcDiscInput.value = 0;
            gcMsg.textContent = ''; rowGc && (rowGc.style.display = 'none');
            updateTotalsAll(); return;
        }
        const payload = { code, suat_chieu_id: suatId, seat_count: seatCount, combo_items: getComboItems() };
        try {
            const res = await fetch('apply_giftcard.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data.ok) {
                gcDiscount = parseInt(data.discount || 0);
                gcCodeInput.value = code;
                gcDiscInput.value = gcDiscount;
                gcMsg.textContent = data.message;
                gcMsg.className = 'voucher-msg';
                rowGc && (rowGc.style.display = '');
            } else {
                gcDiscount = 0; gcCodeInput.value = ''; gcDiscInput.value = 0;
                gcMsg.textContent = data.message;
                gcMsg.className = 'voucher-msg error';
                rowGc && (rowGc.style.display = 'none');
            }
        } catch(e) { gcMsg.textContent = 'Lỗi kết nối.'; gcMsg.className = 'voucher-msg error'; }
        updateTotalsAll();
    }

    // Fetch user points badge
    fetch('apply_points.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({points:0,suat_chieu_id:suatId,seat_count:seatCount}) })
        .then(r => r.json()).then(d => { if (d.points_avail !== undefined && ptsBadge) ptsBadge.textContent = '⭐ ' + d.points_avail + ' điểm'; }).catch(()=>{});

    if (pointsBtn) pointsBtn.addEventListener('click', applyPoints);
    if (pointsInput) pointsInput.addEventListener('keydown', e => { if (e.key==='Enter') { e.preventDefault(); applyPoints(); } });
    if (gcBtn) gcBtn.addEventListener('click', applyGiftCard);
    if (gcInput) gcInput.addEventListener('keydown', e => { if (e.key==='Enter') { e.preventDefault(); applyGiftCard(); } });
        });
    }
    comboItems.forEach(item => {
        const qtyInput = item.querySelector('.combo-qty');
        const minusBtn = item.querySelector('.minus-btn');
        const plusBtn = item.querySelector('.plus-btn');

        if (!qtyInput) return;

        if (minusBtn && plusBtn) {
            minusBtn.addEventListener('click', () => {
                let val = parseInt(qtyInput.value) || 0;
                if (val > 0) {
                    qtyInput.value = val - 1;
                    qtyInput.dispatchEvent(new Event('input'));
                }
            });
            plusBtn.addEventListener('click', () => {
                let val = parseInt(qtyInput.value) || 0;
                qtyInput.value = val + 1;
                qtyInput.dispatchEvent(new Event('input'));
            });
        }

        qtyInput.addEventListener('input', function() {
            updateTotals();
            if (voucherApplied) applyVoucher();
        });
    });

    updateTotals();
    validate(); // Initial check
});
</script>
<script>
// Theme toggle
(function(){
  var body = document.body;
  var btn = document.getElementById('themeToggle');
  var stored = localStorage.getItem('theme') || 'dark';
  body.setAttribute('data-theme', stored);
  if (!btn) return;
  btn.addEventListener('click', function(){
    var cur = body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', cur);
    localStorage.setItem('theme', cur);
  });
})();
</script>

</body>
</html>
