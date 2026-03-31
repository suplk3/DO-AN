<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['vai_tro'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET is_banned = 1 WHERE id = ? AND vai_tro = 'user'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}
header('Location: quan_ly_user.php');
exit();
