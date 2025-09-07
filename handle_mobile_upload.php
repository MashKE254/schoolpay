<?php
// handle_mobile_upload.php
require 'config.php';

$token = $_POST['token'] ?? '';

if (empty($token) || !isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    die("Upload failed. Please try again.");
}

// Verify token and status again for security
$stmt = $pdo->prepare("SELECT id, school_id FROM receipt_uploads WHERE token = ? AND status = 'pending'");
$stmt->execute([$token]);
$upload_record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$upload_record) {
    die("Invalid, expired, or already used link.");
}

$upload_dir = 'uploads/temp/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

$file_ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
$file_name = 'receipt_' . $token . '.' . $file_ext;
$target_file = $upload_dir . $file_name;

if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
    // Update the database record with the file path and 'completed' status
    $stmt = $pdo->prepare("UPDATE receipt_uploads SET temp_filepath = ?, status = 'completed' WHERE token = ?");
    $stmt->execute([$target_file, $token]);
    echo "<h1>Success!</h1><p>Your receipt has been sent to your laptop. You can now close this window.</p>";
} else {
    echo "<h1>Error</h1><p>There was a problem saving your receipt.</p>";
}