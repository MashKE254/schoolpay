<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'header.php'; // This handles session and sets $school_id

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$invoice = $invoice_id ? getInvoiceDetails($pdo, $invoice_id, $school_id) : null;

if (!$invoice) {
    echo "<div class='container'><div class='alert alert-danger'>Invoice not found or you do not have permission to view it.</div></div>";
    include 'footer.php';
    exit;
}

// Calculate balance
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND school_id = ?");
$stmt->execute([$invoice_id, $school_id]);
$total_paid = $stmt->fetchColumn();
$balance = ($invoice['total_amount'] ?? 0) - $total_paid;
$invoice['balance_due'] = $balance;
$invoice['total_paid'] = $total_paid;

// Determine invoice status based on balance and due date
$status = 'Draft';
$status_class = 'draft';
if ($balance <= 0) {
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

    .invoice-container {
        max-width: 960px;
        margin: 2rem auto;
        background-color: #fff;
        border: 1px solid var(--invoice-border-color);
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        color: var(--invoice-text-color);
    }

    .invoice-page-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid var(--invoice-border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .invoice-page-header h1 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }

    .invoice-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .invoice-body {
        padding: 2.5rem;
    }

    .invoice-header-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2.5rem;
        flex-wrap: wrap;
        gap: 2rem;
    }
    
    .school-info h2 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--invoice-primary-color);
        margin: 0;
    }

    .school-info p, .student-info p {
        margin: 0.25rem 0;
        line-height: 1.6;
        color: var(--invoice-light-text);
    }

    .invoice-meta {
        text-align: right;
    }

    .invoice-meta h3 {
        font-size: 1.875rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: var(--invoice-text-color);
    }

    .invoice-status {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .invoice-status.paid { background-color: var(--status-paid-bg); color: var(--status-paid-text); }
    .invoice-status.overdue { background-color: var(--status-overdue-bg); color: var(--status-overdue-text); }
    .invoice-status.sent { background-color: var(--status-sent-bg); color: var(--status-sent-text); }
    
    .billing-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin-bottom: 2.5rem;
        padding: 1.5rem;
        background-color: var(--invoice-background-color);
        border-radius: 0.5rem;
    }
    
    .billing-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--invoice-light-text);
        margin: 0 0 0.5rem 0;
        text-transform: uppercase;
    }
    
    .billing-info p {
        font-weight: 500;
        margin: 0;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2.5rem;
    }
    .items-table th, .items-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--invoice-border-color);
    }
    .items-table thead {
        background-color: var(--invoice-background-color);
    }
    .items-table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--invoice-light-text);
    }
    .items-table .text-right { text-align: right; }
    .items-table .item-name { font-weight: 600; }
    .items-table .item-description { font-size: 0.875rem; color: var(--invoice-light-text); }

    .invoice-summary {
        display: flex;
        justify-content: flex-end;
    }
    .summary-box {
        width: 100%;
        max-width: 320px;
    }
    .summary-line {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        color: var(--invoice-light-text);
    }
    .summary-line.total {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--invoice-text-color);
        border-top: 2px solid var(--invoice-border-color);
        margin-top: 0.5rem;
    }
    .summary-line.balance-due {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--invoice-primary-color);
        background-color: #eff6ff; /* blue-100 */
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .invoice-footer {
        margin-top: 2.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--invoice-border-color);
        font-size: 0.875rem;
        color: var(--invoice-light-text);
    }
    .invoice-footer h5 {
        font-weight: 600;
        color: var(--invoice-text-color);
        margin: 0 0 0.5rem 0;
    }

    @media print {
        body {
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-container {
            margin: 0;
            border: none;
            box-shadow: none;
            max-width: 100%;
        }
        .invoice-page-header {
            display: none;
        }
        .invoice-body {
            padding: 0;
        }
    }
</style>

<div class="invoice-container">
    <div class="invoice-page-header">
        <h1>Invoice #<?php echo htmlspecialchars($invoice['id']); ?></h1>
        <div class="invoice-actions">
            <a href="customer_center.php?tab=invoices" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <?php if ($balance > 0): ?>
                <a href="customer_center.php?tab=receive_payment&student_id=<?= $invoice['student_id'] ?>" class="btn btn-success">
                    <i class="fas fa-dollar-sign"></i> Receive Payment
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="invoice-body">
        <header class="invoice-header-section">
            <div class="school-info">
                <h2><?php echo htmlspecialchars($invoice['school_name']); ?></h2>
            </div>
            <div class="invoice-meta">
                <h3>INVOICE</h3>
                <h2>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>
                <div class="invoice-status <?php echo $status_class; ?>">
                    <?php echo $status; ?>
                </div>
            </div>
        </header>

        <section class="billing-info">
            <div>
                <h4>Bill To</h4>
                <p><strong><?php echo htmlspecialchars($invoice['student_name']); ?></strong></p>
                <p><?php echo nl2br(htmlspecialchars($invoice['student_address'])); ?></p>
            </div>
            <?php if (!empty($invoice['class_name'])): ?>
            <div>
                <h4>Class</h4>
                <p><strong><?php echo htmlspecialchars($invoice['class_name']); ?></strong></p>
            </div>
            <?php endif; ?>
            <div>
                <h4>Invoice Date</h4>
                <p><?php echo date('F j, Y', strtotime($invoice['invoice_date'])); ?></p>
            </div>
            <div>
                <h4>Due Date</h4>
                <p><?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></p>
            </div>
            <div>
                <h4>Total Amount</h4>
                <p style="font-size: 1.25rem; font-weight: 700;">$<?php echo number_format($invoice['total_amount'], 2); ?></p>
            </div>
        </section>

        <section>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $item): ?>
                    <tr>
                        <td>
                            <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                        </td>
                        <td class="text-right"><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="invoice-summary">
            <div class="summary-box">
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
                <div class="summary-line">
                    <span>Total Paid</span>
                    <span>-$<?php echo number_format($invoice['total_paid'], 2); ?></span>
                </div>
                <div class="summary-line balance-due">
                    <span>Balance Due</span>
                    <span>$<?php echo number_format($invoice['balance_due'], 2); ?></span>
                </div>
            </div>
        </section>

        <?php if (!empty($invoice['notes'])): ?>
        <footer class="invoice-footer">
            <h5>Notes</h5>
            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </footer>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>