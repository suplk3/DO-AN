<?php
include "../config/db.php";

if (isset($_GET['rap_id'])) {
    $rap_id = $_GET['rap_id'];
    $phong_chieu = mysqli_query($conn, "SELECT * FROM phong_chieu WHERE rap_id = $rap_id");
    while ($pc = mysqli_fetch_assoc($phong_chieu)) {
        echo '<option value="' . $pc['id'] . '">' . htmlspecialchars($pc['ten_phong']) . '</option>';
    }
}
?>