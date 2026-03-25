<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function format_chat_time(?string $value): string
{
    if (!$value) {
        return '';
    }

    return date('H:i d/m', strtotime($value));
}

function ensure_admin_chat_table(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_user_id (user_id),
    KEY idx_chat_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $conn->query($sql);
}

function ensure_direct_chat_table(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS user_messages (
    id INT NOT NULL AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_messages_sender (sender_id),
    KEY idx_user_messages_receiver (receiver_id),
    KEY idx_user_messages_pair (sender_id, receiver_id),
    KEY idx_user_messages_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $conn->query($sql);
}

function get_user_role(mysqli $conn, int $userId): ?string
{
    $stmt = $conn->prepare("SELECT vai_tro FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row['vai_tro'] ?? null;
}

function user_exists_and_is_not_admin(mysqli $conn, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM users WHERE id = ? AND (vai_tro IS NULL OR vai_tro <> 'admin') LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ok = $result && $result->fetch_row();
    $stmt->close();

    return (bool)$ok;
}

function has_direct_conversation(mysqli $conn, int $currentUserId, int $otherUserId): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM user_messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ok = $result && $result->fetch_row();
    $stmt->close();

    return (bool)$ok;
}

function follows_user(mysqli $conn, int $currentUserId, int $otherUserId): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM follows
        WHERE follower_id = ? AND following_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $currentUserId, $otherUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ok = $result && $result->fetch_row();
    $stmt->close();

    return (bool)$ok;
}

function can_access_direct_chat(mysqli $conn, int $currentUserId, int $otherUserId): bool
{
    if ($otherUserId <= 0 || $otherUserId === $currentUserId) {
        return false;
    }

    if (!user_exists_and_is_not_admin($conn, $otherUserId)) {
        return false;
    }

    if (follows_user($conn, $currentUserId, $otherUserId)) {
        return true;
    }

    return has_direct_conversation($conn, $currentUserId, $otherUserId);
}

function get_admin_contact(mysqli $conn, int $currentUserId): array
{
    $lastMessage = '';
    $lastTime = '';
    $lastTs = 0;

    $stmt = $conn->prepare("
        SELECT message, created_at
        FROM chat_messages
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            $lastMessage = htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8');
            $lastTime = format_chat_time($row['created_at'] ?? '');
            $lastTs = strtotime($row['created_at'] ?? '') ?: 0;
        }
    }

    return [
        'type' => 'admin',
        'user_id' => 0,
        'name' => 'Admin hỗ trợ',
        'avatar' => null,
        'note' => 'Hỗ trợ vé, thanh toán và cộng đồng',
        'last_message' => $lastMessage,
        'last_time' => $lastTime,
        'last_ts' => $lastTs,
        'pinned' => 1,
    ];
}

function get_direct_contacts(mysqli $conn, int $currentUserId): array
{
    $contacts = [];

    $stmt = $conn->prepare("
        SELECT u.id, u.ten, u.avatar
        FROM follows f
        INNER JOIN users u ON u.id = f.following_id
        WHERE f.follower_id = ?
          AND (u.vai_tro IS NULL OR u.vai_tro <> 'admin')
        ORDER BY u.ten ASC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $contacts[(int)$row['id']] = [
                'type' => 'user',
                'user_id' => (int)$row['id'],
                'name' => $row['ten'],
                'avatar' => $row['avatar'],
                'note' => 'Bạn đang theo dõi',
                'last_message' => '',
                'last_time' => '',
                'last_ts' => 0,
                'pinned' => 0,
            ];
        }

        $stmt->close();
    }

    $stmt = $conn->prepare("
        SELECT
            CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_id,
            u.ten,
            u.avatar,
            m.message,
            m.created_at
        FROM user_messages m
        INNER JOIN (
            SELECT MAX(id) AS last_id
            FROM user_messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
        ) latest ON latest.last_id = m.id
        INNER JOIN users u
            ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
        WHERE (u.vai_tro IS NULL OR u.vai_tro <> 'admin')
        ORDER BY m.created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param("iiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $otherId = (int)$row['other_id'];
            if (!isset($contacts[$otherId])) {
                $contacts[$otherId] = [
                    'type' => 'user',
                    'user_id' => $otherId,
                    'name' => $row['ten'],
                    'avatar' => $row['avatar'],
                    'note' => 'Đã từng trò chuyện',
                    'last_message' => '',
                    'last_time' => '',
                    'last_ts' => 0,
                    'pinned' => 0,
                ];
            }

            $contacts[$otherId]['last_message'] = htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8');
            $contacts[$otherId]['last_time'] = format_chat_time($row['created_at'] ?? '');
            $contacts[$otherId]['last_ts'] = strtotime($row['created_at'] ?? '') ?: 0;
        }

        $stmt->close();
    }

    $contacts = array_values($contacts);
    usort($contacts, static function (array $a, array $b): int {
        if (($a['last_ts'] ?? 0) !== ($b['last_ts'] ?? 0)) {
            return ($b['last_ts'] ?? 0) <=> ($a['last_ts'] ?? 0);
        }

        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });

    return $contacts;
}

if (!isset($_SESSION['user_id'])) {
    respond(['success' => false, 'error' => 'Chưa đăng nhập']);
}

$action = $_GET['action'] ?? '';
$currentUserId = (int)$_SESSION['user_id'];
$isAdmin = (($_SESSION['vai_tro'] ?? '') === 'admin');

ensure_admin_chat_table($conn);
ensure_direct_chat_table($conn);

if ($action === 'send') {
    $scope = $_POST['scope'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        respond(['success' => false, 'error' => 'Tin nhắn trống']);
    }

    if ($scope === 'direct') {
        $otherUserId = (int)($_POST['user_id'] ?? 0);

        if (!can_access_direct_chat($conn, $currentUserId, $otherUserId)) {
            respond(['success' => false, 'error' => 'Bạn chỉ có thể nhắn với người đang theo dõi hoặc đã trò chuyện']);
        }

        $stmt = $conn->prepare("
            INSERT INTO user_messages (sender_id, receiver_id, message)
            VALUES (?, ?, ?)
        ");

        if (!$stmt) {
            respond(['success' => false, 'error' => 'Không thể lưu tin nhắn']);
        }

        $stmt->bind_param("iis", $currentUserId, $otherUserId, $message);
        $ok = $stmt->execute();
        $stmt->close();

        respond(['success' => $ok]);
    }

    $chatUserId = $isAdmin ? (int)($_POST['user_id'] ?? 0) : $currentUserId;
    $senderIsAdmin = $isAdmin ? 1 : 0;

    if ($chatUserId <= 0) {
        respond(['success' => false, 'error' => 'Cuộc trò chuyện không hợp lệ']);
    }

    $stmt = $conn->prepare("
        INSERT INTO chat_messages (user_id, is_admin, message)
        VALUES (?, ?, ?)
    ");

    if (!$stmt) {
        respond(['success' => false, 'error' => 'Không thể lưu tin nhắn']);
    }

    $stmt->bind_param("iis", $chatUserId, $senderIsAdmin, $message);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $senderIsAdmin === 0 && table_exists($conn, 'notifications')) {
        $v_res = $conn->query("SELECT id FROM ve WHERE user_id = $chatUserId LIMIT 1");
        $has_booked = ($v_res && $v_res->num_rows > 0);
        
        $u_res = $conn->query("SELECT ten FROM users WHERE id = $chatUserId LIMIT 1");
        $u_name = ($u_res && $u_row = $u_res->fetch_assoc()) ? $u_row['ten'] : "User #$chatUserId";
        
        $note = $has_booked ? " (đã từng đặt vé)" : "";
        $title = "Tin nhắn mới từ $u_name";
        $body = "Thành viên này$note vừa nhắn tin cho bạn ở khung chat hỗ trợ.";
        $link = "../admin/quan_ly_chat.php?user_id=" . $chatUserId;
        
        $admin_q = $conn->query("SELECT id FROM users WHERE vai_tro='admin'");
        if ($admin_q) {
            $a_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, target_id, title, body, link) VALUES (?, 'new_chat', ?, ?, ?, ?)");
            if ($a_stmt) {
                while ($adm = $admin_q->fetch_assoc()) {
                    $aid = (int)$adm['id'];
                    $a_stmt->bind_param("iisss", $aid, $chatUserId, $title, $body, $link);
                    $a_stmt->execute();
                }
                $a_stmt->close();
            }
        }
    }

    respond(['success' => $ok]);
}

if ($action === 'get_messages') {
    $scope = $_GET['scope'] ?? '';

    if ($scope === 'direct') {
        $otherUserId = (int)($_GET['user_id'] ?? 0);

        if (!can_access_direct_chat($conn, $currentUserId, $otherUserId)) {
            respond(['success' => false, 'error' => 'Không thể mở cuộc trò chuyện này']);
        }

        $stmt = $conn->prepare("
            SELECT id, sender_id, receiver_id, message, created_at
            FROM user_messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY id ASC
            LIMIT 200
        ");

        if (!$stmt) {
            respond(['success' => false, 'error' => 'Không thể tải tin nhắn']);
        }

        $stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];

        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'id' => (int)$row['id'],
                'message' => htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'),
                'created_at' => format_chat_time($row['created_at']),
                'is_mine' => ((int)$row['sender_id'] === $currentUserId),
                'is_admin' => 0,
            ];
        }

        $stmt->close();
        respond(['success' => true, 'data' => $messages]);
    }

    $chatUserId = $isAdmin ? (int)($_GET['user_id'] ?? 0) : $currentUserId;
    if ($chatUserId <= 0) {
        respond(['success' => true, 'data' => []]);
    }

    $stmt = $conn->prepare("
        SELECT id, is_admin, message, created_at
        FROM chat_messages
        WHERE user_id = ?
        ORDER BY id ASC
        LIMIT 200
    ");

    if (!$stmt) {
        respond(['success' => false, 'error' => 'Không thể tải tin nhắn']);
    }

    $stmt->bind_param("i", $chatUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $isAdminMessage = ((int)$row['is_admin'] === 1);
        $messages[] = [
            'id' => (int)$row['id'],
            'is_admin' => $isAdminMessage ? 1 : 0,
            'is_mine' => $isAdmin ? $isAdminMessage : !$isAdminMessage,
            'message' => htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'),
            'created_at' => format_chat_time($row['created_at']),
        ];
    }

    $stmt->close();
    respond(['success' => true, 'data' => $messages]);
}

if ($action === 'get_chat_contacts') {
    if ($isAdmin) {
        respond(['success' => false, 'error' => 'Admin không dùng hộp thư cộng đồng này']);
    }

    $contacts = [];
    $contacts[] = get_admin_contact($conn, $currentUserId);
    foreach (get_direct_contacts($conn, $currentUserId) as $contact) {
        $contacts[] = $contact;
    }

    respond(['success' => true, 'data' => $contacts]);
}

if ($action === 'get_conversations') {
    if (!$isAdmin) {
        respond(['success' => false, 'error' => 'Không có quyền']);
    }

    $query = "
        SELECT
            u.id,
            u.ten,
            u.email,
            (SELECT message FROM chat_messages WHERE user_id = u.id ORDER BY id DESC LIMIT 1) AS last_msg,
            (SELECT created_at FROM chat_messages WHERE user_id = u.id ORDER BY id DESC LIMIT 1) AS last_time
        FROM users u
        WHERE u.id IN (SELECT DISTINCT user_id FROM chat_messages)
        ORDER BY last_time DESC
    ";

    $result = $conn->query($query);
    $conversations = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['last_msg'] = htmlspecialchars($row['last_msg'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['last_time'] = $row['last_time'] ? date('d/m H:i', strtotime($row['last_time'])) : '';
            $conversations[] = $row;
        }
    }

    respond(['success' => true, 'data' => $conversations]);
}

respond(['success' => false, 'error' => 'Action không hợp lệ']);
