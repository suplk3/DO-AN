<?php
/* post_action.php */
session_start();
include "../config/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$me = (int)$_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $text   = trim($_POST['noi_dung'] ?? '');
    $phim_id = (int)($_POST['phim_id'] ?? 0) ?: null;
    if (!$text) { header("Location: social.php"); exit; }
    $text = mb_substr($text, 0, 2000);

    $img = null;
    if (!empty($_FILES['hinh_anh']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed) && $_FILES['hinh_anh']['size'] < 5*1024*1024) {
            $img = uniqid('post_') . '.' . $ext;
            $dir = __DIR__ . '/../assets/images/posts/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $dir . $img);
        }
    }

    $s = mysqli_prepare($conn,
        "INSERT INTO posts (user_id,phim_id,noi_dung,hinh_anh) VALUES (?,?,?,?)"
    );
    mysqli_stmt_bind_param($s,'iiss',$me,$phim_id,$text,$img);
    mysqli_stmt_execute($s);
    $post_id = mysqli_insert_id($conn);

    // Notify followers
    if ($post_id > 0) {
        $u_res = mysqli_query($conn, "SELECT ho_ten FROM users WHERE id=$me");
        $u_row = mysqli_fetch_assoc($u_res);
        $actor_name = $u_row ? $u_row['ho_ten'] : 'Ai đó';
        $title = "Bài viết mới";
        $body = $actor_name . " vừa đăng một bài viết mới.";
        $link = "social.php#post_" . $post_id;
        
        $f_q = mysqli_query($conn, "SELECT id FROM users WHERE id != $me");
        if ($f_q) {
            $n_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, actor_id, type, target_id, title, body, link) VALUES (?, ?, 'new_post', ?, ?, ?, ?)");
            while($f = mysqli_fetch_assoc($f_q)) {
                $fid = (int)$f['id'];
                mysqli_stmt_bind_param($n_stmt, 'iiisss', $fid, $me, $post_id, $title, $body, $link);
                mysqli_stmt_execute($n_stmt);
            }
            if ($n_stmt) mysqli_stmt_close($n_stmt);
        }
    }

    header("Location: social.php"); exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    // Chỉ chủ bài hoặc admin mới xoá được
    $post = mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id,hinh_anh FROM posts WHERE id=$pid"));
    if ($post && ($post['user_id']==$me || ($_SESSION['vai_tro']??'')==='admin')) {
        if ($post['hinh_anh']) {
            @unlink(__DIR__.'/../assets/images/posts/'.$post['hinh_anh']);
        }
        mysqli_query($conn,"DELETE FROM reactions WHERE target_type='post' AND target_id=$pid");
        mysqli_query($conn,"DELETE FROM comments  WHERE target_type='post' AND target_id=$pid");
        mysqli_query($conn,"DELETE FROM posts WHERE id=$pid");
    }
    $ref = $_SERVER['HTTP_REFERER'] ?? 'social.php';
    header("Location: $ref"); exit;
}

header("Location: social.php"); exit;
