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
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="min-h-screen bg-[#fafafa] py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-bold tracking-tighter text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1 uppercase tracking-widest font-medium">Financial Overview & Insights</p>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-xl p-2 shadow-sm flex items-center gap-2">
                <form method="GET" id="period-selector-form" class="flex items-center gap-2">
                    <select name="period" id="period-select" class="border-0 bg-transparent text-xs font-bold uppercase tracking-wider text-gray-600 focus:ring-0 cursor-pointer">
                        <option value="30d" <?= ($period === '30d') ? 'selected' : '' ?>>30 Days</option>
                        <option value="90d" <?= ($period === '90d') ? 'selected' : '' ?>>90 Days</option>
                        <option value="6m" <?= ($period === '6m') ? 'selected' : '' ?>>6 Months</option>
                        <option value="ytd" <?= ($period === 'ytd') ? 'selected' : '' ?>>YTD</option>
                        <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom</option>
                    </select>
                    
                    <div id="custom-date-fields" class="<?= ($period === 'custom') ? 'flex' : 'hidden' ?> items-center gap-2 border-l border-gray-100 pl-2">
                        <input type="date" name="start_date_custom" value="<?= htmlspecialchars($custom_start) ?>" class="text-[10px] border-0 p-0 focus:ring-0">
                        <span class="text-gray-300 text-xs">to</span>
                        <input type="date" name="end_date_custom" value="<?= htmlspecialchars($custom_end) ?>" class="text-[10px] border-0 p-0 focus:ring-0">
                        <button type="submit" class="p-1 text-blue-600 hover:bg-blue-50 rounded"><i class="fas fa-check text-[10px]"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-10">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Revenue</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-gray-900"><?= format_currency($totalIncome) ?></span>
                </div>
                <div class="mt-4 flex items-center text-[10px] font-bold text-emerald-600 bg-emerald-50 w-fit px-2 py-0.5 rounded-full">
                    <i class="fas fa-arrow-up mr-1"></i> <?= $period_label ?>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expenses</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-gray-900"><?= format_currency($totalExpenses) ?></span>
                </div>
                <div class="mt-4 flex items-center text-[10px] font-bold text-rose-600 bg-rose-50 w-fit px-2 py-0.5 rounded-full">
                    <i class="fas fa-arrow-down mr-1"></i> Outflow
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Receivables</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-gray-900"><?= format_currency($outstandingFees) ?></span>
                </div>
                <div class="mt-4 flex items-center text-[10px] font-bold text-amber-600 bg-amber-50 w-fit px-2 py-0.5 rounded-full uppercase tracking-tighter">
                    Unpaid Invoices
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Enrollment</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-gray-900"><?= number_format($totalStudents) ?></span>
                </div>
                <div class="mt-4 flex items-center text-[10px] font-bold text-blue-600 bg-blue-50 w-fit px-2 py-0.5 rounded-full">
                    Active Students
                </div>
            </div>

            <div class="bg-gray-900 rounded-2xl p-6 shadow-xl">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Net Cash</p>
                <div class="mt-2">
                    <span class="text-2xl font-bold tracking-tight text-white"><?= format_currency($currentBalance) ?></span>
                </div>
                <div class="mt-4 text-[10px] font-medium text-gray-400">
                    Calculated for selected range
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-lg font-bold tracking-tight">Performance</h3>
                            <p class="text-xs text-gray-400 uppercase tracking-widest mt-0.5">Cash Flow Trend</p>
                        </div>
                        <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-tighter text-gray-400">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Income</span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Expenses</span>
                        </div>
                    </div>
                    <div class="h-[350px]">
                        <canvas id="financeChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-gray-900">Critical Alerts: Overdue</h3>
                        <?php if (!empty($overdueInvoices)): ?>
                            <span class="text-[10px] font-black text-rose-600 bg-rose-50 px-2 py-1 rounded border border-rose-100 uppercase">Attention Required</span>
                        <?php endif; ?>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <?php if (empty($overdueInvoices)): ?>
                            <div class="p-10 text-center">
                                <i class="fas fa-check-circle text-emerald-200 text-4xl mb-3"></i>
                                <p class="text-sm text-gray-500">No overdue invoices found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach(array_slice($overdueInvoices, 0, 5) as $inv): ?>
                                <div class="p-5 flex items-center justify-between group hover:bg-gray-50 transition-all">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-400 group-hover:bg-rose-100 group-hover:text-rose-600 transition-colors">
                                            <?= strtoupper(substr($inv['student_name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($inv['student_name']) ?></p>
                                            <p class="text-[10px] text-gray-400 uppercase tracking-widest">Invoice #<?= $inv['id'] ?> • Due: <?= date('M d', strtotime($inv['due_date'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-rose-600 font-mono tracking-tighter"><?= format_currency($inv['balance']) ?></p>
                                        <button class="text-[10px] font-bold text-blue-600 uppercase tracking-widest hover:underline mt-1">Send SMS</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                
                <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-6">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="create_invoice.php" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-gray-300 hover:shadow-sm transition-all flex flex-col items-center text-center group">
                            <i class="fas fa-plus-circle text-gray-300 group-hover:text-blue-500 mb-2 transition-colors"></i>
                            <span class="text-[10px] font-bold text-gray-600 uppercase tracking-tight">Invoice</span>
                        </a>
                        <a href="customer_center.php?tab=receive_payment" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-gray-300 hover:shadow-sm transition-all flex flex-col items-center text-center group">
                            <i class="fas fa-hand-holding-usd text-gray-300 group-hover:text-emerald-500 mb-2 transition-colors"></i>
                            <span class="text-[10px] font-bold text-gray-600 uppercase tracking-tight">Payment</span>
                        </a>
                        </div>
                </div>

                <?php if ($budgetSummary): ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Budget Usage</h3>
                        <span class="text-[10px] font-bold text-gray-900"><?= $budgetSummary['percentage_used'] ?>%</span>
                    </div>
                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-black rounded-full" style="width: <?= min($budgetSummary['percentage_used'], 100) ?>%"></div>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Spent</p>
                        <p class="text-sm font-bold text-gray-900"><?= format_currency($budgetSummary['total_actual']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-6">Outflow Breakdown</h3>
                    <div class="h-48">
                        <canvas id="expenseDonutChart"></canvas>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Standardize Chart.js Colors
    const colors = {
        emerald: '#10b981',
        rose: '#f43f5e',
        slate: '#64748b',
        zinc: '#27272a'
    };

    // --- Line Chart Refined ---
    const ctxLine = document.getElementById('financeChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Income',
                data: <?= json_encode($finalIncomeData) ?>,
                borderColor: colors.emerald,
                borderWidth: 2.5,
                backgroundColor: 'rgba(16, 185, 129, 0.03)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4
            }, {
                label: 'Expenses',
                data: <?= json_encode($finalExpenseData) ?>,
                borderColor: colors.rose,
                borderWidth: 2.5,
                backgroundColor: 'rgba(244, 63, 94, 0.03)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' } },
                y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { size: 10, family: 'monospace' }, color: '#94a3b8' } }
            }
        }
    });

    // --- Donut Chart Refined ---
    const ctxDonut = document.getElementById('expenseDonutChart').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($expenseCatLabels) ?>,
            datasets: [{
                data: <?= json_encode($expenseCatData) ?>,
                backgroundColor: ['#18181b', '#3f3f46', '#71717a', '#a1a1aa', '#e4e4e7'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Handle Custom Picker
    const periodSelect = document.getElementById('period-select');
    periodSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            document.getElementById('custom-date-fields').classList.remove('hidden');
            document.getElementById('custom-date-fields').classList.add('flex');
        } else {
            document.getElementById('period-selector-form').submit();
        }
    });
});
</script>
<?php include 'footer.php'; ob_end_flush(); ?>
