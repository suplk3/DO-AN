<?php
include "check_admin.php";
include "../config/db.php";

// Lấy tháng và năm từ form
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate
$selected_month = max(1, min(12, $selected_month));
$selected_year = max(2020, $selected_year);

$start_date = "$selected_year-$selected_month-01";
$end_date = date('Y-m-t', strtotime($start_date));

// Lấy tất cả năm có dữ liệu
$years_sql = "SELECT DISTINCT YEAR(ngay) as year FROM suat_chieu ORDER BY year DESC";
$years_result = mysqli_query($conn, $years_sql);
$available_years = [];
while($row = mysqli_fetch_assoc($years_result)) {
    $available_years[] = $row['year'];
}

// 1. DOANH THU THÁNG
$revenue_sql = "
SELECT 
    COALESCE(SUM(sc.gia), 0) AS total_revenue,
    COUNT(ve.id) AS total_tickets,
    COUNT(DISTINCT sc.id) AS total_showtimes
FROM ve
JOIN suat_chieu sc ON ve.suat_chieu_id = sc.id
WHERE DATE(sc.ngay) >= '$start_date' AND DATE(sc.ngay) <= '$end_date'
";
$revenue_result = mysqli_query($conn, $revenue_sql);
$revenue_data = mysqli_fetch_assoc($revenue_result);

// So sánh với tháng trước
$prev_month = $selected_month - 1;
$prev_year = $selected_year;
if($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$prev_start_date = "$prev_year-$prev_month-01";
$prev_end_date = date('Y-m-t', strtotime($prev_start_date));

$prev_revenue_sql = "
SELECT COALESCE(SUM(sc.gia), 0) AS total_revenue
FROM ve
JOIN suat_chieu sc ON ve.suat_chieu_id = sc.id
WHERE DATE(sc.ngay) >= '$prev_start_date' AND DATE(sc.ngay) <= '$prev_end_date'
";
$prev_revenue_result = mysqli_query($conn, $prev_revenue_sql);
$prev_revenue_data = mysqli_fetch_assoc($prev_revenue_result);

$prev_revenue = $prev_revenue_data['total_revenue'] ?? 0;
$current_revenue = $revenue_data['total_revenue'] ?? 0;
$revenue_change = $prev_revenue > 0 ? (($current_revenue - $prev_revenue) / $prev_revenue * 100) : 0;

// 2. DOANH THU THEO NGÀY
$daily_revenue_sql = "
SELECT 
    DATE(sc.ngay) as ngay,
    COALESCE(SUM(sc.gia), 0) AS daily_revenue,
    COUNT(ve.id) AS daily_tickets
FROM ve
JOIN suat_chieu sc ON ve.suat_chieu_id = sc.id
WHERE DATE(sc.ngay) >= '$start_date' AND DATE(sc.ngay) <= '$end_date'
GROUP BY DATE(sc.ngay)
ORDER BY ngay
";
$daily_revenue_result = mysqli_query($conn, $daily_revenue_sql);

$daily_dates = [];
$daily_revenues = [];
while($row = mysqli_fetch_assoc($daily_revenue_result)) {
    $daily_dates[] = date('d/m', strtotime($row['ngay']));
    $daily_revenues[] = $row['daily_revenue'];
}

// 3. TỶ LỄ LẤP ĐẦY
$occupancy_sql = "
SELECT 
    pc.id,
    pc.ten_phong,
    r.ten_rap,
    COUNT(DISTINCT sc.id) AS showtimes,
    COUNT(DISTINCT ve.id) AS booked_seats,
    COUNT(DISTINCT g.id) AS total_seats,
    ROUND((COUNT(DISTINCT ve.id) / NULLIF(COUNT(DISTINCT g.id), 0) * 100), 2) AS occupancy_rate
FROM phong_chieu pc
LEFT JOIN rap r ON pc.rap_id = r.id
LEFT JOIN suat_chieu sc ON sc.phong_id = pc.id AND DATE(sc.ngay) >= '$start_date' AND DATE(sc.ngay) <= '$end_date'
LEFT JOIN ve ON ve.suat_chieu_id = sc.id
LEFT JOIN ghe g ON g.phong_id = pc.id
GROUP BY pc.id, pc.ten_phong, r.ten_rap
ORDER BY occupancy_rate DESC
";
$occupancy_result = mysqli_query($conn, $occupancy_sql);

// 4. TOP PHIM
$top_movie_sql = "
SELECT 
    p.id,
    p.ten_phim,
    p.poster,
    COUNT(ve.id) AS ticket_sold,
    COALESCE(SUM(sc.gia), 0) AS movie_revenue,
    ROUND(COALESCE(SUM(sc.gia), 0) / NULLIF(COUNT(ve.id), 1), 0) AS avg_price
FROM phim p
LEFT JOIN suat_chieu sc ON sc.phim_id = p.id AND DATE(sc.ngay) >= '$start_date' AND DATE(sc.ngay) <= '$end_date'
LEFT JOIN ve ON ve.suat_chieu_id = sc.id
GROUP BY p.id, p.ten_phim, p.poster
HAVING movie_revenue > 0
ORDER BY movie_revenue DESC
LIMIT 1
";
$top_movie_result = mysqli_query($conn, $top_movie_sql);
$top_movie = mysqli_fetch_assoc($top_movie_result);

// 5. TOP 5 PHIM
$top_5_sql = "
SELECT 
    p.id,
    p.ten_phim,
    COUNT(ve.id) AS ticket_sold,
    COALESCE(SUM(sc.gia), 0) AS movie_revenue
FROM phim p
LEFT JOIN suat_chieu sc ON sc.phim_id = p.id AND DATE(sc.ngay) >= '$start_date' AND DATE(sc.ngay) <= '$end_date'
LEFT JOIN ve ON ve.suat_chieu_id = sc.id
GROUP BY p.id, p.ten_phim
HAVING movie_revenue > 0
ORDER BY movie_revenue DESC
LIMIT 5
";
$top_5_result = mysqli_query($conn, $top_5_sql);

$top_5_names = [];
$top_5_revenues = [];
while($row = mysqli_fetch_assoc($top_5_result)) {
    $top_5_names[] = substr($row['ten_phim'], 0, 15) . (strlen($row['ten_phim']) > 15 ? '...' : '');
    $top_5_revenues[] = $row['movie_revenue'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Thống Kê - Rạp Chiếu Phim</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        header h1 {
            font-size: 32px;
            color: #333;
            font-weight: 800;
            flex: 1;
        }

        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-section label {
            font-weight: 600;
            color: #666;
        }

        .filter-section select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-section select:hover {
            border-color: #667eea;
        }

        .filter-section select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .kpi-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .kpi-value {
            font-size: 36px;
            font-weight: 800;
            color: #333;
            margin-bottom: 5px;
        }

        .kpi-change {
            font-size: 12px;
            font-weight: 600;
        }

        .kpi-change.positive {
            color: #22c55e;
        }

        .kpi-change.negative {
            color: #ef4444;
        }

        .top-movie {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .top-movie-content {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .movie-poster img {
            width: 200px;
            height: 280px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .movie-info h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .movie-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .movie-stat {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .movie-stat:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        .stat-value {
            color: #333;
            font-weight: 700;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            font-size: 16px;
            color: #333;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .table-card h3 {
            font-size: 16px;
            color: #333;
            font-weight: 700;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 15px;
            text-align: left;
            color: #666;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
        }

        tr:hover {
            background: #f9fafb;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }

        .progress-high {
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
        }

        .progress-medium {
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
        }

        .progress-low {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        @media (max-width: 1024px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }

            .top-movie-content {
                flex-direction: column;
                text-align: center;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .filter-section {
                flex-direction: column;
                width: 100%;
            }

            .filter-section select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="suat_chieu.php" class="back-link">← Quay lại quản lý suất chiếu</a>

        <header>
            <h1>📊 Dashboard Thống Kê</h1>
            <div class="filter-section">
                <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label>Tháng:</label>
                        <select name="month" id="month" style="margin-left: 10px;">
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == $selected_month ? 'selected' : ''; ?>>
                                    Tháng <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label>Năm:</label>
                        <select name="year" id="year" style="margin-left: 10px;">
                            <?php 
                            $current_year = date('Y');
                            for($year = $current_year; $year >= 2020; $year--):
                            ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="filter-btn">📅 Xem</button>
                </form>
            </div>
        </header>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">💰 Doanh Thu</div>
                <div class="kpi-value"><?php echo number_format($revenue_data['total_revenue']); ?> ₫</div>
                <div class="kpi-change <?php echo $revenue_change >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $revenue_change >= 0 ? '📈' : '📉'; ?> 
                    <?php echo number_format(abs($revenue_change), 1); ?>% so với tháng trước
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">🎫 Vé Bán</div>
                <div class="kpi-value"><?php echo number_format($revenue_data['total_tickets']); ?></div>
                <div class="kpi-change">Trung bình: <?php echo $revenue_data['total_tickets'] > 0 ? number_format($revenue_data['total_revenue'] / $revenue_data['total_tickets']) : 0; ?> ₫/vé</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">🎬 Suất Chiếu</div>
                <div class="kpi-value"><?php echo $revenue_data['total_showtimes']; ?></div>
                <div class="kpi-change"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
            </div>
        </div>

        <!-- Top Movie -->
        <?php if($top_movie && $top_movie['movie_revenue'] > 0): ?>
        <div class="top-movie">
            <div class="top-movie-content">
                <div class="movie-poster">
                    <?php if($top_movie['poster']): ?>
                        <img src="../assets/images/posts/<?php echo htmlspecialchars($top_movie['poster']); ?>" alt="<?php echo htmlspecialchars($top_movie['ten_phim']); ?>">
                    <?php else: ?>
                        <div style="width: 200px; height: 280px; background: #e9ecef; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #999;">Không có hình</div>
                    <?php endif; ?>
                </div>
                <div class="movie-info" style="flex: 1;">
                    <div class="movie-badge">🏆 PHIM DOANH THU #1</div>
                    <h2><?php echo htmlspecialchars($top_movie['ten_phim']); ?></h2>
                    
                    <div class="movie-stat">
                        <span class="stat-label">💵 Doanh Thu Tháng</span>
                        <span class="stat-value"><?php echo number_format($top_movie['movie_revenue']); ?> ₫</span>
                    </div>
                    <div class="movie-stat">
                        <span class="stat-label">🎫 Vé Đã Bán</span>
                        <span class="stat-value"><?php echo number_format($top_movie['ticket_sold']); ?> vé</span>
                    </div>
                    <div class="movie-stat">
                        <span class="stat-label">💳 Giá Vé Trung Bình</span>
                        <span class="stat-value"><?php echo number_format($top_movie['avg_price']); ?> ₫</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>📈 Doanh Thu Theo Ngày</h3>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>🏅 Top 5 Phim Có Doanh Thu Cao</h3>
                <canvas id="topMoviesChart"></canvas>
            </div>
        </div>

        <!-- Occupancy Table -->
        <div class="table-card">
            <h3>📍 Tỷ Lệ Lấp Đầy Phòng Chiếu</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rạp</th>
                        <th>Phòng</th>
                        <th>Suất Chiếu</th>
                        <th>Vé Bán</th>
                        <th>Tỷ Lệ Lấp Đầy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($occupancy = mysqli_fetch_assoc($occupancy_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($occupancy['ten_rap'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($occupancy['ten_phong']); ?></td>
                        <td><?php echo $occupancy['showtimes']; ?></td>
                        <td><?php echo $occupancy['booked_seats']; ?></td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <?php 
                                    $rate = $occupancy['occupancy_rate'] ?? 0;
                                    $rate_class = 'progress-low';
                                    if($rate >= 70) $rate_class = 'progress-high';
                                    elseif($rate >= 40) $rate_class = 'progress-medium';
                                    ?>
                                    <div class="progress-fill <?php echo $rate_class; ?>" style="width: <?php echo min($rate, 100); ?>%;"></div>
                                </div>
                                <span style="min-width: 50px; text-align: right; font-weight: 700;"><?php echo number_format($rate, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($daily_dates); ?>,
                datasets: [{
                    label: 'Doanh Thu (₫)',
                    data: <?php echo json_encode($daily_revenues); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: { font: { size: 12, weight: 600 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            }
                        }
                    }
                }
            }
        });

        // Top Movies Chart
        const topMoviesCtx = document.getElementById('topMoviesChart').getContext('2d');
        new Chart(topMoviesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($top_5_names); ?>,
                datasets: [{
                    label: 'Doanh Thu (₫)',
                    data: <?php echo json_encode($top_5_revenues); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#00f2fe'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: { font: { size: 12, weight: 600 } }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
