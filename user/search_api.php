<?php
// user/search_api.php
// Trả về JSON danh sách phim khớp với từ khóa
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

include "../config/db.php";

$q    = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all'; // 'showing' | 'upcoming' | 'all'

if (mb_strlen($q) < 1) {
    echo json_encode(['results' => [], 'total' => 0]);
    exit;
}

$today  = date('Y-m-d');
$q_safe = mysqli_real_escape_string($conn, $q);

// Điều kiện lọc theo type
$where_type = '';
if ($type === 'showing')  $where_type = "AND (ngay_khoi_chieu IS NULL OR ngay_khoi_chieu <= '$today')";
if ($type === 'upcoming') $where_type = "AND ngay_khoi_chieu > '$today'";

$sql = "SELECT id, ten_phim, poster, the_loai, thoi_luong, ngay_khoi_chieu, mo_ta
        FROM phim
        WHERE (ten_phim LIKE '%$q_safe%' OR the_loai LIKE '%$q_safe%' OR mo_ta LIKE '%$q_safe%')
        $where_type
        ORDER BY
            CASE WHEN ten_phim LIKE '$q_safe%' THEN 0    -- tên bắt đầu bằng keyword → ưu tiên
                 WHEN ten_phim LIKE '%$q_safe%' THEN 1
                 ELSE 2 END,
            id DESC
        LIMIT 20";

$result  = mysqli_query($conn, $sql);
$movies  = [];

while ($row = mysqli_fetch_assoc($result)) {
    $is_upcoming = !empty($row['ngay_khoi_chieu']) && $row['ngay_khoi_chieu'] > $today;
    $movies[] = [
        'id'              => (int)$row['id'],
        'ten_phim'        => $row['ten_phim'],
        'poster'          => $row['poster'],
        'the_loai'        => $row['the_loai'] ?? '',
        'thoi_luong'      => $row['thoi_luong'] ?? '',
        'ngay_khoi_chieu' => $row['ngay_khoi_chieu'] ?? null,
        'is_upcoming'     => $is_upcoming,
        'mo_ta_short'     => mb_strimwidth($row['mo_ta'] ?? '', 0, 80, '...'),
    ];
}

echo json_encode(['results' => $movies, 'total' => count($movies)]);