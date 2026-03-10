<?php
session_start();
include "../config/db.php";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TTVH Cinemas</title>
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
                <a href="index.php" class="nav-link active">
                    <span class="icon">🎬</span>
                    <span class="text">PHIM</span>
                </a>
                <a href="sap_chieu.php" class="nav-link">
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="hello">
                        <span class="icon">👋</span>
                        <span class="text">Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? 'bạn') ?></span>
                    </span>
                    <a href="../user/ve_cua_toi.php" class="btn btn-sm">
                        <span class="icon">🎟️</span>
                        <span class="text">VÉ CỦA TÔI</span>
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
    <!-- Banner Carousel -->
    <?php
    // Giới hạn hiển thị tối đa 5 banner
    $banner_sql = "SELECT * FROM phim WHERE banner IS NOT NULL AND banner != '' ORDER BY id DESC LIMIT 5";
    $banner_result = mysqli_query($conn, $banner_sql);
    $banners = [];
    while ($b = mysqli_fetch_assoc($banner_result)) {
        $banners[] = $b;
    }
    ?>
    <?php if (count($banners) > 0): ?>
    <div class="banner-carousel">
        <div class="banner-slides">
            <?php foreach ($banners as $index => $banner): ?>
            <div class="banner-slide<?= $index === 0 ? ' active' : '' ?>">
                <a href="chi_tiet_phim.php?id=<?= (int)$banner['id'] ?>">
                    <img src="../assets/images/<?= htmlspecialchars($banner['banner']) ?>" 
                         alt="<?= htmlspecialchars($banner['ten_phim']) ?>">
                    <div class="banner-overlay">
                        <h2 class="banner-title"><?= htmlspecialchars($banner['ten_phim']) ?></h2>
                        <span class="banner-cta">Xem chi tiết</span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($banners) > 1): ?>
        <button class="banner-nav banner-prev">&#10094;</button>
        <button class="banner-nav banner-next">&#10095;</button>
        <div class="banner-dots">
            <?php for ($i = 0; $i < count($banners); $i++): ?>
            <span class="banner-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>"></span>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <h1 class="page-title">PHIM ĐANG CHIẾU</h1>

    <div class="movies">
    <?php
    // Chỉ hiển thị phim đang chiếu: ngày khởi chiếu <= hôm nay hoặc chưa có
    $today = date('Y-m-d');
    $sql = "SELECT * FROM phim WHERE (ngay_khoi_chieu IS NULL OR ngay_khoi_chieu <= '$today') ORDER BY id DESC";
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
    <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
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

<!-- Banner Carousel Script -->
<script>
(function() {
    const carousel = document.querySelector('.banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.banner-slide');
    const dots = carousel.querySelectorAll('.banner-dot');
    const prevBtn = carousel.querySelector('.banner-prev');
    const nextBtn = carousel.querySelector('.banner-next');
    
    if (slides.length <= 1) return;
    
    let currentIndex = 0;
    let autoPlayInterval;
    
    function showSlide(index) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        
        slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
    }
    
    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        showSlide(currentIndex);
    }
    
    function prevSlide() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(currentIndex);
    }
    
    function startAutoPlay() {
        autoPlayInterval = setInterval(nextSlide, 5000);
    }
    
    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }
    
    if (nextBtn) nextBtn.addEventListener('click', () => {
        nextSlide();
        stopAutoPlay();
        startAutoPlay();
    });
    
    if (prevBtn) prevBtn.addEventListener('click', () => {
        prevSlide();
        stopAutoPlay();
        startAutoPlay();
    });
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentIndex = index;
            showSlide(currentIndex);
            stopAutoPlay();
            startAutoPlay();
        });
    });
    
    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);
    
    startAutoPlay();
})();
</script>

</body>
</html>
