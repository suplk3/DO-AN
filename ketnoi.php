<?php
$conn = mysqli_connect("localhost", "root", "", "movie_ticket");

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
?>
