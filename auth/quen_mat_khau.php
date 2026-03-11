<?php
// Đổi mật khẩu qua email (đơn giản, không yêu cầu đăng nhập)
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_pw = trim($_POST['mat_khau_moi'] ?? '');
    $new_cf = trim($_POST['mat_khau_moi_xn'] ?? '');

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
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        if (!$user) {
            $error = "Email không tồn tại trong hệ thống.";
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $up = mysqli_prepare($conn, "UPDATE users SET mat_khau = ? WHERE email = ?");
            mysqli_stmt_bind_param($up, "ss", $hash, $email);
            if (mysqli_stmt_execute($up)) {
                $message = "Đổi mật khẩu thành công. Bạn có thể đăng nhập lại.";
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
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background:#0b1224; color:#e5e7eb; font-family:'Inter',system-ui,sans-serif; }
        .wrap { max-width: 560px; margin: 50px auto; padding: 28px; border-radius: 16px; background:#111827; box-shadow:0 12px 32px rgba(0,0,0,0.25); }
        h1 { margin:0 0 8px 0; }
        p.desc { margin:0 0 16px 0; color:#9ca3af; }
        label { display:block; margin-top:12px; font-weight:600; }
        input { width:100%; padding:14px 14px; border-radius:12px; border:1px solid rgba(255,255,255,0.14); background:rgba(255,255,255,0.07); color:#fff; }
        .btn { width:100%; margin-top:16px; padding:12px; border:none; border-radius:10px; background:linear-gradient(135deg,#f97316,#ec4899); color:#fff; font-weight:700; cursor:pointer; }
        .msg { margin-top:12px; padding:10px 12px; border-radius:10px; font-weight:600; }
        .ok { background:rgba(34,197,94,0.15); color:#bbf7d0; border:1px solid rgba(34,197,94,0.35); }
        .err { background:rgba(248,113,113,0.15); color:#fecdd3; border:1px solid rgba(248,113,113,0.35); }
        a.back { display:inline-block; margin-top:12px; color:#60a5fa; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Quên mật khẩu</h1>
        <p class="desc">Nhập email đăng ký và mật khẩu mới (≥ 3 ký tự).</p>
        <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label>Email</label>
            <input type="email" name="email" required placeholder="email@gmail.com">

            <label>Tạo mật khẩu mới</label>
            <input type="password" name="mat_khau_moi" required placeholder="Ít nhất 3 ký tự">

            <label>Nhập lại mật khẩu đã đặt</label>
            <input type="password" name="mat_khau_moi_xn" required placeholder="Nhập lại mật khẩu">

            <button class="btn" type="submit">Đổi mật khẩu</button>
        </form>
        <a class="back" href="../user/index.php?show_login=1">← Quay lại đăng nhập</a>
    </div>
</body>
</html>
