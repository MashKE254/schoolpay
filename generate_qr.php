<?php
// generate_qr.php (Updated to use endroid/qr-code)
session_start();
require 'config.php';
require 'vendor/autoload.php';

// Use the new library's classes
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: application/json');
$response = ['success' => false];

$school_id = $_SESSION['school_id'] ?? 0;
if ($school_id === 0) {
    $response['error'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}

try {
    // Generate a secure, unique token
    $token = bin2hex(random_bytes(32));

    // Store the token in the database
    $stmt = $pdo->prepare(
        "INSERT INTO receipt_uploads (school_id, token, status, created_at) VALUES (?, ?, 'pending', NOW())"
    );
    $stmt->execute([$school_id, $token]);

    // Construct the full URL for the mobile upload page
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = '192.168.0.14';
    $path = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
    $full_url = $protocol . $host . $path . '/mobile_receipt_upload.php?token=' . $token;

    // --- NEW QR CODE GENERATION LOGIC ---
    $result = Builder::create()
        ->writer(new PngWriter())
        ->writerOptions([])
        ->data($full_url)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
        ->size(300)
        ->margin(10)
        ->build();

    // Get the raw image data as a string
    $qrCodeString = $result->getString();

    if (empty($qrCodeString)) {
         throw new Exception("QR code generation resulted in empty data.");
    }

    $response['success'] = true;
    $response['token'] = $token;
    // The result is already a PNG string, so just base64 encode it
    $response['qr_code'] = 'data:image/png;base64,' . base64_encode($qrCodeString);

} catch (Exception $e) {
    $response['error'] = 'Failed to generate QR code: ' . $e->getMessage();
    // Log the full error for better debugging
    error_log("QR Generation Error: " . $e->getTraceAsString());
}

echo json_encode($response);