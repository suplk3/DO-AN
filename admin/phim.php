<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$result = mysqli_query($conn, "SELECT * FROM phim ORDER BY id DESC");
$count = mysqli_num_rows($result);

$showingMovieIds = array();
$showingResult = mysqli_query(
    $conn,
    "SELECT DISTINCT phim_id
     FROM suat_chieu
     WHERE phim_id IS NOT NULL"
);

if ($showingResult) {
    while ($showingRow = mysqli_fetch_assoc($showingResult)) {
        $showingMovieIds[(int)$showingRow['phim_id']] = true;
    }
}

$showingCount = count($showingMovieIds);
$notShowingCount = max(0, $count - $showingCount);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý phim</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body.admin-dark {
            min-height: 100vh;
            margin: 0;
            color: #e2e8f0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 8% 12%, rgba(239, 68, 68, 0.18), transparent 34%),
                radial-gradient(circle at 88% 0%, rgba(59, 130, 246, 0.2), transparent 36%),
                linear-gradient(160deg, #050816 0%, #0a1024 42%, #081226 100%);
        }
        .admin-shell {
            max-width: 1200px;
            margin: 28px auto;
            padding: 0 16px 32px;
            animation: pageEnter .45s ease both;
        }
        .admin-header {
            margin-bottom: 14px;
        }
        .page-title {
            margin: 0;
            font-size: 34px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #ffffff;
            padding: 20px 24px;
            border-radius: 14px;
            border: 1px solid rgba(59, 130, 246, 0.45);
            background: linear-gradient(90deg, rgba(30, 58, 95, 0.48), rgba(15, 23, 42, 0.94));
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.35), inset 0 1px 0 rgba(96, 165, 250, 0.15);
        }
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin: 16px 0;
        }
        .stat-card {
            border-radius: 14px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 14px 16px;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 58, 95, 0.35));
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.35);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
            animation: pageEnter .45s ease both;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stat-card .number {
            font-size: 34px;
            font-weight: 800;
            color: #60a5fa;
            line-height: 1.1;
        }
        .stat-card .label {
            margin-top: 2px;
            font-size: 13px;
            font-weight: 700;
            color: rgba(226, 232, 240, 0.72);
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        .stat-icon {
            font-size: 24px;
            min-width: 28px;
            text-align: center;
        }
        .stat-card.total .number { color: #60a5fa; }
        .stat-card.filter-showing .number { color: #06d6a0; }
        .stat-card.pending .number { color: #fbbf24; }
        .stat-card.filter-showing {
            cursor: pointer;
        }
        .stat-card.filter-showing:hover {
            transform: translateY(-2px);
            border-color: rgba(96, 165, 250, 0.55);
            box-shadow: 0 16px 32px rgba(30, 64, 175, 0.28);
        }
        .top-bar {
            display: grid;
            grid-template-columns: minmax(500px, 560px) minmax(280px, 1fr);
            gap: 12px;
            margin-bottom: 12px;
            align-items: stretch;
        }
        .toolbar-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            align-items: stretch;
        }
        .toolbar-search {
            min-width: 0;
            height: 48px;
            display: flex;
            align-items: stretch;
        }
        .toolbar-search .search-box {
            width: 100%;
            height: 48px;
            display: flex;
            align-items: center;
        }
        .filter-row {
            display: grid;
            grid-template-columns: minmax(180px, 240px) minmax(180px, 240px) 1fr;
            gap: 10px;
            margin-bottom: 14px;
            align-items: center;
        }
        .filter-hint {
            justify-self: end;
            color: rgba(226, 232, 240, 0.78);
            font-size: 13px;
            letter-spacing: .3px;
        }
        .search-box,
        .filter-select {
            position: relative;
        }
        .admin-shell .search-box input,
        .admin-shell .filter-select select {
            width: 100%;
            height: 48px;
            box-sizing: border-box;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.42);
            color: #e2e8f0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 58, 95, 0.32));
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .admin-shell .search-box input {
            padding: 0 14px 0 42px;
        }
        .admin-shell .filter-select select {
            padding: 0 12px;
        }
        .admin-shell .search-box input::placeholder {
            color: rgba(203, 213, 225, 0.74);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            opacity: .9;
            color: #60a5fa;
        }
        .admin-shell .search-box input:focus,
        .admin-shell .filter-select select:focus {
            border-color: rgba(96, 165, 250, 0.82);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.22);
        }
        .filter-select select option {
            background: #0f172a;
            color: #e2e8f0;
        }
        .msg.success {
            margin-bottom: 14px;
            border: 1px solid rgba(16, 185, 129, 0.45);
            background: rgba(16, 185, 129, 0.14);
            color: #6ee7b7;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
        }
        .toolbar-btn {
            margin-top: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 14px;
            height: 48px;
            box-sizing: border-box;
            border-radius: 10px;
            font-weight: 800;
            border: 1px solid rgba(59, 130, 246, 0.45);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            white-space: nowrap;
            text-align: center;
        }
        .toolbar-btn:hover {
            transform: translateY(-1px);
        }
        .toolbar-btn.primary {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-color: rgba(252, 165, 165, 0.75);
            box-shadow: 0 8px 18px rgba(220, 38, 38, 0.35);
            color: #ffffff;
        }
        .toolbar-btn.neutral {
            background: linear-gradient(135deg, rgba(71, 85, 105, 0.55), rgba(30, 41, 59, 0.85));
            border-color: rgba(148, 163, 184, 0.52);
            color: #dbeafe;
        }
        .toolbar-btn.outline {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.16), rgba(37, 99, 235, 0.08));
            border-color: rgba(59, 130, 246, 0.75);
            color: #93c5fd;
            box-shadow: 0 6px 14px rgba(59, 130, 246, 0.2);
        }
        .table-wrapper {
            overflow: auto;
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.84), rgba(30, 41, 59, 0.66));
            box-shadow: 0 14px 28px rgba(2, 6, 23, 0.38);
            animation: pageEnter .5s ease both;
        }
        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            font-size: 14px;
        }
        table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 13px 14px;
            text-align: left;
            font-size: 12px;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: #f8fafc;
            background: linear-gradient(90deg, rgba(30, 58, 95, 0.95), rgba(22, 47, 86, 0.95));
            border-bottom: 1px solid rgba(59, 130, 246, 0.35);
        }
        table tbody td {
            padding: 14px;
            color: rgba(226, 232, 240, 0.9);
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
            vertical-align: middle;
        }
        table tbody tr:nth-child(even) {
            background: rgba(30, 41, 59, 0.34);
        }
        table tbody tr:hover {
            background: rgba(30, 64, 175, 0.18);
        }
        table td:first-child {
            width: 74px;
            color: #93c5fd;
            font-weight: 700;
        }
        table td img {
            width: 56px;
            height: 82px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: 0 8px 16px rgba(2, 6, 23, 0.35);
        }
        .genre-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .genre-badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(251, 113, 133, 0.38);
            background: rgba(190, 24, 93, 0.18);
            color: #fda4af;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .status-badge.showing {
            background: rgba(16, 185, 129, 0.16);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.4);
        }
        .status-badge.not-showing {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.32);
        }
        .movie-title {
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: 0.2px;
        }
        .poster-empty {
            color: rgba(203, 213, 225, 0.66);
            font-style: italic;
        }
        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            align-items: center;
        }
        .action-btns a {
            padding: 6px 10px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            transition: transform .15s ease, filter .15s ease;
            text-align: center;
        }
        .action-btns a:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
        }
        .action-btns .btn-edit {
            background: rgba(59, 130, 246, 0.18);
            border: 1px solid rgba(96, 165, 250, 0.4);
            color: #93c5fd;
        }
        .action-btns .btn-delete {
            background: rgba(239, 68, 68, 0.16);
            border: 1px solid rgba(248, 113, 113, 0.45);
            color: #fecaca;
        }
        .empty-state {
            text-align: center;
            padding: 42px 20px;
            color: rgba(226, 232, 240, 0.74);
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
        }
        .empty-state .message {
            font-size: 17px;
            margin-bottom: 8px;
        }
        .empty-state .action {
            margin-top: 18px;
        }
        .empty-state .empty-add-btn {
            padding: 12px 20px;
            font-size: 15px;
            border-radius: 10px;
            border: 1px solid rgba(252, 165, 165, 0.75);
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.28);
        }
        .empty-row {
            display: none;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: rgba(226, 232, 240, 0.72);
            font-style: italic;
        }
        .no-results-cell {
            text-align: center;
            padding: 40px;
            color: rgba(226, 232, 240, 0.72);
        }
        .no-results-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        @keyframes pageEnter {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 960px) {
            .top-bar {
                grid-template-columns: 1fr;
            }
            .toolbar-actions {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .toolbar-search {
                width: 100%;
            }
            .filter-row {
                grid-template-columns: 1fr 1fr;
            }
            .filter-hint {
                grid-column: 1 / -1;
                justify-self: start;
            }
            .stat-card .number {
                font-size: 26px;
            }
        }
        @media (max-width: 680px) {
            .admin-shell {
                margin-top: 20px;
            }
            .page-title {
                font-size: 24px;
                padding: 16px 18px;
            }
            .quick-stats {
                grid-template-columns: 1fr;
            }
            .toolbar-btn {
                width: 100%;
                justify-content: center;
            }
            .toolbar-actions {
                width: 100%;
                grid-template-columns: 1fr;
            }
            .filter-row {
                grid-template-columns: 1fr;
            }
            table {
                font-size: 13px;
            }
            table thead th,
            table tbody td {
                padding: 10px;
            }
            .action-btns {
                flex-direction: row;
            }
        }
    </style>
</head>
<body class="admin-dark">

<div class="admin-shell">
    <div class="admin-header">
        <h1 class="page-title">🎬 Quản lý phim</h1>
    </div>

    <div class="top-bar">
        <div class="toolbar-actions">
            <a href="them_phim.php" class="btn toolbar-btn primary">
                <span>➕</span> Thêm phim mới
            </a>
            <a href="../user/index.php" class="btn toolbar-btn outline">
                <span>🏠</span> Về trang chính
            </a>
            <a href="reset_id.php" class="btn toolbar-btn neutral">
                <span>🔄</span> Reset ID
            </a>
        </div>
        <div class="toolbar-search">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Tìm tên phim, thể loại..." onkeyup="filterMovies()">
            </div>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="quick-stats">
        <div class="stat-card total">
            <span class="stat-icon">🎬</span>
            <div>
                <div class="label">Tổng phim</div>
                <div class="number"><?= $count ?></div>
            </div>
        </div>
        <div class="stat-card filter-showing" title="Nhấn để lọc phim đã chiếu" onclick="showOnlyShowingMovies()">
            <span class="stat-icon">✓</span>
            <div>
                <div class="label">Đã chiếu</div>
                <div class="number"><?= $showingCount ?></div>
            </div>
        </div>
        <div class="stat-card pending">
            <span class="stat-icon">⏸</span>
            <div>
                <div class="label">Chưa có suất</div>
                <div class="number"><?= $notShowingCount ?></div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="filter-row">
        <div class="filter-select">
            <select id="genreFilter" onchange="filterMovies()">
                <option value="">Tất cả thể loại</option>
                <?php
                $genres_result = mysqli_query($conn, "SELECT the_loai FROM phim WHERE the_loai != '' AND the_loai IS NOT NULL ORDER BY the_loai");
                $all_genres = array();
                while ($row = mysqli_fetch_assoc($genres_result)) {
                    $genres_list = array_map('trim', explode(',', $row['the_loai']));
                    $all_genres = array_merge($all_genres, $genres_list);
                }
                $all_genres = array_unique($all_genres);
                sort($all_genres);
                foreach ($all_genres as $genre) {
                    if (!empty($genre)) {
                        echo '<option value="' . htmlspecialchars($genre) . '">' . htmlspecialchars($genre) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        <div class="filter-select">
            <select id="statusFilter" onchange="filterMovies()">
                <option value="">Tất cả trạng thái</option>
                <option value="showing">Đã chiếu</option>
                <option value="not_showing">Chưa có suất chiếu</option>
            </select>
        </div>
        <div class="filter-hint">Tổng: <strong><?= $count ?></strong> phim</div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="msg success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th># ID</th>
                    <th>🎬 Tên phim</th>
                    <th>🏷️ Thể loại</th>
                    <th>🖼️ Poster</th>
                    <th>🔒 Trạng thái</th>
                    <th>⚙️ Hành động</th>
                </tr>
            </thead>
            <tbody id="movieTableBody">
                <?php 
                if ($count === 0) {
                    echo '<tr class="empty-row"><td colspan="6">
                        <div class="empty-state">
                            <div class="icon">🎬</div>
                            <div class="message">Chưa có phim nào trong hệ thống</div>
                            <div class="action">
                                <a href="them_phim.php" class="btn empty-add-btn">
                                    <span>➕</span> Thêm phim đầu tiên
                                </a>
                            </div>
                        </div>
                    </td></tr>';
                } else {
                    while ($row = mysqli_fetch_assoc($result)): 
                        $isShowing = isset($showingMovieIds[(int)$row['id']]);
                ?>
                <tr class="movie-row"
                    data-genres="<?= htmlspecialchars(str_replace(',', '|', $row['the_loai'])) ?>"
                    data-title="<?= htmlspecialchars(strtolower($row['ten_phim'])) ?>"
                    data-showing="<?= $isShowing ? '1' : '0' ?>">
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td class="movie-title"><?= htmlspecialchars($row['ten_phim']) ?></td>
                    <td>
                        <div class="genre-list">
                        <?php 
                        // Tách các thể loại và hiển thị dưới dạng badge
                        $genres = array_map('trim', explode(',', $row['the_loai']));
                        foreach ($genres as $g) {
                            if (!empty($g)) {
                                echo '<span class="genre-badge">' . htmlspecialchars($g) . '</span>';
                            }
                        }
                        ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($row['poster']) && file_exists(__DIR__ . '/../assets/images/' . $row['poster'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($row['poster']) ?>" alt="poster" loading="lazy">
                        <?php else: ?>
                            <span class="poster-empty">Chưa có ảnh</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isShowing): ?>
                            <span class="status-badge showing">Đã chiếu</span>
                        <?php else: ?>
                            <span class="status-badge not-showing">Chưa có suất</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="sua_phim.php?id=<?= $row['id'] ?>" class="btn-edit" title="Chỉnh sửa phim">✏️ Sửa</a>
                            <a href="xoa_phim.php?id=<?= $row['id'] ?>" 
                               class="btn-delete"
                               onclick="return confirm('Bạn có chắc muốn xóa phim này?\nTên: <?= htmlspecialchars($row['ten_phim']) ?>')"
                               title="Xóa phim">❌ Xóa</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Khởi tạo khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    // Focus vào input tìm kiếm
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.focus();
    }
});

// Hàm lọc phim
function filterMovies() {
    const searchInput = document.getElementById('searchInput');
    const genreFilter = document.getElementById('genreFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!searchInput || !genreFilter || !statusFilter) {
        console.error('Không tìm thấy element search hoặc filter');
        return;
    }
    
    const searchValue = searchInput.value.toLowerCase().trim();
    const genreValue = genreFilter.value.trim();
    const statusValue = statusFilter.value.trim();
    const rows = document.querySelectorAll('.movie-row');
    const tbody = document.getElementById('movieTableBody');
    
    let visibleCount = 0;
    
    // Xóa thông báo "Không tìm thấy" cũ
    const oldNoResults = tbody.querySelector('.no-results');
    if (oldNoResults) {
        oldNoResults.remove();
    }
    
    // Lọc từng hàng
    rows.forEach(row => {
        const title = (row.dataset.title || '').toLowerCase();
        const genresStr = (row.dataset.genres || ''); // Sử dụng data-genres
        const isShowing = row.dataset.showing === '1';
        
        // Tách các genre từ chuỗi (được ngăn cách bằng |)
        const genres = genresStr.split('|').map(g => g.trim().toLowerCase()).filter(g => g.length > 0);
        
        // Kiểm tra điều kiện tìm kiếm
        const matchSearch = searchValue === '' || title.includes(searchValue);
        
        // Kiểm tra thể loại - nếu có 1 thể loại khớp thì tính là vừa
        let matchGenre = false;
        if (genreValue === '') {
            matchGenre = true;
        } else {
            matchGenre = genres.some(g => g === genreValue.toLowerCase());
        }

        // Kiểm tra trạng thái chiếu
        let matchStatus = false;
        if (statusValue === '') {
            matchStatus = true;
        } else if (statusValue === 'showing') {
            matchStatus = isShowing;
        } else if (statusValue === 'not_showing') {
            matchStatus = !isShowing;
        }
        
        // Hiển thị hoặc ẩn hàng
        if (matchSearch && matchGenre && matchStatus) {
            row.style.display = 'table-row';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Hiển thị thông báo nếu không có kết quả
    if (visibleCount === 0) {
        const noResults = document.createElement('tr');
        noResults.className = 'no-results';
        noResults.innerHTML = `
            <td colspan="6" class="no-results-cell">
                <div class="no-results-icon">🔍</div>
                <div>Không tìm thấy phim nào phù hợp</div>
            </td>
        `;
        tbody.appendChild(noResults);
    }
}

function showOnlyShowingMovies() {
    const statusFilter = document.getElementById('statusFilter');
    if (!statusFilter) return;
    statusFilter.value = 'showing';
    filterMovies();
}
</script>

</body>
</html>
