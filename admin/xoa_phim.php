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

// Tắt kiểm tra khóa ngoài
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Xóa phim
$sqlDelete = "DELETE FROM phim WHERE id = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $id);
$stmtDelete->execute();

// Đánh lại ID liên tục từ 1
$sqlGetPhim = "SELECT id FROM phim ORDER BY id ASC";
$resultPhim = mysqli_query($conn, $sqlGetPhim);
$newId = 1;

while ($row = mysqli_fetch_assoc($resultPhim)) {
    $oldId = $row['id'];
    if ($oldId != $newId) {
        // Cập nhật phim_id trong suat_chieu
        $sqlUpdateSuat = "UPDATE suat_chieu SET phim_id = ? WHERE phim_id = ?";
        $stmtUpdateSuat = $conn->prepare($sqlUpdateSuat);
        $stmtUpdateSuat->bind_param("ii", $newId, $oldId);
        $stmtUpdateSuat->execute();
        
        // Cập nhật ID trong phim
        $sqlUpdate = "UPDATE phim SET id = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ii", $newId, $oldId);
        $stmtUpdate->execute();
    }
    $newId++;
}

// Reset AUTO_INCREMENT
mysqli_query($conn, "ALTER TABLE phim AUTO_INCREMENT = " . $newId);

// Bật lại kiểm tra khóa ngoài
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

header("Location: phim.php");
exit();
