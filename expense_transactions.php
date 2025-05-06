<?php
// transactions.php - For retrieving transaction data

session_start();
require 'config.php';
require 'functions.php';

// Get transaction history for specific account if requested
if (isset($_GET['account_id'])) {
    $accountId = (int)$_GET['account_id'];
    
    $query = "
        SELECT 
            t.id, 
            t.transaction_date, 
            t.description, 
            t.amount, 
            t.type,
            a.account_code,
            a.account_name,
            t.transaction_type
        FROM 
            transactions t
        JOIN 
            accounts a ON t.account_id = a.id
        WHERE 
            t.account_id = :account_id
        ORDER BY 
            t.transaction_date DESC, 
            t.id DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'transactions' => $transactions]);
    exit;
}

// Filter transactions based on parameters
if (isset($_GET['filter'])) {
    $whereConditions = [];
    $params = [];
    
    // Date range filter
    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "t.transaction_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "t.transaction_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    
    // Account filter
    if (!empty($_GET['account_code'])) {
        $whereConditions[] = "a.account_code = :account_code";
        $params[':account_code'] = $_GET['account_code'];
    }
    
    // Transaction type filter
    if (!empty($_GET['transaction_type'])) {
        $whereConditions[] = "t.transaction_type = :transaction_type";
        $params[':transaction_type'] = $_GET['transaction_type'];
    }
    
    // Description search
    if (!empty($_GET['search'])) {
        $whereConditions[] = "t.description LIKE :search";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    
    // Build where clause
    $where = '';
    if (!empty($whereConditions)) {
        $where = "WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Query with filters
    $query = "
        SELECT 
            t.id, 
            t.transaction_date, 
            t.description, 
            t.amount, 
            t.type,
            a.account_code,
            a.account_name,
            t.transaction_type
        FROM 
            transactions t
        JOIN 
            accounts a ON t.account_id = a.id
        $where
        ORDER BY 
            t.transaction_date DESC, 
            t.id DESC
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'transactions' => $transactions]);
    exit;
}

// If no specific request, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;