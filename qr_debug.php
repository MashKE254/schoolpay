<?php
// qr_debug.php - A diagnostic script for QR code generation issues.

// Start a session to check for authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic HTML styling for readability
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Code Generation Debugger</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; padding: 20px; color: #333; }
        h1, h3 { color: #1a202c; }
        .check { margin-bottom: 12px; padding: 12px; border-left: 5px solid; border-radius: 4px; }
        .pass { border-color: #28a745; background-color: #e9f7ef; }
        .fail { border-color: #dc3545; background-color: #fce8e6; }
        strong { font-weight: bold; color: #1a202c; }
        code { background-color: #e2e8f0; padding: 2px 5px; border-radius: 4px; font-family: "Courier New", Courier, monospace; }
        pre { background-color: #e2e8f0; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; }
        textarea { width: 100%; box-sizing: border-box; height: 120px; font-size: 0.9rem; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>QR Code Generation Debugger 🛠️</h1>
    <p>This script checks your server setup to ensure QR codes can be generated. Please copy the entire output and send it back for analysis.</p>
    <hr style="margin: 20px 0;">
HTML;

// --- CHECK 1: PHP Session ---
if (isset($_SESSION['school_id']) && !empty($_SESSION['school_id'])) {
    echo "<div class='check pass'><strong>PASS:</strong> PHP Session is active and <code>school_id</code> is set. (Value: " . htmlspecialchars($_SESSION['school_id']) . ")</div>";
} else {
    echo "<div class='check fail'><strong>FAIL:</strong> PHP Session is not active or <code>school_id</code> is not set. <strong>Action:</strong> Please log in to your application in another tab and then refresh this page.</div>";
    echo "</body></html>";
    exit;
}

// --- CHECK 2: GD Extension ---
if (extension_loaded('gd') && function_exists('imagepng')) {
    echo "<div class='check pass'><strong>PASS:</strong> The PHP GD extension is loaded and the <code>imagepng()</code> function is available.</div>";
} else {
    echo "<div class='check fail'><strong>FAIL:</strong> The PHP GD extension is NOT enabled. This is the most likely cause of your problem. <strong>Action:</strong> You must edit your <code>php.ini</code> file, uncomment the line <code>extension=gd</code> by removing the semicolon (<code>;</code>) from the beginning, and <strong>RESTART your Apache server</strong>.</div>";
}

// --- CHECK 3: Composer Autoloader ---
$autoloader_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader_path)) {
    echo "<div class='check pass'><strong>PASS:</strong> Composer autoloader found at <code>" . htmlspecialchars($autoloader_path) . "</code>.</div>";
    require $autoloader_path;
} else {
    echo "<div class='check fail'><strong>FAIL:</strong> Composer autoloader NOT found at <code>" . htmlspecialchars($autoloader_path) . "</code>. <strong>Action:</strong> Run <code>composer install</code> in your project directory from the command line.</div>";
    echo "</body></html>";
    exit;
}

// --- CHECK 4: QR Code Library Class ---
if (class_exists('chillerlan\\QRCode\\QRCode')) {
    echo "<div class='check pass'><strong>PASS:</strong> The QRCode library class (<code>chillerlan\\QRCode\\QRCode</code>) was loaded successfully by Composer.</div>";
} else {
    echo "<div class='check fail'><strong>FAIL:</strong> The QRCode library class could not be found. This suggests an issue with your Composer installation or the autoloader file.</div>";
    echo "</body></html>";
    exit;
}

$qrcode_image_data = null;
// --- CHECK 5: Attempt to generate QR Code ---
try {
    $test_url = 'https://www.google.com';
    $options = new \chillerlan\QRCode\QROptions([
        'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => \chillerlan\QRCode\QRCode::ECC_L,
    ]);
    $qrcode_image_data = (new \chillerlan\QRCode\QRCode($options))->render($test_url);

    if ($qrcode_image_data) {
        echo "<div class='check pass'><strong>PASS:</strong> Successfully generated a QR code in memory. The library and its dependencies are working correctly.</div>";
        echo "<h3>Generated QR Code Test:</h3>";
        echo '<p>If you see a QR code below, your server environment is correctly set up.</p>';
        echo '<img src="data:image/png;base64,' . base64_encode($qrcode_image_data) . '" alt="Test QR Code">';
    } else {
        echo "<div class='check fail'><strong>FAIL:</strong> The QRCode library ran but did not produce image data. This is an unusual error. Please check your server's error logs for more details.</div>";
    }
} catch (Exception $e) {
    echo "<div class='check fail'><strong>FAIL:</strong> An exception occurred while trying to generate the QR code. Error message: <pre>" . htmlspecialchars($e->getMessage()) . "</pre></div>";
}

// --- CHECK 6: Raw Data URI Output ---
if (isset($qrcode_image_data) && $qrcode_image_data) {
    $data_uri = 'data:image/png;base64,' . base64_encode($qrcode_image_data);
    echo '<hr style="margin: 20px 0;">';
    echo "<h3>Raw Data URI Test:</h3>";
    echo "<p>Please copy the entire text from the box below and paste it into an online 'Base64 to Image' converter to see if it generates a valid QR code.</p>";
    echo '<textarea readonly>' . htmlspecialchars($data_uri) . '</textarea>';
}

echo "</body></html>";
?>