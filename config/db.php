<?php
// Disable strict exception throwing for mysqli in PHP 8.1+ to prevent fatal errors
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = ""; // XAMPP mặc định
$dbname = "cinema"; // tên DB bạn vừa import

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Helpers: check table/column existence (avoid fatal error if DB not updated yet)
function table_exists($conn, $table) {
    $sql = "SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "s", $table);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ok = $res && mysqli_fetch_row($res);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

function column_exists($conn, $table, $column) {
    $sql = "SELECT 1 FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "ss", $table, $column);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ok = $res && mysqli_fetch_row($res);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

// Tự động xóa suất chiếu quá 36h so với thời gian thực (để suất chiếu tự mất ở trang người dùng)
// Chạy ở cấp độ toàn cục (Global) mỗi khi database được kết nối
if (table_exists($conn, 'suat_chieu') && table_exists($conn, 've')) {
    mysqli_query($conn, "DELETE ve FROM ve JOIN suat_chieu sc ON ve.suat_chieu_id = sc.id WHERE CONCAT(sc.ngay, ' ', sc.gio) < DATE_SUB(NOW(), INTERVAL 36 HOUR)");
    mysqli_query($conn, "DELETE FROM suat_chieu WHERE CONCAT(ngay, ' ', gio) < DATE_SUB(NOW(), INTERVAL 36 HOUR)");
}
?>
