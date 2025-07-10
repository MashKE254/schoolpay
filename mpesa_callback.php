<?php
// mpesa_callback.php - Corrected Version
// Fixes negative balance and applies payment to student invoices.

require 'config.php';
require 'functions.php';

// Get the raw POST data from the M-Pesa API and log it
$postData = file_get_contents('php://input');
file_put_contents("mpesa_requests.log", $postData . "\n", FILE_APPEND);

$data = json_decode($postData, true);

// Process only if it's a standard Paybill transaction
if (isset($data['TransactionType']) && $data['TransactionType'] == 'Pay Bill') {
    
    $transactionId = $data['TransID'];
    $transactionAmount = (float)$data['TransAmount'];
    $studentAdmissionNo = trim($data['BillRefNumber']);
    $payerMsisdn = $data['MSISDN'];
    $payerFirstName = $data['FirstName'];
    $transactionTime = $data['TransTime'];

    $pdo->beginTransaction();
    try {
        // 1. Prevent duplicate processing
        $stmt = $pdo->prepare("SELECT id FROM mpesa_transactions WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        if ($stmt->fetch()) {
            $pdo->commit();
            // Respond to Safaricom but exit script to prevent re-processing
            header('Content-Type: application/json');
            echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
            exit();
        }

        // 2. Find the student and their school
        $stmt = $pdo->prepare("SELECT id, school_id FROM students WHERE student_id_no = ?");
        $stmt->execute([$studentAdmissionNo]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) throw new Exception("Student with admission number {$studentAdmissionNo} not found.");
        
        $student_id = $student['id'];
        $school_id = $student['school_id'];
        
        // 3. Find the school's M-Pesa asset account
        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE school_id = ? AND (account_name LIKE '%M-Pesa%' OR account_name LIKE '%Paybill%') LIMIT 1");
        $stmt->execute([$school_id]);
        $mpesa_account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$mpesa_account) throw new Exception("M-Pesa asset account not configured for school ID {$school_id}.");
        $coa_account_id = $mpesa_account['id'];

        // 4. Create a single payment receipt for the total amount
        $payment_date = date('Y-m-d H:i:s', strtotime($transactionTime));
        $memo = "M-Pesa payment from {$payerFirstName}. Ref: {$transactionId}";
        $receipt_number = 'MP-' . $transactionId;
        $stmt = $pdo->prepare(
            "INSERT INTO payment_receipts (school_id, receipt_number, student_id, payment_date, amount, payment_method, memo, coa_account_id)
             VALUES (?, ?, ?, ?, ?, 'M-Pesa', ?, ?)"
        );
        $stmt->execute([$school_id, $receipt_number, $student_id, $payment_date, $transactionAmount, $memo, $coa_account_id]);
        $receiptId = $pdo->lastInsertId();
        
        // 5. **FIX**: Apply the payment to the student's unpaid invoices
        $unpaid_invoices = getUnpaidInvoices($pdo, $student_id, $school_id);
        $remaining_amount_to_apply = $transactionAmount;

        foreach ($unpaid_invoices as $invoice) {
            if ($remaining_amount_to_apply <= 0) break;

            $balance_due = $invoice['balance'];
            $amount_to_pay_on_invoice = min($remaining_amount_to_apply, $balance_due);

            // Insert into the payments table to link payment to invoice
            $paymentStmt = $pdo->prepare(
                "INSERT INTO payments (school_id, invoice_id, student_id, payment_date, amount, payment_method, memo, receipt_id, coa_account_id) 
                 VALUES (?, ?, ?, ?, ?, 'M-Pesa', ?, ?, ?)"
            );
            $paymentStmt->execute([$school_id, $invoice['id'], $student_id, $payment_date, $amount_to_pay_on_invoice, $memo, $receiptId, $coa_account_id]);
            
            $remaining_amount_to_apply -= $amount_to_pay_on_invoice;
        }

        // 6. **FIX**: Update the M-Pesa account balance correctly
        // An incoming payment is a DEBIT to an asset account (which increases its balance).
        updateAccountBalance($pdo, $coa_account_id, $transactionAmount, 'debit', $school_id);

        // 7. Log the raw M-Pesa transaction for auditing
        $stmt = $pdo->prepare("INSERT INTO mpesa_transactions (transaction_id, amount, student_id, raw_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$transactionId, $transactionAmount, $student_id, $postData]);

        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        file_put_contents('mpesa_errors.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Respond to Safaricom to acknowledge receipt of the callback
header('Content-Type: application/json');
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Accepted"
]);
exit();
