<?php
session_start();
include "../config/db.php";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CGV Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
    <link rel="stylesheet" href="../assets/css/login-modal.css">
</head>
<body class="user-index">

<header class="header">
    <div class="header-inner">
        <a href="index.php" class="logo">CGV</a>

        <nav class="header-nav">
            <div class="header-nav-left">
                <a href="index.php" class="nav-link active">🎬 PHIM</a>
                <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin'): ?>
                    <a href="../admin/phim.php" class="nav-link admin">⚙️ QUẢN LÝ PHIM</a>
                    <a href="../admin/suat_chieu.php" class="nav-link admin">⚙️ QUẢN LÝ SUẤT CHIẾU</a>
                <?php endif; ?>
            </div>

            <div class="header-nav-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="hello">👋 Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? 'bạn') ?></span>
                    <a href="../user/ve_cua_toi.php" class="btn btn-sm">VÉ CỦA TÔI</a>
                    <a href="../auth/logout.php" class="btn btn-sm btn-outline"
                       onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
                        ĐĂNG XUẤT
                    </a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-sm open-login-modal">🔐 ĐĂNG NHẬP</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="container">
    <h1 class="page-title">PHIM ĐANG CHIẾU</h1>

    <div class="movies">
    <?php
    $sql = "SELECT * FROM phim ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)):
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
                    <span class="duration"><?= htmlspecialchars($row['thoi_gian'] ?? '') ?></span>
                </div>

                <div class="card-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="chon_suat.php?phim_id=<?= $id ?>" class="btn btn-primary">ĐẶT VÉ</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn btn-outline open-login-modal">ĐĂNG NHẬP ĐỂ ĐẶT VÉ</a>
                    <?php endif; ?>
                    <a href="chi_tiet_phim.php?id=<?= $id ?>" class="btn btn-sm">CHI TIẾT</a>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> CGV Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<!-- Header shrink script -->
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
