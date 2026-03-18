<?php
include "../config/db.php";
session_start();

header('Content-Type: application/json; charset=utf-8');

function json_fail($msg) {
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$code = trim($data['code'] ?? '');
$suat_chieu_id = (int)($data['suat_chieu_id'] ?? 0);
$seat_count = (int)($data['seat_count'] ?? 0);
$combo_items = $data['combo_items'] ?? [];

if ($code === '') json_fail('Vui lòng nhập mã voucher.');
if ($suat_chieu_id <= 0 || $seat_count <= 0) json_fail('Dữ liệu suất chiếu/ghế không hợp lệ.');
if (!isset($_SESSION['user_id'])) json_fail('Bạn cần đăng nhập để dùng voucher.');
if (!table_exists($conn, 'vouchers') || !table_exists($conn, 'voucher_usages')) {
    json_fail('Bảng vouchers chưa được tạo. Hãy import SQL mới.');
}

// 1) Base total từ giá suất chiếu
$stmt = mysqli_prepare($conn, "SELECT gia FROM suat_chieu WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
if (!$row) json_fail('Suất chiếu không tồn tại.');
$gia = (int)$row['gia'];
$base_total = $gia * $seat_count;

// 2) Combo total
$combo_total = 0;
if (table_exists($conn, 'combos') && is_array($combo_items) && count($combo_items) > 0) {
    $ids = [];
    $qty_map = [];
    foreach ($combo_items as $it) {
        $cid = (int)($it['id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        if ($cid > 0 && $qty > 0) {
            $ids[] = $cid;
            $qty_map[$cid] = $qty;
        }
    }
    if ($ids) {
        $id_list = implode(',', array_unique($ids));
        $q = mysqli_query($conn, "SELECT id, gia FROM combos WHERE active=1 AND id IN ($id_list)");
        while ($r = mysqli_fetch_assoc($q)) {
            $cid = (int)$r['id'];
            $price = (int)$r['gia'];
            $combo_total += $price * ($qty_map[$cid] ?? 0);
        }
    }
}

$sub_total = $base_total + $combo_total;

// 3) Lấy voucher
$stmt = mysqli_prepare($conn, "SELECT * FROM vouchers WHERE code = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$voucher || (int)$voucher['active'] !== 1) json_fail('Voucher không hợp lệ hoặc đã bị khóa.');

// 4) Kiểm tra ngày & điều kiện
$today = date('Y-m-d');
if (!empty($voucher['start_date']) && $today < $voucher['start_date']) json_fail('Voucher chưa đến ngày sử dụng.');
if (!empty($voucher['end_date']) && $today > $voucher['end_date']) json_fail('Voucher đã hết hạn.');
if ($sub_total < (int)$voucher['min_total']) json_fail('Đơn hàng chưa đạt giá trị tối thiểu.');

// Usage limit
if (!is_null($voucher['usage_limit'])) {
    if ((int)$voucher['used_count'] >= (int)$voucher['usage_limit']) {
        json_fail('Voucher đã hết lượt sử dụng.');
    }
}

// User already used?
$uid = (int)$_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT id FROM voucher_usages WHERE voucher_id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $voucher['id'], $uid);
mysqli_stmt_execute($stmt);
$used = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if ($used) json_fail('Bạn đã dùng voucher này rồi.');

// 5) Tính giảm
$discount = 0;
if ($voucher['discount_type'] === 'percent') {
    $discount = (int)round($sub_total * ((int)$voucher['discount_value']) / 100);
} else {
    $discount = (int)$voucher['discount_value'];
}
if (!empty($voucher['max_discount']) && $discount > (int)$voucher['max_discount']) {
    $discount = (int)$voucher['max_discount'];
}
if ($discount > $sub_total) $discount = $sub_total;

$final_total = max(0, $sub_total - $discount);

echo json_encode([
    'ok' => true,
    'discount' => $discount,
    'sub_total' => $sub_total,
    'final_total' => $final_total,
    'message' => 'Áp dụng voucher thành công.'
], JSON_UNESCAPED_UNICODE);
