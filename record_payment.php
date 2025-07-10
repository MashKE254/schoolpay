<?php
session_start(); // Start session to access school_id
require 'config.php';
require 'functions.php';
require 'header.php'; // Ensures $school_id is set

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid request method. Use POST.'
    ]);
    exit;
}

$response = ['success' => false];

try {
    // 1. Validate and sanitize input
    $student_id      = intval($_POST['student_id'] ?? 0);
    $payment_date    = trim($_POST['payment_date'] ?? '');
    $payment_method  = trim($_POST['payment_method'] ?? '');
    $memo            = trim($_POST['memo'] ?? '');
    $invoice_ids     = $_POST['invoice_ids']     ?? [];
    $payment_amounts = $_POST['payment_amounts'] ?? [];
    $coa_account_id  = intval($_POST['coa_account_id'] ?? 0);

    // 2. Basic required‐field checks
    if ($student_id <= 0) throw new Exception('Invalid or missing student ID.');
    if (empty($payment_date)) throw new Exception('Payment date is required.');
    if (empty($payment_method)) throw new Exception('Payment method is required.');
    if ($coa_account_id <= 0) throw new Exception('A "Deposit To" account must be selected.');
    if (!is_array($invoice_ids) || !is_array($payment_amounts) || count($invoice_ids) !== count($payment_amounts)) {
        throw new Exception('Mismatched invoice IDs and payment amounts.');
    }

    // 3. Begin a single transaction for all database operations
    $pdo->beginTransaction();
    $totalPaid = 0.0;

    // 4. Create a single payment receipt first
    $receipt_number = 'REC-' . strtoupper(uniqid());
    $insertReceiptStmt = $pdo->prepare(
        "INSERT INTO payment_receipts
            (school_id, receipt_number, student_id, payment_date, amount, payment_method, memo, coa_account_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // 5. Loop through each invoice/payment pair
    foreach ($invoice_ids as $idx => $rawInvoiceId) {
        $invoice_id = intval($rawInvoiceId);
        $amount = floatval($payment_amounts[$idx] ?? 0);
        
        if ($invoice_id < 1 || $amount <= 0) {
            continue; // Skip zero payments or invalid invoices
        }

        // 5a. Insert into payments table
        $insertPaymentStmt = $pdo->prepare(
            "INSERT INTO payments 
                (school_id, invoice_id, student_id, payment_date, amount, payment_method, memo, coa_account_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertPaymentStmt->execute([
            $school_id,
            $invoice_id,
            $student_id,
            $payment_date,
            $amount,
            $payment_method,
            $memo,
            $coa_account_id
        ]);
        $totalPaid += $amount;
    }

    if ($totalPaid <= 0) {
        throw new Exception('No valid payment amounts were submitted.');
    }
    
    // 6. Now execute the receipt insertion with the final total amount
    $insertReceiptStmt->execute([
        $school_id,
        $receipt_number,
        $student_id,
        $payment_date,
        $totalPaid,
        $payment_method,
        $memo,
        $coa_account_id
    ]);
    $receipt_id = $pdo->lastInsertId();

    // 7. Link all of today's payments to the new receipt
    $updatePaymentsStmt = $pdo->prepare(
        "UPDATE payments SET receipt_id = ? 
         WHERE student_id = ? AND payment_date = ? AND memo = ? AND receipt_id IS NULL AND school_id = ?"
    );
    $updatePaymentsStmt->execute([$receipt_id, $student_id, $payment_date, $memo, $school_id]);

    // **FIX: Update the balance of the asset account in the General Ledger**
    // This adds the received payment amount to the account's balance.
    $updateAccountStmt = $pdo->prepare(
        "UPDATE accounts SET balance = balance + ? WHERE id = ? AND school_id = ?"
    );
    $updateAccountStmt->execute([$totalPaid, $coa_account_id, $school_id]);

    // 8. Commit the transaction
    $pdo->commit();

    // 9. Build successful response
    $response = [
        'success'        => true,
        'receipt_id'     => $receipt_id,
        'receipt_number' => $receipt_number
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['error'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['error'] = 'Database error: ' . $e->getMessage();
}

// 10. Return JSON response
echo json_encode($response);
exit;
?>
