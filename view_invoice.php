<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'header.php'; // This handles session and sets $school_id

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- FIX: Added $school_id to the function call ---
$invoice = $invoice_id ? getInvoiceDetails($pdo, $invoice_id, $school_id) : null;

if (!$invoice) {
    echo "<div class='alert alert-danger'>Invoice not found or you do not have permission to view it.</div>";
    include 'footer.php';
    exit;
}

// Calculate balance
// --- FIX: Added school_id to the query for security ---
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND school_id = ?");
$stmt->execute([$invoice_id, $school_id]);
$total_paid = $stmt->fetchColumn();
$balance = ($invoice['total_amount'] ?? 0) - $total_paid;

?>
<div class="container">
    <div class="invoice-header">
        <h2>Invoice #<?php echo $invoice['id']; ?></h2>
        <div class="invoice-actions">
            <a href="customer_center.php?tab=invoices" class="btn-secondary">Back to List</a>
            <?php if ($balance > 0): ?>
                <a href="customer_center.php?tab=receive_payment&student_id=<?= $invoice['student_id'] ?>" class="btn-success">
                    <i class="fas fa-dollar-sign"></i> Receive Payment
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
        </div>
    </div>

    <div class="invoice-details">
        <?php
            // We pass the calculated balance to the template
            $invoice['balance_due'] = $balance;
            include 'invoice_template.php';
        ?>
    </div>
</div>

<style>
.invoice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 1rem;}
.invoice-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.invoice-details { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

@media print {
    body > header, .container > .invoice-header, body > footer, .btn-secondary, .btn-success { display: none; }
    body > .container { padding: 0; }
    .invoice-details { box-shadow: none; border: 1px solid #ccc; }
}
</style>

<?php include 'footer.php'; ?>