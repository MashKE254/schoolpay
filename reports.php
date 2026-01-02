<?php
/**
 * reports.php - v5.3 - Multi-Tenant Reports Page with Custom Report Builder
 *
 * This page provides a comprehensive overview of the school's financial health
 * through a series of standard reports and a powerful custom report generator.
 * This version adds the "Income by Item" report.
 */

// --- BLOCK 1: SETUP & PRE-PROCESSING ---
require 'config.php';
require 'functions.php';
include 'header.php'; // Handles session and sets $school_id

// --- BLOCK 2: Data Fetching for Standard & Custom Reports ---

// Set default date range for reports, allowing for overrides from GET parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('first day of this year'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$payPeriod = $_GET['pay_period'] ?? date('Y-m');

// --- Custom Report Generation ---
$customReportData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_custom_report'])) {
    try {
        // Pass the entire POST array to the function for processing
        $customReportData = generateCustomReport($pdo, $school_id, $_POST);
    } catch (Exception $e) {
        $customReportError = "Error generating custom report: " . $e->getMessage();
    }
}

// --- Export Custom Report to CSV ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_custom_report'])) {
    $reportDataJson = $_POST['report_data_json'] ?? '[]';
    $reportData = json_decode($reportDataJson, true);

    if (!empty($reportData) && isset($reportData['headers']) && isset($reportData['data'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="custom_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $reportData['headers']);
        foreach ($reportData['data'] as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// --- Data Fetching for All Standard Reports ---
$plData = getDetailedPLData($pdo, $startDate, $endDate, $school_id);
$balanceSheetData = getBalanceSheetData($pdo, $school_id);
$arAgingData = getArAgingData($pdo, $school_id);
$studentBalances = getStudentBalanceReport($pdo, $school_id);
$payrollSummary = getPayrollSummary($pdo, $school_id, $payPeriod);
$statutoryData = getStatutoryDeductionsReport($pdo, $school_id, $payPeriod);

// --- NEW: Data Fetching & Processing for Income by Item Report ---
$incomeByItemDataRaw = getIncomeByItemAndClass($pdo, $school_id, $startDate, $endDate);
$incomeByItemAndClass = [];
foreach ($incomeByItemDataRaw as $row) {
    $className = $row['class_name'] ?? 'Unassigned / No Class';
    $incomeByItemAndClass[$className][] = [
        'item_name' => $row['item_name'],
        'total_income' => $row['total_income']
    ];
}
// --- END NEW ---

// *** THIS IS THE FIX: Calculate totals using the new column names ***
$statutoryTotals = [
    'paye' => array_sum(array_column($statutoryData, 'paye')),
    'nhif' => array_sum(array_column($statutoryData, 'nhif')),
    'nssf' => array_sum(array_column($statutoryData, 'nssf')),
    'housing_levy' => array_sum(array_column($statutoryData, 'housing_levy'))
];
// *** END OF FIX ***

$paymentPromises = getPaymentPromisesReport($pdo, $school_id);
$depositSummary = getDepositSummary($pdo, $school_id, $startDate, $endDate);
$all_students_for_dropdown = getStudents($pdo, $school_id, null, null, 'active');
$all_classes_for_dropdown = getClasses($pdo, $school_id);

// Calculate A/R Aging totals
$arTotals = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, 'older' => 0, 'total' => 0];
foreach ($arAgingData as $data) {
    foreach ($data as $key => $value) $arTotals[$key] += $value;
}
?>
<style>
    .amount-header, .amount { text-align: right; }
    .pl-statement { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--dark); }
    .pl-section { margin-bottom: 25px; }
    .pl-header { font-size: 1.2rem; font-weight: 700; color: var(--primary); margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid var(--primary); }
    .pl-line-item { display: flex; justify-content: space-between; padding: 8px 15px; border-bottom: 1px solid var(--border); }
    .pl-line-item .account-name { padding-left: 20px; }
    .pl-total-line { display: flex; justify-content: space-between; padding: 10px 15px; font-weight: 700; border-top: 2px solid var(--dark); border-bottom: 1px solid var(--dark); margin-top: 5px; }
    .pl-net-income { display: flex; justify-content: space-between; padding: 12px 15px; font-size: 1.3rem; font-weight: 700; color: var(--white); background: var(--primary); border-radius: var(--border-radius); margin-top: 20px; }
    .pl-net-income.loss { background: var(--danger); }
    .balance-section { margin-bottom: 25px; }
    .balance-section h4 { font-size: 1.2rem; color: var(--primary); margin-bottom: 10px; }
    .balance-section table { width: 100%; }
    .balance-section .total td { font-weight: 700; border-top: 2px solid var(--dark); }
    .balance-total { margin-top: 20px; border-top: 3px double var(--primary); padding-top: 10px; }
    .balance-total td { font-weight: 700; font-size: 1.1rem; }
    #custom-report-form { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start; }
    .report-options, .report-results { background-color: #f8f9fa; padding: 1.5rem; border-radius: 12px; }
    .column-selection-group { margin-top: 1rem; }
    .column-selection-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
    .column-checkboxes { max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); padding: 10px; background-color: #fff; border-radius: 8px; }
    .column-checkboxes label { display: block; font-weight: normal; }
    .report-results-container { min-height: 300px; }
</style>

<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-chart-bar"></i> Financial Reports</h1>
        <p>Analyze your school's financial performance and build custom reports.</p>
    </div>
</div>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link" onclick="openTab(event, 'plReport')"><i class="fas fa-chart-line"></i> P&L</button>
        <button class="tab-link" onclick="openTab(event, 'balanceSheet')"><i class="fas fa-balance-scale"></i> Balance Sheet</button>
        <button class="tab-link" onclick="openTab(event, 'arAging')"><i class="fas fa-clock"></i> A/R Aging</button>
        <button class="tab-link" onclick="openTab(event, 'incomeByItem')"><i class="fas fa-tags"></i> Income by Item</button>
        <button class="tab-link" onclick="openTab(event, 'studentBalances')"><i class="fas fa-user-graduate"></i> Student Balances</button>
        <button class="tab-link" onclick="openTab(event, 'payrollReports')"><i class="fas fa-money-check-alt"></i> Payroll</button>
        <button class="tab-link" onclick="openTab(event, 'depositSummary')"><i class="fas fa-university"></i> Deposits</button>
        <button class="tab-link" onclick="openTab(event, 'promisesReport')"><i class="fas fa-handshake"></i> Promises</button>
        <button class="tab-link" onclick="openTab(event, 'customReport')"><i class="fas fa-tools"></i> Custom Report Builder</button>
    </div>
  
    <div id="plReport" class="tab-content">
        <div class="card">
            <h3>Profit & Loss Statement</h3>
            <form method="GET" class="filter-controls">
                <input type="hidden" name="tab" value="plReport">
                <div class="form-group"><label for="plDateFrom">From:</label><input type="date" id="plDateFrom" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></div>
                <div class="form-group"><label for="plDateTo">To:</label><input type="date" id="plDateTo" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>
            <div class="pl-statement">
                <div class="pl-section">
                    <div class="pl-header">Revenue</div>
                    <?php foreach($plData['revenue']['accounts'] as $account): ?><div class="pl-line-item"><span class="account-name"><?= htmlspecialchars($account['account_name']) ?></span><span class="amount"><?= format_currency($account['total']) ?></span></div><?php endforeach; ?>
                    <div class="pl-total-line"><span>Total Revenue</span><span class="amount"><?= format_currency($plData['revenue']['total']) ?></span></div>
                </div>
                <div class="pl-section">
                    <div class="pl-header">Expenses</div>
                    <?php foreach($plData['expense']['accounts'] as $account): ?><div class="pl-line-item"><span class="account-name"><?= htmlspecialchars($account['account_name']) ?></span><span class="amount"><?= format_currency($account['total']) ?></span></div><?php endforeach; ?>
                    <div class="pl-total-line"><span>Total Expenses</span><span class="amount"><?= format_currency($plData['expense']['total']) ?></span></div>
                </div>
                <div class="pl-net-income <?= $plData['net_income'] < 0 ? 'loss' : '' ?>"><span>Net Income</span><span class="amount"><?= format_currency($plData['net_income']) ?></span></div>
            </div>
        </div>
    </div>
    
    <div id="balanceSheet" class="tab-content">
        <div class="card">
            <h3>Balance Sheet</h3>
            <div class="report-period">As of: <?= date('F d, Y') ?></div>
            <div class="balance-section"><h4>Assets</h4><table><tbody>
                <?php foreach($balanceSheetData['assets'] as $asset): ?><tr><td><?= htmlspecialchars($asset['account_name']) ?></td><td class="amount"><?= format_currency($asset['balance']) ?></td></tr><?php endforeach; ?>
                <tr class="total"><td>Total Assets</td><td class="amount"><?= format_currency($balanceSheetData['total_assets']) ?></td></tr>
            </tbody></table></div>
            <div class="balance-section"><h4>Liabilities</h4><table><tbody>
                <?php foreach($balanceSheetData['liabilities'] as $liability): ?><tr><td><?= htmlspecialchars($liability['account_name']) ?></td><td class="amount"><?= format_currency($liability['balance']) ?></td></tr><?php endforeach; ?>
                <tr class="total"><td>Total Liabilities</td><td class="amount"><?= format_currency($balanceSheetData['total_liabilities']) ?></td></tr>
            </tbody></table></div>
            <div class="balance-section"><h4>Equity</h4><table><tbody>
                <?php foreach($balanceSheetData['equity'] as $equity): ?><tr><td><?= htmlspecialchars($equity['account_name']) ?></td><td class="amount"><?= format_currency($equity['balance']) ?></td></tr><?php endforeach; ?>
                <tr><td>Retained Earnings</td><td class="amount"><?= format_currency($balanceSheetData['retained_earnings']) ?></td></tr>
                <tr class="total"><td>Total Equity</td><td class="amount"><?= format_currency($balanceSheetData['total_equity']) ?></td></tr>
            </tbody></table></div>
            <div class="balance-total"><table><tbody><tr><td>Total Liabilities + Equity</td><td class="amount"><?= format_currency($balanceSheetData['total_liabilities_equity']) ?></td></tr></tbody></table></div>
        </div>
    </div>
  
    <div id="arAging" class="tab-content">
        <div class="card">
            <h3>Accounts Receivable Aging Summary</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>Student</th><th class="amount-header">Current</th><th class="amount-header">1-30 Days</th><th class="amount-header">31-60 Days</th><th class="amount-header">61-90 Days</th><th class="amount-header">90+ Days</th><th class="amount-header">Total Due</th></tr></thead>
                    <tbody><?php foreach($arAgingData as $student => $data): ?><tr><td><?= htmlspecialchars($student) ?></td><td class="amount"><?= format_currency($data['current']) ?></td><td class="amount"><?= format_currency($data['30']) ?></td><td class="amount"><?= format_currency($data['60']) ?></td><td class="amount"><?= format_currency($data['90']) ?></td><td class="amount"><?= format_currency($data['older']) ?></td><td class="amount"><strong><?= format_currency($data['total']) ?></strong></td></tr><?php endforeach; ?></tbody>
                    <tfoot><tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong><?= format_currency($arTotals['current']) ?></strong></td><td class="amount"><strong><?= format_currency($arTotals['30']) ?></strong></td><td class="amount"><strong><?= format_currency($arTotals['60']) ?></strong></td><td class="amount"><strong><?= format_currency($arTotals['90']) ?></strong></td><td class="amount"><strong><?= format_currency($arTotals['older']) ?></strong></td><td class="amount"><strong><?= format_currency($arTotals['total']) ?></strong></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="incomeByItem" class="tab-content">
        <div class="card">
            <h3>Income by Item & Class</h3>
            <p>This report shows total invoiced amounts, grouped by class and then by individual fee items within the selected date range.</p>
            <form method="GET" class="filter-controls">
                <input type="hidden" name="tab" value="incomeByItem">
                <div class="form-group">
                    <label for="ibiDateFrom">From:</label>
                    <input type="date" id="ibiDateFrom" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="form-group">
                    <label for="ibiDateTo">To:</label>
                    <input type="date" id="ibiDateTo" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>

            <?php if (empty($incomeByItemAndClass)): ?>
                <div class="alert alert-info">No income data found for the selected period.</div>
            <?php else: ?>
                <?php foreach ($incomeByItemAndClass as $className => $items): ?>
                    <h4 style="margin-top: 2rem; border-bottom: 1px solid #ccc; padding-bottom: 5px;"><?= htmlspecialchars($className) ?></h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th class="amount-header">Total Invoiced Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $classTotal = 0;
                                foreach ($items as $item):
                                    $classTotal += $item['total_income'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td class="amount"><?= format_currency($item['total_income'], $_SESSION['currency_symbol'] ?? 'Ksh') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td><strong>Total for <?= htmlspecialchars($className) ?></strong></td>
                                    <td class="amount"><strong><?= format_currency($classTotal, $_SESSION['currency_symbol'] ?? 'Ksh') ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="studentBalances" class="tab-content">
        <div class="card">
            <h3>Student Balance Report</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>Student Name</th><th>Class</th><th>Phone Number</th><th class="amount-header">Total Outstanding Balance</th></tr></thead>
                    <tbody><?php foreach($studentBalances as $student): ?><tr><td><?= htmlspecialchars($student['name']) ?></td><td><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></td><td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td><td class="amount"><?= format_currency($student['total_balance']) ?></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="payrollReports" class="tab-content">
        <div class="card">
            <h3>Payroll Reports</h3>
            <form method="GET" class="filter-controls">
                <input type="hidden" name="tab" value="payrollReports">
                <div class="form-group"><label for="pay_period">Pay Period:</label><input type="month" id="pay_period" name="pay_period" value="<?= htmlspecialchars($payPeriod) ?>"></div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>
            <h4>Payroll Summary for <?= date('F Y', strtotime($payPeriod . '-01')) ?></h4>
            <div class="table-container">
                <table>
                    <thead><tr><th>Metric</th><th class="amount-header">Amount</th></tr></thead>
                    <tbody><tr><td>Employees Paid</td><td class="amount"><?= $payrollSummary['employee_count'] ?></td></tr><tr><td>Total Gross Pay</td><td class="amount"><?= format_currency($payrollSummary['total_gross']) ?></td></tr><tr><td>Total Deductions</td><td class="amount"><?= format_currency($payrollSummary['total_deductions']) ?></td></tr><tr class="total-row"><td><strong>Total Net Pay</strong></td><td class="amount"><strong><?= format_currency($payrollSummary['total_net']) ?></strong></td></tr></tbody>
                </table>
            </div>
            <h4 style="margin-top: 2rem;">Statutory Deductions Report for <?= date('F Y', strtotime($payPeriod . '-01')) ?></h4>
            <div class="table-container">
                 <table>
                    <thead><tr><th>Employee Name</th><th class="amount-header">PAYE</th><th class="amount-header">NHIF</th><th class="amount-header">NSSF</th><th class="amount-header">Housing Levy</th></tr></thead>
                    <tbody><?php foreach($statutoryData as $row): ?><tr><td><?= htmlspecialchars($row['employee_name']) ?></td><td class="amount"><?= format_currency($row['paye']) ?></td><td class="amount"><?= format_currency($row['nhif']) ?></td><td class="amount"><?= format_currency($row['nssf']) ?></td><td class="amount"><?= format_currency($row['housing_levy']) ?></td></tr><?php endforeach; ?></tbody>
                    <tfoot><tr class="total-row"><td><strong>TOTALS</strong></td><td class="amount"><strong><?= format_currency($statutoryTotals['paye']) ?></strong></td><td class="amount"><strong><?= format_currency($statutoryTotals['nhif']) ?></strong></td><td class="amount"><strong><?= format_currency($statutoryTotals['nssf']) ?></strong></td><td class="amount"><strong><?= format_currency($statutoryTotals['housing_levy']) ?></strong></td></tr></tfoot>
                </table>
                 </div>
        </div>
    </div>
    
    <div id="depositSummary" class="tab-content">
        <div class="card">
            <h3>Bank Deposit Summary</h3>
             <form method="GET" class="filter-controls">
                <input type="hidden" name="tab" value="depositSummary">
                <div class="form-group"><label for="depositDateFrom">From:</label><input type="date" id="depositDateFrom" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></div>
                <div class="form-group"><label for="depositDateTo">To:</label><input type="date" id="depositDateTo" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>
            <div class="table-container">
                <table>
                    <thead><tr><th>Deposit Date</th><th>Bank Account</th><th>Memo / Details</th><th class="amount-header">Amount</th></tr></thead>
                    <tbody><?php if (empty($depositSummary)): ?><tr><td colspan="4" class="text-center">No deposits found for this period.</td></tr><?php else: ?><?php foreach($depositSummary as $deposit): ?><tr><td><?= date('M d, Y', strtotime($deposit['deposit_date'])) ?></td><td><?= htmlspecialchars($deposit['account_name']) ?></td><td><?= htmlspecialchars($deposit['memo']) ?></td><td class="amount"><?= format_currency($deposit['amount']) ?></td></tr><?php endforeach; ?><?php endif; ?></tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="promisesReport" class="tab-content">
        <div class="card">
            <h3>Payment Promises Report</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>Student</th><th>Invoice #</th><th>Promise Date</th><th>Promised Due Date</th><th class="amount-header">Amount</th><th>Status</th></tr></thead>
                    <tbody><?php if (empty($paymentPromises)): ?><tr><td colspan="6" class="text-center">No pending or broken promises found.</td></tr><?php else: ?><?php foreach($paymentPromises as $promise): ?><tr><td><?= htmlspecialchars($promise['student_name']) ?></td><td><a href="view_invoice.php?id=<?= $promise['invoice_id'] ?>" target="_blank"><?= $promise['invoice_id'] ?></a></td><td><?= date('M d, Y', strtotime($promise['promise_date'])) ?></td><td><?= date('M d, Y', strtotime($promise['promised_due_date'])) ?></td><td class="amount"><?= format_currency($promise['promised_amount']) ?></td><td><span class="badge badge-<?= strtolower($promise['status']) ?>"><?= htmlspecialchars($promise['status']) ?></span></td></tr><?php endforeach; ?><?php endif; ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="customReport" class="tab-content">
        <div class="card">
            <h3>Custom Report Builder</h3>
            <p>Select a report type, choose your columns and filters, and generate a custom report. You can then export the results to CSV.</p>
            <div id="custom-report-form">
                <div class="report-options">
                    <h4>1. Report Options</h4>
                    <form method="POST" action="reports.php?tab=customReport">
                        <input type="hidden" name="active_tab" value="customReport">
                        <div class="form-group"><label for="report_type">Report Type</label><select name="report_type" id="report_type" class="form-control" required><option value="">-- Select --</option><option value="payments">Payments Received</option><option value="invoices">Invoices</option><option value="expenses">Expenses</option><option value="students">Student List</option></select></div>
                        <div id="columns-container"></div>
                        <h4>2. Filters</h4>
                        <div class="form-group"><label for="start_date">From Date</label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>"></div>
                        <div class="form-group"><label for="end_date">To Date</label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>"></div>
                        <div id="filters-container"></div>
                        <h4>3. Formatting</h4>
                         <div class="form-group"><label for="sort_by">Sort By</label><select name="sort_by" id="sort_by" class="form-control"></select></div>
                        <div class="form-group"><label for="sort_order">Sort Order</label><select name="sort_order" class="form-control"><option value="ASC">Ascending</option><option value="DESC">Descending</option></select></div>
                        <div class="form-actions"><button type="submit" name="generate_custom_report" class="btn-primary">Generate Report</button></div>
                    </form>
                </div>
                <div class="report-results">
                    <h4>Report Results</h4>
                    <?php if (isset($customReportError)): ?><div class="alert alert-danger"><?= htmlspecialchars($customReportError) ?></div><?php endif; ?>
                    <div class="report-results-container">
                        <?php if ($customReportData && !empty($customReportData['data'])): ?>
                            <form method="post" style="text-align: right; margin-bottom: 1rem;"><input type="hidden" name="export_custom_report" value="1"><input type="hidden" name="report_data_json" value="<?= htmlspecialchars(json_encode($customReportData)) ?>"><button type="submit" class="btn-success"><i class="fas fa-file-csv"></i> Export to CSV</button></form>
                            <div class="table-container">
                                <table>
                                    <thead><tr><?php foreach ($customReportData['headers'] as $header): ?><th><?= htmlspecialchars($header) ?></th><?php endforeach; ?></tr></thead>
                                    <tbody><?php foreach ($customReportData['data'] as $row): ?><tr><?php foreach ($row as $cell): ?><td><?= htmlspecialchars($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                                </table>
                            </div>
                        <?php elseif (isset($_POST['generate_custom_report'])): ?><p>No results found for the selected criteria.</p><?php else: ?><p>Your generated report will appear here.</p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// --- Standard Tab Navigation ---
function openTab(evt, tabName) {
    document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove('active'));
    document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
    if (history.pushState) {
        let newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + tabName;
        window.history.pushState({path:newurl},'',newurl);
    }
}

// --- Custom Report Builder Logic ---
const reportOptions = {
    payments: {
        columns: { 'r.receipt_number': 'Receipt #', 's.name': 'Student', 'p.payment_date': 'Date', 'p.amount': 'Amount', 'p.payment_method': 'Method', 'c.name': 'Class' },
        filters: ['student', 'class']
    },
    invoices: {
        columns: { 'i.invoice_number': 'Invoice #', 's.name': 'Student', 'i.invoice_date': 'Date', 'i.due_date': 'Due Date', 'i.total_amount': 'Total', 'i.amount_paid': 'Paid', 'i.balance': 'Balance', 'i.status': 'Status', 'c.name': 'Class' },
        filters: ['student', 'class', 'status']
    },
    expenses: {
        columns: { 'a.account_name': 'Account', 'e.transaction_date': 'Date', 'e.description': 'Description', 'e.amount': 'Amount', 'e.payment_method': 'Method' },
        filters: []
    },
    students: {
        columns: { 's.student_id_no': 'Student ID', 's.name': 'Name', 'c.name': 'Class', 's.status': 'Status', 's.phone': 'Phone', 's.email': 'Email' },
        filters: ['class', 'status']
    }
};

document.getElementById('report_type').addEventListener('change', function() {
    const type = this.value;
    const columnsContainer = document.getElementById('columns-container');
    const filtersContainer = document.getElementById('filters-container');
    const sortBySelect = document.getElementById('sort_by');
    
    columnsContainer.innerHTML = '';
    filtersContainer.innerHTML = '';
    sortBySelect.innerHTML = '';

    if (type && reportOptions[type]) {
        const options = reportOptions[type];
        
        let columnsHtml = '<div class="column-selection-group"><label>Columns to Include</label><div class="column-checkboxes">';
        for (const [key, value] of Object.entries(options.columns)) {
            columnsHtml += `<label><input type="checkbox" name="columns[]" value="${key}" checked> ${value}</label>`;
        }
        columnsHtml += '</div></div>';
        columnsContainer.innerHTML = columnsHtml;

        for (const [key, value] of Object.entries(options.columns)) {
            sortBySelect.innerHTML += `<option value="${key}">${value}</option>`;
        }

        let filtersHtml = '';
        // Pre-generated dropdowns from PHP
        const studentDropdown = `<?php
            $studentOptions = '<option value="">All</option>';
            foreach ($all_students_for_dropdown as $student) {
                $studentOptions .= '<option value="' . htmlspecialchars($student['id']) . '">' . htmlspecialchars($student['name']) . '</option>';
            }
            echo '<select name="student_id" class="form-control">' . $studentOptions . '</select>';
        ?>`;

        const classDropdown = `<?php
            $classOptions = '<option value="">All</option>';
            foreach ($all_classes_for_dropdown as $class) {
                $classOptions .= '<option value="' . htmlspecialchars($class['id']) . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '<select name="class_id" class="form-control">' . $classOptions . '</select>';
        ?>`;

        if (options.filters.includes('student')) {
            filtersHtml += `<div class="form-group"><label for="filter_student_id">Student</label>${studentDropdown}</div>`;
        }
        if (options.filters.includes('class')) {
            filtersHtml += `<div class="form-group"><label for="filter_class_id">Class</label>${classDropdown}</div>`;
        }
        if (options.filters.includes('status')) {
             filtersHtml += `<div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="">All</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="Paid">Paid</option><option value="Unpaid">Unpaid</option><option value="Overdue">Overdue</option></select></div>`;
        }
        filtersContainer.innerHTML = filtersHtml;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'plReport';
    const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
    if (tabButton) {
        // We need to simulate a click to run the JS function
        const mockEvent = { currentTarget: tabButton };
        openTab(mockEvent, tab);
    } else {
        // Fallback for safety
        const firstButton = document.querySelector('.tab-link');
        if(firstButton) {
            const mockEvent = { currentTarget: firstButton };
            openTab(mockEvent, 'plReport');
        }
    }
});
</script>
</body>
</html>