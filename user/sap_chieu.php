<?php
session_start();
include "../config/db.php";

// Lấy ngày hôm nay
$today = date('Y-m-d');

// Phim sắp chiếu: có ngày khởi chiếu > hôm nay
$sql = "SELECT * FROM phim WHERE ngay_khoi_chieu > '$today' ORDER BY ngay_khoi_chieu ASC";
$result = mysqli_query($conn, $sql);

function fmt_date($d) {
    return $d ? date('d/m/Y', strtotime($d)) : '';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Phim Sắp Chiếu - TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
</head>
<body class="user-index">

<header class="header">
    <div class="header-inner">
        <a href="index.php" class="logo">TTVH</a>

        <nav class="header-nav">
            <div class="header-nav-left">
                <a href="index.php" class="nav-link">
                    <span class="icon">🎬</span>
                    <span class="text">PHIM</span>
                </a>
                <a href="sap_chieu.php" class="nav-link active">
                    <span class="icon">📅</span>
                    <span class="text">SẮP CHIẾU</span>
                </a>
                <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
                    <a href="../admin/phim.php" class="nav-link admin">
                        <span class="icon">🎬</span>
                        <span class="text">QUẢN LÝ PHIM</span>
                    </a>
                    <a href="../admin/suat_chieu.php" class="nav-link admin">
                        <span class="icon">📅</span>
                        <span class="text">QUẢN LÝ SUẤT CHIẾU</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="header-nav-right">
                <?php if (isset($_SESSION['user_id'])):
                    $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');
                    $ticket_label = $is_admin ? 'QUẢN LÝ USER' : 'VÉ CỦA TÔI';
                ?>
                    <span class="hello">
                        <span class="icon">👋</span>
                        <span class="text">Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? 'bạn') ?></span>
                    </span>
                    <a href="ve_cua_toi.php" class="btn btn-sm">
                        <span class="icon">🎟️</span>
                        <span class="text"><?= $ticket_label ?></span>
                    </a>
                    <a href="../auth/logout.php" class="btn btn-sm btn-outline"
                       onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
                        <span class="icon">🚪</span>
                        <span class="text">ĐĂNG XUẤT</span>
                    </a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-sm open-login-modal">
                        <span class="icon">🔐</span>
                        <span class="text">ĐĂNG NHẬP</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="container">
    <h1 class="page-title">PHIM SẮP CHIẾU</h1>

    <div class="movies">
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="no-movies">
            <p>Hiện chưa có phim sắp chiếu.</p>
        </div>
    <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($result)): 
            $id = (int)$row['id'];
        ?>
        <article class="movie-card">
            <a href="chi_tiet_phim.php?id=<?= $id ?>" class="poster-link">
                <img class="poster" src="../assets/images/<?= htmlspecialchars($row['poster']) ?>"
                     alt="<?= htmlspecialchars($row['ten_phim']) ?>" loading="lazy">
            </a>

            <div class="card-body">
                <h3 class="movie-title" title="<?= htmlspecialchars($row['ten_phim']) ?>">
                    <?= htmlspecialchars($row['ten_phim']) ?>
                </h3>

                <div class="card-meta">
                    <span class="genre"><?= htmlspecialchars($row['the_loai'] ?? '') ?></span>
                    <span class="duration"><?= htmlspecialchars($row['thoi_luong'] ?? '') ?></span>
                </div>

                <div class="release-date">
                    <span class="label">Khởi chiếu:</span>
                    <span class="date"><?= fmt_date($row['ngay_khoi_chieu']) ?></span>
                </div>

                <div class="card-actions">
                    <a href="chi_tiet_phim.php?id=<?= $id ?>" class="btn btn-sm">CHI TIẾT</a>
                </div>
            </div>
        </article>
        <?php endwhile; ?>
    <?php endif; ?>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script>
(function(){
    const header = document.querySelector('.header');
    const body = document.querySelector('body.user-index');
    if(!header || !body) return;
    const onScroll = () => {
        if (window.scrollY > 50) {
            header.classList.add('shrink');
            body.classList.add('header-shrink');
        } else {
            header.classList.remove('shrink');
            body.classList.remove('header-shrink');
        }
    };
    window.addEventListener('scroll', onScroll, {passive:true});
    onScroll();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/login-modal.js"></script>

</body>
</html>

