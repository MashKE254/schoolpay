<?php
// Start with session and includes
session_start();
require 'config.php';
require 'functions.php';

// Set content type header for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'error' => ''
];

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize inputs
        $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : null;
        $supplier_name = isset($_POST['supplier_name']) ? trim($_POST['supplier_name']) : null;
        $invoice_number = isset($_POST['invoice_number']) ? trim($_POST['invoice_number']) : '';
        $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : null;
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        // Verify required data
        if (empty($payment_date)) {
            throw new Exception('Payment date is required');
        }
        if (empty($supplier_name)) {
            throw new Exception('Supplier name is required');
        }
        if (empty($account_id)) {
            throw new Exception('Account is required');
        }
        if (empty($amount) || $amount <= 0) {
            throw new Exception('Valid amount is required');
        }

        // Verify account exists
        $stmt = $pdo->prepare("SELECT id, account_name, account_type FROM accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            throw new Exception('Selected account does not exist');
        }

        // Format description
        $formatted_description = "Payment to supplier: " . $supplier_name;
        if (!empty($invoice_number)) {
            $formatted_description .= " (Invoice #: " . $invoice_number . ")";
        }
        if (!empty($description)) {
            $formatted_description .= " - " . $description;
        }

        // Start transaction
        $pdo->beginTransaction();
        
        // Insert into expense_transactions table
        $stmt = $pdo->prepare("
            INSERT INTO expense_transactions 
            (account_id, transaction_date, amount, description, type, transaction_type) 
            VALUES (?, ?, ?, ?, 'supplier_payment', 'debit')
        ");
        $stmt->execute([$account_id, $payment_date, $amount, $formatted_description]);
        
        // Update account balance
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        // If we're paying from cash/bank account, need to credit that account
        // Find the cash/bank account (assuming account code starting with 1 is an asset account)
        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE account_code LIKE '1%' AND account_type = 'Assets' LIMIT 1");
        $stmt->execute();
        $cashAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cashAccount) {
            // Record the credit transaction
            $stmt = $pdo->prepare("
                INSERT INTO expense_transactions 
                (account_id, transaction_date, amount, description, type, transaction_type) 
                VALUES (?, ?, ?, ?, 'supplier_payment', 'credit')
            ");
            $stmt->execute([$cashAccount['id'], $payment_date, $amount, $formatted_description]);
            
            // Update cash account balance
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $cashAccount['id']]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        $response['success'] = true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid request method';
}

// Return JSON response
echo json_encode($response);
exit;
?>