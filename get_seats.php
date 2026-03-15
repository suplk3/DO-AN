<?php
/**
 * get_seats.php — REST fallback
 * Dùng khi SSE không khả dụng (dự phòng)
 * Đặt ở: gốc project (ngang với thư mục user/)
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

include __DIR__ . '/config/db.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi kết nối DB']);
    exit;
}

$suat_id = filter_input(INPUT_GET, 'suat_id', FILTER_VALIDATE_INT);
if (!$suat_id) {
    http_response_code(400);
    echo json_encode(['error' => 'suat_id không hợp lệ']);
    exit;
}

// Lấy phong_id
$stmt = mysqli_prepare($conn, "SELECT phong_id FROM suat_chieu WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $suat_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy suất chiếu']);
    exit;
}
$phong_id = (int)$row['phong_id'];

// Lấy toàn bộ ghế + trạng thái
$sql = "
    SELECT g.ten_ghe,
           EXISTS(SELECT 1 FROM ve WHERE ve.ghe_id = g.id AND ve.suat_chieu_id = ?) AS da_dat
    FROM ghe g
    WHERE g.phong_id = ?
    ORDER BY LEFT(g.ten_ghe,1), CAST(SUBSTRING(g.ten_ghe,2) AS UNSIGNED)
";
$stmt2 = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt2, 'ii', $suat_id, $phong_id);
mysqli_stmt_execute($stmt2);
$res = mysqli_stmt_get_result($stmt2);

$seats = [];
while ($r = mysqli_fetch_assoc($res)) {
    $seats[] = ['ten_ghe' => $r['ten_ghe'], 'da_dat' => (int)$r['da_dat']];
}
mysqli_stmt_close($stmt2);
mysqli_close($conn);

echo json_encode($seats);