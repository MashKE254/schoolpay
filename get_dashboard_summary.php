<?php
// get_dashboard_summary.php

require 'config.php';
require 'functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
$school_id = $_SESSION['school_id'];

// --- Define the date range ---
$period = $_GET['period'] ?? '6m';
$end_date = new DateTime();
switch ($period) {
    case '30d':
        $start_date = (new DateTime())->sub(new DateInterval('P30D'));
        break;
    case '90d':
        $start_date = (new DateTime())->sub(new DateInterval('P90D'));
        break;
    case '1y':
        $start_date = (new DateTime())->sub(new DateInterval('P1Y'));
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? new DateTime($_GET['start_date']) : new DateTime();
        $end_date = isset($_GET['end_date']) ? new DateTime($_GET['end_date']) : new DateTime();
        break;
    case '6m':
    default:
        $start_date = (new DateTime())->sub(new DateInterval('P6M'));
        break;
}
$start_date_str = $start_date->format('Y-m-d');
$end_date_str = $end_date->format('Y-m-d');

$summary = [];

// --- Total Paid (from Payments) ---
$stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE school_id = ? AND payment_date BETWEEN ? AND ?");
$stmt->execute([$school_id, $start_date_str, $end_date_str]);
$summary['total_paid'] = $stmt->fetchColumn() ?: 0;

// --- Total Expected (from Invoices) ---
$stmt = $pdo->prepare("SELECT SUM(total_amount) as total_expected FROM invoices WHERE school_id = ? AND issue_date BETWEEN ? AND ?");
$stmt->execute([$school_id, $start_date_str, $end_date_str]);
$summary['total_expected'] = $stmt->fetchColumn() ?: 0;

// --- Total Out (Expenses) ---
$stmt = $pdo->prepare("
    SELECT SUM(t.debit) as total_out
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.school_id = ?
      AND a.account_type = 'expense'
      AND t.transaction_date BETWEEN ? AND ?
");
$stmt->execute([$school_id, $start_date_str, $end_date_str]);
$summary['total_out'] = $stmt->fetchColumn() ?: 0;


// --- Existing Summaries (Students and Invoices) ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
$stmt->execute([$school_id]);
$summary['total_students'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE school_id = ? AND status = 'unpaid'");
$stmt->execute([$school_id]);
$summary['unpaid_invoices'] = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode($summary);

?>