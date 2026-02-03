<?php
include "../config/db.php";
$id = $_GET['id'];
$suat = mysqli_query($conn,
    "SELECT * FROM suat_chieu WHERE phim_id=$id");
?>

<h2>Chọn suất chiếu</h2>
<?php while($s = mysqli_fetch_assoc($suat)) { ?>
    <a href="chon_ghe.php?id=<?= $s['id'] ?>">
        <?= $s['ngay'] ?> - <?= $s['gio'] ?>
    </a><br>
<?php } ?>
