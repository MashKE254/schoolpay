<?php
// mobile_receipt_upload.php - Improved Version
require 'config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die("Invalid or missing token.");
}

// Verify token exists and is pending
$stmt = $pdo->prepare("SELECT id FROM receipt_uploads WHERE token = ? AND status = 'pending'");
$stmt->execute([$token]);
if (!$stmt->fetch()) {
    die("This link is invalid or has already been used.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Receipt</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f4f4f4; text-align: center; }
        .container { padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 90%;}
        h1 { color: #333; margin-top: 0; }
        p { color: #666; margin-bottom: 25px; }
        #status { margin-top: 20px; font-weight: bold; color: #555; }
        .upload-btn {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        .upload-btn:hover { background-color: #0056b3; }
        .upload-btn i { margin-right: 10px; }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Take Photo</h1>
        <p>Tap the button below to open your camera.</p>
        
        <form id="uploadForm" action="handle_mobile_upload.php" method="post" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="file" name="receipt" accept="image/*" capture="environment" id="fileInput">
        </form>
        
        <button class="upload-btn" id="openCameraButton">
            <i class="fas fa-camera"></i> Open Camera
        </button>
        
        <div id="status"></div>
    </div>
    <script>
        const fileInput = document.getElementById('fileInput');
        const statusDiv = document.getElementById('status');
        const openCameraButton = document.getElementById('openCameraButton');
        
        // When the user taps the button, programmatically click the hidden file input
        openCameraButton.addEventListener('click', function() {
            fileInput.click();
        });

        // When a file is selected (a photo is taken), submit the form
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                statusDiv.textContent = 'Uploading...';
                openCameraButton.style.display = 'none'; // Hide the button after use
                document.getElementById('uploadForm').submit();
            }
        });
    </script>
</body>
</html>