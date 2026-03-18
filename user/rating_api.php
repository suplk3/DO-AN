<?php
include "../config/db.php";
session_start();

header('Content-Type: application/json; charset=utf-8');

function respond($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!table_exists($conn, 'ratings')) {
    respond(['ok' => false, 'message' => 'Bảng ratings chưa được tạo. Hãy import SQL mới.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $phim_id = (int)($_GET['phim_id'] ?? 0);
    if ($phim_id <= 0) respond(['ok' => false, 'message' => 'Phim không hợp lệ.']);

    $avgRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM ratings WHERE phim_id = $phim_id"
    ));
    $avg = $avgRow && $avgRow['avg_rating'] !== null ? round((float)$avgRow['avg_rating'], 1) : 0;
    $total = (int)($avgRow['total'] ?? 0);

    $my = 0;
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $mr = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT rating FROM ratings WHERE user_id = $uid AND phim_id = $phim_id LIMIT 1"
        ));
        if ($mr) $my = (int)$mr['rating'];
    }
    respond(['ok' => true, 'avg' => $avg, 'total' => $total, 'my' => $my]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_id'])) {
    respond(['ok' => false, 'message' => 'Bạn cần đăng nhập để đánh giá.']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = $_POST;

$phim_id = (int)($data['phim_id'] ?? 0);
$rating  = (int)($data['rating'] ?? 0);
if ($phim_id <= 0 || $rating < 1 || $rating > 5) {
    respond(['ok' => false, 'message' => 'Dữ liệu không hợp lệ.']);
}

$uid = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn,
    "INSERT INTO ratings (user_id, phim_id, rating)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = CURRENT_TIMESTAMP"
);
mysqli_stmt_bind_param($stmt, "iii", $uid, $phim_id, $rating);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$avgRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM ratings WHERE phim_id = $phim_id"
));
$avg = $avgRow && $avgRow['avg_rating'] !== null ? round((float)$avgRow['avg_rating'], 1) : 0;
$total = (int)($avgRow['total'] ?? 0);

respond(['ok' => true, 'avg' => $avg, 'total' => $total, 'my' => $rating]);
