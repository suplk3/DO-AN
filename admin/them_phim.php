<?php
include "check_admin.php";
include "../config/db.php";

if (isset($_POST['them'])) {
    $ten = $_POST['ten'];
    $loai = $_POST['loai'];
    $tg = $_POST['thoiluong'];
    $mota = $_POST['mota'];
    $poster = $_POST['poster'];

    mysqli_query($conn,
        "INSERT INTO phim(ten_phim,the_loai,thoi_luong,mo_ta,poster)
         VALUES('$ten','$loai',$tg,'$mota','$poster')");
    header("Location: phim.php");
}
?>

<h2>Thêm phim</h2>
<form method="POST">
    <input name="ten" placeholder="Tên phim"><br>
    <input name="loai" placeholder="Thể loại"><br>
    <input name="thoiluong" placeholder="Thời lượng"><br>
    <textarea name="mota"></textarea><br>
    <input name="poster" placeholder="Link poster"><br>
    <button name="them">Thêm</button>
</form>
