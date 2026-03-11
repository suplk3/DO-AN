<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$thong_bao = '';

// Hủy vé của chính mình
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huy_ve'])) {
    $ve_id = (int)$_POST['ve_id'];
    $sql_huy = "DELETE FROM ve WHERE id = ? AND user_id = ?";
    $stmt_huy = mysqli_prepare($conn, $sql_huy);
    mysqli_stmt_bind_param($stmt_huy, 'ii', $ve_id, $user_id);
    $thong_bao = mysqli_stmt_execute($stmt_huy)
        ? "✅ Hủy vé thành công"
        : "❌ Hủy vé thất bại";
}

// Lấy vé của user hiện tại
$sql = "SELECT v.id AS ve_id, p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia,
               r.ten_rap, pc.ten_phong, g.ten_ghe
        FROM ve v
        LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
        LEFT JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        LEFT JOIN ghe g ON v.ghe_id = g.id
        WHERE v.user_id = ?
        ORDER BY sc.ngay DESC, sc.gio DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ves = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vé của tôi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="ve-page">
<div class="wrap">
    <div class="header">
        <h1>🎫 Vé của tôi</h1>
        <a href="../user/index.php" class="btn btn-outline">← Danh sách phim</a>
    </div>

    <?php if (!empty($thong_bao)): ?>
        <div class="msg msg-success"><?= htmlspecialchars($thong_bao) ?></div>
    <?php endif; ?>

    <?php if (empty($ves)): ?>
        <div class="empty">
            <h2>Bạn chưa đặt vé nào</h2>
            <p>Hãy chọn phim và đặt vé để xem lịch sử vé tại đây.</p>
            <a href="../user/index.php" class="btn btn-hero" style="margin-top:12px;">🎬 Đặt vé ngay</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($ves as $ve): ?>
                <div class="card">
                    <img src="../assets/images/<?= htmlspecialchars($ve['poster']) ?>" alt="<?= htmlspecialchars($ve['ten_phim']) ?>">
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($ve['ten_phim']) ?></div>
                        <div class="meta mt">
                            <div>Rạp: <strong><?= htmlspecialchars($ve['ten_rap']) ?></strong></div>
                            <div>Phòng: <strong><?= htmlspecialchars($ve['ten_phong']) ?></strong></div>
                        </div>
                        <div class="meta mt">
                            <div>Ghế: <strong><?= htmlspecialchars($ve['ten_ghe']) ?></strong></div>
                            <div><?= date('d/m/Y', strtotime($ve['ngay'])) ?> • <?= substr($ve['gio'],0,5) ?></div>
                        </div>
                        <div class="price">Giá: <?= number_format($ve['gia']) ?> đ</div>
                        <div class="actions">
                            <button class="btn btn-print" onclick="window.print()">🖨️ In vé</button>
                            <a class="btn btn-save" href="save_ticket.php?ve_id=<?= (int)$ve['ve_id']; ?>">💾 Lưu vé</a>
                            <form method="POST">
                                <input type="hidden" name="ve_id" value="<?= (int)$ve['ve_id']; ?>">
                                <button type="submit" name="huy_ve" class="btn btn-cancel" onclick="return confirm('Bạn có chắc muốn hủy vé này?')">❌ Hủy vé</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<div id="star-overlay"></div>
<script>
function createStar() {
    const overlay = document.getElementById('star-overlay');
    const parent = overlay || document.body;
    const star = document.createElement('div');
    star.className = 'star';
    star.style.left = Math.random() * 100 + 'vw';
    star.style.animationDuration = Math.random() * 3 + 2 + 's';
    star.style.animationDelay = Math.random() * 2 + 's';
    parent.appendChild(star);
    setTimeout(() => { star.remove(); }, 5000);
}
setInterval(createStar, 200);
</script>
</body>
</html>
