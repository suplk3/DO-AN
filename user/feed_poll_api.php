<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
$me = (int)$_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode(['error' => 'invalid_data']);
    exit;
}

$latest_time = isset($data['latest_time']) ? (int)$data['latest_time'] : 0;
$post_ids = isset($data['post_ids']) ? $data['post_ids'] : [];
$ids = is_array($post_ids) ? array_values(array_filter(array_map('intval', $post_ids))) : [];

$res = [
    'updates' => [],
    'new_posts_html' => ''
];

// 0. Cập nhật trạng thái Ban/Unban thời gian thực.
$res_me_status = mysqli_query($conn, "SELECT is_banned FROM users WHERE id=$me");
        $me_status = $res_me_status ? mysqli_fetch_assoc($res_me_status) : null;
$res['is_banned'] = !empty($me_status['is_banned']);

// 1. Cap nhat so like/comment cho cac bai dang dang hien co.
if (!empty($ids)) {
    $ids_str = implode(',', $ids);
    $sql = "SELECT id,
            (SELECT COUNT(*) FROM reactions WHERE target_type='post' AND target_id=posts.id) AS tong_reaction,
            (SELECT COUNT(*) FROM comments WHERE target_type='post' AND target_id=posts.id) AS tong_comment
            FROM posts
            WHERE id IN ($ids_str)";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $res['updates'][$row['id']] = [
                'reactions' => (int)$row['tong_reaction'],
                'comments' => (int)$row['tong_comment']
            ];
        }
    }
    
    // Tìm ra những bài viết không còn tồn tại trong DB (bị xóa)
    $found_ids = array_keys($res['updates']);
    $res['deleted_posts'] = array_values(array_diff($ids, $found_ids));
}

// 2. Lay bai moi hon bai gan nhat dang co tren DOM.
$exclude_existing_sql = !empty($ids) ? ' AND p.id NOT IN (' . implode(',', $ids) . ')' : '';
$created_filter_sql = $latest_time > 0
    ? " AND p.created_at >= '" . date('Y-m-d H:i:s', $latest_time) . "'"
    : '';

$sql_new = "
    SELECT p.*,
           u.ten AS ten_user, u.avatar,
           CASE
               WHEN p.user_id = $me THEN 'self'
               WHEN EXISTS (
                   SELECT 1
                   FROM follows f
                   WHERE f.follower_id = $me AND f.following_id = p.user_id
               ) THEN 'following'
               ELSE 'public'
           END AS source,
           (SELECT COUNT(*) FROM reactions
            WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
           (SELECT COUNT(*) FROM comments
            WHERE target_type='post' AND target_id=p.id) AS tong_comment,
           (SELECT loai FROM reactions
            WHERE target_type='post' AND target_id=p.id AND user_id=$me
            LIMIT 1) AS my_reaction,
           ph.ten_phim, ph.poster AS phim_poster
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN phim ph ON p.phim_id = ph.id
    WHERE 1=1
    $created_filter_sql
    $exclude_existing_sql
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT 20
";

$q_new = mysqli_query($conn, $sql_new);
if ($q_new && mysqli_num_rows($q_new) > 0) {
    $res_me_info = mysqli_query($conn, "SELECT * FROM users WHERE id=$me");
        $me_info = $res_me_info ? mysqli_fetch_assoc($res_me_info) : null;
    $REACTIONS = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'sad' => '😢', 'angry' => '😡'];

    ob_start();
    while ($post = mysqli_fetch_assoc($q_new)) {
        include 'components/post_card.php';
    }
    $res['new_posts_html'] = ob_get_clean();
}

echo json_encode($res);
