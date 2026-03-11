<?php
include "../config/db.php";
session_start();

// --- Basic Input Validation ---
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    die("Lỗi: Dữ liệu không hợp lệ. Vui lòng thử lại.");
}

// extra fields from payment page
if (empty($_POST['agree']) || !isset($_POST['payment_method'])) {
    die("Vui lòng đồng ý điều khoản và chọn hình thức thanh toán.");
}
$payment_method = $_POST['payment_method'];

// ensure terms agreed and payment method chosen
if (empty($_POST['agree']) || !isset($_POST['payment_method'])) {
    die("Vui lòng đồng ý điều khoản và chọn hình thức thanh toán.");
}

$payment_method = $_POST['payment_method'];

$user_id = $_SESSION['user_id'] ?? 1; // Fallback for testing, ensure user is logged in for production
$suat_chieu_id = (int)$_POST['suat_chieu_id'];
$ghe_array = array_filter(explode(",", $_POST['ghe'])); // array_filter to remove empty values

if (empty($ghe_array)) {
    die("Lỗi: Chưa chọn ghế nào.");
}

// --- Get phong_id from suat_chieu_id to ensure correct seat context ---
$phong_id = null;
$stmt = mysqli_prepare($conn, "SELECT phong_id FROM suat_chieu WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $phong_id = (int)$row['phong_id'];
}
mysqli_stmt_close($stmt);

if (!$phong_id) {
    die("Lỗi: Suất chiếu không tồn tại.");
}

// --- Begin Transaction for Atomic Inserts ---
mysqli_begin_transaction($conn);

try {
    // --- Prepare statements for security and performance ---
    $get_ghe_id_stmt = mysqli_prepare($conn, "SELECT id FROM ghe WHERE ten_ghe = ? AND phong_id = ?");
    $insert_ve_stmt = mysqli_prepare($conn, "INSERT INTO ve (user_id, ghe_id, suat_chieu_id) VALUES (?, ?, ?)");

    foreach ($ghe_array as $ten_ghe) {
        // Find ghe_id securely
        mysqli_stmt_bind_param($get_ghe_id_stmt, "si", $ten_ghe, $phong_id);
        mysqli_stmt_execute($get_ghe_id_stmt);
        $result_ghe = mysqli_stmt_get_result($get_ghe_id_stmt);
        $ghe_row = mysqli_fetch_assoc($result_ghe);

        if (!$ghe_row) {
            // If seat doesn't exist in this room, rollback and fail
            throw new Exception("Ghế '{$ten_ghe}' không tồn tại trong phòng này.");
        }
        $ghe_id = (int)$ghe_row['id'];

        // Insert ticket securely
        mysqli_stmt_bind_param($insert_ve_stmt, "iii", $user_id, $ghe_id, $suat_chieu_id);
        if (!mysqli_stmt_execute($insert_ve_stmt)) {
            // If any insert fails, rollback and fail
            throw new Exception("Không thể đặt ghế '{$ten_ghe}'. Có thể ghế đã được người khác đặt.");
        }
    }

    // If all insertions were successful, commit the transaction
    mysqli_commit($conn);

    // Close prepared statements
    mysqli_stmt_close($get_ghe_id_stmt);
    mysqli_stmt_close($insert_ve_stmt);

} catch (Exception $e) {
    // An error occurred, rollback all changes
    mysqli_rollback($conn);
    die("Đã xảy ra lỗi trong quá trình đặt vé: " . $e->getMessage());
}

// --- Fetch show information for confirmation message (already safe) ---
$info = null;
$sql = "SELECT p.ten_phim, sc.ngay, sc.gio, sc.gia
        FROM suat_chieu sc
        JOIN phim p ON sc.phim_id = p.id
        WHERE sc.id = $suat_chieu_id";
$r = mysqli_query($conn, $sql);
if ($r) {
    $info = mysqli_fetch_assoc($r);
}

// Formatted values for template
$seat_list = htmlspecialchars(implode(", ", $ghe_array));
$seat_count = count($ghe_array);
// price per seat comes from the show info; default to 0 if missing
$price_per_seat = (isset($info['gia']) && $info['gia'] !== null) ? (int)$info['gia'] : 0;
$total_amount = $seat_count * $price_per_seat;

$movie_name = $info['ten_phim'] ?? '';
$show_datetime = '';
if ($info) {
    $show_datetime = date('d/m/Y', strtotime($info['ngay'])) . ' ' . date('H:i', strtotime($info['gio']));
}

mysqli_close($conn);
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
  /* invoice style */
  .invoice-card {
    background: #fff;
    color: #333;
    padding: 20px;
    border-radius: 8px;
    max-width: 480px;
    margin: 40px auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    text-align: center; /* Center content */
  }

  .invoice-card h2 {
    margin-top: 0;
    color: #e71a0f;
  }

  .invoice-card p {
    background-color: #333; /* Dark background */
    color: #fff;
    padding: 12px;
    border-radius: 6px;
    margin: 10px 0;
  }

  .invoice-card p strong {
      color: #ffc107; /* Gold color for labels */
  }

  .invoice-actions {
      margin-top: 25px;
  }

  .invoice-card .btn {
    margin: 0 5px;
  }

  /* Confetti particles */
  .confetti {
    position: fixed;
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
        <div class="logo">TTVH</div>
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
    <div class="invoice-card">
        <h2>🧾 Hóa đơn đặt vé</h2>
        <p><strong>Phim:</strong> <?= htmlspecialchars($movie_name) ?></p>
        <?php if ($show_datetime): ?>
            <p><strong>Thời gian:</strong> <?= htmlspecialchars($show_datetime) ?></p>
        <?php endif; ?>
        <p><strong>Ghế:</strong> <?= $seat_list ?> (<?= $seat_count ?> ghế)</p>
        <?php if (!empty(
            $payment_method
        )): ?>
            <p><strong>Phương thức:</strong> <?= htmlspecialchars($payment_method) ?></p>
        <?php endif; ?>
        <p><strong>Giá vé:</strong> <?= number_format($price_per_seat,0,',','.') ?>₫ / ghế</p>
        <p><strong>Tổng thanh toán:</strong> <?= number_format($total_amount,0,',','.') ?>₫</p>
        <div class="invoice-actions">
            <button onclick="window.print()" class="btn btn-secondary">🖨️ In hóa đơn</button>
            <a href="index.php" class="btn btn-primary">🏠 VỀ TRANG CHÍNH</a>
        </div>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
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