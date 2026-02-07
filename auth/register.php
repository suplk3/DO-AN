<?php
include "../config/db.php";

if (isset($_POST['dangky'])) {
    $ten = $_POST['ten'];
    $email = $_POST['email'];
    $mk = password_hash($_POST['mat_khau'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users(ten,email,mat_khau) 
            VALUES('$ten','$email','$mk')";
    mysqli_query($conn, $sql);
    header("Location: login.php");
}
?>

<form method="POST">
    <input name="ten" placeholder="Tên">
    <input name="email" placeholder="Email">
    <input name="mat_khau" type="password" placeholder="Mật khẩu">
    <button name="dangky">Đăng ký</button>
</form>
