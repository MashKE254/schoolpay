<?php
require 'config.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($account);
}