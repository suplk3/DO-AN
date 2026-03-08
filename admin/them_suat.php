<?php
include "check_admin.php";
include "../config/db.php";

$phim = mysqli_query($conn, "SELECT * FROM phim");
$raps = mysqli_query($conn, "SELECT * FROM rap");
// load halls for initial state (first theater)
$phong_chieu = [];
if ($raps && $r = mysqli_fetch_assoc($raps)) {
    $firstRapId = $r['id'];
    $phong_chieu_result = mysqli_query($conn, "SELECT * FROM phong_chieu WHERE rap_id = $firstRapId");
    while ($row = mysqli_fetch_assoc($phong_chieu_result)) {
        $phong_chieu[] = $row;
    }
}
// rewind $raps pointer for later loop
mysqli_data_seek($raps, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phim_id = $_POST['phim_id'];
    $rap_id = $_POST['rap_id'];
    $phong_id = $_POST['phong_id'];
    $ngay = $_POST['ngay'];
    $gio = $_POST['gio'];
    $gia = $_POST['gia'];

    $sql = "INSERT INTO suat_chieu (phim_id, phong_id, ngay, gio, gia)
            VALUES ('$phim_id', '$phong_id', '$ngay', '$gio', '$gia')";
    mysqli_query($conn, $sql);

    header("Location: suat_chieu.php");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thêm suất chiếu</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #0b1b2b 0%, #0f172a 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #ffffff;
        min-height: 100vh;
    }

    .admin-header {
        background: linear-gradient(90deg, #0b0e17 0%, #0f1419 100%);
        padding: 20px 40px;
        border-bottom: 2px solid rgba(59, 130, 246, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
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

    .btn-header {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .btn-add {
        background: linear-gradient(135deg, #e50914 0%, #b20710 100%);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
    }

    .btn-add:hover {
        background: linear-gradient(135deg, #ff2e36 0%, #d60512 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(229, 9, 20, 0.4);
    }

    .btn-back {
        background: transparent;
        color: #ffffff;
        border: 1px solid rgba(59, 130, 246, 0.5);
        padding: 9px 19px;
    }

    .btn-back:hover {
        background: rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.8);
    }

    .page-container {
        max-width: 700px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .form-section {
        background: linear-gradient(135deg, #1a1f2e 0%, #161b28 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 14px;
        padding: 40px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4),
                    0 4px 16px rgba(59, 130, 246, 0.12),
                    inset 0 1px 0 rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
    }

    .form-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 30px;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 8px;
        color: #ffffff;
        font-size: 15px;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .form-select option {
        background: #1a1f2e;
        color: #ffffff;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: rgba(59, 130, 246, 0.6);
        background: rgba(15, 23, 42, 0.8);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1),
                    inset 0 1px 2px rgba(59, 130, 246, 0.1);
    }

    .form-input::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
    }

    .btn-submit {
        flex: 1;
        padding: 14px 24px;
        background: linear-gradient(135deg, #e50914 0%, #b20710 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #ff2e36 0%, #d60512 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(229, 9, 20, 0.4);
    }

    .btn-cancel {
        flex: 1;
        padding: 14px 24px;
        background: transparent;
        color: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.4);
        color: #ffffff;
    }

    @media (max-width: 768px) {
        .admin-header {
            flex-direction: column;
            gap: 15px;
            padding: 15px 20px;
        }

        .header-left {
            width: 100%;
        }

        .header-actions {
            width: 100%;
        }

        .btn-header {
            flex: 1;
        }

        .form-section {
            padding: 24px;
        }

        .page-container {
            margin: 20px auto;
        }
    }
</style>
</head>
<body>

<div class="admin-header">
    <div class="header-left">
        <div class="header-title">🎬 QUẢN LÝ SUẤT CHIẾU</div>
    </div>
    <div class="header-actions">
        <button class="btn-header btn-back" onclick="window.location.href='suat_chieu.php'">Về trang chính</button>
    </div>
</div>

<div class="page-container">
    <div class="form-section">
        <div class="form-title">➕ Thêm suất chiếu mới</div>

        <form method="post">
            <div class="form-group">
                <label class="form-label">Phim</label>
                <select name="phim_id" class="form-select" required>
                    <option value="">-- Chọn phim --</option>
                    <?php while ($p = mysqli_fetch_assoc($phim)): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= $p['ten_phim'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Rạp chiếu</label>
                <select name="rap_id" id="rapSelect" class="form-select" required>
                    <option value="">-- Chọn rạp --</option>
                    <?php 
                    mysqli_data_seek($raps, 0);
                    while ($r = mysqli_fetch_assoc($raps)): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['ten_rap']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Phòng chiếu</label>
                <select name="phong_id" id="phongSelect" class="form-select" required>
                    <option value="">-- Chọn phòng --</option>
                    <?php foreach ($phong_chieu as $pc): ?>
                        <option value="<?= $pc['id'] ?>"><?= htmlspecialchars($pc['ten_phong']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Ngày chiếu</label>
                <input type="date" name="ngay" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Giờ chiếu</label>
                <input type="time" name="gio" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Giá vé (đ)</label>
                <input type="number" name="gia" class="form-input" placeholder="Nhập giá vé" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Lưu suất chiếu</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='suat_chieu.php'">Hủy</button>
            </div>
        </form>
    </div>
</div>


<script>
document.getElementById('rapSelect').addEventListener('change', function() {
    var rapId = this.value;
    const phongSelect = document.getElementById('phongSelect');
    
    if (!rapId) {
        phongSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'load_phong.php?rap_id=' + rapId, true);
    xhr.onload = function() {
        if (this.status == 200) {
            phongSelect.innerHTML = '<option value="">-- Chọn phòng --</option>' + this.responseText;
        }
    };
    xhr.send();
});
</script>

</body>
</html>
