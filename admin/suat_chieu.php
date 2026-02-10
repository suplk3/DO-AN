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
    body { max-width: 1200px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #0f1419 0%, #1a1f2e 100%); color: #e2e8f0; }
</style>
</head>
<body>

<h2 style="background: linear-gradient(135deg, #1a1f2e 0%, #0f172a 100%); color: #ffffff; margin-bottom: 24px; padding: 20px 24px; border-radius: 12px; font-size: 24px; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4), 0 2px 8px rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.15); margin: 0 0 24px 0;">🎞️ QUẢN LÝ SUẤT CHIẾU</h2>

<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <a href="them_suat.php" class="btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1px solid #f87171; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px;">➕ Thêm suất chiếu</a>
    <a href="../user/index.php" class="btn" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%); color: #3b82f6; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1.5px solid #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px;">🏠 Về trang chính</a>
</div>

<div class="actions-section">
    <div class="action-header">
        <div>⏰ Khung giờ</div>
        <div>🎬 Tên phim</div>
        <div>💰 Giá</div>
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
        <div class="action-price">
            <?= number_format($row['gia']) ?> đ
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
