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
            padding: 0;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 32px;
            gap: 20px;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ef4444;
            font-weight: 900;
            font-size: 22px;
        }

        .navbar .brand span {
            font-size: 28px;
        }

        .navbar .nav-center {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }

        .navbar .nav-center a {
            color: #e2e8f0;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            background: transparent;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .navbar .nav-center a:hover {
            background: rgba(99, 102, 241, 0.25);
            border-color: rgba(99, 102, 241, 0.5);
            color: #fff;
        }

        .navbar .search-box {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            padding: 8px 16px;
            color: #94a3b8;
            width: 280px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .navbar .search-box:focus {
            outline: none;
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(99, 102, 241, 0.6);
            color: #e2e8f0;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.25);
        }

        .navbar .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .navbar .notification-icon,
        .navbar .admin-profile {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .navbar .notification-icon {
            position: relative;
            font-size: 20px;
        }

        .navbar .notification-icon:hover,
        .navbar .admin-profile:hover {
            transform: scale(1.1);
        }

        .navbar .admin-profile {
            background: rgba(99, 102, 241, 0.3);
            border: 1px solid rgba(99, 102, 241, 0.5);
            padding: 6px 12px;
            border-radius: 12px;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 14px;
            position: relative;
        }

        .navbar .admin-profile:hover {
            background: rgba(99, 102, 241, 0.5);
            border-color: rgba(99, 102, 241, 0.8);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(15, 23, 42, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 12px;
            margin-top: 8px;
            min-width: 200px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #cbd5e1;
            text-decoration: none;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.2s;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu a:hover {
            background: rgba(99, 102, 241, 0.3);
            color: #fff;
        }

        header {
            margin-top: 70px;
            background: transparent;
            padding: 0;
            border-radius: 0;
            margin-bottom: 30px;
            box-shadow: none;
            border: none;
        }

        @media (max-width: 1200px) {
            .navbar .search-box {
                width: 200px;
            }

            .navbar .nav-center a {
                font-size: 13px;
                padding: 8px 14px;
            }
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
                padding: 0;
            }

            .container {
                max-width: 100%;
                padding: 0 12px 70px;
                margin-top: 64px;
            }

            .navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                border-radius: 0;
                padding: 10px 12px;
                background: rgba(15, 23, 42, 0.95);
                border: none;
                border-bottom: 1px solid rgba(148, 163, 184, 0.2);
                flex-wrap: wrap;
                gap: 0;
            }

            .navbar .brand {
                font-size: 16px;
                color: #ef4444;
                flex: 0 0 auto;
            }

            .navbar .nav-center {
                display: none;
                flex: 1;
                width: 100%;
                flex-direction: column;
                margin-top: 8px;
                gap: 4px;
            }

            .navbar .nav-center.mobile-open {
                display: flex;
            }

            .navbar .nav-center a {
                width: 100%;
                text-align: left;
                padding: 10px 12px;
                font-size: 14px;
            }

            .navbar .search-box {
                display: none;
            }

            .navbar .nav-right {
                flex: 1;
                justify-content: flex-end;
            }

            .navbar .notification-icon {
                font-size: 18px;
            }

            .navbar .admin-profile {
                padding: 6px 10px;
                font-size: 13px;
            }

            .navbar .admin-profile span:last-child {
                display: none;
            }

            .dropdown-menu {
                width: 160px;
            }

            .dropdown-menu a {
                font-size: 12px;
                padding: 10px 12px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-top: 12px;
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
                box-shadow: 0 8px 16px rgba(0,0,0,0.35);
            }

            .menu-card-header {
                padding: 20px;
                font-size: 28px;
            }

            .menu-card-title {
                font-size: 16px;
                color: #e2e8f0;
                border-bottom: 1px solid rgba(148,163,184,0.2);
                padding: 12px 16px;
            }

            .menu-card-content {
                color: #cbd5e1;
                padding: 12px 16px;
                font-size: 13px;
            }

            .menu-card-link {
                background: linear-gradient(135deg, #0ea5e9, #6366f1);
                padding: 10px 12px;
                font-size: 13px;
            }

            .section-title {
                color: #e2e8f0;
                font-size: 18px;
                margin-top: 16px;
                margin-bottom: 12px;
            }

            .mobile-action {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                display: flex;
                gap: 6px;
                justify-content: space-around;
                padding: 10px 12px;
                background: rgba(15,23,42,0.95);
                border-top: 1px solid rgba(148,163,184,0.2);
                z-index: 999;
            }

            .mobile-action a {
                flex: 1;
                text-align: center;
                padding: 8px 6px;
                border-radius: 10px;
                color: #e2e8f0;
                text-decoration: none;
                font-size: 12px;
                border: 1px solid rgba(148,163,184,0.2);
                background: rgba(99, 102, 241, 0.2);
            }

            .mobile-action a:hover {
                background: rgba(99,102,241,0.35);
            }
        }
    </style>
</head>
<body>
    <!-- Admin Navbar Desktop -->
    <nav class="navbar">
        <div class="brand"><span>🎞️</span>TTVH</div>
        <div class="nav-center">
            <a href="phim.php">🎬 PHIM</a>
            <a href="suat_chieu.php">📅 SẮP CHIẾU</a>
            <a href="../user/social.php">👥 CỘNG ĐỒNG</a>
        </div>
        <input type="text" class="search-box" placeholder="Tìm phim, thể loại..." id="globalSearch">
        <div class="nav-right">
            <span class="notification-icon" title="Thông báo">🔔</span>
            <div class="admin-profile" id="adminMenuBtn">
                <span>⚙️ Admin</span>
                <span>▼</span>
                <div class="dropdown-menu" id="adminDropdown">
                    <a href="dashboard.php">📊 Dashboard</a>
                    <a href="phim.php">🎬 Quản lý phim</a>
                    <a href="suat_chieu.php">⏰ Quản lý suất chiếu</a>
                    <a href="quan_ly_user.php">👥 Quản lý user</a>
                    <a href="quan_ly_voucher.php">🎟️ Quản lý voucher</a>
                    <a href="../auth/logout.php">🚪 Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div style="padding: 20px;"></div>

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
        // Admin profile dropdown
        const adminMenuBtn = document.getElementById('adminMenuBtn');
        const adminDropdown = document.getElementById('adminDropdown');

        if (adminMenuBtn && adminDropdown) {
            adminMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                adminDropdown.classList.toggle('show');
            });

            document.addEventListener('click', () => {
                adminDropdown.classList.remove('show');
            });
        }

        // Search functionality
        const searchBox = document.getElementById('globalSearch');
        if (searchBox) {
            searchBox.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const query = searchBox.value.trim();
                    if (query) {
                        // Redirect to search page or execute search
                        console.log('Search for:', query);
                    }
                }
            });
        }

        // Mobile menu toggle button for nav-center
        const brandElement = document.querySelector('.navbar .brand');
        const navCenter = document.querySelector('.navbar .nav-center');

        if (navCenter && window.innerWidth <= 768) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-menu-toggle';
            toggleBtn.innerHTML = '☰';
            toggleBtn.style.cssText = `
                background: none;
                border: none;
                color: #e2e8f0;
                font-size: 22px;
                cursor: pointer;
                padding: 4px 8px;
                display: flex;
                align-items: center;
            `;
            
            brandElement.parentNode.insertBefore(toggleBtn, brandElement.nextSibling);

            toggleBtn.addEventListener('click', () => {
                navCenter.classList.toggle('mobile-open');
            });
        }

        // Close dropdown when clicking on menu items
        const dropdownLinks = document.querySelectorAll('.dropdown-menu a');
        if (adminDropdown) {
            dropdownLinks.forEach(link => {
                link.addEventListener('click', () => {
                    adminDropdown.classList.remove('show');
                });
            });
        }
    </script>
</body>
</html>
