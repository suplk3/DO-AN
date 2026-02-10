<?php
include '../config/db.php';

$suat_chieu_id = $_GET['suat_id'] ?? 0;

if ($suat_chieu_id == 0) {
    die("Thiếu suất chiếu");
}

$sql = "
SELECT 
    ghe.id,
    ghe.ten_ghe,
    EXISTS (
        SELECT 1 
        FROM ve 
        WHERE ve.ghe_id = ghe.id 
        AND ve.suat_chieu_id = $suat_chieu_id
    ) AS da_dat
FROM ghe
ORDER BY ghe.ten_ghe
";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi SQL: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chọn ghế</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>CHỌN GHẾ</h2>
<div class="screen">MÀN HÌNH</div>

<div class="seat-wrapper">
<?php
$currentRow = '';
while ($row = mysqli_fetch_assoc($result)) {
    $rowChar = substr($row['ten_ghe'], 0, 1);

    if ($currentRow != $rowChar) {
        if ($currentRow != '') echo '</div>';
        echo "<div class='seat-row'>";
        $currentRow = $rowChar;
    }

    $class = $row['da_dat'] ? 'seat booked' : 'seat';

    echo "<button 
            class='$class' 
            data-seat='{$row['ten_ghe']}'
            ".($row['da_dat'] ? 'disabled' : '').">
            {$row['ten_ghe']}
          </button>";
}

if ($currentRow != '') echo '</div>';
?>
</div>
<div class="checkout">
    <p>Ghế đã chọn: <strong id="selected-seats"></strong></p>
    <p>Tổng tiền: <strong id="total">0 đ</strong></p>

    <form action="dat_ve.php" method="POST">
        <input type="hidden" name="ghe" id="seat-input">
        <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">

        <button type="submit">TIẾP TỤC THANH TOÁN</button>
    </form>
</div>



<script src="../assets/js/ghe.js"></script>


</body>


</html>