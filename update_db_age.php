<?php
$conn = mysqli_connect("localhost", "root", "", "cinema");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM `phim` LIKE 'do_tuoi'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE `phim` ADD `do_tuoi` VARCHAR(10) DEFAULT 'P'";
    if (mysqli_query($conn, $sql)) {
        echo "Successfully added 'do_tuoi' column.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Column 'do_tuoi' already exists.";
}

mysqli_close($conn);
?>
