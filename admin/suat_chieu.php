<?php
include "check_admin.php";
include "../config/db.php";

$phim = mysqli_query($conn, "SELECT * FROM phim");
$phong = mysqli_query($conn, "SELECT * FROM phong_chieu");

if (isset($_POST['them'])) {
    $phim_id = $_POST['phim'];
    $phong_id = $_POST['phong'];
    $ngay = $_POST['ngay'];
    $gio = $_POST['gio'];
    $gia = $_POST['gia'];

    mysqli_query($conn,
        "INSERT INTO suat_chieu(phim_id,phong_id,ngay,gio,gia)
         VALUES($phim_id,$phong_id,'$ngay','$gio',$gia)");
}
?>

<h2>Thêm suất chiếu</h2>
<form method="POST">
    Phim:
    <select name="phim">
        <?php while($p = mysqli_fetch_assoc($phim)) { ?>
        <option value="<?= $p['id'] ?>"><?= $p['ten_phim'] ?></option>
        <?php } ?>
    </select><br>

    Phòng:
    <select name="phong">
        <?php while($r = mysqli_fetch_assoc($phong)) { ?>
        <option value="<?= $r['id'] ?>"><?= $r['ten_phong'] ?></option>
        <?php } ?>
    </select><br>

    Ngày: <input type="date" name="ngay"><br>
    Giờ: <input type="time" name="gio"><br>
    Giá vé: <input name="gia"><br>

    <button name="them">Thêm suất</button>
</form>
