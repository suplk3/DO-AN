<?php
session_start();
include "../config/db.php";
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

$today = date('Y-m-d');
$sql = "SELECT * FROM phim WHERE ngay_khoi_chieu > '$today' ORDER BY ngay_khoi_chieu ASC";
$result = mysqli_query($conn, $sql);

function fmt_date($d) {
    if (!$d) return '';
    $ts    = strtotime($d);
    $days  = (int)((strtotime($d) - strtotime(date('Y-m-d'))) / 86400);
    $label = $days === 1 ? 'Ngày mai' : ($days <= 7 ? "Còn $days ngày" : '');
    return [
        'display' => date('d/m/Y', $ts),
        'label'   => $label,
        'days'    => $days,
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Phim Sắp Chiếu — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<link rel="stylesheet" href="../assets/css/search.css">
<link rel="stylesheet" href="../assets/css/user-menu.css">
<link rel="stylesheet" href="../assets/css/theme-toggle.css">

<style>
/* ── Countdown badge ── */
.countdown-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    padding: 3px 9px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(245,185,66,0.18), rgba(245,185,66,0.08));
    border: 1px solid rgba(245,185,66,0.35);
    color: #f5b942;
}
.countdown-badge.soon {
    background: linear-gradient(135deg, rgba(232,54,42,0.2), rgba(232,54,42,0.08));
    border-color: rgba(232,54,42,0.4);
    color: #ff6b6b;
    animation: pulseBadge 1.6s ease-in-out infinite;
}
@keyframes pulseBadge {
    0%, 100% { box-shadow: 0 0 0 0 rgba(232,54,42,0.3); }
    50%       { box-shadow: 0 0 0 5px rgba(232,54,42,0); }
}

/* ── Release date row ── */
.release-date {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 13px;
}
.release-date .label {
    color: #64748b;
    font-size: 12px;
}
.release-date .date {
    font-weight: 700;
    color: #f5b942;
    background: rgba(245,185,66,0.1);
    border: 1px solid rgba(245,185,66,0.2);
    padding: 2px 9px;
    border-radius: 5px;
}

/* ── Upcoming ribbon ── */
.upcoming-ribbon {
    position: absolute;
    top: 14px;
    left: -1px;
    background: linear-gradient(135deg, #e8192c, #c01020);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    padding: 4px 12px 4px 10px;
    clip-path: polygon(0 0, 100% 0, 88% 100%, 0 100%);
    z-index: 3;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

/* ── Sort/filter bar ── */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.filter-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}
.filter-btn {
    padding: 7px 16px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    color: rgba(241,245,249,0.7);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s ease;
    font-family: inherit;
}
.filter-btn:hover, .filter-btn.active {
    background: linear-gradient(135deg, rgba(232,54,42,0.25), rgba(232,54,42,0.1));
    border-color: rgba(232,54,42,0.4);
    color: #fff;
}

/* ── Empty state ── */
.no-movies {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
}
.no-movies-icon {
    font-size: 56px;
    margin-bottom: 16px;
    opacity: 0.6;
}
.no-movies-text {
    font-size: 18px;
    font-weight: 700;
    color: #e2e8f0;
    margin-bottom: 8px;
}
.no-movies-sub {
    font-size: 14px;
    color: #64748b;
}
</style>
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="user-index">

<!-- ── Header ── -->
<?php $active_page = 'sap_chieu'; include 'components/header.php'; ?>
<!-- ── Main ── -->
<main class="container">

    <h1 class="page-title">🗓️ PHIM SẮP CHIẾU</h1>

    <?php
    // Đưa kết quả vào mảng để dùng lại
    $movies = [];
    while ($row = mysqli_fetch_assoc($result)) $movies[] = $row;
    ?>

    <?php if (empty($movies)): ?>
    <div class="movies">
        <div class="no-movies">
            <div class="no-movies-icon">🎞️</div>
            <div class="no-movies-text">Chưa có phim sắp chiếu</div>
            <div class="no-movies-sub">Hãy quay lại sau để xem lịch chiếu mới nhất nhé!</div>
        </div>
    </div>
    <?php else: ?>

    <div class="movies" id="movieGrid">
        <?php foreach ($movies as $row):
            $id   = (int)$row['id'];
            $info = fmt_date($row['ngay_khoi_chieu']);
            $soon = $info['days'] <= 7;
        ?>
        <article class="movie-card" data-days="<?= $info['days'] ?>">

            <!-- Ribbon sắp chiếu -->
            <div class="upcoming-ribbon">SẮP CHIẾU</div>

            <a href="chi_tiet_phim.php?id=<?= $id ?>" class="poster-link">
                <img class="poster"
                     src="../assets/images/<?= htmlspecialchars($row['poster']) ?>"
                     alt="<?= htmlspecialchars($row['ten_phim']) ?>"
                     loading="lazy">
            </a>

            <div class="card-body">
                <h3 class="movie-title" title="<?= htmlspecialchars($row['ten_phim']) ?>">
                    <?= htmlspecialchars($row['ten_phim']) ?>
                </h3>

                <div class="card-meta">
                    <?php if (!empty($row['the_loai'])): ?>
                    <span class="genre"><?= htmlspecialchars($row['the_loai']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($row['thoi_luong'])): ?>
                    <span class="duration">⏱ <?= htmlspecialchars($row['thoi_luong']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Ngày khởi chiếu + countdown -->
                <div class="release-date">
                    <span class="label">Khởi chiếu:</span>
                    <span class="date"><?= $info['display'] ?></span>
                    <?php if ($info['label']): ?>
                    <span class="countdown-badge<?= $soon ? ' soon' : '' ?>">
                        <?= $soon ? '🔥 ' : '⏳ ' ?><?= $info['label'] ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <a href="chi_tiet_phim.php?id=<?= $id ?>" class="btn btn-sm" style="flex:1; text-align:center;">
                        🎬 Xem chi tiết
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<!-- Scripts -->
<script>
(function () {
    /* ── Header shrink ── */
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

    /* ── Movie card stagger fade-up (giống index.php) ── */
    const cards = Array.from(document.querySelectorAll('.movie-card'));
    if (!cards.length) return;

    const STAGGER = 90;
    let batchCounter = 0;
    let batchTimer   = null;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            const card    = entry.target;
            const isInit  = card.dataset.initial === '1';
            const delay   = isInit
                ? Math.min(parseInt(card.dataset.cardIndex, 10) * STAGGER, 400)
                : batchCounter * 60;

            if (!isInit) {
                batchCounter++;
                clearTimeout(batchTimer);
                batchTimer = setTimeout(() => { batchCounter = 0; }, 300);
            }

            setTimeout(() => card.classList.add('is-visible'), delay);
            observer.unobserve(card);
        });
    }, {
        threshold: 0,
        rootMargin: '0px 0px 300px 0px'
    });

    cards.forEach((card, i) => {
        card.dataset.cardIndex = i;
        const rect = card.getBoundingClientRect();
        if (rect.top < window.innerHeight + 300) card.dataset.initial = '1';
        observer.observe(card);
    });

})();
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/login-modal.js"></script>


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
</body>
</html>
