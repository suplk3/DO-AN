<?php
/* profile_action.php */
session_start();
include "../config/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$me = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = mb_substr(trim($_POST['ten'] ?? ''), 0, 100);
    $bio = mb_substr(trim($_POST['bio'] ?? ''), 0, 300);
    if (!$ten) { header("Location: profile.php?id=$me"); exit; }

    $avatar = null;
    if (!empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['avatar']['size'] < 3*1024*1024) {
            $avatar = 'av_' . $me . '_' . uniqid() . '.' . $ext;
            $dir = __DIR__ . '/../assets/images/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $avatar);
        }
    }

    if ($avatar) {
        $s = mysqli_prepare($conn,"UPDATE users SET ten=?,bio=?,avatar=? WHERE id=?");
        mysqli_stmt_bind_param($s,'sssi',$ten,$bio,$avatar,$me);
    } else {
        $s = mysqli_prepare($conn,"UPDATE users SET ten=?,bio=? WHERE id=?");
        mysqli_stmt_bind_param($s,'ssi',$ten,$bio,$me);
    }
    mysqli_stmt_execute($s);

    // Cập nhật session name
    $_SESSION['ten'] = $ten;
    $_SESSION['ten_nguoi_dung'] = $ten;
}
header("Location: profile.php?id=$me"); exit;
