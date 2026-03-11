<?php
include '../config/db.php';

$suat_chieu_id = $_GET['suat_id'] ?? 0;

if ($suat_chieu_id == 0) {
    die("Thiếu suất chiếu");
}

$sql = "
SELECT 
    ghe.id,
    ghe.ten_ghe,
    EXISTS (
        SELECT 1 
        FROM ve 
        WHERE ve.ghe_id = ghe.id 
        AND ve.suat_chieu_id = $suat_chieu_id
    ) AS da_dat
FROM ghe
WHERE ghe.phong_id = (SELECT phong_id FROM suat_chieu WHERE id = $suat_chieu_id LIMIT 1)
ORDER BY ghe.ten_ghe
";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi SQL: " . mysqli_error($conn));
}

// Lấy thông tin phim và suất chiếu
$info_sql = "
SELECT 
    p.id AS phim_id, p.ten_phim, p.poster, p.the_loai, p.thoi_luong, p.mo_ta,
    s.ngay, s.gio, s.gia,
    pc.ten_phong, r.ten_rap
FROM suat_chieu s
LEFT JOIN phim p ON s.phim_id = p.id
LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
LEFT JOIN rap r ON pc.rap_id = r.id
WHERE s.id = $suat_chieu_id
";
$info_result = mysqli_query($conn, $info_sql);
$info = mysqli_fetch_assoc($info_result);

if (!$info) {
    die("Không tìm thấy suất chiếu");
}

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chọn ghế - <?= htmlspecialchars($info['ten_phim']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/movie-detail.css">
    <style>
    /* ensure seat labels are always white for visibility */
    .seat-selection .seat {
        color: #fff !important;
    }
    .seat-selection .seat.booked {
        color: #fff !important;
    }
    .seat-selection .seat:hover {
        color: #fff !important;
    }
    </style>
</head>
<body class="movie-detail-page">

<header class="header">
    <div class="header-inner">
        <div class="logo">TTVH</div>
        <nav class="menu">
            <a href="index.php" class="nav-link">🎬 PHIM</a>
        </nav>
        <?php
        $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');
        $ticket_label = $is_admin ? 'QUẢN LÝ USER' : 'VÉ CỦA TÔI';
        ?>
        <div class="actions">
            <a href="ve_cua_toi.php" class="link">🎟️ <?= $ticket_label ?></a>
        </div>
    </div>
</header>

<main class="md-container">
    <a class="back" href="chi_tiet_phim.php?id=<?= $info['phim_id'] ?? 0 ?>">← Quay lại chi tiết phim</a>

    <section class="seat-selection">
        <h1 class="section-title">Chọn ghế</h1>
        <div class="showtime-info">
            <h2><?= htmlspecialchars($info['ten_phim']) ?></h2>
            <p><strong>Ngày:</strong> <?= fmt_date($info['ngay']) ?> | <strong>Giờ:</strong> <?= fmt_time($info['gio']) ?> | <strong>Giá:</strong> <?= fmt_money($info['gia']) ?></p>
            <p><strong>Rạp:</strong> <?= htmlspecialchars($info['ten_rap']) ?> | <strong>Phòng:</strong> <?= htmlspecialchars($info['ten_phong']) ?></p>
        </div>

        <div class="screen">MÀN HÌNH</div>

        <div class="seat-wrapper">
<?php
$currentRow = '';
while ($row = mysqli_fetch_assoc($result)) {
    $rowChar = substr($row['ten_ghe'], 0, 1);

    if ($currentRow != $rowChar) {
        if ($currentRow != '') echo '</div>';
        echo "<div class='seat-row'>";
        $currentRow = $rowChar;
    }

    $class = $row['da_dat'] ? 'seat booked' : 'seat';

    echo "<button 
            class='$class' 
            data-seat='{$row['ten_ghe']}'
            ".($row['da_dat'] ? 'disabled' : '').">
            {$row['ten_ghe']}
          </button>";
}


if ($currentRow != '') echo '</div>';
?>
        </div>

        <div class="checkout">
            <p>Ghế đã chọn: <strong id="selected-seats"></strong></p>
            <p>Tổng tiền: <strong id="total">0 đ</strong></p>

            <form action="payment.php" method="POST">
                <input type="hidden" name="ghe" id="seat-input">
                <input type="hidden" name="suat_chieu_id" value="<?= $suat_chieu_id ?>">

                <button type="submit" class="btn-primary">TIẾP TỤC THANH TOÁN</button>
            </form>
        </div>
    </section>

    <section class="movie-info-section">
        <h2 class="section-title">Thông tin phim</h2>
        <div class="movie-info-card">
            <div class="movie-poster">
                <img src="../assets/images/<?= htmlspecialchars($info['poster']) ?>" alt="<?= htmlspecialchars($info['ten_phim']) ?>" loading="lazy">
            </div>
            <div class="movie-details">
                <h3><?= htmlspecialchars($info['ten_phim']) ?></h3>
                <div class="movie-meta">
                    <span class="chip"><?= htmlspecialchars($info['the_loai'] ?: 'Khác') ?></span>
                    <span class="chip"><?= htmlspecialchars($info['thoi_luong'] ? ($info['thoi_luong'] . ' phút') : '') ?></span>
                </div>
                <p class="movie-description">
                    <?= nl2br(htmlspecialchars(mb_strimwidth($info['mo_ta'] ?? '', 0, 300, '...'))) ?>
                </p>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- START OF COMBINED SCRIPT LOGIC ---

    const suatChieuId = <?= json_encode($suat_chieu_id) ?>;
    const giaVe = <?= json_encode($info['gia']) ?>;
    
    let selectedSeats = []; // Mảng chứa các ghế đang được chọn bởi người dùng hiện tại
    
    const selectedSeatsDisplay = document.getElementById("selected-seats");
    const totalDisplay = document.getElementById("total");
    const seatInput = document.getElementById("seat-input");
    const seatWrapper = document.querySelector(".seat-wrapper");

    // Hàm cập nhật UI (danh sách ghế đã chọn và tổng tiền)
    function updateCheckoutUI() {
        selectedSeats.sort(); // Sắp xếp cho đẹp
        selectedSeatsDisplay.innerText = selectedSeats.length > 0 ? selectedSeats.join(", ") : "Chưa có ghế nào";
        totalDisplay.innerText = (selectedSeats.length * giaVe).toLocaleString("vi-VN") + " đ";
        seatInput.value = selectedSeats.join(",");
    }

    // Xử lý khi người dùng click chọn ghế
    seatWrapper.addEventListener("click", function (e) {
        const seatButton = e.target.closest('.seat');
        
        if (!seatButton || seatButton.classList.contains("booked")) {
            return; // Bỏ qua nếu không phải ghế hoặc ghế đã được đặt
        }

        const seatName = seatButton.dataset.seat;
        seatButton.classList.toggle("selected");

        if (selectedSeats.includes(seatName)) {
            // Nếu ghế đã có trong danh sách, loại bỏ nó
            selectedSeats = selectedSeats.filter(s => s !== seatName);
        } else {
            // Nếu chưa có, thêm nó vào
            selectedSeats.push(seatName);
        }

        updateCheckoutUI(); // Cập nhật lại UI
    });

    // Ngăn chặn việc thanh toán nếu chưa chọn ghế
    document.querySelector('.checkout form').addEventListener('submit', function(e) {
        if (selectedSeats.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một ghế.');
        }
    });

    // Hàm kiểm tra và cập nhật trạng thái ghế từ server (polling)
    async function updateBookedSeats() {
        try {
            const response = await fetch(`../get_seats.php?suat_id=${suatChieuId}&_=${new Date().getTime()}`);
            if (!response.ok) {
                console.error("Lỗi khi lấy trạng thái ghế. Status:", response.status);
                return;
            }
            const serverSeats = await response.json();

            let selectionChanged = false; // Cờ để kiểm tra xem lựa chọn có bị thay đổi không

            serverSeats.forEach(serverSeat => {
                const seatElement = seatWrapper.querySelector(`[data-seat='${serverSeat.ten_ghe}']`);
                if (!seatElement) return;

                const isBookedOnServer = serverSeat.da_dat === 1;
                const isBookedOnClient = seatElement.classList.contains('booked');

                // Chỉ cập nhật nếu trạng thái trên server là 'booked' và client chưa cập nhật
                if (isBookedOnServer && !isBookedOnClient) {
                    seatElement.classList.add('booked');
                    seatElement.classList.remove('selected'); // Bỏ chọn nếu người khác đã đặt
                    seatElement.disabled = true;

                    if (selectedSeats.includes(serverSeat.ten_ghe)) {
                        selectedSeats = selectedSeats.filter(s => s !== serverSeat.ten_ghe);
                        selectionChanged = true;
                    }
                }
            });

            if (selectionChanged) {
                updateCheckoutUI();
                alert("Một hoặc nhiều ghế bạn đang chọn đã có người khác đặt. Vui lòng kiểm tra lại lựa chọn của bạn.");
            }

        } catch (error) {
            console.error("Lỗi khi cập nhật trạng thái ghế:", error);
        }
    }

    // --- END OF COMBINED SCRIPT LOGIC ---

    // Khởi chạy
    updateCheckoutUI(); // Cập nhật UI lần đầu
    setInterval(updateBookedSeats, 3000); // Cập nhật trạng thái mỗi 3 giây
});
</script>

</body>
</html>
