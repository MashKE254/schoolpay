<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');
session_start();

try {
    // Check for user authentication and school context
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
        throw new Exception('Authentication required.');
    }
    $school_id = $_SESSION['school_id'];

    // Validate student ID from the GET request
    if (!isset($_GET['student_id'])) {
        throw new Exception('Student ID is required.');
    }
    $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
    if (!$student_id || $student_id < 1) {
        throw new Exception('Invalid Student ID.');
    }

    // Corrected and simplified query:
    // - Uses the reliable, pre-calculated 'balance' column from the invoices table.
    // - Adds the crucial 'school_id' check for multi-tenancy security.
    // - Uses a small threshold (0.009) to safely handle floating-point numbers.
    $stmt = $pdo->prepare("
        SELECT 
            id,
            invoice_date,
            due_date,
            total_amount,
            paid_amount,
            balance
        FROM invoices
        WHERE student_id = :student_id 
          AND school_id = :school_id
          AND balance > 0.009
        ORDER BY due_date ASC
    ");

    $stmt->execute([
        ':student_id' => $student_id,
        ':school_id' => $school_id
    ]);
    
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure numeric types are correctly cast for JSON response
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
    http_response_code(400); // Set appropriate error status
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}