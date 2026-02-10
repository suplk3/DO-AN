<?php
include "../config/db.php";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$suat = mysqli_query($conn, "SELECT * FROM suat_chieu WHERE phim_id=$id");
?>

<div class="phim-page">
    <div class="phim-card">
        <div class="phim-header">
            <a href="index.php" class="back">← Quay lại</a>
            <h2>Chọn suất chiếu</h2>
        </div>

        <div class="suat-list">
            <?php while($s = mysqli_fetch_assoc($suat)) { ?>
                <a href="chon_ghe.php?id=<?= $s['id'] ?>" class="suat-item">
                    <span class="suat-date"><?= htmlspecialchars($s['ngay']) ?></span>
                    <span class="suat-time"><?= htmlspecialchars($s['gio']) ?></span>
                </a>
            <?php } ?>
            <?php if(mysqli_num_rows($suat) == 0) { ?>
                <div style="color:rgba(255,255,255,0.7);padding:12px">Không có suất chiếu.</div>
            <?php } ?>
        </div>
    </div>
</div>
