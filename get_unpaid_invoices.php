<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode(['error' => 'Student ID is required']);
    exit;
}

$student_id = intval($_GET['student_id']);
$invoices = getUnpaidInvoices($pdo, $student_id);

echo json_encode($invoices); 