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
            background: linear-gradient(135deg, #0f172a 0%, #1a2847 50%, #15294c 100%);
            min-height: 100vh;
            padding: 30px;
            color: #e0e7ff;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(26, 40, 71, 0.9) 100%);
            border: 1px solid rgba(139, 92, 246, 0.3);
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            backdrop-filter: blur(10px);
        }

        header h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 900;
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
            color: #c7d2fe;
            font-size: 14px;
        }

        .filter-section select {
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.8);
            border: 1.5px solid rgba(139, 92, 246, 0.5);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            color: #e0e7ff;
            min-width: 140px;
        }

        .filter-section select:hover {
            border-color: #8b5cf6;
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
        }

        .filter-section select:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
        }

        .filter-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }

        .filter-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(139, 92, 246, 0.5);
        }

        .filter-btn:active {
            transform: translateY(-1px);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .kpi-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(26, 40, 71, 0.95) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #8b5cf6, transparent);
        }

        .kpi-card:hover {
            transform: translateY(-8px);
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 25px 60px rgba(139, 92, 246, 0.25), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        .kpi-label {
            font-size: 12px;
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            margin-bottom: 12px;
            opacity: 0.8;
        }

        .kpi-value {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #8b5cf6 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .kpi-change {
            font-size: 13px;
            font-weight: 600;
        }

        .kpi-change.positive {
            color: #34d399;
        }

        .kpi-change.negative {
            color: #f87171;
        }

        .top-movie {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(26, 40, 71, 0.95) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
        }

        .top-movie-content {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .movie-poster img {
            width: 200px;
            height: 280px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 15px 50px rgba(139, 92, 246, 0.3);
            border: 2px solid rgba(139, 92, 246, 0.2);
            transition: all 0.3s;
        }

        .movie-poster img:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 60px rgba(139, 92, 246, 0.4);
        }

        .movie-info h2 {
            font-size: 32px;
            color: #e0e7ff;
            margin-bottom: 8px;
            font-weight: 900;
        }

        .movie-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #000;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 800;
            margin-bottom: 25px;
            font-size: 13px;
            box-shadow: 0 8px 20px rgba(251, 191, 36, 0.3);
        }

        .movie-stat {
            display: flex;
            justify-content: space-between;
            padding: 18px 0;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            font-size: 15px;
        }

        .movie-stat:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #a5b4fc;
            font-weight: 500;
        }

        .stat-value {
            color: #fbbf24;
            font-weight: 800;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(26, 40, 71, 0.95) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
        }

        .chart-card h3 {
            font-size: 16px;
            color: #e0e7ff;
            font-weight: 800;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(26, 40, 71, 0.95) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
            overflow-x: auto;
            backdrop-filter: blur(10px);
        }

        .table-card h3 {
            font-size: 16px;
            color: #e0e7ff;
            font-weight: 800;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(139, 92, 246, 0.1);
            border-bottom: 1px solid rgba(139, 92, 246, 0.3);
        }

        th {
            padding: 18px;
            text-align: left;
            color: #a5b4fc;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            font-size: 14px;
            color: #e0e7ff;
        }

        tr:hover {
            background: rgba(139, 92, 246, 0.1);
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .progress-bar {
            flex: 1;
            height: 6px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }

        .progress-high {
            background: linear-gradient(90deg, #34d399 0%, #10b981 100%);
            box-shadow: 0 0 10px rgba(52, 211, 153, 0.5);
        }

        .progress-medium {
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }

        .progress-low {
            background: linear-gradient(90deg, #f87171 0%, #ef4444 100%);
            box-shadow: 0 0 10px rgba(248, 113, 113, 0.5);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 15px;
        }

        .back-link:hover {
            color: #8b5cf6;
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

            body {
                padding: 15px;
            }

            header {
                padding: 20px;
            }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(139, 92, 246, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #8b5cf6 0%, #6366f1 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #9d5eff 0%, #7c3aed 100%);
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
                        <select name="month" id="month">
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == $selected_month ? 'selected' : ''; ?>>
                                    Tháng <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label>Năm:</label>
                        <select name="year" id="year">
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

                    <button type="submit" class="filter-btn">📅 Xem Thống Kê</button>
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
                <div class="kpi-change">Giá TB: <?php echo $revenue_data['total_tickets'] > 0 ? number_format($revenue_data['total_revenue'] / $revenue_data['total_tickets']) : 0; ?> ₫</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">🎬 Suất Chiếu</div>
                <div class="kpi-value"><?php echo $revenue_data['total_showtimes']; ?></div>
                <div class="kpi-change" style="color: #a5b4fc;"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
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
        // Chart.js default settings cho dark theme
        Chart.defaults.color = '#a5b4fc';
        Chart.defaults.borderColor = 'rgba(139, 92, 246, 0.2)';

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($daily_dates); ?>,
                datasets: [{
                    label: 'Doanh Thu (₫)',
                    data: <?php echo json_encode($daily_revenues); ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#1e293b',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#c084fc',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: { 
                            font: { size: 12, weight: 700 },
                            color: '#a5b4fc'
                        }
                    },
                    filler: {
                        propagate: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(139, 92, 246, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#a5b4fc',
                            font: { weight: 600 },
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#a5b4fc',
                            font: { weight: 600 }
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
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        '#8b5cf6',
                        '#a855f7',
                        '#ec4899',
                        '#3b82f6',
                        '#22c55e'
                    ],
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: { 
                            font: { size: 12, weight: 700 },
                            color: '#a5b4fc'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(139, 92, 246, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#a5b4fc',
                            font: { weight: 600 },
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#a5b4fc',
                            font: { weight: 600 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
