<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>
