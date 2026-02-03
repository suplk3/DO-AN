<?php
include "../config/db.php";
$phim = mysqli_query($conn, "SELECT * FROM phim");
?>

<h2>PHIM ĐANG CHIẾU</h2>
<?php while($p = mysqli_fetch_assoc($phim)) { ?>
    <div>
        <img src="<?= $p['poster'] ?>" width="120">
        <h3><?= $p['ten_phim'] ?></h3>
        <a href="phim.php?id=<?= $p['id'] ?>">Đặt vé</a>
    </div>
<?php } ?>
