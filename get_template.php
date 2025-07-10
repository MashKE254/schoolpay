<?php
require_once 'config.php';
require_once 'functions.php';
session_start();

header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$school_id = $_SESSION['school_id'];
$templateId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$templateId) {
    echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
    exit;
}

// Prepare and execute the query, ensuring the template belongs to the correct school
$stmt = $pdo->prepare("SELECT * FROM invoice_templates WHERE id = ? AND school_id = ?");
$stmt->execute([$templateId, $school_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    echo json_encode(['success' => false, 'error' => 'Template not found or you do not have permission to access it.']);
    exit;
}

$items = json_decode($template['items'], true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Failed to parse template items.']);
    exit;
}

echo json_encode([
    'success' => true,
    'items' => $items
]);
?>