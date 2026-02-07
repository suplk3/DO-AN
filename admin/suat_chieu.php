<?php
include "check_admin.php";
include "../config/db.php";

$sql = "
SELECT 
    suat_chieu.*,
    phim.ten_phim,
    COUNT(ve.id) AS so_ve
FROM suat_chieu
JOIN phim ON suat_chieu.phim_id = phim.id
LEFT JOIN ve ON ve.suat_chieu_id = suat_chieu.id
GROUP BY suat_chieu.id
ORDER BY ngay, gio
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>🎞️ QUẢN LÝ SUẤT CHIẾU</h2>

<a href="them_suat.php" class="btn">➕ Thêm suất chiếu</a>
<a href="../user/index.php" class="btn">🏠 Về trang chính</a>

<table border="1" cellpadding="10" cellspacing="0">
<tr>
    <th>Phim</th>
    <th>Ngày</th>
    <th>Giờ</th>
    <th>Giá</th>
    <th>Hành động</th>
</tr>

<?php while ($row = mysqli_fetch_assoc($result)): ?>
<tr>
    <td><?= $row['ten_phim'] ?></td>
    <td><?= date('d/m/Y', strtotime($row['ngay'])) ?></td>
    <td><?= $row['gio'] ?></td>
    <td><?= number_format($row['gia']) ?> đ</td>
    <<td>
<?php if ($row['so_ve'] == 0): ?>
    <a href="sua_suat.php?id=<?= $row['id'] ?>">✏️ Sửa</a> |
    <a href="xoa_suat.php?id=<?= $row['id'] ?>"
       onclick="return confirm('Xóa suất chiếu này?')">
       ❌ Xóa
    </a>
<?php else: ?>
    <span style="color:red;font-weight:bold">
        🔒 Đã có vé
    </span>
<?php endif; ?>
</td>

</tr>
<?php endwhile; ?>

</table>

</body>
</html>
