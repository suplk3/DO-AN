<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "check_admin.php";
include "../config/db.php";

function admin_query($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("[dashboard] MySQL error: " . mysqli_error($conn) . " | SQL: " . $sql);
        return false;
    }
    return $result;
}

$total_users = 0;
if ($row = mysqli_fetch_assoc(admin_query($conn, "SELECT COUNT(*) AS c FROM users"))) {
    $total_users = (int)$row['c'];
}

$total_movies = 0;
if ($row = mysqli_fetch_assoc(admin_query($conn, "SELECT COUNT(*) AS c FROM phim"))) {
    $total_movies = (int)$row['c'];
}

$total_shows = 0;
if ($row = mysqli_fetch_assoc(admin_query($conn, "SELECT COUNT(*) AS c FROM suat_chieu"))) {
    $total_shows = (int)$row['c'];
}

$total_tickets = 0;
if ($row = mysqli_fetch_assoc(admin_query($conn, "SELECT COUNT(*) AS c FROM ve"))) {
    $total_tickets = (int)$row['c'];
}

$total_revenue = 0;
if ($row = mysqli_fetch_assoc(admin_query($conn,
    "SELECT SUM(sc.gia) AS revenue
     FROM ve v
     JOIN suat_chieu sc ON v.suat_chieu_id = sc.id"
))) {
    $total_revenue = (int)($row['revenue'] ?? 0);
}

$chart_labels = [];
$chart_data = [];
$chart_query = admin_query($conn, "
    SELECT p.ten_phim, COUNT(v.id) as total_tickets 
    FROM phim p 
    LEFT JOIN suat_chieu sc ON p.id = sc.phim_id 
    LEFT JOIN ve v ON sc.id = v.suat_chieu_id 
    GROUP BY p.id 
    ORDER BY total_tickets DESC 
    LIMIT 10
");
if ($chart_query) {
    while ($row = mysqli_fetch_assoc($chart_query)) {
        $chart_labels[] = $row['ten_phim'];
        $chart_data[] = (int)$row['total_tickets'];
    }
}

$latest = admin_query($conn,
    "SELECT v.id, u.ten, p.ten_phim, sc.ngay, sc.gio
     FROM ve v
     JOIN users u ON v.user_id = u.id
     JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
     JOIN phim p ON sc.phim_id = p.id
     ORDER BY v.id DESC
     LIMIT 6"
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body.admin-dark {
      min-height: 100vh;
      margin: 0;
      color: #e2e8f0;
      font-family: "Trebuchet MS", "Segoe UI", sans-serif;
      background:
        radial-gradient(circle at 10% 20%, rgba(239, 68, 68, 0.15), transparent 40%),
        radial-gradient(circle at 90% 10%, rgba(59, 130, 246, 0.15), transparent 40%),
        radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.1), transparent 50%),
        linear-gradient(135deg, #050816 0%, #0a1024 40%, #081226 100%);
      background-attachment: fixed;
    }
    .wrap { max-width: 1200px; margin: 26px auto; padding: 0 16px 40px; }
    .title { font-size: 34px; font-weight: 800; margin-bottom: 24px; color:#fff; text-shadow: 0 2px 10px rgba(255,255,255,0.1); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; margin-bottom: 24px; }
    .card {
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(59, 130, 246, 0.2);
      border-radius: 16px;
      padding: 18px 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: '';
      position: absolute;
      top: -50%; left: -50%;
      width: 200%; height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
      opacity: 0;
      transition: opacity 0.3s;
    }
    .card:hover {
      transform: translateY(-5px) scale(1.02);
      border-color: rgba(96, 165, 250, 0.6);
      box-shadow: 0 15px 35px rgba(59, 130, 246, 0.2);
    }
    .card:hover::before { opacity: 1; }
    .card .num { font-size: 32px; font-weight: 900; color: #93c5fd; text-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }
    .card .label { font-size: 13px; color: rgba(226,232,240,0.8); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 8px; }
    .section {
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.05);
      padding: 24px;
      margin-top: 24px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }
    .section h3 { margin-top: 0; color: #fff; font-size: 20px; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
    .table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 15px; }
    .table th, .table td { padding: 14px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .table th { text-align: left; color:#94a3b8; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
    .table tbody tr { transition: background 0.2s; }
    .table tbody tr:hover { background: rgba(255,255,255,0.03); }
    .toolbar { display:flex; flex-wrap: wrap; gap:12px; margin-bottom: 24px; }
    .btn {
      padding: 10px 16px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      border: 1px solid rgba(59,130,246,0.3);
      color: #93c5fd;
      background: rgba(59,130,246,0.1);
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn:hover {
      background: rgba(59,130,246,0.25);
      border-color: rgba(59,130,246,0.6);
      transform: translateY(-2px);
      color: #fff;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    .main-grid {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 24px;
    }
    @media (max-width: 900px) {
      .main-grid { grid-template-columns: 1fr; }
    }
    .chart-container {
      position: relative;
      height: 350px;
      width: 100%;
    }
    @media (max-width: 900px) {
      .main-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="admin-dark">
  <div class="wrap">
  <?php 
  $active_page = 'dashboard';
  include "../user/components/header.php"; 
  ?>

    <div class="title">✨ Admin Dashboard</div>

    <div class="grid">
      <div class="card"><div class="label">Người dùng</div><div class="num"><?= $total_users ?></div></div>
      <div class="card"><div class="label">Phim</div><div class="num"><?= $total_movies ?></div></div>
      <div class="card"><div class="label">Suất chiếu</div><div class="num"><?= $total_shows ?></div></div>
      <div class="card"><div class="label">Vé đã bán</div><div class="num"><?= $total_tickets ?></div></div>
      <div class="card"><div class="label">Doanh thu</div><div class="num" style="color:#34d399;"><?= number_format($total_revenue, 0, ',', '.') ?>₫</div></div>
    </div>

    <div class="main-grid">
      <div class="section" style="margin-top:0;">
        <h3>📊 Thống kê lượng vé đặt theo phim</h3>
        <div class="chart-container">
          <canvas id="ticketChart"></canvas>
        </div>
      </div>

      <div class="section" style="margin-top:0;">
        <h3>🎟️ Vé mới nhất</h3>
        <div style="overflow-x:auto;">
          <table class="table">
            <thead>
              <tr>
                <th>#ID</th>
                <th>Khách</th>
                <th>Phim</th>
                <th>Ngày</th>
                <th>Giờ</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($latest && mysqli_num_rows($latest) > 0): ?>
              <?php while ($r = mysqli_fetch_assoc($latest)): ?>
                <tr>
                  <td>#<?= (int)$r['id'] ?></td>
                  <td><?= htmlspecialchars($r['ten']) ?></td>
                  <td><?= htmlspecialchars($r['ten_phim']) ?></td>
                  <td><?= date('d/m/Y', strtotime($r['ngay'])) ?></td>
                  <td><?= date('H:i', strtotime($r['gio'])) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5">Chưa có vé.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    const ctx = document.getElementById('ticketChart').getContext('2d');
    const chartLabels = <?= json_encode($chart_labels) ?>;
    const chartData = <?= json_encode($chart_data) ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Số lượng vé',
                data: chartData,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(14, 165, 233, 0.8)',
                    'rgba(244, 63, 94, 0.8)',
                    'rgba(168, 85, 247, 0.8)'
                ],
                borderColor: 'rgba(15, 23, 42, 1)',
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#e2e8f0', font: { family: "'Segoe UI', sans-serif", size: 12 }, padding: 15 }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { size: 14, family: "'Segoe UI', sans-serif" },
                    bodyFont: { size: 13, family: "'Segoe UI', sans-serif" },
                    padding: 12,
                    borderColor: 'rgba(59, 130, 246, 0.3)',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            layout: { padding: 10 }
        }
    });
  </script>
</body>
</html>
