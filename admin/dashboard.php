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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            color: #333;
            font-size: 28px;
            font-weight: 700;
        }

        .month-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-title {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .card-subtitle {
            font-size: 12px;
            color: #999;
        }

        .card.revenue {
            border-left: 5px solid #667eea;
        }

        .card.tickets {
            border-left: 5px solid #764ba2;
        }

        .card.showtimes {
            border-left: 5px solid #f093fb;
        }

        .top-movie-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            grid-column: 1 / -1;
        }

        .top-movie-container {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .movie-poster {
            flex-shrink: 0;
        }

        .movie-poster img {
            width: 180px;
            height: 250px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .movie-details {
            flex: 1;
        }

        .movie-details h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 700;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .occupancy-table {
            width: 100%;
            border-collapse: collapse;
        }

        .occupancy-table thead {
            background: #f8f9fa;
        }

        .occupancy-table th {
            padding: 12px;
            text-align: left;
            color: #666;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 2px solid #e9ecef;
        }

        .occupancy-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            color: #333;
            font-size: 14px;
        }

        .occupancy-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 6px;
            border-radius: 3px;
            margin-top: 5px;
        }

        .occupancy-table tr:hover {
            background: #f8f9fa;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .occupancy-low { background: #dc3545; }
        .occupancy-medium { background: #ffc107; }
        .occupancy-high { background: #28a745; }

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
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #667eea;
            color: white;
        }

        .format-vnd {
            color: #667eea;
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
                <div class="card-subtitle">Tổng số vé bán được</div>
            </div>

            <div class="card showtimes">
                <div class="card-title">🎬 Suất Chiếu</div>
                <div class="card-value"><?php echo $revenue_data['total_showtimes']; ?></div>
                <div class="card-subtitle">Tổng số suất chiếu</div>
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
                        <div style="width:180px; height:250px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">Không có hình</div>
                    <?php endif; ?>
                </div>
                <div class="movie-details">
                    <h2>🏆 Phim Doanh Thu #1</h2>
                    <h3 style="color: #667eea; margin-bottom: 20px; font-size: 20px;"><?php echo htmlspecialchars($top_movie['ten_phim']); ?></h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">💵 Doanh Thu</span>
                        <span class="detail-value format-vnd"><?php echo number_format($top_movie['movie_revenue']); ?> ₫</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">🎫 Vé Bán</span>
                        <span class="detail-value"><?php echo number_format($top_movie['ticket_sold']); ?> vé</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">💳 Giá Trung Bình</span>
                        <span class="detail-value format-vnd"><?php echo number_format($top_movie['avg_price']); ?> ₫</span>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="top-movie-card" style="text-align: center; color: #999; padding: 40px;">
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
            <h3 style="margin-bottom: 20px; color: #333; font-size: 16px; font-weight: 600;">
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
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="progress-bar">
                                    <?php 
                                    $rate = $occupancy['occupancy_rate'] ?? 0;
                                    $rate_class = 'occupancy-low';
                                    if($rate >= 70) $rate_class = 'occupancy-high';
                                    elseif($rate >= 40) $rate_class = 'occupancy-medium';
                                    ?>
                                    <div class="progress-fill <?php echo $rate_class; ?>" style="width: <?php echo min($rate, 100); ?>%;"></div>
                                </div>
                                <span><?php echo number_format($rate, 2); ?>%</span>
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
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            font: { size: 12, weight: 600 }
                        }
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
                        labels: {
                            font: { size: 12, weight: 600 }
                        }
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
