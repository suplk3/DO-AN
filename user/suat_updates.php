<?php
/**
 * suat_updates.php — SSE realtime cập nhật badge ghế trống
 * cho tất cả suất chiếu của 1 phim trên trang chi_tiet_phim.php
 */

set_time_limit(0);

if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

if (session_status() === PHP_SESSION_NONE) session_start();
session_write_close();

include "../config/db.php";
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

mysqli_query($conn, "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");

$phim_id = (int)($_GET['phim_id'] ?? 0);
if (!$phim_id) {
    echo "event: error\ndata: {\"message\":\"phim_id không hợp lệ\"}\n\n";
    flush();
    exit;
}

// Lấy toàn bộ suất chiếu + số ghế trống
function getSuatData($conn, $phim_id) {
    if (!@mysqli_ping($conn)) return null;

    $sql = "
        SELECT s.id,
               pc.id AS phong_id,
               COUNT(g.id) AS total_ghe,
               SUM(EXISTS(
                   SELECT 1 FROM ve WHERE ve.suat_chieu_id = s.id AND ve.ghe_id = g.id
               )) AS booked
        FROM suat_chieu s
        LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
        LEFT JOIN ghe g ON g.phong_id = pc.id
        WHERE s.phim_id = $phim_id
        GROUP BY s.id
    ";
    $res = mysqli_query($conn, $sql);
    if (!$res) return null;

    $data = array();
    while ($r = mysqli_fetch_assoc($res)) {
        $total = (int)$r['total_ghe'];
        $booked = (int)$r['booked'];
        $avail  = max(0, $total - $booked);
        $data[(int)$r['id']] = array(
            'id'        => (int)$r['id'],
            'total_ghe' => $total,
            'avail'     => $avail,
        );
    }
    return $data;
}

// Push snapshot đầu tiên
$prev = getSuatData($conn, $phim_id);
if ($prev) {
    echo "event: suats_update\n";
    echo "data: " . json_encode(array_values($prev)) . "\n\n";
    flush();
}

$timeout    = 55;
$started_at = time();

while (true) {
    if (connection_aborted()) break;
    if ((time() - $started_at) >= $timeout) {
        echo "event: reconnect\ndata: {}\n\n";
        flush();
        break;
    }

    usleep(800000); // 800ms

    $current = getSuatData($conn, $phim_id);
    if ($current === null) continue;

    // Chỉ push nếu có thay đổi
    $changed = array();
    foreach ($current as $id => $s) {
        if (!isset($prev[$id]) || $s['avail'] !== $prev[$id]['avail']) {
            $changed[] = $s;
        }
    }

    if (!empty($changed)) {
        echo "event: suats_update\n";
        echo "data: " . json_encode($changed) . "\n\n";
        flush();
        $prev = $current;
    }

    // Keepalive
    if ((time() - $started_at) % 10 === 0) {
        echo ": keepalive\n\n";
        flush();
    }
}

mysqli_close($conn);
