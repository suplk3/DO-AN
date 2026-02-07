<?php
include "check_admin.php";
include "../config/db.php";

$id = $_GET['id'] ?? 0;
$check = mysqli_fetch_assoc(
    mysqli_query($conn,
    "SELECT COUNT(*) AS tong FROM ve WHERE suat_chieu_id = $id")
);

if ($check['tong'] > 0) {
    die("Không thể sửa suất chiếu đã có vé!");
}

// Lấy suất chiếu hiện tại
$suat = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM suat_chieu WHERE id = $id")
);

// Danh sách phim
$phim = mysqli_query($conn, "SELECT * FROM phim");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phim_id = $_POST['phim_id'];
    $ngay = $_POST['ngay'];
    $gio = $_POST['gio'];
    $gia = $_POST['gia'];

    $sql = "
    UPDATE suat_chieu SET
        phim_id = '$phim_id',
        ngay = '$ngay',
        gio = '$gio',
        gia = '$gia'
    WHERE id = $id
    ";
    mysqli_query($conn, $sql);

    header("Location: suat_chieu.php");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>✏️ SỬA SUẤT CHIẾU</h2>

<form method="post">

    <label>Phim:</label><br>
    <select name="phim_id">
        <?php while ($p = mysqli_fetch_assoc($phim)): ?>
            <option value="<?= $p['id'] ?>"
                <?= ($p['id'] == $suat['phim_id']) ? 'selected' : '' ?>>
                <?= $p['ten_phim'] ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Ngày:</label><br>
    <input type="date" name="ngay"
           value="<?= $suat['ngay'] ?>" required><br><br>

    <label>Giờ:</label><br>
    <input type="time" name="gio"
           value="<?= $suat['gio'] ?>" required><br><br>

    <label>Giá vé:</label><br>
    <input type="number" name="gia"
           value="<?= $suat['gia'] ?>" required><br><br>

    <button class="btn">💾 Lưu thay đổi</button>
    <a href="suat_chieu.php" class="btn">⬅ Quay lại</a>
</form>

</body>
</html>
