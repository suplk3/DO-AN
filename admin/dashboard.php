<?php
include "check_admin.php";
include "../config/db.php";

// Lấy tháng hiện tại
$current_month = date('m');
$current_year = date('Y');
$start_date = "$current_year-$current_month-01";
$end_date = date('Y-m-t', strtotime($start_date));

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

// 2. TỶ LỆ LẤP ĐẦY PHÒNG CHIẾU
$occupancy_sql = "
SELECT 
    pc.id,
    pc.ten_phong,
    r.ten_rap,
    COUNT(DISTINCT sc.id) AS total_seats_available,
    COUNT(DISTINCT ve.id) AS booked_seats,
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

// 3. PHIM DOANH THU TOP 1
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
LIMIT 10
";
$top_movie_result = mysqli_query($conn, $top_movie_sql);

// 4. DOANH THU THEO NGÀY
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

// Chuẩn bị dữ liệu cho chart
$daily_dates = [];
$daily_revenues = [];
while($row = mysqli_fetch_assoc($daily_revenue_result)) {
    $daily_dates[] = date('d/m', strtotime($row['ngay']));
    $daily_revenues[] = $row['daily_revenue'];
}

// 5. TOP 5 PHIM
$top_5_movies_sql = "
SELECT 
    p.id,
    p.ten_phim,
    p.poster,
    COUNT(ve.id) AS ticket_sold,
    COALESCE(SUM(sc.gia), 0) AS movie_revenue
FROM phim p
LEFT JOIN suat_chieu sc ON sc.phim_id = p.id AND DATE(sc.ngay) >= '$start_date' AND DATE(sc.ngay) <= '$end_date'
LEFT JOIN ve ON ve.suat_chieu_id = sc.id
GROUP BY p.id, p.ten_phim, p.poster
ORDER BY movie_revenue DESC
LIMIT 5
";
$top_5_result = mysqli_query($conn, $top_5_movies_sql);

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
    <title>Dashboard Thống Kê</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(26, 40, 71, 0.9) 100%);
            border: 1px solid rgba(139, 92, 246, 0.3);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        header h1 {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 32px;
            font-weight: 900;
        }

        .month-info {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(26, 40, 71, 0.95) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #8b5cf6, transparent);
        }

        .card:hover {
            transform: translateY(-8px);
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 25px 60px rgba(139, 92, 246, 0.25), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        .card-title {
            font-size: 12px;
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            font-weight: 700;
            opacity: 0.8;
        }

        .card-value {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #8b5cf6 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .card-subtitle {
            font-size: 13px;
            color: #a5b4fc;
            font-weight: 500;
        }

        .card.revenue {
            border-left: none;
        }

        .card.tickets {
            border-left: none;
        }

        .card.showtimes {
            border-left: none;
        }

        .top-movie-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(26, 40, 71, 0.95) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
            grid-column: 1 / -1;
            backdrop-filter: blur(10px);
        }

        .top-movie-container {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .movie-poster {
            flex-shrink: 0;
        }

        .movie-poster img {
            width: 200px;
            height: 280px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 15px 50px rgba(139, 92, 246, 0.3);
            border: 2px solid rgba(139, 92, 246, 0.2);
            transition: all 0.3s;
        }

        .movie-poster img:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 60px rgba(139, 92, 246, 0.4);
        }

        .movie-details {
            flex: 1;
        }

        .movie-details h2 {
            font-size: 32px;
            color: #e0e7ff;
            margin-bottom: 8px;
            font-weight: 900;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 18px 0;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            font-size: 15px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #a5b4fc;
            font-weight: 500;
        }

        .detail-value {
            color: #fbbf24;
            font-weight: 800;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
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
            margin-bottom: 25px;
            color: #e0e7ff;
            font-size: 16px;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .occupancy-table {
            width: 100%;
            border-collapse: collapse;
        }

        .occupancy-table thead {
            background: rgba(139, 92, 246, 0.1);
            border-bottom: 1px solid rgba(139, 92, 246, 0.3);
        }

        .occupancy-table th {
            padding: 18px;
            text-align: left;
            color: #a5b4fc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .occupancy-table td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: #e0e7ff;
            font-size: 14px;
        }

        .occupancy-table tr:hover {
            background: rgba(139, 92, 246, 0.1);
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

        .progress-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .occupancy-low { 
            background: linear-gradient(90deg, #f87171 0%, #ef4444 100%);
            box-shadow: 0 0 10px rgba(248, 113, 113, 0.5);
        }
        .occupancy-medium { 
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }
        .occupancy-high { 
            background: linear-gradient(90deg, #34d399 0%, #10b981 100%);
            box-shadow: 0 0 10px rgba(52, 211, 153, 0.5);
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(139, 92, 246, 0.2);
            color: #a5b4fc;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .back-button:hover {
            background: rgba(139, 92, 246, 0.3);
            color: #c084fc;
            border-color: rgba(139, 92, 246, 0.5);
        }

        .format-vnd {
            color: #fbbf24;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }

            .top-movie-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .movie-poster {
                flex-shrink: 0;
            }

            .movie-details {
                flex: 1;
                width: 100%;
            }

            .detail-row {
                justify-content: center;
                flex-direction: column;
                text-align: center;
            }

            header {
                flex-direction: column;
                gap: 10px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(139, 92, 246, 0.2);
            color: #a5b4fc;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .back-button:hover {
            background: rgba(139, 92, 246, 0.3);
            color: #c084fc;
            border-color: rgba(139, 92, 246, 0.5);
        }

        .format-vnd {
            color: #fbbf24;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="suat_chieu.php" class="back-button">← Quay lại</a>

        <header>
            <h1>📊 Dashboard Thống Kê</h1>
            <div class="month-info">
                Tháng <?php echo $current_month; ?>/<?php echo $current_year; ?>
            </div>
        </header>

        <!-- KPI Cards -->
        <div class="dashboard-grid">
            <div class="card revenue">
                <div class="card-title">💰 Doanh Thu Tháng</div>
                <div class="card-value format-vnd"><?php echo number_format($revenue_data['total_revenue']); ?> ₫</div>
                <div class="card-subtitle">Từ <?php echo count($daily_dates); ?> ngày có suất chiếu</div>
            </div>

            <div class="card tickets">
                <div class="card-title">🎫 Vé Bán</div>
                <div class="card-value"><?php echo number_format($revenue_data['total_tickets']); ?></div>
                <div class="card-subtitle">Giá TB: <?php echo $revenue_data['total_tickets'] > 0 ? number_format($revenue_data['total_revenue'] / $revenue_data['total_tickets']) : 0; ?> ₫</div>
            </div>

            <div class="card showtimes">
                <div class="card-title">🎬 Suất Chiếu</div>
                <div class="card-value"><?php echo $revenue_data['total_showtimes']; ?></div>
                <div class="card-subtitle" style="color: #a5b4fc;"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
            </div>
        </div>

        <!-- Top Movie -->
        <?php 
        $top_movie = mysqli_fetch_assoc(mysqli_query($conn, $top_movie_sql));
        if($top_movie && $top_movie['movie_revenue'] > 0): 
        ?>
        <div class="top-movie-card">
            <div class="top-movie-container">
                <div class="movie-poster">
                    <?php if($top_movie['poster']): ?>
                        <img src="../assets/images/posts/<?php echo htmlspecialchars($top_movie['poster']); ?>" alt="<?php echo htmlspecialchars($top_movie['ten_phim']); ?>">
                    <?php else: ?>
                        <div style="width:200px; height:280px; background: rgba(139, 92, 246, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #a5b4fc; border: 1px solid rgba(139, 92, 246, 0.3);">Không có hình</div>
                    <?php endif; ?>
                </div>
                <div class="movie-details">
                    <div style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #000; padding: 10px 20px; border-radius: 25px; font-weight: 800; margin-bottom: 25px; font-size: 13px; box-shadow: 0 8px 20px rgba(251, 191, 36, 0.3);">🏆 PHIM DOANH THU #1</div>
                    <h2><?php echo htmlspecialchars($top_movie['ten_phim']); ?></h2>
                    
                    <div class="detail-row">
                        <span class="detail-label">💵 Doanh Thu Tháng</span>
                        <span class="detail-value format-vnd"><?php echo number_format($top_movie['movie_revenue']); ?> ₫</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">🎫 Vé Đã Bán</span>
                        <span class="detail-value"><?php echo number_format($top_movie['ticket_sold']); ?> vé</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">💳 Giá Vé Trung Bình</span>
                        <span class="detail-value format-vnd"><?php echo number_format($top_movie['avg_price']); ?> ₫</span>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="top-movie-card" style="text-align: center; color: #a5b4fc; padding: 40px;">
            <p>Chưa có dữ liệu doanh thu cho tháng này</p>
        </div>
        <?php endif; ?>

        <!-- Charts -->
        <div class="charts-container">
            <div class="chart-card">
                <h3>📈 Doanh Thu Theo Ngày</h3>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>🏅 Top 5 Phim</h3>
                <canvas id="topMoviesChart"></canvas>
            </div>
        </div>

        <!-- Occupancy Rate Table -->
        <div class="card">
            <h3 style="margin-bottom: 20px; color: #e0e7ff; font-size: 16px; font-weight: 800; background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                📍 Tỷ Lệ Lấp Đầy Phòng Chiếu
            </h3>
            <table class="occupancy-table">
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
                        <td><?php echo htmlspecialchars($occupancy['ten_rap']); ?></td>
                        <td><?php echo htmlspecialchars($occupancy['ten_phong']); ?></td>
                        <td><?php echo $occupancy['total_seats_available']; ?></td>
                        <td><?php echo $occupancy['booked_seats']; ?></td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <?php 
                                    $rate = $occupancy['occupancy_rate'] ?? 0;
                                    $rate_class = 'occupancy-low';
                                    if($rate >= 70) $rate_class = 'occupancy-high';
                                    elseif($rate >= 40) $rate_class = 'occupancy-medium';
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
