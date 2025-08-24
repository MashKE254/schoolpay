<?php
// generate_statements.php - Professional Bulk & Single Statement Generator
require_once 'config.php';
require_once 'functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    die("Authentication required.");
}

$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// --- Get Data from Form ---
$statement_type = $_POST['statement_type'] ?? 'all';
$class_id = $_POST['class_id'] ?? null;
$student_id_single = $_POST['student_id'] ?? null;
$date_from = $_POST['date_from'] ?? date('Y-m-01');
$date_to = $_POST['date_to'] ?? date('Y-m-d');

// --- Fetch School Details for Header ---
$stmt_school = $pdo->prepare("SELECT s.name, sd.* FROM schools s LEFT JOIN school_details sd ON s.id = sd.school_id WHERE s.id = ?");
$stmt_school->execute([$school_id]);
$school_details = $stmt_school->fetch(PDO::FETCH_ASSOC);


// --- Determine Which Students to Process ---
$student_ids_to_process = [];
if ($statement_type === 'class' && !empty($class_id)) {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
    $stmt->execute([$class_id, $school_id]);
    $student_ids_to_process = $stmt->fetchAll(PDO::FETCH_COLUMN);
} elseif ($statement_type === 'student' && !empty($student_id_single)) {
    // New logic for a single student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ? AND status = 'active'");
    $stmt->execute([$student_id_single, $school_id]);
    $student_ids_to_process = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else { // Default to 'all'
    $stmt = $pdo->prepare("SELECT id FROM students WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$school_id]);
    $student_ids_to_process = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($student_ids_to_process)) {
    die("No active students found for the selected criteria. <a href='customer_center.php?tab=statements'>Go Back</a>");
}

// --- Start HTML Output ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Statements</title>
    <style>
        :root {
            --statement-primary-color: #3b82f6; /* blue-500 */
            --statement-text-color: #374151; /* gray-700 */
            --statement-light-text: #6b7280; /* gray-500 */
            --statement-border-color: #e5e7eb; /* gray-200 */
            --statement-background-color: #f9fafb; /* gray-50 */
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            font-size: 10pt; 
            color: var(--statement-text-color);
            background-color: #f3f4f6;
        }
        .statement-container {
            background-color: #fff;
            margin: 2rem auto;
            padding: 2.5rem;
            max-width: 800px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            border-radius: 0.5rem;
            page-break-after: always;
        }
        .statement-container:last-child {
             page-break-after: auto;
        }
        .statement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--statement-border-color);
        }
        .school-info img { max-width: 150px; max-height: 70px; margin-bottom: 1rem; }
        .school-info h2 { font-size: 1.25rem; font-weight: 700; color: var(--statement-primary-color); margin: 0; }
        .school-info p { margin: 0.25rem 0; line-height: 1.5; color: var(--statement-light-text); }
        .statement-meta { text-align: right; }
        .statement-meta h1 { font-size: 1.875rem; font-weight: 800; margin: 0 0 0.5rem 0; text-transform: uppercase; color: #111827; }
        .statement-meta p { margin: 0.25rem 0; color: var(--statement-light-text); }
        
        .billing-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .billing-info h4 { font-size: 0.75rem; font-weight: 600; color: var(--statement-light-text); margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.05em; }
        .billing-info p { font-weight: 500; margin: 0; line-height: 1.6; }

        .summary-section {
            background-color: var(--statement-background-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2.5rem;
            text-align: center;
        }
        .summary-section h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--statement-light-text);
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
        }
        .summary-section .balance-amount {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--statement-primary-color);
        }
         .summary-section .balance-amount.negative {
            color: #ef4444; /* red-500 */
        }
        
        .transactions-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .transactions-table th, .transactions-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--statement-border-color); }
        .transactions-table thead { background-color: var(--statement-background-color); }
        .transactions-table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--statement-light-text); }
        .transactions-table .text-right { text-align: right; }
        .transactions-table tbody tr:last-child td { border-bottom: none; }
        .balance-row td { font-weight: bold; border-top: 2px solid var(--statement-text-color); }
        
        .statement-footer {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--statement-border-color);
            text-align: center;
            font-size: 0.875rem;
            color: var(--statement-light-text);
        }
        @media print {
            body { background-color: #fff; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .statement-container { margin: 0; box-shadow: none; border: none; border-radius: 0; padding: 0.5in; }
        }
    </style>
</head>
<body>

<?php foreach ($student_ids_to_process as $student_id): ?>
    <?php
    // --- For each student, get their data ---
    $student = getStudentById($pdo, $student_id, $school_id);
    
    // 1. Calculate Opening Balance
    $stmt_ob_inv = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE student_id = ? AND school_id = ? AND invoice_date < ?");
    $stmt_ob_inv->execute([$student_id, $school_id, $date_from]);
    $total_invoiced_before = $stmt_ob_inv->fetchColumn();

    $stmt_ob_pay = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE student_id = ? AND school_id = ? AND payment_date < ?");
    $stmt_ob_pay->execute([$student_id, $school_id, $date_from]);
    $total_paid_before = $stmt_ob_pay->fetchColumn();
    
    $opening_balance = $total_invoiced_before - $total_paid_before;

    // 2. Get Transactions for the period
    $stmt_inv = $pdo->prepare("SELECT id, invoice_date AS date, total_amount, 'Invoice' AS type FROM invoices WHERE student_id = ? AND school_id = ? AND invoice_date BETWEEN ? AND ?");
    $stmt_inv->execute([$student_id, $school_id, $date_from, $date_to]);
    $invoices_period = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

    $stmt_pay = $pdo->prepare("
        SELECT payments.id, payments.payment_date AS date, payments.amount, 'Payment' AS type, payments.memo, payment_receipts.receipt_number 
        FROM payments 
        LEFT JOIN payment_receipts ON payments.receipt_id = payment_receipts.id 
        WHERE payments.student_id = ? AND payments.school_id = ? AND payments.payment_date BETWEEN ? AND ?
    ");
    $stmt_pay->execute([$student_id, $school_id, $date_from, $date_to]);
    $payments_period = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);

    $transactions = array_merge($invoices_period, $payments_period);
    usort($transactions, fn($a, $b) => strcmp($a['date'], $b['date']));
    
    $running_balance = $opening_balance;
    ?>

    <div class="statement-container">
        <header class="statement-header">
            <div class="school-info">
                <?php if (!empty($school_details['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($school_details['logo_url']) ?>" alt="School Logo">
                <?php endif; ?>
                <h2><?= htmlspecialchars($school_details['name']) ?></h2>
                <p><?= nl2br(htmlspecialchars($school_details['address'])) ?></p>
                <p><?= htmlspecialchars($school_details['phone']) ?> | <?= htmlspecialchars($school_details['email']) ?></p>
            </div>
            <div class="statement-meta">
                <h1>Statement</h1>
                <p><strong>Date:</strong> <?= date('F j, Y') ?></p>
                <p><strong>Account:</strong> <?= htmlspecialchars($student['student_id_no'] ?? $student['id']) ?></p>
            </div>
        </header>

        <section class="billing-info">
            <div>
                <h4>Statement For</h4>
                <p><strong><?= htmlspecialchars($student['name']) ?></strong></p>
                <p><?= nl2br(htmlspecialchars($student['address'])) ?></p>
            </div>
             <div>
                <h4>Statement Period</h4>
                <p><?= date('F j, Y', strtotime($date_from)) ?></p>
                <p>to <?= date('F j, Y', strtotime($date_to)) ?></p>
            </div>
        </section>

        <section class="summary-section">
            <h4>Amount Due</h4>
            <div class="balance-amount <?= ($running_balance < 0) ? 'negative' : '' ?>">
                $<?= number_format(end($transactions) ? ($opening_balance + array_reduce($transactions, function($carry, $tx){ return $carry + ($tx['type'] === 'Invoice' ? $tx['total_amount'] : -$tx['amount']); }, 0)) : $opening_balance, 2) ?>
            </div>
        </section>

        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-right">Charges</th>
                    <th class="text-right">Payments</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4"><strong>Balance forward from <?= date('F j, Y', strtotime($date_from . ' -1 day')) ?></strong></td>
                    <td class="text-right"><strong><?= number_format($opening_balance, 2) ?></strong></td>
                </tr>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem;">No transactions in this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $charge = 0;
                        $payment = 0;
                        $description = '';

                        if ($tx['type'] === 'Invoice') {
                            $charge = $tx['total_amount'];
                            $running_balance += $charge;
                            $description = 'Invoice #' . $tx['id'];
                        } else {
                            $payment = $tx['amount'];
                            $running_balance -= $payment;
                            $description = 'Payment Received';
                            if(!empty($tx['receipt_number'])) {
                                $description .= ' (Ref: ' . htmlspecialchars($tx['receipt_number']) . ')';
                            }
                        }
                        ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($tx['date'])) ?></td>
                            <td><?= $description ?></td>
                            <td class="text-right"><?= $charge > 0 ? number_format($charge, 2) : '' ?></td>
                            <td class="text-right"><?= $payment > 0 ? number_format($payment, 2) : '' ?></td>
                            <td class="text-right"><?= number_format($running_balance, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="balance-row">
                    <td colspan="4">Closing Balance as of <?= date('M d, Y', strtotime($date_to)) ?></td>
                    <td class="text-right"><?= number_format($running_balance, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <footer class="statement-footer">
            <p>Please contact the finance office with any questions regarding this statement. Thank you!</p>
        </footer>
    </div>
<?php endforeach; ?>

</body>
</html>
