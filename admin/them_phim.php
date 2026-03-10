<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten_phim'] ?? '');
    $the_loai = trim($_POST['the_loai'] ?? '');
    $thoi_luong = trim($_POST['thoi_luong'] ?? '');
    $noi_dung = trim($_POST['mo_ta'] ?? '');
    $ngay_khoi_chieu = !empty($_POST['ngay_khoi_chieu']) ? $_POST['ngay_khoi_chieu'] : null;

    if ($ten === '') {
        $errors[] = 'Tên phim là bắt buộc.';
    }

    $posterName = '';
    if (empty($_FILES['poster']['name'])) {
        $errors[] = 'Vui lòng chọn poster.';
    } else {
        if ($_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['poster']['tmp_name']);
            finfo_close($finfo);
            if (!array_key_exists($mime, $allowed)) {
                $errors[] = 'Định dạng poster không hợp lệ (JPG, PNG, GIF).';
            } else {
                $ext = $allowed[$mime];
                $posterName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = __DIR__ . '/../assets/images/' . $posterName;
                if (!move_uploaded_file($_FILES['poster']['tmp_name'], $dest)) {
                    $errors[] = 'Không thể lưu poster, thử lại.';
                }
            }
        } else {
            $errors[] = 'Lỗi khi upload poster.';
        }
    }

    // Handle banner upload (optional)
    $bannerName = null;
    if (!empty($_FILES['banner']['name'])) {
        if ($_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['banner']['tmp_name']);
            finfo_close($finfo);
            if (!array_key_exists($mime, $allowed)) {
                $errors[] = 'Định dạng banner không hợp lệ (JPG, PNG, GIF).';
            } else {
                $ext = $allowed[$mime];
                $bannerName = time() . '_banner_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = __DIR__ . '/../assets/images/' . $bannerName;
                if (!move_uploaded_file($_FILES['banner']['tmp_name'], $dest)) {
                    $errors[] = 'Không thể lưu banner, thử lại.';
                }
            }
        } else {
            $errors[] = 'Lỗi khi upload banner.';
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO phim (ten_phim, the_loai, thoi_luong, mo_ta, poster, banner, ngay_khoi_chieu) VALUES (?,?,?,?,?,?,?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssss', $ten, $the_loai, $thoi_luong, $noi_dung, $posterName, $bannerName, $ngay_khoi_chieu);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($ok) {
            $_SESSION['success'] = 'Thêm phim thành công.';
            header('Location: phim.php');
            exit;
        } else {
            $errors[] = 'Lỗi cơ sở dữ liệu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thêm phim</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style> .icon {font-size:20px;margin-right:8px;color:var(--accent-red)} </style>
</head>
<body class="admin-dark">

<div class="form-container">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <a href="phim.php" class="btn-home" style="padding:6px 10px;font-weight:700">Quản lý phim</a>
        <h2 style="margin:0;color:var(--accent-red);font-size:20px"><span class="icon">➕</span> Thêm phim</h2>
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

    <form method="post" enctype="multipart/form-data">
        <div class="form-row">
            <label for="ten_phim">Tên phim</label>
            <input id="ten_phim" name="ten_phim" type="text" value="<?= isset($ten) ? htmlspecialchars($ten) : '' ?>" placeholder="Tên phim" required>
        </div>

        <div class="form-row">
            <label for="the_loai">Thể loại</label>
            <input id="the_loai" name="the_loai" type="text" value="<?= isset($the_loai) ? htmlspecialchars($the_loai) : '' ?>" placeholder="Ví dụ: Hành động, Tình cảm">
        </div>

        <div class="form-row">
            <label for="thoi_luong">Thời lượng</label>
            <input id="thoi_luong" name="thoi_luong" type="text" value="<?= isset($thoi_luong) ? htmlspecialchars($thoi_luong) : '' ?>" placeholder="Phút">
        </div>

        <div class="form-row">
            <label for="mo_ta">Nội dung</label>
            <textarea id="mo_ta" name="mo_ta" placeholder="Mô tả ngắn"><?= isset($noi_dung) ? htmlspecialchars($noi_dung) : '' ?></textarea>
        </div>

        <div class="form-row">
            <label for="poster">Poster</label>
            <input id="poster" type="file" name="poster" accept="image/*" required>
        </div>

        <div class="form-row">
            <label for="banner">Banner (cho trang chủ)</label>
            <input id="banner" type="file" name="banner" accept="image/*">
            <small style="color: #6b7280; margin-left: 10px;">Ảnh banner sẽ hiển thị ở trang chủ (không bắt buộc)</small>
        </div>

        <div class="form-row">
            <label for="ngay_khoi_chieu">Ngày khởi chiếu</label>
            <input id="ngay_khoi_chieu" type="date" name="ngay_khoi_chieu" value="<?= isset($ngay_khoi_chieu) ? htmlspecialchars($ngay_khoi_chieu) : '' ?>">
            <small style="color: #6b7280; margin-left: 10px;">Để trống nếu là phim đang chiếu</small>
        </div>

        <div class="actions">
            <button class="btn-primary" type="submit">Thêm phim</button>
            <a class="btn-home" href="phim.php">⬅ Quay lại</a>
        </div>
    </form>
</div>

</body>
</html>
