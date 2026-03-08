<?php
header('Content-Type: application/json');
include 'config/db.php';

// Check for database connection immediately
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra file config/db.php']);
    exit;
}

// Validate suat_id
$suat_id = filter_input(INPUT_GET, 'suat_id', FILTER_VALIDATE_INT);

if (!$suat_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID suất chiếu không hợp lệ.']);
    exit;
}

// Lấy phòng ID từ suất chiếu
$phong_id = null;
$stmt_phong = mysqli_prepare($conn, "SELECT phong_id FROM suat_chieu WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_phong, "i", $suat_id);
mysqli_stmt_execute($stmt_phong);
$result_phong = mysqli_stmt_get_result($stmt_phong);
if ($phong_row = mysqli_fetch_assoc($result_phong)) {
    $phong_id = $phong_row['phong_id'];
}
mysqli_stmt_close($stmt_phong);


if (!$phong_id) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy suất chiếu với ID được cung cấp.']);
    exit;
}

// Lấy trạng thái ghế cho phòng và suất chiếu cụ thể
$sql = "
SELECT 
    ghe.ten_ghe,
    EXISTS (
        SELECT 1 FROM ve 
        WHERE ve.ghe_id = ghe.id 
        AND ve.suat_chieu_id = ?
    ) AS da_dat
FROM ghe
WHERE ghe.phong_id = ?
ORDER BY ghe.ten_ghe
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $suat_id, $phong_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$seats = [];
while($row = mysqli_fetch_assoc($result)){
    // Cast da_dat to an integer for consistent JSON
    $row['da_dat'] = (int)$row['da_dat'];
    $seats[] = $row;
}
mysqli_stmt_close($stmt);

mysqli_close($conn);
echo json_encode($seats);