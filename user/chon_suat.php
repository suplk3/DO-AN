<?php
session_start();
include "../config/db.php";

$phim_id = $_GET['phim_id'] ?? 0;

if ($phim_id == 0) {
    die("Không tìm thấy phim");
}

// Lấy danh sách ngày chiếu
$sql_ngay = "
SELECT DISTINCT ngay 
FROM suat_chieu 
WHERE phim_id = $phim_id
";
$q_ngay = mysqli_query($conn, $sql_ngay);

$ngay_chon = $_GET['ngay'] ?? date('Y-m-d');

// Lấy suất chiếu theo ngày
$sql_suat = "
SELECT * 
FROM suat_chieu 
WHERE phim_id = $phim_id 
AND ngay = '$ngay_chon'
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
body {
    background: linear-gradient(135deg, #0a0e27, #121829);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
    color: rgba(255,255,255,0.9);
    margin: 0;
    padding: 0;
}

header {
    background: linear-gradient(90deg, var(--brand-dark), var(--brand-darker));
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

header .logo {
    font-size: 28px;
    font-weight: bold;
    color: var(--accent-red);
    letter-spacing: 2px;
}

header nav {
    display: flex;
    gap: 20px;
}

header nav a {
    color: rgba(255,255,255,0.92);
    text-decoration: none;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.2s;
}

header nav a:hover {
    background: rgba(229,9,20,0.2);
    color: #fff;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px 20px;
}

.page-title {
    font-size: 28px;
    color: var(--accent-red);
    margin-bottom: 8px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-title::before {
    content: '';
    width: 4px;
    height: 28px;
    background: var(--accent-red);
    border-radius: 2px;
}

.section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 18px;
    color: rgba(255,255,255,0.95);
    margin-bottom: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title::before {
    content: '';
    width: 3px;
    height: 18px;
    background: var(--accent-red);
    border-radius: 1px;
}

.date-list {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1);
}

.date-list a {
    padding: 10px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    text-decoration: none;
    color: rgba(255,255,255,0.8);
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.date-list a:hover {
    background: rgba(229,9,20,0.2);
    border-color: rgba(229,9,20,0.3);
    color: #fff;
    transform: translateY(-2px);
}

.date-list a.active {
    background: linear-gradient(135deg, var(--accent-red), #ff4444);
    border-color: var(--accent-red);
    color: #fff;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(229,9,20,0.3);
}

.time-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.time-btn {
    display: flex;
    flex-direction: column;
    padding: 16px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    text-align: center;
    cursor: pointer;
}

.time-btn:hover {
    background: linear-gradient(135deg, rgba(229,9,20,0.15), rgba(229,9,20,0.08));
    border-color: rgba(229,9,20,0.4);
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(229,9,20,0.2);
}

.time-btn .time {
    font-size: 18px;
    color: #fff;
    margin-bottom: 6px;
}

.time-btn .price {
    font-size: 14px;
    color: var(--accent-red);
    font-weight: 700;
}

.empty-message {
    text-align: center;
    padding: 40px;
    color: rgba(255,255,255,0.6);
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1);
}

.empty-message .icon {
    font-size: 40px;
    margin-bottom: 12px;
    display: block;
}

@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .time-list {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    header nav {
        width: 100%;
        justify-content: flex-end;
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
    <div class="page-title">Chọn suất chiếu</div>

    <div class="section">
        <div class="section-title">Chọn ngày</div>
        <div class="date-list">
            <?php 
            if (mysqli_num_rows($q_ngay) > 0) {
                while($d = mysqli_fetch_assoc($q_ngay)): ?>
                    <a href="?phim_id=<?= $phim_id ?>&ngay=<?= $d['ngay'] ?>"
                       class="<?= ($d['ngay'] == $ngay_chon) ? 'active' : '' ?>">
                       <?= date('d/m/Y', strtotime($d['ngay'])) ?>
                    </a>
                <?php endwhile;
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
