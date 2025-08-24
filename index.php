<?php
// index.php - Revamped Dashboard with Dynamic KPIs & Chart

require 'config.php';
require 'functions.php';
include 'header.php'; // Handles session and sets $school_id

// --- Define the date range based on user selection ---
$period = $_GET['period'] ?? '6m'; // Default to Last 6 Months
$end_date_obj = new DateTime();
$period_label = 'Last 6 Months';

// Set default custom dates to today
$custom_start = $_GET['start_date_custom'] ?? date('Y-m-d');
$custom_end = $_GET['end_date_custom'] ?? date('Y-m-d');

switch ($period) {
    case '30d':
        $start_date_obj = (new DateTime())->sub(new DateInterval('P30D'));
        $period_label = 'Last 30 Days';
        break;
    case '90d':
        $start_date_obj = (new DateTime())->sub(new DateInterval('P90D'));
        $period_label = 'Last 90 Days';
        break;
    case '1y':
        $start_date_obj = (new DateTime())->sub(new DateInterval('P1Y'));
        $period_label = 'Last Year';
        break;
    case 'ytd':
        $start_date_obj = new DateTime(date('Y-01-01'));
        $period_label = 'This Year (YTD)';
        break;
    case 'custom': // NEW: Handle the custom date range
        $start_date_obj = new DateTime($custom_start);
        $end_date_obj = new DateTime($custom_end);
        $period_label = $start_date_obj->format('M j, Y') . ' - ' . $end_date_obj->format('M j, Y');
        break;
    case '6m':
    default:
        $start_date_obj = (new DateTime())->sub(new DateInterval('P6M'));
        $period_label = 'Last 6 Months';
        break;
}
$start_date = $start_date_obj->format('Y-m-d');
$end_date = $end_date_obj->format('Y-m-d');

// --- Call the function with the dynamic date range for KPIs ---
// This relies on the updated getDashboardSummary function in functions.php
$summary = getDashboardSummary($pdo, $school_id, $start_date, $end_date);
$totalStudents = $summary['total_students'];
$totalIncome = $summary['total_income'];
$totalExpenses = $summary['total_expenses'];
$currentBalance = $summary['current_balance'];

// --- Data Fetching for Widgets ---

// Recent Transactions
$recentTransactions = [];
$stmt_payments = $pdo->prepare("
    SELECT p.id, p.payment_date as date, p.amount, 'Payment' as type, s.name as related_name, i.id as reference_number
    FROM payments p JOIN invoices i ON p.invoice_id = i.id JOIN students s ON i.student_id = s.id
    WHERE p.school_id = ? ORDER BY p.payment_date DESC LIMIT 5
");
$stmt_payments->execute([$school_id]);
$recentTransactions = array_merge($recentTransactions, $stmt_payments->fetchAll(PDO::FETCH_ASSOC));

$stmt_expenses_recent = $pdo->prepare("
    SELECT e.id, e.transaction_date as date, e.amount, 'Expense' as type, e.type as related_name, e.reference_number
    FROM expenses e WHERE e.school_id = ? AND e.transaction_type = 'debit' ORDER BY e.transaction_date DESC LIMIT 5
");
$stmt_expenses_recent->execute([$school_id]);
$recentTransactions = array_merge($recentTransactions, $stmt_expenses_recent->fetchAll(PDO::FETCH_ASSOC));

usort($recentTransactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
$recentTransactions = array_slice($recentTransactions, 0, 5);

// Overdue Invoices
$overdueInvoices = getOpenInvoicesReport($pdo, $school_id);

// --- DYNAMIC CHART LOGIC ---
// Determine grouping format based on the selected period length
$days_diff = $end_date_obj->diff($start_date_obj)->days;
if ($days_diff <= 90) { // Group by day for periods up to 90 days
    $sql_group_format = '%Y-%m-%d';
    $php_label_format = 'M j';
    $php_key_format = 'Y-m-d';
    $interval = new DateInterval('P1D');
} else { // Group by month for longer periods
    $sql_group_format = '%Y-%m';
    $php_label_format = 'M Y';
    $php_key_format = 'Y-m';
    $interval = new DateInterval('P1M');
}

// Generate all labels and keys for the period
$chartLabels = [];
$chartKeys = [];
$periodIterator = new DatePeriod($start_date_obj, $interval, $end_date_obj->add(new DateInterval('P1D'))); // Add 1 day to include the end date

foreach ($periodIterator as $date) {
    $chartLabels[] = $date->format($php_label_format);
    $chartKeys[] = $date->format($php_key_format);
}

// Initialize data arrays with keys
$chartIncomeData = array_fill_keys($chartKeys, 0);
$chartExpenseData = array_fill_keys($chartKeys, 0);

// Fetch and map income data
$stmt_chart_income = $pdo->prepare("
    SELECT DATE_FORMAT(payment_date, '{$sql_group_format}') as period, SUM(amount) as total
    FROM payments WHERE school_id = :school_id AND payment_date BETWEEN :start_date AND :end_date
    GROUP BY period ORDER BY period ASC
");
$stmt_chart_income->execute([':school_id' => $school_id, ':start_date' => $start_date, ':end_date' => $end_date]);
$incomeResults = $stmt_chart_income->fetchAll(PDO::FETCH_ASSOC);
foreach ($incomeResults as $row) {
    if (isset($chartIncomeData[$row['period']])) {
        $chartIncomeData[$row['period']] = (float)$row['total'];
    }
}

// Fetch and map expense data
$stmt_chart_expense = $pdo->prepare("
    SELECT DATE_FORMAT(e.transaction_date, '{$sql_group_format}') as period, SUM(e.amount) as total
    FROM expenses e JOIN accounts a ON e.account_id = a.id
    WHERE e.school_id = :school_id AND e.transaction_date BETWEEN :start_date AND :end_date
      AND a.account_type = 'expense' AND e.transaction_type = 'debit'
    GROUP BY period ORDER BY period ASC
");
$stmt_chart_expense->execute([':school_id' => $school_id, ':start_date' => $start_date, ':end_date' => $end_date]);
$expenseResults = $stmt_chart_expense->fetchAll(PDO::FETCH_ASSOC);
foreach ($expenseResults as $row) {
    if (isset($chartExpenseData[$row['period']])) {
        $chartExpenseData[$row['period']] = (float)$row['total'];
    }
}

// Final data arrays for Chart.js
$finalIncomeData = array_values($chartIncomeData);
$finalExpenseData = array_values($chartExpenseData);

// Expense Breakdown chart data
$expense_cat_stmt = $pdo->prepare("
    SELECT a.account_name, SUM(e.amount) as total
    FROM expenses e JOIN accounts a ON e.account_id = a.id
    WHERE e.school_id = ? AND a.account_type = 'expense' AND e.transaction_type = 'debit' AND e.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY a.id ORDER BY total DESC LIMIT 5
");
$expense_cat_stmt->execute([$school_id]);
$topExpenses = $expense_cat_stmt->fetchAll(PDO::FETCH_ASSOC);
$expenseCatLabels = array_column($topExpenses, 'account_name');
$expenseCatData = array_column($topExpenses, 'total');

// Budget summary
$budgetSummary = null;
$budget_stmt = $pdo->prepare("SELECT id, name, start_date, end_date FROM budgets WHERE school_id = ? AND status = 'active' AND NOW() BETWEEN start_date AND end_date LIMIT 1");
$budget_stmt->execute([$school_id]);
$active_budget = $budget_stmt->fetch(PDO::FETCH_ASSOC);
if ($active_budget) {
    $budget_data = getBudgetVsActualsData($pdo, $active_budget['id'], $active_budget['start_date'], $active_budget['end_date'], $school_id);
    if ($budget_data) {
        $budgetSummary = [
            'name' => $active_budget['name'],
            'total_budgeted' => $budget_data['expense']['totals']['budgeted'],
            'total_actual' => $budget_data['expense']['totals']['actual'],
            'percentage_used' => ($budget_data['expense']['totals']['budgeted'] > 0) ? round(($budget_data['expense']['totals']['actual'] / $budget_data['expense']['totals']['budgeted']) * 100) : 0
        ];
    }
}
?>

<div class="dashboard-container">
    <!-- Period Selector Form -->
    <div class="dashboard-controls">
        <form method="GET" id="period-selector-form">
            <div class="form-group">
                <label for="period-select">Show Stats For:</label>
                <select name="period" id="period-select">
                    <option value="30d" <?php echo ($period === '30d') ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90d" <?php echo ($period === '90d') ? 'selected' : ''; ?>>Last 90 Days</option>
                    <option value="6m" <?php echo ($period === '6m') ? 'selected' : ''; ?>>Last 6 Months</option>
                    <option value="ytd" <?php echo ($period === 'ytd') ? 'selected' : ''; ?>>This Year (YTD)</option>
                    <option value="1y" <?php echo ($period === '1y') ? 'selected' : ''; ?>>Last Year</option>
                    <option value="custom" <?php echo ($period === 'custom') ? 'selected' : ''; ?>>Custom Range...</option>
                </select>
            </div>
            <!-- NEW: Custom Date Inputs -->
            <div class="custom-date-range" id="custom-date-fields" style="display: <?php echo ($period === 'custom') ? 'flex' : 'none'; ?>;">
                <div class="form-group">
                    <label for="start_date_custom">From:</label>
                    <input type="date" name="start_date_custom" id="start_date_custom" value="<?php echo htmlspecialchars($custom_start); ?>">
                </div>
                <div class="form-group">
                    <label for="end_date_custom">To:</label>
                    <input type="date" name="end_date_custom" id="end_date_custom" value="<?php echo htmlspecialchars($custom_end); ?>">
                </div>
                <button type="submit" class="btn-primary btn-small">Apply</button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card income-card">
            <div class="card-label">Total Income</div>
            <div class="card-value">$<?php echo number_format($totalIncome, 2); ?></div>
            <div class="card-trend positive"><i class="fas fa-calendar-alt"></i> <?php echo $period_label; ?></div>
        </div>
        <div class="summary-card expense-card">
            <div class="card-label">Total Expenses</div>
            <div class="card-value">$<?php echo number_format($totalExpenses, 2); ?></div>
            <div class="card-trend negative"><i class="fas fa-calendar-alt"></i> <?php echo $period_label; ?></div>
        </div>
         <div class="summary-card student-card">
            <div class="card-label">Active Students</div>
            <div class="card-value"><?php echo number_format($totalStudents); ?></div>
            <div class="card-trend"><i class="fas fa-user-check"></i> Total Enrolled</div>
        </div>
        <div class="summary-card balance-card">
            <div class="card-label">Net Balance</div>
            <div class="card-value">$<?php echo number_format($currentBalance, 2); ?></div>
            <div class="card-trend <?php echo ($currentBalance >= 0) ? 'positive' : 'negative'; ?>">
                <i class="fas fa-<?php echo ($currentBalance >= 0) ? 'smile' : 'frown'; ?>"></i>
                For <?php echo $period_label; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Dashboard Grid -->
    <div class="dashboard-main-grid">
        <div class="main-content">
            <!-- Financial Chart -->
            <div class="dashboard-card chart-container">
                <div class="card-header">
                    <h3><i class="fas fa-chart-area"></i> Income vs. Expense (<?php echo $period_label; ?>)</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>

            <!-- Overdue Invoices -->
            <div class="dashboard-card overdue-card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Action Required: Overdue Invoices</h3>
                    <?php if (!empty($overdueInvoices)): ?>
                        <div class="priority-indicator"><span><?php echo count($overdueInvoices); ?></span> Overdue</div>
                    <?php endif; ?>
                </div>
                <div class="item-list">
                    <?php if (empty($overdueInvoices)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i><p>All payments are up to date!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach(array_slice($overdueInvoices, 0, 4) as $invoice): ?>
                             <?php
    $dueDate = new DateTime($invoice['due_date']);
    $dueDate->setTime(0, 0, 0); // Normalize due date to midnight

    $now = new DateTime('today'); // 'today' also defaults to midnight

    $days_overdue = 0; // Default to 0
    if ($dueDate < $now) {
        // Only calculate the difference if the due date is in the past
        $days_overdue = $now->diff($dueDate)->days;
    }
?>
                            <div class="list-item">
                                <div class="item-details">
                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="item-title"><?php echo htmlspecialchars($invoice['student_name']); ?></a>
                                    <span class="item-meta">Invoice #<?php echo $invoice['id']; ?> • <span class="days-overdue"><?php echo $days_overdue; ?> days late</span></span>
                                </div>
                                <div class="item-action">
                                    <span class="item-amount-bad">$<?php echo number_format($invoice['balance'], 2); ?></span>
                                    <button class="btn-secondary btn-small">Send Reminder</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                 <div class="card-footer">
                    <a href="reports.php?tab=arAging" class="view-all-btn">View All Overdue <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="sidebar-content">
             <!-- Quick Actions -->
            <div class="dashboard-card quick-actions-card">
                <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
                <div class="quick-actions-grid">
                    <a href="create_invoice.php" class="action-item">
                        <i class="fas fa-plus"></i><span>New Invoice</span>
                    </a>
                    <a href="customer_center.php?tab=receive_payment" class="action-item">
                        <i class="fas fa-hand-holding-usd"></i><span>Receive Payment</span>
                    </a>
                    <a href="expense_management.php?tab=journal" class="action-item">
                        <i class="fas fa-edit"></i><span>Record Expense</span>
                    </a>
                    <a href="payroll.php" class="action-item">
                        <i class="fas fa-money-check-alt"></i><span>Run Payroll</span>
                    </a>
                </div>
            </div>
            
            <!-- Budget Overview -->
            <?php if ($budgetSummary): ?>
            <div class="dashboard-card budget-card">
                <div class="card-header">
                    <h3><i class="fas fa-bullseye"></i> Budget Overview</h3>
                    <a href="budget.php" class="view-all-btn"><i class="fas fa-eye"></i></a>
                </div>
                <div class="budget-details">
                    <span class="budget-name"><?php echo htmlspecialchars($budgetSummary['name']); ?></span>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $budgetSummary['percentage_used']; ?>%;"></div>
                        </div>
                        <span class="progress-label"><?php echo $budgetSummary['percentage_used']; ?>% of Expense Budget Used</span>
                    </div>
                    <div class="budget-numbers">
                        <span>$<?php echo number_format($budgetSummary['total_actual'], 0); ?></span>
                        <span>$<?php echo number_format($budgetSummary['total_budgeted'], 0); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Expense Breakdown -->
            <div class="dashboard-card expense-breakdown-card">
                 <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Expense Breakdown (Last 90 Days)</h3>
                </div>
                <div class="donut-chart-wrapper">
                    <?php if (empty($topExpenses)): ?>
                         <div class="empty-state">
                            <i class="fas fa-receipt"></i><p>No expense data for this period.</p>
                        </div>
                    <?php else: ?>
                        <canvas id="expenseDonutChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Add styles for the new period selector */
.dashboard-controls {
    background: var(--card-bg);
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
}
#period-selector-form {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
#period-selector-form .form-group, .custom-date-range {
    display: flex;
    align-items: center;
    gap: 10px;
}
#period-selector-form label {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 0;
}
#period-select, input[type="date"] {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    font-weight: 500;
}
:root {
    --primary: #2c3e50; --secondary: #3498db; --success: #2ecc71;
    --warning: #f39c12; --danger: #e74c3c; --light: #ecf0f1;
    --card-bg: #ffffff; --border: #dfe6e9;
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
body { background-color: #f4f7f9; }
.dashboard-container { max-width: 1600px; margin: auto; padding: 20px; }
.summary-cards {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px;
}
.summary-card {
    background: var(--card-bg); padding: 20px; border-radius: 12px; box-shadow: var(--shadow);
    border-left: 5px solid var(--secondary);
}
.summary-card.income-card { border-color: var(--success); }
.summary-card.expense-card { border-color: var(--danger); }
.summary-card.balance-card { border-color: var(--primary); }
.card-label { font-size: 1rem; color: #7f8c8d; margin-bottom: 8px; }
.card-value { font-size: 2rem; font-weight: 700; color: var(--primary); }
.card-trend { font-size: 0.8rem; margin-top: 10px; display: flex; align-items: center; gap: 5px;}
.card-trend.positive { color: var(--success); }
.card-trend.negative { color: var(--danger); }
.dashboard-main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
.main-content, .sidebar-content { display: flex; flex-direction: column; gap: 20px; }
.dashboard-card { background: var(--card-bg); border-radius: 12px; box-shadow: var(--shadow); padding: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 15px; }
.card-header h3 { font-size: 1.1rem; color: var(--primary); display: flex; align-items: center; gap: 10px; }
.view-all-btn { font-size: 0.8rem; text-decoration: none; color: var(--secondary); font-weight: 600; }
.chart-wrapper { height: 300px; }
.donut-chart-wrapper { height: 220px; display: flex; align-items: center; justify-content: center; }
.quick-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.action-item { background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; color: var(--primary); text-decoration: none; font-weight: 600; transition: all 0.2s ease; }
.action-item:hover { background: var(--secondary); color: white; transform: translateY(-3px); }
.action-item i { display: block; font-size: 1.5rem; margin-bottom: 8px; }
.item-list { display: flex; flex-direction: column; gap: 10px; }
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; }
.list-item:hover { background: #f8f9fa; }
.item-details .item-title { font-weight: 600; color: var(--primary); text-decoration: none; }
.item-details .item-meta { font-size: 0.8rem; color: #7f8c8d; }
.days-overdue { color: var(--danger); font-weight: bold; }
.item-action { display: flex; align-items: center; gap: 10px; }
.item-amount-bad { font-weight: 700; color: var(--danger); }
.btn-small { padding: 5px 10px; font-size: 0.75rem; }
.budget-card .budget-name { font-weight: 600; color: var(--primary); }
.budget-card .progress-bar { background: var(--light); border-radius: 5px; height: 10px; margin: 10px 0 5px; }
.budget-card .progress-fill { background: var(--success); height: 100%; border-radius: 5px; }
.budget-card .progress-label { font-size: 0.8rem; color: #7f8c8d; }
.budget-card .budget-numbers { display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #7f8c8d; }
.empty-state { text-align: center; padding: 30px; }
.empty-state i { font-size: 2.5rem; color: #bdc3c7; margin-bottom: 10px; }
.empty-state p { font-weight: 600; color: #7f8c8d; }
@media (max-width: 1200px) { .dashboard-main-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .summary-cards { grid-template-columns: 1fr; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Pass PHP data to JS ---
    const chartData = {
        labels: <?= json_encode($chartLabels) ?>,
        income: <?= json_encode($finalIncomeData) ?>,
        expenses: <?= json_encode($finalExpenseData) ?>
    };
    const expenseDonutData = {
        labels: <?= json_encode($expenseCatLabels) ?>,
        data: <?= json_encode($expenseCatData) ?>
    };

    // --- Main Financial Chart ---
    const ctxLine = document.getElementById('financeChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Income',
                data: chartData.income,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Expenses',
                data: chartData.expenses,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // --- Expense Breakdown Donut Chart ---
    if (expenseDonutData.data.length > 0) {
        const ctxDonut = document.getElementById('expenseDonutChart').getContext('2d');
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: expenseDonutData.labels,
                datasets: [{
                    label: 'Top Expenses',
                    data: expenseDonutData.data,
                    backgroundColor: ['#3498db', '#e74c3c', '#f1c40f', '#9b59b6', '#34495e'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- NEW: JavaScript for Custom Date Selector ---
    const periodSelect = document.getElementById('period-select');
    const customDateFields = document.getElementById('custom-date-fields');
    const form = document.getElementById('period-selector-form');

    periodSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateFields.style.display = 'flex';
        } else {
            customDateFields.style.display = 'none';
            form.submit();
        }
    });
});
</script>
