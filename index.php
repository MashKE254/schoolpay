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
$outstandingFees = $summary['outstanding_fees']; // Retrieve the new outstanding fees value

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

<!-- Dashboard Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Period Selector -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" id="period-selector-form" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-3">
                <label for="period-select" class="text-sm font-semibold text-gray-700">Show Stats For:</label>
                <select name="period" id="period-select" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="30d" <?= ($period === '30d') ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="90d" <?= ($period === '90d') ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="6m" <?= ($period === '6m') ? 'selected' : '' ?>>Last 6 Months</option>
                    <option value="ytd" <?= ($period === 'ytd') ? 'selected' : '' ?>>This Year (YTD)</option>
                    <option value="1y" <?= ($period === '1y') ? 'selected' : '' ?>>Last Year</option>
                    <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom Range...</option>
                </select>
            </div>
            <div id="custom-date-fields" class="<?= ($period === 'custom') ? 'flex' : 'hidden' ?> items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="start_date_custom" class="text-sm text-gray-600">From:</label>
                    <input type="date" name="start_date_custom" id="start_date_custom" value="<?= htmlspecialchars($custom_start) ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center gap-2">
                    <label for="end_date_custom" class="text-sm text-gray-600">To:</label>
                    <input type="date" name="end_date_custom" id="end_date_custom" value="<?= htmlspecialchars($custom_end) ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Income -->
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-emerald-500 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Income</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= format_currency($totalIncome) ?></p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-arrow-up text-emerald-600"></i>
                </div>
            </div>
            <p class="text-xs text-emerald-600 mt-3 flex items-center gap-1">
                <i class="fas fa-calendar-alt"></i> <?= $period_label ?>
            </p>
        </div>

        <!-- Total Expenses -->
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-500 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Expenses</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= format_currency($totalExpenses) ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-arrow-down text-red-600"></i>
                </div>
            </div>
            <p class="text-xs text-red-600 mt-3 flex items-center gap-1">
                <i class="fas fa-calendar-alt"></i> <?= $period_label ?>
            </p>
        </div>

        <!-- Outstanding Fees -->
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-amber-500 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Outstanding Fees</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= format_currency($outstandingFees) ?></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-invoice-dollar text-amber-600"></i>
                </div>
            </div>
            <p class="text-xs text-amber-600 mt-3 flex items-center gap-1">
                <i class="fas fa-exclamation-circle"></i> Total Due
            </p>
        </div>

        <!-- Active Students -->
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-blue-500 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Students</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($totalStudents) ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-graduate text-blue-600"></i>
                </div>
            </div>
            <p class="text-xs text-blue-600 mt-3 flex items-center gap-1">
                <i class="fas fa-user-check"></i> Total Enrolled
            </p>
        </div>

        <!-- Net Balance -->
        <div class="bg-white rounded-xl shadow-sm border-l-4 <?= ($currentBalance >= 0) ? 'border-emerald-500' : 'border-red-500' ?> p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Net Balance</p>
                    <p class="text-2xl font-bold <?= ($currentBalance >= 0) ? 'text-emerald-600' : 'text-red-600' ?> mt-1"><?= format_currency($currentBalance) ?></p>
                </div>
                <div class="w-12 h-12 <?= ($currentBalance >= 0) ? 'bg-emerald-100' : 'bg-red-100' ?> rounded-full flex items-center justify-center">
                    <i class="fas fa-<?= ($currentBalance >= 0) ? 'smile' : 'frown' ?> <?= ($currentBalance >= 0) ? 'text-emerald-600' : 'text-red-600' ?>"></i>
                </div>
            </div>
            <p class="text-xs <?= ($currentBalance >= 0) ? 'text-emerald-600' : 'text-red-600' ?> mt-3 flex items-center gap-1">
                <i class="fas fa-chart-line"></i> For <?= $period_label ?>
            </p>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Financial Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-chart-area text-blue-500"></i>
                        Income vs. Expense
                    </h3>
                    <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full"><?= $period_label ?></span>
                </div>
                <div class="h-72">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>

            <!-- Overdue Invoices -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                        Action Required: Overdue Invoices
                    </h3>
                    <?php if (!empty($overdueInvoices)): ?>
                    <span class="bg-red-100 text-red-700 text-sm font-semibold px-3 py-1 rounded-full">
                        <?= count($overdueInvoices) ?> Overdue
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($overdueInvoices)): ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-3xl text-emerald-500"></i>
                    </div>
                    <p class="text-gray-600 font-medium">All payments are up to date!</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach(array_slice($overdueInvoices, 0, 4) as $invoice):
                        $dueDate = new DateTime($invoice['due_date']);
                        $dueDate->setTime(0, 0, 0);
                        $now = new DateTime('today');
                        $days_overdue = ($dueDate < $now) ? $now->diff($dueDate)->days : 0;
                    ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div>
                            <a href="view_invoice.php?id=<?= $invoice['id'] ?>" class="font-semibold text-gray-900 hover:text-blue-600 transition-colors">
                                <?= htmlspecialchars($invoice['student_name']) ?>
                            </a>
                            <p class="text-sm text-gray-500 mt-1">
                                Invoice #<?= $invoice['id'] ?> • <span class="text-red-600 font-medium"><?= $days_overdue ?> days late</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold text-red-600"><?= format_currency($invoice['balance']) ?></span>
                            <button class="px-3 py-1.5 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                Send Reminder
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="reports.php?tab=arAging" class="text-sm text-blue-600 font-medium hover:text-blue-700 flex items-center gap-1">
                        View All Overdue <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column (1/3 width) -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <i class="fas fa-bolt text-amber-500"></i>
                    Quick Actions
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="create_invoice.php" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl hover:from-blue-100 hover:to-blue-200 transition-all group">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-plus text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">New Invoice</span>
                    </a>
                    <a href="customer_center.php?tab=receive_payment" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl hover:from-emerald-100 hover:to-emerald-200 transition-all group">
                        <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-hand-holding-usd text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Receive Payment</span>
                    </a>
                    <a href="expense_management.php?tab=journal" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl hover:from-red-100 hover:to-red-200 transition-all group">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-edit text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Record Expense</span>
                    </a>
                    <a href="payroll.php" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl hover:from-purple-100 hover:to-purple-200 transition-all group">
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-money-check-alt text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Run Payroll</span>
                    </a>
                </div>
            </div>

            <!-- Budget Overview -->
            <?php if ($budgetSummary): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-bullseye text-indigo-500"></i>
                        Budget Overview
                    </h3>
                    <a href="budget.php" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                <div>
                    <p class="font-medium text-gray-900 mb-3"><?= htmlspecialchars($budgetSummary['name']) ?></p>
                    <div class="relative h-3 bg-gray-200 rounded-full overflow-hidden mb-2">
                        <div class="absolute h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all" style="width: <?= min($budgetSummary['percentage_used'], 100) ?>%;"></div>
                    </div>
                    <p class="text-sm text-gray-500 mb-3"><?= $budgetSummary['percentage_used'] ?>% of Expense Budget Used</p>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Spent: <span class="font-semibold text-gray-900"><?= format_currency($budgetSummary['total_actual']) ?></span></span>
                        <span class="text-gray-600">Budget: <span class="font-semibold text-gray-900"><?= format_currency($budgetSummary['total_budgeted']) ?></span></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Expense Breakdown -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <i class="fas fa-chart-pie text-pink-500"></i>
                    Expense Breakdown
                </h3>
                <p class="text-sm text-gray-500 mb-4">Last 90 Days</p>
                <div class="h-48 flex items-center justify-center">
                    <?php if (empty($topExpenses)): ?>
                    <div class="text-center">
                        <i class="fas fa-receipt text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500 text-sm">No expense data for this period.</p>
                    </div>
                    <?php else: ?>
                    <canvas id="expenseDonutChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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
