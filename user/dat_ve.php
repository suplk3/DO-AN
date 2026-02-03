<?php
session_start();
include "../config/db.php";

$user_id = $_SESSION['user']['id'];
$suat_id = $_POST['suat_id'];
$ghe_id = $_POST['ghe_id'];

$check = mysqli_query($conn,
    "SELECT * FROM ve 
     WHERE suat_chieu_id=$suat_id AND ghe_id=$ghe_id");

if (mysqli_num_rows($check) > 0) {
    echo "❌ Ghế đã được đặt!";
    exit;
}

mysqli_query($conn,
    "INSERT INTO ve(user_id,suat_chieu_id,ghe_id)
     VALUES($user_id,$suat_id,$ghe_id)");

echo "🎉 Đặt vé thành công!";
