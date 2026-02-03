<?php
include "check_admin.php";
include "../config/db.php";
$phim = mysqli_query($conn, "SELECT * FROM phim");
?>

<h2>Quản lý phim</h2>
<a href="them_phim.php">➕ Thêm phim</a>

<table border="1">
<tr>
    <th>ID</th>
    <th>Tên phim</th>
    <th>Thể loại</th>
    <th>Thời lượng</th>
</tr>

<?php while($p = mysqli_fetch_assoc($phim)) { ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= $p['ten_phim'] ?></td>
    <td><?= $p['the_loai'] ?></td>
    <td><?= $p['thoi_luong'] ?> phút</td>
</tr>
<?php } ?>
</table>
