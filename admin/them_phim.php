<?php
include "check_admin.php";
include "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten = $_POST['ten_phim'];
    $the_loai = $_POST['the_loai'];
    $thoi_luong = $_POST['thoi_luong'];
    $noi_dung = $_POST['mo_ta'];

    $poster = $_FILES['poster']['name'];
    move_uploaded_file(
        $_FILES['poster']['tmp_name'],
        "../assets/images/$poster"
    );

    $sql = "INSERT INTO phim 
        (ten_phim, the_loai, thoi_luong, mo_ta, poster)
        VALUES ('$ten','$the_loai','$thoi_luong','$noi_dung','$poster')";

   if (mysqli_query($conn, $sql)) {
    header("Location: phim.php");
    exit;
} else {
    echo "Lỗi SQL: " . mysqli_error($conn);
}
    header("Location: phim.php");
}
?>

<h2>THÊM PHIM</h2>
<form method="post" enctype="multipart/form-data">
    <input name="ten_phim" placeholder="Tên phim" required><br>
    <input name="the_loai" placeholder="Thể loại"><br>
    <input name="thoi_luong" placeholder="Thời lượng"><br>
    <textarea name="mo_ta" placeholder="Nội dung phim"></textarea><br>
    <input type="file" name="poster" required><br>
    <button>Thêm phim</button>
</form>
