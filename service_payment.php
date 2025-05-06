<?php
// Start session and include necessary files
session_start();
require 'config.php';
require 'functions.php';

// Make sure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return JSON error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Set the content type header to application/json
header('Content-Type: application/json');

try {
    // Get form data
    $payment_date = $_POST['payment_date'] ?? '';
    $provider_name = $_POST['provider_name'] ?? '';
    $account_id = $_POST['account_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';

    // Validate required fields
    if (empty($payment_date) || empty($provider_name) || empty($account_id) || empty($amount)) {
        echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
        exit;
    }

    // Modify the description to include provider name
    $full_description = "Payment to " . $provider_name . ": " . $description;
    
    // Insert the service payment into the expense_transactions table
    $stmt = $pdo->prepare("
        INSERT INTO expense_transactions (
            transaction_date, 
            description, 
            amount, 
            account_id, 
            type, 
            transaction_type
        ) VALUES (
            :payment_date, 
            :description, 
            :amount, 
            :account_id, 
            'service_payment', 
            'debit'
        )
    ");

    $stmt->bindParam(':payment_date', $payment_date);
    $stmt->bindParam(':description', $full_description);
    $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
    $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
    $stmt->execute();

    // Also create a corresponding credit entry for the cash/bank account
    // (You may want to have this configurable to select which account to credit)
    
    // Get the cash/bank account (this is just an example - you might want to make this configurable)
    $cash_account_stmt = $pdo->prepare("SELECT id FROM accounts WHERE account_type = 'Assets' AND account_name LIKE '%Cash%' OR account_name LIKE '%Bank%' LIMIT 1");
    $cash_account_stmt->execute();
    $cash_account = $cash_account_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cash_account) {
        $cash_account_id = $cash_account['id'];
        
        $credit_stmt = $pdo->prepare("
            INSERT INTO expense_transactions (
                transaction_date, 
                description, 
                amount, 
                account_id, 
                type, 
                transaction_type
            ) VALUES (
                :payment_date, 
                :description, 
                :amount, 
                :account_id, 
                'service_payment', 
                'credit'
            )
        ");
        
        $credit_stmt->bindParam(':payment_date', $payment_date);
        $credit_stmt->bindParam(':description', $full_description);
        $credit_stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $credit_stmt->bindParam(':account_id', $cash_account_id, PDO::PARAM_INT);
        $credit_stmt->execute();
    }

    // Update account balances
    updateAccountBalance($pdo, $account_id);
    if (isset($cash_account_id)) {
        updateAccountBalance($pdo, $cash_account_id);
    }

    // Return success response
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Return error response
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Return error response for any other exceptions
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}