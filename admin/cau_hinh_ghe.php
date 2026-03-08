<?php
include "check_admin.php";
include "../config/db.php";

// Validate phong_id
$phong_id = isset($_GET['phong_id']) ? (int)$_GET['phong_id'] : 0;
if ($phong_id <= 0) {
    die("ID phòng không hợp lệ.");
}

// Fetch room and cinema details
$sql = "SELECT p.ten_phong, r.ten_rap 
        FROM phong_chieu p 
        JOIN rap r ON p.rap_id = r.id 
        WHERE p.id = $phong_id";
$result = mysqli_query($conn, $sql);
$phong = mysqli_fetch_assoc($result);
if (!$phong) {
    die("Không tìm thấy phòng chiếu.");
}

// Handle seat generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_seats'])) {
    $rows = isset($_POST['rows']) ? (int)$_POST['rows'] : 0;
    $seats_per_row = isset($_POST['seats_per_row']) ? (int)$_POST['seats_per_row'] : 0;

    if ($rows > 0 && $seats_per_row > 0) {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // 1. Delete all existing seats for this room
            $delete_sql = "DELETE FROM ghe WHERE phong_id = $phong_id";
            mysqli_query($conn, $delete_sql);

            // 2. Generate and insert new seats
            $insert_sql = "INSERT INTO ghe (ten_ghe, phong_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);

            for ($i = 0; $i < $rows; $i++) {
                $row_letter = chr(65 + $i); // A, B, C, ...
                for ($j = 1; $j <= $seats_per_row; $j++) {
                    $seat_name = $row_letter . $j;
                    mysqli_stmt_bind_param($stmt, "si", $seat_name, $phong_id);
                    mysqli_stmt_execute($stmt);
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);

            header("Location: quan_ly_phong.php?status=config_success");
            exit;

        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conn);
            die("Lỗi: Không thể cấu hình ghế. Vui lòng thử lại. " . $exception->getMessage());
        }
    }
}

// Get current seat count
$seat_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total_seats FROM ghe WHERE phong_id = $phong_id");
$current_seat_count = mysqli_fetch_assoc($seat_count_result)['total_seats'];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Cấu hình ghế</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    body { max-width: 800px; margin: 20px auto; padding: 20px; background: #0a0e17; color: #e2e8f0; font-family: sans-serif; }
    .page-header { background: linear-gradient(135deg, #1a1f2e 0%, #0f172a 100%); color: #ffffff; margin-bottom: 24px; padding: 20px 24px; border-radius: 12px; font-size: 24px; }
    .page-header .sub-title { font-size: 16px; color: #94a3b8; }
    .config-form { background: #1a1f2e; padding: 30px; border-radius: 10px; border: 1px solid rgba(59, 130, 246, 0.1); }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #cbd5e1; }
    .form-input { width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #3b82f6; background-color: #1e293b; color: white; font-size: 16px; }
    .btn { padding: 12px 20px; border-radius: 5px; text-decoration: none; border: none; cursor: pointer; font-weight: bold; }
    .btn-primary { background-color: #3b82f6; color: white; }
    .btn-secondary { background-color: #4A5568; color: white; }
    .actions { display: flex; gap: 10px; margin-top: 20px; }
    .current-info { background-color: #1e293b; color: #a0aec0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6; }
</style>
</head>
<body>

<div class="page-header">
    Cấu hình ghế
    <div class="sub-title">Cho phòng <strong><?= htmlspecialchars($phong['ten_phong']) ?></strong> tại rạp <strong><?= htmlspecialchars($phong['ten_rap']) ?></strong></div>
</div>

<div class="config-form">

    <div class="current-info">
        Số ghế hiện tại: <strong><?= $current_seat_count ?></strong>
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="rows" class="form-label">Số hàng ghế (A, B, C...)</label>
            <input type="number" id="rows" name="rows" class="form-input" placeholder="Ví dụ: 10" required min="1" max="26">
        </div>
        <div class="form-group">
            <label for="seats_per_row" class="form-label">Số ghế trên mỗi hàng</label>
            <input type="number" id="seats_per_row" name="seats_per_row" class="form-input" placeholder="Ví dụ: 15" required min="1">
        </div>
        <div class="actions">
            <button type="submit" name="generate_seats" class="btn btn-primary">Tạo lại sơ đồ ghế</button>
            <a href="quan_ly_phong.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>
</div>

</body>
</html>
