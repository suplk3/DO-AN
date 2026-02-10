<?php
include "check_admin.php";
include "../config/db.php";

$sql = "
SELECT 
    suat_chieu.*,
    phim.ten_phim,
    COUNT(ve.id) AS so_ve
FROM suat_chieu
JOIN phim ON suat_chieu.phim_id = phim.id
LEFT JOIN ve ON ve.suat_chieu_id = suat_chieu.id
GROUP BY suat_chieu.id
ORDER BY ngay, gio
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/actions.css">
<style>
    body { max-width: 1200px; margin: 0 auto; padding: 20px; background: #fff8e6; color: #0f172a; }
</style>
</head>
<body>

<h2 style="background: linear-gradient( #0f172a); color: #ffffff; margin-bottom: 20px;">🎞️ QUẢN LÝ SUẤT CHIẾU</h2>

<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="them_suat.php" class="btn" style="background: rgba(232, 68, 23, 0.93); color:  white ; padding: 10px 16px; border-radius: 6px; text-decoration: none;">➕ Thêm suất chiếu</a>
    <a href="../user/index.php" class="btn" style="background: rgba(232, 68, 23, 0.93); color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; border: 1px solid rgba(255,255,255,0.2);">🏠 Về trang chính</a>
</div>

<div class="actions-section">
    <div class="action-header">
        <div>⏰ Khung giờ</div>
        <div>🎬 Tên phim</div>
        <div>⚙️ Hành động</div>
    </div>

<?php while ($row = mysqli_fetch_assoc($result)): ?>
    <div class="action-row">
        <div class="action-time">
            <span class="action-time-icon">📅</span>
            <span><?= date('d/m/Y', strtotime($row['ngay'])) ?> - <?= $row['gio'] ?></span>
        </div>
        <div class="action-movie">
            <span class="action-movie-icon">🎬</span>
            <span><?= htmlspecialchars($row['ten_phim']) ?></span>
        </div>
        <div class="action-buttons">
            <?php if ($row['so_ve'] == 0): ?>
                <a href="sua_suat.php?id=<?= $row['id'] ?>" class="btn-action">✏️ Sửa</a>
                <span class="separator">|</span>
                <a href="xoa_suat.php?id=<?= $row['id'] ?>" class="btn-action btn-delete-action" onclick="return confirm('Xóa suất chiếu này?')">❌ Xóa</a>
            <?php else: ?>
                <div style="display: flex; align-items: center; gap: 6px; color: #ff4d4f; font-weight: 700;">
                    <span>🔒</span>
                    <span>Đã có về</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endwhile; ?>
</div>

</body>
</html>
