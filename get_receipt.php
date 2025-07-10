<?php
require_once 'config.php';
require_once 'functions.php';
session_start();

header('Content-Type: application/json');

// Security check: Ensure user is logged in and it's a valid request
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$school_id = $_SESSION['school_id'];
$receipt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$response = ['success' => false];

if ($receipt_id) {
    try {
        // --- FIX: Use the updated function that joins school details ---
        $receipt = getReceiptDetails($pdo, $receipt_id, $school_id);
        
        if ($receipt) {
            $response = [
                'success' => true,
                'receipt' => $receipt
            ];
        } else {
            $response['error'] = 'Receipt not found or you do not have permission to view it.';
        }
    } catch (PDOException $e) {
        error_log("Get Receipt Error: " . $e->getMessage());
        $response['error'] = 'Database error.';
    }
} else {
    $response['error'] = 'Invalid receipt ID';
}

echo json_encode($response);
?>