<?php
include "check_admin.php";
include "../config/db.php";

// Lấy thống kê nhanh
$quick_stats_sql = "
SELECT 
    (SELECT COUNT(*) FROM phim) AS total_movies,
    (SELECT COUNT(*) FROM suat_chieu) AS total_showtimes,
    (SELECT COUNT(*) FROM ve) AS total_tickets,
    (SELECT COUNT(DISTINCT user_id) FROM ve) AS total_customers,
    (SELECT SUM(gia) FROM ve JOIN suat_chieu ON ve.suat_chieu_id = suat_chieu.id WHERE DATE(suat_chieu.ngay) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AS monthly_revenue
";
$quick_stats_result = mysqli_query($conn, $quick_stats_sql);
$quick_stats = mysqli_fetch_assoc($quick_stats_result);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Rạp Chiếu Phim</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 25px 35px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h1 {
            font-size: 28px;
            color: #333;
            font-weight: 800;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 999;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(102, 126, 234, 0.25);
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #334155;
            font-weight: 900;
            font-size: 18px;
        }

        .navbar .brand span {
            font-size: 24px;
        }

        @media (max-width: 768px) {
            .navbar .brand span {
                font-size: 24px;
            }
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            transition: max-height 0.25s ease, opacity 0.25s ease;
        }

        .navbar .nav-links.open {
            display: flex;
            max-height: 420px;
            opacity: 1;
        }

        .navbar .nav-links.closed {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .navbar .nav-links a {
            color: #1e293b;
            text-decoration: none;
            background: linear-gradient(120deg, rgba(99,179,237,0.15), rgba(129,148,244,0.2));
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(99,179,237,0.25);
            box-shadow: inset 0 0 0 0 rgba(99,179,237,0.3);
            transition: all 0.25s ease;
        }

        .navbar .nav-links a:hover,
        .navbar .nav-links a.active {
            color: #fff;
            background: linear-gradient(120deg, #4f46e5, #3b82f6);
            border-color: rgba(59,130,246,0.6);
            box-shadow: 0 8px 18px rgba(59,130,246,0.25);
            transform: translateY(-1px);
        }

        .navbar .nav-links a.active {
            font-weight: 800;
        }

        .navbar .nav-links a:hover {
            background: rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }

        .navbar .nav-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #334155;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .user-info .username {
            font-weight: 700;
            color: #333;
            font-size: 15px;
        }

        .logout-btn {
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 800;
            color: #667eea;
            margin: 10px 0;
        }

        .stat-card .label {
            color: #666;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .menu-card-header {
            padding: 30px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 36px;
            text-align: center;
        }

        .menu-card-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .menu-card-content {
            padding: 15px;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .menu-card-link {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .menu-card-link:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6b3fa1 100%);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin: 30px 0 20px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .success-banner {
            background: #ecfdf5;
            border-left: 4px solid #22c55e;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #167d31;
            font-weight: 500;
            display: none;
        }

        .success-banner.show {
            display: block;
        }

        @media (max-width: 768px) {
            body {
                padding: 8px;
                background: radial-gradient(circle at 10% 10%, rgba(99, 102, 241, 0.2), transparent 35%),
                            linear-gradient(180deg, #0f172a 0%, #111827 100%);
            }

            .container {
                max-width: 100%;
                padding: 0;
            }

            .navbar {
                border-radius: 12px;
                padding: 10px 12px;
                position: fixed;
                top: 8px;
                left: 8px;
                right: 8px;
                z-index: 1000;
                background: rgba(15, 23, 42, 0.94);
                border: 1px solid rgba(148, 163, 184, 0.3);
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
            }

            .navbar .brand {
                font-size: 16px;
                color: #e2e8f0;
            }

            .navbar .nav-toggle {
                display: inline-flex;
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(99, 102, 241, 0.35));
                border: 1px solid rgba(99, 102, 241, 0.6);
                border-radius: 12px;
                width: 42px;
                height: 42px;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: #e2e8f0;
                transition: transform 0.2s ease, background 0.3s ease;
            }

            .navbar .nav-toggle:active {
                transform: scale(0.96);
            }

            .navbar .nav-links {
                position: absolute;
                left: 8px;
                right: 8px;
                top: 60px;
                display: none;
                flex-direction: column;
                width: calc(100% - 16px);
                margin: 0 auto;
                border-radius: 14px;
                padding: 10px;
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(148,163,184,0.35);
                box-shadow: 0 20px 35px rgba(0,0,0,0.5);
                animation: slideDown 0.25s ease;
            }

            @keyframes slideDown {
                from { transform: translateY(-8px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }

            .navbar .nav-links.open {
                display: flex;
            }

            .navbar .nav-links a {
                width: 100%;
                text-align: left;
                padding: 12px 14px;
                margin-bottom: 8px;
                border-radius: 12px;
                font-size: 15px;
                color: #e2e8f0;
                background: rgba(15, 23, 42, 0.5);
                border: 1px solid rgba(148,163,184,0.25);
            }

            .navbar .nav-links a:last-child {
                margin-bottom: 0;
            }

            .navbar .nav-links a.active,
            .navbar .nav-links a:hover {
                color: #fff;
                background: linear-gradient(135deg, #4f46e5, #3b82f6);
                border-color: rgba(59,130,246,0.7);
                box-shadow: 0 10px 20px rgba(59,130,246,0.35);
            }

            header {
                flex-direction: column;
                text-align: center;
                margin-top: 76px;
            }

            .user-info {
                text-align: center;
            }

            .user-info p {
                font-size: 13px;
                color: #cbd5e1;
            }

            .logout-btn {
                background: #164e63;
                padding: 8px 14px;
                font-size: 13px;
                border-radius: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
                background: rgba(15, 23, 42, 0.85);
                border: 1px solid rgba(148,163,184,0.25);
                color: #e2e8f0;
            }

            .stat-card .value {
                font-size: 26px;
                color: #f8fafc;
            }

            .stat-card .label {
                color: #94a3b8;
            }

            .menu-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .menu-card {
                background: rgba(15, 23, 42, 0.86);
                border: 1px solid rgba(148,163,184,0.25);
                box-shadow: 0 10px 20px rgba(0,0,0,0.45);
            }

            .menu-card-header {
                padding: 20px;
                font-size: 30px;
            }

            .menu-card-title {
                font-size: 16px;
                color: #e2e8f0;
                border-bottom: 1px solid rgba(148,163,184,0.2);
            }

            .menu-card-content {
                color: #cbd5e1;
            }

            .menu-card-link {
                background: linear-gradient(135deg, #0ea5e9, #6366f1);
            }

            .section-title {
                color: #e2e8f0;
                font-size: 18px;
            }

            .menu-grid, .stats-grid {
                margin-bottom: 18px;
            }

            .toolbar { display: none; }

            .mobile-action {
                position: fixed;
                bottom: 12px;
                left: 12px;
                right: 12px;
                display: flex;
                gap: 8px;
                justify-content: center;
                padding: 8px;
                background: rgba(15,23,42,0.9);
                border: 1px solid rgba(148,163,184,0.3);
                border-radius: 14px;
                z-index: 1000;
                backdrop-filter: blur(10px);
            }

            .mobile-action a {
                flex: 1;
                text-align: center;
                padding: 8px 6px;
                border-radius: 10px;
                color: #e2e8f0;
                text-decoration: none;
                font-size: 12px;
                border: 1px solid rgba(148,163,184,0.3);
                background: rgba(15,23,42,0.75);
            }

            .mobile-action a:hover {
                background: rgba(59,130,246,0.35);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="brand"><span>🎬</span> Admin Dashboard</div>
            <button class="nav-toggle" aria-label="Mở menu">☰</button>
            <div class="nav-links">
                <a href="dashboard.php" class="active">📊 Tổng Quan</a>
                <a href="phim.php">🎥 Phim</a>
                <a href="suat_chieu.php">🗓️ Suất Chiếu</a>
                <a href="quan_ly_user.php">👥 User</a>
                <a href="quan_ly_voucher.php">🎟️ Voucher</a>
            </div>
        </nav>

        <header>
            <div class="user-info">
                <p>Xin chào,</p>
                <p class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                <a href="../auth/logout.php" class="logout-btn">🚪 Đăng Xuất</a>
            </div>
        </header>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">📽️ Phim</div>
                <div class="value"><?php echo $quick_stats['total_movies'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">🎬 Suất Chiếu</div>
                <div class="value"><?php echo $quick_stats['total_showtimes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">🎫 Vé Bán</div>
                <div class="value"><?php echo number_format($quick_stats['total_tickets'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">👥 Khách Hàng</div>
                <div class="value"><?php echo $quick_stats['total_customers'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">💰 Doanh Thu Tháng</div>
                <div class="value"><?php echo number_format(($quick_stats['monthly_revenue'] ?? 0) / 1000000, 1); ?>M</div>
            </div>
        </div>

        <!-- Main Menu -->
        <h2 class="section-title">� QUẢN LÝ TỔNG QUÁT</h2>
        <div class="menu-grid">
            <div class="menu-card">
                <div class="menu-card-header">👤</div>
                <div class="menu-card-title">Quản Lý Người Dùng</div>
                <div class="menu-card-content">Xem danh sách người dùng, phân quyền, quản lý tài khoản</div>
                <a href="quan_ly_user.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>

            <div class="menu-card">
                <div class="menu-card-header">🎬</div>
                <div class="menu-card-title">Quản Lý Phim</div>
                <div class="menu-card-content">Thêm, sửa, xóa phim; cập nhật thông tin phim</div>
                <a href="phim.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>
        </div>

        <h2 class="section-title">🎪 QUẢN LÝ SUẤT CHIẾU & PHÒNG</h2>
        <div class="menu-grid">
            <div class="menu-card">
                <div class="menu-card-header">⏰</div>
                <div class="menu-card-title">Quản Lý Suất Chiếu</div>
                <div class="menu-card-content">Thêm, sửa, xóa suất chiếu; xem danh sách suất chiếu</div>
                <a href="suat_chieu.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>

            <div class="menu-card">
                <div class="menu-card-header">🏛️</div>
                <div class="menu-card-title">Quản Lý Phòng Chiếu</div>
                <div class="menu-card-content">Quản lý phòng, cấu hình ghế, tính năng lấp đầy</div>
                <a href="quan_ly_phong.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>

            <div class="menu-card">
                <div class="menu-card-header">🪑</div>
                <div class="menu-card-title">Cấu Hình Ghế</div>
                <div class="menu-card-content">Thiết lập sơ đồ ghế, quản lý tình trạng ghế</div>
                <a href="cau_hinh_ghe.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>
        </div>

        <h2 class="section-title">⚙️ CÔNG CỤ & CẤU HÌNH</h2>
        <div class="menu-grid">
            <div class="menu-card">
                <div class="menu-card-header">🏠</div>
                <div class="menu-card-title">Về Trang Chính</div>
                <div class="menu-card-content">Quay lại trang chính người dùng</div>
                <a href="../user/index.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>

            <div class="menu-card">
                <div class="menu-card-header">📱</div>
                <div class="menu-card-title">API Thống Kê</div>
                <div class="menu-card-content">Lấy dữ liệu JSON cho tích hợp với hệ thống khác</div>
                <a href="statistics_api.php?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="menu-card-link">Xem Chi Tiết →</a>
            </div>
        </div>
    </div>

    <div class="mobile-action">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="phim.php">🎥 Phim</a>
        <a href="quan_ly_user.php">👥 User</a>
    </div>

    <script>
        // Show success banner if redirected with success message
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success')) {
            const banner = document.querySelector('.success-banner');
            if (banner) {
                banner.classList.add('show');
                setTimeout(() => {
                    banner.classList.remove('show');
                }, 5000);
            }
        }

        // Responsive nav toggle
        const navToggle = document.querySelector('.nav-toggle');
        const navLinksContainer = document.querySelector('.nav-links');

        if (navToggle && navLinksContainer) {
            navToggle.addEventListener('click', () => {
                navLinksContainer.classList.toggle('open');
                navLinksContainer.classList.toggle('closed');
            });
        }

        // Thay đổi icon logo ở mobile thành icon cuộn phim
        const brandIcon = document.querySelector('.navbar .brand span');
        function updateBrandIcon() {
            if (!brandIcon) return;
            if (window.innerWidth <= 768) {
                brandIcon.textContent = '🎞️';
            } else {
                brandIcon.textContent = '🎬';
            }
        }

        updateBrandIcon();
        window.addEventListener('resize', updateBrandIcon);
    </script>
</body>
</html>
