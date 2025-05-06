<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statement_date = $_POST['statement_date'];
    $student_ids = $_POST['student_ids'] ?? [];
    $show_invoices = isset($_POST['show_invoices']);
    $show_payments = isset($_POST['show_payments']);
    $show_balances = isset($_POST['show_balances']);

    $statements = [];
    foreach ($student_ids as $student_id) {
        $stmt = $pdo->prepare("
            SELECT 
                s.name AS student_name, 
                t.date, 
                t.description, 
                t.amount_invoiced, 
                t.amount_paid, 
                (t.amount_invoiced - t.amount_paid) AS balance 
            FROM students s
            LEFT JOIN transactions t ON s.id = t.student_id
            WHERE s.id = ? AND t.date <= ?
            ORDER BY t.date
        ");
        $stmt->execute([$student_id, $statement_date]);
        $statements[] = [
            'student_name' => $stmt->fetchColumn(),
            'transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    // Render statements (for simplicity, output as JSON)
    header('Content-Type: application/json');
    echo json_encode($statements);
}
?>
