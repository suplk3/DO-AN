<?php
include "../config/db.php";
session_start();

$user_id = $_SESSION['user_id'] ?? 1; // test
$suat_chieu_id = $_POST['suat_chieu_id'];
$ghe = explode(",", $_POST['ghe']);

// insert tickets
foreach ($ghe as $ten_ghe) {
    $sql = "SELECT id FROM ghe WHERE ten_ghe='$ten_ghe'";
    $r = mysqli_query($conn, $sql);
    $ghe_id = mysqli_fetch_assoc($r)['id'];

    mysqli_query($conn, "
        INSERT INTO ve (user_id, ghe_id, suat_chieu_id)
        VALUES ($user_id, $ghe_id, $suat_chieu_id)
    ");
}

// fetch show information for confirmation message
$info = null;
$sql = "SELECT p.ten_phim, sc.ngay, sc.gio
        FROM suat_chieu sc
        JOIN phim p ON sc.phim_id = p.id
        WHERE sc.id = $suat_chieu_id";
$r = mysqli_query($conn, $sql);
if ($r) {
    $info = mysqli_fetch_assoc($r);
}

// formatted values for template
$seat_list = htmlspecialchars(implode(", ", $ghe));
$movie_name = $info['ten_phim'] ?? '';
$show_datetime = '';
if ($info) {
    $show_datetime = date('d/m/Y', strtotime($info['ngay'])) . ' ' . date('H:i', strtotime($info['gio']));
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đặt vé thành công</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<style>
  /* Confetti particles */
  .confetti {
    position: fixed;
    width: 10px;
    height: 10px;
    background: #ff6b6b;
    pointer-events: none;
    top: -10px;
    animation: confetti-fall 4s ease-in forwards;
  }
  
  @keyframes confetti-fall {
    0% {
      transform: translateY(0) translateX(0) rotateZ(0deg);
      opacity: 1;
    }
    100% {
      transform: translateY(100vh) translateX(var(--tx)) rotateZ(720deg);
      opacity: 0;
    }
  }
</style>
</head>
<body class="user-index">
<header class="header">
    <div class="header-inner">
        <div class="logo">CGV</div>
        <nav class="menu">
            <a href="index.php" class="nav-link">🎬 PHIM</a>
            <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
                <a href="../admin/phim.php" class="nav-link admin">⚙️ QUẢN LÝ PHIM</a>
                <a href="../admin/suat_chieu.php" class="nav-link admin">⚙️ QUẢN LÝ SUẤT CHIẾU</a>
            <?php endif; ?>
        </nav>
        <div class="actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="hello">👋 Xin chào</span>
            <a href="ve_cua_toi.php" class="admin-btn">VÉ CỦA TÔI</a>
            <a href="../auth/logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">🚪 ĐĂNG XUẤT</a>
        <?php else: ?>
            <a href="../auth/login.php" class="open-login-modal">🔐 ĐĂNG NHẬP</a>
        <?php endif; ?>
        </div>
    </div>
</header>

<main class="container">
    <div class="message-card">
        <h2>🎉 Đặt vé thành công!</h2>
        <p><strong>Phim:</strong> <?= htmlspecialchars($movie_name) ?></p>
        <?php if ($show_datetime): ?>
            <p><strong>Thời gian:</strong> <?= htmlspecialchars($show_datetime) ?></p>
        <?php endif; ?>
        <p><strong>Ghế:</strong> <?= $seat_list ?></p>
        <p>Cảm ơn bạn đã mua vé. Hẹn gặp lại tại rạp!</p>
        <a href="index.php" class="btn btn-primary">🏠 VỀ TRANG CHÍNH</a>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> CGV Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script src="../assets/js/login-modal.js"></script>
<script>
  // Generate celebratory confetti particles on page load
  function createConfetti() {
    const colors = ['#ff6b6b', '#ff8a80', '#ffab91', '#ffcc80', '#fff59d', '#c8e6c9', '#80deea', '#b3e5fc', '#c5cae9', '#e1bee7'];
    
    for (let i = 0; i < 100; i++) {
      const confetti = document.createElement('div');
      confetti.className = 'confetti';
      confetti.style.left = Math.random() * 100 + '%';
      confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
      confetti.style.animationDelay = Math.random() * 0.8 + 's';
      confetti.style.animationDuration = (Math.random() * 2 + 3.5) + 's';
      confetti.style.width = (Math.random() * 8 + 4) + 'px';
      confetti.style.height = confetti.style.width;
      
      // Random horizontal movement
      const horizontalMove = (Math.random() - 0.5) * 300;
      confetti.style.setProperty('--tx', horizontalMove + 'px');
      
      document.body.appendChild(confetti);
      
      // Remove confetti after animation
      setTimeout(() => confetti.remove(), 5000);
    }
  }
  
  // Trigger confetti on page load
  window.addEventListener('load', createConfetti);
</script>
</body>
</html>