<?php
include "check_admin.php";
include "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tắt kiểm tra khóa ngoài
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

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
}
?>

<h2>🔄 RESET ID PHI</h2>
<link rel="stylesheet" href="../assets/css/style.css">

<div style="text-align: center; margin-top: 50px;">
    <p>Bạn chắc chắn muốn đánh lại ID từ 1?</p>
    <form method="post" style="margin-top: 20px;">
        <button class="btn btn-add" style="background: #ff6b6b;">✓ Xác nhận Reset</button>
        <a href="phim.php" class="btn btn-back">⬅ Hủy</a>
    </form>
</div>
