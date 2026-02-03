<?php
include "ketnoi.php";

$phim_id    = $_POST['phim_id'];
$suat_chieu = $_POST['suat_chieu'];
$ghe        = $_POST['ghe'];
$hoten      = $_POST['hoten'];
$sdt        = $_POST['sdt'];

$sql = "INSERT INTO ve(phim_id, suat_chieu, ghe, hoten, sdt)
        VALUES ($phim_id, '$suat_chieu', '$ghe', '$hoten', '$sdt')";

mysqli_query($conn, $sql);

echo "<h2>🎉 Đặt vé thành công!</h2>";
echo "<a href='index.php'>Quay về trang chủ</a>";
