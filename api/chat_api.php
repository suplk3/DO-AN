<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$action = $_GET['action'] ?? '';
$is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin') ? 1 : 0;
$current_user_id = $_SESSION['user_id'];

// 1. Gửi tin nhắn
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    
    // Nếu là admin gửi, phải truyền user_id của cuộc hội thoại lên
    // Nếu là user bình thường gửi, user_id chính là id của user
    if ($is_admin) {
        $chat_user_id = (int)($_POST['user_id'] ?? 0);
    } else {
        $chat_user_id = $current_user_id;
    }

    if (empty($message) || $chat_user_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, is_admin, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $chat_user_id, $is_admin, $message);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Lỗi DB']);
    }
    exit;
}

// 2. Lấy tin nhắn của 1 User
if ($action === 'get_messages') {
    if ($is_admin) {
        $chat_user_id = (int)($_GET['user_id'] ?? 0);
    } else {
        $chat_user_id = $current_user_id;
    }

    if ($chat_user_id <= 0) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // Lấy tin nhắn (lấy tối đa 100 tin nhắn gần nhất)
    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY id ASC LIMIT 100");
    $stmt->bind_param("i", $chat_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'is_admin' => $row['is_admin'],
            'message' => htmlspecialchars($row['message']),
            'created_at' => date('H:i d/m', strtotime($row['created_at']))
        ];
    }
    echo json_encode(['success' => true, 'data' => $messages]);
    exit;
}

// 3. Lấy danh sách cuộc hội thoại (Chỉ dành cho Admin)
if ($action === 'get_conversations') {
    if (!$is_admin) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền']);
        exit;
    }

    $query = "
        SELECT u.id, u.ten, u.email, 
               (SELECT message FROM chat_messages WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as last_msg,
               (SELECT created_at FROM chat_messages WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as last_time
        FROM users u 
        WHERE u.id IN (SELECT DISTINCT user_id FROM chat_messages)
        ORDER BY last_time DESC
    ";
    
    $result = $conn->query($query);
    $conversations = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['last_msg'] = htmlspecialchars($row['last_msg'] ?? '');
            $row['last_time'] = $row['last_time'] ? date('d/m H:i', strtotime($row['last_time'])) : '';
            $conversations[] = $row;
        }
    }
    echo json_encode(['success' => true, 'data' => $conversations]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
