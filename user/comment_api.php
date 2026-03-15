<?php
/* comment_api.php */
session_start();
include "../config/db.php";
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Chưa đăng nhập']); exit; }
$me = (int)$_SESSION['user_id'];

function time_ago($dt) {
    $d = time()-strtotime($dt);
    if($d<60) return 'Vừa xong';
    if($d<3600) return floor($d/60).' phút trước';
    if($d<86400) return floor($d/3600).' giờ trước';
    return date('d/m/Y',strtotime($dt));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = in_array($_GET['target_type']??'',['post','phim']) ? $_GET['target_type'] : null;
    $tid  = (int)($_GET['target_id'] ?? 0);
    if (!$type || !$tid) { echo json_encode(['comments'=>[]]); exit; }
    $res = mysqli_query($conn,"
        SELECT c.*, u.ten, u.avatar
        FROM comments c JOIN users u ON c.user_id=u.id
        WHERE c.target_type='$type' AND c.target_id=$tid AND c.parent_id IS NULL
        ORDER BY c.created_at ASC LIMIT 50
    ");
    $list = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $r['time_ago'] = time_ago($r['created_at']);
        $list[] = $r;
    }
    echo json_encode(['comments'=>$list]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d    = json_decode(file_get_contents('php://input'), true);
    $type = in_array($d['target_type']??'',['post','phim']) ? $d['target_type'] : null;
    $tid  = (int)($d['target_id'] ?? 0);
    $text = trim($d['noi_dung'] ?? '');
    if (!$type || !$tid || !$text) { echo json_encode(['ok'=>false]); exit; }
    $text = mb_substr($text, 0, 1000);
    $s = mysqli_prepare($conn,"INSERT INTO comments (user_id,target_type,target_id,noi_dung) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($s,'isis',$me,$type,$tid,$text);
    $ok = mysqli_stmt_execute($s);
    echo json_encode(['ok'=>$ok, 'id'=>(int)mysqli_insert_id($conn)]);
    exit;
}
