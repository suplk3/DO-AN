<?php
include "check_admin.php";
include "../config/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: phim.php");
    exit();
}

// Check phim đã có suất chiếu chưa
$sqlCheck = "SELECT COUNT(*) AS tong FROM suat_chieu WHERE phim_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$result = $stmtCheck->get_result()->fetch_assoc();

if ($result['tong'] > 0) {
    die("Khong the xoa phim da co suat chieu!");
}

// Xóa phim
$sqlDelete = "DELETE FROM phim WHERE id = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $id);
$stmtDelete->execute();

header("Location: phim.php");
exit();
