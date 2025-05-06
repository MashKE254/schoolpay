<?php
require 'config.php';

if (!isset($_GET['student_id'])) {
    echo json_encode([]);
    exit;
}

$student_id = intval($_GET['student_id']);
$stmt = $pdo->prepare("
    SELECT 
        t.date, 
        t.description, 
        t.amount_invoiced, 
        t.amount_paid, 
        (t.amount_invoiced - t.amount_paid) AS balance 
    FROM transactions t 
    WHERE t.student_id = ?
    ORDER BY t.date DESC
");
$stmt->execute([$student_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
