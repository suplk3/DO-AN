<?php
session_start();
require_once '../config/db.php';

$error = '';

if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $mat_khau = $_POST['mat_khau'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($mat_khau, $user['mat_khau'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['ten']     = $user['ten'];
        $_SESSION['vai_tro'] = $user['vai_tro'];

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect_url' => '../user/index.php']);
            exit;
        }
        header("Location: ../user/index.php"); exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu không đúng.";
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

/* Modal fragment */
if (isset($_GET['modal']) && $_GET['modal'] == '1') {
    $authPath = dirname($_SERVER['SCRIPT_NAME']); ?>
    <div class="login-modal" role="dialog" aria-modal="true">
      <div class="auth-card">
        <button class="login-close" aria-label="Đóng">&times;</button>
        <div class="auth-inner">
          <div class="auth-panel auth-panel-form">
            <div class="auth-view auth-view-login is-active">
              <h2 class="auth-heading">Đăng nhập</h2>
              <p class="auth-subtitle">Đăng nhập để đặt vé và xem lịch sử của bạn.</p>
              <form method="POST" action="<?= $authPath ?>/login.php" class="login-form">
                <div class="input-group">
                  <input type="email" name="email" placeholder="Email" required autocomplete="email">
                  <span class="icon">📧</span>
                </div>
                <div class="input-group">
                  <input type="password" name="mat_khau" placeholder="Mật khẩu" required autocomplete="current-password">
                  <span class="icon pw-toggle" title="Hiện/Ẩn">👁</span>
                </div>
                <div style="text-align:right;margin-top:4px;">
                  <a class="forgot-link" href="<?= $authPath ?>/quen_mat_khau.php">Quên mật khẩu?</a>
                </div>
                <button type="submit" name="login" class="btn-primary">Đăng nhập</button>
              </form>
              <p class="login-error message" style="color:#f87171;font-weight:600;margin-top:8px;">
                <?= htmlspecialchars($error) ?>
              </p>
              <p class="auth-small">
                Chưa có tài khoản?
                <button type="button" class="link-btn switch-to-register">Đăng ký ngay</button>
              </p>
            </div>

            <div class="auth-view auth-view-register">
              <h2 class="auth-heading">Đăng ký</h2>
              <p class="auth-subtitle">Tạo tài khoản để lưu vé và nhận ưu đãi.</p>
              <form method="POST" action="<?= $authPath ?>/register.php" class="register-form">
                <div class="input-group">
                  <input name="ten" placeholder="Họ và tên" required autocomplete="name">
                  <span class="icon">👤</span>
                </div>
                <div class="input-group">
                  <input name="email" type="email" placeholder="Email" required autocomplete="email">
                  <span class="icon">📧</span>
                </div>
                <div class="input-group">
                  <input name="mat_khau" type="password" placeholder="Mật khẩu (≥3 ký tự)" required>
                  <span class="icon pw-toggle">👁</span>
                </div>
                <button name="dangky" class="btn-primary">Tạo tài khoản</button>
                <p class="auth-small">
                  Đã có tài khoản?
                  <button type="button" class="link-btn switch-to-login">Đăng nhập</button>
                </p>
                <p class="register-msg message"></p>
              </form>
            </div>
          </div>

          <div class="auth-panel auth-panel-hero">
            <div class="hero hero-login">
              <h3>🎬 Chào mừng trở lại!</h3>
              <p>Đăng nhập để xem lịch chiếu mới nhất và đặt vé nhanh chóng.</p>
              <div class="hero-features">
                <div class="hero-feature"><span class="hero-feature-icon">🎟️</span>Đặt vé dễ dàng</div>
                <div class="hero-feature"><span class="hero-feature-icon">📋</span>Xem lịch sử đặt vé</div>
                <div class="hero-feature"><span class="hero-feature-icon">🔔</span>Nhận thông báo suất chiếu</div>
              </div>
              <button type="button" class="hero-btn switch-to-register">Tạo tài khoản mới</button>
            </div>
            <div class="hero hero-register">
              <h3>🌟 Tham gia ngay!</h3>
              <p>Lưu lịch sử đặt vé, theo dõi phim yêu thích và nhận ưu đãi độc quyền.</p>
              <div class="hero-features">
                <div class="hero-feature"><span class="hero-feature-icon">⚡</span>Đặt vé siêu nhanh</div>
                <div class="hero-feature"><span class="hero-feature-icon">💎</span>Ưu đãi thành viên</div>
                <div class="hero-feature"><span class="hero-feature-icon">📱</span>Quản lý trên mobile</div>
              </div>
              <button type="button" class="hero-btn switch-to-login">Đã có tài khoản</button>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php exit; }

/* Standalone page */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Đăng nhập — TTVH Cinemas</title>
  <link rel="stylesheet" href="../assets/css/login-modal.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh;
      background: radial-gradient(ellipse 100% 60% at 50% 0%, #1a0a2e 0%, #060912 55%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      font-family: 'Be Vietnam Pro', system-ui, sans-serif;
      padding: 24px 16px;
    }
    .brand {
      font-size: 28px; font-weight: 900;
      color: #e8192c; letter-spacing: 1px;
      text-decoration: none; margin-bottom: 28px;
      text-shadow: 0 0 20px rgba(232,25,44,0.4);
    }
    .login-modal.is-inline {
      width: 100%; max-width: 880px;
      animation: modalIn .4s cubic-bezier(0.22,1,0.36,1);
    }
    @keyframes modalIn {
      from { opacity: 0; transform: scale(0.95) translateY(20px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }
  </style>
</head>
<body>
  <a href="../user/index.php" class="brand">TTVH</a>

  <div class="login-modal is-inline" role="dialog">
    <div class="auth-card">
      <div class="auth-inner">
        <div class="auth-panel auth-panel-form">
          <div class="auth-view auth-view-login" style="position:relative;opacity:1;transform:none;pointer-events:auto;">
            <h2 class="auth-heading">Đăng nhập</h2>
            <p class="auth-subtitle">Chào mừng trở lại! Đăng nhập để tiếp tục.</p>
            <form method="POST" action="login.php" class="login-form">
              <div class="input-group">
                <input type="email" name="email" placeholder="Email" required autocomplete="email">
                <span class="icon">📧</span>
              </div>
              <div class="input-group">
                <input type="password" name="mat_khau" placeholder="Mật khẩu" required autocomplete="current-password">
                <span class="icon pw-toggle" title="Hiện/Ẩn">👁</span>
              </div>
              <div style="text-align:right;margin-top:4px;">
                <a class="forgot-link" href="quen_mat_khau.php">Quên mật khẩu?</a>
              </div>
              <button type="submit" name="login" class="btn-primary">Đăng nhập</button>
              <?php if ($error): ?>
              <div class="login-toast" style="margin-top:12px;">
                <span class="toast-icon">!</span>
                <span><?= htmlspecialchars($error) ?></span>
              </div>
              <?php endif; ?>
            </form>
            <p class="auth-small" style="margin-top:16px;">
              Chưa có tài khoản? <a href="register.php" style="color:#a78bfa;font-weight:700;">Đăng ký ngay</a>
            </p>
          </div>
        </div>

        <div class="auth-panel auth-panel-hero">
          <div class="hero hero-login" style="opacity:1;transform:none;pointer-events:auto;">
            <h3>🎬 TTVH Cinemas</h3>
            <p>Đăng nhập để khám phá hàng trăm bộ phim và đặt vé nhanh chóng.</p>
            <div class="hero-features">
              <div class="hero-feature"><span class="hero-feature-icon">🎟️</span>Đặt vé không cần xếp hàng</div>
              <div class="hero-feature"><span class="hero-feature-icon">📋</span>Theo dõi lịch sử đặt vé</div>
              <div class="hero-feature"><span class="hero-feature-icon">🔔</span>Nhận thông báo phim mới</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/login-modal.js"></script>
</body>
</html>