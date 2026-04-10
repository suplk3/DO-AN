<?php
session_start();
include "../config/db.php";
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$me = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'poll';

function time_ago($dt) {
    global $conn;
    $now_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT UNIX_TIMESTAMP(NOW()) AS now_ts, UNIX_TIMESTAMP('$dt') AS dt_ts"));
    $d = max(0, (int)$now_row['now_ts'] - (int)$now_row['dt_ts']);
    if ($d < 60)     return 'Vừa xong';
    if ($d < 3600)   return floor($d/60).' phút trước';
    if ($d < 86400)  return floor($d/3600).' giờ trước';
    if ($d < 604800) return floor($d/86400).' ngày trước';
    return date('d/m/Y', strtotime($dt));
}

if ($action === 'poll') {
    // Return unread count + top 15 latest notifications
    $c_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$me AND is_read=0");
    $unreadCount = $c_res ? (int)mysqli_fetch_assoc($c_res)['c'] : 0;

    $n_res = mysqli_query($conn, "
        SELECT n.*, u.avatar, u.ten AS actor_name
        FROM notifications n
        LEFT JOIN users u ON n.actor_id = u.id
        WHERE n.user_id=$me
        ORDER BY n.created_at DESC
        LIMIT 15
    ");

    $notifs = [];
    if ($n_res) {
        while ($r = mysqli_fetch_assoc($n_res)) {
            $r['time_ago'] = time_ago($r['created_at']);
            
            $avatar = $r['avatar'];
            $actor_name = $r['actor_name'];

            if (!$avatar && !$actor_name) { // system notification
                 $r['display_avatar'] = '🤖'; 
            } elseif ($avatar) {
                 $r['display_avatar'] = '<img src="../assets/images/'.$avatar.'" class="notif-avatar" alt="Avatar">';
            } else {
                 $r['display_avatar'] = '<div class="temp-avatar">'.mb_substr($actor_name,0,1).'</div>';
            }

            $notifs[] = $r;
        }
    }

    $updated_profiles = [];
    $client_last_ts = (int)($_GET['p_ts'] ?? 0);
    $recent_file = __DIR__ . '/recent_profiles.json';
    if ($client_last_ts > 0 && file_exists($recent_file)) {
        $recent = json_decode(file_get_contents($recent_file), true) ?: [];
        foreach ($recent as $v) {
            if ($v['ts'] >= $client_last_ts) {
                $updated_profiles[] = $v;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount,
        'notifications' => $notifs,
        'updated_profiles' => $updated_profiles,
        'p_ts' => time()
    ]);
    exit;
}

if ($action === 'mark_read') {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$me");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_one_read') {
    $id = (int)($_POST['id'] ?? 0);
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$me AND id=$id");
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
