<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$result = mysqli_query($conn, "SELECT * FROM phim ORDER BY id DESC");
$count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý phim</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-header{margin-bottom:20px}
        .table-wrapper{overflow-x:auto;border-radius:10px}
        table{font-size:14px;border-collapse:separate;border-spacing:0}
        table thead{background:var(--accent-red);position:sticky;top:0}
        table thead th{color:rgba(255,255,255,0.85);padding:12px 14px;text-align:left;font-weight:700}
        table tbody tr{border-bottom:1px solid rgba(15,23,42,0.04)}
        table tbody tr:hover{background:rgba(229,9,20,0.08)}
        /* Chữ trắng mềm mại không quá sáng */
        table tbody td{padding:12px 14px;color:rgba(255,255,255,0.8)}
        table td:first-child{width:60px}
        table td img{width:50px;border-radius:6px;border:1px solid rgba(255,255,255,0.06)}
        .action-btns{display:flex;gap:8px;flex-wrap:wrap}
        .top-bar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
        .stats{color:rgba(255,255,255,0.7);font-size:14px}
        .search-filter-bar{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
        .search-box{position:relative}
        .search-box input{padding:8px 12px;padding-left:35px;border:1px solid rgba(255,255,255,0.2);border-radius:6px;background:rgba(255,255,255,0.1);color:#fff;width:250px}
        .search-box input::placeholder{color:rgba(255,255,255,0.6)}
        .search-box::before{content:'🔍';position:absolute;left:10px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.6)}
        .filter-select select{padding:8px 12px;border:1px solid rgba(255,255,255,0.2);border-radius:6px;background:rgba(255,255,255,0.1);color:#fff}
        .filter-select select option{background:#1a1a1a;color:#fff}
        .quick-stats{display:flex;gap:20px;margin-bottom:15px;flex-wrap:wrap}
        .stat-card{background:rgba(255,255,255,0.05);padding:12px 18px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);text-align:center;min-width:120px}
        .stat-card .number{font-size:20px;font-weight:700;color:var(--accent-red)}
        .stat-card .label{font-size:12px;color:rgba(255,255,255,0.7);margin-top:2px}
        .empty-state{text-align:center;padding:40px 20px;color:rgba(255,255,255,0.6)}
        .empty-state .icon{font-size:48px;margin-bottom:15px;display:block}
        .empty-state .message{font-size:16px;margin-bottom:10px}
        .empty-state .action{margin-top:20px}
        .genre-badge{background:rgba(229,9,20,0.2);color:#fff;padding:4px 8px;border-radius:12px;font-size:12px;border:1px solid rgba(229,9,20,0.3)}
        .movie-title{font-weight:500}
        .empty-row{display:none}
        .no-results{text-align:center;padding:40px;color:rgba(255,255,255,0.6);font-style:italic}
        .no-results .icon{font-size:32px;margin-bottom:10px;display:block}
        @media(max-width:768px){
            table{font-size:12px}
            table thead th, table tbody td{padding:8px 10px}
            .action-btns{flex-direction:column}
            .action-btns a{width:100%}
            .search-filter-bar{flex-direction:column;align-items:stretch}
            .search-box input{width:100%}
            .quick-stats{justify-content:space-around}
            .stat-card{min-width:80px}
        }
    </style>
</head>
<body class="admin-dark">

<div style="max-width:1200px;margin:0 auto;padding:20px">
    <div class="admin-header">
        <h1 style="margin:0;color:var(--accent-red);font-size:26px">🎬 Quản lý phim</h1>
        <p class="stats">Tổng: <strong><?= $count ?></strong> phim</p>
    </div>

    <!-- Thống kê nhanh -->
    <div class="quick-stats">
        <div class="stat-card">
            <div class="number"><?= $count ?></div>
            <div class="label">Tổng phim</div>
        </div>
        <div class="stat-card">
            <div class="number">0</div>
            <div class="label">Đang chiếu</div>
        </div>
    </div>

    <!-- Thanh tìm kiếm và bộ lọc -->
    <div class="search-filter-bar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Tìm kiếm phim..." onkeyup="filterMovies()">
        </div>
        <div class="filter-select">
            <select id="genreFilter" onchange="filterMovies()">
                <option value="">Tất cả thể loại</option>
                <?php
                // Lấy tất cả thể loại từ database
                $genres_result = mysqli_query($conn, "SELECT the_loai FROM phim WHERE the_loai != '' AND the_loai IS NOT NULL ORDER BY the_loai");
                $all_genres = array();
                while ($row = mysqli_fetch_assoc($genres_result)) {
                    // Tách các thể loại nếu có nhiều (separated by comma)
                    $genres_list = array_map('trim', explode(',', $row['the_loai']));
                    $all_genres = array_merge($all_genres, $genres_list);
                }
                // Loại bỏ duplicate và sắp xếp
                $all_genres = array_unique($all_genres);
                sort($all_genres);
                // Hiển thị các option
                foreach ($all_genres as $genre) {
                    if (!empty($genre)) {
                        echo '<option value="' . htmlspecialchars($genre) . '">' . htmlspecialchars($genre) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="msg success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <a href="them_phim.php" class="btn btn-add" style="display:flex;align-items:center;gap:6px;padding:10px 16px">
            <span>➕</span> Thêm phim mới
        </a>
        <a href="reset_id.php" class="btn btn-add" style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2)">
            <span>🔄</span> Reset ID
        </a>
        <a href="../user/index.php" class="btn btn-home" style="display:flex;align-items:center;gap:6px;padding:10px 16px">
            <span>🏠</span> Về trang chính
        </a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên phim</th>
                    <th>Thể loại</th>
                    <th>Poster</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="movieTableBody">
                <?php 
                if ($count === 0) {
                    echo '<tr class="empty-row"><td colspan="5">
                        <div class="empty-state">
                            <div class="icon">🎬</div>
                            <div class="message">Chưa có phim nào trong hệ thống</div>
                            <div class="action">
                                <a href="them_phim.php" class="btn btn-add" style="display:inline-flex;align-items:center;gap:6px;padding:12px 20px;font-size:16px">
                                    <span>➕</span> Thêm phim đầu tiên
                                </a>
                            </div>
                        </div>
                    </td></tr>';
                } else {
                    while ($row = mysqli_fetch_assoc($result)): 
                ?>
                <tr class="movie-row" data-genres="<?= htmlspecialchars(str_replace(',', '|', $row['the_loai'])) ?>" data-title="<?= htmlspecialchars(strtolower($row['ten_phim'])) ?>">
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td class="movie-title"><?= htmlspecialchars($row['ten_phim']) ?></td>
                    <td>
                        <?php 
                        // Tách các thể loại và hiển thị dưới dạng badge
                        $genres = array_map('trim', explode(',', $row['the_loai']));
                        foreach ($genres as $g) {
                            if (!empty($g)) {
                                echo '<span class="genre-badge" style="margin-right:5px;margin-bottom:5px;display:inline-block;">' . htmlspecialchars($g) . '</span>';
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($row['poster']) && file_exists(__DIR__ . '/../assets/images/' . $row['poster'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($row['poster']) ?>" alt="poster" loading="lazy">
                        <?php else: ?>
                            <span style="color:rgba(255,255,255,0.4);font-style:italic">Chưa có ảnh</span>
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
    
    // Thêm sự kiện cho hover
    addRowHoverEffect();
});

// Hàm lọc phim
function filterMovies() {
    const searchInput = document.getElementById('searchInput');
    const genreFilter = document.getElementById('genreFilter');
    
    if (!searchInput || !genreFilter) {
        console.error('Không tìm thấy element search hoặc filter');
        return;
    }
    
    const searchValue = searchInput.value.toLowerCase().trim();
    const genreValue = genreFilter.value.trim();
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
        
        // Hiển thị hoặc ẩn hàng
        if (matchSearch && matchGenre) {
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
            <td colspan="5" style="text-align:center;padding:40px;color:rgba(255,255,255,0.6);">
                <div style="font-size:32px;margin-bottom:10px">🔍</div>
                <div>Không tìm thấy phim nào phù hợp</div>
            </td>
        `;
        tbody.appendChild(noResults);
    }
}

// Hàm thêm hiệu ứng hover
function addRowHoverEffect() {
    const rows = document.querySelectorAll('.movie-row');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(229,9,20,0.15)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
}
</script>

</body>
</html>
