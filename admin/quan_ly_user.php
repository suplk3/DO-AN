<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['vai_tro'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql_base = "SELECT v.id AS ve_id,
                    v.user_id,
                    u.ten AS ten_user,
                    u.email AS email_user,
                    p.ten_phim,
                    sc.ngay,
                    sc.gio,
                    sc.gia,
                    r.ten_rap,
                    pc.ten_phong,
                    g.ten_ghe
             FROM ve v
             LEFT JOIN users u ON v.user_id = u.id
             LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
             LEFT JOIN phim p ON sc.phim_id = p.id
             LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
             LEFT JOIN rap r ON pc.rap_id = r.id
             LEFT JOIN ghe g ON v.ghe_id = g.id";

if ($search !== '') {
    $search_param = '%' . $search . '%';
    $sql = $sql_base . " WHERE (u.ten LIKE ? OR u.email LIKE ?) ORDER BY sc.ngay DESC, sc.gio DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $search_param, $search_param);
} else {
    $sql = $sql_base . " ORDER BY sc.ngay DESC, sc.gio DESC";
    $stmt = mysqli_prepare($conn, $sql);
}
if (!$stmt) die(mysqli_error($conn));
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ves = mysqli_fetch_all($result, MYSQLI_ASSOC);

$users_list = [];
if ($search !== '') {
    $u_stmt = mysqli_prepare($conn, "SELECT id, ten, email FROM users WHERE vai_tro = 'user' AND (ten LIKE ? OR email LIKE ?) ORDER BY ten");
    mysqli_stmt_bind_param($u_stmt, 'ss', $search_param, $search_param);
    mysqli_stmt_execute($u_stmt);
    $u_res = mysqli_stmt_get_result($u_stmt);
} else {
    $u_res = mysqli_query($conn, "SELECT id, ten, email FROM users WHERE vai_tro = 'user' ORDER BY ten");
}
if ($u_res) {
    while ($u = mysqli_fetch_assoc($u_res)) {
        $users_list[(int)$u['id']] = $u;
    }
}
$ves_by_user = [];
foreach ($ves as $v) {
    $uid = (int)$v['user_id'];
    $ves_by_user[$uid][] = $v;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý user</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="ve-page">
<div class="wrap">
    <div class="header">
        <h1>👥 Quản lý user</h1>
        <a href="../user/index.php" class="btn btn-outline">← Trang chính</a>
    </div>

    <form method="get" class="search-bar" style="margin: 0 0 14px 0; display:flex; gap:8px; flex-wrap:wrap;">
        <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm tên hoặc email người dùng..." style="padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.18);background:rgba(0,0,0,0.25);color:#fff;min-width:240px;">
        <button type="submit" class="btn">Tìm</button>
        <?php if ($search !== ''): ?>
            <a href="quan_ly_user.php" class="btn btn-outline">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <div class="grid">
        <?php foreach ($users_list as $uid => $user): 
            $list = $ves_by_user[$uid] ?? []; ?>
            <div class="card">
                <div class="card-body">
                    <div class="card-title">👤 <?= htmlspecialchars($user['ten'] ?? 'Khách'); ?></div>
                    <div class="meta mt">
                        <div>Email: <strong><?= htmlspecialchars($user['email'] ?? ''); ?></strong></div>
                        <div>Số vé: <strong><?= count($list); ?></strong></div>
                    </div>
                    <?php if (empty($list)): ?>
                        <p class="muted" style="margin-top:8px;">Chưa đặt vé nào.</p>
                    <?php else: ?>
                        <?php foreach ($list as $ve): ?>
                            <div class="meta mt" style="border-top:1px solid rgba(226,232,240,0.08);padding-top:6px;">
                                <div><strong><?= htmlspecialchars($ve['ten_phim']); ?></strong></div>
                                <div><?= date('d/m/Y', strtotime($ve['ngay'])); ?> • <?= substr($ve['gio'],0,5); ?></div>
                                <div>Rạp: <?= htmlspecialchars($ve['ten_rap']); ?> | Phòng: <?= htmlspecialchars($ve['ten_phong']); ?> | Ghế: <?= htmlspecialchars($ve['ten_ghe']); ?></div>
                                <div>Giá: <?= number_format($ve['gia']); ?> đ</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

