<?php
session_start();
require_once '../config/db.php';

$error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $mat_khau = $_POST['mat_khau'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($mat_khau, $user['mat_khau'])) {
             session_start();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['ten'] = $user['ten'];
            $_SESSION['vai_tro'] = $user['vai_tro'];
     header("Location: ../user/index.php");
           /* if ($user['vai_tro'] === 'admin') {
                header("Location: ../user/index.php");
            } else {
                header("Location: ../user/index.php");
            }*/
         //   exit;
        } else {
            $error = "Sai mật khẩu";
        }
    } else {
        $error = "Email không tồn tại";
    }
}
?>

<?php
// If requested as a modal fragment, output only the form markup
if (isset($_GET['modal']) && $_GET['modal'] == '1') {
    ?>
    <div class="login-modal" role="dialog" aria-modal="true">
        <div class="auth-card">
            <button class="login-close" aria-label="Đóng">&times;</button>
            <div class="auth-avatar">👤</div>

            <div class="auth-views">
                <div class="auth-view" data-view="login">
                    <h2>Sign In</h2>
                    <form method="POST" action="/testdoan/auth/login.php" class="login-form">
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="input-group">
                            <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
                            <span class="icon pw-toggle" title="Hiện/Ẩn">👁</span>
                        </div>
                        <button type="submit" name="login" class="btn-primary">Đăng nhập</button>

                        <div style="display:flex;gap:8px;margin-top:10px;align-items:center;">
                            <label style="font-weight:400;margin-right:auto;"><input type="checkbox" name="remember"> Ghi nhớ</label>
                            <a href="#" class="forgot-link" data-view-target="forgot">Quên mật khẩu?</a>
                        </div>

                        <div style="margin-top:12px;text-align:center;">
                            <button type="button" class="switch-to-register" style="background:transparent;border:1px solid rgba(255,255,255,0.18);padding:8px 12px;border-radius:8px;color:#fff;cursor:pointer;">Đăng ký</button>
                        </div>
                    </form>
                    <p class="login-error message"><?php echo $error; ?></p>
                </div>

                <div class="auth-view" data-view="register" style="display:none;">
                    <h2>Sign Up</h2>
                    <form method="POST" action="/testdoan/auth/register.php" class="register-form">
                        <div class="input-group"><input name="ten" placeholder="Tên" required></div>
                        <div class="input-group"><input name="email" type="email" placeholder="Email" required></div>
                        <div class="input-group"><input name="mat_khau" type="password" placeholder="Mật khẩu" required></div>
                        <button name="dangky" class="btn-primary">Đăng ký</button>
                        <div style="margin-top:10px;text-align:center;"><button type="button" class="switch-to-login" style="background:transparent;border:1px solid rgba(255,255,255,0.08);padding:6px 10px;border-radius:8px;color:#fff;cursor:pointer;">Quay lại đăng nhập</button></div>
                    </form>
                </div>

                <div class="auth-view" data-view="forgot" style="display:none;">
                    <h2>Reset</h2>
                    <form method="POST" action="/testdoan/auth/forgot_password.php" class="forgot-form">
                        <div class="input-group"><input name="email" type="email" placeholder="Email đã đăng ký" required></div>
                        <button name="send_reset" class="btn-primary">Gửi liên kết</button>
                    </form>
                    <p class="forgot-msg message"></p>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="../assets/css/login-modal.css">
</head>
<body>

<h2>ĐĂNG NHẬP</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="mat_khau" placeholder="Mật khẩu" required><br><br>
    <button type="submit" name="login">Đăng nhập</button>
</form>

<p style="color:red"><?= $error ?></p>

<script src="../assets/js/login-modal.js"></script>
</body>
</html>
