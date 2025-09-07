<?php
// vehicle_expense.php - Corrected Version
session_start();
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];
$school_id = $_SESSION['school_id'] ?? null;

if (!$school_id) {
    $response['error'] = 'Authentication session has expired.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate and sanitize inputs
        $expense_date = $_POST['expense_date'] ?? null;
        $payment_account_id = (int)($_POST['payment_account_id'] ?? 0);
        $vehicle_id = trim($_POST['vehicle_id'] ?? '');
        $expense_type = trim($_POST['expense_type'] ?? '');
        $account_id = (int)($_POST['account_id'] ?? 0); // This is the expense account to debit
        $amount = (float)($_POST['amount'] ?? 0);
        $odometer = $_POST['odometer'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $receipt_image_path = null;

        if (empty($expense_date) || empty($vehicle_id) || empty($expense_type) || empty($account_id) || empty($payment_account_id) || $amount <= 0) {
            throw new Exception('Valid data for all required fields is needed.');
        }

        // --- NEW UPLOAD LOGIC ---
        // Check for QR code upload first
        if (!empty($_POST['uploaded_receipt_path'])) {
            $temp_path = $_POST['uploaded_receipt_path'];
            // Security check: ensure the path is within the temp directory
            if (file_exists($temp_path) && strpos(realpath($temp_path), realpath('uploads/temp/')) === 0) {
                $upload_dir = 'uploads/receipts/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                
                $new_filename = 'receipt_' . uniqid('', true) . '.' . pathinfo($temp_path, PATHINFO_EXTENSION);
                $new_path = $upload_dir . $new_filename;

                if (rename($temp_path, $new_path)) {
                    $receipt_image_path = $new_path;
                }
            }
        } 
        // Fallback to direct file upload
        else if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/receipts/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            
            $file_ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('receipt_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target_file)) {
                $receipt_image_path = $target_file;
            }
        }
        
        $formatted_description = "Vehicle expense: " . ucfirst($expense_type) . " for vehicle " . $vehicle_id;
        if ($odometer) $formatted_description .= " (Odometer: " . $odometer . ")";
        if (!empty($description)) $formatted_description .= " - " . $description;

        // Get the latest ID before creating new entries
        $stmt_last_id = $pdo->query("SELECT MAX(id) FROM expenses");
        $last_id = $stmt_last_id->fetchColumn() ?: 0;
        
        // Use the centralized journal entry function
        create_journal_entry(
            $pdo,
            $school_id,
            $expense_date,
            $formatted_description,
            $amount,
            $account_id, // Debit Expense
            $payment_account_id // Credit Asset
        );

        // If a receipt was saved, update the new expense record
        if ($receipt_image_path) {
            $stmt_update = $pdo->prepare(
                "UPDATE expenses SET receipt_image_url = ? WHERE school_id = ? AND account_id = ? AND id > ? ORDER BY id ASC LIMIT 1"
            );
            $stmt_update->execute([$receipt_image_path, $school_id, $account_id, $last_id]);
        }
        
        $pdo->commit();
        $response['success'] = true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $response['error'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
exit;
?>