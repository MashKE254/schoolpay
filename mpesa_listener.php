<?php
// mpesa_listener.php - Listens for M-Pesa C2B payment notifications

// --- Configuration and Database Connection ---
require 'config.php'; // Your database credentials
require 'functions.php'; // Your custom functions

// --- Set Headers ---
header("Content-Type: application/json");

// --- Get the M-Pesa Notification Data ---
$mpesa_data = file_get_contents('php://input');

// --- Log the raw data for debugging ---
// It's crucial to log incoming requests to a file to debug any issues.
$log_file = "mpesa_payments.log";
file_put_contents($log_file, $mpesa_data . PHP_EOL, FILE_APPEND);

// --- Decode the JSON data from M-Pesa ---
$data = json_decode($mpesa_data);

// A success response to send back to Safaricom to acknowledge receipt
$success_response = '{"ResultCode":0, "ResultDesc":"Success"}';
$error_response = '{"ResultCode":1, "ResultDesc":"Error"}';

try {
    // --- Validate the incoming data ---
    // Check if key fields exist. In a production system, you'd do more validation.
    if (!isset($data->TransID, $data->TransAmount, $data->BillRefNumber)) {
        throw new Exception("Invalid M-Pesa data received.");
    }

    // --- Extract Key Information ---
    $transaction_id = $data->TransID;
    $amount_paid = (float)$data->TransAmount;
    $student_id_no = trim($data->BillRefNumber); // This is the Student ID used as Account No.
    $payer_phone = $data->MSISDN; // Parent's phone number
    $payment_date = date('Y-m-d H:i:s'); // Use the current time

    // --- Find the Student in the Database ---
    $stmt = $pdo->prepare("SELECT id, name, email FROM students WHERE student_id_no = ?");
    $stmt->execute([$student_id_no]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student with ID No. '{$student_id_no}' not found.");
    }
    
    $student_internal_id = $student['id'];

    // --- Find Student's Unpaid Invoices ---
    // We need to apply the payment to one or more invoices. Oldest first is a good strategy.
    $stmt = $pdo->prepare("SELECT id, balance FROM invoices WHERE student_id = ? AND balance > 0 ORDER BY due_date ASC");
    $stmt->execute([$student_internal_id]);
    $unpaid_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($unpaid_invoices)) {
        // Optional: Handle overpayments or payments when no invoice is due.
        // You could log this or credit the student's account for future use.
        throw new Exception("No unpaid invoices found for student ID '{$student_id_no}'. Payment not applied.");
    }

    // --- Database Transaction: Record Payment & Receipt ---
    $pdo->beginTransaction();

    // 1. Create a single receipt for this entire M-Pesa transaction
    $receipt_number = 'MPESA-' . $transaction_id;
    $receipt_memo = "M-Pesa payment from {$payer_phone}. Ref: {$transaction_id}";
    $receiptStmt = $pdo->prepare(
        "INSERT INTO payment_receipts (receipt_number, student_id, payment_date, amount, payment_method, memo) VALUES (?, ?, ?, ?, 'M-Pesa', ?)"
    );
    $receiptStmt->execute([$receipt_number, $student_internal_id, $payment_date, $amount_paid, $receipt_memo]);
    $receiptId = $pdo->lastInsertId();

    // 2. Apply the payment across the unpaid invoices
    $remaining_amount = $amount_paid;
    foreach ($unpaid_invoices as $invoice) {
        if ($remaining_amount <= 0) break;

        $invoice_id = $invoice['id'];
        $invoice_balance = (float)$invoice['balance'];
        
        $amount_to_apply = min($remaining_amount, $invoice_balance);

        // Insert into the payments table, linking it to the invoice and the new receipt
        $paymentStmt = $pdo->prepare(
            "INSERT INTO payments (invoice_id, student_id, payment_date, amount, payment_method, memo, receipt_id) VALUES (?, ?, ?, ?, 'M-Pesa', ?, ?)"
        );
        $paymentStmt->execute([$invoice_id, $student_internal_id, $payment_date, $amount_to_apply, $receipt_memo, $receiptId]);

        $remaining_amount -= $amount_to_apply;
    }

    $pdo->commit();

    // You would get these details from your Africa's Talking account
$username = "your_username";
$apiKey   = "your_api_key";

// The message to send
$message = "Dear Parent, we have received your payment of KES {$amount_paid} for student {$student['name']}. Receipt No: {$receipt_number}. Thank you.";

// The phone number to send to
$recipients = "+{$payer_phone}"; // Ensure phone number is in international format


} catch (Exception $e) {
    // If anything goes wrong, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log the error and respond to Safaricom
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo $error_response;
}

?>