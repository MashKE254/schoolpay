<?php
// invoice_template.php - Reusable invoice template with school details
$standalone = $standalone ?? false;

if ($standalone) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    echo '<title>Invoice #' . ($invoice['id'] ?? '') . '</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .invoice-container { border: 1px solid #eee; padding: 30px; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; align-items: flex-start; }
        .school-info h2 { margin: 0; color: #000; }
        .school-info p { margin: 5px 0; }
        .invoice-title h1 { margin: 0; text-align: right; color: #888; text-transform: uppercase; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; background-color: #f9f9f9; padding: 20px; border-radius: 5px;}
        .info-section { flex: 1; }
        .info-section h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        tfoot td { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
    </style></head><body>';
}
?>

<div class="invoice-container">
    <div class="invoice-header">
        <div class="school-info">
            <?php if (!empty($invoice['school_logo_url'])): ?>
                <img src="<?= htmlspecialchars($invoice['school_logo_url']) ?>" alt="School Logo" style="max-width: 150px; max-height: 70px; margin-bottom: 10px;">
            <?php else: ?>
                <h2><?= htmlspecialchars($invoice['school_name'] ?? 'Your School') ?></h2>
            <?php endif; ?>
            <p><?= nl2br(htmlspecialchars($invoice['school_address'] ?? '')) ?></p>
            <p><?= htmlspecialchars($invoice['school_phone'] ?? '') ?></p>
            <p><?= htmlspecialchars($invoice['school_email'] ?? '') ?></p>
        </div>
        <div class="invoice-title">
            <h1>Invoice</h1>
            <p>#<?= htmlspecialchars($invoice['id'] ?? '') ?></p>
        </div>
    </div>
    
    <div class="invoice-info">
        <div class="info-section">
            <h3>Bill To</h3>
            <p>
                <?= htmlspecialchars($invoice['student_name']) ?><br>
                <?php if(!empty($invoice['student_address'])): ?>
                    <?= nl2br(htmlspecialchars($invoice['student_address'])) ?>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="info-section">
            <h3>Details</h3>
            <p>
                <strong>Date:</strong> <?= date('M d, Y', strtotime($invoice['invoice_date'])) ?><br>
                <strong>Due Date:</strong> <?= date('M d, Y', strtotime($invoice['due_date'])) ?><br>
                <strong>Status:</strong> <?= htmlspecialchars($invoice['status']) ?>
            </p>
        </div>
    </div>
    
    <div class="invoice-items">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['description'] ?? '') ?></td>
                        <td class="text-right"><?= $item['quantity'] ?></td>
                        <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                        <td class="text-right">$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                    <td class="text-right"><strong>$<?= number_format($invoice['total_amount'], 2) ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>Amount Paid:</strong></td>
                    <td class="text-right"><strong>$<?= number_format($total_paid, 2) ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right" style="font-size: 1.2em;"><strong>Balance Due:</strong></td>
                    <td class="text-right" style="font-size: 1.2em;"><strong>$<?= number_format($balance, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if (!empty($invoice['notes'])): ?>
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