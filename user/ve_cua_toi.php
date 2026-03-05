<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
	header('Location: ../auth/login.php');
	exit();
}

$user_id = $_SESSION['user_id'];

// xử lý hủy vé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huy_ve'])) {
	$ve_id = intval($_POST['ve_id']);
	$sql_huy = "DELETE FROM ve WHERE id = ? AND user_id = ?";
	$stmt_huy = mysqli_prepare($conn, $sql_huy);
	mysqli_stmt_bind_param($stmt_huy, "ii", $ve_id, $user_id);
	if (mysqli_stmt_execute($stmt_huy)) {
		$thong_bao = "✅ Hủy vé thành công";
	} else {
		$thong_bao = "❌ Hủy vé thất bại";
	}
}

$sql = "SELECT v.id AS ve_id,
			   p.ten_phim,
			   p.poster,
			   sc.ngay,
			   sc.gio,
			   sc.gia,
			   r.ten_rap,
			   pc.ten_phong,
			   g.ten_ghe
		FROM ve v
		LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
		LEFT JOIN phim p ON sc.phim_id = p.id
		LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id
		LEFT JOIN rap r ON pc.rap_id = r.id
		LEFT JOIN ghe g ON v.ghe_id = g.id
		WHERE v.user_id = ?
		ORDER BY sc.ngay DESC, sc.gio DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) die(mysqli_error($conn));
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ves = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Debug tạm thời: truy cập ?debug=1 để hiện thông tin chẩn đoán
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
	echo "<pre style='background:#fff;padding:12px;border:1px solid #ddd'>";
	echo "Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL') . "\n\n";

	$check_sql = "SELECT COUNT(*) AS cnt FROM ve WHERE user_id = ?";
	$st = mysqli_prepare($conn, $check_sql);
	mysqli_stmt_bind_param($st, 'i', $user_id);
	mysqli_stmt_execute($st);
	$resc = mysqli_stmt_get_result($st);
	$cnt = mysqli_fetch_assoc($resc)['cnt'];
	echo "Số vé trong bảng ve cho user_id={$user_id}: " . $cnt . "\n\n";

	// Chi tiết để kiểm tra mismatch giữa ghe.phong_id và suat_chieu.phong_id
	$diag_sql = "SELECT v.id AS ve_id, v.ghe_id, g.phong_id AS ghe_phong, sc.phong_id AS sc_phong, p.ten_phim, g.ten_ghe
				 FROM ve v
				 LEFT JOIN ghe g ON v.ghe_id = g.id
				 LEFT JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
				 LEFT JOIN phim p ON sc.phim_id = p.id
				 WHERE v.user_id = ?";
	$st2 = mysqli_prepare($conn, $diag_sql);
	mysqli_stmt_bind_param($st2, 'i', $user_id);
	mysqli_stmt_execute($st2);
	$r2 = mysqli_stmt_get_result($st2);
	$rows = mysqli_fetch_all($r2, MYSQLI_ASSOC);
	echo "Chi tiết vé (ve_id, ghe_id, ghe.phong_id, suat_chieu.phong_id, ten_phim, ten_ghe):\n";
	foreach ($rows as $row) {
		echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
	}
	echo "\nVes array (truncated):\n";
	echo htmlspecialchars(substr(print_r(array_slice($ves,0,10), true),0,2000));
	echo "</pre>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Vé của tôi</title>
	<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="ve-page">
<div class="wrap">
	<div class="header">
		<h1>🎫 Vé của tôi</h1>
		<a href="../user/index.php" class="btn btn-outline">← Danh sách phim</a>
	</div>

	<?php if (!empty($thong_bao)): ?>
			<div class="msg msg-success">
		</div>
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
						<img src="../assets/images/<?php echo htmlspecialchars($ve['poster']); ?>" alt="<?php echo htmlspecialchars($ve['ten_phim']); ?>">
						<div class="card-body">
							<div class="card-title"><?php echo htmlspecialchars($ve['ten_phim']); ?></div>
						<div class="meta mt">
								<div>Rạp: <strong><?php echo htmlspecialchars($ve['ten_rap']); ?></strong></div>
								<div>Phòng: <strong><?php echo htmlspecialchars($ve['ten_phong']); ?></strong></div>
							</div>
						<div class="meta mt">
								<div>Ghế: <strong><?php echo htmlspecialchars($ve['ten_ghe']); ?></strong></div>
								<div><?php echo date('d/m/Y', strtotime($ve['ngay'])); ?> • <?php echo substr($ve['gio'],0,5); ?></div>
							</div>
							<div class="price">Giá: <?php echo number_format($ve['gia']); ?> đ</div>

							<div class="actions">
								<button class="btn btn-print" onclick="window.print()">🖨️ In vé</button>
								<a class="btn btn-save" href="save_ticket.php?ve_id=<?php echo (int)$ve['ve_id']; ?>">💾 Lưu vé</a>
								<form method="POST">
									<input type="hidden" name="ve_id" value="<?php echo (int)$ve['ve_id']; ?>">
									<button type="submit" name="huy_ve" class="btn btn-cancel" onclick="return confirm('Bạn có chắc muốn hủy vé này?')">❌ Hủy vé</button>
								</form>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<!-- overlay cho hiệu ứng động -->
<div id="star-overlay"></div>

<script>
function createStar() {
    const overlay = document.getElementById('star-overlay');
    const parent = overlay || document.body;
    const star = document.createElement('div');
    star.className = 'star';
    star.style.left = Math.random() * 100 + 'vw';
    star.style.animationDuration = Math.random() * 3 + 2 + 's'; // 2-5s
    star.style.animationDelay = Math.random() * 2 + 's';
    parent.appendChild(star);

    // Xóa ngôi sao sau khi animation kết thúc
    setTimeout(() => {
        star.remove();
    }, 5000);
}

// Tạo ngôi sao mỗi 200ms
setInterval(createStar, 200);
</script>

</body>
</html>

