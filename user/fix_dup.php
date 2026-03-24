<?php
$file = 'dat_ve.php';
$lines = file($file);
$out = [];
foreach ($lines as $i => $line) {
    if ($i >= 671 && $i <= 771) {
        continue;
    }
    $out[] = $line;
}
file_put_contents($file, implode('', $out));
echo 'Done';
