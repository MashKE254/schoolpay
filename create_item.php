<?php
// create_item.php - Create new item with proper JSON response
header('Content-Type: application/json');
require 'config.php';
require 'functions.php';

try {
    // Validate inputs
    if (empty($_POST['name']) || !isset($_POST['price'])) {
        throw new Exception("Name and price are required");
    }
    
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $item_type = $_POST['item_type'] ?? 'item';
    
    // Create the item using the function from functions.php
    $result = createItem($pdo, $name, $price, $description, $parent_id, $item_type);
    
    echo json_encode([
        'success' => true,
        'message' => 'Item created successfully',
        'item_id' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>