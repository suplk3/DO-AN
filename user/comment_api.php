<?php
session_start();
include "../config/db.php";
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Chưa đăng nhập']); exit; }
$me = (int)$_SESSION['user_id'];

function time_ago($dt) {
    // Lấy thời gian hiện tại trực tiếp từ MySQL để đảm bảo cùng timezone với created_at
    global $conn;
    $now_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT UNIX_TIMESTAMP(NOW()) AS now_ts, UNIX_TIMESTAMP('$dt') AS dt_ts"));
    $d = max(0, (int)$now_row['now_ts'] - (int)$now_row['dt_ts']);
    if ($d < 60)     return 'Vừa xong';
    if ($d < 3600)   return floor($d/60).' phút trước';
    if ($d < 86400)  return floor($d/3600).' giờ trước';
    if ($d < 604800) return floor($d/86400).' ngày trước';
    return date('d/m/Y', strtotime($dt));
}

// ── GET: lấy comments + replies lồng nhau ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = in_array($_GET['target_type']??'', ['post','phim']) ? $_GET['target_type'] : null;
    $tid  = (int)($_GET['target_id'] ?? 0);
    if (!$type || !$tid) { echo json_encode(['comments'=>[]]); exit; }

    $res = mysqli_query($conn, "
        SELECT c.*, u.ten, u.avatar
        FROM comments c JOIN users u ON c.user_id = u.id
        WHERE c.target_type='$type' AND c.target_id=$tid
        ORDER BY c.created_at ASC LIMIT 200
    ");

    $all = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $r['time_ago'] = time_ago($r['created_at']);
        $r['replies']  = [];
        $all[$r['id']] = $r;
    }

    // Dùng reference để replies được gắn đúng vào comment cha
    foreach ($all as $id => $item) {
        if ($item['parent_id'] && isset($all[$item['parent_id']])) {
            $all[$item['parent_id']]['replies'][] = &$all[$id];
        }
    }

    // Chỉ lấy comment gốc (không có parent) sau khi đã group xong
    $roots = [];
    foreach ($all as $item) {
        if (!$item['parent_id']) {
            $roots[] = $item;
        }
    }

    echo json_encode(['comments' => array_values($roots)]);
    exit;
}

// ── POST: thêm comment hoặc reply ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action']??'') !== 'delete') {
    $d         = json_decode(file_get_contents('php://input'), true);
    $type      = in_array($d['target_type']??'', ['post','phim']) ? $d['target_type'] : null;
    $tid       = (int)($d['target_id']  ?? 0);
    $text      = trim($d['noi_dung']    ?? '');
    $parent_id = !empty($d['parent_id']) ? (int)$d['parent_id'] : null;

    if (!$type || !$tid || !$text) { echo json_encode(['ok'=>false]); exit; }
    $text = mb_substr($text, 0, 1000);

    // Chỉ cho reply 1 cấp — validate parent thuộc đúng target và là comment gốc
    if ($parent_id) {
        $chk = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM comments
             WHERE id=$parent_id AND target_type='$type' AND target_id=$tid AND parent_id IS NULL"
        ));
        if (!$chk) $parent_id = null;
    }

    if ($parent_id) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO comments (user_id,target_type,target_id,parent_id,noi_dung) VALUES (?,?,?,?,?)"
        );
        mysqli_stmt_bind_param($stmt, 'isiis', $me, $type, $tid, $parent_id, $text);
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO comments (user_id,target_type,target_id,noi_dung) VALUES (?,?,?,?)"
        );
        mysqli_stmt_bind_param($stmt, 'isis', $me, $type, $tid, $text);
    }

    $ok    = mysqli_stmt_execute($stmt);
    $newid = (int)mysqli_insert_id($conn);

    $new = null;
    if ($ok && $newid) {
        $row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT c.*, u.ten, u.avatar FROM comments c
             JOIN users u ON c.user_id=u.id WHERE c.id=$newid"
        ));
        if ($row) {
            $row['time_ago'] = time_ago($row['created_at']);
            $row['replies']  = [];
            $new = $row;
        }
    }

    echo json_encode(['ok'=>$ok, 'id'=>$newid, 'comment'=>$new]);
    exit;
}

// ── DELETE: xoá comment ──
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || 
    ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action']??'') === 'delete')) {
    $d      = json_decode(file_get_contents('php://input'), true);
    $cmt_id = (int)($d['comment_id'] ?? $_GET['comment_id'] ?? 0);
    if (!$cmt_id) { echo json_encode(['ok'=>false,'msg'=>'Thiếu comment_id']); exit; }

    if (session_status() === PHP_SESSION_NONE) session_start();
    $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');

    // Lấy comment để kiểm tra quyền
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT user_id, parent_id, target_type, target_id FROM comments WHERE id=$cmt_id"
    ));
    if (!$row) { echo json_encode(['ok'=>false,'msg'=>'Không tìm thấy']); exit; }

    // Chỉ chủ comment hoặc admin mới được xoá
    if ((int)$row['user_id'] !== $me && !$is_admin) {
        echo json_encode(['ok'=>false,'msg'=>'Không có quyền']); exit;
    }

    // Nếu là comment gốc → xoá luôn các replies
    if (!$row['parent_id']) {
        mysqli_query($conn, "DELETE FROM comments WHERE parent_id=$cmt_id");
    }
    mysqli_query($conn, "DELETE FROM comments WHERE id=$cmt_id");

    echo json_encode(['ok'=>true]);
    exit;
}