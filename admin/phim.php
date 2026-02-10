<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$result = mysqli_query($conn, "SELECT * FROM phim ORDER BY id DESC");
$count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý phim</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-header{margin-bottom:20px}
        .table-wrapper{overflow-x:auto;border-radius:10px}
        table{font-size:14px;border-collapse:separate;border-spacing:0}
        table thead{background:var(--accent-red);position:sticky;top:0}
        table thead th{color:#fff;padding:12px 14px;text-align:left;font-weight:700}
        table tbody tr{border-bottom:1px solid rgba(15,23,42,0.04)}
        table tbody tr:hover{background:rgba(229,9,20,0.08)}
        /* Đổi màu chữ trong các ô bảng sang tối để đọc trên nền sáng */
        table tbody td{padding:12px 14px;color:#0f172a}
        table td:first-child{width:60px}
        table td img{width:50px;border-radius:6px;border:1px solid rgba(255,255,255,0.06)}
        .action-btns{display:flex;gap:8px;flex-wrap:wrap}
        .top-bar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
        .stats{color:rgba(255,255,255,0.7);font-size:14px}
        @media(max-width:768px){
            table{font-size:12px}
            table thead th, table tbody td{padding:8px 10px}
            .action-btns{flex-direction:column}
            .action-btns a{width:100%}
        }
    </style>
</head>
<body class="admin-dark">

<div style="max-width:1200px;margin:0 auto;padding:20px">
    <div class="admin-header">
        <h1 style="margin:0;color:var(--accent-red);font-size:26px">🎬 Quản lý phim</h1>
        <p class="stats">Tổng: <strong><?= $count ?></strong> phim</p>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="msg success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <a href="them_phim.php" class="btn btn-add" style="display:flex;align-items:center;gap:6px;padding:10px 16px">
            <span>➕</span> Thêm phim
        </a>
        <a href="reset_id.php" class="btn btn-add" style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2)">
            <span>🔄</span> Reset ID
        </a>
        <a href="../user/index.php" class="btn btn-home" style="display:flex;align-items:center;gap:6px;padding:10px 16px">
            <span>🏠</span> Trang chính
        </a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên phim</th>
                    <th>Thể loại</th>
                    <th>Poster</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($count === 0) {
                    echo '<tr><td colspan="5" style="text-align:center;padding:30px;color:rgba(255,255,255,0.5)">Chưa có phim nào.</td></tr>';
                }
                while ($row = mysqli_fetch_assoc($result)): 
                ?>
                <tr>
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['ten_phim']) ?></td>
                    <td><?= htmlspecialchars($row['the_loai']) ?></td>
                    <td>
                        <?php if (!empty($row['poster']) && file_exists(__DIR__ . '/../assets/images/' . $row['poster'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($row['poster']) ?>" alt="poster">
                        <?php else: ?>
                            <span style="color:rgba(255,255,255,0.4)">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="sua_phim.php?id=<?= $row['id'] ?>" class="btn-edit">✏️ Sửa</a>
                            <a href="xoa_phim.php?id=<?= $row['id'] ?>" 
                               class="btn-delete"
                               onclick="return confirm('Xóa phim này?')">
                               ❌ Xóa
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
