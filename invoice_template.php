<?php
// invoice_template.php - v3.1 (With layout fixes)
?>
<div class="invoice-container">
    <div class="invoice-header">
        <div class="school-details">
            <?php if (!empty($invoice['school_logo_url'])): ?>
                <img src="<?= htmlspecialchars($invoice['school_logo_url']) ?>" alt="School Logo" style="max-width: 150px; max-height: 70px; margin-bottom: 10px;">
            <?php else: ?>
                <h2><?= htmlspecialchars($invoice['school_name'] ?? 'Your School') ?></h2>
            <?php endif; ?>
            <p><?= nl2br(htmlspecialchars($invoice['school_address'] ?? '')) ?></p>
        </div>
        <div class="invoice-meta">
            <h1>INVOICE</h1>
            <p><strong>Invoice #:</strong> <?= htmlspecialchars($invoice['id'] ?? '') ?></p>
            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($invoice['invoice_date'])) ?></p>
            <p><strong>Due Date:</strong> <?= date('F j, Y', strtotime($invoice['due_date'])) ?></p>
        </div>
    </div>

    <div class="billing-details">
        <div class="bill-to">
            <h3>Bill To</h3>
            <p>
                <?= htmlspecialchars($invoice['student_name']) ?><br>
                <?php if(!empty($invoice['class_name'])): ?>
                    <strong>Grade:</strong> <?= htmlspecialchars($invoice['class_name']) ?><br>
                <?php endif; ?>
                <?= nl2br(htmlspecialchars($invoice['student_address'] ?? '')) ?>
            </p>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th class="text-right" style="width: 15%;">Quantity</th>
                <th class="text-right" style="width: 15%;">Unit Price</th>
                <th class="text-right" style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice['items'] as $item): ?>
                <tr>
                    <td class="item-name"><?= htmlspecialchars($item['parent_item_name'] ?? $item['item_name']) ?></td>
                    <td class="text-right"><?= $item['quantity'] ?></td>
                    <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-right">$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-wrapper">
            <div class="total-line sub">
                <div class="label">Subtotal:</div>
                <div class="value">$<?= number_format($invoice['total_amount'], 2) ?></div>
            </div>
            <div class="total-line sub">
                <div class="label">Amount Paid:</div>
                <div class="value">-$<?= number_format($total_paid, 2) ?></div>
            </div>
            <div class="total-line grand-total">
                <div class="label">Balance Due:</div>
                <div class="value">$<?= number_format($balance, 2) ?></div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <?php if (!empty($invoice['notes'])): ?>
            <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
        <?php endif; ?>
        <h4>Payment Information</h4>
        <p>Kindly make all payments to the school's bank account or via the designated PayBill number.<br>Thank you for your business!</p>
    </div>
</div>