<?php
// create_item.php - Secure, Multi-Tenant AJAX endpoint for creating items
require_once 'config.php';
require_once 'functions.php';
session_start();

header('Content-Type: application/json');

// Security check: Ensure user is logged in and it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$school_id = $_SESSION['school_id'];
$name = trim($_POST['name'] ?? '');
$price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
$description = trim($_POST['description'] ?? '');
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$item_type = $parent_id ? 'child' : 'parent';

if (empty($name) || $price === false) {
    echo json_encode(['success' => false, 'message' => 'Item Name and a valid Price are required.']);
    exit();
}

try {
    $item_id = createItem($pdo, $school_id, $name, $price, $description, $parent_id, $item_type);
    
    // Fetch the newly created item to return its full details
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND school_id = ?");
    $stmt->execute([$item_id, $school_id]);
    $newItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($newItem) {
        echo json_encode(['success' => true, 'item' => $newItem]);
    } else {
        throw new Exception("Failed to retrieve the newly created item.");
    }

} catch (Exception $e) {
    // In a real app, you would log the error to a file
    error_log("Create Item Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred while creating the item.']);
}
?>