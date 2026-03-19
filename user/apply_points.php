<?php
include "../config/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

function json_fail($msg) {
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE); exit;
}

if (!isset($_SESSION['user_id'])) json_fail('Bạn cần đăng nhập để dùng điểm TTVH.');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$points_to_use  = max(0, (int)($data['points'] ?? 0));
$suat_chieu_id  = (int)($data['suat_chieu_id'] ?? 0);
$seat_count     = (int)($data['seat_count'] ?? 0);
$combo_items    = $data['combo_items'] ?? [];

if ($points_to_use <= 0) json_fail('Vui lòng nhập số điểm hợp lệ.');
if ($suat_chieu_id <= 0 || $seat_count <= 0) json_fail('Dữ liệu suất chiếu không hợp lệ.');

// Auto-add points column if missing
if (!column_exists($conn, 'users', 'points')) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN points INT DEFAULT 0 NOT NULL");
}

$uid = (int)$_SESSION['user_id'];

// Fetch user points
$stmt = mysqli_prepare($conn, "SELECT points FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$available_points = (int)($row['points'] ?? 0);
if ($points_to_use > $available_points) {
    json_fail("Bạn chỉ có {$available_points} điểm TTVH.");
}

// Calculate discount: 100 points = 10,000đ (100đ/point)
$discount = $points_to_use * 100;

// Calculate sub_total for cap
$stmt = mysqli_prepare($conn, "SELECT gia FROM suat_chieu WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$sc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$sc) json_fail('Suất chiếu không tồn tại.');

$base_total  = (int)$sc['gia'] * $seat_count;
$combo_total = 0;
if (table_exists($conn, 'combos') && is_array($combo_items) && count($combo_items) > 0) {
    $ids = []; $qty_map = [];
    foreach ($combo_items as $it) {
        $cid = (int)($it['id'] ?? 0); $qty = (int)($it['qty'] ?? 0);
        if ($cid > 0 && $qty > 0) { $ids[] = $cid; $qty_map[$cid] = $qty; }
    }
    if ($ids) {
        $q = mysqli_query($conn, "SELECT id, gia FROM combos WHERE active=1 AND id IN (".implode(',', array_unique($ids)).")");
        while ($r = mysqli_fetch_assoc($q)) {
            $combo_total += (int)$r['gia'] * ($qty_map[(int)$r['id']] ?? 0);
        }
    }
}
$sub_total = $base_total + $combo_total;

if ($discount > $sub_total) {
    $discount = $sub_total;
    $points_to_use = (int)ceil($discount / 100);
}

echo json_encode([
    'ok'            => true,
    'discount'      => $discount,
    'points_used'   => $points_to_use,
    'points_avail'  => $available_points,
    'final_total'   => max(0, $sub_total - $discount),
    'message'       => "Đã dùng {$points_to_use} điểm TTVH → giảm " . number_format($discount, 0, ',', '.') . '₫'
], JSON_UNESCAPED_UNICODE);
