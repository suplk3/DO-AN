<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Xóa liên kết (nếu cần thiết, nếu không có cascade delete thì cẩn thận)
    // mysqli_query($conn, "DELETE FROM combo_orders WHERE combo_id = $id");
    
    $stmt = mysqli_prepare($conn, "DELETE FROM combos WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Xóa combo thành công!";
    } else {
        $_SESSION['error'] = "Không thể xóa combo này có thể do combo đã được đặt mua!";
    }
    mysqli_stmt_close($stmt);
}

header("Location: quan_ly_combo.php");
exit;
?>
