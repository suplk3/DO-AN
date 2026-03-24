<?php
$file = 'c:\xampp\htdocs\doan\DO-AN\assets\css\style.css';
$content = file_get_contents($file);
$marker = '/* ══════════════════════════════════════════════════════';
$pos = strpos($content, $marker);

if ($pos !== false) {
    // Extract everything from the marker to the end
    $mobileCss = substr($content, $pos);
    
    // Check if the block is what we expect
    if (strpos($mobileCss, 'GLOBAL MOBILE HEADER') !== false) {
        // Save to mobile-premium.css
        file_put_contents('c:\xampp\htdocs\doan\DO-AN\assets\css\mobile-premium.css', $mobileCss);
        
        // Remove from style.css
        $newContent = substr($content, 0, $pos);
        file_put_contents($file, $newContent);
        echo "Successfully extracted to mobile-premium.css\n";
    } else {
        echo "Marker found but not the expected block.\n";
    }
} else {
    echo "Marker not found.\n";
}
?>
