<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$thong_bao = '';

// Hủy vé của chính mình
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huy_ve'])) {
    $ve_id = (int)$_POST['ve_id'];
    $sql_huy = "DELETE FROM ve WHERE id = ? AND user_id = ?";
    $stmt_huy = mysqli_prepare($conn, $sql_huy);
    mysqli_stmt_bind_param($stmt_huy, 'ii', $ve_id, $user_id);
    $thong_bao = mysqli_stmt_execute($stmt_huy)
        ? "✅ Hủy vé thành công"
        : "❌ Hủy vé thất bại";
}

// Lấy vé của user hiện tại
$sql = "SELECT v.id AS ve_id, p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia,
               r.ten_rap, pc.ten_phong, g.ten_ghe
        FROM ve v
        LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
        LEFT JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        LEFT JOIN ghe g ON v.ghe_id = g.id
        WHERE v.user_id = ?
        ORDER BY sc.ngay DESC, sc.gio DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ves = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vé của tôi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 840px;
            position: relative;
            color: #000;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #eee;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 20px;
            cursor: pointer;
        }
        .modal-body {
             font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .ticket-printable {
            width: 800px;
            height: 400px;
            display: flex;
            overflow: hidden;
        }
        .ticket-stub { padding: 20px; width: 280px; text-align: left; display: flex; flex-direction: column; justify-content: space-between; border-right: 2px dashed #ccc;}
        .ticket-main { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .ticket-main .poster { width: 100%; max-width: 250px; height: auto; border-radius: 8px; }
        .ticket-details h1 { font-size: 26px; margin: 15px 0 10px; font-weight: 700; color: #000; }
        .ticket-qr img { width: 200px; height: 200px; }
        .ticket-info { font-size: 14px; }
        .ticket-info strong { font-weight: 600; }
        .ticket-info p { margin: 4px 0; }
        .modal-actions { text-align: right; margin-top: 20px; }

        /* Print styles */
        @media print {
            body > *:not(.modal-printable-area) {
                display: none;
            }
            html, body, .modal-printable-area {
                width: 100%;
                height: auto;
                margin: 0 !important;
                padding: 0 !important;
                float: none !important;
                background: #fff !important;
            }
            .modal-content {
                box-shadow: none;
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body class="ve-page">
<div class="wrap">
    <div class="header">
        <h1>🎫 Vé của tôi</h1>
        <a href="../user/index.php" class="btn btn-outline">← Danh sách phim</a>
    </div>

    <?php if (!empty($thong_bao)): ?>
        <div class="msg msg-success"><?= htmlspecialchars($thong_bao) ?></div>
    <?php endif; ?>

    <?php if (empty($ves)): ?>
        <div class="empty">
            <h2>Bạn chưa đặt vé nào</h2>
            <p>Hãy chọn phim và đặt vé để xem lịch sử vé tại đây.</p>
            <a href="../user/index.php" class="btn btn-hero" style="margin-top:12px;">🎬 Đặt vé ngay</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($ves as $ve): ?>
                <div class="card">
                    <img src="../assets/images/<?= htmlspecialchars($ve['poster']) ?>" alt="<?= htmlspecialchars($ve['ten_phim']) ?>">
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($ve['ten_phim']) ?></div>
                        <div class="meta mt">
                            <div>Rạp: <strong><?= htmlspecialchars($ve['ten_rap']) ?></strong></div>
                            <div>Phòng: <strong><?= htmlspecialchars($ve['ten_phong']) ?></strong></div>
                        </div>
                        <div class="meta mt">
                            <div>Ghế: <strong><?= htmlspecialchars($ve['ten_ghe']) ?></strong></div>
                            <div><?= date('d/m/Y', strtotime($ve['ngay'])) ?> • <?= substr($ve['gio'],0,5) ?></div>
                        </div>
                        <div class="price">Giá: <?= number_format($ve['gia']) ?> đ</div>
                        <div class="actions">
                            <button class="btn btn-print" onclick="showPrintModal(<?= (int)$ve['ve_id']; ?>)">🖨️ In vé</button>
                            <a class="btn btn-save" href="save_ticket.php?ve_id=<?= (int)$ve['ve_id']; ?>">💾 Lưu vé</a>
                            <form method="POST">
                                <input type="hidden" name="ve_id" value="<?= (int)$ve['ve_id']; ?>">
                                <button type="submit" name="huy_ve" class="btn btn-cancel" onclick="return confirm('Bạn có chắc muốn hủy vé này?')">❌ Hủy vé</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="star-overlay"></div>

<!-- Modal for printing tickets -->
<div id="print-modal-overlay" class="modal-overlay" style="display: none;">
    <div id="print-modal-content" class="modal-content">
        <!-- Content will be injected by JavaScript -->
    </div>
</div>


<script>
function createStar() {
    const overlay = document.getElementById('star-overlay');
    const parent = overlay || document.body;
    const star = document.createElement('div');
    star.className = 'star';
    star.style.left = Math.random() * 100 + 'vw';
    star.style.animationDuration = Math.random() * 3 + 2 + 's';
    star.style.animationDelay = Math.random() * 2 + 's';
    parent.appendChild(star);
    setTimeout(() => { star.remove(); }, 5000);
}
setInterval(createStar, 200);

async function showPrintModal(ve_id) {
    const overlay = document.getElementById('print-modal-overlay');
    const modalContent = document.getElementById('print-modal-content');
    
    // Show loading state
    modalContent.innerHTML = '<p>Đang tải dữ liệu vé...</p>';
    overlay.style.display = 'flex';

    try {
        const response = await fetch(`get_ticket_details.php?ve_id=${ve_id}`);
        if (!response.ok) {
            throw new Error('Không thể tải thông tin vé. Vui lòng thử lại.');
        }
        const ticket = await response.json();

        // Populate the modal with the ticket layout
        modalContent.innerHTML = `
            <div class="modal-body modal-printable-area">
                <div class="ticket-printable">
                    <div class="ticket-stub">
                        <div class="ticket-info">
                            <p><strong>RẠP:</strong> ${ticket.ten_rap}</p>
                            <p><strong>PHÒNG:</strong> ${ticket.ten_phong}</p>
                            <p><strong>NGÀY:</strong> ${ticket.ngay_f}</p>
                            <p><strong>GIỜ:</strong> ${ticket.gio_f}</p>
                            <p><strong>GHẾ:</strong></p>
                            <h2 style="font-size: 48px; margin: 5px 0;">${ticket.ten_ghe}</h2>
                        </div>
                        <div class="ticket-qr">
                            <img src="${ticket.qr_code_url}" alt="QR Code">
                        </div>
                    </div>
                    <div class="ticket-main">
                        <img src="${ticket.poster_url}" alt="Poster Phim" class="poster">
                        <div class="ticket-details">
                            <h1>${ticket.ten_phim}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                 <button onclick="window.print()" class="btn">🖨️ In</button>
                 <button onclick="closePrintModal()" class="btn btn-outline">Đóng</button>
            </div>
             <button onclick="closePrintModal()" class="modal-close">×</button>
        `;

    } catch (error) {
        modalContent.innerHTML = `<p style="color: red;">${error.message}</p> <button onclick="closePrintModal()" class="btn btn-outline">Đóng</button>`;
    }
}

function closePrintModal() {
    const overlay = document.getElementById('print-modal-overlay');
    overlay.style.display = 'none';
}
</script>
</body>
</html>
