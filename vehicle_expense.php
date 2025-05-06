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
        $expense_date = isset($_POST['expense_date']) ? $_POST['expense_date'] : null;
        $vehicle_id = isset($_POST['vehicle_id']) ? trim($_POST['vehicle_id']) : null;
        $expense_type = isset($_POST['expense_type']) ? trim($_POST['expense_type']) : null;
        $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : null;
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : null;
        $odometer = isset($_POST['odometer']) ? (float)$_POST['odometer'] : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        // Verify required data
        if (empty($expense_date)) {
            throw new Exception('Expense date is required');
        }
        if (empty($vehicle_id)) {
            throw new Exception('Vehicle ID is required');
        }
        if (empty($expense_type)) {
            throw new Exception('Expense type is required');
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
        $formatted_description = "Vehicle expense: " . ucfirst($expense_type) . " for vehicle " . $vehicle_id;
        if ($odometer) {
            $formatted_description .= " (Odometer: " . $odometer . ")";
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
            VALUES (?, ?, ?, ?, 'vehicle_expense', 'debit')
        ");
        $stmt->execute([$account_id, $expense_date, $amount, $formatted_description]);
        
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
                VALUES (?, ?, ?, ?, 'vehicle_expense', 'credit')
            ");
            $stmt->execute([$cashAccount['id'], $expense_date, $amount, $formatted_description]);
            
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