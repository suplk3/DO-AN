<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

// Auto-create tables
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS gift_cards (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        code        VARCHAR(50) UNIQUE NOT NULL,
        balance     INT NOT NULL DEFAULT 0,
        used        TINYINT(1) DEFAULT 0,
        used_by     INT DEFAULT NULL,
        used_at     DATETIME DEFAULT NULL,
        expired_at  DATE DEFAULT NULL,
        note        VARCHAR(255) DEFAULT '',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
if (!column_exists($conn, 'users', 'points')) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN points INT DEFAULT 0 NOT NULL");
}

$msg   = '';
$msgOk = true;
$tab   = $_GET['tab'] ?? 'giftcard';

// ── Gift card actions ─────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_gc') {
    $code   = strtoupper(trim($_POST['code'] ?? ''));
    $bal    = (int)($_POST['balance'] ?? 0);
    $note   = trim($_POST['note'] ?? '');
    $exp    = $_POST['expired_at'] ?: null;
    if (!$code || $bal <= 0) { $msg = 'Vui lòng nhập mã và số tiền hợp lệ.'; $msgOk = false; }
    else {
        $stmt = mysqli_prepare($conn, "INSERT INTO gift_cards (code,balance,note,expired_at) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "siss", $code, $bal, $note, $exp);
        if (mysqli_stmt_execute($stmt)) $msg = "Đã tạo thẻ <strong>$code</strong> – " . number_format($bal,0,',','.') . '₫';
        else { $msg = 'Mã thẻ đã tồn tại.'; $msgOk = false; }
        mysqli_stmt_close($stmt);
    }
    $tab = 'giftcard';
}

if ($action === 'del_gc' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM gift_cards WHERE id=".(int)$_GET['id']);
    header('Location: quan_ly_the.php?tab=giftcard'); exit;
}

// ── Points actions ────────────────────────────────────────────
if ($action === 'set_pts') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $pts = (int)($_POST['points'] ?? 0);
    mysqli_query($conn, "UPDATE users SET points=$pts WHERE id=$uid");
    $msg = 'Đã cập nhật điểm.'; $tab = 'points';
}

if ($action === 'add_pts') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $pts = (int)($_POST['add_points'] ?? 0);
    if ($pts > 0) { mysqli_query($conn, "UPDATE users SET points=points+$pts WHERE id=$uid"); $msg = "Đã thêm $pts điểm."; }
    $tab = 'points';
}

// ── Fetch data ────────────────────────────────────────────────
$gift_cards = [];
$rs = mysqli_query($conn, "SELECT gc.*, u.ten as used_by_name FROM gift_cards gc LEFT JOIN users u ON gc.used_by=u.id ORDER BY gc.id DESC");
while ($r = mysqli_fetch_assoc($rs)) $gift_cards[] = $r;

$users = [];
$rs = mysqli_query($conn, "SELECT id, ten, email, points FROM users ORDER BY id DESC");
while ($r = mysqli_fetch_assoc($rs)) $users[] = $r;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý Thẻ & Điểm – Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root { --accent:#e8192c; --accent-dim:rgba(232,25,44,0.15); --bg:#05060e; --card:#111827;
  --border:rgba(255,255,255,0.07); --border-accent:rgba(232,25,44,0.3); --text:#f0f0f5;
  --muted:#6b7280; --green:#22c55e; --gold:#f59e0b; }
*{box-sizing:border-box;}
body.admin-dark{background:var(--bg);background-image:radial-gradient(ellipse 70% 40% at 10% 0%,rgba(232,25,44,0.1) 0%,transparent 60%);color:var(--text);font-family:"Segoe UI",Inter,sans-serif;min-height:100vh;margin:0;}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px 60px;}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.btn{padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;border:1px solid rgba(59,130,246,0.4);color:#93c5fd;background:rgba(59,130,246,0.1);cursor:pointer;transition:.2s;}
.btn:hover{background:rgba(59,130,246,0.2);}
.btn-red{border-color:var(--border-accent);color:var(--accent);background:var(--accent-dim);}
.btn-gold{border-color:rgba(245,158,11,0.4);color:var(--gold);background:rgba(245,158,11,0.08);}
.btn-green{border-color:rgba(34,197,94,0.4);color:var(--green);background:rgba(34,197,94,0.08);}
.btn-sm{padding:5px 10px;font-size:12px;border-radius:6px;}
h1{font-size:26px;font-weight:800;margin-bottom:20px;color:#fff;}
h2{font-size:16px;font-weight:700;color:#fff;margin-bottom:14px;}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;}
.alert.err{background:var(--accent-dim);border-color:var(--border-accent);color:#fca5a5;}
.tabs{display:flex;gap:8px;margin-bottom:22px;}
.tab-btn{padding:10px 20px;border-radius:10px;border:1px solid var(--border);background:transparent;color:var(--muted);font-size:14px;font-weight:600;cursor:pointer;transition:.2s;}
.tab-btn.active{border-color:var(--accent);color:var(--accent);background:var(--accent-dim);}
.tab-pane{display:none;} .tab-pane.active{display:block;}
.form-card{background:var(--card);border:1px solid var(--border-accent);border-radius:16px;padding:22px;margin-bottom:24px;position:relative;}
.form-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--accent),transparent);border-radius:16px 16px 0 0;}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;}
.field{display:flex;flex-direction:column;gap:5px;}
.field label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);}
.field input,.field select{padding:9px 12px;border-radius:9px;border:1px solid var(--border);background:#1a1a2e;color:var(--text);font-size:13px;outline:none;transition:border-color .2s;}
.field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim);}
.form-actions{display:flex;gap:10px;margin-top:14px;}
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:24px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead{background:rgba(255,255,255,0.04);}
th{padding:12px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);font-weight:700;}
td{padding:11px 14px;border-top:1px solid var(--border);vertical-align:middle;}
tr:hover td{background:rgba(255,255,255,0.02);}
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-ok{background:rgba(34,197,94,0.15);color:var(--green);border:1px solid rgba(34,197,94,0.3);}
.badge-used{background:rgba(107,114,128,0.15);color:#9ca3af;border:1px solid rgba(107,114,128,0.3);}
.badge-exp{background:var(--accent-dim);color:var(--accent);border:1px solid var(--border-accent);}
.code-chip{font-family:monospace;background:rgba(232,25,44,0.12);color:#ff4d63;padding:3px 10px;border-radius:6px;border:1px solid rgba(232,25,44,0.2);letter-spacing:1px;}
.gold-chip{font-family:monospace;background:rgba(245,158,11,0.12);color:var(--gold);padding:3px 10px;border-radius:6px;border:1px solid rgba(245,158,11,0.3);letter-spacing:1px;}
.actions{display:flex;gap:6px;flex-wrap:wrap;}
</style>
</head>
<body class="admin-dark">
<div class="wrap">
  <div class="toolbar">
    <a class="btn" href="dashboard.php">📊 Dashboard</a>
    <a class="btn" href="quan_ly_voucher.php">🎟️ Voucher</a>
    <a class="btn" href="quan_ly_user.php">👥 Users</a>
    <a class="btn" href="../user/index.php">🏠 Trang người dùng</a>
  </div>

  <h1>💎 Quản lý Thẻ Quà Tặng & Điểm TTVH</h1>

  <?php if ($msg): ?><div class="alert <?= $msgOk ? '' : 'err' ?>"><?= $msg ?></div><?php endif; ?>

  <div class="tabs">
    <button class="tab-btn <?= $tab === 'giftcard' ? 'active' : '' ?>" onclick="switchTab('giftcard')">🎁 Thẻ quà tặng</button>
    <button class="tab-btn <?= $tab === 'points' ? 'active' : '' ?>" onclick="switchTab('points')">⭐ Điểm TTVH</button>
  </div>

  <!-- ─── TAB: GIFT CARDS ─── -->
  <div class="tab-pane <?= $tab === 'giftcard' ? 'active' : '' ?>" id="tab-giftcard">

    <div class="form-card">
      <h2>➕ Tạo thẻ quà tặng mới</h2>
      <form method="POST">
        <input type="hidden" name="action" value="add_gc">
        <div class="form-grid">
          <div class="field">
            <label>Mã thẻ *</label>
            <input type="text" name="code" placeholder="VD: GC-ABCD-1234" required style="text-transform:uppercase">
          </div>
          <div class="field">
            <label>Số tiền (₫) *</label>
            <input type="number" name="balance" min="1000" step="1000" placeholder="VD: 100000">
          </div>
          <div class="field">
            <label>Ghi chú</label>
            <input type="text" name="note" placeholder="VD: Tặng khách VIP">
          </div>
          <div class="field">
            <label>Hết hạn</label>
            <input type="date" name="expired_at">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-red">🎁 Tạo thẻ</button>
          <button type="button" class="btn btn-gold" onclick="generateCode()">⚡ Tự sinh mã</button>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Mã thẻ</th><th>Số tiền</th><th>Trạng thái</th><th>Hết hạn</th><th>Ghi chú</th><th>Hành động</th></tr>
        </thead>
        <tbody>
        <?php if (empty($gift_cards)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:28px">Chưa có thẻ nào.</td></tr>
        <?php else: foreach ($gift_cards as $gc): ?>
          <?php
            $today = date('Y-m-d');
            $isExp = !empty($gc['expired_at']) && $gc['expired_at'] < $today;
            $status = $gc['used'] ? 'badge-used' : ($isExp ? 'badge-exp' : 'badge-ok');
            $statusText = $gc['used'] ? ('Đã dùng bởi ' . ($gc['used_by_name'] ?? '#'.$gc['used_by'])) : ($isExp ? 'Hết hạn' : 'Còn hiệu lực');
          ?>
          <tr>
            <td style="color:var(--muted)">#<?= $gc['id'] ?></td>
            <td><span class="code-chip"><?= htmlspecialchars($gc['code']) ?></span></td>
            <td style="color:var(--gold);font-weight:700"><?= number_format($gc['balance'],0,',','.') ?>₫</td>
            <td><span class="badge <?= $status ?>"><?= $statusText ?></span></td>
            <td style="font-size:12px;color:var(--muted)"><?= $gc['expired_at'] ? date('d/m/Y', strtotime($gc['expired_at'])) : '—' ?></td>
            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($gc['note']) ?></td>
            <td>
              <div class="actions">
                <a href="?action=del_gc&id=<?= $gc['id'] ?>&tab=giftcard" class="btn btn-sm btn-red"
                   onclick="return confirm('Xóa thẻ này?')">🗑️ Xóa</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ─── TAB: POINTS ─── -->
  <div class="tab-pane <?= $tab === 'points' ? 'active' : '' ?>" id="tab-points">

    <div class="form-card" style="border-color:rgba(245,158,11,0.3)">
      <h2 style="color:var(--gold)">⭐ Thông tin điểm TTVH</h2>
      <ul style="color:var(--muted);font-size:13px;line-height:2;list-style:none;padding:0;margin:0">
        <li>🎫 Mua 1 vé → nhận <strong style="color:var(--gold)">1 điểm / 1.000₫</strong> giá vé</li>
        <li>💰 100 điểm = giảm <strong style="color:var(--gold)">10.000₫</strong> khi thanh toán</li>
        <li>⭐ Điểm có thể cộng dồn qua nhiều lần mua</li>
        <li>🎁 Admin có thể tặng thêm điểm thủ công bên dưới</li>
      </ul>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Tên</th><th>Email</th><th>Điểm hiện tại</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td style="color:var(--muted)">#<?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['ten']) ?></td>
            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="gold-chip">⭐ <?= number_format((int)($u['points'] ?? 0), 0, ',', '.') ?> điểm</span></td>
            <td>
              <form method="POST" style="display:flex;gap:6px;align-items:center">
                <input type="hidden" name="action" value="add_pts">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="number" name="add_points" min="1" placeholder="+ điểm" style="width:90px;padding:5px 8px;border-radius:7px;border:1px solid var(--border);background:#1a1a2e;color:var(--text);font-size:13px;outline:none;">
                <button type="submit" class="btn btn-sm btn-gold">➕ Thêm</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function switchTab(t) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + t).classList.add('active');
  event.target.classList.add('active');
}
function generateCode() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let c = 'GC-';
  for (let i=0;i<4;i++) c += chars[Math.floor(Math.random()*chars.length)];
  c += '-';
  for (let i=0;i<4;i++) c += chars[Math.floor(Math.random()*chars.length)];
  document.querySelector('input[name="code"]').value = c;
}
</script>
</body>
</html>
