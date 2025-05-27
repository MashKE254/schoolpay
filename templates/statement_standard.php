<!DOCTYPE html>
<html>
<head>
    <style>
        .statement-header { text-align: center; margin-bottom: 20px; }
        .student-info { margin-bottom: 15px; }
        .transaction-table { width: 100%; border-collapse: collapse; }
        .transaction-table th, .transaction-table td { padding: 8px; border: 1px solid #ddd; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <div class="statement-header">
        <h2>Account Statement</h2>
        <p>Period: <?= date('M d, Y', strtotime($dateFrom)) ?> - <?= date('M d, Y', strtotime($dateTo)) ?></p>
    </div>

    <?php foreach ($filteredStudents as $student): ?>
    <div class="student-statement">
        <div class="student-info">
            <h3><?= htmlspecialchars($student['name']) ?></h3>
            <p>Student ID: <?= $student['id'] ?></p>
        </div>

        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <?php if ($includeOverdue): ?>
                    <th>Days Overdue</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($student['transactions'] as $trans): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($trans['date'])) ?></td>
                    <td><?= ucfirst($trans['type']) ?></td>
                    <td>
                        <?php if ($trans['type'] === 'invoice'): ?>
                            Invoice #<?= $trans['invoice_id'] ?>
                        <?php else: ?>
                            Payment (<?= $trans['payment_method'] ?>)
                        <?php endif; ?>
                    </td>
                    <td>$<?= number_format($trans['amount'], 2) ?></td>
                    <?php if ($includeOverdue): ?>
                    <td>
                        <?php if ($trans['type'] === 'invoice' && $trans['balance'] > 0): 
                            $dueDate = new DateTime($trans['date']);
                            $today = new DateTime();
                            echo $today->diff($dueDate)->format('%a');
                        endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <p>Balance: $<?= number_format($student['balance'], 2) ?></p>
        </div>
        
        <?php if ($includeMessage): ?>
        <div class="statement-message">
            <p><?= nl2br(htmlspecialchars($statementMessage)) ?></p>
        </div>
        <?php endif; ?>
        
        <div style="page-break-after: always;"></div>
    </div>
    <?php endforeach; ?>
</body>
</html>