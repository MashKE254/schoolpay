<?php
// reports.php - Multi-Tenant Reports Page (Complete & Enhanced)
require 'config.php';
require 'functions.php';
include 'header.php'; // Handles session and sets $school_id

// Set default date range for reports, allowing for overrides from GET parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('first day of this year'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$payPeriod = $_GET['pay_period'] ?? date('Y-m');

// --- Data Fetching for All Reports ---

// 1. Existing Reports
$plData = getDetailedPLData($pdo, $startDate, $endDate, $school_id);
$incomeByCustomer = getIncomeByCustomer($pdo, $startDate, $endDate, $school_id);
$balanceSheetData = getBalanceSheetData($pdo, $school_id);
$openInvoices = getOpenInvoicesReport($pdo, $school_id);
$incomeByCategory = getIncomeByCategory($pdo, $startDate, $endDate, $school_id);

// 2. FIXED: A/R Aging Report Data Calculation
$arAgingData = [];
$arTotals = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, 'older' => 0, 'total' => 0];
$arStmt = $pdo->prepare("
    SELECT s.name as student_name, (i.total_amount - i.paid_amount) as balance, i.due_date
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    WHERE i.school_id = ? AND (i.total_amount - i.paid_amount) > 0.01
");
$arStmt->execute([$school_id]);

// Use DateTime objects for accurate and robust date calculations
$today = new DateTime();
$today->setTime(0, 0, 0); // Normalize to the start of the day

while ($row = $arStmt->fetch(PDO::FETCH_ASSOC)) {
    $dueDate = new DateTime($row['due_date']);
    $dueDate->setTime(0, 0, 0); // Normalize due date as well

    $studentName = $row['student_name'];
    if (!isset($arAgingData[$studentName])) {
        $arAgingData[$studentName] = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, 'older' => 0, 'total' => 0];
    }

    // Check if the invoice is overdue
    if ($dueDate >= $today) {
        $daysOverdue = 0;
        $arAgingData[$studentName]['current'] += $row['balance'];
    } else {
        $interval = $today->diff($dueDate);
        $daysOverdue = $interval->days;
        
        // Bucket the overdue amount
        if ($daysOverdue <= 30) {
            $arAgingData[$studentName]['30'] += $row['balance'];
        } elseif ($daysOverdue <= 60) {
            $arAgingData[$studentName]['60'] += $row['balance'];
        } elseif ($daysOverdue <= 90) {
            $arAgingData[$studentName]['90'] += $row['balance'];
        } else {
            $arAgingData[$studentName]['older'] += $row['balance'];
        }
    }
    
    $arAgingData[$studentName]['total'] += $row['balance'];
}
// Calculate column totals
foreach ($arAgingData as $data) {
    foreach ($data as $key => $value) $arTotals[$key] += $value;
}


// 3. NEW: Student Balance Report Data
$studentBalanceStmt = $pdo->prepare("
    SELECT s.name, s.phone, c.name as class_name, SUM(i.total_amount - i.paid_amount) as total_balance
    FROM students s
    JOIN invoices i ON s.id = i.student_id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.school_id = ? AND (i.total_amount - i.paid_amount) > 0.01
    GROUP BY s.id
    ORDER BY total_balance DESC
");
$studentBalanceStmt->execute([$school_id]);
$studentBalances = $studentBalanceStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. NEW: Payroll Summary Report Data
$payrollSummaryStmt = $pdo->prepare("
    SELECT
        COUNT(id) as employee_count,
        COALESCE(SUM(gross_pay), 0) as total_gross,
        COALESCE(SUM(total_deductions), 0) as total_deductions,
        COALESCE(SUM(net_pay), 0) as total_net
    FROM payroll
    WHERE school_id = ? AND pay_period = ?
");
$payrollSummaryStmt->execute([$school_id, $payPeriod]);
$payrollSummary = $payrollSummaryStmt->fetch(PDO::FETCH_ASSOC);

// 5. NEW: Statutory Deductions Report Data
$statutoryStmt = $pdo->prepare("
    SELECT employee_name, tax, insurance, retirement, other_deduction
    FROM payroll
    WHERE school_id = ? AND pay_period = ?
");
$statutoryStmt->execute([$school_id, $payPeriod]);
$statutoryData = $statutoryStmt->fetchAll(PDO::FETCH_ASSOC);
$statutoryTotals = [
    'tax' => array_sum(array_column($statutoryData, 'tax')),
    'insurance' => array_sum(array_column($statutoryData, 'insurance')),
    'retirement' => array_sum(array_column($statutoryData, 'retirement')),
    'other_deduction' => array_sum(array_column($statutoryData, 'other_deduction'))
];

// 6. NEW: Payment Promises Report Data
$promisesStmt = $pdo->prepare("
    SELECT pp.*, s.name as student_name
    FROM payment_promises pp
    JOIN students s ON pp.student_id = s.id
    WHERE pp.school_id = ? AND pp.status IN ('Pending', 'Broken')
    ORDER BY pp.promised_due_date ASC
");
$promisesStmt->execute([$school_id]);
$paymentPromises = $promisesStmt->fetchAll(PDO::FETCH_ASSOC);

// 7. NEW: Bank Deposit Summary
$depositStmt = $pdo->prepare("
    SELECT d.deposit_date, d.amount, d.memo, a.account_name
    FROM deposits d
    JOIN accounts a ON d.account_id = a.id
    WHERE d.school_id = ? AND d.deposit_date BETWEEN ? AND ?
    ORDER BY d.deposit_date DESC
");
$depositStmt->execute([$school_id, $startDate, $endDate]);
$depositSummary = $depositStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Reports</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .amount-header {
            text-align: right;
        }
        /* Styles for the Professional P&L Statement */
        .pl-statement {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
        }
        .pl-section {
            margin-bottom: 25px;
        }
        .pl-header {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--primary);
        }
        .pl-line-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid var(--border);
        }
        .pl-line-item .account-name {
            padding-left: 20px; /* Indent sub-items */
        }
        .pl-total-line {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            font-weight: 700;
            border-top: 2px solid var(--dark);
            border-bottom: 1px solid var(--dark);
            margin-top: 5px;
        }
        .pl-net-income {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--white);
            background: var(--primary);
            border-radius: var(--border-radius);
            margin-top: 20px;
        }
        .pl-net-income.loss {
            background: var(--danger);
        }
        /* Balance Sheet specific styles */
        .balance-section { margin-bottom: 25px; }
        .balance-section h4 { font-size: 1.2rem; color: var(--primary); margin-bottom: 10px; }
        .balance-section table { width: 100%; }
        .balance-section .total td { font-weight: 700; border-top: 2px solid var(--dark); }
        .balance-total { margin-top: 20px; border-top: 3px double var(--primary); padding-top: 10px; }
        .balance-total td { font-weight: 700; font-size: 1.1rem; }
    </style>
</head>
<body>
<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-chart-bar"></i> Financial Reports</h1>
        <p>Analyze your school's financial performance.</p>
    </div>
</div>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'plReport')"><i class="fas fa-chart-line"></i> P&L</button>
        <button class="tab-link" onclick="openTab(event, 'balanceSheet')"><i class="fas fa-balance-scale"></i> Balance Sheet</button>
        <button class="tab-link" onclick="openTab(event, 'arAging')"><i class="fas fa-clock"></i> A/R Aging</button>
        <button class="tab-link" onclick="openTab(event, 'studentBalances')"><i class="fas fa-user-graduate"></i> Student Balances</button>
        <button class="tab-link" onclick="openTab(event, 'payrollReports')"><i class="fas fa-money-check-alt"></i> Payroll</button>
        <button class="tab-link" onclick="openTab(event, 'depositSummary')"><i class="fas fa-university"></i> Deposits</button>
        <button class="tab-link" onclick="openTab(event, 'promisesReport')"><i class="fas fa-handshake"></i> Promises</button>
        <a href="budget.php" class="tab-link"><i class="fas fa-calculator"></i> Budget vs Actuals</a>
        <a href="expense_management.php?tab=ledger" class="tab-link"><i class="fas fa-book-open"></i> General Ledger</a>
    </div>
  
    <div id="plReport" class="tab-content active">
        <div class="card">
            <h3>Profit & Loss Statement</h3>
            
            <form method="GET" action="reports.php" class="filter-controls">
                <input type="hidden" name="tab" value="plReport">
                <div class="form-group">
                    <label for="plDateFrom">From:</label>
                    <input type="date" id="plDateFrom" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="form-group">
                    <label for="plDateTo">To:</label>
                    <input type="date" id="plDateTo" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>
            
            <div class="pl-statement">
                <div class="pl-section">
                    <div class="pl-header">Revenue</div>
                    <?php if (empty($plData['revenue']['accounts'])): ?>
                        <div class="pl-line-item"><span>No revenue recorded for this period.</span><span class="amount">$0.00</span></div>
                    <?php else: ?>
                        <?php foreach($plData['revenue']['accounts'] as $account): ?>
                        <div class="pl-line-item">
                            <span class="account-name"><?= htmlspecialchars($account['account_name']) ?></span>
                            <span class="amount">$<?= number_format($account['total'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="pl-total-line">
                        <span>Total Revenue</span>
                        <span class="amount">$<?= number_format($plData['revenue']['total'], 2) ?></span>
                    </div>
                </div>

                <div class="pl-section">
                    <div class="pl-header">Expenses</div>
                     <?php if (empty($plData['expense']['accounts'])): ?>
                        <div class="pl-line-item"><span>No expenses recorded for this period.</span><span class="amount">$0.00</span></div>
                    <?php else: ?>
                        <?php foreach($plData['expense']['accounts'] as $account): ?>
                        <div class="pl-line-item">
                            <span class="account-name"><?= htmlspecialchars($account['account_name']) ?></span>
                            <span class="amount">$<?= number_format($account['total'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="pl-total-line">
                        <span>Total Expenses</span>
                        <span class="amount">$<?= number_format($plData['expense']['total'], 2) ?></span>
                    </div>
                </div>
                
                <div class="pl-net-income <?= $plData['net_income'] < 0 ? 'loss' : '' ?>">
                    <span>Net Income</span>
                    <span class="amount">$<?= number_format($plData['net_income'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div id="balanceSheet" class="tab-content">
        <div class="card">
            <h3>Balance Sheet</h3>
            <div class="report-period">As of: <?= date('F d, Y') ?></div>
            <div class="balance-section">
                <h4>Assets</h4>
                <table><tbody>
                    <?php foreach($balanceSheetData['assets'] as $asset): ?>
                    <tr><td><?= htmlspecialchars($asset['account_name']) ?></td><td class="amount">$<?= number_format($asset['balance'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="total"><td>Total Assets</td><td class="amount">$<?= number_format($balanceSheetData['total_assets'], 2) ?></td></tr>
                </tbody></table>
            </div>
            <div class="balance-section">
                <h4>Liabilities</h4>
                <table><tbody>
                    <?php foreach($balanceSheetData['liabilities'] as $liability): ?>
                    <tr><td><?= htmlspecialchars($liability['account_name']) ?></td><td class="amount">$<?= number_format($liability['balance'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="total"><td>Total Liabilities</td><td class="amount">$<?= number_format($balanceSheetData['total_liabilities'], 2) ?></td></tr>
                </tbody></table>
            </div>
            <div class="balance-section">
                <h4>Equity</h4>
                <table><tbody>
                    <?php foreach($balanceSheetData['equity'] as $equity): ?>
                    <tr><td><?= htmlspecialchars($equity['account_name']) ?></td><td class="amount">$<?= number_format($equity['balance'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td>Retained Earnings</td><td class="amount">$<?= number_format($balanceSheetData['retained_earnings'], 2) ?></td></tr>
                    <tr class="total"><td>Total Equity</td><td class="amount">$<?= number_format($balanceSheetData['total_equity'], 2) ?></td></tr>
                </tbody></table>
            </div>
            <div class="balance-total">
                <table><tbody><tr><td>Total Liabilities + Equity</td><td class="amount">$<?= number_format($balanceSheetData['total_liabilities_equity'], 2) ?></td></tr></tbody></table>
            </div>
        </div>
    </div>
  
    <div id="arAging" class="tab-content">
        <div class="card">
            <h3>Accounts Receivable Aging Summary</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th class="amount-header">Current</th>
                            <th class="amount-header">1-30 Days</th>
                            <th class="amount-header">31-60 Days</th>
                            <th class="amount-header">61-90 Days</th>
                            <th class="amount-header">90+ Days</th>
                            <th class="amount-header">Total Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($arAgingData as $student => $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($student) ?></td>
                            <td class="amount">$<?= number_format($data['current'], 2) ?></td>
                            <td class="amount">$<?= number_format($data['30'], 2) ?></td>
                            <td class="amount">$<?= number_format($data['60'], 2) ?></td>
                            <td class="amount">$<?= number_format($data['90'], 2) ?></td>
                            <td class="amount">$<?= number_format($data['older'], 2) ?></td>
                            <td class="amount"><strong>$<?= number_format($data['total'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>$<?= number_format($arTotals['current'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($arTotals['30'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($arTotals['60'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($arTotals['90'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($arTotals['older'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($arTotals['total'], 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="studentBalances" class="tab-content">
        <div class="card">
            <h3>Student Balance Report</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Phone Number</th>
                            <th class="amount-header">Total Outstanding Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($studentBalances as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                            <td class="amount">$<?= number_format($student['total_balance'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="payrollReports" class="tab-content">
        <div class="card">
            <h3>Payroll Reports</h3>
            <form method="GET" action="reports.php" class="filter-controls">
                <input type="hidden" name="tab" value="payrollReports">
                <div class="form-group">
                    <label for="pay_period">Pay Period:</label>
                    <input type="month" id="pay_period" name="pay_period" value="<?= htmlspecialchars($payPeriod) ?>">
                </div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>

            <h4>Payroll Summary for <?= date('F Y', strtotime($payPeriod . '-01')) ?></h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th class="amount-header">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Employees Paid</td><td class="amount"><?= $payrollSummary['employee_count'] ?></td></tr>
                        <tr><td>Total Gross Pay</td><td class="amount">$<?= number_format($payrollSummary['total_gross'], 2) ?></td></tr>
                        <tr><td>Total Deductions</td><td class="amount">$<?= number_format($payrollSummary['total_deductions'], 2) ?></td></tr>
                        <tr class="total-row"><td><strong>Total Net Pay</strong></td><td class="amount"><strong>$<?= number_format($payrollSummary['total_net'], 2) ?></strong></td></tr>
                    </tbody>
                </table>
            </div>

            <h4 style="margin-top: 2rem;">Statutory Deductions Report for <?= date('F Y', strtotime($payPeriod . '-01')) ?></h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th class="amount-header">PAYE</th>
                            <th class="amount-header">NHIF</th>
                            <th class="amount-header">NSSF</th>
                            <th class="amount-header">Housing Levy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($statutoryData as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_name']) ?></td>
                            <td class="amount">$<?= number_format($row['tax'], 2) ?></td>
                            <td class="amount">$<?= number_format($row['insurance'], 2) ?></td>
                            <td class="amount">$<?= number_format($row['retirement'], 2) ?></td>
                            <td class="amount">$<?= number_format($row['other_deduction'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td><strong>TOTALS</strong></td>
                            <td class="amount"><strong>$<?= number_format($statutoryTotals['tax'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($statutoryTotals['insurance'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($statutoryTotals['retirement'], 2) ?></strong></td>
                            <td class="amount"><strong>$<?= number_format($statutoryTotals['other_deduction'], 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <div id="depositSummary" class="tab-content">
        <div class="card">
            <h3>Bank Deposit Summary</h3>
             <form method="GET" action="reports.php" class="filter-controls">
                <input type="hidden" name="tab" value="depositSummary">
                <div class="form-group">
                    <label for="depositDateFrom">From:</label>
                    <input type="date" id="depositDateFrom" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="form-group">
                    <label for="depositDateTo">To:</label>
                    <input type="date" id="depositDateTo" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Deposit Date</th>
                            <th>Bank Account</th>
                            <th>Memo / Details</th>
                            <th class="amount-header">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($depositSummary)): ?>
                            <tr><td colspan="4" class="text-center">No deposits found for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach($depositSummary as $deposit): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($deposit['deposit_date'])) ?></td>
                                <td><?= htmlspecialchars($deposit['account_name']) ?></td>
                                <td><?= htmlspecialchars($deposit['memo']) ?></td>
                                <td class="amount">$<?= number_format($deposit['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="promisesReport" class="tab-content">
        <div class="card">
            <h3>Payment Promises Report</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Invoice #</th>
                            <th>Promise Date</th>
                            <th>Promised Due Date</th>
                            <th class="amount-header">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paymentPromises)): ?>
                            <tr><td colspan="6" class="text-center">No pending or broken promises found.</td></tr>
                        <?php else: ?>
                            <?php foreach($paymentPromises as $promise): ?>
                            <tr>
                                <td><?= htmlspecialchars($promise['student_name']) ?></td>
                                <td><a href="view_invoice.php?id=<?= $promise['invoice_id'] ?>" target="_blank"><?= $promise['invoice_id'] ?></a></td>
                                <td><?= date('M d, Y', strtotime($promise['promise_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($promise['promised_due_date'])) ?></td>
                                <td class="amount">$<?= number_format($promise['promised_amount'], 2) ?></td>
                                <td><span class="badge badge-<?= strtolower($promise['status']) ?>"><?= htmlspecialchars($promise['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
// Tab navigation logic
function openTab(evt, tabName) {
    document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove('active'));
    document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove('active'));
    
    const tabElement = document.getElementById(tabName);
    if (tabElement) {
        tabElement.classList.add('active');
    }
    
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    }
}

// Ensure the correct tab is active on page load based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'plReport';
    const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`) || document.querySelector(`.tab-link[href*="${tab}"]`);
    if (tabButton) {
        // For buttons that are actual links, we don't need to simulate a click, just mark active.
        if (tabButton.tagName === 'A') {
            document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove('active'));
            tabButton.classList.add('active');
        } else {
            tabButton.click();
        }
    } else {
        // Fallback to the first tab if the specified one doesn't exist
        const firstButton = document.querySelector('.tab-link');
        if (firstButton) {
            firstButton.click();
        }
    }
});
</script>
</body>
</html>