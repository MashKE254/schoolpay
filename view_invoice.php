<?php
require_once 'config.php';
require_once 'functions.php';

// --- NEW: Token-based Public Access Logic ---
$token = $_GET['token'] ?? null;
$invoice = null;
$school_id_from_token = null;

if ($token) {
    // A token is provided, try to fetch the invoice publicly
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE token = ?");
    $stmt->execute([$token]);
    $invoice_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice_data) {
        $invoice_id = $invoice_data['id'];
        // This is the key fix: get the school_id directly from the initial query
        $school_id_from_token = $invoice_data['school_id']; 
        // Now use the existing function to get all details
        $invoice = getInvoiceDetails($pdo, $invoice_id, $school_id_from_token);
    }
} else {
    // --- Fallback to Original Session-based Logic ---
    session_start();
    if (isset($_SESSION['school_id'])) {
        $school_id_from_session = $_SESSION['school_id'];
        $invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($invoice_id) {
            $invoice = getInvoiceDetails($pdo, $invoice_id, $school_id_from_session);
        }
    }
}

// If no invoice was found by either method, exit.
if (!$invoice) {
    // Don't output a full header/footer if it's a likely public link access
    if (!$token) {
       require_once 'header.php';
    }
    echo "<div class='container' style='padding: 2rem;'><div class='alert alert-danger'>Invoice not found or the link is invalid.</div></div>";
    if (!$token) {
       require_once 'footer.php';
    }
    exit;
}

// Now we are sure we have an invoice, let's ensure we have the correct school_id
$current_school_id = $invoice['school_id'];

// If accessed via token, we don't need the header's session checks
if (!$token) {
    require_once 'header.php';
}

// Recalculate financial details for display
$stmt_paid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND school_id = ?");
$stmt_paid->execute([$invoice['id'], $current_school_id]);
$total_paid = $stmt_paid->fetchColumn();
$balance = $invoice['total_amount'] - $total_paid;
$invoice['balance_due'] = $balance;
$invoice['total_paid'] = $total_paid;

// Determine status for display
$status = 'Draft';
$status_class = 'draft';
if ($balance <= 0.01) {
    $status = 'Paid';
    $status_class = 'paid';
} elseif (date('Y-m-d') > $invoice['due_date']) {
    $status = 'Overdue';
    $status_class = 'overdue';
} else {
    $status = 'Sent';
    $status_class = 'sent';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link rel="stylesheet" href="styles.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
         :root {
            --invoice-primary-color: #3b82f6; /* blue-500 */
            --invoice-text-color: #374151; /* gray-700 */
            --invoice-light-text: #6b7280; /* gray-500 */
            --invoice-border-color: #e5e7eb; /* gray-200 */
            --invoice-background-color: #f9fafb; /* gray-50 */
            --status-paid-bg: #dcfce7; /* green-100 */
            --status-paid-text: #166534; /* green-800 */
            --status-overdue-bg: #fee2e2; /* red-100 */
            --status-overdue-text: #991b1b; /* red-800 */
            --status-sent-bg: #e0f2fe; /* sky-100 */
            --status-sent-text: #075985; /* sky-800 */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            max-width: 960px;
            margin: 2rem auto;
            background-color: #fff;
            border: 1px solid var(--invoice-border-color);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            color: var(--invoice-text-color);
        }
        .invoice-body { padding: 2.5rem; }
        .invoice-header-section { display: flex; justify-content: space-between; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 2rem; }
        .school-info h2 { font-size: 1.25rem; font-weight: 700; color: var(--invoice-primary-color); margin: 0; }
        .school-info p, .student-info p { margin: 0.25rem 0; line-height: 1.6; color: var(--invoice-light-text); }
        .invoice-meta { text-align: right; }
        .invoice-meta h3 { font-size: 1.875rem; font-weight: 700; margin: 0 0 0.5rem 0; color: var(--invoice-text-color); }
        .invoice-status { display: inline-block; padding: 0.375rem 0.875rem; border-radius: 9999px; font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .invoice-status.paid { background-color: var(--status-paid-bg); color: var(--status-paid-text); }
        .invoice-status.overdue { background-color: var(--status-overdue-bg); color: var(--status-overdue-text); }
        .invoice-status.sent { background-color: var(--status-sent-bg); color: var(--status-sent-text); }
        .billing-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 2.5rem; padding: 1.5rem; background-color: var(--invoice-background-color); border-radius: 0.5rem; }
        .billing-info h4 { font-size: 0.875rem; font-weight: 600; color: var(--invoice-light-text); margin: 0 0 0.5rem 0; text-transform: uppercase; }
        .billing-info p { font-weight: 500; margin: 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 2.5rem; }
        .items-table th, .items-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--invoice-border-color); }
        .items-table thead { background-color: var(--invoice-background-color); }
        .items-table th { font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--invoice-light-text); }
        .items-table .text-right { text-align: right; }
        .invoice-summary { display: flex; justify-content: flex-end; }
        .summary-box { width: 100%; max-width: 320px; }
        .summary-line { display: flex; justify-content: space-between; padding: 0.75rem 0; }
        .summary-line.total { font-size: 1.25rem; font-weight: 700; color: var(--invoice-text-color); border-top: 2px solid var(--invoice-border-color); margin-top: 0.5rem; }
        .summary-line.balance-due { font-size: 1.5rem; font-weight: 700; color: var(--invoice-primary-color); background-color: #eff6ff; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; }
        
        @media print {
            body { background-color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .invoice-container { margin: 0; border: none; box-shadow: none; max-width: 100%; }
        }

        /* Responsive styles for mobile */
        @media (max-width: 768px) {
            .invoice-body { padding: 1.5rem; }
            .invoice-header-section { flex-direction: column; align-items: flex-start; }
            .invoice-meta { text-align: left; margin-top: 1rem; }
            .billing-info { grid-template-columns: 1fr; gap: 1rem; }
            .invoice-summary { justify-content: center; }
            .summary-box { max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="invoice-container">
    <div class="invoice-body">
         <div class="invoice-header-section">
            <div class="school-info"><h2><?php echo htmlspecialchars($invoice['school_name']); ?></h2></div>
            <div class="invoice-meta">
                <h3>INVOICE</h3>
                <h2>#<?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>
                <div class="invoice-status <?php echo $status_class; ?>"><?php echo $status; ?></div>
            </div>
        </div>

        <section class="billing-info">
            <div><h4>Bill To</h4><p><strong><?php echo htmlspecialchars($invoice['student_name']); ?></strong></p><p><?php echo nl2br(htmlspecialchars($invoice['student_address'])); ?></p></div>
            <?php if (!empty($invoice['class_name'])): ?><div><h4>Class</h4><p><strong><?php echo htmlspecialchars($invoice['class_name']); ?></strong></p></div><?php endif; ?>
            <div><h4>Invoice Date</h4><p><?php echo date('F j, Y', strtotime($invoice['invoice_date'])); ?></p></div>
            <div><h4>Due Date</h4><p><?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></p></div>
        </section>

        <section>
            <div style="overflow-x: auto;">
                <table class="items-table">
                    <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td><div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div></td>
                            <td class="text-right"><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-right">$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="invoice-summary">
            <div class="summary-box">
                <div class="summary-line"><span>Subtotal</span><span>$<?php echo number_format($invoice['total_amount'], 2); ?></span></div>
                <div class="summary-line"><span>Total Paid</span><span>-$<?php echo number_format($invoice['total_paid'], 2); ?></span></div>
                <div class="summary-line balance-due"><span>Balance Due</span><span>$<?php echo number_format($invoice['balance_due'], 2); ?></span></div>
            </div>
        </section>
    </div>
</div>
<?php if (!$token): include 'footer.php'; endif; // Only show footer if logged in ?>
</body>
</html>