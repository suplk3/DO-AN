<?php
/**
 * seat_events.php — Server-Sent Events
 * Giữ kết nối mở, polling DB mỗi 800ms
 * Khi phát hiện ghế mới bị đặt → đẩy ngay xuống client
 * Đặt file này ở: user/seat_events.php  (hoặc root tùy cấu trúc)
 */

set_time_limit(0); // FIX: Cho phép script chạy vô thời hạn

// Tắt output buffering để stream ngay lập tức
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

// Headers SSE bắt buộc
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');   // tắt buffering trên Nginx
header('Access-Control-Allow-Origin: *');

// Khởi động session chỉ để đọc user_id nếu cần, rồi ĐÓNG NGAY
// session_write_close() là fix quan trọng nhất:
// Nếu không có dòng này, PHP giữ lock file session suốt 55 giây
// → mọi tab khác của cùng trình duyệt bị TREO hoàn toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();  // ← GIẢI PHÓNG SESSION LOCK NGAY LẬP TỨC

include "../config/db.php";

// Nếu config/db.php có session_start() bên trong, đóng lại một lần nữa
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// FIX: Đảm bảo connection này luôn đọc được dữ liệu mới nhất đã commit,
// tránh bị ảnh hưởng bởi REPEATABLE READ isolation level mặc định của InnoDB.
mysqli_query($conn, "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");

$suat_id = filter_input(INPUT_GET, 'suat_id', FILTER_VALIDATE_INT);
if (!$suat_id) {
    echo "event: error\ndata: {\"message\":\"suat_id không hợp lệ\"}\n\n";
    flush();
    exit;
}

/**
 * Lấy snapshot ghế hiện tại từ DB
 * Trả về mảng [ten_ghe => da_dat (0|1)]
 */
function getSeats($conn, $suat_id) {
    // Ping để giữ kết nối DB sống (tránh "MySQL server has gone away" sau 8h idle)
    if (!@mysqli_ping($conn)) {
        // Kết nối chết → không làm gì, vòng lặp tiếp tục
        return null;
    }
    $phong_sql = "SELECT phong_id FROM suat_chieu WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $phong_sql);
    mysqli_stmt_bind_param($stmt, 'i', $suat_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) return [];
    $phong_id = (int)$row['phong_id'];

    $sql = "
        SELECT g.ten_ghe,
               EXISTS(SELECT 1 FROM ve WHERE ve.ghe_id = g.id AND ve.suat_chieu_id = ?) AS da_dat
        FROM ghe g
        WHERE g.phong_id = ?
        ORDER BY LEFT(g.ten_ghe,1), CAST(SUBSTRING(g.ten_ghe,2) AS UNSIGNED)
    ";
    $stmt2 = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt2, 'ii', $suat_id, $phong_id);
    mysqli_stmt_execute($stmt2);
    $res = mysqli_stmt_get_result($stmt2);

    $seats = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $seats[$r['ten_ghe']] = (int)$r['da_dat'];
    }
    mysqli_stmt_close($stmt2);
    return $seats;
}

// Gửi snapshot đầu tiên ngay khi client kết nối
$prev = getSeats($conn, $suat_id);
echo "event: init\n";
echo "data: " . json_encode(array_map(fn($g,$d)=>['ten_ghe'=>$g,'da_dat'=>$d], array_keys($prev), $prev)) . "\n\n";
flush();

$timeout    = 55;   // giây — đóng sau 55s, client tự reconnect (EventSource tự động)
$interval   = 0.8;  // giây — kiểm tra DB mỗi 800ms
$started_at = time();

while (true) {
    // Kiểm tra client còn kết nối không
    if (connection_aborted()) break;

    // Timeout → đóng, client EventSource sẽ tự kết nối lại
    if ((time() - $started_at) >= $timeout) {
        echo "event: reconnect\ndata: {}\n\n";
        flush();
        break;
    }

    usleep((int)($interval * 1_000_000));

    $current = getSeats($conn, $suat_id);

    // Nếu DB lỗi tạm thời, bỏ qua vòng này
    if ($current === null) continue;

    // So sánh với snapshot trước — chỉ gửi khi có thay đổi
    $changed = [];
    foreach ($current as $ghe => $da_dat) {
        $prev_val = $prev[$ghe] ?? 0;
        if ($da_dat !== $prev_val) {
            $changed[] = ['ten_ghe' => $ghe, 'da_dat' => $da_dat];
        }
    }

    if (!empty($changed)) {
        echo "event: seats_update\n";
        echo "data: " . json_encode($changed) . "\n\n";
        flush();
        $prev = $current;   // cập nhật snapshot
    }

    // Keepalive comment mỗi ~10s để tránh proxy timeout
    if ((time() - $started_at) % 10 === 0) {
        echo ": keepalive\n\n";
        flush();
    }
}

mysqli_close($conn);