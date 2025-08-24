<?php
/**
 * process_categorized_requisition.php
 *
 * Handles the final processing of a requisition batch after items have been
 * assigned to expense accounts. It performs the double-entry accounting
 * transactions and now ALSO creates corresponding records in the payroll table
 * for any items categorized as salaries or wages.
 */

require_once 'config.php';
require_once 'functions.php'; // Contains updateAccountBalance() and getOrCreateAccount()

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication and required session variables
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    header("Location: login.php?error=session_expired");
    exit();
}

$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];

// Ensure this script is accessed via a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: expense_management.php?error=invalid_request");
    exit();
}

// --- Input Validation ---
$batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
$items = $_POST['items'] ?? [];

if (!$batch_id || empty($items) || !is_array($items)) {
    header("Location: expense_management.php?error=missing_data");
    exit();
}

// --- Begin Database Transaction ---
$pdo->beginTransaction();

try {
    // 1. Fetch the requisition batch details to verify it's valid and not yet processed
    $stmt_batch = $pdo->prepare(
        "SELECT * FROM requisition_batches WHERE id = :batch_id AND school_id = :school_id AND status = 'pending_categorization'"
    );
    $stmt_batch->execute([':batch_id' => $batch_id, ':school_id' => $school_id]);
    $batch = $stmt_batch->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        throw new Exception("This requisition batch was not found, has already been processed, or does not belong to your school.");
    }

    $payment_account_id = $batch['payment_account_id'];
    $transaction_date = $batch['transaction_date'];
    $grand_total = 0;
    
    // **NEW**: Get the ID for the 'Salaries & Wages' account to identify payroll items.
    $salaries_account_id = getOrCreateAccount($pdo, $school_id, 'Salaries & Wages', 'expense', '6010');

    // 2. Prepare statements for reuse in the loop
    $stmt_update_item = $pdo->prepare("UPDATE requisition_items SET assigned_expense_account_id = :account_id WHERE id = :item_id AND batch_id = :batch_id");
    $stmt_get_item_details = $pdo->prepare("SELECT total_cost, description FROM requisition_items WHERE id = :item_id");
    $stmt_insert_payroll = $pdo->prepare(
        "INSERT INTO payroll (school_id, employee_name, employee_type, pay_period, pay_date, gross_pay, net_pay, status, notes) 
         VALUES (?, ?, 'casual', ?, ?, ?, ?, 'Paid', ?)"
    );

    foreach ($items as $item_data) {
        $item_id = filter_var($item_data['id'], FILTER_VALIDATE_INT);
        $account_id = filter_var($item_data['account_id'], FILTER_VALIDATE_INT);

        if (!$item_id || !$account_id) {
            throw new Exception("Invalid item or account ID provided.");
        }

        // Assign the expense account to the item in the database
        $stmt_update_item->execute([
            ':account_id' => $account_id,
            ':item_id' => $item_id,
            ':batch_id' => $batch_id
        ]);
        
        // Securely get the item's cost and description from the database
        $stmt_get_item_details->execute([':item_id' => $item_id]);
        $item = $stmt_get_item_details->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $item_cost = (float)$item['total_cost'];
            $grand_total += $item_cost;

            // **NEW LOGIC**: If the item is a salary, create a payroll record.
            if ($account_id === $salaries_account_id) {
                // Attempt to parse the name from the description (e.g., "Joseph Wanjiru-(07...)" -> "Joseph Wanjiru")
                $employee_name = $item['description'];
                if (preg_match('/^([a-zA-Z\s\']+)/', $item['description'], $matches)) {
                    $employee_name = trim($matches[1]);
                }

                $pay_period = date('Y-m', strtotime($transaction_date));
                $notes = "Paid via requisition batch #" . $batch_id;

                $stmt_insert_payroll->execute([
                    $school_id,
                    $employee_name,
                    $pay_period,
                    $transaction_date,
                    $item_cost, // gross_pay
                    $item_cost, // net_pay
                    $notes
                ]);
            }
        }
    }

    // 3. Create the balanced double-entry expense transactions in the general ledger
    // This part is now simplified because the payroll system handles the detail,
    // and the accounting system just needs to know the total expense.
    create_journal_entry(
        $pdo,
        $school_id,
        $transaction_date,
        "Bulk payment for requisition batch #{$batch_id} (Ref: " . htmlspecialchars($batch['original_filename']) . ")",
        $grand_total,
        $salaries_account_id, // Debit the main expense account
        $payment_account_id   // Credit the asset account (e.g., Petty Cash)
    );

    // 4. Mark the requisition batch as processed
    $stmt_update_batch = $pdo->prepare("UPDATE requisition_batches SET status = 'processed' WHERE id = :batch_id");
    $stmt_update_batch->execute([':batch_id' => $batch_id]);

    // 5. Commit the transaction
    $pdo->commit();

    // Redirect with a success message
    header("Location: expense_management.php?tab=requisition&success=" . urlencode("Requisition batch #{$batch_id} was processed successfully! Casual payments have been added to payroll history."));
    exit();

} catch (Exception $e) {
    // If anything goes wrong, roll back the entire transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirect back to the categorization page with a clear error message
    $error_info = "Error processing requisition: " . $e->getMessage();
    header("Location: categorize_requisition.php?batch_id={$batch_id}&error=" . urlencode($error_info));
    exit();
}
?>
