<?php
include "../config/db.php";
session_start();

$user_id = $_SESSION['user_id'] ?? 1; // test
$suat_chieu_id = $_POST['suat_chieu_id'];
$ghe = explode(",", $_POST['ghe']);

foreach ($ghe as $ten_ghe) {
    $sql = "SELECT id FROM ghe WHERE ten_ghe='$ten_ghe'";
    $r = mysqli_query($conn, $sql);
    $ghe_id = mysqli_fetch_assoc($r)['id'];

    mysqli_query($conn, "
        INSERT INTO ve (user_id, ghe_id, suat_chieu_id)
        VALUES ($user_id, $ghe_id, $suat_chieu_id)
    ");
}

echo "<h2>🎉 Đặt vé thành công!</h2>";
