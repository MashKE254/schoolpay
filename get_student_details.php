<?php
require 'config.php';
require 'functions.php'; // Assuming functions.php is available for any helpers

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid student ID.'];

if (isset($_GET['id'])) {
    $studentId = intval($_GET['id']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        // Get student's personal details from the students table
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $studentDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($studentDetail) {
            // Fetch all invoices for the student
            $stmt = $pdo->prepare("SELECT id, id AS invoice_number, invoice_date, due_date, total_amount, paid_amount, balance FROM invoices WHERE student_id = ?");
            $stmt->execute([$studentId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch all payments for the student
            $stmt = $pdo->prepare("SELECT p.id, p.payment_date AS date, p.amount, p.payment_method, p.memo FROM payments p WHERE p.student_id = ? ORDER BY p.payment_date ASC");
            $stmt->execute([$studentId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

            // Combine invoices and payments into a single transaction list
            $allTransactions = [];
            foreach ($invoices as $invoice) {
                $allTransactions[] = [
                    'id' => $invoice['id'],
                    'date' => $invoice['invoice_date'],
                    'type' => 'invoice',
                    'description' => 'Invoice #' . $invoice['invoice_number'],
                    'amount' => (float)$invoice['total_amount'],
                ];
            }
            foreach ($payments as $payment) {
                $allTransactions[] = [
                    'id' => $payment['id'],
                    'date' => $payment['date'],
                    'type' => 'payment',
                    'description' => 'Payment (' . ($payment['payment_method'] ?? 'N/A') . ')' . ($payment['memo'] ? ' - ' . htmlspecialchars($payment['memo']) : ''),
                    'amount' => (float)$payment['amount'],
                ];
            }
            
            // Sort the combined list by date, with the newest transactions first
            usort($allTransactions, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            // Calculate the financial summary
            $totalInvoiced = array_sum(array_column($invoices, 'total_amount'));
            $totalPaid = array_sum(array_column($payments, 'amount'));
            $studentBalance = $totalInvoiced - $totalPaid;
            
            // Prepare the complete JSON response for the front-end
            $response = [
                'success' => true,
                'student' => $studentDetail,
                'transactions' => $allTransactions,
                'summary' => [
                    'balance' => $studentBalance,
                    'totalInvoiced' => $totalInvoiced,
                    'totalPaid' => $totalPaid,
                ]
            ];
        } else {
            $response['message'] = 'Student not found.';
        }

    } catch (Exception $e) {
        $response['message'] = 'Error fetching student details: ' . $e->getMessage();
    }
}

echo json_encode($response);