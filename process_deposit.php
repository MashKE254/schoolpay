<?php
// process_deposit.php - Handles grouping payments into a single deposit.

require_once 'config.php';
require_once 'functions.php';

// Start session to get the school_id
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in and has a valid school session
if (!isset($_SESSION['school_id'])) {
    die("Error: Your session has expired or is invalid. Please log in again.");
}
$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_ids = $_POST['payment_ids'] ?? [];
    $bank_account_id = (int)($_POST['deposit_to_account'] ?? 0);
    $deposit_date = $_POST['deposit_date'] ?? date('Y-m-d');

    // Basic validation
    if (empty($payment_ids) || empty($bank_account_id)) {
        // Redirect back with an error message
        $_SESSION['error_message'] = "You must select at least one payment and a bank account to deposit to.";
        header("Location: banking.php?tab=deposit");
        exit();
    }

    $pdo->beginTransaction();
    try {
        // 1. Calculate the total deposit amount from the selected payments
        $in_clause = implode(',', array_fill(0, count($payment_ids), '?'));
        $total_stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE id IN ($in_clause) AND school_id = ? AND deposit_id IS NULL");
        
        $params = array_merge($payment_ids, [$school_id]);
        $total_stmt->execute($params);
        $total_deposit = $total_stmt->fetchColumn();

        if ($total_deposit > 0) {
            // 2. Find the "Undeposited Funds" account ID. This is where the money is coming FROM.
            $undeposited_account_id = getUndepositedFundsAccountId($pdo, $school_id);
            if (!$undeposited_account_id) {
                throw new Exception("The 'Undeposited Funds' account could not be found. Please ensure it exists in your Chart of Accounts.");
            }

            // 3. Create a single master deposit record in the 'deposits' table
            $memo = "Bank Deposit of " . count($payment_ids) . " payments.";
            $deposit_stmt = $pdo->prepare(
                "INSERT INTO deposits (school_id, deposit_date, account_id, amount, memo) VALUES (?, ?, ?, ?, ?)"
            );
            $deposit_stmt->execute([$school_id, $deposit_date, $bank_account_id, $total_deposit, $memo]);
            $deposit_id = $pdo->lastInsertId();

            // 4. Link all the individual payments to this new deposit record
            $update_payments_stmt = $pdo->prepare("UPDATE payments SET deposit_id = ? WHERE id IN ($in_clause) AND school_id = ?");
            // The parameters here need to be structured correctly for execute.
            $update_params = array_merge([$deposit_id], $payment_ids, [$school_id]);
            $update_payments_stmt->execute($update_params);

            // 5. **THE FIX**: Create a balanced journal entry for the transfer.
            // This replaces the direct balance updates and ensures the transaction appears in the General Ledger.
            // Debit the bank account (asset increases), Credit Undeposited Funds (asset decreases).
            create_journal_entry($pdo, $school_id, $deposit_date, $memo, $total_deposit, $bank_account_id, $undeposited_account_id);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Successfully deposited $" . number_format($total_deposit, 2) . " from " . count($payment_ids) . " payments.";
        header("Location: banking.php?tab=deposit");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error processing deposit: " . $e->getMessage();
        header("Location: banking.php?tab=deposit");
        exit();
    }
} else {
    // Redirect if accessed directly
    header("Location: banking.php");
    exit();
}