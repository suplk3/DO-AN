<?php

if (session_status() == PHP_SESSION_NONE) session_start();

include "check_admin.php";

include "../config/db.php";



$total_users = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'] ?? 0);

$total_movies = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM phim"))['c'] ?? 0);

$total_shows = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM suat_chieu"))['c'] ?? 0);

$total_tickets = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM ve"))['c'] ?? 0);



$rev_row = mysqli_fetch_assoc(mysqli_query($conn,

    "SELECT SUM(sc.gia) AS revenue

     FROM ve v

     JOIN suat_chieu sc ON v.suat_chieu_id = sc.id"

));

$total_revenue = (int)($rev_row['revenue'] ?? 0);



$latest = mysqli_query($conn,

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

  <style>

    body.admin-dark {

      min-height: 100vh;

      margin: 0;

      color: #e2e8f0;

      font-family: "Trebuchet MS", "Segoe UI", sans-serif;

      background:

        radial-gradient(circle at 8% 12%, rgba(239, 68, 68, 0.18), transparent 34%),

        radial-gradient(circle at 88% 0%, rgba(59, 130, 246, 0.2), transparent 36%),

        linear-gradient(160deg, #050816 0%, #0a1024 42%, #081226 100%);

    }

    .wrap { max-width: 1200px; margin: 26px auto; padding: 0 16px 40px; }

    .title { font-size: 32px; font-weight: 800; margin-bottom: 16px; color:#fff; }

    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 12px; }

    .card {

      background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 58, 95, 0.35));

      border: 1px solid rgba(59, 130, 246, 0.3);

      border-radius: 14px;

      padding: 14px 16px;

      box-shadow: 0 10px 24px rgba(2, 6, 23, 0.35);

    }

    .card .num { font-size: 30px; font-weight: 800; color: #60a5fa; }

    .card .label { font-size: 12px; color: rgba(226,232,240,0.7); text-transform: uppercase; letter-spacing: .5px; }

    .section { margin-top: 20px; }

    .table { width: 100%; border-collapse: collapse; font-size: 14px; }

    .table th, .table td { padding: 10px; border-bottom: 1px solid rgba(148,163,184,0.2); }

    .table th { text-align: left; color:#cbd5f5; font-size: 12px; text-transform: uppercase; letter-spacing: .6px; }

    .toolbar { display:flex; gap:10px; margin-bottom: 10px; }

    .btn { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; border:1px solid rgba(59,130,246,0.4); color:#93c5fd; background: rgba(59,130,246,0.1); }

  </style>

</head>

<body class="admin-dark">

  <div class="wrap">

    <div class="toolbar">

      <a class="btn" href="phim.php">🎬 Quản lý phim</a>

      <a class="btn" href="suat_chieu.php">🗓️ Quản lý suất chiếu</a>

      <a class="btn" href="quan_ly_user.php">👥 Quản lý user</a>

      <a class="btn" href="quan_ly_combo.php">🍿 Quản lý Combo</a>

      <a class="btn" href="quan_ly_voucher.php">🎟️ Quản lý Voucher</a>
      <a class="btn" href="quan_ly_the.php">💎 Thẻ &amp; Điểm</a>
      <a class="btn" href="../user/index.php">🏠 Trang người dùng</a>

    </div>

    <div class="title">📊 Admin Dashboard</div>



    <div class="grid">

      <div class="card"><div class="label">Người dùng</div><div class="num"><?= $total_users ?></div></div>

      <div class="card"><div class="label">Phim</div><div class="num"><?= $total_movies ?></div></div>

      <div class="card"><div class="label">Suất chiếu</div><div class="num"><?= $total_shows ?></div></div>

      <div class="card"><div class="label">Vé đã bán</div><div class="num"><?= $total_tickets ?></div></div>

      <div class="card"><div class="label">Doanh thu (ước tính)</div><div class="num"><?= number_format($total_revenue, 0, ',', '.') ?>₫</div></div>

    </div>



    <div class="section">

      <h3>Vé mới nhất</h3>

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

</body>

</html>


