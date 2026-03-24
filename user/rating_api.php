<?php
include "../config/db.php";
session_start();

header('Content-Type: application/json; charset=utf-8');

function respond(array $arr): void
{
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_column_type(mysqli $conn, string $table, string $column): ?string
{
    $stmt = $conn->prepare("
        SELECT COLUMN_TYPE
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row['COLUMN_TYPE'] ?? null;
}

function ensure_ratings_table(mysqli $conn): bool
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phim_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_phim (user_id, phim_id),
    KEY idx_phim (phim_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        return false;
    }

    $ratingType = get_column_type($conn, 'ratings', 'rating');
    if ($ratingType && stripos($ratingType, 'decimal') === false) {
        if (!$conn->query("ALTER TABLE ratings MODIFY rating DECIMAL(2,1) NOT NULL")) {
            return false;
        }
    }

    if (!column_exists($conn, 'ratings', 'updated_at')) {
        if (!$conn->query("ALTER TABLE ratings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP")) {
            return false;
        }
    }

    return true;
}

function get_rating_summary(mysqli $conn, int $phimId): array
{
    $stmt = $conn->prepare("
        SELECT AVG(rating) AS avg_rating, COUNT(*) AS total
        FROM ratings
        WHERE phim_id = ?
    ");

    if (!$stmt) {
        return ['avg' => 0, 'total' => 0];
    }

    $stmt->bind_param("i", $phimId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return [
        'avg' => ($row && $row['avg_rating'] !== null) ? round((float)$row['avg_rating'], 1) : 0,
        'total' => (int)($row['total'] ?? 0),
    ];
}

function get_my_rating(mysqli $conn, int $userId, int $phimId): float
{
    $stmt = $conn->prepare("
        SELECT rating
        FROM ratings
        WHERE user_id = ? AND phim_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return 0.0;
    }

    $stmt->bind_param("ii", $userId, $phimId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? (float)$row['rating'] : 0.0;
}

function movie_exists(mysqli $conn, int $phimId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM phim WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $phimId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ok = $result && $result->fetch_row();
    $stmt->close();

    return (bool)$ok;
}

function user_has_ticket_for_movie(mysqli $conn, int $userId, int $phimId): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM ve v
        INNER JOIN suat_chieu sc ON sc.id = v.suat_chieu_id
        WHERE v.user_id = ? AND sc.phim_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $userId, $phimId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ok = $result && $result->fetch_row();
    $stmt->close();

    return (bool)$ok;
}

function get_rating_permission(mysqli $conn, int $userId, int $phimId, bool $isAdmin): array
{
    if ($isAdmin) {
        return ['can_rate' => true, 'message' => 'Admin có thể đánh giá mà không cần mua vé.'];
    }

    if (user_has_ticket_for_movie($conn, $userId, $phimId)) {
        return ['can_rate' => true, 'message' => 'Bạn đã mua vé cho phim này và có thể đánh giá.'];
    }

    return ['can_rate' => false, 'message' => 'Bạn cần mua vé phim này trước khi đánh giá.'];
}

function is_valid_half_rating($value): bool
{
    if (!is_numeric($value)) {
        return false;
    }

    $rating = (float)$value;
    if ($rating < 0.5 || $rating > 5) {
        return false;
    }

    return abs(($rating * 2) - round($rating * 2)) < 0.00001;
}

if (!ensure_ratings_table($conn)) {
    respond(['ok' => false, 'message' => 'Không thể khởi tạo bảng đánh giá.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $phimId = (int)($_GET['phim_id'] ?? 0);
    if ($phimId <= 0) {
        respond(['ok' => false, 'message' => 'Phim không hợp lệ.']);
    }

    $summary = get_rating_summary($conn, $phimId);
    $myRating = 0.0;
    $canRate = false;
    $permissionMessage = 'Đăng nhập để chấm điểm phim này.';
    if (isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        $myRating = get_my_rating($conn, $userId, $phimId);
        $permission = get_rating_permission($conn, $userId, $phimId, (($_SESSION['vai_tro'] ?? '') === 'admin'));
        $canRate = $permission['can_rate'];
        $permissionMessage = $permission['message'];
    }

    respond([
        'ok' => true,
        'avg' => $summary['avg'],
        'total' => $summary['total'],
        'my' => $myRating,
        'can_rate' => $canRate,
        'permission_message' => $permissionMessage,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_id'])) {
    respond(['ok' => false, 'message' => 'Bạn cần đăng nhập để đánh giá.']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

$phimId = (int)($data['phim_id'] ?? 0);
$ratingRaw = $data['rating'] ?? null;
if ($phimId <= 0 || !is_valid_half_rating($ratingRaw)) {
    respond(['ok' => false, 'message' => 'Dữ liệu không hợp lệ.']);
}
$rating = (float)$ratingRaw;

if (!movie_exists($conn, $phimId)) {
    respond(['ok' => false, 'message' => 'Phim không tồn tại.']);
}

$userId = (int)$_SESSION['user_id'];
$isAdmin = (($_SESSION['vai_tro'] ?? '') === 'admin');
$permission = get_rating_permission($conn, $userId, $phimId, $isAdmin);
if (!$permission['can_rate']) {
    respond(['ok' => false, 'message' => $permission['message']]);
}

$existingId = 0;

$stmt = $conn->prepare("
    SELECT id
    FROM ratings
    WHERE user_id = ? AND phim_id = ?
    LIMIT 1
");

if (!$stmt) {
    respond(['ok' => false, 'message' => 'Không thể xử lý đánh giá.']);
}

$stmt->bind_param("ii", $userId, $phimId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if ($row) {
    if (column_exists($conn, 'ratings', 'updated_at')) {
        $stmt = $conn->prepare("
            UPDATE ratings
            SET rating = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            UPDATE ratings
            SET rating = ?
            WHERE id = ?
        ");
    }

    if (!$stmt) {
        respond(['ok' => false, 'message' => 'Không thể cập nhật đánh giá.']);
    }

    $existingId = (int)$row['id'];
    $stmt->bind_param("di", $rating, $existingId);
    $ok = $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        INSERT INTO ratings (user_id, phim_id, rating)
        VALUES (?, ?, ?)
    ");

    if (!$stmt) {
        respond(['ok' => false, 'message' => 'Không thể lưu đánh giá.']);
    }

    $stmt->bind_param("iid", $userId, $phimId, $rating);
    $ok = $stmt->execute();
    $stmt->close();
}

if (empty($ok)) {
    respond(['ok' => false, 'message' => 'Không thể lưu đánh giá.']);
}

$summary = get_rating_summary($conn, $phimId);

respond([
    'ok' => true,
    'avg' => $summary['avg'],
    'total' => $summary['total'],
    'my' => $rating,
]);
