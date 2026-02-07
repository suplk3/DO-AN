<?php
session_start();
include "../config/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM phim WHERE id = $id";
$result = mysqli_query($conn, $sql);
$phim = mysqli_fetch_assoc($result);

if (!$phim) {
    http_response_code(404);
    echo "Không tìm thấy phim";
    exit;
}

/* lấy suất chiếu kèm phòng + rạp */
$suat_sql = "SELECT s.*, pc.ten_phong, r.ten_rap
             FROM suat_chieu s
             LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
             LEFT JOIN rap r ON pc.rap_id = r.id
             WHERE s.phim_id = $id
             ORDER BY s.ngay, s.gio";
$suat_result = mysqli_query($conn, $suat_sql);
$suats = [];
while ($r = mysqli_fetch_assoc($suat_result)) $suats[] = $r;

// Lấy id của suất chiếu đầu tiên (nếu có) để link "Đặt vé" nhanh
$first_suat_id = null;
if (count($suats) > 0) {
    $first_suat_id = (int)$suats[0]['id'];
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
<title><?= htmlspecialchars($phim['ten_phim']) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/movie-detail.css">
</head>
<body class="movie-detail-page">

<header class="header">
    <div class="header-inner">
        <div class="logo">CGV</div>

        <nav class="menu">
            <a href="index.php" class="nav-link">🎬 PHIM</a>

            <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
                <a href="../admin/phim.php" class="nav-link admin">⚙️ QUẢN LÝ PHIM</a>
                <a href="../admin/suat_chieu.php" class="nav-link admin">⚙️ QUẢN LÝ SUẤT CHIẾU</a>
            <?php endif; ?>
        </nav>

        <div class="actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="hello">👋 <?= htmlspecialchars($_SESSION['ten'] ?? 'Khách') ?></span>
                <a href="#" class="link">🎟️ VÉ CỦA TÔI</a>
                <a href="../auth/logout.php" class="btn-ghost"
                   onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">🚪 ĐĂNG XUẤT</a>
            <?php else: ?>
                <a href="../auth/login.php" class="btn">🔐 ĐĂNG NHẬP</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="md-container">
    <a class="back" href="index.php">← Quay lại</a>

    <section class="md-card">
        <div class="md-poster">
            <img src="../assets/images/<?= htmlspecialchars($phim['poster']) ?>"
                 alt="<?= htmlspecialchars($phim['ten_phim']) ?>" loading="lazy">
        </div>

        <div class="md-info">
            <h1 class="md-title"><?= htmlspecialchars($phim['ten_phim']) ?></h1>

            <div class="md-meta">
                <span class="chip"><?= htmlspecialchars($phim['the_loai'] ?: 'Khác') ?></span>
                <span class="chip"><?= htmlspecialchars($phim['thoi_luong'] ? ($phim['thoi_luong'] . ' phút') : '') ?></span>
            </div>

            <p class="md-short">
                <?= nl2br(htmlspecialchars(mb_strimwidth($phim['mo_ta'] ?? '', 0, 400, '...'))) ?>
            </p>

            <div class="md-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="chon_suat.php?phim_id=<?= (int)$phim['id'] ?>" class="btn-primary">🎟 Đặt vé</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-outline">🔐 Đăng nhập để đặt vé</a>
                <?php endif; ?>
                <a href="#showtimes" class="btn-sm">📅 Xem suất chiếu</a>
            </div>

            <div class="md-extra">
                <h3>Nội dung chi tiết</h3>
                <?php if (!empty($phim['mo_ta'])): ?>
                    <p><?= nl2br(htmlspecialchars($phim['mo_ta'])) ?></p>
                <?php else: ?>
                    <p><i>Thông tin nội dung đang được cập nhật.</i></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="showtimes" class="showtimes-block">
        <h2 class="section-title">Suất chiếu</h2>

        <?php if (count($suats) === 0): ?>
            <p class="muted">Hiện chưa có suất chiếu cho phim này.</p>
        <?php else: ?>
            <div class="showtimes-grid">
                <?php foreach ($suats as $s):
                    $suat_id = (int)$s['id'];
                    $phong_id = (int)$s['phong_id'];
                    /* tổng ghế trong phòng (nếu có phòng) */
                    $total_seats = null;
                    if ($phong_id > 0) {
                        $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ghe WHERE phong_id = $phong_id");
                        $tot = mysqli_fetch_assoc($q);
                        $total_seats = (int)$tot['total'];
                    }
                    /* ghế đã đặt cho suất */
                    $bq = mysqli_query($conn, "SELECT COUNT(*) AS booked FROM ve WHERE suat_chieu_id = $suat_id");
                    $bk = mysqli_fetch_assoc($bq);
                    $booked = (int)$bk['booked'];
                    $available = is_null($total_seats) ? null : max(0, $total_seats - $booked);
                ?>
                    <article class="showtime-card">
                        <div class="st-top">
                            <div class="st-datetime">
                                <div class="st-date"><?= fmt_date($s['ngay']) ?></div>
                                <div class="st-time"><?= fmt_time($s['gio']) ?></div>
                            </div>
                            <div class="st-price"><?= fmt_money($s['gia']) ?></div>
                        </div>

                        <div class="st-body">
                            <div class="st-room"><?= htmlspecialchars($s['ten_rap'] ?: 'Rạp chưa đặt') ?> — <?= htmlspecialchars($s['ten_phong'] ?: 'Phòng') ?></div>
                            <div class="st-seats">
                                <?php if (is_null($total_seats)): ?>
                                    <span class="badge">Ghế: Không xác định</span>
                                <?php else: ?>
                                    <span class="badge available"><?= $available ?> / <?= $total_seats ?> trống</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="st-actions">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="chon_ghe.php?suat_id=<?= $suat_id ?>" class="btn-primary">Đặt ngay</a>
                            <?php else: ?>
                                <a href="../auth/login.php" class="btn-outline">Đăng nhập để đặt</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> CGV Cinemas — Thiết kế gọn, responsive.</div>
</footer>

</body>
</html>
