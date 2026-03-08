<?php
include '../config/db.php';

$suat_chieu_id = $_GET['suat_id'] ?? 0;

if ($suat_chieu_id == 0) {
    die("Thiếu suất chiếu");
}

$sql = "
SELECT 
    ghe.id,
    ghe.ten_ghe,
    EXISTS (
        SELECT 1 
        FROM ve 
        WHERE ve.ghe_id = ghe.id 
        AND ve.suat_chieu_id = $suat_chieu_id
    ) AS da_dat
FROM ghe
WHERE ghe.phong_id = (SELECT phong_id FROM suat_chieu WHERE id = $suat_chieu_id LIMIT 1)
ORDER BY ghe.ten_ghe
";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi SQL: " . mysqli_error($conn));
}

// Lấy thông tin phim và suất chiếu
$info_sql = "
SELECT 
    p.id AS phim_id, p.ten_phim, p.poster, p.the_loai, p.thoi_luong, p.mo_ta,
    s.ngay, s.gio, s.gia,
    pc.ten_phong, r.ten_rap
FROM suat_chieu s
LEFT JOIN phim p ON s.phim_id = p.id
LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
LEFT JOIN rap r ON pc.rap_id = r.id
WHERE s.id = $suat_chieu_id
";
$info_result = mysqli_query($conn, $info_sql);
$info = mysqli_fetch_assoc($info_result);

if (!$info) {
    die("Không tìm thấy suất chiếu");
}

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chọn ghế - <?= htmlspecialchars($info['ten_phim']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/movie-detail.css">
    <style>
    /* ensure seat labels are always white for visibility */
    .seat-selection .seat {
        color: #fff !important;
    }
    .seat-selection .seat.booked {
        color: #fff !important;
    }
    .seat-selection .seat:hover {
        color: #fff !important;
    }
    </style>
</head>
<body class="movie-detail-page">

<header class="header">
    <div class="header-inner">
        <div class="logo">CGV</div>
        <nav class="menu">
            <a href="index.php" class="nav-link">🎬 PHIM</a>
        </nav>
        <div class="actions">
            <a href="ve_cua_toi.php" class="link">🎟️ VÉ CỦA TÔI</a>
        </div>
    </div>
</header>

<main class="md-container">
    <a class="back" href="chi_tiet_phim.php?id=<?= $info['phim_id'] ?? 0 ?>">← Quay lại chi tiết phim</a>

    <section class="seat-selection">
        <h1 class="section-title">Chọn ghế</h1>
        <div class="showtime-info">
            <h2><?= htmlspecialchars($info['ten_phim']) ?></h2>
            <p><strong>Ngày:</strong> <?= fmt_date($info['ngay']) ?> | <strong>Giờ:</strong> <?= fmt_time($info['gio']) ?> | <strong>Giá:</strong> <?= fmt_money($info['gia']) ?></p>
            <p><strong>Rạp:</strong> <?= htmlspecialchars($info['ten_rap']) ?> | <strong>Phòng:</strong> <?= htmlspecialchars($info['ten_phong']) ?></p>
        </div>

        <div class="screen">MÀN HÌNH</div>

        <div class="seat-wrapper">
<?php
$currentRow = '';
while ($row = mysqli_fetch_assoc($result)) {
    $rowChar = substr($row['ten_ghe'], 0, 1);

    if ($currentRow != $rowChar) {
        if ($currentRow != '') echo '</div>';
        echo "<div class='seat-row'>";
        $currentRow = $rowChar;
    }

    $class = $row['da_dat'] ? 'seat booked' : 'seat';

    echo "<button 
            class='$class' 
            data-seat='{$row['ten_ghe']}'
            ".($row['da_dat'] ? 'disabled' : '').">
            {$row['ten_ghe']}
          </button>";
}

if ($currentRow != '') echo '</div>';
?>
        </div>

        <div class="checkout">
            <p>Ghế đã chọn: <strong id="selected-seats"></strong></p>
            <p>Tổng tiền: <strong id="total">0 đ</strong></p>

            <form action="dat_ve.php" method="POST">
                <input type="hidden" name="ghe" id="seat-input">
                <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">

                <button type="submit" class="btn-primary">TIẾP TỤC THANH TOÁN</button>
            </form>
        </div>
    </section>

    <section class="movie-info-section">
        <h2 class="section-title">Thông tin phim</h2>
        <div class="movie-info-card">
            <div class="movie-poster">
                <img src="../assets/images/<?= htmlspecialchars($info['poster']) ?>" alt="<?= htmlspecialchars($info['ten_phim']) ?>" loading="lazy">
            </div>
            <div class="movie-details">
                <h3><?= htmlspecialchars($info['ten_phim']) ?></h3>
                <div class="movie-meta">
                    <span class="chip"><?= htmlspecialchars($info['the_loai'] ?: 'Khác') ?></span>
                    <span class="chip"><?= htmlspecialchars($info['thoi_luong'] ? ($info['thoi_luong'] . ' phút') : '') ?></span>
                </div>
                <p class="movie-description">
                    <?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'] ?? '', 0, 300, '...'))) ?>
                </p>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> CGV Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script src="../assets/js/ghe.js"></script>

</body>
</html>