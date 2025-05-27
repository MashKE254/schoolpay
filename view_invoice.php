<?php
require 'config.php';
require 'functions.php';
include 'header.php';

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$invoice = $invoice_id ? getInvoiceDetails($pdo, $invoice_id) : null;

if (!$invoice) {
    echo "<div class='alert alert-danger'>Invoice not found</div>";
    include 'footer.php';
    exit;
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recordPayment'])) {
    try {
        $payment_date = $_POST['payment_date'];
        $amount = floatval($_POST['amount']);
        $method = $_POST['method'];
        $memo = trim($_POST['memo']);
        
        // Record payment using database balance
        recordPayment($pdo, $invoice_id, $invoice['student_id'], $payment_date, $amount, $method, $memo);
        
        // Refresh to get updated balance
        header("Location: view_invoice.php?id=" . $invoice_id);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Calculate balance from database values
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) 
    FROM payments 
    WHERE invoice_id = ?
");
$stmt->execute([$invoice_id]);
$total_paid = $stmt->fetchColumn();

$balance = $invoice['total_amount'] - $total_paid;
?>

<div class="container">
    <div class="invoice-header">
        <h2>Invoice #<?php echo $invoice['id']; ?></h2>
        <div class="invoice-actions">
            <a href="customer_center.php" class="btn-secondary">Back to List</a>
            <button onclick="window.print()" class="btn-primary">Print Invoice</button>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="invoice-details">
        <div class="invoice-info">
            <div class="info-section">
                <h3>Bill To</h3>
                <p>
                    <?php echo htmlspecialchars($invoice['student_name']); ?><br>
                    Student ID: <?php echo htmlspecialchars($invoice['student_id']); ?><br>
                    Email: <?php echo htmlspecialchars($invoice['email']); ?><br>
                    Phone: <?php echo htmlspecialchars($invoice['phone']); ?><br>
                    Address: <?php echo htmlspecialchars($invoice['address']); ?>
                </p>
            </div>
            
            <div class="info-section">
                <h3>Invoice Details</h3>
                <p>
                    Date: <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?><br>
                    Due Date: <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?><br>
                    Status: <span class="status-<?php echo strtolower($invoice['status']); ?>"><?php echo $invoice['status']; ?></span><br>
                    Total Amount: $<?php echo number_format($invoice['total_amount'], 2); ?><br>
                    Paid Amount: $<?php echo number_format($invoice['paid_amount'], 2); ?><br>
                    Balance Due: $<?php echo number_format($balance, 2); ?>
                </p>
            </div>
        </div>
        
        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $parentItems = [];
                    $childItems = [];
                    
                    foreach ($invoice['items'] as $item) {
                        if (empty($item['parent_id'])) {
                            $parentItems[$item['id']] = $item;
                        } else {
                            $childItems[$item['parent_id']][] = $item;
                        }
                    }
                    
                    foreach ($parentItems as $parentId => $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        </tr>
                        
                        <?php if (!empty($childItems[$parentId])): ?>
                            <?php foreach ($childItems[$parentId] as $childItem): ?>
                                <tr class="child-item">
                                    <td class="child-indent">- <?php echo htmlspecialchars($childItem['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($childItem['description'] ?? ''); ?></td>
                                    <td><?php echo $childItem['quantity']; ?></td>
                                    <td>$<?php echo number_format($childItem['unit_price'], 2); ?></td>
                                    <td>$<?php echo number_format($childItem['quantity'] * $childItem['unit_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                        <td><strong>$<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Amount Paid:</strong></td>
                        <td><strong>$<?php echo number_format($invoice['paid_amount'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Balance Due:</strong></td>
                        <td><strong>$<?php echo number_format($balance, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if ($invoice['notes']): ?>
            <div class="invoice-notes">
                <h3>Notes</h3>
                <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($balance > 0): ?>
        <div class="payment-section">
            <h3>Record Payment</h3>
            <form method="post" class="payment-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" name="amount" id="amount" step="0.01" 
                               max="<?php echo $balance; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="method">Payment Method</label>
                        <select name="method" id="method" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Check">Check</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="memo">Memo</label>
                    <textarea name="memo" id="memo" rows="2"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="recordPayment" class="btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.invoice-actions {
    display: flex;
    gap: 10px;
}

.invoice-details {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.invoice-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.info-section {
    flex: 1;
    padding: 15px;
}

.info-section h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

.invoice-items table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.invoice-items th,
.invoice-items td {
    padding: 10px;
    border: 1px solid #eee;
    text-align: left;
}

.invoice-items th {
    background: #f5f5f5;
}

.invoice-items tfoot td {
    background: #f5f5f5;
    font-weight: bold;
}

.text-right {
    text-align: right;
}

.status-draft { color: #666; }
.status-sent { color: #2196F3; }
.status-paid { color: #4CAF50; }
.status-overdue { color: #f44336; }

.payment-section {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.child-item {
    background-color: #f9f9f9;
}

.child-indent {
    padding-left: 25px !important;
}

@media print {
    .invoice-actions,
    .payment-section {
        display: none;
    }
}
</style>

<?php include 'footer.php'; ?>