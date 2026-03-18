<?php
$lines = file('assets/css/style.css');
foreach ($lines as $i => $line) {
    if (strpos($line, '<<<<<<<') === 0 || strpos($line, '=======') === 0 || strpos($line, '>>>>>>>') === 0) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
