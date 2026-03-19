<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

// ── Auto-create tables ─────────────────────────────────────────────────────
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS vouchers (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        code           VARCHAR(50) UNIQUE NOT NULL,
        description    VARCHAR(255) DEFAULT '',
        discount_type  ENUM('percent','fixed') DEFAULT 'percent',
        discount_value INT NOT NULL DEFAULT 0,
        max_discount   INT DEFAULT NULL,
        min_total      INT DEFAULT 0,
        usage_limit    INT DEFAULT NULL,
        used_count     INT DEFAULT 0,
        start_date     DATE DEFAULT NULL,
        end_date       DATE DEFAULT NULL,
        active         TINYINT(1) DEFAULT 1,
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS voucher_usages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        voucher_id INT NOT NULL,
        user_id    INT NOT NULL,
        used_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_usage (voucher_id, user_id)
    )
");

$msg   = '';
$msgOk = true;

// ── Handle POST actions ────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $desc  = trim($_POST['description'] ?? '');
    $dtype = $_POST['discount_type'] ?? 'percent';
    $dval  = (int)($_POST['discount_value'] ?? 0);
    $maxd  = $_POST['max_discount'] !== '' ? (int)$_POST['max_discount'] : null;
    $mint  = (int)($_POST['min_total'] ?? 0);
    $ulim  = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
    $start = $_POST['start_date'] ?: null;
    $end   = $_POST['end_date']   ?: null;

    if (!$code || $dval <= 0) {
        $msg = 'Vui lòng nhập đầy đủ mã voucher và giá trị giảm.';
        $msgOk = false;
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO vouchers (code,description,discount_type,discount_value,max_discount,min_total,usage_limit,start_date,end_date) VALUES (?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'sssiiiiss', $code, $desc, $dtype, $dval, $maxd, $mint, $ulim, $start, $end);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "Đã thêm voucher <strong>$code</strong>.";
        } else {
            $msg = 'Lỗi: Mã voucher đã tồn tại hoặc dữ liệu không hợp lệ.';
            $msgOk = false;
        }
        mysqli_stmt_close($stmt);
    }
}

if ($action === 'edit') {
    $id    = (int)($_POST['id'] ?? 0);
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $desc  = trim($_POST['description'] ?? '');
    $dtype = $_POST['discount_type'] ?? 'percent';
    $dval  = (int)($_POST['discount_value'] ?? 0);
    $maxd  = $_POST['max_discount'] !== '' ? (int)$_POST['max_discount'] : null;
    $mint  = (int)($_POST['min_total'] ?? 0);
    $ulim  = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
    $start = $_POST['start_date'] ?: null;
    $end   = $_POST['end_date']   ?: null;

    $stmt = mysqli_prepare($conn, "UPDATE vouchers SET code=?,description=?,discount_type=?,discount_value=?,max_discount=?,min_total=?,usage_limit=?,start_date=?,end_date=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sssiiiissi', $code, $desc, $dtype, $dval, $maxd, $mint, $ulim, $start, $end, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Đã cập nhật voucher <strong>$code</strong>.";
}

if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE vouchers SET active = 1 - active WHERE id = $id");
    header('Location: quan_ly_voucher.php'); exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM voucher_usages WHERE voucher_id = $id");
    mysqli_query($conn, "DELETE FROM vouchers WHERE id = $id");
    header('Location: quan_ly_voucher.php'); exit;
}

// ── Fetch edit target ──────────────────────────────────────────────────────
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = mysqli_query($conn, "SELECT * FROM vouchers WHERE id = $eid");
    $editing = mysqli_fetch_assoc($r);
}

// ── Fetch all vouchers ─────────────────────────────────────────────────────
$vouchers = [];
$rs = mysqli_query($conn, "SELECT * FROM vouchers ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($rs)) $vouchers[] = $row;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý Voucher – Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root {
  --accent: #e8192c;
  --accent-dim: rgba(232,25,44,0.15);
  --bg: #05060e;
  --surface: #0d1022;
  --card: #111827;
  --border: rgba(255,255,255,0.07);
  --border-accent: rgba(232,25,44,0.3);
  --text: #f0f0f5;
  --muted: #6b7280;
  --green: #22c55e;
}
* { box-sizing: border-box; }
body.admin-dark {
  background: var(--bg);
  background-image:
    radial-gradient(ellipse 70% 40% at 10% 0%, rgba(232,25,44,0.1) 0%, transparent 60%),
    radial-gradient(ellipse 50% 30% at 90% 100%, rgba(59,130,246,0.08) 0%, transparent 60%);
  color: var(--text);
  font-family: "Segoe UI", Inter, sans-serif;
  min-height: 100vh;
  margin: 0;
}
.wrap { max-width: 1200px; margin: 0 auto; padding: 24px 16px 60px; }
.toolbar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.btn { padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 700;
  font-size: 13px; border: 1px solid rgba(59,130,246,0.4);
  color: #93c5fd; background: rgba(59,130,246,0.1); cursor: pointer; transition: .2s; }
.btn:hover { background: rgba(59,130,246,0.2); }
.btn-red { border-color: var(--border-accent); color: var(--accent); background: var(--accent-dim); }
.btn-red:hover { background: rgba(232,25,44,0.25); }
.btn-green { border-color: rgba(34,197,94,0.4); color: var(--green); background: rgba(34,197,94,0.08); }
.btn-green:hover { background: rgba(34,197,94,0.18); }
.btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }

h1 { font-size: 26px; font-weight: 800; margin-bottom: 20px; color: #fff; }
h2 { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 16px; }

.alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px;
  background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
.alert.err { background: var(--accent-dim); border-color: var(--border-accent); color: #fca5a5; }

/* Form card */
.form-card {
  background: var(--card);
  border: 1px solid var(--border-accent);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 28px;
  position: relative;
}
.form-card::before {
  content: '';
  position: absolute; top:0; left:0; right:0; height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
  border-radius: 16px 16px 0 0;
}
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 14px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
.field input, .field select, .field textarea {
  padding: 10px 12px; border-radius: 9px;
  border: 1px solid var(--border); background: #1a1a2e;
  color: var(--text); font-size: 14px; outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.field input:focus, .field select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.field input::placeholder { color: var(--muted); }
.field small { font-size: 11px; color: var(--muted); }
.form-actions { display: flex; gap: 10px; margin-top: 18px; }

/* Table */
.table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead { background: rgba(255,255,255,0.04); }
th { padding: 13px 14px; text-align: left; font-size: 11px; text-transform: uppercase;
  letter-spacing: .6px; color: var(--muted); font-weight: 700; }
td { padding: 12px 14px; border-top: 1px solid var(--border); vertical-align: middle; }
tr:hover td { background: rgba(255,255,255,0.02); }

.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-active { background: rgba(34,197,94,0.15); color: var(--green); border: 1px solid rgba(34,197,94,0.3); }
.badge-off { background: rgba(107,114,128,0.15); color: #9ca3af; border: 1px solid rgba(107,114,128,0.3); }
.badge-pct { background: rgba(59,130,246,0.12); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); }
.badge-fixed { background: var(--accent-dim); color: var(--accent); border: 1px solid var(--border-accent); }

.code-chip {
  font-family: monospace; background: rgba(232,25,44,0.12);
  color: #ff4d63; padding: 3px 10px; border-radius: 6px; font-size: 13px;
  border: 1px solid rgba(232,25,44,0.2); letter-spacing: 1px;
}
.actions { display: flex; gap: 6px; flex-wrap: wrap; }
</style>
</head>
<body class="admin-dark">
<div class="wrap">

  <div class="toolbar">
    <a class="btn" href="dashboard.php">📊 Dashboard</a>
    <a class="btn" href="phim.php">🎬 Phim</a>
    <a class="btn" href="suat_chieu.php">🗓️ Suất chiếu</a>
    <a class="btn" href="quan_ly_user.php">👥 Users</a>
    <a class="btn" href="quan_ly_combo.php">🍿 Combo</a>
  </div>

  <h1>🎟️ Quản lý Voucher</h1>

  <?php if ($msg): ?>
    <div class="alert <?= $msgOk ? '' : 'err' ?>"><?= $msg ?></div>
  <?php endif; ?>

  <!-- ── FORM THÊM / SỬA ── -->
  <div class="form-card">
    <h2><?= $editing ? '✏️ Sửa Voucher #' . $editing['id'] : '➕ Thêm Voucher mới' ?></h2>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

      <div class="form-grid">
        <div class="field">
          <label>Mã Voucher *</label>
          <input type="text" name="code" value="<?= htmlspecialchars($editing['code'] ?? '') ?>"
                 placeholder="VD: SUMMER30" required style="text-transform:uppercase">
          <small>Chỉ chữ hoa, số, dấu gạch</small>
        </div>
        <div class="field">
          <label>Mô tả</label>
          <input type="text" name="description" value="<?= htmlspecialchars($editing['description'] ?? '') ?>"
                 placeholder="VD: Giảm 30% mùa hè">
        </div>
        <div class="field">
          <label>Loại giảm *</label>
          <select name="discount_type">
            <option value="percent"  <?= ($editing['discount_type'] ?? 'percent') === 'percent'  ? 'selected':'' ?>>% Phần trăm</option>
            <option value="fixed"    <?= ($editing['discount_type'] ?? '') === 'fixed' ? 'selected':'' ?>>₫ Số tiền cố định</option>
          </select>
        </div>
        <div class="field">
          <label>Giá trị giảm *</label>
          <input type="number" name="discount_value" min="1"
                 value="<?= htmlspecialchars($editing['discount_value'] ?? '') ?>" placeholder="VD: 30">
          <small>Nếu %, nhập 30 → giảm 30%</small>
        </div>
        <div class="field">
          <label>Giảm tối đa (₫)</label>
          <input type="number" name="max_discount" min="0"
                 value="<?= htmlspecialchars($editing['max_discount'] ?? '') ?>" placeholder="Bỏ trống = không giới hạn">
          <small>Chỉ áp dụng khi loại % </small>
        </div>
        <div class="field">
          <label>Đơn tối thiểu (₫)</label>
          <input type="number" name="min_total" min="0"
                 value="<?= htmlspecialchars($editing['min_total'] ?? 0) ?>" placeholder="0 = không giới hạn">
        </div>
        <div class="field">
          <label>Giới hạn lượt dùng</label>
          <input type="number" name="usage_limit" min="1"
                 value="<?= htmlspecialchars($editing['usage_limit'] ?? '') ?>" placeholder="Bỏ trống = không giới hạn">
        </div>
        <div class="field">
          <label>Ngày bắt đầu</label>
          <input type="date" name="start_date" value="<?= $editing['start_date'] ?? '' ?>">
        </div>
        <div class="field">
          <label>Ngày hết hạn</label>
          <input type="date" name="end_date" value="<?= $editing['end_date'] ?? '' ?>">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-red">
          <?= $editing ? '💾 Cập nhật' : '➕ Thêm Voucher' ?>
        </button>
        <?php if ($editing): ?>
          <a href="quan_ly_voucher.php" class="btn">✖ Hủy</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ── DANH SÁCH ── -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Mã</th>
          <th>Mô tả</th>
          <th>Giảm</th>
          <th>Đơn tối thiểu</th>
          <th>Lượt dùng</th>
          <th>Hạn sử dụng</th>
          <th>Trạng thái</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($vouchers)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px">Chưa có voucher nào.</td></tr>
      <?php else: foreach ($vouchers as $v): ?>
        <tr>
          <td style="color:var(--muted)">#<?= $v['id'] ?></td>
          <td><span class="code-chip"><?= htmlspecialchars($v['code']) ?></span></td>
          <td style="color:var(--muted);max-width:180px"><?= htmlspecialchars($v['description']) ?></td>
          <td>
            <?php if ($v['discount_type'] === 'percent'): ?>
              <span class="badge badge-pct"><?= $v['discount_value'] ?>%</span>
              <?php if ($v['max_discount']): ?>
                <small style="color:var(--muted)"> tối đa <?= number_format($v['max_discount'],0,',','.') ?>₫</small>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-fixed"><?= number_format($v['discount_value'],0,',','.') ?>₫</span>
            <?php endif; ?>
          </td>
          <td><?= $v['min_total'] > 0 ? number_format($v['min_total'],0,',','.').'₫' : '<span style="color:var(--muted)">—</span>' ?></td>
          <td>
            <?= $v['used_count'] ?>
            <?php if ($v['usage_limit']): ?>
              / <?= $v['usage_limit'] ?>
            <?php else: ?>
              <span style="color:var(--muted)">/ ∞</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--muted)">
            <?php
              $today = date('Y-m-d');
              if ($v['end_date'] && $v['end_date'] < $today) {
                  echo '<span style="color:#f87171">Hết hạn: '.date('d/m/Y', strtotime($v['end_date'])).'</span>';
              } elseif ($v['end_date']) {
                  echo 'Đến: '.date('d/m/Y', strtotime($v['end_date']));
              } else {
                  echo '<span style="color:var(--muted)">Không giới hạn</span>';
              }
            ?>
          </td>
          <td>
            <span class="badge <?= $v['active'] ? 'badge-active' : 'badge-off' ?>">
              <?= $v['active'] ? '✅ Hoạt động' : '⛔ Tắt' ?>
            </span>
          </td>
          <td>
            <div class="actions">
              <a href="?edit=<?= $v['id'] ?>" class="btn btn-sm">✏️ Sửa</a>
              <a href="?action=toggle&id=<?= $v['id'] ?>" class="btn btn-sm <?= $v['active'] ? 'btn-red' : 'btn-green' ?>"
                 onclick="return confirm('<?= $v['active'] ? 'Tắt' : 'Bật' ?> voucher này?')">
                <?= $v['active'] ? '⛔ Tắt' : '✅ Bật' ?>
              </a>
              <a href="?action=delete&id=<?= $v['id'] ?>" class="btn btn-sm btn-red"
                 onclick="return confirm('Xóa voucher <?= htmlspecialchars($v['code']) ?>? Lịch sử sử dụng cũng sẽ bị xóa.')">
                🗑️ Xóa
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
