<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$ve_id = isset($_GET['ve_id']) ? intval($_GET['ve_id']) : 0;

if ($ve_id <= 0) {
    die('Vé không hợp lệ');
}

$sql = "SELECT v.id AS ve_id, p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia, r.ten_rap, pc.ten_phong, g.ten_ghe
        FROM ve v
        JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
        JOIN phim p ON sc.phim_id = p.id
        JOIN phong_chieu pc ON sc.phong_id = pc.id
        JOIN rap r ON pc.rap_id = r.id
        JOIN ghe g ON v.ghe_id = g.id
        WHERE v.id = ? AND v.user_id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $ve_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$ve = mysqli_fetch_assoc($res);

if (!$ve) {
    die('Không tìm thấy vé hoặc bạn không có quyền truy cập.');
}

// Tạo nội dung HTML vé để tải về
$ticket_html = "<!doctype html>\n<html lang=\"vi\">\n<head>\n<meta charset=\"utf-8\">\n<title>Ve-".htmlspecialchars($ve['ve_id'])."</title>\n<style>body{font-family:Arial;padding:20px} .ticket{max-width:600px;border:1px solid #ddd;padding:20px;border-radius:8px} img{max-width:150px} .header{display:flex;gap:20px} .meta{margin-top:14px} .meta div{margin-bottom:6px}</style>\n</head>\n<body>\n<div class=\"ticket\">\n  <div class=\"header\">\n    <img src=\"../assets/images/".htmlspecialchars($ve['poster'])."\" alt=\"poster\">\n    <div>\n      <h2>".htmlspecialchars($ve['ten_phim'])."</h2>\n      <div>Mã vé: #".htmlspecialchars($ve['ve_id'])."</div>\n    </div>\n  </div>\n  <div class=\"meta\">\n    <div><strong>Rạp:</strong> ".htmlspecialchars($ve['ten_rap'])."</div>\n    <div><strong>Phòng:</strong> ".htmlspecialchars($ve['ten_phong'])."</div>\n    <div><strong>Ghế:</strong> ".htmlspecialchars($ve['ten_ghe'])."</div>\n    <div><strong>Ngày:</strong> ".date('d/m/Y', strtotime($ve['ngay']))."</div>\n    <div><strong>Giờ:</strong> ".substr($ve['gio'],0,5)."</div>\n    <div><strong>Giá:</strong> ".number_format($ve['gia'])." đ</div>\n  </div>\n</div>\n</body>\n</html>";

// Gửi header để tải file HTML
$filename = 've_'. $ve['ve_id'] .'.html';
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="'. $filename .'"');
echo $ticket_html;
exit();
