<?php
// generate_statements.php - Professional Bulk & Single Statement Generator (with Public Link support)
require_once 'config.php';
require_once 'functions.php';

session_start();

$school_id = null;
$student_ids_to_process = [];

// --- NEW: Handle Public Access via Token (GET request) ---
$token = $_GET['token'] ?? null;
if ($token) {
    $stmt = $pdo->prepare("SELECT id, school_id FROM students WHERE token = ? AND status = 'active'");
    $stmt->execute([$token]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $school_id = $student['school_id'];
        $student_ids_to_process[] = $student['id'];
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
    }
} else {
    // --- Original Session-based Logic (POST request) ---
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
        die("Authentication required.");
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request method for internal use.");
    }
    
    $school_id = $_SESSION['school_id'];
    $statement_type = $_POST['statement_type'] ?? 'all';
    $class_id = $_POST['class_id'] ?? null;
    $student_id_single = $_POST['student_id'] ?? null;
    $date_from = $_POST['date_from'] ?? date('Y-m-01');
    $date_to = $_POST['date_to'] ?? date('Y-m-d');

    if ($statement_type === 'class' && !empty($class_id)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
        $stmt->execute([$class_id, $school_id]);
        $student_ids_to_process = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($statement_type === 'student' && !empty($student_id_single)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ? AND status = 'active'");
        $stmt->execute([$student_id_single, $school_id]);
        $student_ids_to_process = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE school_id = ? AND status = 'active'");
        $stmt->execute([$school_id]);
        $student_ids_to_process = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (empty($student_ids_to_process) || empty($school_id)) {
    die("No active students found or school could not be identified. <a href='customer_center.php?tab=statements'>Go Back</a>");
}

// Fetch School Details for Header
$stmt_school = $pdo->prepare("SELECT s.name, sd.* FROM schools s LEFT JOIN school_details sd ON s.id = sd.school_id WHERE s.id = ?");
$stmt_school->execute([$school_id]);
$school_details = $stmt_school->fetch(PDO::FETCH_ASSOC);

// --- Start HTML Output ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Statements</title>
    <style>
        :root { --statement-primary-color: #3b82f6; --statement-text-color: #374151; --statement-light-text: #6b7280; --statement-border-color: #e5e7eb; --statement-background-color: #f9fafb; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 10pt; color: var(--statement-text-color); background-color: #f3f4f6; }
        .statement-container { background-color: #fff; margin: 2rem auto; padding: 2.5rem; max-width: 800px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 0.5rem; page-break-after: always; }
        .statement-container:last-child { page-break-after: auto; }
        .statement-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--statement-border-color); }
        .school-info h2 { font-size: 1.25rem; font-weight: 700; color: var(--statement-primary-color); margin: 0; }
        .school-info p { margin: 0.25rem 0; color: var(--statement-light-text); }
        .statement-meta h1 { font-size: 1.875rem; font-weight: 800; margin: 0 0 0.5rem 0; color: #111827; }
        .billing-info { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem; }
        .billing-info h4 { font-size: 0.75rem; font-weight: 600; color: var(--statement-light-text); margin: 0 0 0.5rem 0; text-transform: uppercase; }
        .summary-section { background-color: var(--statement-background-color); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2.5rem; text-align: center; }
        .summary-section h4 { font-size: 0.875rem; color: var(--statement-light-text); margin: 0 0 0.5rem 0; }
        .summary-section .balance-amount { font-size: 2.25rem; font-weight: 800; color: var(--statement-primary-color); }
        .summary-section .balance-amount.negative { color: #ef4444; }
        .transactions-table { width: 100%; border-collapse: collapse; }
        .transactions-table th, .transactions-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--statement-border-color); }
        .transactions-table thead { background-color: var(--statement-background-color); }
        .transactions-table th { font-weight: 600; font-size: 0.75rem; color: var(--statement-light-text); }
        .transactions-table .text-right { text-align: right; }
        .balance-row td { font-weight: bold; border-top: 2px solid var(--statement-text-color); }
        .statement-footer { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--statement-border-color); text-align: center; color: var(--statement-light-text); }
        @media print {
            body { background-color: #fff; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .statement-container { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
<?php foreach ($student_ids_to_process as $student_id): ?>
    <?php
    $student = getStudentById($pdo, $student_id, $school_id);
    $stmt_ob_inv = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE student_id = ? AND school_id = ? AND invoice_date < ?");
    $stmt_ob_inv->execute([$student_id, $school_id, $date_from]);
    $total_invoiced_before = $stmt_ob_inv->fetchColumn();
    $stmt_ob_pay = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE student_id = ? AND school_id = ? AND payment_date < ?");
    $stmt_ob_pay->execute([$student_id, $school_id, $date_from]);
    $total_paid_before = $stmt_ob_pay->fetchColumn();
    $opening_balance = $total_invoiced_before - $total_paid_before;
    $stmt_inv = $pdo->prepare("SELECT id, invoice_date AS date, total_amount, 'Invoice' AS type FROM invoices WHERE student_id = ? AND school_id = ? AND invoice_date BETWEEN ? AND ?");
    $stmt_inv->execute([$student_id, $school_id, $date_from, $date_to]);
    $invoices_period = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);
    $stmt_pay = $pdo->prepare("SELECT p.id, p.payment_date AS date, p.amount, 'Payment' AS type, pr.receipt_number FROM payments p LEFT JOIN payment_receipts pr ON p.receipt_id = pr.id WHERE p.student_id = ? AND p.school_id = ? AND p.payment_date BETWEEN ? AND ?");
    $stmt_pay->execute([$student_id, $school_id, $date_from, $date_to]);
    $payments_period = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
    $transactions = array_merge($invoices_period, $payments_period);
    usort($transactions, fn($a, $b) => strcmp($a['date'], $b['date']));
    $closing_balance = $opening_balance + array_reduce($transactions, fn($carry, $tx) => $carry + ($tx['type'] === 'Invoice' ? $tx['total_amount'] : -$tx['amount']), 0);
    ?>
    <div class="statement-container">
        <header class="statement-header">
            <div class="school-info"><h2><?= htmlspecialchars($school_details['name']) ?></h2><p><?= nl2br(htmlspecialchars($school_details['address'])) ?></p></div>
            <div class="statement-meta"><h1>Statement</h1></div>
        </header>
        <section class="billing-info">
            <div><h4>Statement For</h4><p><strong><?= htmlspecialchars($student['name']) ?></strong></p></div>
             <div><h4>Statement Period</h4><p><?= date('F j, Y', strtotime($date_from)) ?> to <?= date('F j, Y', strtotime($date_to)) ?></p></div>
        </section>
        <section class="summary-section">
            <h4>Amount Due</h4>
            <div class="balance-amount <?= ($closing_balance < 0) ? 'negative' : '' ?>">$<?= number_format($closing_balance, 2) ?></div>
        </section>
        <table class="transactions-table">
            <thead><tr><th>Date</th><th>Description</th><th class="text-right">Charges</th><th class="text-right">Payments</th><th class="text-right">Balance</th></tr></thead>
            <tbody>
                <tr><td colspan="4"><strong>Balance forward</strong></td><td class="text-right"><strong><?= number_format($opening_balance, 2) ?></strong></td></tr>
                <?php $running_balance = $opening_balance; if(empty($transactions)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem;">No transactions in this period.</td></tr>
                <?php else: foreach ($transactions as $tx):
                    $charge = ($tx['type'] === 'Invoice') ? $tx['total_amount'] : 0;
                    $payment = ($tx['type'] === 'Payment') ? $tx['amount'] : 0;
                    $running_balance += $charge - $payment;
                    $description = $tx['type'] === 'Invoice' ? 'Invoice #' . $tx['id'] : 'Payment Received (Ref: ' . htmlspecialchars($tx['receipt_number'] ?? 'N/A') . ')';
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($tx['date'])) ?></td>
                    <td><?= $description ?></td>
                    <td class="text-right"><?= $charge > 0 ? number_format($charge, 2) : '' ?></td>
                    <td class="text-right"><?= $payment > 0 ? number_format($payment, 2) : '' ?></td>
                    <td class="text-right"><?= number_format($running_balance, 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                <tr class="balance-row"><td colspan="4">Closing Balance</td><td class="text-right"><?= number_format($running_balance, 2) ?></td></tr>
            </tbody>
        </table>
        <footer class="statement-footer"><p>Thank you for your prompt payment.</p></footer>
    </div>
<?php endforeach; ?>
</body>
</html>
