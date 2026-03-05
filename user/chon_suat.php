<?php
session_start();
include "../config/db.php";

$phim_id = isset($_GET['phim_id']) ? intval($_GET['phim_id']) : 0;

if ($phim_id <= 0) {
    die("Không tìm thấy phim");
}

// Lấy danh sách ngày chiếu
$sql_ngay = "
SELECT DISTINCT ngay 
FROM suat_chieu 
WHERE phim_id = $phim_id
";
$q_ngay = mysqli_query($conn, $sql_ngay);

// chuyển các ngày vào mảng để dễ xử lý và xác định giá trị mặc định
$ngay_list = [];
if ($q_ngay) {
    while ($row = mysqli_fetch_assoc($q_ngay)) {
        $ngay_list[] = $row['ngay'];
    }
}

// chọn ngày hiện tại từ query string nếu hợp lệ, ngược lại lấy phần tử đầu của danh sách
$ngay_chon = null;
if (isset($_GET['ngay']) && in_array($_GET['ngay'], $ngay_list, true)) {
    $ngay_chon = $_GET['ngay'];
} elseif (!empty($ngay_list)) {
    $ngay_chon = $ngay_list[0];
} else {
    // không có ngày nào trong database -> sử dụng ngày hôm nay để hiển thị thông báo
    $ngay_chon = date('Y-m-d');
}

// Lấy suất chiếu theo ngày
// nếu danh sách ngày rỗng thì $ngay_chon đã được đặt thành ngày hiện tại ở phía trên
$ngay_esc = mysqli_real_escape_string($conn, $ngay_chon);
$sql_suat = "
SELECT * 
FROM suat_chieu 
WHERE phim_id = $phim_id 
AND ngay = '$ngay_esc'
";
$q_suat = mysqli_query($conn, $sql_suat);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chọn suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* layout */
body {
    background: #12131a;
    color: #e0e0e0;
}
.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
}
.section {
    background: #1f2028;
    border-radius: 8px;
    padding: 20px 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}
.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #fff;
}

/* date list */
.date-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.date-list a{
    padding:8px 15px;
    background:#2c2e38;
    text-decoration:none;
    color:#e0e0e0;
    border-radius:6px;
    transition:background 0.2s, color 0.2s;
}
.date-list a.active{
    background:#e71a0f;
    color:white;
}
.date-list a:hover{
    background:#3a3c48;
}

/* time buttons */
.time-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.time-btn{
    display:inline-block;
    margin:5px;
    padding:10px 20px;
    background:#e71a0f;
    color:#fff;
    text-decoration:none;
    font-weight:bold;
    border-radius:6px;
    transition:background 0.2s;
}
.time-btn:hover{
    background:#c4160e;
}

/* empty message card */
.empty-message{
    text-align:center;
    padding:30px 10px;
    color:#bbb;
}
.empty-message .icon{
    font-size:40px;
    margin-bottom:10px;
}

/* responsive tweaks */
@media (max-width:600px) {
    .date-list, .time-list {
        justify-content: center;
    }
    .time-btn, .date-list a {
        flex: 1 0 120px;
        text-align: center;
    }
}
</style>
</head>
<body>

<header>
    <div class="logo">🎬 CGV</div>
    <nav>
        <a href="index.php">PHIM</a>
        <a href="ve_cua_toi.php">VÉ CỦA TÔI</a>
        <a href="../auth/logout.php">ĐĂNG XUẤT</a>
    </nav>
</header>

<div class="container">
    <div class="title">CHỌN NGÀY</div>

    <div class="section">
        <div class="section-title">Chọn ngày</div>
        <div class="date-list">
            <?php 
            if (!empty($ngay_list)) {
                foreach ($ngay_list as $d): ?>
                    <a href="?phim_id=<?= $phim_id ?>&ngay=<?= $d ?>"
                       class="<?= ($d == $ngay_chon) ? 'active' : '' ?>">
                       <?= date('d/m/Y', strtotime($d)) ?>
                    </a>
                <?php endforeach;
            } else {
                echo '<div class="empty-message">Không có ngày chiếu nào</div>';
            }
            ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Chọn giờ chiếu</div>
        <div class="time-list">
            <?php 
            if (mysqli_num_rows($q_suat) > 0) {
                while($s = mysqli_fetch_assoc($q_suat)): ?>
                    <a class="time-btn" href="chon_ghe.php?suat_id=<?= $s['id'] ?>" title="Chọn ghế">
                        <span class="time"><?= $s['gio'] ?></span>
                        <span class="price"><?= number_format($s['gia']) ?>đ</span>
                    </a>
                <?php endwhile;
            } else {
                echo '<div class="empty-message" style="grid-column: 1 / -1; margin-top: -12px;">
                    <div class="icon">🎬</div>
                    <div>Không có suất chiếu nào trong ngày này</div>
                </div>';
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>
