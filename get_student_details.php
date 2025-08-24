<?php
/**
 * get_student_details.php - v3.0 - Professional Grade
 * AJAX endpoint to fetch a comprehensive, chronological financial history for a student.
 * - Fetches student personal information.
 * - Calculates their overall financial summary (invoiced, paid, balance).
 * - Combines invoices, payments, and promises into a single, sorted transaction timeline.
 */
header('Content-Type: application/json');
require_once 'config.php';
require_once 'functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$school_id = $_SESSION['school_id'];
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($student_id)) {
    echo json_encode(['success' => false, 'error' => 'No student ID provided.']);
    exit();
}

try {
    // --- 1. Fetch Student Info ---
    $student = getStudentById($pdo, $student_id, $school_id);
    if (!$student) {
        throw new Exception('Student not found.');
    }

    // --- 2. Calculate Financial Summary ---
    $stmt_summary = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_amount), 0) as totalInvoiced,
            COALESCE(SUM(paid_amount), 0) as totalPaid
        FROM invoices 
        WHERE student_id = ? AND school_id = ?
    ");
    $stmt_summary->execute([$student_id, $school_id]);
    $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);
    $summary['balance'] = $summary['totalInvoiced'] - $summary['totalPaid'];

    // --- 3. Build the Transaction History Timeline ---
    $transaction_history = [];

    // Fetch Invoices
    $invoices_stmt = $pdo->prepare("SELECT * FROM invoices WHERE student_id = ? AND school_id = ?");
    $invoices_stmt->execute([$student_id, $school_id]);
    while ($row = $invoices_stmt->fetch(PDO::FETCH_ASSOC)) {
        $transaction_history[] = [
            'type' => 'invoice',
            'date' => $row['invoice_date'],
            'data' => $row
        ];
    }

    // Fetch Payments
    $payments_stmt = $pdo->prepare("
        SELECT p.*, pr.receipt_number 
        FROM payments p 
        LEFT JOIN payment_receipts pr ON p.receipt_id = pr.id
        WHERE p.student_id = ? AND p.school_id = ?
    ");
    $payments_stmt->execute([$student_id, $school_id]);
     while ($row = $payments_stmt->fetch(PDO::FETCH_ASSOC)) {
        $transaction_history[] = [
            'type' => 'payment',
            'date' => $row['payment_date'],
            'data' => $row
        ];
    }

    // Fetch Promises
    $promises_stmt = $pdo->prepare("SELECT * FROM payment_promises WHERE student_id = ? AND school_id = ?");
    $promises_stmt->execute([$student_id, $school_id]);
    while ($row = $promises_stmt->fetch(PDO::FETCH_ASSOC)) {
        $transaction_history[] = [
            'type' => 'promise',
            'date' => $row['promise_date'],
            'data' => $row
        ];
    }

    // Sort the combined history by date, descending
    usort($transaction_history, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // --- 4. Assemble the final JSON Response ---
    $response = [
        'success' => true,
        'student' => $student,
        'summary' => [
            'totalInvoiced' => (float)$summary['totalInvoiced'],
            'totalPaid' => (float)$summary['totalPaid'],
            'balance' => (float)$summary['balance']
        ],
        'history' => $transaction_history
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
