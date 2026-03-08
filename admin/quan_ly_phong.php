<?php
include "check_admin.php";
include "../config/db.php";

// Handle form submission for adding a new room
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $ten_phong = mysqli_real_escape_string($conn, $_POST['ten_phong']);
    $rap_id = (int)$_POST['rap_id'];

    if (!empty($ten_phong) && $rap_id > 0) {
        $insert_sql = "INSERT INTO phong_chieu (ten_phong, rap_id) VALUES ('$ten_phong', $rap_id)";
        mysqli_query($conn, $insert_sql);
        // Redirect to avoid form resubmission
        header("Location: quan_ly_phong.php?status=add_success");
        exit;
    }
}

// Fetch all raps
$raps_result = mysqli_query($conn, "SELECT * FROM rap ORDER BY ten_rap");
$raps = [];
while ($row = mysqli_fetch_assoc($raps_result)) {
    $raps[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Phòng chiếu và Ghế</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/features.css">
    <style>
        body { max-width: 1200px; margin: 20px auto; padding: 20px; background: #0a0e17; color: #e2e8f0; font-family: sans-serif; }
        .page-header { background: linear-gradient(135deg, #1a1f2e 0%, #0f172a 100%); color: #ffffff; margin-bottom: 24px; padding: 20px 24px; border-radius: 12px; font-size: 24px; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4); border: 1px solid rgba(59, 130, 246, 0.15); display: flex; justify-content: space-between; align-items: center; }
        .rap-section { margin-bottom: 30px; background: #1a1f2e; padding: 20px; border-radius: 10px; border: 1px solid rgba(59, 130, 246, 0.1); }
        .rap-header { font-size: 20px; color: #60a5fa; margin-bottom: 15px; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; }
        .phong-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #0f172a; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #3b82f6; transition: background-color 0.2s ease; }
        .phong-item:hover { background-color: #1e293b; }
        .phong-name { font-weight: bold; }
        .phong-details { display: flex; align-items: center; gap: 20px; }
        .seat-count { font-style: italic; color: #94a3b8; }
        .btn-config { background: #3b82f6; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; transition: background-color 0.2s ease; }
        .btn-config:hover { background: #2563eb; }
        .no-phong { padding: 15px; text-align: center; color: #64748b; }
        .add-phong-form { background: #0f172a; padding: 20px; border-radius: 8px; margin-top: 15px; border-top: 1px solid rgba(59, 130, 246, 0.1); }
        .form-title { font-size: 16px; margin-bottom: 10px; color: #cbd5e1; font-weight: bold;}
        .form-input { padding: 10px; border-radius: 5px; border: 1px solid #3b82f6; background-color: #1e293b; color: white; }
        .btn { padding: 10px 15px; border-radius: 5px; text-decoration: none; border: none; cursor: pointer; }
    </style>
</head>
<body>

<div class="page-header">
    <span>🏢 Quản lý Phòng chiếu</span>
    <a href="suat_chieu.php" class="btn" style="background-color: #4A5568; color: white;">← Về trang suất chiếu</a>
</div>

<?php if (empty($raps)): ?>
    <div class="rap-section">
        <p class="no-phong">Chưa có rạp chiếu nào được tạo. Vui lòng thêm rạp trước.</p>
    </div>
<?php else: ?>
    <?php foreach ($raps as $rap): ?>
        <section class="rap-section">
            <h2 class="rap-header">Rạp: <?= htmlspecialchars($rap['ten_rap']) ?></h2>
            
            <?php
            // Fetch rooms for the current rap
            $rap_id = (int)$rap['id'];
            $phong_result = mysqli_query($conn, "SELECT * FROM phong_chieu WHERE rap_id = $rap_id ORDER BY ten_phong");
            
            if (mysqli_num_rows($phong_result) > 0):
                while ($phong = mysqli_fetch_assoc($phong_result)):
                    // Count seats for the current room
                    $phong_id = (int)$phong['id'];
                    $seat_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total_seats FROM ghe WHERE phong_id = $phong_id");
                    $seat_count = mysqli_fetch_assoc($seat_count_result)['total_seats'];
            ?>
                    <div class="phong-item">
                        <div class="phong-name"><?= htmlspecialchars($phong['ten_phong']) ?></div>
                        <div class="phong-details">
                            <div class="seat-count">(Hiện có: <strong><?= $seat_count ?> ghế</strong>)</div>
                            <a href="cau_hinh_ghe.php?phong_id=<?= $phong_id ?>" class="btn-config">⚙️ Cấu hình ghế</a>
                        </div>
                    </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="no-phong">Chưa có phòng nào trong rạp này.</div>
            <?php endif; ?>

            <div class="add-phong-form">
                 <h3 class="form-title">Thêm phòng mới cho rạp "<?= htmlspecialchars($rap['ten_rap']) ?>"</h3>
                 <form method="POST" action="quan_ly_phong.php" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="rap_id" value="<?= $rap['id'] ?>">
                    <input type="text" name="ten_phong" placeholder="Tên phòng mới, vd: P01" class="form-input" required style="flex-grow: 1;">
                    <button type="submit" name="add_room" class="btn" style="background-color: #3b82f6; color: white;">➕ Thêm Phòng</button>
                </form>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
