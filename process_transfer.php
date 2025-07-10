<?php
// process_transfer.php - Corrected Version
// This script now handles its own session to prevent the "headers already sent" error.

session_start();
require_once 'config.php';
require_once 'functions.php';

// Check for a valid session and school_id before processing
if (!isset($_SESSION['school_id'])) {
    die("Error: Your session has expired or is invalid. Please log in again.");
}
$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Correctly read values from the "Transfer Funds" form
    $from_account_id = (int)($_POST['from_account'] ?? 0);
    $to_account_id = (int)($_POST['to_account'] ?? 0);
    $amount = (float)($_POST['transfer_amount'] ?? 0);
    $date = $_POST['transfer_date'] ?? date('Y-m-d');
    $memo = "Fund Transfer: " . ($_POST['transfer_memo'] ?? 'Internal Transfer');

    // Validate the inputs
    if ($from_account_id <= 0 || $to_account_id <= 0) {
        die("Error: You must select both a 'From' and a 'To' account.");
    }
    if ($from_account_id === $to_account_id) {
        die("Error: Cannot transfer funds to the same account.");
    }
    if ($amount <= 0) {
        die("Error: Transfer amount must be greater than zero.");
    }

    $pdo->beginTransaction();
    try {
        // Step 1: Credit (decrease) the "From" account. This creates a record of money leaving.
        $stmt_credit = $pdo->prepare(
            "INSERT INTO expenses (school_id, transaction_date, description, amount, account_id, transaction_type, type) 
             VALUES (?, ?, ?, ?, ?, 'credit', 'transfer')"
        );
        $stmt_credit->execute([$school_id, $date, $memo, $amount, $from_account_id]);
        updateAccountBalance($pdo, $from_account_id, $amount, 'credit', $school_id);

        // Step 2: Debit (increase) the "To" account. This creates a record of money arriving.
        $stmt_debit = $pdo->prepare(
            "INSERT INTO expenses (school_id, transaction_date, description, amount, account_id, transaction_type, type) 
             VALUES (?, ?, ?, ?, ?, 'debit', 'transfer')"
        );
        $stmt_debit->execute([$school_id, $date, $memo, $amount, $to_account_id]);
        updateAccountBalance($pdo, $to_account_id, $amount, 'debit', $school_id);

        $pdo->commit();
        header("Location: banking.php?success=1");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error processing transfer: " . $e->getMessage());
    }
} else {
    // Redirect if accessed directly without POST method
    header("Location: banking.php");
    exit();
}
