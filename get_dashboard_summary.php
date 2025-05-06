<?php
// get_dashboard_summary.php
require 'config.php';
require 'functions.php';
header('Content-Type: application/json');

try {
    // Ensure $pdo is initialized
    if (!isset($pdo)) {
        throw new Exception('Database connection not initialized.');
    }

    $summary = getDashboardSummary($pdo);

    // Ensure $summary is valid
    if ($summary === false) {
        throw new Exception('Failed to fetch dashboard summary.');
    }

    echo json_encode(['success' => true, 'data' => $summary]);
} catch (Exception $e) {
    // Handle errors gracefully
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}