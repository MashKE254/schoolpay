<?php
// service_payment.php - Corrected Version
session_start();
require 'config.php';
require 'functions.php';
require 'header.php'; // Ensures $school_id is available

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize inputs
        $payment_date = $_POST['payment_date'] ?? '';
        $provider_name = $_POST['provider_name'] ?? '';
        $account_id = (int)($_POST['account_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $description = $_POST['description'] ?? '';
        $receipt_image_path = null;

        if (empty($payment_date) || empty($provider_name) || empty($account_id) || $amount <= 0) {
            throw new Exception('All required fields must be filled with valid data.');
        }

        // Handle file upload
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/receipts/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            
            $file_ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('receipt_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target_file)) {
                $receipt_image_path = $target_file;
            }
        }

        $full_description = "Payment to " . $provider_name . ": " . $description;

        // Start transaction
        $pdo->beginTransaction();

        // 1. Debit the expense account
        $stmt = $pdo->prepare("
            INSERT INTO expenses (school_id, transaction_date, description, amount, account_id, type, transaction_type, receipt_image_url) 
            VALUES (?, ?, ?, ?, ?, 'service_payment', 'debit', ?)
        ");
        $stmt->execute([$school_id, $payment_date, $full_description, $amount, $account_id, $receipt_image_path]);

        // Use the new function to update the expense account balance
        updateAccountBalance($pdo, $account_id, $amount, 'debit', $school_id);

        // 2. Credit the cash/bank account
        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE school_id = ? AND account_type = 'asset' ORDER BY id LIMIT 1");
        $stmt->execute([$school_id]);
        $cashAccount = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cashAccount) {
            $cash_account_id = $cashAccount['id'];
            $stmt = $pdo->prepare("
                INSERT INTO expenses (school_id, transaction_date, description, amount, account_id, type, transaction_type, receipt_image_url) 
                VALUES (?, ?, ?, ?, ?, 'service_payment', 'credit', ?)
            ");
            $stmt->execute([$school_id, $payment_date, $full_description, $amount, $cash_account_id, $receipt_image_path]);

            // Use the new function to update the cash account balance
            updateAccountBalance($pdo, $cash_account_id, $amount, 'credit', $school_id);
        }

        // Commit transaction
        $pdo->commit();
        $response['success'] = true;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['error'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
exit;
?>
