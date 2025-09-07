<?php
// get_student_fees.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'functions.php'; // Ensure your functions can be accessed
session_start();

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];
$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$class_id_param = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT); // New: Get class_id directly
$academic_year = trim($_GET['academic_year'] ?? '');
$term = trim($_GET['term'] ?? '');

$class_id = null;

// **MODIFICATION START**: Determine the class_id to use
if ($class_id_param) {
    // If a class_id is passed directly, verify it belongs to the school and use it.
    $stmt_class_verify = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
    $stmt_class_verify->execute([$class_id_param, $school_id]);
    if ($stmt_class_verify->fetch()) {
        $class_id = $class_id_param;
    } else {
        echo json_encode(['success' => false, 'error' => 'Class not found or unauthorized.']);
        exit();
    }
} elseif ($student_id) {
    // If no class_id is passed, fall back to the original method of finding it via the student.
    $stmt_student = $pdo->prepare("SELECT class_id FROM students WHERE id = ? AND school_id = ?");
    $stmt_student->execute([$student_id, $school_id]);
    $class_id = $stmt_student->fetchColumn();
}
// **MODIFICATION END**

// Now, proceed with the determined $class_id
if (!$class_id || !$academic_year || !$term) {
    echo json_encode(['success' => false, 'error' => 'Missing required student/class, academic year, or term.']);
    exit();
}

try {
    // 1. Get all fee structure items for that class and term (No longer need to fetch student's class first)
    $stmt_fees = $pdo->prepare(
        "SELECT fsi.*, i.name as item_name 
         FROM fee_structure_items fsi
         JOIN items i ON fsi.item_id = i.id
         WHERE fsi.school_id = ? 
           AND fsi.class_id = ? 
           AND fsi.academic_year = ? 
           AND fsi.term = ?"
    );
    $stmt_fees->execute([$school_id, $class_id, $academic_year, $term]);
    $all_fees = $stmt_fees->fetchAll(PDO::FETCH_ASSOC);

    // 2. Separate into mandatory and optional fees (Logic remains the same)
    $mandatory_items = [];
    $optional_items = [];
    foreach ($all_fees as $fee) {
        if ($fee['is_mandatory']) {
            $mandatory_items[] = $fee;
        } else {
            $optional_items[] = $fee;
        }
    }

    echo json_encode([
        'success' => true,
        'mandatory_items' => $mandatory_items,
        'optional_items' => $optional_items
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>