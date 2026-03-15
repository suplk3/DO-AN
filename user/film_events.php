<?php
/**
 * film_events.php — SSE realtime cho reactions + comments của phim
 * Pattern giống seat_events.php (đang hoạt động trên InfinityFree)
 */

set_time_limit(0);

// Tắt output buffering — GIỐNG seat_events.php
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

// Giải phóng session lock ngay
if (session_status() === PHP_SESSION_NONE) session_start();
session_write_close();

include "../config/db.php";
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

// Đọc committed data, không bị cache bởi InnoDB
mysqli_query($conn, "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
mysqli_query($conn, "SET SESSION query_cache_type = 0"); // tắt query cache nếu có

$phim_id = (int)($_GET['phim_id'] ?? 0);
if (!$phim_id) {
    echo "event: error\ndata: {\"message\":\"phim_id không hợp lệ\"}\n\n";
    flush();
    exit;
}

function getReactions($conn, $phim_id) {
    if (!@mysqli_ping($conn)) return null;
    $breakdown = array();
    $total = 0;
    $r = mysqli_query($conn,
        "SELECT SQL_NO_CACHE loai, COUNT(*) AS c FROM reactions
         WHERE target_type='phim' AND target_id=$phim_id
         GROUP BY loai"
    );
    if ($r) while ($row = mysqli_fetch_assoc($r)) {
        $breakdown[$row['loai']] = (int)$row['c'];
        $total += (int)$row['c'];
    }
    return array('breakdown' => $breakdown, 'total' => $total);
}

function getCommentCount($conn, $phim_id) {
    if (!@mysqli_ping($conn)) return null;
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SQL_NO_CACHE COUNT(*) AS c FROM comments
         WHERE target_type='phim' AND target_id=$phim_id"
    ));
    return (int)($r['c'] ?? 0);
}

// Snapshot ban đầu — push ngay khi client kết nối
$prev_react = getReactions($conn, $phim_id);
$prev_cmt   = getCommentCount($conn, $phim_id);

echo "event: init\n";
echo "data: " . json_encode(array(
    'reactions' => $prev_react,
    'comments'  => $prev_cmt
)) . "\n\n";
flush();

$timeout    = 55;
$interval   = 800000;
$started_at = time();
$loop       = 0;

while (true) {
    if (connection_aborted()) break;
    if ((time() - $started_at) >= $timeout) {
        echo "event: reconnect\ndata: {}\n\n";
        flush();
        break;
    }

    usleep($interval);

    // Check reactions — luôn push mỗi vòng để debug
    $react = getReactions($conn, $phim_id);
    if ($react !== null) {
        ksort($react['breakdown']);
        ksort($prev_react['breakdown']);
        $prev_json  = json_encode($prev_react['breakdown']);
        $react_json = json_encode($react['breakdown']);

        if ($react['total'] !== $prev_react['total'] || $react_json !== $prev_json) {
            echo "event: reactions_update\n";
            echo "data: " . json_encode($react) . "\n\n";
            flush();
            $prev_react = $react;
        }
    }

    // Check comments
    $cmt = getCommentCount($conn, $phim_id);
    if ($cmt !== null && $cmt !== $prev_cmt) {
        echo "event: comments_update\n";
        echo "data: " . json_encode(array(
            'total' => $cmt,
            'ts'    => time()
        )) . "\n\n";
        flush();
        $prev_cmt = $cmt;
    }

    // Keepalive mỗi ~10s
    if ((time() - $started_at) % 10 === 0) {
        echo ": keepalive\n\n";
        flush();
    }
    $loop++;
}

mysqli_close($conn);