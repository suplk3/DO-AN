<?php
/* profile_action.php */
session_start();
include "../config/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$me = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = isset($_POST['is_ajax']);
    $ten = mb_substr(trim($_POST['ten'] ?? ''), 0, 100);
    $bio = mb_substr(trim($_POST['bio'] ?? ''), 0, 300);
    if (!$ten) { 
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Tên không được để trống']);
            exit;
        }
        header("Location: profile.php?id=$me"); exit; 
    }

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

    // Broadcasting
    $current_avatar = $avatar;
    if (!$current_avatar) {
        $res = mysqli_query($conn, "SELECT avatar FROM users WHERE id=$me");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $current_avatar = $row['avatar'];
        }
    }
    
    $file = __DIR__ . '/../api/recent_profiles.json';
    $recent = [];
    if (file_exists($file)) {
        $recent = json_decode(file_get_contents($file), true) ?: [];
    }
    $recent[$me] = [
        'id' => $me,
        'ten' => $ten,
        'avatar' => $current_avatar,
        'ts' => time()
    ];
    $now = time();
    $recent = array_filter($recent, function($v) use ($now) { return $now - $v['ts'] <= 60; });
    file_put_contents($file, json_encode($recent));

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'ten' => $ten,
            'bio' => $bio,
            'avatar' => $avatar ? $avatar : null
        ]);
        exit;
    }
}
header("Location: profile.php?id=$me"); exit;
