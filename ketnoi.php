<?php
$conn = mysqli_connect("localhost", "root", "", "movie_ticket");
mysqli_set_charset($conn, "utf8");

if (!$conn) {
    die("Kết nối CSDL thất bại");
}
?>
