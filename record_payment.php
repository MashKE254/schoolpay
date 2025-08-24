<?php
session_start();
require 'config.php';
require 'functions.php'; // This must contain getOrCreateAccount() and create_journal_entry()

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired.']);
    exit;
}
$school_id = $_SESSION['school_id'];
$response = ['success' => false];

try {
    $student_id      = intval($_POST['student_id'] ?? 0);
    $payment_date    = trim($_POST['payment_date'] ?? '');
    $payment_method  = trim($_POST['payment_method'] ?? '');
    $memo            = trim($_POST['memo'] ?? '');
    $invoice_ids     = $_POST['invoice_ids']     ?? [];
    $payment_amounts = $_POST['payment_amounts'] ?? [];
    $deposit_to_account_id  = intval($_POST['coa_account_id'] ?? 0); // Asset account to debit

    if ($student_id <= 0 || empty($payment_date) || empty($payment_method) || $deposit_to_account_id <= 0) {
        throw new Exception('Missing required fields: Student, Date, Method, or Deposit To Account.');
    }

    $pdo->beginTransaction();

    $totalPaid = 0;
    foreach ($payment_amounts as $amount) {
        $totalPaid += floatval($amount);
    }

    if ($totalPaid <= 0) {
        throw new Exception('Total payment amount must be greater than zero.');
    }

    $stmt_student = $pdo->prepare("SELECT name FROM students WHERE id = ?");
    $stmt_student->execute([$student_id]);
    $student_name = $stmt_student->fetchColumn();

    $receipt_number = 'REC-' . strtoupper(uniqid());
    $stmt_receipt = $pdo->prepare(
        "INSERT INTO payment_receipts (school_id, receipt_number, student_id, payment_date, amount, payment_method, memo, coa_account_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_receipt->execute([$school_id, $receipt_number, $student_id, $payment_date, $totalPaid, $payment_method, $memo, $deposit_to_account_id]);
    $receiptId = $pdo->lastInsertId();

    foreach ($invoice_ids as $index => $invoice_id) {
        $amount = floatval($payment_amounts[$index]);
        if ($amount > 0) {
            $stmt_payment = $pdo->prepare(
                "INSERT INTO payments (school_id, invoice_id, student_id, payment_date, amount, payment_method, memo, receipt_id, coa_account_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_payment->execute([$school_id, $invoice_id, $student_id, $payment_date, $amount, $payment_method, $memo, $receiptId, $deposit_to_account_id]);
        }
    }

    // The account being credited should be Accounts Receivable (an asset account)
    $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
    $description = "Payment from {$student_name}. Receipt #{$receipt_number}";

    // Debit the asset account (e.g., Bank), Credit Accounts Receivable.
    create_journal_entry($pdo, $school_id, $payment_date, $description, $totalPaid, $deposit_to_account_id, $accounts_receivable_id);

    $pdo->commit();

    $response = [
        'success'        => true,
        'message'        => 'Payment recorded successfully.',
        'receipt_id'     => $receiptId,
        'receipt_number' => $receipt_number
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>
