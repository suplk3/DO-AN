<?php
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
?>
