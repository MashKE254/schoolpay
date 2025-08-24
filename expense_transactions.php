<?php
// expense_transactions.php - For retrieving comprehensive general ledger data with filters

session_start();
require 'config.php';
require 'functions.php';

// Ensure user is logged in and has a school ID
if (!isset($_SESSION['school_id'])) {
    header('Content-Type: application/json');
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


// --- Query 2: Calculate the specific "Total Expenses" for the same period ---
$query_expenses = "
    SELECT COALESCE(SUM(e.amount), 0)
    FROM expenses e
    JOIN accounts a ON e.account_id = a.id
    {$whereClause} AND a.account_type = 'expense' AND e.transaction_type = 'debit'
";
$stmt_expenses = $pdo->prepare($query_expenses);
$stmt_expenses->execute($params);
$total_expenses = $stmt_expenses->fetchColumn();


// --- Return all data as a single JSON response ---
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'summary' => [
        'total_expenses' => (float)$total_expenses
    ]
]);
exit;