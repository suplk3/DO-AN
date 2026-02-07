<?php
include "check_admin.php";
include "../config/db.php";

$phim = mysqli_query($conn, "SELECT * FROM phim");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phim_id = $_POST['phim_id'];
    $ngay = $_POST['ngay'];
    $gio = $_POST['gio'];
    $gia = $_POST['gia'];

    $sql = "INSERT INTO suat_chieu (phim_id, ngay, gio, gia)
            VALUES ('$phim_id', '$ngay', '$gio', '$gia')";
    mysqli_query($conn, $sql);

    header("Location: suat_chieu.php");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thêm suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>➕ THÊM SUẤT CHIẾU</h2>

<form method="post">
    <label>Phim:</label><br>
    <select name="phim_id" required>
        <?php while ($p = mysqli_fetch_assoc($phim)): ?>
            <option value="<?= $p['id'] ?>">
                <?= $p['ten_phim'] ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Ngày chiếu:</label><br>
    <input type="date" name="ngay" required><br><br>

    <label>Giờ chiếu:</label><br>
    <input type="time" name="gio" required><br><br>

    <label>Giá vé:</label><br>
    <input type="number" name="gia" required><br><br>

    <button class="btn">Lưu suất chiếu</button>
</form>

</body>
</html>
