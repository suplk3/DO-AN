<?php
/* follow_api.php */
session_start();
include "../config/db.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Chưa đăng nhập'], JSON_UNESCAPED_UNICODE);
    exit;
}

function create_follow_notification(mysqli $conn, int $actorId, int $targetUserId): void
{
    if (!table_exists($conn, 'notifications')) {
        return;
    }

    $actorStmt = mysqli_prepare($conn, "SELECT ten FROM users WHERE id = ? LIMIT 1");
    if (!$actorStmt) {
        return;
    }

    mysqli_stmt_bind_param($actorStmt, 'i', $actorId);
    mysqli_stmt_execute($actorStmt);
    $actorRes = mysqli_stmt_get_result($actorStmt);
    $actorRow = $actorRes ? mysqli_fetch_assoc($actorRes) : null;
    mysqli_stmt_close($actorStmt);

    $actorName = trim((string)($actorRow['ten'] ?? 'Ai đó'));
    if ($actorName === '') {
        $actorName = 'Ai đó';
    }

    $title = 'Có người theo dõi bạn';
    $body = $actorName . ' vừa theo dõi bạn.';
    $link = 'profile.php?id=' . $actorId;

    $hasActorId = column_exists($conn, 'notifications', 'actor_id');
    $hasType = column_exists($conn, 'notifications', 'type');
    $hasTargetId = column_exists($conn, 'notifications', 'target_id');

    if ($hasActorId && $hasType && $hasTargetId) {
        $targetId = $actorId;
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO notifications (user_id, actor_id, type, target_id, title, body, link)
             VALUES (?, ?, 'new_follower', ?, ?, ?, ?)"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iiisss', $targetUserId, $actorId, $targetId, $title, $body, $link);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        return;
    }

    $fallbackStmt = mysqli_prepare(
        $conn,
        "INSERT INTO notifications (user_id, title, body, link) VALUES (?, ?, ?, ?)"
    );
    if ($fallbackStmt) {
        mysqli_stmt_bind_param($fallbackStmt, 'isss', $targetUserId, $title, $body, $link);
        mysqli_stmt_execute($fallbackStmt);
        mysqli_stmt_close($fallbackStmt);
    }
}

$me = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$fid = (int)($data['following_id'] ?? $_POST['following_id'] ?? $_GET['following_id'] ?? 0);
$refererTargetId = 0;

if (!empty($_SERVER['HTTP_REFERER'])) {
    $refererQuery = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
    if (is_string($refererQuery) && $refererQuery !== '') {
        parse_str($refererQuery, $refererParams);
        $refererTargetId = (int)($refererParams['id'] ?? 0);
    }
}

if ((!$fid || $fid === $me) && $refererTargetId > 0 && $refererTargetId !== $me) {
    $fid = $refererTargetId;
}
if (!$fid || $fid === $me) {
    echo json_encode(['error' => 'Không hợp lệ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$exist = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT 1 FROM follows WHERE follower_id=$me AND following_id=$fid"
));

if ($exist) {
    mysqli_query($conn, "DELETE FROM follows WHERE follower_id=$me AND following_id=$fid");
    echo json_encode(['action' => 'unfollowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ok = mysqli_query($conn, "INSERT INTO follows (follower_id,following_id) VALUES ($me,$fid)");
if (!$ok) {
    echo json_encode(['error' => 'Không thể theo dõi lúc này'], JSON_UNESCAPED_UNICODE);
    exit;
}

create_follow_notification($conn, $me, $fid);
echo json_encode(['action' => 'followed'], JSON_UNESCAPED_UNICODE);
