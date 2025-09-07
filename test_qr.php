<?php
// test_qr.php
// Place this file in the same directory as your other PHP files.
// Then, navigate to it in your browser: http://your-site.com/test_qr.php

// These lines will force any hidden errors to be displayed
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;

// We will try to generate a simple QR code for Google
$data = 'https://www.google.com';

try {
    // If this works, it will output a QR code image directly to the browser
    header('Content-type: image/png');
    echo (new QRCode)->render($data);

} catch (Exception $e) {
    // If it fails, it will create a small black image with the error message written in it
    header('Content-type: image/png');
    $im = imagecreatetruecolor(800, 150);
    $bg = imagecolorallocate($im, 0, 0, 0);
    $text_color = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, 799, 149, $bg);
    imagestring($im, 5, 10, 10, 'A fatal error occurred:', $text_color);
    // Wrap the error message text
    $message = "Error: " . $e->getMessage();
    $lines = explode("\n", wordwrap($message, 100));
    $y = 30;
    foreach ($lines as $line) {
        imagestring($im, 5, 10, $y, $line, $text_color);
        $y += 20;
    }
    imagepng($im);
    imagedestroy($im);
}
?>