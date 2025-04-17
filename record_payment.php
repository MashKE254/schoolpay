<?php
require 'config.php';
require 'functions.php';

// Set header and force JSON output with correct charset
header('Content-Type: application/json; charset=utf-8');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$response = ['success' => false];

try {
    // Retrieve and sanitize POST input
    $student_id = intval($_POST['student_id'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? '';
    
    // IMPORTANT FIX: Match the parameter name from the JavaScript form
    // Changed from 'method' to 'payment_method' to match form data
    $payment_method = $_POST['payment_method'] ?? '';
    
    $memo = $_POST['memo'] ?? '';
    $invoice_payments = $_POST['invoice_payments'] ?? [];
    
    // Validate required fields
    if ($student_id <= 0 || empty($payment_date) || empty($payment_method)) {
        throw new Exception('Missing or invalid payment data.');
    }
    
    // Calculate the total payment from the individual invoice payment amounts
    $total_payment = 0;
    foreach ($invoice_payments as $invoice_id => $payment_amount) {
        $payment_amount = floatval($payment_amount);
        if ($payment_amount > 0) {
            $total_payment += $payment_amount;
        }
    }
    
    // If no positive payment has been provided, throw an error
    if ($total_payment <= 0) {
        throw new Exception('No payment amount provided.');
    }
    
    // Allow partial payment: Use the sum of the invoice payments as the actual amount
    $amount = $total_payment;
    
    $pdo->beginTransaction();

    foreach ($invoice_payments as $invoice_id => $amount) {
        $amount = floatval($amount);
        if ($amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, student_id, payment_date, amount, payment_method, memo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$invoice_id, $student_id, $payment_date, $amount, $payment_method, $memo]);

            $stmt = $pdo->prepare("UPDATE invoices SET paid_amount = paid_amount + ? WHERE id = ?");
            $stmt->execute([$amount, $invoice_id]);
        }
    }

    $pdo->commit();
    $response['success'] = true;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>