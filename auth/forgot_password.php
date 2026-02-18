<?php
include "../config/db.php";

$response = ['success' => false, 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset'])) {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Vui lòng nhập email hợp lệ.';
    } else {
        // In production: create token + send email. Here we only simulate.
        $response['success'] = true;
        $response['message'] = 'Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi (mô phỏng).';
    }

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }
}

// If requested as modal fragment, return a small fragment
if (isset($_GET['modal']) && $_GET['modal'] == '1') {
    ?>
    <div class="forgot-fragment">
        <h2>QUÊN MẬT KHẨU</h2>
        <form method="POST" action="/testdoan/auth/forgot_password.php" class="forgot-form">
            <input name="email" type="email" placeholder="Email đã đăng ký" required><br>
            <button name="send_reset">Gửi liên kết</button>
        </form>
        <p class="forgot-msg"></p>
    </div>
    <?php
    exit;
}

?>

<h2>QUÊN MẬT KHẨU</h2>
<form method="POST" action="/testdoan/auth/forgot_password.php">
    <input name="email" type="email" placeholder="Email đã đăng ký">
    <button name="send_reset">Gửi liên kết</button>
</form>
<?php if (!empty($response['message'])): ?><p style="color:green"><?php echo htmlspecialchars($response['message']); ?></p><?php endif; ?>
