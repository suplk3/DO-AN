<?php
session_start();
include "../config/db.php";
$force_show_login = (isset($_GET['show_login']) && !isset($_SESSION['user_id']));
$notif_unread = 0;
if (isset($_SESSION['user_id'])) {
    if (table_exists($conn, 'notifications')) {
        $uid = (int)$_SESSION['user_id'];
        $nr = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0"
        ));
        $notif_unread = (int)($nr['c'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
    <link rel="stylesheet" href="../assets/css/login-modal.css">
    <link rel="stylesheet" href="../assets/css/search.css">
    <link rel="stylesheet" href="../assets/css/user-menu.css">
    <link rel="stylesheet" href="../assets/css/theme-toggle.css">
    
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="user-index">

<?php $active_page = 'phim'; include 'components/header.php'; ?>
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

<!-- Main scripts: header shrink + movie card stagger animation -->
<script>
(function () {

    /* ── 1. Header shrink khi scroll ── */
    const header = document.querySelector('.header');
    const body   = document.querySelector('body.user-index');
    if (header && body) {
        const onScroll = () => {
            const past = window.scrollY > 50;
            header.classList.toggle('shrink', past);
            body.classList.toggle('header-shrink', past);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ── 2. Movie card stagger fade-up ── */
    const cards = Array.from(document.querySelectorAll('.movie-card'));
    if (!cards.length) return;

    // Thời gian delay giữa mỗi card (ms)
    const STAGGER = 90;
    // Card nào nằm trong viewport ngay khi load → kích hoạt ngay
    // Card nào ngoài viewport → chờ IntersectionObserver khi scroll tới

    // Theo dõi batch index cho scroll reveal (reset mỗi lần scroll)
    let batchCounter = 0;
    let batchTimer   = null;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            const card = entry.target;

            // Với cards trong viewport lúc load: dùng index toàn cục để stagger đẹp
            // Với cards scroll reveal: dùng batch counter (reset sau 300ms không có card mới)
            const isInitial = card.dataset.initial === '1';
            const delay = isInitial
                ? Math.min(parseInt(card.dataset.cardIndex, 10) * STAGGER, 400)
                : batchCounter * 60; // scroll reveal: stagger nhẹ 60ms

            if (!isInitial) {
                batchCounter++;
                clearTimeout(batchTimer);
                batchTimer = setTimeout(() => { batchCounter = 0; }, 300);
            }

            setTimeout(() => card.classList.add('is-visible'), delay);
            observer.unobserve(card);
        });
    }, {
        threshold: 0,
        rootMargin: '0px 0px 300px 0px'  // trigger trước 300px — load sớm trước khi nhìn thấy
    });

    // Phân biệt cards trong viewport lúc đầu vs cards ngoài viewport
    cards.forEach((card, i) => {
        card.dataset.cardIndex = i;
        const rect = card.getBoundingClientRect();
        if (rect.top < window.innerHeight + 300) {
            card.dataset.initial = '1'; // trong viewport hoặc gần viewport lúc load
        }
        observer.observe(card);
    });

})();
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/login-modal.js"></script>
<?php if ($force_show_login): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const trigger = document.querySelector('.open-login-modal');
    if (trigger) trigger.click();
});
</script>
<?php endif; ?>

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

<script>
// User dropdown toggle
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', e => {
        e.stopPropagation();
        userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown.classList.remove('open'));
}
</script>
<script>
// Theme toggle
(function(){
  var body = document.body;
  var btn = document.getElementById('themeToggle');
  var stored = localStorage.getItem('theme') || 'dark';
  body.setAttribute('data-theme', stored);
  if (!btn) return;
  btn.addEventListener('click', function(){
    var cur = body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', cur);
    localStorage.setItem('theme', cur);
  });
})();
</script>
</body>
</html>
