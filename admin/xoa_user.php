<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['vai_tro'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    // Để tránh lỗi khoá ngoại nếu admin chưa set CASCADE, xoá vé trước
    $stmt = mysqli_prepare($conn, "DELETE FROM ve WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if ($stmt) mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND vai_tro = 'user'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if ($stmt) mysqli_stmt_execute($stmt);
}
header('Location: quan_ly_ban_user.php');
exit();
