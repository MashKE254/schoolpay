<?php
// validation.php
$config = require __DIR__ . '/config.php';
$body   = json_decode(file_get_contents('php://input'), true);

$studentNumber = $body['BillRefNumber'] ?? '';
header('Content-Type: application/json');

try {
    $pdo = new PDO(
        $config['db']['dsn'],
        $config['db']['username'],
        $config['db']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check that the student exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
    $stmt->execute([$studentNumber]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "ResultCode" => 1,
            "ResultDesc" => "Student number not found"
        ]);
        exit;
    }

    // Accepted
    echo json_encode([
        "ResultCode" => 0,
        "ResultDesc" => "Accepted"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ResultCode" => 2,
        "ResultDesc" => "Validation error: " . $e->getMessage()
    ]);
}
file_put_contents(__DIR__.'/mpesa.log',
  "[".date('c')."] " . file_get_contents('php://input') . "\n",
  FILE_APPEND
);