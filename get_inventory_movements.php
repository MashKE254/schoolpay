<?php
// get_inventory_movements.php
// AJAX endpoint to fetch stock movement log

header('Content-Type: application/json');
require_once 'config.php';
require_once 'functions.php';

if (session_status() == PHP_SESSION_NONE) session_start();
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, i.name as item_name, u.name as user_name 
        FROM inventory_movements m
        JOIN inventory_items i ON m.item_id = i.id
        JOIN users u ON m.user_id = u.id
        WHERE m.school_id = ? 
        ORDER BY m.transaction_date DESC 
        LIMIT 100
    ");
    $stmt->execute([$school_id]);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'movements' => $movements]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}