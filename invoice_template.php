<?php
// invoice_template.php - Reusable invoice template
$standalone = $standalone ?? false;

if ($standalone) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    echo '<title>Invoice #' . $invoice['id'] . '</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-section { flex: 1; padding: 15px; }
        .info-section h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #eee; text-align: left; }
        th { background: #f5f5f5; }
        tfoot td { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .child-item { background-color: #f9f9f9; }
        .child-indent { padding-left: 25px; }
    </style></head><body>';
}
?>

<div class="invoice-details">
    <?php if (!$standalone): ?>
        <div class="invoice-header">
            <h2>Invoice #<?= $invoice['id'] ?></h2>
        </div>
    <?php endif; ?>
    
    <div class="invoice-info">
        <div class="info-section">
            <h3>Bill To</h3>
            <p>
                <?= htmlspecialchars($invoice['student_name']) ?><br>
                Student ID: <?= htmlspecialchars($invoice['student_id']) ?><br>
                Email: <?= htmlspecialchars($invoice['email']) ?><br>
                Phone: <?= htmlspecialchars($invoice['phone']) ?><br>
                Address: <?= htmlspecialchars($invoice['address']) ?>
            </p>
        </div>
        
        <div class="info-section">
            <h3>Invoice Details</h3>
            <p>
                Date: <?= date('M d, Y', strtotime($invoice['invoice_date'])) ?><br>
                Due Date: <?= date('M d, Y', strtotime($invoice['due_date'])) ?><br>
                Status: <?= $invoice['status'] ?><br>
                Total Amount: $<?= number_format($invoice['total_amount'], 2) ?><br>
                Paid Amount: $<?= number_format($total_paid, 2) ?><br>
                Balance Due: $<?= number_format($balance, 2) ?>
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
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['description'] ?? '') ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['unit_price'], 2) ?></td>
                        <td>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                    </tr>
                    
                    <?php if (!empty($childItems[$parentId])): ?>
                        <?php foreach ($childItems[$parentId] as $childItem): ?>
                            <tr class="child-item">
                                <td class="child-indent">- <?= htmlspecialchars($childItem['item_name']) ?></td>
                                <td><?= htmlspecialchars($childItem['description'] ?? '') ?></td>
                                <td><?= $childItem['quantity'] ?></td>
                                <td>$<?= number_format($childItem['unit_price'], 2) ?></td>
                                <td>$<?= number_format($childItem['quantity'] * $childItem['unit_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                    <td><strong>$<?= number_format($invoice['total_amount'], 2) ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>Amount Paid:</strong></td>
                    <td><strong>$<?= number_format($total_paid, 2) ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>Balance Due:</strong></td>
                    <td><strong>$<?= number_format($balance, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if ($invoice['notes']): ?>
        <div class="invoice-notes">
            <h3>Notes</h3>
            <p><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<?php
if ($standalone) {
    echo '</body></html>';
}
?>