<?php
/**
 * get_class_details.php
 *
 * AJAX endpoint to fetch comprehensive details for a specific class.
 *
 * This script is responsible for:
 * - Validating that a valid class ID is provided in the request.
 * - Ensuring the user is authenticated and has the necessary permissions for their school.
 * - Fetching the main details for the specified class from the 'classes' table.
 * - Fetching a complete list of students (id, name, status, and individual balance) associated with that class.
 * - Calculating a financial summary (total invoiced, total paid, and current balance) for the entire class.
 * - Returning all the collected data as a single, well-structured JSON object for the frontend to consume.
 */

// Set the content type of the response to JSON to ensure the browser interprets it correctly.
header('Content-Type: application/json');

// Include necessary configuration and shared function files.
require_once 'config.php';
require_once 'functions.php';

// Start the session to access session variables like user_id and school_id.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Security Check ---
// Verify that the user is logged in and has a school context. If not, deny access.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Authentication required. Please log in.']);
    exit();
}

// Store the school ID from the session for use in all subsequent database queries.
$school_id = $_SESSION['school_id'];

// --- Input Validation ---
// Check if a class ID was provided in the request and if it's a numeric value.
if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'A valid class ID is required.']);
    exit;
}
// Sanitize and store the class ID to prevent potential security issues.
$class_id = intval($_GET['class_id']);


try {
    // --- 1. Fetch Basic Class Details ---
    // Prepare a statement to get the main details of the class, ensuring it belongs to the correct school.
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :class_id AND school_id = :school_id");
    $stmt->execute([
        ':class_id' => $class_id,
        ':school_id' => $school_id
    ]);
    $classDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no class is found, it's either the wrong school or the class doesn't exist.
    if (!$classDetails) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'error' => 'Class not found or you do not have permission to view it.']);
        exit();
    }

    // --- 2. Fetch Associated Students ---
    $stmt_students = $pdo->prepare("SELECT id, name, student_id_no, status FROM students WHERE class_id = :class_id AND school_id = :school_id ORDER BY name ASC");
    $stmt_students->execute([':class_id' => $class_id, ':school_id' => $school_id]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // --- 2.5. Calculate Balance for Each Student (Efficiently) ---
    $student_ids = array_column($students, 'id');
    if (!empty($student_ids)) {
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $stmt_balances = $pdo->prepare("
            SELECT
                student_id,
                COALESCE(SUM(total_amount), 0) - COALESCE(SUM(amount_paid), 0) as balance
            FROM invoices
            WHERE student_id IN ($placeholders) AND school_id = ?
            GROUP BY student_id
        ");
        $params = array_merge($student_ids, [$school_id]);
        $stmt_balances->execute($params);
        $balances_map = $stmt_balances->fetchAll(PDO::FETCH_KEY_PAIR); // Creates an associative array [student_id => balance]
        
        // Merge balances into the student array
        foreach($students as &$student) {
            $student['balance'] = (float)($balances_map[$student['id']] ?? 0.0);
        }
        unset($student); // Important to unset the reference
    }
    $classDetails['students'] = $students;


    // --- 3. Calculate Financial Summary for the Class (Most Robust Method) ---
    $stmt_summary = $pdo->prepare("
        SELECT
            COALESCE(SUM(i.total_amount), 0) AS totalInvoiced,
            COALESCE(SUM(i.amount_paid), 0) AS totalPaid
        FROM invoices i
        WHERE i.school_id = ?
        AND i.student_id IN (SELECT id FROM students WHERE class_id = ? AND school_id = ?)
    ");
    $stmt_summary->execute([$school_id, $class_id, $school_id]);
    $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);

    $totalInvoiced = (float)($summary['totalInvoiced'] ?? 0);
    $totalPaid = (float)($summary['totalPaid'] ?? 0);
    $balance = $totalInvoiced - $totalPaid;

    $classDetails['summary'] = [
        'totalInvoiced' => $totalInvoiced,
        'totalPaid' => $totalPaid,
        'balance' => $balance
    ];

    // --- 4. Success Response ---
    echo json_encode(['success' => true, 'data' => $classDetails]);

} catch (PDOException $e) {
    error_log("Database Error in get_class_details.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'A database error occurred. Please try again later.']);
} catch (Exception $e) {
    error_log("General Error in get_class_details.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred.']);
}
?>