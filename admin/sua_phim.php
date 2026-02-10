<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: phim.php");
    exit;
}

/* Lấy dữ liệu phim an toàn */
$stmt = mysqli_prepare($conn, "SELECT * FROM phim WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$phim = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$phim) {
    header("Location: phim.php");
    exit;
}

$errors = [];
/* Xử lý cập nhật */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten_phim'] ?? '');
    $the_loai = trim($_POST['the_loai'] ?? '');
    $thoi_luong = trim($_POST['thoi_luong'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');

    if ($ten === '') {
        $errors[] = 'Tên phim là bắt buộc.';
    }

    /* Xử lý upload poster nếu có */
    $poster = $phim['poster'];
    if (!empty($_FILES['poster']['name'])) {
        if ($_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['poster']['tmp_name']);
            finfo_close($finfo);

            if (!array_key_exists($mime, $allowed)) {
                $errors[] = 'Định dạng ảnh không hợp lệ. Chỉ cho phép JPG, PNG, GIF.';
            } else {
                $ext = $allowed[$mime];
                $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = __DIR__ . '/../assets/images/' . $newName;
                if (!move_uploaded_file($_FILES['poster']['tmp_name'], $dest)) {
                    $errors[] = 'Không thể lưu ảnh. Vui lòng thử lại.';
                } else {
                    $poster = $newName;
                }
            }
        } else {
            $errors[] = 'Lỗi khi upload ảnh.';
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE phim SET ten_phim = ?, the_loai = ?, thoi_luong = ?, mo_ta = ?, poster = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssi', $ten, $the_loai, $thoi_luong, $noi_dung, $poster, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            $_SESSION['success'] = 'Cập nhật phim thành công.';
            header('Location: phim.php');
            exit;
        } else {
            $errors[] = 'Lỗi cơ sở dữ liệu, vui lòng thử lại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa phim</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container{max-width:760px;margin:28px auto;padding:18px;border-radius:8px;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
        .form-row{display:flex;gap:12px;margin-bottom:12px;align-items:center}
        label{width:120px;font-weight:600}
        input[type=text], textarea, input[type=file]{flex:1;padding:8px;border:1px solid #d1d5db;border-radius:6px}
        textarea{min-height:120px}
        .actions{display:flex;gap:10px;align-items:center}
        .btn-primary{background:#1f6feb;color:#fff;padding:8px 14px;border:none;border-radius:6px;cursor:pointer}
        .btn-secondary{background:#f3f4f6;padding:8px 12px;border-radius:6px;text-decoration:none;color:#111;border:1px solid #e5e7eb}
        .msg{padding:10px;border-radius:6px;margin-bottom:12px}
        .msg.error{background:#fee2e2;color:#991b1b}
        .msg.success{background:#ecfdf5;color:#065f46}
        .poster-preview{max-width:140px;border-radius:6px;border:1px solid #e5e7eb}
    </style>
</head>
<body class="admin-dark">

<div class="form-container">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <a href="phim.php" class="btn-home" style="padding:6px 10px;font-weight:700">Quản lý phim</a>
        <h2 style="margin:0;color:var(--accent-red);font-size:20px">✏️ Sửa phim</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="msg error">
            <ul style="margin:0 0 0 18px;padding:6px 0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="msg success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-box">
        <div class="form-row">
            <label for="ten_phim">Tên phim</label>
            <input id="ten_phim" name="ten_phim" type="text" value="<?= htmlspecialchars($phim['ten_phim']) ?>" required placeholder="Nhập tên phim">
        </div>

        <div class="form-row">
            <label for="the_loai">Thể loại</label>
            <input id="the_loai" name="the_loai" type="text" value="<?= htmlspecialchars($phim['the_loai']) ?>" placeholder="Ví dụ: Hành động, Tình cảm">
        </div>

        <div class="form-row">
            <label for="thoi_luong">Thời lượng</label>
            <input id="thoi_luong" name="thoi_luong" type="text" value="<?= htmlspecialchars($phim['thoi_luong']) ?>" placeholder="Ví dụ: 120 phút">
        </div>

        <div class="form-row">
            <label for="noi_dung">Nội dung</label>
            <textarea id="noi_dung" name="noi_dung" placeholder="Mô tả ngắn về phim"><?= htmlspecialchars($phim['mo_ta']) ?></textarea>
        </div>

        <div class="form-row">
            <label>Poster hiện tại</label>
            <?php if (!empty($phim['poster']) && file_exists(__DIR__ . '/../assets/images/' . $phim['poster'])): ?>
                <img class="poster-preview" src="../assets/images/<?= htmlspecialchars($phim['poster']) ?>" alt="poster">
            <?php else: ?>
                <div style="color:#6b7280">Chưa có poster</div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="poster">Thay poster</label>
            <input id="poster" type="file" name="poster" accept="image/*">
        </div>

        <div class="actions">
            <button class="btn-primary" type="submit">Cập nhật</button>
            <a class="btn-home" href="phim.php">⬅ Quay lại</a>
        </div>
    </form>
</div>

</body>
</html>
