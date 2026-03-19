<?php
include "../config/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

function json_fail($msg) {
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE); exit;
}

if (!isset($_SESSION['user_id'])) json_fail('Bạn cần đăng nhập để dùng thẻ quà tặng.');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$code          = strtoupper(trim($data['code'] ?? ''));
$suat_chieu_id = (int)($data['suat_chieu_id'] ?? 0);
$seat_count    = (int)($data['seat_count'] ?? 0);
$combo_items   = $data['combo_items'] ?? [];

if ($code === '') json_fail('Vui lòng nhập mã thẻ quà tặng.');
if ($suat_chieu_id <= 0 || $seat_count <= 0) json_fail('Dữ liệu suất chiếu không hợp lệ.');

// Auto-create gift_cards table
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS gift_cards (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        code        VARCHAR(50) UNIQUE NOT NULL,
        balance     INT NOT NULL DEFAULT 0,
        used        TINYINT(1) DEFAULT 0,
        used_by     INT DEFAULT NULL,
        used_at     DATETIME DEFAULT NULL,
        expired_at  DATE DEFAULT NULL,
        note        VARCHAR(255) DEFAULT '',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Fetch gift card
$stmt = mysqli_prepare($conn, "SELECT * FROM gift_cards WHERE code = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$card = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$card) json_fail('Mã thẻ quà tặng không hợp lệ.');
if ((int)$card['used'] === 1) json_fail('Thẻ quà tặng này đã được sử dụng.');
if (!empty($card['expired_at']) && date('Y-m-d') > $card['expired_at']) {
    json_fail('Thẻ quà tặng đã hết hạn (hết hiệu lực: ' . date('d/m/Y', strtotime($card['expired_at'])) . ').');
}

$discount = (int)$card['balance'];

// Calculate sub_total for info
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
$sub_total   = $base_total + $combo_total;
if ($discount > $sub_total) $discount = $sub_total;
$final_total = max(0, $sub_total - $discount);

echo json_encode([
    'ok'          => true,
    'discount'    => $discount,
    'final_total' => $final_total,
    'card_value'  => (int)$card['balance'],
    'message'     => 'Thẻ quà tặng hợp lệ → giảm ' . number_format($discount, 0, ',', '.') . '₫'
], JSON_UNESCAPED_UNICODE);
