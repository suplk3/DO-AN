<?php
session_start();
include "../config/db.php";

$phim_id = $_GET['phim'] ?? 1;

// Lấy danh sách ngày chiếu
$sql_ngay = "SELECT DISTINCT ngay FROM suat_chieu WHERE phim_id = $phim_id";
$q_ngay = mysqli_query($conn, $sql_ngay);

$ngay_chon = $_GET['ngay'] ?? date('Y-m-d');

// Lấy suất chiếu theo ngày
$sql_suat = "SELECT * FROM suat_chieu 
             WHERE phim_id = $phim_id AND ngay = '$ngay_chon'";
$q_suat = mysqli_query($conn, $sql_suat);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chọn suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.date-list a{
    padding:8px 15px;
    margin-right:10px;
    background:#eee;
    text-decoration:none;
    color:black;
}
.date-list a.active{
    background:#e71a0f;
    color:white;
}
.time-btn{
    display:inline-block;
    margin:10px;
    padding:10px 20px;
    border:2px solid #e71a0f;
    color:#e71a0f;
    text-decoration:none;
    font-weight:bold;
}
.time-btn:hover{
    background:#e71a0f;
    color:white;
}
</style>
</head>
<body>

<header>
    <div class="logo">CGV</div>
    <nav>
        <a href="index.php">PHIM</a>
        <a href="../auth/logout.php">ĐĂNG XUẤT</a>
    </nav>
</header>

<div class="container">
    <div class="title">CHỌN NGÀY</div>

    <div class="date-list">
        <?php while($d = mysqli_fetch_assoc($q_ngay)): ?>
            <a href="?phim=<?= $phim_id ?>&ngay=<?= $d['ngay'] ?>"
               class="<?= ($d['ngay'] == $ngay_chon) ? 'active' : '' ?>">
               <?= date('d/m', strtotime($d['ngay'])) ?>
            </a>
        <?php endwhile; ?>
    </div>

    <div class="title">CHỌN GIỜ CHIẾU</div>

    <?php while($s = mysqli_fetch_assoc($q_suat)): ?>
        <a class="time-btn"
   href="chon_ghe.php?suat_id=<?= $s['id'] ?>">
   <?= $s['gio'] ?> - <?= number_format($s['gia']) ?>đ
</a>

    <?php endwhile; ?>
</div>

</body>
</html>
