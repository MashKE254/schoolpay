<?php
require 'config.php';

$response = ['totalBalance' => 0, 'totalPaid' => 0];

try {
    $stmt = $pdo->query("SELECT SUM(total_amount - amount_paid) AS totalBalance, SUM(amount_paid) AS totalPaid FROM invoices");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['totalBalance'] = $result['totalBalance'] ?? 0;
    $response['totalPaid'] = $result['totalPaid'] ?? 0;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
