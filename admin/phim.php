
<?php

include "check_admin.php";
include "../config/db.php";


$result = mysqli_query($conn, "SELECT * FROM phim");
?>
<h1>🎬 QUẢN LÝ PHIM</h1>
<link rel="stylesheet" href="../assets/css/style.css">


<div class="top-bar">
    <a href="them_phim.php" class="btn btn-add">➕ Thêm phim</a>
    <a href="reset_id.php" class="btn btn-add" style="background: #ff9800;">🔄 Reset ID</a>
    <a href="../user/index.php" class="btn btn-home">🏠 Trang chính</a>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Tên phim</th>
        <th>Poster</th>
        <th>Hành động</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['ten_phim'] ?></td>
        <td>
            <img src="../assets/images/<?= $row['poster'] ?>">
        </td>
        <td>
            <a href="sua_phim.php?id=<?= $row['id'] ?>" class="btn-edit">✏️ Sửa</a>
            <a href="xoa_phim.php?id=<?= $row['id'] ?>" 
               class="btn-delete"
               onclick="return confirm('Bạn có chắc muốn xóa phim này?')">
               ❌ Xóa
            </a>
        </td>
    </tr>
    <?php } ?>
</table>
