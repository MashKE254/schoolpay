<?php
// ajax_create_class.php
require 'config.php';
session_start();

header('Content-Type: application/json');

// Basic security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$school_id = $_SESSION['school_id'];
$class_name = trim($_POST['class_name'] ?? '');

if (empty($class_name)) {
    echo json_encode(['success' => false, 'message' => 'Class name cannot be empty.']);
    exit();
}

try {
    // Check if class already exists for this school
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND school_id = ?");
    $stmt->execute([$class_name, $school_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This class name already exists.']);
        exit();
    }

    // Insert the new class
    $stmt = $pdo->prepare("INSERT INTO classes (school_id, name) VALUES (?, ?)");
    $stmt->execute([$school_id, $class_name]);
    $new_class_id = $pdo->lastInsertId();

    if ($new_class_id) {
        echo json_encode([
            'success' => true,
            'id' => $new_class_id,
            'name' => $class_name
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save the new class.']);
    }

} catch (PDOException $e) {
    // Log error for debugging
    error_log("AJAX Create Class Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}