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
 * - Fetching a complete list of students (id, name, status) associated with that class.
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
    // Prepare a statement to get all students belonging to this class, ordered by name.
    $stmt_students = $pdo->prepare("SELECT id, name, status FROM students WHERE class_id = :class_id AND school_id = :school_id ORDER BY name ASC");
    $stmt_students->execute([
        ':class_id' => $class_id,
        ':school_id' => $school_id
    ]);
    // Add the list of students as a nested array in the response data.
    $classDetails['students'] = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. Calculate Financial Summary for the Class (Most Robust Method) ---
    // This query uses a subquery to be resilient. It correctly handles classes
    // with no students or no invoices by summing amounts for students that
    // are found within the specified class. This guarantees a single summary row is always returned.
    $stmt_summary = $pdo->prepare("
        SELECT
            COALESCE(SUM(i.total_amount), 0) AS totalInvoiced,
            COALESCE(SUM(i.paid_amount), 0) AS totalPaid
        FROM invoices i
        WHERE i.school_id = :school_id
        AND i.student_id IN (SELECT id FROM students WHERE class_id = :class_id AND school_id = :school_id)
    ");
    $stmt_summary->execute([
        ':class_id' => $class_id,
        ':school_id' => $school_id
    ]);
    $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);

    // Defensive check (now highly unlikely to be needed, but good practice)
    if (!$summary) {
        $summary = ['totalInvoiced' => 0, 'totalPaid' => 0];
    }

    // Calculate the outstanding balance and ensure all financial values are floats for accurate JSON representation.
    $totalInvoiced = (float) $summary['totalInvoiced'];
    $totalPaid = (float) $summary['totalPaid'];
    $balance = $totalInvoiced - $totalPaid;

    // Add the summary object to the response data, which the JavaScript expects for display.
    $classDetails['summary'] = [
        'totalInvoiced' => $totalInvoiced,
        'totalPaid' => $totalPaid,
        'balance' => $balance
    ];

    // --- 4. Success Response ---
    // Return the combined data (details, students, and summary) as a single JSON object.
    echo json_encode(['success' => true, 'data' => $classDetails]);

} catch (PDOException $e) {
    // --- Database Error Handling ---
    // Log the detailed error for debugging purposes and return a generic error message to the user.
    error_log("Database Error in get_class_details.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'A database error occurred. Please try again later.']);
} catch (Exception $e) {
    // --- General Error Handling ---
    // Catch any other unexpected exceptions.
    error_log("General Error in get_class_details.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred.']);
}

?>
