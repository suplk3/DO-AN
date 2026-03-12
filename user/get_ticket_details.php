<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['ve_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$ve_id = (int)$_GET['ve_id'];

// Lấy thông tin vé
$sql = "SELECT v.id AS ve_id, p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia,
               r.ten_rap, pc.ten_phong, g.ten_ghe
        FROM ve v
        LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
        LEFT JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        LEFT JOIN ghe g ON v.ghe_id = g.id
        WHERE v.id = ? AND v.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $ve_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ve = mysqli_fetch_assoc($result);

mysqli_close($conn);

if (!$ve) {
    http_response_code(404);
    echo json_encode(['error' => 'Ticket not found']);
    exit();
}

// Format data for JSON response
$ve['ngay_f'] = date('d/m/Y', strtotime($ve['ngay']));
$ve['gio_f'] = substr($ve['gio'], 0, 5);
$ve['poster_url'] = '../assets/images/' . htmlspecialchars($ve['poster']);

// Prepare QR data
$qr_data = "ID Vé: " . $ve['ve_id'] . "
" .
           "Phim: " . $ve['ten_phim'] . "
" .
           "Rạp: " . $ve['ten_rap'] . " - " . $ve['ten_phong'] . "
" .
           "Ghế: " . $ve['ten_ghe'] . "
" .
           "Ngày: " . $ve['ngay_f'] . "
" .
           "Giờ: " . $ve['gio_f'];

$ve['qr_code_url'] = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qr_data);


header('Content-Type: application/json');
echo json_encode($ve);
