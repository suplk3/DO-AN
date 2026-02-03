<?php
include "../config/db.php";
$suat_id = $_GET['id'];

$ghe = mysqli_query($conn,"
SELECT ghe.*,
IF(ve.id IS NULL, 'trong', 'da-dat') AS trang_thai
FROM ghe
LEFT JOIN ve ON ghe.id = ve.ghe_id AND ve.suat_chieu_id = $suat_id
WHERE ghe.phong_id = (
    SELECT phong_id FROM suat_chieu WHERE id=$suat_id
)");
?>

<link rel="stylesheet" href="../assets/css/style.css">

<h2>Chọn ghế</h2>

<form method="POST" action="dat_ve.php">
<input type="hidden" name="suat_id" value="<?= $suat_id ?>">
<input type="hidden" name="ghe_id" id="ghe_id">

<?php while($g = mysqli_fetch_assoc($ghe)) { ?>
<button type="button"
    class="ghe <?= $g['trang_thai'] ?>"
    data-id="<?= $g['id'] ?>"
    <?= $g['trang_thai']=='da-dat'?'disabled':'' ?>>
    <?= $g['ten_ghe'] ?>
</button>
<?php } ?>

<br><br>
<button type="submit">Đặt vé</button>
</form>

<script src="../assets/js/ghe.js"></script>
