<?php
session_start();
include "../config/db.php";

if (isset($_POST['dangnhap'])) {
    $email = $_POST['email'];
    $mk = $_POST['mat_khau'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($mk, $user['mat_khau'])) {
        $_SESSION['user'] = $user;
        header("Location: ../user/index.php");
    }
}
?>

<form method="POST">
    <input name="email">
    <input name="mat_khau" type="password">
    <button name="dangnhap">Đăng nhập</button>
</form>
