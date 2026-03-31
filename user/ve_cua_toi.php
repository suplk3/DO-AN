<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$thong_bao = '';

// Hủy vé của chính mình
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huy_ve'])) {
    $ve_id = (int)$_POST['ve_id'];
    $sql_huy = "DELETE FROM ve WHERE id = ? AND user_id = ?";
    $stmt_huy = mysqli_prepare($conn, $sql_huy);
    mysqli_stmt_bind_param($stmt_huy, 'ii', $ve_id, $user_id);
    $thong_bao = mysqli_stmt_execute($stmt_huy)
        ? "✅ Hủy vé thành công"
        : "❌ Hủy vé thất bại";
}

// Lấy vé của user hiện tại
$sql = "SELECT v.id AS ve_id, p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia,
               r.ten_rap, pc.ten_phong, g.ten_ghe
        FROM ve v
        LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
        LEFT JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        LEFT JOIN ghe g ON v.ghe_id = g.id
        WHERE v.user_id = ?
        ORDER BY sc.ngay DESC, sc.gio DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ves = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vé của tôi — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<link rel="stylesheet" href="../assets/css/search.css">
<link rel="stylesheet" href="../assets/css/user-menu.css">
<link rel="stylesheet" href="../assets/css/theme-toggle.css">
<style>
/* ── Ticket cards ── */
.ticket-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 8px;
}
.ticket-card {
  background: linear-gradient(135deg, #111827, #0d1322);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .22s, box-shadow .22s, border-color .22s;
  opacity: 0;
  transform: translateY(28px);
}
.ticket-card.is-visible {
  animation: cardFadeUp .55s cubic-bezier(0.22,1,0.36,1) forwards;
}
@keyframes cardFadeUp {
  from { opacity:0; transform:translateY(28px); }
  to   { opacity:1; transform:translateY(0); }
}
.ticket-card:hover {
  transform: translateY(-4px);
  border-color: rgba(232,25,44,.25);
  box-shadow: 0 16px 40px rgba(0,0,0,.5);
}
.ticket-header {
  display: flex; gap: 14px; padding: 16px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.ticket-poster {
  width: 60px; height: 84px;
  object-fit: cover; border-radius: 8px; flex-shrink: 0;
}
.ticket-title {
  font-size: 15px; font-weight: 800; color: #f1f5f9;
  margin-bottom: 6px; line-height: 1.3;
}
.ticket-meta { display: flex; flex-direction: column; gap: 4px; }
.ticket-meta span { font-size: 12px; color: #64748b; }
.ticket-meta strong { color: #94a3b8; }
.ticket-body { padding: 14px 16px; flex: 1; }
.ticket-detail-row {
  display: flex; justify-content: space-between;
  align-items: center; padding: 6px 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  font-size: 13px;
}
.ticket-detail-row:last-child { border-bottom: none; }
.ticket-detail-row .label { color: #64748b; font-weight: 600; }
.ticket-detail-row .val { color: #f1f5f9; font-weight: 700; text-align: right; }
.ticket-seat-badge {
  display: inline-flex; align-items: center; justify-content: center;
  width: 36px; height: 36px;
  background: linear-gradient(135deg,#e8192c,#c01020);
  border-radius: 8px;
  font-size: 14px; font-weight: 900; color: #fff;
  box-shadow: 0 4px 12px rgba(232,25,44,.35);
}
.ticket-price { font-size: 16px; font-weight: 800; color: #f5c518; }
.ticket-actions {
  display: flex; gap: 8px; padding: 12px 16px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.ticket-actions .btn-act {
  flex: 1; padding: 9px 8px; border-radius: 9px; border: none;
  font-family: 'Be Vietnam Pro', sans-serif;
  font-size: 11px; font-weight: 700; cursor: pointer;
  text-decoration: none; text-align: center;
  transition: all .2s; display: flex; align-items: center;
  justify-content: center; gap: 5px;
}
.btn-print { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: rgba(241,245,249,.8); }
.btn-print:hover { background: rgba(255,255,255,.1); color: #fff; }
.btn-save { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25); color: #86efac; }
.btn-save:hover { background: rgba(34,197,94,.2); }
.btn-cancel { background: rgba(232,25,44,.1); border: 1px solid rgba(232,25,44,.2); color: #fca5a5; }
.btn-cancel:hover { background: rgba(232,25,44,.2); }

/* --- Light Theme Overrides --- */
body[data-theme="light"] .ticket-card {
  background: #ffffff !important;
  border-color: #e2e8f0 !important;
  box-shadow: 0 10px 25px rgba(0,0,0,.04);
}
body[data-theme="light"] .ticket-card:hover { border-color: #cbd5e1 !important; box-shadow: 0 16px 40px rgba(0,0,0,.08); }
body[data-theme="light"] .ticket-header,
body[data-theme="light"] .ticket-detail-row,
body[data-theme="light"] .ticket-actions { border-color: #f1f5f9 !important; }
body[data-theme="light"] .ticket-title { color: #0f172a !important; }
body[data-theme="light"] .ticket-meta span, 
body[data-theme="light"] .ticket-detail-row .label { color: #64748b !important; }
body[data-theme="light"] .ticket-meta strong,
body[data-theme="light"] .ticket-detail-row .val { color: #1e293b !important; }
body[data-theme="light"] .ticket-price { color: #e8192c !important; }
body[data-theme="light"] .btn-print { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
body[data-theme="light"] .btn-print:hover { background: #f1f5f9; color: #0f172a; }
body[data-theme="light"] .btn-save { background: rgba(34,197,94,.1); border-color: rgba(34,197,94,.2); color: #15803d; }
body[data-theme="light"] .btn-save:hover { background: rgba(34,197,94,.15); }
body[data-theme="light"] .btn-cancel { background: rgba(232,25,44,.08); border-color: rgba(232,25,44,.15); color: #b91c1c; }
body[data-theme="light"] .btn-cancel:hover { background: rgba(232,25,44,.12); }

/* Toast message */
.toast-msg {
  padding: 12px 16px; border-radius: 12px; margin-bottom: 20px;
  font-size: 13px; font-weight: 600; border: 1px solid;
  animation: toastIn .3s cubic-bezier(0.22,1,0.36,1);
}
@keyframes toastIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
.toast-msg.ok  { background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.25);color:#86efac; }
.toast-msg.err { background:rgba(232,25,44,.1);border-color:rgba(232,25,44,.25);color:#fca5a5; }

/* Empty state */
.empty-state { text-align:center; padding: 80px 20px; }
.empty-icon { font-size: 56px; margin-bottom: 16px; opacity: .5; }
.empty-title { font-size: 20px; font-weight: 800; color: #e2e8f0; margin-bottom: 8px; }
.empty-sub { font-size: 14px; color: #64748b; margin-bottom: 24px; }

/* Modal */
.print-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.7);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 20px;
  backdrop-filter: blur(8px);
  opacity: 0; pointer-events: none; transition: opacity .25s;
}
.print-overlay.open { opacity: 1; pointer-events: auto; }
.print-box {
  background: #fff; color: #000; border-radius: 16px;
  max-width: 840px; width: 100%; position: relative;
  overflow: auto; max-height: 90vh;
  transform: scale(.94); transition: transform .25s;
}
.print-overlay.open .print-box { transform: scale(1); }
.print-close {
  position: absolute; top: 12px; right: 12px;
  width: 30px; height: 30px; border-radius: 50%;
  background: #eee; border: none; font-size: 18px; cursor: pointer;
}
.ticket-printable {
  width: 800px; min-height: 380px; display: flex; overflow: hidden;
}
.ticket-stub { padding: 24px; width: 280px; display: flex; flex-direction: column; justify-content: space-between; border-right: 2px dashed #ddd; }
.ticket-main { padding: 24px; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
.ticket-main .t-poster { width: 100%; max-width: 220px; border-radius: 8px; }
.ticket-stub h2 { font-size: 48px; margin: 6px 0; font-weight: 900; color: #000; }
.ticket-stub p { margin: 4px 0; font-size: 14px; color: #333; }
.ticket-main h1 { font-size: 22px; font-weight: 800; margin: 12px 0 0; color: #000; }
.modal-footer { padding: 14px 20px; text-align: right; display: flex; gap: 8px; justify-content: flex-end; }
.btn-do-print { padding: 10px 20px; background: #e8192c; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-do-close { padding: 10px 20px; background: #eee; color: #333; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }

@media (max-width:640px) {
  .ticket-grid { grid-template-columns: 1fr; }
  .ticket-printable { width: 100%; flex-direction: column; }
  .ticket-stub { width: 100%; border-right: none; border-bottom: 2px dashed #ddd; }
}
@media print {
  body > *:not(#printArea) { display: none !important; }
  #printArea { display: block !important; }
}
</style>
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="user-index">

<?php $active_page = 've_cua_toi'; include 'components/header.php'; ?>
<main class="container">
  <h1 class="page-title">🎫 Vé của tôi</h1>

  <?php if (!empty($thong_bao)):
    $ok = str_starts_with($thong_bao, '✅'); ?>
    <div class="toast-msg <?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($thong_bao) ?></div>
  <?php endif; ?>

  <?php if (empty($ves)): ?>
    <div class="empty-state">
      <div class="empty-icon">🎟️</div>
      <div class="empty-title">Bạn chưa có vé nào</div>
      <div class="empty-sub">Hãy chọn phim và đặt vé để xem lịch sử tại đây.</div>
      <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#e8192c,#c01020);color:#fff;text-decoration:none;border-radius:12px;font-weight:800;font-size:14px;box-shadow:0 6px 20px rgba(232,25,44,.35);">🎬 Đặt vé ngay</a>
    </div>
  <?php else: ?>
    <div class="ticket-grid">
      <?php foreach ($ves as $ve): ?>
      <div class="ticket-card">
        <div class="ticket-header">
          <img src="../assets/images/<?= htmlspecialchars($ve['poster']) ?>"
               class="ticket-poster" alt="<?= htmlspecialchars($ve['ten_phim']) ?>">
          <div style="flex:1;min-width:0;">
            <div class="ticket-title"><?= htmlspecialchars($ve['ten_phim']) ?></div>
            <div class="ticket-meta">
              <span>🏢 <strong><?= htmlspecialchars($ve['ten_rap']) ?></strong></span>
              <span>🚪 <?= htmlspecialchars($ve['ten_phong']) ?></span>
            </div>
          </div>
        </div>
        <div class="ticket-body">
          <div class="ticket-detail-row">
            <span class="label">📅 Ngày chiếu</span>
            <span class="val"><?= date('d/m/Y', strtotime($ve['ngay'])) ?></span>
          </div>
          <div class="ticket-detail-row">
            <span class="label">⏰ Giờ chiếu</span>
            <span class="val"><?= substr($ve['gio'],0,5) ?></span>
          </div>
          <div class="ticket-detail-row">
            <span class="label">🪑 Ghế</span>
            <span class="ticket-seat-badge"><?= htmlspecialchars($ve['ten_ghe']) ?></span>
          </div>
          <div class="ticket-detail-row">
            <span class="label">💰 Giá vé</span>
            <span class="ticket-price"><?= number_format($ve['gia'], 0, ',', '.') ?>₫</span>
          </div>
        </div>
        <div class="ticket-actions">
          <button class="btn-act btn-print" onclick="showPrintModal(<?= (int)$ve['ve_id'] ?>)">🖨️ In vé</button>
          <a class="btn-act btn-save" href="save_ticket.php?ve_id=<?= (int)$ve['ve_id'] ?>">💾 Lưu</a>
          <form method="POST" style="flex:1;margin:0;">
            <input type="hidden" name="ve_id" value="<?= (int)$ve['ve_id'] ?>">
            <button type="submit" name="huy_ve" class="btn-act btn-cancel" style="width:100%;"
                    onclick="return confirm('Bạn có chắc muốn hủy vé này?')">❌ Hủy</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas</div></footer>

<!-- Print Modal -->
<div id="printOverlay" class="print-overlay" onclick="if(event.target===this)closePrintModal()">
  <div class="print-box">
    <div id="printArea"></div>
  </div>
</div>

<script>
/* Stagger animation */
const io2 = new IntersectionObserver(entries => {
  entries.forEach((e,i) => {
    if(!e.isIntersecting) return;
    setTimeout(()=>e.target.classList.add('is-visible'), i*80);
    io2.unobserve(e.target);
  });
},{threshold:0,rootMargin:'0px 0px 200px 0px'});
document.querySelectorAll('.ticket-card').forEach((c,i)=>{c.dataset.i=i;io2.observe(c);});

/* Header shrink */
const hdr=document.querySelector('.header'),bod=document.querySelector('body.user-index');
if(hdr&&bod){const fn=()=>{hdr.classList.toggle('shrink',scrollY>50);bod.classList.toggle('header-shrink',scrollY>50);};window.addEventListener('scroll',fn,{passive:true});fn();}

/* Print modal */
async function showPrintModal(ve_id) {
  const overlay = document.getElementById('printOverlay');
  const area    = document.getElementById('printArea');
  area.innerHTML = '<div style="padding:40px;text-align:center;color:#666;">Đang tải...</div>';
  overlay.classList.add('open');
  try {
    const r = await fetch(`get_ticket_details.php?ve_id=${ve_id}`);
    if (!r.ok) throw new Error('Lỗi tải vé');
    const t = await r.json();
    area.innerHTML = `
      <button class="print-close" onclick="closePrintModal()">×</button>
      <div class="ticket-printable">
        <div class="ticket-stub">
          <div>
            <p><strong>RẠP:</strong> ${t.ten_rap}</p>
            <p><strong>PHÒNG:</strong> ${t.ten_phong}</p>
            <p><strong>NGÀY:</strong> ${t.ngay_f}</p>
            <p><strong>GIỜ:</strong> ${t.gio_f}</p>
            <p><strong>GHẾ:</strong></p>
            <h2>${t.ten_ghe}</h2>
          </div>
          <img src="${t.qr_code_url}" style="width:160px;height:160px;" alt="QR">
        </div>
        <div class="ticket-main">
          <img src="${t.poster_url}" class="t-poster" alt="">
          <h1>${t.ten_phim}</h1>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-do-print" onclick="window.print()">🖨️ In vé</button>
        <button class="btn-do-close" onclick="closePrintModal()">Đóng</button>
      </div>`;
  } catch(e) {
    area.innerHTML = `<div style="padding:24px;color:red;">${e.message}</div>`;
  }
}
function closePrintModal(){document.getElementById('printOverlay').classList.remove('open');}
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// User dropdown toggle
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', e => {
        e.stopPropagation();
        userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown.classList.remove('open'));
}
</script>
</body>
</html>