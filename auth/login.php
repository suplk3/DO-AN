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

    if ($user && password_verify($mat_khau, $user['mat_khau'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['ten'] = $user['ten'];
        $_SESSION['vai_tro'] = $user['vai_tro'];

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect_url' => '../user/index.php']);
            exit;
        }

        header("Location: ../user/index.php");
        exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu không đúng.";

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
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
                        <?php // form action always point to this script's directory (works even when fragment is injected)
                        $authPath = dirname($_SERVER['SCRIPT_NAME']); // e.g. '/testdoan/auth'
                        ?>
                        <form method="POST" action="<?= $authPath ?>/login.php" class="login-form">
                            <div class="input-group">
                                <input type="email" name="email" placeholder="Email" required>
                            </div>
                            <div class="input-group">
                                <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
                                <span class="icon pw-toggle" title="Hiện/Ẩn">👁</span>
                            </div>
                            <button type="submit" name="login" class="btn-primary">Đăng nhập</button>
                        </form>
                        <p class="login-error message" style="color:#f87171;font-weight:600;"><?php echo $error; ?></p>
                    </div>

                    <div class="auth-view auth-view-register">
                        <h2 class="auth-heading">Đăng ký</h2>
                        <p class="auth-subtitle">Tạo tài khoản mới để lưu vé và nhận nhiều ưu đãi.</p>
                        <?php // compute auth directory for register action too
                        $authPath = dirname($_SERVER['SCRIPT_NAME']);
                        ?>
                        <form method="POST" action="<?= $authPath ?>/register.php" class="register-form">
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
                        <button type="button" class="hero-btn switch-to-register">Đăng ký</button>
                    </div>
                    <div class="hero hero-register">
                        <h3>Tạo tài khoản mới</h3>
                        <p>Lưu lại lịch sử đặt vé, theo dõi suất chiếu yêu thích và nhận ưu đãi dành riêng cho bạn.</p>
                        <button type="button" class="hero-btn switch-to-login">Đăng nhập</button>
                    </div>
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
<body class="login-standalone" style="margin:0; min-height:100vh; background:linear-gradient(135deg,#0b1026,#121835); display:flex; align-items:center; justify-content:center;">
    <div class="login-modal is-inline" role="dialog" aria-modal="true" style="width:960px; max-width:95vw;">
        <div class="auth-card">
            <div class="auth-inner">
                <div class="auth-panel auth-panel-form" style="width:100%; background:rgba(0,0,0,0.4);">
                    <div class="auth-view auth-view-login is-active">
                        <h2 class="auth-heading">Đăng nhập</h2>
                        <p class="auth-subtitle">Hãy đăng nhập để tiếp tục đặt vé và xem lịch sử của bạn.</p>
                        <form method="POST" action="login.php" class="login-form">
                            <div class="input-group">
                                <input type="email" name="email" placeholder="Email" required>
                            </div>
                            <div class="input-group">
                                <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
                                <span class="icon pw-toggle" title="Hiện/Ẩn">👁</span>
                            </div>
                            <button type="submit" name="login" class="btn-primary">Đăng nhập</button>
                            <?php if (!empty($error)): ?>
                                <p class="login-error message" style="color:#f87171;font-weight:600;margin-top:10px;text-align:center;"><?= $error ?></p>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/login-modal.js"></script>
</body>
</html>
