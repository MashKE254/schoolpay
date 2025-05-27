<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$response = ['success' => false];

try {
    // Validate and sanitize input
    $student_id = intval($_POST['student_id'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $memo = $_POST['memo'] ?? '';
    
    // Get parallel arrays of invoice IDs and payment amounts
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    $payment_amounts = $_POST['payment_amounts'] ?? [];

    // Validate required fields
    if ($student_id <= 0 || empty($payment_date) || empty($payment_method)) {
        throw new Exception('Missing or invalid payment data.');
    }

    // Validate array lengths match
    if (count($invoice_ids) !== count($payment_amounts)) {
        throw new Exception('Mismatched invoice and payment data.');
    }

    $pdo->beginTransaction();

    // Add after getting $invoice_ids and $payment_amounts:
    foreach ($invoice_ids as $index => $invoice_id) {
    $invoice_id = intval($invoice_id);
    if ($invoice_id < 1) {
        throw new Exception("Invalid invoice ID at position $index");
    }

    $amount = floatval($payment_amounts[$index] ?? 0);
    if ($amount > 0) {
        // Validate against invoice balance
        $current_balance = getInvoiceBalance($pdo, $invoice_id);
        if ($amount > $current_balance) {
            throw new Exception("Payment amount exceeds balance for Invoice #$invoice_id");
        }

        // Insert payment
        $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, student_id, payment_date, amount, payment_method, memo) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_id, $student_id, $payment_date, $amount, $payment_method, $memo]);

        // Remove the manual update to invoices.paid_amount here
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