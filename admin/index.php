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
            header {
                flex-direction: column;
                text-align: center;
            }

            .user-info {
                text-align: center;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>🎬 Admin Dashboard</h1>
            </div>
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
        <h2 class="section-title">📊 QUẢN LÝ DOANH THU & THỐNG KÊ</h2>
        <div class="menu-grid">
            <div class="menu-card">
                <div class="menu-card-header">📈</div>
                <div class="menu-card-title">Dashboard Thống Kê</div>
                <div class="menu-card-content">Xem tổng quan doanh thu tháng, tỷ lệ lấp đầy phòng, top phim doanh thu cao nhất</div>
                <a href="dashboard_advanced.php" class="menu-card-link">Xem Chi Tiết →</a>
            </div>

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
    </script>
</body>
</html>
