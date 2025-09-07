<?php
// check_upload_status.php
session_start();
require 'config.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'error' => 'Invalid request'];

$token = $_GET['token'] ?? '';
$school_id = $_SESSION['school_id'] ?? 0;

if ($school_id === 0) {
    $response['error'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare(
            "SELECT status, temp_filepath, created_at FROM receipt_uploads WHERE token = ? AND school_id = ?"
        );
        $stmt->execute([$token, $school_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            // Check for expiration (e.g., 5 minutes)
            if (strtotime($record['created_at']) < (time() - 300) && $record['status'] === 'pending') {
                $response['status'] = 'expired';
            } else {
                $response['status'] = $record['status'];
                if ($record['status'] === 'completed') {
                    $response['filePath'] = $record['temp_filepath'];
                }
            }
        } else {
            $response['status'] = 'not_found';
        }
    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);