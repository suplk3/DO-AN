<?php
include 'config/db.php';

echo "<h2>🔍 Debug - Kiểm tra suất chiếu & ghế</h2>";

// Kiểm tra suất chiếu
echo "<h3>Tất cả suất chiếu:</h3>";
$sql = "SELECT sc.id, sc.phim_id, sc.phong_id, sc.ngay, sc.gio, p.ten_phim, pc.ten_phong FROM suat_chieu sc LEFT JOIN phim p ON sc.phim_id = p.id LEFT JOIN phong_chieu pc ON sc.phong_id = pc.id";
$r = mysqli_query($conn, $sql);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>sc.id</th><th>phim_id</th><th>phong_id</th><th>ngay</th><th>gio</th><th>ten_phim</th><th>ten_phong</th></tr>";
while ($row = mysqli_fetch_assoc($r)) {
    echo "<tr>";
    foreach ($row as $val) {
        echo "<td>" . ($val ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Kiểm tra phòng
echo "<h3>Tất cả phòng:</h3>";
$sql2 = "SELECT id, rap_id, ten_phong FROM phong_chieu";
$r2 = mysqli_query($conn, $sql2);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>id</th><th>rap_id</th><th>ten_phong</th></tr>";
while ($row = mysqli_fetch_assoc($r2)) {
    echo "<tr><td>" . $row['id'] . "</td><td>" . $row['rap_id'] . "</td><td>" . $row['ten_phong'] . "</td></tr>";
}
echo "</table>";

// Kiểm tra ghế
echo "<h3>Tất cả ghế (10 dòng đầu):</h3>";
$sql3 = "SELECT id, phong_id, ten_ghe FROM ghe LIMIT 10";
$r3 = mysqli_query($conn, $sql3);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>id</th><th>phong_id</th><th>ten_ghe</th></tr>";
while ($row = mysqli_fetch_assoc($r3)) {
    echo "<tr><td>" . $row['id'] . "</td><td>" . $row['phong_id'] . "</td><td>" . $row['ten_ghe'] . "</td></tr>";
}
echo "</table>";

// Kiểm tra ghế theo phòng
echo "<h3>Ghế theo phòng (COUNT):</h3>";
$sql4 = "SELECT phong_id, COUNT(*) as cnt FROM ghe GROUP BY phong_id";
$r4 = mysqli_query($conn, $sql4);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>phong_id</th><th>Số ghế</th></tr>";
while ($row = mysqli_fetch_assoc($r4)) {
    echo "<tr><td>" . $row['phong_id'] . "</td><td>" . $row['cnt'] . "</td></tr>";
}
echo "</table>";
echo "<br><a href='user/index.php'>← Quay lại</a>";
?>
