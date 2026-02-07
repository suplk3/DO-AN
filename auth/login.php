<?php
session_start();
require_once '../config/db.php';

$error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $mat_khau = $_POST['mat_khau'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($mat_khau, $user['mat_khau'])) {
             session_start();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['ten'] = $user['ten'];
            $_SESSION['vai_tro'] = $user['vai_tro'];
     header("Location: ../user/index.php");
           /* if ($user['vai_tro'] === 'admin') {
                header("Location: ../user/index.php");
            } else {
                header("Location: ../user/index.php");
            }*/
         //   exit;
        } else {
            $error = "Sai mật khẩu";
        }
    } else {
        $error = "Email không tồn tại";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
</head>
<body>

<h2>ĐĂNG NHẬP</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="mat_khau" placeholder="Mật khẩu" required><br><br>
    <button type="submit" name="login">Đăng nhập</button>
</form>

<p style="color:red"><?= $error ?></p>

</body>
</html>
