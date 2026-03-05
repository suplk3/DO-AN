<?php
session_start();
include __DIR__ . '/../config/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dangky'])) {
    $ten = trim($_POST['ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';

    if ($ten === '' || $email === '' || $mat_khau === '') {
        $response['message'] = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Email không hợp lệ.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->fetch_assoc()) {
            $response['message'] = 'Email đã được sử dụng.';
        } else {
            $hash = password_hash($mat_khau, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (ten,email,mat_khau) VALUES (?,?,?)");
            $ins->bind_param('sss', $ten, $email, $hash);
            if ($ins->execute()) {
                $newId = $ins->insert_id;
                $response['success'] = true;
                $response['message'] = 'Đăng ký thành công.';
                // Auto-login
                $_SESSION['user_id'] = $newId;
                $_SESSION['ten'] = $ten;
                $_SESSION['vai_tro'] = 'user';
            } else {
                $response['message'] = 'Lỗi hệ thống, vui lòng thử lại.';
            }
        }
    }

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($isAjax) {
        if ($response['success']) {
            $parentDir = dirname(dirname($_SERVER['SCRIPT_NAME']));
            $response['redirect_url'] = $parentDir . '/user/index.php';
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

    // Non-AJAX: show message and stop (no redirect)
    if ($response['success']) {
        echo '<!doctype html><meta charset="utf-8"><title>Đăng ký thành công</title>';
        echo '<div style="max-width:600px;margin:40px auto;font-family:Arial,Helvetica,sans-serif">';
        echo '<h2>Đăng ký thành công</h2>';
        echo '<p>Bạn đã được đăng nhập tự động. <a href="../user/index.php">Tiếp tục tới trang chính</a></p>';
        echo '</div>';
        exit;
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Lỗi</title>';
        echo '<div style="max-width:600px;margin:40px auto;font-family:Arial,Helvetica,sans-serif">';
        echo '<h2>Lỗi</h2>';
        echo '<p style="color:red">' . htmlspecialchars($response['message']) . '</p>';
        echo '<p><a href="register.php">Quay lại</a></p>';
        echo '</div>';
        exit;
    }
}

// Modal fragment support
if (isset($_GET['modal']) && $_GET['modal'] == '1') {
    ?>
    <?php
    $authPath = dirname($_SERVER['SCRIPT_NAME']);
    ?>
    <div class="register-fragment">
        <h2>ĐĂNG KÝ</h2>
        <form method="POST" class="register-form" action="<?= $authPath ?>/register.php">
            <div class="input-group"><input name="ten" placeholder="Tên" required></div>
            <div class="input-group"><input name="email" type="email" placeholder="Email" required></div>
            <div class="input-group"><input name="mat_khau" type="password" placeholder="Mật khẩu" required></div>
            <button name="dangky">Đăng ký</button>
            <p class="register-msg"></p>
        </form>
    </div>
    <?php
    exit;
}

// Standalone register page HTML (non-AJAX fallback form)
?>
<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><title>Đăng ký</title></head>
<body>
<h2>Đăng ký</h2>
<?php $authPath = dirname($_SERVER['SCRIPT_NAME']); ?>
<form method="POST" action="<?= $authPath ?>/register.php">
    <div><label>Tên<br><input name="ten"></label></div>
    <div><label>Email<br><input name="email" type="email"></label></div>
    <div><label>Mật khẩu<br><input name="mat_khau" type="password"></label></div>
    <div><button name="dangky">Đăng ký</button></div>
    <p><a href="<?= $authPath ?>/login.php">Đã có tài khoản? Đăng nhập</a></p>
</form>
</body>
</html>
