<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

$result = mysqli_query($conn, "SELECT * FROM combos ORDER BY id DESC");
$count = $result ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý Combo Bắp Nước</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body.admin-dark {
            min-height: 100vh;
            margin: 0;
            color: #e2e8f0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 8% 12%, rgba(239, 68, 68, 0.18), transparent 34%),
                radial-gradient(circle at 88% 0%, rgba(59, 130, 246, 0.2), transparent 36%),
                linear-gradient(160deg, #050816 0%, #0a1024 42%, #081226 100%);
        }
        .admin-shell {
            max-width: 1200px;
            margin: 28px auto;
            padding: 0 16px 32px;
            animation: pageEnter .45s ease both;
        }
        .admin-header { margin-bottom: 14px; }
        .page-title {
            margin: 0; font-size: 34px; letter-spacing: 1px; color: #ffffff;
            padding: 20px 24px; border-radius: 14px; border: 1px solid rgba(59, 130, 246, 0.45);
            background: linear-gradient(90deg, rgba(30, 58, 95, 0.48), rgba(15, 23, 42, 0.94));
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.35), inset 0 1px 0 rgba(96, 165, 250, 0.15);
        }
        .top-bar { display: flex; gap: 12px; margin-bottom: 16px; margin-top: 20px;}
        .toolbar-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 0 20px; height: 48px; border-radius: 10px; font-weight: 800;
            text-decoration: none; transition: transform .18s; white-space: nowrap;
        }
        .toolbar-btn:hover { transform: translateY(-2px); }
        .toolbar-btn.primary { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; box-shadow: 0 8px 18px rgba(220, 38, 38, 0.35); }
        .toolbar-btn.outline { background: rgba(59, 130, 246, 0.16); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.75); }
        .msg.success { background: rgba(16, 185, 129, 0.14); color: #6ee7b7; padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(16, 185, 129, 0.45); margin-bottom: 14px; font-weight: 700; }
        .table-wrapper {
            overflow: auto; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.3);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.84), rgba(30, 41, 59, 0.66));
            box-shadow: 0 14px 28px rgba(2, 6, 23, 0.38);
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table th { padding: 13px 14px; text-align: left; font-size: 12px; text-transform: uppercase; color: #f8fafc; background: linear-gradient(90deg, rgba(30, 58, 95, 0.95), rgba(22, 47, 86, 0.95)); border-bottom: 1px solid rgba(59, 130, 246, 0.35); }
        table td { padding: 14px; color: rgba(226, 232, 240, 0.9); border-bottom: 1px solid rgba(148, 163, 184, 0.16); vertical-align: middle; }
        table tr:nth-child(even) { background: rgba(30, 41, 59, 0.34); }
        table tr:hover { background: rgba(30, 64, 175, 0.18); }
        .status-badge { padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .status-badge.active { background: rgba(16, 185, 129, 0.16); color: #6ee7b7; }
        .status-badge.inactive { background: rgba(239, 68, 68, 0.12); color: #fca5a5; }
        .action-btns a { padding: 6px 10px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 800; margin-right: 5px; }
        .btn-edit { background: rgba(59, 130, 246, 0.18); color: #93c5fd; border: 1px solid rgba(96, 165, 250, 0.4); }
        .btn-delete { background: rgba(239, 68, 68, 0.16); color: #fecaca; border: 1px solid rgba(248, 113, 113, 0.45); }
        @keyframes pageEnter { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="admin-dark">
<div class="admin-shell">
    <div class="admin-header">
        <h1 class="page-title">🍿 Quản lý Combo Bắp Nước</h1>
    </div>

    <div class="top-bar">
        <a href="them_combo.php" class="toolbar-btn primary"><span>➕</span> Thêm Combo mới</a>
        <a href="dashboard.php" class="toolbar-btn outline"><span>🏠</span> Về Dashboard</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="msg success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th># ID</th>
                    <th>🍿 Tên Combo</th>
                    <th>📝 Mô tả</th>
                    <th>💵 Giá (VNĐ)</th>
                    <th>Trạng thái</th>
                    <th>⚙️ Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($count === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 40px; color: #cbd5e1;">Chưa có combo nào. Vui lòng thêm combo.</td>
                </tr>
                <?php else: while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td style="font-weight: bold; color: #fff;"><?= htmlspecialchars($row['ten']) ?></td>
                    <td><?= htmlspecialchars($row['mo_ta']) ?></td>
                    <td style="color:#7FFF00; font-weight:bold;"><?= number_format($row['gia'], 0, ',', '.') ?>₫</td>
                    <td>
                        <?php if ($row['active'] == 1): ?>
                            <span class="status-badge active">Đang bán</span>
                        <?php else: ?>
                            <span class="status-badge inactive">Đã ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-btns">
                        <a href="sua_combo.php?id=<?= $row['id'] ?>" class="btn-edit">✏️ Sửa</a>
                        <a href="xoa_combo.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa combo này?');">❌ Xóa</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
