<?php
include "check_admin.php";
include "../config/db.php";

$id = $_GET['id'] ?? 0;

// Check đã có vé chưa
$check = mysqli_fetch_assoc(
    mysqli_query($conn,
    "SELECT COUNT(*) AS tong FROM ve WHERE suat_chieu_id = $id")
);

if ($check['tong'] > 0) {
    die("Không thể xóa suất chiếu đã có vé!");
}

mysqli_query($conn, "DELETE FROM suat_chieu WHERE id = $id");
header("Location: suat_chieu.php");
