<?php
// journal_entry.php - Handle journal entry submissions

session_start();
require 'config.php';
require 'functions.php';

// Handle journal entry submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        $entry_date = $_POST['entry_date'];
        $description = $_POST['description'];
        $accounts = $_POST['account'];
        $debits = $_POST['debit'];
        $credits = $_POST['credit'];
        
        // Validate totals match
        $total_debit = array_sum(array_filter($debits, 'is_numeric'));
        $total_credit = array_sum(array_filter($credits, 'is_numeric'));
        
        if (abs($total_debit - $total_credit) > 0.001) { // Allow tiny float precision differences
            throw new Exception("Debits and credits must balance.");
        }
        
        // Process each line
        for ($i = 0; $i < count($accounts); $i++) {
            $account_id = $accounts[$i];
            $debit_amount = !empty($debits[$i]) ? $debits[$i] : 0;
            $credit_amount = !empty($credits[$i]) ? $credits[$i] : 0;
            
            // Skip if both debit and credit are zero
            if ($debit_amount == 0 && $credit_amount == 0) {
                continue;
            }
            
            // Determine transaction type and amount
            if ($debit_amount > 0) {
                $amount = $debit_amount;
                $transaction_type = 'debit';
            } else {
                $amount = $credit_amount;
                $transaction_type = 'credit';
            }
            
            // Insert transaction record - using expense_transactions table
            $stmt = $pdo->prepare("
                INSERT INTO expenses (
                    account_id, 
                    transaction_date, 
                    amount, 
                    description, 
                    type,
                    transaction_type
                ) VALUES (?, ?, ?, ?, 'journal', ?)
            ");
            $stmt->execute([
                $account_id,
                $entry_date,
                $amount,
                $description,
                $transaction_type
            ]);
            
            // Update account balance
            // For assets and expenses, debits increase the balance and credits decrease it
            // For liabilities, equity, and revenue, credits increase the balance and debits decrease it
            $stmt = $pdo->prepare("SELECT account_type FROM accounts WHERE id = ?");
            $stmt->execute([$account_id]);
            $account_type = $stmt->fetchColumn();
            
            $balance_adjustment = 0;
            
            if (in_array($account_type, ['Assets', 'Expenses'])) {
                // Debit increases, credit decreases
                $balance_adjustment = $debit_amount - $credit_amount;
            } else {
                // Credit increases, debit decreases
                $balance_adjustment = $credit_amount - $debit_amount;
            }
            
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$balance_adjustment, $account_id]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Return error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Return error for non-POST requests
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
exit;