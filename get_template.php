<?php
require 'config.php';

$templateId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$templateId) {
    echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM invoice_templates WHERE id = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    echo json_encode(['success' => false, 'error' => 'Template not found']);
    exit;
}

// Decode items JSON
$items = json_decode($template['items'], true);

echo json_encode([
    'success' => true,
    'items' => $items
]);