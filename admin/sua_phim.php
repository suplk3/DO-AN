<?php
include "check_admin.php";
include "../config/db.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: phim.php");
    exit;
}

/* Lấy dữ liệu phim */
$result = mysqli_query($conn, "SELECT * FROM phim WHERE id = $id");
$phim = mysqli_fetch_assoc($result);

if (!$phim) {
    header("Location: phim.php");
    exit;
}

/* Xử lý cập nhật */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten = $_POST['ten_phim'];
    $the_loai = $_POST['the_loai'];
    $thoi_luong = $_POST['thoi_luong'];
    $noi_dung = $_POST['noi_dung'];

    if (!empty($_FILES['poster']['name'])) {
        $poster = $_FILES['poster']['name'];
        move_uploaded_file(
            $_FILES['poster']['tmp_name'],
            "../assets/images/$poster"
        );
    } else {
        $poster = $phim['poster'];
    }

    $sql = "UPDATE phim SET
        ten_phim='$ten',
        the_loai='$the_loai',
        thoi_luong='$thoi_luong',
        mo_ta='$noi_dung',
        poster='$poster'
        WHERE id=$id";

    mysqli_query($conn, $sql);
    header("Location: phim.php");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa phim</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>✏️ SỬA PHIM</h2>

<form method="post" enctype="multipart/form-data" class="form-box">
    <input name="ten_phim" value="<?= $phim['ten_phim'] ?>" required><br>

    <input name="the_loai" value="<?= $phim['the_loai'] ?>"><br>

    <input name="thoi_luong" value="<?= $phim['thoi_luong'] ?>"><br>

    <textarea name="noi_dung"><?= $phim['mo_ta'] ?></textarea><br>

    <p>Poster hiện tại:</p>
    <img src="../assets/images/<?= $phim['poster'] ?>" width="120"><br><br>

    <input type="file" name="poster"><br><br>

    <button>Cập nhật</button>
    <a href="phim.php" class="btn-back">⬅ Quay lại</a>
</form>

</body>
</html>
