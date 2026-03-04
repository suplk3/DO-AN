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

            <div class="auth-inner">
                <div class="auth-panel auth-panel-form">
                    <div class="auth-view auth-view-login is-active">
                        <h2 class="auth-heading">Đăng nhập</h2>
                        <p class="auth-subtitle">Hãy đăng nhập để tiếp tục đặt vé và xem lịch sử của bạn.</p>
                        <form method="POST" action="/testdoan/auth/login.php" class="login-form">
                            <div class="input-group">
                                <input type="email" name="email" placeholder="Email" required>
                            </div>
                            <div class="input-group">
                                <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
                                <span class="icon pw-toggle" title="Hiện/Ẩn">👁</span>
                            </div>
                            <button type="submit" name="login" class="btn-primary">Đăng nhập23123</button>
                        </form>
                        <p class="login-error message"><?php echo $error; ?></p>
                    </div>

                    <div class="auth-view auth-view-register">
                        <h2 class="auth-heading">Đăng ký</h2>
                        <p class="auth-subtitle">Tạo tài khoản mới để lưu vé và nhận nhiều ưu đãi.</p>
                        <form method="POST" action="/testdoan/auth/register.php" class="register-form">
                            <div class="input-group"><input name="ten" placeholder="Tên" required></div>
                            <div class="input-group"><input name="email" type="email" placeholder="Email" required></div>
                            <div class="input-group"><input name="mat_khau" type="password" placeholder="Mật khẩu" required></div>
                            <button name="dangky" class="btn-primary">Đăng ký</button>
                            <p class="auth-small">
                                Đã có tài khoản?
                                <button type="button" class="link-btn switch-to-login">Quay lại đăng nhập</button>
                            </p>
                            <p class="register-msg message"></p>
                        </form>
                    </div>
                </div>

                <div class="auth-panel auth-panel-hero">
                    <div class="hero hero-login">
                        <h3>Chào mừng trở lại</h3>
                        <p>Đăng nhập để cập nhật các suất chiếu mới nhất và những bộ phim bạn yêu thích.</p>
                        <a href="/testdoan/auth/register.php" class="hero-btn">Đăng ký</a>
                    </div>
                    <div class="hero hero-register"></div>
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
