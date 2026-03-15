<?php
/* reaction_api.php — POST JSON: {target_type, target_id, loai} */
session_start();
include "../config/db.php";
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Chưa đăng nhập']); exit; }
$me = (int)$_SESSION['user_id'];
$d  = json_decode(file_get_contents('php://input'), true);
$type   = in_array($d['target_type']??'', ['post','phim']) ? $d['target_type'] : null;
$tid    = (int)($d['target_id'] ?? 0);
$loai   = in_array($d['loai']??'', ['like','love','haha','wow','sad','angry']) ? $d['loai'] : 'like';
if (!$type || !$tid) { echo json_encode(['error'=>'Thiếu dữ liệu']); exit; }

// Toggle: nếu đã react cùng loại → xoá, khác loại → update
$exist = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, loai FROM reactions WHERE user_id=$me AND target_type='$type' AND target_id=$tid"
));
if ($exist) {
    if ($exist['loai'] === $loai) {
        mysqli_query($conn, "DELETE FROM reactions WHERE id={$exist['id']}");
        $action = 'removed';
    } else {
        mysqli_query($conn, "UPDATE reactions SET loai='$loai' WHERE id={$exist['id']}");
        $action = 'updated';
    }
} else {
    $s = mysqli_prepare($conn, "INSERT INTO reactions (user_id,target_type,target_id,loai) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($s,'siss',$me,$type,$tid,$loai);
    mysqli_stmt_execute($s);
    $action = 'added';
}
$total = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM reactions WHERE target_type='$type' AND target_id=$tid"
))['c'];
echo json_encode(['action'=>$action,'total'=>$total]);
