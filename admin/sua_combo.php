<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$error = "";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Combo không tồn tại.");
}

$stmt_get = mysqli_prepare($conn, "SELECT * FROM combos WHERE id = ?");
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$combo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
mysqli_stmt_close($stmt_get);

if (!$combo) {
    die("Combo không tồn tại.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten'] ?? '');
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    $gia = (int)($_POST['gia'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;

    if ($ten === '') {
        $error = "Tên combo không được để trống!";
    } elseif ($gia < 0) {
        $error = "Giá combo không hợp lệ!";
    } else {
        $stmt_upd = mysqli_prepare($conn, "UPDATE combos SET ten=?, mo_ta=?, gia=?, active=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_upd, "ssiii", $ten, $mo_ta, $gia, $active, $id);
        if (mysqli_stmt_execute($stmt_upd)) {
            $_SESSION['success'] = "Cập nhật combo thành công!";
            header("Location: quan_ly_combo.php");
            exit;
        } else {
            $error = "Lỗi khi lưu vào CSDL!";
        }
        mysqli_stmt_close($stmt_upd);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Sửa Combo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body.admin-dark {
            min-height: 100vh; margin: 0; color: #e2e8f0; font-family: "Trebuchet MS", sans-serif;
            background: linear-gradient(160deg, #050816 0%, #0a1024 42%, #081226 100%);
        }
        .form-container {
            max-width: 600px; margin: 40px auto; padding: 25px; border-radius: 12px;
            background: #1e293b; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        h2 { color: #fff; margin-top: 0; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: bold; }
        input[type="text"], input[type="number"], textarea {
            width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #475569;
            background: #0f172a; color: #f8fafc; font-family: inherit; box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
            outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.3);
        }
        .btn-submit {
            background: #3b82f6; color: white; border: none; padding: 12px 20px; font-weight: bold;
            border-radius: 6px; cursor: pointer; width: 100%; transition: 0.2s;
        }
        .btn-submit:hover { background: #2563eb; }
        .btn-back { display: inline-block; margin-top: 15px; color: #94a3b8; text-decoration: none; }
        .btn-back:hover { color: #fff; text-decoration: underline; }
        .error-msg { color: #fca5a5; background: #7f1d1d; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body class="admin-dark">
<div class="form-container">
    <h2>✏️ Sửa Combo: <?= htmlspecialchars($combo['ten']) ?></h2>
    <?php if ($error !== ''): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Tên Combo *</label>
            <input type="text" name="ten" required value="<?= htmlspecialchars($_POST['ten'] ?? $combo['ten']) ?>">
        </div>
        <div class="form-group">
            <label>Mô tả chi tiết</label>
            <textarea name="mo_ta" rows="4"><?= htmlspecialchars($_POST['mo_ta'] ?? $combo['mo_ta']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Giá (VNĐ) *</label>
            <input type="number" name="gia" required min="0" value="<?= htmlspecialchars($_POST['gia'] ?? $combo['gia']) ?>">
        </div>
        <div class="form-group" style="display:flex; align-items:center; gap: 10px;">
            <input type="checkbox" name="active" id="active" style="width:18px; height:18px;" <?= ($combo['active'] == 1) ? 'checked' : '' ?>>
            <label for="active" style="margin-bottom:0; cursor:pointer;">Đang mở bán</label>
        </div>
        <button type="submit" class="btn-submit">Lưu lại</button>
    </form>
    <a href="quan_ly_combo.php" class="btn-back">⬅ Quay lại danh sách</a>
</div>
</body>
</html>
