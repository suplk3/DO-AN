<?php
include "../config/db.php";
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Chưa đăng nhập.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!table_exists($conn, 'notifications')) {
    echo json_encode(['ok' => false, 'message' => 'Bảng notifications chưa được tạo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = $_POST;

$action = $data['action'] ?? 'read';
if ($action === 'read_all') {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'ID không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}
mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id=$id AND user_id=$uid");
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
