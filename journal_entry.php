<?php
// journal_entry.php - Handle journal entry submissions (Corrected Version)

session_start();
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');
$response = ['success' => false];
$school_id = $_SESSION['school_id'] ?? null;

if (!$school_id) {
    $response['error'] = 'Authentication session has expired.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $entry_date = $_POST['entry_date'];
        $description = $_POST['description'];
        $accounts = $_POST['account'];
        $debits = $_POST['debit'];
        $credits = $_POST['credit'];
        
        $total_debit = array_sum(array_filter($debits, 'is_numeric'));
        $total_credit = array_sum(array_filter($credits, 'is_numeric'));
        
        if (abs($total_debit - $total_credit) > 0.001) {
            throw new Exception("Debits and credits must balance.");
        }
        if ($total_debit == 0) {
            throw new Exception("Journal entry cannot be empty.");
        }
        
        for ($i = 0; $i < count($accounts); $i++) {
            $account_id = $accounts[$i];
            $debit_amount = !empty($debits[$i]) ? (float)$debits[$i] : 0;
            $credit_amount = !empty($credits[$i]) ? (float)$credits[$i] : 0;
            
            if ($debit_amount == 0 && $credit_amount == 0) continue;
            
            if ($debit_amount > 0) {
                $amount = $debit_amount;
                $transaction_type = 'debit';
            } else {
                $amount = $credit_amount;
                $transaction_type = 'credit';
            }
            
            // CORRECTED: Added school_id to the INSERT query
            $stmt = $pdo->prepare("
                INSERT INTO expenses (
                    school_id, account_id, transaction_date, amount, 
                    description, type, transaction_type
                ) VALUES (?, ?, ?, ?, ?, 'journal', ?)
            ");
            $stmt->execute([
                $school_id, $account_id, $entry_date, $amount,
                $description, $transaction_type
            ]);
            
            // Use the centralized balance update function for consistency
            updateAccountBalance($pdo, $account_id, $amount, $transaction_type, $school_id);
        }
        
        $pdo->commit();
        $response['success'] = true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
exit;