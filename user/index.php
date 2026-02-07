<?php
session_start();
include "../config/db.php";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CGV Cinemas</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
</head>
<body class="user-index">

<header class="header">
    <div class="header-inner">
        <div class="logo">CGV</div>

        <nav class="menu">
            <a href="index.php" class="nav-link active">🎬 PHIM</a>

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
                   onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
                   🚪 ĐĂNG XUẤT
                </a>
            <?php else: ?>
                <a href="../auth/login.php" class="btn">🔐 ĐĂNG NHẬP</a>
            <?php endif; ?>
        </div>
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
                        <a href="../auth/login.php" class="btn btn-outline">ĐĂNG NHẬP ĐỂ ĐẶT VÉ</a>
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

</body>
</html>
