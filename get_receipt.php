<?php
require 'config.php';

$receipt_id = intval($_GET['id'] ?? 0);
$response = ['success' => false];

if ($receipt_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, s.name AS student_name 
            FROM payment_receipts r
            JOIN students s ON s.id = r.student_id
            WHERE r.id = ?
        ");
        $stmt->execute([$receipt_id]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($receipt) {
            $response = [
                'success' => true,
                'receipt' => $receipt
            ];
        } else {
            $response['error'] = 'Receipt not found';
        }
    } catch (PDOException $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid receipt ID';
}

header('Content-Type: application/json');
echo json_encode($response);