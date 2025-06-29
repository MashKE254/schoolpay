<?php
require 'config.php';
require 'functions.php';

$startDate = $_GET['from'] ?? date('Y-01-01');
$endDate = $_GET['to'] ?? date('Y-m-d');

$data = getIncomeByItem($pdo, $startDate, $endDate);

header('Content-Type: application/json');
echo json_encode($data);