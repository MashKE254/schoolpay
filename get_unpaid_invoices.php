<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');

try {
    // Validate student ID
    if (!isset($_GET['student_id'])) {
        throw new Exception('Student ID is required');
    }

    $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
    
    if (!$student_id || $student_id < 1) {
        throw new Exception('Invalid Student ID');
    }

    // Get unpaid invoices with positive balance
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.invoice_date,
            i.due_date,
            i.total_amount,
            COALESCE(SUM(p.amount), 0) AS paid_amount,
            (i.total_amount - COALESCE(SUM(p.amount), 0)) AS balance
        FROM invoices i
        LEFT JOIN payments p ON p.invoice_id = i.id
        WHERE i.student_id = ?
        GROUP BY i.id
        HAVING balance > 0
        ORDER BY i.due_date ASC
    ");

    $stmt->execute([$student_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format amounts as floats
    foreach ($invoices as &$invoice) {
        $invoice['total_amount'] = (float)$invoice['total_amount'];
        $invoice['paid_amount'] = (float)$invoice['paid_amount'];
        $invoice['balance'] = (float)$invoice['balance'];
    }

    echo json_encode([
        'success' => true,
        'data' => $invoices
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}