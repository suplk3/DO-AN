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

<header>
    <div class="logo">CGV</div>
    <nav>
        <a href="index.php">PHIM</a>

        <?php if(isset($_SESSION['user'])): ?>
            <a href="#">VÉ CỦA TÔI</a>
            <a href="../auth/logout.php">ĐĂNG XUẤT</a>
        <?php else: ?>
            <a href="../auth/login.php">ĐĂNG NHẬP</a>
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
        <img src="../assets/images/<?= $row['poster'] ?>">
        <h3><?= $row['ten_phim'] ?></h3>
        <a href="chon_suat.php?phim_id=<?= $row['id'] ?>" class="btn">
            ĐẶT VÉ
        </a>
    </div>
<?php } ?>
</div>

</div>

</body>
</html>
