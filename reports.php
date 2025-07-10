<?php
// reports.php - Multi-Tenant Reports Page (Complete & Enhanced)
require 'config.php';
require 'functions.php';
include 'header.php'; // Handles session and sets $school_id

// Set default date range for reports, allowing for overrides from GET parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('first day of this year'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// --- Data Fetching for All Reports ---
$plData = getDetailedPLData($pdo, $startDate, $endDate, $school_id);
$incomeByCustomer = getIncomeByCustomer($pdo, $startDate, $endDate, $school_id);
$balanceSheetData = getBalanceSheetData($pdo, $school_id);
$openInvoices = getOpenInvoicesReport($pdo, $school_id);
$incomeByCategory = getIncomeByCategory($pdo, $startDate, $endDate, $school_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Reports</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
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
        <button class="tab-link active" onclick="openTab(event, 'plReport')">Profit & Loss</button>
        <button class="tab-link" onclick="openTab(event, 'incomeReport')">Income by Customer</button>
        <button class="tab-link" onclick="openTab(event, 'balanceSheet')">Balance Sheet</button>
        <button class="tab-link" onclick="openTab(event, 'openInvoices')">Open Invoices</button>
        <button class="tab-link" onclick="openTab(event, 'incomeByItem')">Income by Category</button>
    </div>
  
    <!-- Profit & Loss Report Tab -->
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
                <!-- Revenue Section -->
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

                <!-- Expenses Section -->
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
                
                <!-- Net Income Section -->
                <div class="pl-net-income <?= $plData['net_income'] < 0 ? 'loss' : '' ?>">
                    <span>Net Income</span>
                    <span class="amount">$<?= number_format($plData['net_income'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
  
    <!-- Income by Customer Report Tab -->
    <div id="incomeReport" class="tab-content">
        <div class="card">
            <h3>Income by Customer</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>Student</th><th class="amount-header">Total Payments</th><th>Last Payment</th></tr></thead>
                    <tbody>
                        <?php foreach($incomeByCustomer as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td class="amount">$<?= number_format($row['total_payments'], 2) ?></td>
                            <td><?= !empty($row['last_payment']) ? date('M d, Y', strtotime($row['last_payment'])) : 'N/A' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  
    <!-- Balance Sheet Report Tab -->
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
  
    <!-- Open Invoices Report Tab -->
    <div id="openInvoices" class="tab-content">
        <div class="card">
            <h3>Open Invoices</h3>
            <div class="table-container">
                <table id="openInvoicesTable">
                    <thead><tr><th>Student</th><th>Invoice #</th><th>Issue Date</th><th>Due Date</th><th class="amount-header">Total</th><th class="amount-header">Paid</th><th class="amount-header">Balance</th></tr></thead>
                    <tbody>
                        <?php foreach($openInvoices as $invoice): ?>
                        <tr>
                            <td><?= htmlspecialchars($invoice['student_name']) ?></td>
                            <td><?= $invoice['id'] ?></td>
                            <td><?= date('M d, Y', strtotime($invoice['invoice_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($invoice['due_date'])) ?></td>
                            <td class="amount">$<?= number_format($invoice['total_amount'], 2) ?></td>
                            <td class="amount">$<?= number_format($invoice['paid_amount'], 2) ?></td>
                            <td class="amount">$<?= number_format($invoice['balance'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  
    <!-- Income by Category Tab -->
    <div id="incomeByItem" class="tab-content">
        <div class="card">
            <h3>Income by Category</h3>
            <div class="table-container">
                <table id="categoryTable">
                    <thead><tr><th>Category</th><th class="amount-header">Total Quantity</th><th class="amount-header">Total Income</th><th class="amount-header">Average Price</th></tr></thead>
                    <tbody>
                        <?php foreach($incomeByCategory as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['category_name']) ?></td>
                            <td class="amount"><?= number_format($category['total_quantity']) ?></td>
                            <td class="amount">$<?= number_format($category['total_income'], 2) ?></td>
                            <td class="amount">$<?= number_format($category['average_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
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
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// Ensure the correct tab is active on page load based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'plReport';
    const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
    if (tabButton) {
        tabButton.click();
    } else {
        // Fallback to the first tab if the specified one doesn't exist
        document.querySelector('.tab-link').click();
    }
});
</script>
</body>
</html>
