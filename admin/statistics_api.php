<?php
include "../config/db.php";

header('Content-Type: application/json');

// Lấy tháng từ request (mặc định là tháng hiện tại)
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate input
$month = max(1, min(12, $month));
$year = max(2020, $year);

$start_date = "$year-$month-01";
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

$daily_data = [];
while($row = mysqli_fetch_assoc($daily_revenue_result)) {
    $daily_data[] = [
        'date' => date('d/m', strtotime($row['ngay'])),
        'revenue' => intval($row['daily_revenue']),
        'tickets' => intval($row['daily_tickets'])
    ];
}

// 3. TOP 5 PHIM
$top_5_movies_sql = "
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
$top_5_result = mysqli_query($conn, $top_5_movies_sql);

$top_5_movies = [];
while($row = mysqli_fetch_assoc($top_5_result)) {
    $top_5_movies[] = [
        'name' => substr($row['ten_phim'], 0, 20),
        'tickets_sold' => intval($row['ticket_sold']),
        'revenue' => intval($row['movie_revenue'])
    ];
}

// 4. TỶ LỆ LẤP ĐẦY PHÒNG
$occupancy_sql = "
SELECT 
    pc.id,
    pc.ten_phong,
    r.ten_rap,
    COUNT(DISTINCT sc.id) AS showtimes,
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

$occupancy_data = [];
while($row = mysqli_fetch_assoc($occupancy_result)) {
    $occupancy_data[] = [
        'room_name' => $row['ten_phong'],
        'theater_name' => $row['ten_rap'],
        'showtimes' => intval($row['showtimes']),
        'booked_seats' => intval($row['booked_seats']),
        'occupancy_rate' => floatval($row['occupancy_rate'])
    ];
}

// 5. PHIM DOANH THU TOP 1
$top_movie_sql = "
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
LIMIT 1
";
$top_movie_result = mysqli_query($conn, $top_movie_sql);
$top_movie = mysqli_fetch_assoc($top_movie_result);

$response = [
    'month' => $month,
    'year' => $year,
    'period' => "Tháng $month/$year",
    'kpi' => [
        'total_revenue' => intval($revenue_data['total_revenue'] ?? 0),
        'total_tickets' => intval($revenue_data['total_tickets'] ?? 0),
        'total_showtimes' => intval($revenue_data['total_showtimes'] ?? 0),
        'avg_price_per_ticket' => $revenue_data['total_tickets'] > 0 ? round($revenue_data['total_revenue'] / $revenue_data['total_tickets']) : 0
    ],
    'daily_data' => $daily_data,
    'top_5_movies' => $top_5_movies,
    'occupancy_by_room' => $occupancy_data,
    'top_movie' => $top_movie ? [
        'id' => intval($top_movie['id']),
        'name' => $top_movie['ten_phim'],
        'tickets_sold' => intval($top_movie['ticket_sold']),
        'revenue' => intval($top_movie['movie_revenue'])
    ] : null
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
