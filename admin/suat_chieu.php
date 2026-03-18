<?php
include "check_admin.php";
include "../config/db.php";

// tự động xóa các suất chiếu đã quá 2 ngày (ngày trước ngày hiện tại 2 ngày trở lên)
// chạy mỗi khi trang quản lý suất chiếu được tải
// xóa vé trước để tránh dữ liệu mồ côi (bảng không có khóa ngoại)
$deleteTickets = "DELETE ve FROM ve JOIN suat_chieu sc ON ve.suat_chieu_id = sc.id WHERE sc.ngay < DATE_SUB(CURDATE(), INTERVAL 2 DAY)";
mysqli_query($conn, $deleteTickets);

$deleteOldSql = "DELETE FROM suat_chieu WHERE ngay < DATE_SUB(CURDATE(), INTERVAL 2 DAY)";
mysqli_query($conn, $deleteOldSql);


$sql = "
SELECT 
    suat_chieu.*,
    phim.ten_phim,
    phong_chieu.ten_phong,
    rap.ten_rap,
    COUNT(ve.id) AS so_ve
FROM suat_chieu
JOIN phim ON suat_chieu.phim_id = phim.id
LEFT JOIN phong_chieu ON suat_chieu.phong_id = phong_chieu.id
LEFT JOIN rap ON phong_chieu.rap_id = rap.id
LEFT JOIN ve ON ve.suat_chieu_id = suat_chieu.id
GROUP BY suat_chieu.id
ORDER BY ngay, gio
";
$result = mysqli_query($conn, $sql);

// Tính thống kê
$stats_sql = "
SELECT 
    COUNT(DISTINCT suat_chieu.id) AS total_showtimes,
    COUNT(DISTINCT CASE WHEN COUNT(ve.id) = 0 THEN suat_chieu.id END) AS available_showtimes,
    COUNT(DISTINCT CASE WHEN COUNT(ve.id) > 0 THEN suat_chieu.id END) AS booked_showtimes,
    COALESCE(SUM(suat_chieu.gia * (SELECT COUNT(*) FROM ve WHERE ve.suat_chieu_id = suat_chieu.id)), 0) AS total_revenue
FROM suat_chieu
LEFT JOIN ve ON ve.suat_chieu_id = suat_chieu.id
GROUP BY suat_chieu.id
";
$stats_result = mysqli_query($conn, "
SELECT 
    COUNT(DISTINCT suat_chieu.id) AS total_showtimes,
    SUM(CASE WHEN (SELECT COUNT(*) FROM ve WHERE ve.suat_chieu_id = suat_chieu.id) = 0 THEN 1 ELSE 0 END) AS available_showtimes,
    SUM(CASE WHEN (SELECT COUNT(*) FROM ve WHERE ve.suat_chieu_id = suat_chieu.id) > 0 THEN 1 ELSE 0 END) AS booked_showtimes
FROM suat_chieu
");
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/actions.css">
<link rel="stylesheet" href="../assets/css/features.css">
<style>
    body { max-width: 1200px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #0a0e17 0%, #0f1419 100%); color: #e2e8f0; }
</style>
</head>
<body>

<div id="notification"></div>

<h2 style="background: linear-gradient(135deg, #1a1f2e 0%, #0f172a 100%); color: #ffffff; margin-bottom: 24px; padding: 20px 24px; border-radius: 12px; font-size: 24px; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4), 0 2px 8px rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.15);">🎞️ QUẢN LÝ SUẤT CHIẾU</h2>

<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: nowrap; align-items: stretch;">
    <a href="them_suat.php" class="btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1px solid #f87171; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px; white-space: nowrap;">➕ Thêm suất chiếu</a>
    <a href="quan_ly_phong.php" class="btn" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1px solid #4ade80; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px; white-space: nowrap;">🏛️ Quản lý phòng</a>
    <a href="dashboard_advanced.php" class="btn" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1px solid #a78bfa; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px; white-space: nowrap;">📊 Dashboard Thống Kê</a>
    <a href="../user/index.php" class="btn" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%); color: #3b82f6; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1.5px solid #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px; white-space: nowrap;">🏠 Về trang chính</a>
    
    <div style="flex: 1; display: flex; align-items: stretch;">
        <div class="search-box" style="display: flex; align-items: center;">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" placeholder="Tìm tên phim, ngày, giờ...">
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="stats-section">
    <div class="stat-card total">
        <div class="stat-icon">🎬</div>
        <div class="stat-content">
            <div class="stat-label">Tổng suất chiếu</div>
            <div class="stat-value"><?= $stats['total_showtimes'] ?? 0 ?></div>
        </div>
    </div>
    <div class="stat-card available">
        <div class="stat-icon">✓</div>
        <div class="stat-content">
            <div class="stat-label">Còn trống</div>
            <div class="stat-value"><?= $stats['available_showtimes'] ?? 0 ?></div>
        </div>
    </div>
    <div class="stat-card booked">
        <div class="stat-icon">🔒</div>
        <div class="stat-content">
            <div class="stat-label">Đã có vé</div>
            <div class="stat-value"><?= $stats['booked_showtimes'] ?? 0 ?></div>
        </div>
    </div>
</div>

<div class="actions-section">
    <div class="action-header">
        <div>⏰ Khung giờ</div>
        <div>🎬 Tên phim</div>
        <div>🏢 Rạp</div>
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
        <div class="action-theater">
            <span class="action-theater-icon">🏢</span>
            <span><?= htmlspecialchars($row['ten_rap'] ?? 'Không xác định') ?></span>
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

<script>
// Search functionality
const searchInput = document.getElementById('searchInput');
const actionRows = document.querySelectorAll('.action-row');

searchInput?.addEventListener('keyup', function(e) {
    const query = e.target.value.toLowerCase();
    
    actionRows.forEach(row => {
        const time = row.querySelector('.action-time span:last-child')?.textContent.toLowerCase() || '';
        const movie = row.querySelector('.action-movie span:last-child')?.textContent.toLowerCase() || '';
        const theater = row.querySelector('.action-theater span:last-child')?.textContent.toLowerCase() || '';
        const price = row.querySelector('.action-price')?.textContent.toLowerCase() || '';
        
        if (time.includes(query) || movie.includes(query) || theater.includes(query) || price.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Notification function
function showNotification(message, type = 'success') {
    const notifContainer = document.getElementById('notification');
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    notif.innerHTML = `
        <span>${message}</span>
        <span class="notification-close" onclick="this.parentElement.classList.add('exit'); setTimeout(() => this.parentElement.remove(), 300)">✕</span>
    `;
    notifContainer.appendChild(notif);
    
    setTimeout(() => {
        notif.classList.add('exit');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Show success notification if redirect from action
const urlParams = new URLSearchParams(window.location.search);
if (window.location.href.includes('them_suat') || window.location.href.includes('sua_suat')) {
    // Optional: show notification based on page state
}
</script>

</body>
</html>
