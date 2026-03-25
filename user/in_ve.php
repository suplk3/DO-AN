<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['ve_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$ve_id = (int)$_GET['ve_id'];

// Lấy thông tin vé
$sql = "SELECT v.id AS ve_id, p.ten_phim, p.poster, sc.ngay, sc.gio,
               r.ten_rap, pc.ten_phong, g.ten_ghe
        FROM ve v
        LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
        LEFT JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        LEFT JOIN ghe g ON v.ghe_id = g.id
        WHERE v.id = ? AND v.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $ve_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ve = mysqli_fetch_assoc($result);

if (!$ve) {
    echo "Không tìm thấy vé hoặc bạn không có quyền truy cập.";
    exit();
}

// Dữ liệu cho QR code
$qr_data = "ID Vé: " . $ve['ve_id'] . "\n" .
           "Phim: " . $ve['ten_phim'] . "\n" .
           "Rạp: " . $ve['ten_rap'] . " - " . $ve['ten_phong'] . "\n" .
           "Ghế: " . $ve['ten_ghe'] . "\n" .
           "Ngày: " . date('d/m/Y', strtotime($ve['ngay'])) . "\n" .
           "Giờ: " . substr($ve['gio'], 0, 5);

$qr_code_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . urlencode($qr_data);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Vé - <?= htmlspecialchars($ve['ten_phim']) ?></title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; margin: 0; }
            .no-print { display: none; }
            .ticket-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f0f0;
        }
        .ticket {
            width: 800px;
            height: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            overflow: hidden;
            position: relative;
        }
        .ticket-stub {
            padding: 20px;
            width: 280px;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .ticket-main {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .ticket-main .poster {
            width: 100%;
            max-width: 250px;
            height: auto;
            border-radius: 8px;
        }
        .ticket-details {
            margin-top: 15px;
        }
        .ticket-details h1 {
            font-size: 26px;
            margin: 0 0 10px;
            font-weight: 700;
        }
        .ticket-details p {
            margin: 5px 0;
            font-size: 16px;
        }
        .ticket-qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .ticket-qr img {
            width: 200px;
            height: 200px;
        }
        .ticket-info {
             font-size: 14px;
        }
        .ticket-info strong {
            font-weight: 600;
        }
        .ticket::before {
            content: '';
            position: absolute;
            left: 279px;
            top: 20px;
            bottom: 20px;
            width: 2px;
            background: repeating-linear-gradient(
                to bottom,
                #ccc, #ccc 5px,
                transparent 5px, transparent 10px
            );
        }
    </style>
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body onload="window.print()">
    <div class="ticket-container">
        <div class="ticket">
            <div class="ticket-stub">
                <div class="ticket-info">
                    <p><strong>RẠP:</strong> <?= htmlspecialchars($ve['ten_rap']) ?></p>
                    <p><strong>PHÒNG:</strong> <?= htmlspecialchars($ve['ten_phong']) ?></p>
                    <p><strong>NGÀY:</strong> <?= date('d/m/Y', strtotime($ve['ngay'])) ?></p>
                    <p><strong>GIỜ:</strong> <?= substr($ve['gio'], 0, 5) ?></p>
                    <p><strong>GHẾ:</strong></p>
                    <h2 style="font-size: 48px; margin: 5px 0;"><?= htmlspecialchars($ve['ten_ghe']) ?></h2>
                </div>
                <div class="ticket-qr">
                    <img src="<?= $qr_code_url ?>" alt="QR Code">
                </div>
            </div>
            <div class="ticket-main">
                <img src="../assets/images/<?= htmlspecialchars($ve['poster']) ?>" alt="Poster Phim" class="poster">
                <div class="ticket-details">
                    <h1><?= htmlspecialchars($ve['ten_phim']) ?></h1>
                </div>
            </div>
        </div>
    </div>
    <div class="no-print" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; margin-right: 10px;">🖨️ In lại vé</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px;">❌ Đóng</button>
    </div>
</body>
</html>
