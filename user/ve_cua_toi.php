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
	<style>
		body{font-family:Segoe UI, Tahoma, Arial;background:#f5f7fb;padding:24px}
		.wrap{max-width:1100px;margin:0 auto}
		.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
		.header h1{font-size:1.6rem;color:#333}
		.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}
		.card{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08)}
		.card img{width:100%;height:180px;object-fit:cover}
		.card-body{padding:14px}
		.meta{display:flex;justify-content:space-between;color:#666;font-size:0.95rem}
		.actions{display:flex;gap:8px;margin-top:12px}
		.btn{padding:9px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:600}
		.btn-print{background:#2b90ff;color:#fff}
		.btn-cancel{background:#ff4d4f;color:#fff}
		.empty{background:#fff;padding:40px;border-radius:10px;text-align:center}
		@media print{.btn,.header{display:none}.card{box-shadow:none;border:1px solid #ddd}}
	</style>
</head>
<body>
<div class="wrap">
	<div class="header">
		<h1>🎫 Vé của tôi</h1>
		<a href="../user/index.php" class="btn" style="background:#f0f0f0;color:#333;text-decoration:none">← Danh sách phim</a>
	</div>

	<?php if (!empty($thong_bao)): ?>
		<div style="margin-bottom:12px;padding:10px;border-radius:8px;background:#e6ffed;color:#1a7a2e">
			<?php echo htmlspecialchars($thong_bao); ?>
		</div>
	<?php endif; ?>

	<?php if (empty($ves)): ?>
		<div class="empty">
			<h2>Bạn chưa đặt vé nào</h2>
			<p>Hãy chọn phim và đặt vé để xem lịch sử vé tại đây.</p>
			<a href="../user/index.php" class="btn" style="margin-top:12px;background:linear-gradient(90deg,#667eea,#764ba2);color:#fff;text-decoration:none">🎬 Đặt vé ngay</a>
		</div>
	<?php else: ?>
		<div class="grid">
			<?php foreach ($ves as $ve): ?>
				<div class="card">
					<img src="../assets/images/<?php echo htmlspecialchars($ve['poster']); ?>" alt="<?php echo htmlspecialchars($ve['ten_phim']); ?>">
					<div class="card-body">
						<div style="font-weight:700;font-size:1.05rem;color:#222"><?php echo htmlspecialchars($ve['ten_phim']); ?></div>
						<div class="meta" style="margin-top:8px">
							<div>Rạp: <strong><?php echo htmlspecialchars($ve['ten_rap']); ?></strong></div>
							<div>Phòng: <strong><?php echo htmlspecialchars($ve['ten_phong']); ?></strong></div>
						</div>
						<div class="meta" style="margin-top:8px">
							<div>Ghế: <strong><?php echo htmlspecialchars($ve['ten_ghe']); ?></strong></div>
							<div><?php echo date('d/m/Y', strtotime($ve['ngay'])); ?> • <?php echo substr($ve['gio'],0,5); ?></div>
						</div>
						<div style="margin-top:12px;font-weight:700;color:#0f62fe">Giá: <?php echo number_format($ve['gia']); ?> đ</div>

						<div class="actions">
							<button class="btn btn-print" onclick="window.print()">🖨️ In vé</button>
							<a class="btn" href="save_ticket.php?ve_id=<?php echo (int)$ve['ve_id']; ?>" style="background:#01a982;color:#fff;text-decoration:none;padding:9px 12px;border-radius:8px">💾 Lưu vé</a>
							<form method="POST" style="margin:0">
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
</body>
</html>

