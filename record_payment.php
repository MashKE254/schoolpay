<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json; charset=utf-8');

// Only accept POST requests
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

    // 2. Basic required‐field checks
    if ($student_id <= 0) {
        throw new Exception('Invalid or missing student ID.');
    }
    if (empty($payment_date)) {
        throw new Exception('Payment date is required.');
    }
    if (empty($payment_method)) {
        throw new Exception('Payment method is required.');
    }

    // 3. Check that invoice_ids and payment_amounts are parallel arrays
    if (!is_array($invoice_ids) || !is_array($payment_amounts) ||
        count($invoice_ids) !== count($payment_amounts)
    ) {
        throw new Exception('Mismatched invoice IDs and payment amounts.');
    }

    // 4. Begin a single transaction for all inserts
    $pdo->beginTransaction();
    $totalPaid = 0.0;

    // 5. Loop through each invoice/payment pair
    foreach ($invoice_ids as $idx => $rawInvoiceId) {
        $invoice_id = intval($rawInvoiceId);
        if ($invoice_id < 1) {
            throw new Exception("Invalid invoice ID at position {$idx}.");
        }

        // Parse payment amount for this invoice
        $amount = floatval($payment_amounts[$idx] ?? 0);
        if ($amount <= 0) {
            // Skip zero or negative amounts
            continue;
        }

        // 5.a. Check current balance for this invoice
        $current_balance = getInvoiceBalance($pdo, $invoice_id);
        if ($amount > $current_balance) {
            throw new Exception("Payment amount (\${$amount}) exceeds current balance (\${$current_balance}) for Invoice #{$invoice_id}.");
        }

        // 5.b. Insert into payments table
        $insertPaymentStmt = $pdo->prepare(
            "INSERT INTO payments 
                (invoice_id, student_id, payment_date, amount, payment_method, memo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insertPaymentStmt->execute([
            $invoice_id,
            $student_id,
            $payment_date,
            $amount,
            $payment_method,
            $memo
        ]);

        // 5.c. Accumulate total paid
        $totalPaid += $amount;
    }

    // 6. If no positive payments were found, roll back and report
    if ($totalPaid <= 0) {
        throw new Exception('No valid payment amounts submitted.');
    }

    // 7. Insert a record into payment_receipts
    $receipt_number = 'REC-' . strtoupper(uniqid());
    $insertReceiptStmt = $pdo->prepare(
        "INSERT INTO payment_receipts
            (receipt_number, student_id, payment_date, amount, payment_method, memo)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $insertReceiptStmt->execute([
        $receipt_number,
        $student_id,
        $payment_date,
        $totalPaid,
        $payment_method,
        $memo
    ]);
    $receipt_id = $pdo->lastInsertId();

    // 8. Commit the transaction
    $pdo->commit();

    // 9. Build successful response
    $response = [
        'success'        => true,
        'receipt_id'     => $receipt_id,
        'receipt_number' => $receipt_number
    ];
}
catch (Exception $e) {
    // Any validation or general exception: roll back if in transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['error'] = $e->getMessage();
}
catch (PDOException $e) {
    // Database exceptions: also roll back if needed
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['error'] = 'Database error: ' . $e->getMessage();
}

// 10. Return JSON response
echo json_encode($response);
exit;
?>
