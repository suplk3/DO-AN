<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Lấy danh sách phim
$phim_query = mysqli_query($conn, "SELECT id FROM phim");
$phim_ids = [];
while ($row = mysqli_fetch_assoc($phim_query)) {
    $phim_ids[] = $row['id'];
}

// Lấy danh sách phòng
$phong_query = mysqli_query($conn, "SELECT id FROM phong_chieu");
$phong_ids = [];
while ($row = mysqli_fetch_assoc($phong_query)) {
    $phong_ids[] = $row['id'];
}

if (empty($phim_ids) || empty($phong_ids)) {
    die("Không có phim hoặc phòng chiếu nào trong DB.");
}

$start_date = new DateTime(); // Hiện tại
$end_date = new DateTime('2026-04-18');
$interval = new DateInterval('P1D');
$daterange = new DatePeriod($start_date, $interval, $end_date->modify('+1 day')); // Bao gồm cả ngày 18

$count = 0;

foreach ($daterange as $date) {
    $ngay = $date->format('Y-m-d');
    
    foreach ($phim_ids as $phim_id) {
        // Tạo 2 suất chiếu mỗi ngày cho mỗi phim: 14:00 và 19:00
        $gio_list = ['14:00:00', '19:00:00'];
        
        foreach ($gio_list as $gio) {
            // Chọn ngẫu nhiên 1 phòng
            $phong_id = $phong_ids[array_rand($phong_ids)];
            $gia = 75000;
            
            // Kiểm tra xem suất chiếu này đã tồn tại chưa
            $check = mysqli_query($conn, "SELECT id FROM suat_chieu WHERE phim_id=$phim_id AND phong_id=$phong_id AND ngay='$ngay' AND gio='$gio'");
            
            if (mysqli_num_rows($check) == 0) {
                $sql = "INSERT INTO suat_chieu (phim_id, phong_id, ngay, gio, gia) VALUES ($phim_id, $phong_id, '$ngay', '$gio', $gia)";
                if (mysqli_query($conn, $sql)) {
                    $count++;
                }
            }
        }
    }
}

echo "<h1>Đã thêm thành công $count suất chiếu mới từ hôm nay đến 18/04/2026!</h1>";
echo "<p>Bạn có thể đóng trang này và <a href='user/index.php'>quay lại trang chủ</a>.</p>";
?>
