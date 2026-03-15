<?php
include "../config/db.php";
session_start();

// --- Basic Input Validation ---
if (!isset($_POST['suat_chieu_id'], $_POST['ghe']) || empty($_POST['ghe'])) {
    die("Lỗi: Dữ liệu không hợp lệ. Vui lòng thử lại.");
}

// extra fields from payment page
if (empty($_POST['agree']) || !isset($_POST['payment_method'])) {
    die("Vui lòng đồng ý điều khoản và chọn hình thức thanh toán.");
}
$payment_method = $_POST['payment_method'];

$user_id = $_SESSION['user_id'] ?? 1; // Fallback for testing
$suat_chieu_id = (int)$_POST['suat_chieu_id'];
$ghe_array = array_filter(explode(",", $_POST['ghe']));

if (empty($ghe_array)) {
    die("Lỗi: Chưa chọn ghế nào.");
}

// --- Get phong_id from suat_chieu_id ---
$phong_id = null;
$stmt = mysqli_prepare($conn, "SELECT phong_id FROM suat_chieu WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $phong_id = (int)$row['phong_id'];
}
mysqli_stmt_close($stmt);

if (!$phong_id) {
    die("Lỗi: Suất chiếu không tồn tại.");
}

// --- Begin Transaction với SERIALIZABLE để chống double booking ---
mysqli_query($conn, "SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE");
mysqli_begin_transaction($conn);
$last_ve_id = null;
try {
    // Chuẩn bị statements
    // SELECT FOR UPDATE: lock hàng ghế lại trong transaction
    // Nếu 2 request cùng lúc, request sau sẽ chờ request trước commit/rollback
    $lock_stmt = mysqli_prepare($conn,
        "SELECT g.id FROM ghe g
         WHERE g.ten_ghe = ? AND g.phong_id = ?
         AND NOT EXISTS (
             SELECT 1 FROM ve
             WHERE ve.ghe_id = g.id AND ve.suat_chieu_id = ?
         )
         FOR UPDATE"
    );
    $insert_ve_stmt = mysqli_prepare($conn,
        "INSERT INTO ve (user_id, ghe_id, suat_chieu_id) VALUES (?, ?, ?)"
    );

    foreach ($ghe_array as $ten_ghe) {
        $ten_ghe = trim($ten_ghe);
        if ($ten_ghe === '') continue;

        // Lock + kiểm tra ghế còn trống trong 1 query atomic
        mysqli_stmt_bind_param($lock_stmt, "sii", $ten_ghe, $phong_id, $suat_chieu_id);
        mysqli_stmt_execute($lock_stmt);
        $result_ghe = mysqli_stmt_get_result($lock_stmt);
        $ghe_row    = mysqli_fetch_assoc($result_ghe);

        if (!$ghe_row) {
            // Ghế không tồn tại HOẶC đã bị người khác đặt mất (race condition bị chặn!)
            throw new Exception("GHE_TAKEN::{$ten_ghe}");
        }
        $ghe_id = (int)$ghe_row['id'];

        // Insert vé
        mysqli_stmt_bind_param($insert_ve_stmt, "iii", $user_id, $ghe_id, $suat_chieu_id);
        if (!mysqli_stmt_execute($insert_ve_stmt)) {
            throw new Exception("Lỗi hệ thống khi đặt ghế '{$ten_ghe}'.");
        }
        $ve_id = (int)mysqli_insert_id($conn);
        if ($ve_id) $last_ve_id = $ve_id;
    }

    mysqli_commit($conn);
    mysqli_stmt_close($lock_stmt);
    mysqli_stmt_close($insert_ve_stmt);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $msg = $e->getMessage();

    // Lỗi ghế đã bị đặt → redirect về chọn ghế với thông báo rõ ràng
    if (str_starts_with($msg, 'GHE_TAKEN::')) {
        $taken = str_replace('GHE_TAKEN::', '', $msg);
        $back  = "chon_ghe.php?suat_id={$suat_chieu_id}&taken=" . urlencode($taken);
        header("Location: $back");
        exit;
    }
    die("Đã xảy ra lỗi: " . htmlspecialchars($msg));
}

// --- Fetch enriched show information for the e-ticket ---
$info = null;
$sql = "SELECT p.ten_phim, p.poster, sc.ngay, sc.gio, sc.gia, r.ten_rap, pc.ten_phong
        FROM suat_chieu sc
        JOIN phim p ON sc.phim_id = p.id
        LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
        LEFT JOIN rap r ON pc.rap_id = r.id
        WHERE sc.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $suat_chieu_id);
mysqli_stmt_execute($stmt);
$result_info = mysqli_stmt_get_result($stmt);
if ($result_info) $info = mysqli_fetch_assoc($result_info);
mysqli_stmt_close($stmt);

// --- Prepare data for the view ---
$seat_list = htmlspecialchars(implode(", ", $ghe_array));
$seat_count = count($ghe_array);
$price_per_seat = (isset($info['gia']) && $info['gia'] !== null) ? (int)$info['gia'] : 0;
$total_amount = $seat_count * $price_per_seat;

$movie_name = $info['ten_phim'] ?? 'N/A';
$poster_path = (!empty($info['poster'])) ? "../assets/images/" . htmlspecialchars($info['poster']) : "https://via.placeholder.com/560x315.png?text=Movie+Poster";
$rap_name = $info['ten_rap'] ?? 'N/A';
$phong_name = $info['ten_phong'] ?? 'N/A';
$show_date = $info ? date('d/m/Y', strtotime($info['ngay'])) : 'N/A';
$show_time = $info ? date('H:i', strtotime($info['gio'])) : 'N/A';
$ticket_id_display = 'TTVH-' . str_pad($last_ve_id ?? 0, 8, '0', STR_PAD_LEFT);

// QR Code & Barcode data
$qr_data = "TicketID: $ticket_id_display | Movie: $movie_name | Seats: $seat_list | Total: $total_amount";
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode($qr_data);



mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt vé thành công - TTVH Cinemas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user-index.css">
    <style>
        body {
            background-color: #0a0a0a;
            color: #e0e0e0;
        }
        #fireworks-canvas {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Behind all content */
        }
        main {
            position: relative;
            z-index: 10;
        }
        .ticket-container {
            max-width: 560px; /* Wider ticket */
            margin: 40px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .ticket {
            background: #1e1e1e;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid #333;
        }
        .ticket-header {
            background: url('<?= $poster_path ?>') no-repeat center center;
            background-size: cover;
            padding: 20px;
            position: relative;
            display: flex;
            align-items: flex-end;
            min-height: 200px;
        }
        .ticket-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to top, rgba(30,30,30,1) 20%, rgba(30,30,30,0.6) 50%, rgba(30,30,30,0) 100%);
        }
        .ticket-header-content {
            position: relative;
            z-index: 2;
        }
        .ticket-header h2 {
            color: #fff;
            font-size: 32px;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .ticket-body {
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .info-section {
            position: relative;
        }
        .info-section .details { font-size: 16px; }
        .info-section .details strong {
            color: #aaa;
            font-size: 13px;
            margin-bottom: 6px;
            display: block;
            font-weight: 500;
            text-transform: uppercase;
        }
        .info-section .details span {
            color: #fff;
            font-weight: 600;
            font-size: 18px;
        }
        .info-section .details span.seat-list {
            font-weight: 700;
            color: #7FFF00;
            font-size: 17px;
        }
        .full-width-section {
            padding-top: 25px;
            border-top: 1px solid #333;
        }
        .ticket-cutout {
            height: 20px;
            background: #0a0a0a;
            position: relative;
        }
        .ticket-cutout::before, .ticket-cutout::after {
            content: '';
            position: absolute;
            width: 40px; height: 40px;
            background: #0a0a0a;
            border-radius: 50%;
            top: -20px;
        }
        .ticket-cutout::before { left: -20px; }
        .ticket-cutout::after { right: -20px; }
        .ticket-bottom-part {
            display: flex;
            align-items: center;
            padding: 30px;
            background: #111;
        }
        .ticket-qr-section {
            text-align: center;
            padding-right: 30px;
            border-right: 1px dashed #555;
            flex-shrink: 0;
        }
        .ticket-qr-section img.qr-code {
            display: block;
            width: 140px;
            height: 140px;
            border: 6px solid #fff;
            border-radius: 10px;
        }
        .ticket-id {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #888;
            margin-top: 10px;
        }
        .ticket-total-section {
            flex-grow: 1;
            padding-left: 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .total-amount span {
            color: #aaa;
            font-size: 16px;
            text-transform: uppercase;
        }
        .total-amount div {
            font-size: 38px;
            font-weight: bold;
            color: #7FFF00;
            margin-top: 8px;
        }
        .barcode {
            margin-top: 20px;
            max-width: 100%;
            height: 50px;
            object-fit: contain;
            filter: brightness(1.5) contrast(3) invert(1);
        }
        .ticket-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            background: #7FFF00;
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(127, 255, 0, 0.2);
        }
        .btn.btn-secondary {
            background: #333;
            color: #fff;
        }
        .btn.btn-secondary:hover {
            background: #444;
        }
        .success-message {
            background-color: #2e7d32;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 500;
        }
    </style>
</head>
<body class="user-index">

<canvas id="fireworks-canvas"></canvas>

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

<main>
    <div class="ticket-container">
        <div class="success-message">🎉 Đặt vé thành công! 🎉</div>
        <div class="ticket">
            <div class="ticket-header">
                <div class="ticket-header-content">
                    <h2><?= htmlspecialchars($movie_name) ?></h2>
                </div>
            </div>
            <div class="ticket-body">
                <div class="info-grid">
                    <div class="info-section">
                        <div class="details">
                            <strong>Ngày chiếu</strong>
                            <span><?= htmlspecialchars($show_date) ?></span>
                        </div>
                    </div>
                    <div class="info-section">
                        <div class="details">
                            <strong>Giờ chiếu</strong>
                            <span><?= htmlspecialchars($show_time) ?></span>
                        </div>
                    </div>
                </div>
                <div class="info-grid">
                     <div class="info-section full-width-section">
                        <div class="details">
                            <strong>Rạp/Phòng</strong>
                            <span><?= htmlspecialchars($rap_name) ?> / <?= htmlspecialchars($phong_name) ?></span>
                        </div>
                    </div>
                </div>
                <div class="info-section full-width-section">
                    <div class="details">
                        <strong>Ghế</strong>
                        <span class="seat-list"><?= $seat_list ?> (<?= $seat_count ?> ghế)</span>
                    </div>
                </div>
            </div>
            <div class="ticket-cutout"></div>
            <div class="ticket-bottom-part">
                <div class="ticket-qr-section">
                    <img class="qr-code" src="<?= $qr_code_url ?>" alt="Ticket QR Code">
                    <div class="ticket-id"><?= htmlspecialchars($ticket_id_display) ?></div>
                </div>
                <div class="ticket-total-section">
                    <div class="total-amount">
                        <span>Tổng cộng</span>
                        <div><?= number_format($total_amount, 0, ',', '.') ?>₫</div>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="ticket-actions">
            <a href="index.php" class="btn">🏠 Về trang chủ</a>
        </div>
    </div>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — All Rights Reserved.</div>
</footer>

<script>
    // --- Fireworks script ---
    window.requestAnimFrame = (() => {
        return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            function (callback) {
                window.setTimeout(callback, 1000 / 60);
            };
    })();

    const canvas = document.getElementById('fireworks-canvas');
    const ctx = canvas.getContext('2d');
    const cw = window.innerWidth;
    const ch = window.innerHeight;
    canvas.width = cw;
    canvas.height = ch;

    let fireworks = [];
    let particles = [];
    let hue = 120;
    const limiterTotal = 5;
    let limiterTick = 0;
    const timerTotal = 80;
    let timerTick = 0;
    let mousedown = false;
    let mx, my;

    function random(min, max) {
        return Math.random() * (max - min) + min;
    }

    function calculateDistance(p1x, p1y, p2x, p2y) {
        const xDistance = p1x - p2x;
        const yDistance = p1y - p2y;
        return Math.sqrt(Math.pow(xDistance, 2) + Math.pow(yDistance, 2));
    }

    function Firework(sx, sy, tx, ty) {
        this.x = sx;
        this.y = sy;
        this.sx = sx;
        this.sy = sy;
        this.tx = tx;
        this.ty = ty;
        this.distanceToTarget = calculateDistance(sx, sy, tx, ty);
        this.distanceTraveled = 0;
        this.coordinates = [];
        this.coordinateCount = 3;
        while (this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = Math.atan2(ty - sy, tx - sx);
        this.speed = 2;
        this.acceleration = 1.05;
        this.brightness = random(50, 70);
        this.targetRadius = 1;
    }

    Firework.prototype.update = function (index) {
        this.coordinates.pop();
        this.coordinates.unshift([this.x, this.y]);
        if (this.targetRadius < 8) {
            this.targetRadius += 0.3;
        } else {
            this.targetRadius = 1;
        }
        this.speed *= this.acceleration;
        const vx = Math.cos(this.angle) * this.speed;
        const vy = Math.sin(this.angle) * this.speed;
        this.distanceTraveled = calculateDistance(this.sx, this.sy, this.x + vx, this.y + vy);
        if (this.distanceTraveled >= this.distanceToTarget) {
            createParticles(this.tx, this.ty);
            fireworks.splice(index, 1);
        } else {
            this.x += vx;
            this.y += vy;
        }
    }

    Firework.prototype.draw = function () {
        ctx.beginPath();
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = `hsl(${hue}, 100%, ${this.brightness}%)`;
        ctx.stroke();
    }

    function Particle(x, y) {
        this.x = x;
        this.y = y;
        this.coordinates = [];
        this.coordinateCount = 5;
        while (this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = random(0, Math.PI * 2);
        this.speed = random(1, 10);
        this.friction = 0.95;
        this.gravity = 1;
        this.hue = random(hue - 20, hue + 20);
        this.brightness = random(50, 80);
        this.alpha = 1;
        this.decay = random(0.015, 0.03);
    }

    Particle.prototype.update = function (index) {
        this.coordinates.pop();
        this.coordinates.unshift([this.x, this.y]);
        this.speed *= this.friction;
        this.x += Math.cos(this.angle) * this.speed;
        this.y += Math.sin(this.angle) * this.speed + this.gravity;
        this.alpha -= this.decay;
        if (this.alpha <= this.decay) {
            particles.splice(index, 1);
        }
    }

    Particle.prototype.draw = function () {
        ctx.beginPath();
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = `hsla(${this.hue}, 100%, ${this.brightness}%, ${this.alpha})`;
        ctx.stroke();
    }

    function createParticles(x, y) {
        let particleCount = 30;
        while (particleCount--) {
            particles.push(new Particle(x, y));
        }
    }

    function loop() {
        requestAnimFrame(loop);
        hue += 0.5;
        ctx.globalCompositeOperation = 'destination-out';
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(0, 0, cw, ch);
        ctx.globalCompositeOperation = 'lighter';

        let i = fireworks.length;
        while (i--) {
            fireworks[i].draw();
            fireworks[i].update(i);
        }
        let j = particles.length;
        while (j--) {
            particles[j].draw();
            particles[j].update(j);
        }

        if (timerTick >= timerTotal) {
            if (!mousedown) {
                fireworks.push(new Firework(cw / 2, ch, random(0, cw), random(0, ch / 2)));
                timerTick = 0;
            }
        } else {
            timerTick++;
        }
        
        if (limiterTick >= limiterTotal) {
            if (mousedown) {
                fireworks.push(new Firework(cw / 2, ch, mx, my));
                limiterTick = 0;
            }
        } else {
            limiterTick++;
        }
    }

    window.onload = loop;
</script>

</body>
</html>