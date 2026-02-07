<?php
session_start();
include "../config/db.php";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>CGV Cinemas</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="header">
    <div class="logo">CGV</div>

    <nav class="menu">
        <a href="index.php">🎬 PHIM</a>

        <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
            <a href="../admin/phim.php" class="admin-btn">⚙️ QUẢN LÝ PHIM</a>
             <a href="../admin/suat_chieu.php" class="admin-btn">⚙️ QUẢN LÝ SUẤT CHIẾU</a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
    <span class="hello">👋 Xin chào</span>
    <a href="#">🎟️ VÉ CỦA TÔI</a>
    <a href="../auth/logout.php"
   onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
   🚪 ĐĂNG XUẤT
</a>
<?php else: ?>
    <a href="../auth/login.php">🔐 ĐĂNG NHẬP</a>
<?php endif; ?>

    </nav>
</header>

<div class="container">
    <div class="title">PHIM ĐANG CHIẾU</div>

    <div class="movies">
<?php
$sql = "SELECT * FROM phim";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
?>
    <div class="movie">
        <a href="chi_tiet_phim.php?id=<?= $row['id'] ?>">
            <img src="../assets/images/<?= $row['poster'] ?>" alt="<?= $row['ten_phim'] ?>">
        </a>

        <h3><?= $row['ten_phim'] ?></h3>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="chon_suat.php?phim_id=<?= $row['id'] ?>" class="btn">
                ĐẶT VÉ
            </a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn btn-login">
                ĐĂNG NHẬP ĐỂ ĐẶT VÉ
            </a>
        <?php endif; ?>
    </div>
<?php } ?>
    </div>
</div>

</body>
</html>
