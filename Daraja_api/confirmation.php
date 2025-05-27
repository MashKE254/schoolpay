<?php
// confirmation.php
$config = require __DIR__ . '/config.php';
$body   = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        $config['db']['dsn'],
        $config['db']['username'],
        $config['db']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->beginTransaction();

    // Extract fields
    $studentNumber = $body['BillRefNumber'];
    $amount        = $body['TransAmount'];
    $receipt       = $body['TransID'];
    $msisdn        = $body['MSISDN'];
    $timestamp     = $body['TransTime']; // e.g. 20250527123045

    // Find student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
    $stmt->execute([$studentNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        throw new RuntimeException("Student not found in confirmation.");
    }

    // Determine which invoice to pay (e.g. latest unpaid)
    $stmt = $pdo->prepare("
      SELECT id 
      FROM invoices
      WHERE student_id = ? 
        AND total_amount > paid_amount
      ORDER BY invoice_date ASC
      LIMIT 1
    ");
    $stmt->execute([$student['id']]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inv) {
        throw new RuntimeException("No open invoice to apply payment.");
    }

    // Insert payment
    $stmt = $pdo->prepare("
      INSERT INTO payments 
        (invoice_id, student_id, payment_date, amount, payment_method, memo, receipt)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    // format timestamp to MySQL DATETIME: YYYYMMDDhhmmss → YYYY‑MM‑DD hh:mm:ss
    $dt = DateTime::createFromFormat('YmdHis', $timestamp);
    $pdate = $dt->format('Y-m-d H:i:s');

    $stmt->execute([
      $inv['id'],
      $student['id'],
      $pdate,
      $amount,
      'mpesa_c2b',
      'Daraja C2B sandbox',
      $receipt
    ]);

    // trigger will update invoices.paid_amount for us
    $pdo->commit();

    // Ack back to Safaricom
    echo json_encode([
      "ResultCode" => 0,
      "ResultDesc" => "Confirmation received successfully"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
      "ResultCode" => 2,
      "ResultDesc" => "Confirmation error: " . $e->getMessage()
    ]);
}
file_put_contents(__DIR__.'/mpesa.log',
  "[".date('c')."] " . file_get_contents('php://input') . "\n",
  FILE_APPEND
);