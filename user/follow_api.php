<?php
/* follow_api.php */
session_start();
include "../config/db.php";
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Chưa đăng nhập']); exit; }
$me  = (int)$_SESSION['user_id'];
$d   = json_decode(file_get_contents('php://input'), true);
$fid = (int)($d['following_id'] ?? 0);
if (!$fid || $fid === $me) { echo json_encode(['error'=>'Không hợp lệ']); exit; }
$exist = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT 1 FROM follows WHERE follower_id=$me AND following_id=$fid"
));
if ($exist) {
    mysqli_query($conn,"DELETE FROM follows WHERE follower_id=$me AND following_id=$fid");
    echo json_encode(['action'=>'unfollowed']);
} else {
    mysqli_query($conn,"INSERT INTO follows (follower_id,following_id) VALUES ($me,$fid)");
    echo json_encode(['action'=>'followed']);
}
