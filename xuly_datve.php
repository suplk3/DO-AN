<?php
include "ketnoi.php";

$ten_phim   = $_POST['ten_phim'];
$suat_chieu = $_POST['suat_chieu'];
$ghe        = $_POST['ghe'];
$hoten      = $_POST['hoten'];
$sdt        = $_POST['sdt'];

$sql = "INSERT INTO ve (ten_phim, suat_chieu, ghe, hoten, sdt)
        VALUES ('$ten_phim', '$suat_chieu', '$ghe', '$hoten', '$sdt')";

if (mysqli_query($conn, $sql)) {
    echo "<h2>🎉 Đặt vé thành công!</h2>";
    echo "<a href='index.php'>Quay về trang chủ</a>";
} else {
    echo "Lỗi: " . mysqli_error($conn);
}
?>
