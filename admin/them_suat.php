<?php
include "check_admin.php";
include "../config/db.php";

$phim = mysqli_query($conn, "SELECT * FROM phim");
$raps = mysqli_query($conn, "SELECT * FROM rap");
// load halls for initial state (first theater)
$phong_chieu = [];
if ($raps && $r = mysqli_fetch_assoc($raps)) {
    $firstRapId = $r['id'];
    $phong_chieu_result = mysqli_query($conn, "SELECT * FROM phong_chieu WHERE rap_id = $firstRapId");
    while ($row = mysqli_fetch_assoc($phong_chieu_result)) {
        $phong_chieu[] = $row;
    }
}
// rewind $raps pointer for later loop
mysqli_data_seek($raps, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phim_id = $_POST['phim_id'];
    $rap_id = $_POST['rap_id'];
    $phong_id = $_POST['phong_id'];
    $ngay = $_POST['ngay'];
    $gio = $_POST['gio'];
    $gia = $_POST['gia'];

    $sql = "INSERT INTO suat_chieu (phim_id, phong_id, ngay, gio, gia)
            VALUES ('$phim_id', '$phong_id', '$ngay', '$gio', '$gia')";
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

    <label>Rạp:</label><br>
    <select name="rap_id" id="rapSelect" required>
        <?php while ($r = mysqli_fetch_assoc($raps)): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['ten_rap']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Phòng chiếu:</label><br>
    <select name="phong_id" id="phongSelect" required>
        <?php foreach ($phong_chieu as $pc): ?>
            <option value="<?= $pc['id'] ?>"><?= htmlspecialchars($pc['ten_phong']) ?></option>
        <?php endforeach; ?>
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
