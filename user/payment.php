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
body {
    background-color: #0a0a0a; /* Slightly darker background */
    color: #e0e0e0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.header {
    background-color: #1e1e1e;
    box-shadow: 0 2px 4px rgba(0,0,0,0.5);
}
.logo {
    color: #7FFF00;
}
.nav-link, .link {
    color: #7FFF00;
}
.container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    max-width: 1200px;
    margin: 40px auto;
}
.left, .right {
    box-sizing: border-box;
    background: #1e1e1e;
    padding: 25px;
    border-radius: 15px; /* Increased border-radius */
    border: 1px solid #333;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Added shadow */
}
.left {
    flex: 1 1 60%;
}
.right {
    flex: 1 1 35%;
    position: relative;
}
.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #7FFF00;
    border-bottom: 1px solid #444;
    padding-bottom: 10px;
}
.input-group {
    margin-bottom: 15px;
}
.input-group label {
    display: block;
    margin-bottom: 5px;
    color: #bbb;
    font-weight: 500;
}
.input-group input[type="text"],
.input-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #444;
    border-radius: 8px; /* Rounded inputs */
    background-color: #2c2c2c;
    color: #e0e0e0;
    transition: border-color 0.3s, box-shadow 0.3s;
}
.input-group input[type="text"]:focus,
.input-group select:focus {
    border-color: #7FFF00;
    box-shadow: 0 0 8px rgba(127, 255, 0, 0.4);
    outline: none;
}
/* New Payment Methods Style */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}
.payment-methods .payment-option {
    border: 1px solid #444;
    border-radius: 10px;
    padding: 15px;
    background-color: #2c2c2c;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}
.payment-methods .payment-option:hover {
    background-color: #383838;
    transform: translateY(-3px);
}
.payment-methods .payment-option.selected {
    border-color: #7FFF00;
    box-shadow: 0 0 10px rgba(127, 255, 0, 0.5);
}
.payment-methods input[type="radio"] {
    display: none; /* Hide original radio button */
}
.payment-methods .payment-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #fff;
    font-weight: 500;
    text-align: center;
}
.payment-methods .icon {
    height: 32px;
    max-width: 80px;
    object-fit: contain;
}
#qr-payment .qr-container {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    display: inline-block;
    margin-top: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
#qr-payment p {
    color: #e0e0e0;
    font-size: 15px;
}
#qr-message {
    color: #333; /* Text inside the white QR box */
    font-weight: 500;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 5px 0;
    font-size: 15px;
}
.summary-row.total {
    font-weight: 700;
    font-size: 18px;
    border-top: 1px solid #444;
    padding-top: 10px;
    color: #7FFF00;
}
.countdown {
    text-align: center;
    margin-top: 20px;
    font-size: 16px;
    font-weight: bold;
    color: #e57373;
}
/* New Button Style */
.btn-next {
    background: #28a745; /* Green color */
    color: #ffffff;
    padding: 15px 25px; /* Bigger padding */
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px; /* Bigger font size */
    font-weight: bold;
    width: 100%; /* Full width */
    margin-top: 10px;
    transition: background-color 0.3s, transform 0.2s;
}
.btn-next:hover:not(:disabled) {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.btn-next:disabled {
    background: #555;
    color: #888;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
.movie-summary {
    background: #1e1e1e;
    padding: 20px;
    border-radius: 15px; /* Increased border-radius */
    margin-bottom: 20px;
    border: 1px solid #333;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Added shadow */
    overflow: hidden; /* To contain float */
}
.left-poster {
    max-width: 120px;
    float: left;
    margin-right: 20px;
    border-radius: 8px;
}
.left-desc {
    font-size: 14px;
    color: #bbb;
    margin-top: 10px;
}
h2, h3 { color: #7FFF00; }
.right div, .right .summary-row { color: #FFFFFF; }

/* Agreement Checkbox Fix */
.agreement-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
    white-space: nowrap; /* Force single line */
}
.agreement-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.agreement-group label {
    font-size: 15px; /* Slightly smaller font */
    line-height: 1.4;
    color: #ccc;
}
</style>
</head>
<body class="user-index">
<header class="header">
    <div class="header-inner">
        <div class="logo">TTVH</div>
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
            <h2><?= htmlspecialchars($info['ten_phim'] ?? '') ?></h2>
            <?php if(!empty($info['mo_ta'])): ?>
                <p class="left-desc" style="clear: both; padding-top: 10px;"><?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'],0,300,'...'))) ?></p>
            <?php endif; ?>
        </div>
        <form action="dat_ve.php" method="POST" id="payment-form">
            <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">
            <input type="hidden" name="ghe" value="<?= htmlspecialchars(implode(",", $ghe_list)) ?>">

            <div class="section">
                <div class="section-title">Bước 1: Mã giảm giá & Điểm thưởng</div>
                <div class="input-group">
                    <input type="text" id="voucher" name="voucher" placeholder="Mã giảm giá">
                </div>
                <div class="input-group">
                    <input type="text" id="ttvh_points" name="ttvh_points" placeholder="Sử dụng điểm TTVH">
                </div>
            </div>

            <div class="section">
                <div class="section-title">Bước 2: Thẻ quà tặng</div>
                <div class="input-group">
                    <input type="text" id="gift_card" name="gift_card" placeholder="Nhập mã thẻ quà tặng">
                </div>
            </div>

            <div class="section">
                <div class="section-title">Bước 3: Hình thức thanh toán</div>
                <div class="payment-methods">
                    <div class="payment-option">
                        <input type="radio" id="pay-card" name="payment_method" value="card">
                        <label for="pay-card" class="payment-label">
                            <img src="https://img.icons8.com/color/96/000000/atm.png" alt="ATM" class="icon">
                            ATM / Internet Banking
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-visa" name="payment_method" value="visa">
                        <label for="pay-visa" class="payment-label">
                            <img src="https://img.icons8.com/color/96/000000/visa.png" alt="Visa" class="icon" style="max-width: 60px;">
                            Thẻ quốc tế
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-zalopay" name="payment_method" value="zalopay">
                        <label for="pay-zalopay" class="payment-label">
                             <img src="https://img.icons8.com/color/96/zalo.png" alt="ZaloPay" class="icon">
                            ZaloPay
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="pay-vnpay" name="payment_method" value="vnpay">
                        <label for="pay-vnpay" class="payment-label">
                            <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPAY" class="icon">
                            VNPAY
                        </label>
                    </div>
                     <div class="payment-option">
                        <input type="radio" id="pay-momo" name="payment_method" value="momo">
                        <label for="pay-momo" class="payment-label">
                            <img src="https://img.mservice.io/momo-payment/icon/images/logo512.png" alt="MoMo" class="icon">
                            MoMo
                        </label>
                    </div>
                </div>

                <div id="payment-details" style="display: none; margin-top: 20px;">
                    <div id="qr-payment" style="display: none; text-align: center;">
                        <p>Quét mã QR để hoàn tất thanh toán</p>
                        <div class="qr-container">
                            <img src="" alt="QR Code" id="qr-code-image" style="max-width: 250px; display: block;">
                        </div>
                        <p id="qr-message" style="margin-top: 15px; font-weight:500;"></p>
                    </div>

                    <div id="card-payment" style="display: none;">
                        <div class="input-group">
                            <input type="text" id="card_number" name="card_number" placeholder="Số thẻ">
                        </div>
                        <div class="input-group">
                            <input type="text" id="card_name" name="card_name" placeholder="Tên chủ thẻ (không dấu)">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div class="input-group" style="flex: 1;">
                                <input type="text" id="card_expiry" name="card_expiry" placeholder="Ngày hết hạn (MM/YY)">
                            </div>
                            <div class="input-group" style="flex: 1;">
                                <input type="text" id="card_cvv" name="card_cvv" placeholder="CVV">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="agreement-group">
                <input type="checkbox" id="agree" name="agree">
                <label for="agree" style="font-size: 15px;">Tôi đồng ý với điều khoản sử dụng và xác nhận mua vé cho người có độ tuổi phù hợp.</label>
            </div>

            <div id="validation-message" style="color: #ffcc80; margin-bottom: 10px; display: none; text-align:center;">Vui lòng đồng ý điều khoản và chọn hình thức thanh toán.</div>
            <button type="submit" id="btn-submit" class="btn-next" disabled>Hoàn tất thanh toán</button>
        </form>
    </div>

    <div class="right">
        <?php if (!empty($info['poster'])): ?>
            <img class="summary-poster" src="../assets/images/<?= htmlspecialchars($info['poster']) ?>" alt="<?= htmlspecialchars($info['ten_phim']) ?>" style="width: 390px; max-width: 100%; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <?php endif; ?>
        <h3>Chi tiết vé</h3>
        <div><strong>Phim:</strong> <?= htmlspecialchars($info['ten_phim'] ?? '') ?></div>
        <?php if ($info): ?>
            <div class="summary-row"><span>Thời gian:</span><span><?= fmt_date($info['ngay']) ?> - <?= fmt_time($info['gio']) ?></span></div>
            <div class="summary-row"><span>Rạp:</span><span><?= htmlspecialchars($info['ten_rap'] ?? '') ?> - <?= htmlspecialchars($info['ten_phong'] ?? '') ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Ghế:</span><span style="font-weight:bold; color: #7FFF00;"><?= $seat_list ?> (<?= $seat_count ?>)</span></div>
        <div class="summary-row"><span>Giá vé:</span><span><?= fmt_money($Price) ?></span></div>
        <hr style="border-color: #333; margin: 10px 0;">
        <div class="summary-row total"><span>TỔNG CỘNG:</span><span><?= fmt_money($total_amount) ?></span></div>

        <div class="countdown" id="countdown">Thời gian giữ vé: <span id="time">10:00</span></div>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — All Rights Reserved.</div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const agree = document.getElementById('agree');
    const btn = document.getElementById('btn-submit');
    const paymentOptions = document.querySelectorAll('.payment-option');
    const validationMessage = document.getElementById('validation-message');
    let hasInteracted = false;

    function validate() {
        const isAgreed = agree.checked;
        const paymentMethodSelected = document.querySelector('input[name="payment_method"]:checked') !== null;
        const isValid = isAgreed && paymentMethodSelected;
        
        btn.disabled = !isValid;

        if (hasInteracted) {
            validationMessage.style.display = isValid ? 'none' : 'block';
        }
    }

    function handleInteraction() {
        if (!hasInteracted) hasInteracted = true;
        validate();
    }
    
    let seconds = 600;
    const timeEl = document.getElementById('time');
    function updateTimer() {
        const m = Math.floor(seconds/60);
        const s = seconds % 60;
        timeEl.textContent = m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
        if (seconds <= 0) {
            clearInterval(timer);
            alert('Thời gian đặt vé đã hết. Vui lòng thực hiện lại.');
            window.location.href = 'index.php';
        }
        seconds--;
    }
    const timer = setInterval(updateTimer, 1000);
    updateTimer();

    const paymentDetailsContainer = document.getElementById('payment-details');
    const qrPayment = document.getElementById('qr-payment');
    const cardPayment = document.getElementById('card-payment');
    const qrCodeImage = document.getElementById('qr-code-image');
    const qrMessage = document.getElementById('qr-message');

    const qrSources = {
        zalopay: {
            src: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=zalopay-payment-for-total-<?= $total_amount ?>',
            message: 'Mở ứng dụng ZaloPay và quét mã QR để hoàn tất thanh toán.'
        },
        vnpay: {
            src: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=vnpay-payment-for-total-<?= $total_amount ?>',
            message: 'Mở ứng dụng ngân hàng hỗ trợ VNPAY QR và quét mã để hoàn tất thanh toán.'
        },
        momo: {
            src: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=momo-payment-for-total-<?= $total_amount ?>',
            message: 'Mở ứng dụng MoMo và quét mã QR để hoàn tất thanh toán.'
        }
    };

    agree.addEventListener('change', handleInteraction);

    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Unselect all others
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            // Select this one
            this.classList.add('selected');
            // Check the hidden radio button
            this.querySelector('input[type="radio"]').checked = true;

            handleInteraction();

            const selectedMethod = this.querySelector('input[type="radio"]').value;
            
            paymentDetailsContainer.style.display = 'block';
            qrPayment.style.display = 'none';
            cardPayment.style.display = 'none';

            if (qrSources[selectedMethod]) {
                qrPayment.style.display = 'block';
                qrCodeImage.src = qrSources[selectedMethod].src;
                qrMessage.textContent = qrSources[selectedMethod].message;
            } else if (selectedMethod === 'card' || selectedMethod === 'visa') {
                cardPayment.style.display = 'block';
            } else {
                paymentDetailsContainer.style.display = 'none';
            }
        });
    });

    validate(); // Initial check
});
</script>

</body>
</html>
