<?php
// expense_transactions.php - For retrieving comprehensive general ledger data with filters

session_start();
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');

// Ensure user is logged in and has a school ID
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}
$school_id = $_SESSION['school_id'];

// --- Build the Filter Conditions ---
$whereConditions = ["e.school_id = :school_id"]; // Start with the mandatory school_id filter
$params = [':school_id' => $school_id];

// Date range filter
if (!empty($_GET['date_from'])) {
    $whereConditions[] = "e.transaction_date >= :date_from";
    $params[':date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $whereConditions[] = "e.transaction_date <= :date_to";
    $params[':date_to'] = $_GET['date_to'];
}

// Account filter (using account ID for precision)
if (!empty($_GET['account_id'])) {
    $whereConditions[] = "e.account_id = :account_id";
    $params[':account_id'] = $_GET['account_id'];
}

// Transaction type filter
if (!empty($_GET['transaction_type'])) {
    $whereConditions[] = "e.transaction_type = :transaction_type";
    $params[':transaction_type'] = $_GET['transaction_type'];
}

// Description search
if (!empty($_GET['search'])) {
    $whereConditions[] = "e.description LIKE :search";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);


// --- Query 1: Fetch the list of all matching transactions ---
$query_transactions = "
    SELECT 
        e.id, e.transaction_date, e.description, e.amount, 
        a.id as account_id, a.account_code, a.account_name, 
        e.transaction_type, e.receipt_image_url
    FROM expenses e
    JOIN accounts a ON e.account_id = a.id
    {$whereClause}
    ORDER BY e.transaction_date DESC, e.id DESC
";
$stmt = $pdo->prepare($query_transactions);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- Query 2: Calculate the summary totals for the same filtered period ---
$query_summary = "
    SELECT 
        COALESCE(SUM(CASE WHEN e.transaction_type = 'debit' THEN e.amount ELSE 0 END), 0) as total_debits,
        COALESCE(SUM(CASE WHEN e.transaction_type = 'credit' THEN e.amount ELSE 0 END), 0) as total_credits
    FROM expenses e
    {$whereClause}
";
$stmt_summary = $pdo->prepare($query_summary);
$stmt_summary->execute($params);
$summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);


// --- Return all data as a single JSON response ---
echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'summary' => [
        'total_debits' => (float)($summary['total_debits'] ?? 0),
        'total_credits' => (float)($summary['total_credits'] ?? 0)
    ]
]);
exit;