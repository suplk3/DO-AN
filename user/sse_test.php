<?php
set_time_limit(0); // FIX: Cho phép script chạy vô thời hạn

header('Content-Type: text/event-stream');
header('Cache-control: no-cache');

while (true) {
    // Every second, send a "ping" event.
    $time = date('r');
    echo "data: The server time is: {$time}\n\n";
    ob_flush();
    flush();
    sleep(1);
}
?>
