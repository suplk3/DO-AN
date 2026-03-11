<?php
include "../config/db.php";
session_start();

// data from previous step (chon_ghe)
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    die("Dữ liệu không hợp lệ. Vui lòng chọn ghế trước khi thanh toán.");
}

$suat_chieu_id = (int)$_POST['suat_chieu_id'];
$ghe_list = array_filter(explode(",", $_POST['ghe']));

if (empty($ghe_list)) {
    die("Chưa có ghế nào được chọn.");
}

// fetch show and movie info
$sql = "SELECT p.ten_phim, p.poster, p.mo_ta, sc.ngay, sc.gio, sc.gia,
        r.ten_rap, pc.ten_phong
        FROM suat_chieu sc
        JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        WHERE sc.id = $suat_chieu_id";
$res = mysqli_query($conn, $sql);
$info = mysqli_fetch_assoc($res);

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }

$seat_list = htmlspecialchars(implode(", ", $ghe_list));
$seat_count = count($ghe_list);
$Price = isset($info['gia']) ? (int)$info['gia'] : 0;
$total_amount = $Price * $seat_count;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thanh toán vé - <?= htmlspecialchars($info['ten_phim'] ?? '') ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<style>
/* payment layout */
.container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    max-width: 1200px;
    margin: 40px auto;
}
.left, .right {
    box-sizing: border-box;
}
.left {
    flex: 1 1 60%;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
}
.right {
    flex: 1 1 35%;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    position: relative;
}
.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-top: 10px;
    margin-bottom: 8px;
}
.input-group {
    margin-bottom: 12px;
}
.input-group label {
    display: block;
    margin-bottom: 4px;
}
.input-group input[type="text"],
.input-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.payment-methods label {
    display: block;
    margin-bottom: 6px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
}
.summary-row.total {
    font-weight: 600;
    border-top: 1px solid #ccc;
    padding-top: 6px;
}
.countdown {
    text-align: center;
    margin-top: 20px;
    font-size: 16px;
    font-weight: bold;
}
.btn-next {
    background: #e71a0f;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}
.btn-next:disabled {
    background: #aaa;
    cursor: not-allowed;
}

/* poster and description styling */
.summary-poster {
    width: 100%;
    border-radius: 4px;
    margin-bottom: 12px;
}
.movie-desc {
    margin-top: 12px;
    font-size: 14px;
    color: #555;
}

/* left-side movie summary */
.movie-summary {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.left-poster {
    max-width: 120px;
    float: left;
    margin-right: 15px;
    border-radius: 4px;
}
.left-desc {
    font-size: 14px;
    color: #333;
    clear: both;
    margin-top: 10px;
}
</style>
</head>
<body class="user-index">
<header class="header">
    <div class="header-inner">
        <div class="logo">CGV</div>
        <nav class="menu">
            <a href="index.php" class="nav-link">🎬 PHIM</a>
        </nav>
        <div class="actions">
            <a href="ve_cua_toi.php" class="link">🎟️ VÉ CỦA TÔI</a>
        </div>
    </div>
</header>

<main class="container">
    <div class="left">
        <div class="movie-summary">
            <?php if(!empty($info['poster'])): ?>
                <img class="left-poster" src="../assets/images/<?=htmlspecialchars($info['poster'])?>" alt="<?=htmlspecialchars($info['ten_phim'])?>">
            <?php endif; ?>
            <h2>Thanh toán - <?= htmlspecialchars($info['ten_phim'] ?? '') ?></h2>
            <?php if(!empty($info['mo_ta'])): ?>
                <p class="left-desc"><?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'],0,300,'...'))) ?></p>
            <?php endif; ?>
        </div>
        <form action="dat_ve.php" method="POST" id="payment-form">
            <!-- preserve seat data -->
            <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">
            <input type="hidden" name="ghe" value="<?= htmlspecialchars(implode(",", $ghe_list)) ?>">

            <div class="section">
                <div class="section-title">Bước 1: Giảm giá</div>
                <div class="input-group">
                    <label for="voucher">Mã giảm giá</label>
                    <input type="text" id="voucher" name="voucher" placeholder="Nhập mã giảm giá">
                </div>
                <div class="input-group">
                    <label for="cgv_points">Điểm CGV</label>
                    <input type="text" id="cgv_points" name="cgv_points" placeholder="Số điểm">
                </div>
            </div>

            <div class="section">
                <div class="section-title">Bước 2: Thẻ quà tặng</div>
                <div class="input-group">
                    <label for="gift_card">Thẻ quà tặng</label>
                    <input type="text" id="gift_card" name="gift_card" placeholder="Nhập mã thẻ">
                </div>
            </div>

            <div class="section">
                <div class="section-title">Bước 3: Hình thức thanh toán</div>
                <div class="payment-methods">
                    <label><input type="radio" name="payment_method" value="card"> ATM card (Thẻ nội địa)</label>
                    <label><input type="radio" name="payment_method" value="visa"> Thẻ quốc tế (Visa, Master, Amex, JCB)</label>
                    <label><input type="radio" name="payment_method" value="momo"> Mã MMCGV - 5K</label>
                    <label><input type="radio" name="payment_method" value="zalopay"> ZaloPay</label>
                    <label><input type="radio" name="payment_method" value="vnpay"> VNPAY</label>
                    <label><input type="radio" name="payment_method" value="s"> Giảm đến 50.000đ</label>
                </div>
            </div>

            <div class="input-group">
                <label><input type="checkbox" id="agree" name="agree"> Tôi đồng ý với điều khoản sử dụng và mua vé cho người có độ tuổi phù hợp</label>
            </div>

            <button type="submit" id="btn-submit" class="btn-next" disabled>Hoàn tất thanh toán</button>
        </form>
    </div>

    <div class="right">
        <?php if (!empty($info['poster'])): ?>
            <img class="summary-poster" src="../assets/images/<?= htmlspecialchars($info['poster']) ?>" alt="<?= htmlspecialchars($info['ten_phim']) ?>">
        <?php endif; ?>
        <h3>Hành trình đặt vé</h3>
        <div><strong>Phim:</strong> <?= htmlspecialchars($info['ten_phim'] ?? '') ?></div>
        <?php if ($info): ?>
            <div class="summary-row"><span>Thời gian:</span><span><?= fmt_date($info['ngay']) ?> <?= fmt_time($info['gio']) ?></span></div>
            <div class="summary-row"><span>Rạp:</span><span><?= htmlspecialchars($info['ten_rap'] ?? '') ?> - <?= htmlspecialchars($info['ten_phong'] ?? '') ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Ghế:</span><span><?= $seat_list ?> (<?= $seat_count ?>)</span></div>
        <div class="summary-row"><span>Giá vé:</span><span><?= fmt_money($Price) ?></span></div>
        <div class="summary-row total"><span>Tổng:</span><span><?= fmt_money($total_amount) ?></span></div>

        <?php if (!empty($info['mo_ta'])): ?>
            <div class="movie-desc"><?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'],0,200,'...'))) ?></div>
        <?php endif; ?>

        <div class="countdown" id="countdown">Thời gian còn lại: <span id="time">10:00</span></div>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> CGV Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const agree = document.getElementById('agree');
    const btn = document.getElementById('btn-submit');
    agree.addEventListener('change', () => {
        btn.disabled = !agree.checked;
    });

    // countdown timer 10 minutes
    let seconds = 600;
    const timeEl = document.getElementById('time');
    function updateTimer() {
        const m = Math.floor(seconds/60);
        const s = seconds % 60;
        timeEl.textContent = m + ':' + (s<10?'0':'')+s;
        if (seconds <= 0) {
            clearInterval(timer);
            alert('Thời gian đặt vé đã hết. Vui lòng chọn lại.');
            window.location.href = 'chon_ghe.php?suat_id=<?= $suat_chieu_id ?>';
        }
        seconds--;
    }
    const timer = setInterval(updateTimer, 1000);
    updateTimer();
});
</script>

</body>
</html>
