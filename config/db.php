<?php
$host = "localhost";
$user = "root";
$pass = ""; // XAMPP mặc định
$dbname = "cinema"; // tên DB bạn vừa import

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Ket noi that bai: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>
