<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email'] ?? '');
    $new_pw  = trim($_POST['mat_khau_moi'] ?? '');
    $new_cf  = trim($_POST['mat_khau_moi_xn'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif ($new_pw === '' || strlen($new_pw) < 3) {
        $error = "Mật khẩu mới phải từ 3 ký tự trở lên.";
    } elseif ($new_pw !== $new_cf) {
        $error = "Xác nhận mật khẩu không khớp.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$user) {
            $error = "Email không tồn tại trong hệ thống.";
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $up   = mysqli_prepare($conn, "UPDATE users SET mat_khau = ? WHERE email = ?");
            mysqli_stmt_bind_param($up, "ss", $hash, $email);
            if (mysqli_stmt_execute($up)) {
                $message = "Đổi mật khẩu thành công! Bạn có thể đăng nhập lại.";
            } else {
                $error = "Không thể đổi mật khẩu, vui lòng thử lại.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Quên mật khẩu — TTVH Cinemas</title>
  <link rel="stylesheet" href="../assets/css/login-modal.css">
  <style>
    /* ── standalone page ── */
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh;
      background: radial-gradient(ellipse 100% 60% at 50% 0%, #1a0a2e 0%, #060912 55%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      font-family: 'Be Vietnam Pro', system-ui, sans-serif;
      color: #f1f5f9;
      padding: 24px 16px;
    }

    /* Brand */
    .brand {
      font-size: 26px; font-weight: 900;
      color: #e8192c; letter-spacing: 1px;
      text-decoration: none;
      margin-bottom: 32px;
      text-shadow: 0 0 20px rgba(232,25,44,0.4);
      display: block; text-align: center;
    }

    /* Card */
    .wrap {
      width: 100%; max-width: 420px;
      background: linear-gradient(135deg,#111827,#0d1322);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 20px;
      padding: 36px 32px;
      box-shadow: 0 32px 80px rgba(0,0,0,0.7);
      position: relative; overflow: hidden;
      animation: cardIn .4s cubic-bezier(0.22,1,0.36,1);
    }
    .wrap::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(90deg,transparent,rgba(232,25,44,.4),transparent);
    }
    @keyframes cardIn {
      from { opacity: 0; transform: translateY(24px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .wrap h1 {
      font-size: 22px; font-weight: 800;
      margin: 0 0 6px; letter-spacing: -0.2px;
    }
    .wrap .desc {
      font-size: 13px; color: #64748b;
      margin: 0 0 24px; line-height: 1.6;
    }

    /* Messages */
    .msg {
      margin-bottom: 16px; padding: 12px 14px;
      border-radius: 12px; font-size: 13px;
      font-weight: 600; line-height: 1.5;
      animation: toastIn .3s cubic-bezier(0.22,1,0.36,1);
    }
    @keyframes toastIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .msg.ok  { background: rgba(34,197,94,.1); color: #86efac; border: 1px solid rgba(34,197,94,.25); }
    .msg.err { background: rgba(232,25,44,.1); color: #fca5a5; border: 1px solid rgba(232,25,44,.25); }

    /* Form */
    .field { margin-bottom: 14px; }
    .field label {
      display: block; font-size: 12px; font-weight: 700;
      letter-spacing: 0.4px; color: #94a3b8;
      margin-bottom: 6px; text-transform: uppercase;
    }
    .field input {
      width: 100%; padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.1);
      background: rgba(255,255,255,0.05);
      color: #f1f5f9; font-family: inherit;
      font-size: 14px; outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .field input::placeholder { color: rgba(255,255,255,0.2); }
    .field input:focus {
      border-color: rgba(124,58,237,.6);
      background: rgba(255,255,255,.07);
      box-shadow: 0 0 0 3px rgba(124,58,237,.15);
    }

    .btn-submit {
      width: 100%; padding: 13px;
      border: none; border-radius: 12px;
      background: linear-gradient(135deg,#f97316,#ec4899);
      color: #fff; font-family: inherit;
      font-size: 14px; font-weight: 800;
      letter-spacing: 0.4px; cursor: pointer;
      margin-top: 6px;
      box-shadow: 0 8px 24px rgba(236,72,153,.35);
      transition: all .22s cubic-bezier(0.34,1.56,0.64,1);
      position: relative; overflow: hidden;
    }
    .btn-submit::before {
      content: '';
      position: absolute; top: -50%; left: -60%;
      width: 40%; height: 200%;
      background: rgba(255,255,255,.2);
      transform: skewX(-20deg); transition: left .5s;
    }
    .btn-submit:hover::before { left: 130%; }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px rgba(236,72,153,.5);
    }

    .back-link {
      display: block; text-align: center;
      margin-top: 20px; font-size: 13px;
      color: #60a5fa; text-decoration: none;
      transition: color .2s;
    }
    .back-link:hover { color: #93c5fd; }

    @media (max-width: 460px) {
      .wrap { padding: 28px 20px; border-radius: 16px; }
    }
  </style>
</head>
<body>
  <a href="../user/index.php" class="brand">TTVH</a>

  <div class="wrap">
    <h1>Quên mật khẩu</h1>
    <p class="desc">Nhập email đã đăng ký và tạo mật khẩu mới (ít nhất 3 ký tự).</p>

    <?php if ($message): ?>
    <div class="msg ok">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="msg err">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$message): ?>
    <form method="post" autocomplete="off">
      <div class="field">
        <label>Email đăng ký</label>
        <input type="email" name="email" required
               placeholder="ten@gmail.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Mật khẩu mới</label>
        <input type="password" name="mat_khau_moi" required placeholder="Ít nhất 3 ký tự">
      </div>
      <div class="field">
        <label>Nhập lại mật khẩu</label>
        <input type="password" name="mat_khau_moi_xn" required placeholder="Nhập lại mật khẩu">
      </div>
      <button class="btn-submit" type="submit">Đổi mật khẩu</button>
    </form>
    <?php else: ?>
    <a href="../user/index.php?show_login=1" class="btn-submit" style="display:block;text-align:center;text-decoration:none;padding:13px;">
      Đăng nhập ngay →
    </a>
    <?php endif; ?>

    <a href="../user/index.php?show_login=1" class="back-link">← Quay lại đăng nhập</a>
  </div>
</body>
</html>