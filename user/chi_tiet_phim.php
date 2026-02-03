<?php
include "../config/db.php";

$id = $_GET['id'] ?? 0;
$sql = "SELECT * FROM phim WHERE id = $id";
$result = mysqli_query($conn, $sql);
$phim = mysqli_fetch_assoc($result);

if (!$phim) {
    die("Không tìm thấy phim");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title><?= $phim['ten_phim'] ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="movie-detail">

    <div class="poster">
        <img src="../assets/images/<?= $phim['poster'] ?>" alt="<?= $phim['ten_phim'] ?>">
    </div>

    <div class="info">
        <h1><?= $phim['ten_phim'] ?></h1>

        <p><strong>Thể loại:</strong> <?= $phim['the_loai'] ?></p>
        <p><strong>Thời lượng:</strong> <?= $phim['thoi_luong'] ?> phút</p>

        <div class="desc">
    <h3>Nội dung phim</h3>

    <?php if (!empty($phim['mo_ta'])): ?>
        <p><?= nl2br($phim['mo_ta']) ?></p>
    <?php else: ?>
        <p><i>Phim đang cập nhật nội dung...</i></p>
    <?php endif; ?>
</div>

        <a href="chon_suat.php?phim_id=<?= $phim['id'] ?>" class="buy-btn">
            🎟 MUA VÉ
        </a>
    </div>

</div>

</body>

</html>
