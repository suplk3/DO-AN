<?php
include "check_admin.php";
include "../config/db.php";

$id = $_GET['id'] ?? 0;
$check = mysqli_fetch_assoc(
    mysqli_query($conn,
    "SELECT COUNT(*) AS tong FROM ve WHERE suat_chieu_id = $id")
);

if ($check['tong'] > 0) {
    die("Không thể sửa suất chiếu đã có vé!");
}

// Lấy suất chiếu hiện tại
$suat = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM suat_chieu WHERE id = $id")
);

// Danh sách phim
$phim = mysqli_query($conn, "SELECT * FROM phim");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phim_id = $_POST['phim_id'];
    $ngay = $_POST['ngay'];
    $gio = $_POST['gio'];
    $gia = $_POST['gia'];

    $sql = "
    UPDATE suat_chieu SET
        phim_id = '$phim_id',
        ngay = '$ngay',
        gio = '$gio',
        gia = '$gia'
    WHERE id = $id
    ";
    mysqli_query($conn, $sql);

    header("Location: suat_chieu.php");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa suất chiếu - Cinema Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0b1b2b 0%, #0f172a 50%, #1e293b 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(229, 9, 20, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.06) 0%, transparent 50%);
            animation: backgroundFloat 20s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes backgroundFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(1deg); }
            66% { transform: translateY(10px) rotate(-1deg); }
        }

        /* Header */
        .admin-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            padding: 24px 32px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            font-size: 28px;
            color: #3b82f6;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn-secondary {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
        }

        /* Main container */
        .main-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 24px;
            position: relative;
            z-index: 10;
        }

        /* Form card */
        .form-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 40px;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 8px 16px rgba(59, 130, 246, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #e50914, #10b981);
        }

        /* Form header */
        .form-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .form-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Form group */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid rgba(59, 130, 246, 0.2);
            border-radius: 10px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-select option {
            background: #1e293b;
            color: #ffffff;
        }

        /* Action buttons */
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 160px;
            justify-content: center;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e50914 0%, #b20710 100%);
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(229, 9, 20, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff2e36 0%, #d60512 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(229, 9, 20, 0.4);
        }

        .btn-secondary-form {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 2px solid rgba(59, 130, 246, 0.3);
        }

        .btn-secondary-form:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }

        /* Decorative elements */
        .decoration-1 {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .decoration-2 {
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 80px;
            height: 80px;
            background: radial-gradient(circle, rgba(229, 9, 20, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .admin-header {
                padding: 16px 20px;
                flex-direction: column;
                gap: 16px;
            }

            .header-title {
                font-size: 20px;
            }

            .main-container {
                margin: 20px auto;
                padding: 0 16px;
            }

            .form-card {
                padding: 24px;
            }

            .form-title {
                font-size: 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <i class="fas fa-film header-icon"></i>
            <div class="header-title">Quản lý suất chiếu</div>
        </div>
        <div class="header-actions">
            <a href="suat_chieu.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Về trang chính
            </a>
        </div>
    </header>

    <main class="main-container">
        <div class="form-card">
            <div class="decoration-1"></div>
            <div class="decoration-2"></div>

            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-edit"></i>
                    Sửa suất chiếu
                </h1>
                <p class="form-subtitle">Cập nhật thông tin suất chiếu</p>
            </div>

            <form method="post" id="editShowtimeForm">
                <div class="form-group">
                    <label class="form-label" for="phim_id">
                        <i class="fas fa-film"></i> Phim
                    </label>
                    <select name="phim_id" id="phim_id" class="form-select" required>
                        <?php 
                        mysqli_data_seek($phim, 0);
                        while ($p = mysqli_fetch_assoc($phim)): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= ($p['id'] == $suat['phim_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['ten_phim']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ngay">
                        <i class="fas fa-calendar-alt"></i> Ngày chiếu
                    </label>
                    <input type="date" name="ngay" id="ngay" class="form-input"
                           value="<?= $suat['ngay'] ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gio">
                        <i class="fas fa-clock"></i> Giờ chiếu
                    </label>
                    <input type="time" name="gio" id="gio" class="form-input"
                           value="<?= $suat['gio'] ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gia">
                        <i class="fas fa-dollar-sign"></i> Giá vé (VNĐ)
                    </label>
                    <input type="number" name="gia" id="gia" class="form-input"
                           value="<?= $suat['gia'] ?>" min="0" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Lưu thay đổi
                    </button>
                    <a href="suat_chieu.php" class="btn btn-secondary-form">
                        <i class="fas fa-times"></i>
                        Hủy bỏ
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Loading state for form submission
        document.getElementById('editShowtimeForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
            submitBtn.disabled = true;
        });

        // Auto-focus first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('phim_id').focus();
        });
    </script>
</body>
</html>
