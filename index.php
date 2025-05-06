<?php
// dashboard.php - Main Dashboard for School Finance Management System
require 'config.php';
require 'functions.php';
include 'header.php';

// Calculate total students
$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
$totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

// Calculate total income (sum of all payments)
$stmt = $pdo->query("SELECT SUM(amount) as total_income FROM payments");
$totalIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total_income'] ?? 0;

// Calculate total expenses (if you have an expenses table)
$stmt = $pdo->query("SELECT SUM(amount) as total_expenses FROM expenses");
$totalExpenses = $stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'] ?? 0;

// Calculate current balance (income - expenses)
$currentBalance = $totalIncome - $totalExpenses;

// Get recent transactions (combining recent payments and expenses)
$recentTransactions = [];

// Get recent payments
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.payment_date as date,
        p.amount,
        'Payment' as type,
        s.name as related_name,
        i.id as reference_number
    FROM 
        payments p
    JOIN 
        invoices i ON p.invoice_id = i.id
    JOIN 
        students s ON i.student_id = s.id
    ORDER BY 
        p.payment_date DESC
    LIMIT 5
");
$recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$recentTransactions = array_merge($recentTransactions, $recentPayments);

// Get recent expenses (if you have an expenses table)
$stmt = $pdo->query("
    SELECT 
        e.id,
        e.transaction_date as date,
        e.amount,
        'Expense' as type,
        e.type as related_name,
        e.reference_number
    FROM 
        expenses e
    ORDER BY 
        e.transaction_date DESC
    LIMIT 5
");
if ($stmt) {
    $recentExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $recentTransactions = array_merge($recentTransactions, $recentExpenses);
}

// Sort transactions by date (newest first)
usort($recentTransactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$recentTransactions = array_slice($recentTransactions, 0, 5);

// Get overdue invoices
$stmt = $pdo->query("
    SELECT 
        i.id,
        i.id as invoice_number,
        i.invoice_date,
        i.due_date,
        i.total_amount,
        COALESCE(SUM(p.amount), 0) as paid_amount,
        i.total_amount - COALESCE(SUM(p.amount), 0) as balance,
        s.name as student_name,
        DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
    FROM 
        invoices i
    JOIN 
        students s ON i.student_id = s.id
    LEFT JOIN 
        payments p ON i.id = p.invoice_id
    WHERE 
        i.due_date < CURRENT_DATE
    GROUP BY 
        i.id
    HAVING 
        balance > 0
    ORDER BY 
        days_overdue DESC
    LIMIT 5
");
$overdueInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly income for chart (last 6 months)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount) as monthly_income
    FROM 
        payments
    WHERE 
        payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY 
        DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY 
        month ASC
");
$monthlyIncome = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly expenses for chart (last 6 months)
$monthlyExpenses = [];
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(amount) as monthly_expense
    FROM 
        expenses
    WHERE 
        transaction_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY 
        DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY 
        month ASC
");
if ($stmt) {
    $monthlyExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get top students by amount paid
$stmt = $pdo->query("
    SELECT 
        s.id,
        s.name,
        SUM(p.amount) as total_paid
    FROM 
        students s
    JOIN 
        invoices i ON s.id = i.student_id
    JOIN 
        payments p ON i.id = p.invoice_id
    GROUP BY 
        s.id
    ORDER BY 
        total_paid DESC
    LIMIT 5
");
$topStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if we should refresh the dashboard (used by other pages)
$refreshScript = '';
if (isset($_GET['refresh']) && $_GET['refresh'] == 'true') {
    $refreshScript = "<script>localStorage.removeItem('dashboard_needs_refresh');</script>";
}
?>

<h2>Dashboard</h2>
<?php echo $refreshScript; ?>

<div class="dashboard-container">
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="card summary-card">
            <div class="card-icon income-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="card-content">
                <h3>Total Income</h3>
                <p class="card-value">$<?php echo number_format($totalIncome, 2); ?></p>
            </div>
        </div>
        
        <div class="card summary-card">
            <div class="card-icon expense-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="card-content">
                <h3>Total Expenses</h3>
                <p class="card-value">$<?php echo number_format($totalExpenses, 2); ?></p>
            </div>
        </div>
        
        <div class="card summary-card">
            <div class="card-icon student-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="card-content">
                <h3>Total Students</h3>
                <p class="card-value"><?php echo $totalStudents; ?></p>
            </div>
        </div>
        
        <div class="card summary-card">
            <div class="card-icon balance-icon">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="card-content">
                <h3>Current Balance</h3>
                <p class="card-value <?php echo ($currentBalance >= 0) ? 'positive-balance' : 'negative-balance'; ?>">
                    $<?php echo number_format(abs($currentBalance), 2); ?>
                    <?php echo ($currentBalance < 0) ? '(Deficit)' : ''; ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
    <!-- Financial Chart - Wrapped in a regular grid item instead of full width -->
    <div class="card chart-card">
        <h3>Financial Overview (Last 6 Months)</h3>
        <div style="position: relative;">
            <canvas id="financeChart"></canvas>
        </div>
    </div>
        
        <!-- Recent Transactions -->
        <div class="card">
            <h3>Recent Transactions</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="4" class="no-data">No recent transactions</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo strtolower($transaction['type']); ?>">
                                        <?php echo $transaction['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($transaction['related_name']);
                                    if (!empty($transaction['reference_number'])) {
                                        echo ' (Ref: ' . htmlspecialchars($transaction['reference_number']) . ')';
                                    }
                                    ?>
                                </td>
                                <td class="amount <?php echo ($transaction['type'] == 'Expense') ? 'expense-amount' : 'income-amount'; ?>">
                                    <?php echo ($transaction['type'] == 'Expense') ? '-' : ''; ?>$<?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="card-footer">
                <a href="<?php echo ($recentTransactions[0]['type'] ?? '') == 'Payment' ? 'customer_center.php' : 'expenses.php'; ?>" class="btn-link">View All Transactions</a>
            </div>
        </div>
        
        <!-- Overdue Invoices -->
        <div class="card">
            <h3>Overdue Invoices</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Student</th>
                        <th>Due Date</th>
                        <th>Days Late</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($overdueInvoices)): ?>
                        <tr>
                            <td colspan="5" class="no-data">No overdue invoices</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($overdueInvoices as $invoice): ?>
                            <tr>
                                <td>
                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>">
                                        <?php echo $invoice['id']; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($invoice['student_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                <td>
                                    <span class="overdue-days">
                                        <?php echo $invoice['days_overdue']; ?> days
                                    </span>
                                </td>
                                <td class="amount">$<?php echo number_format($invoice['balance'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="card-footer">
                <a href="customer_center.php?tab=invoices" class="btn-link">View All Invoices</a>
            </div>
        </div>
        
        <!-- Top Students -->
        <div class="card">
            <h3>Top Students by Payment</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Total Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topStudents)): ?>
                        <tr>
                            <td colspan="2" class="no-data">No payment data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($topStudents as $student): ?>
                            <tr>
                                <td>
                                    <a href="customer_center.php?view_student=<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </a>
                                </td>
                                <td class="amount">$<?php echo number_format($student['total_paid'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="card-footer">
                <a href="customer_center.php" class="btn-link">View All Students</a>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    margin: 20px 0;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-card {
    display: flex;
    align-items: center;
    padding: 20px;
}

.card-icon {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 15px;
    font-size: 24px;
    color: white;
}

.income-icon {
    background-color: #4CAF50;
}

.expense-icon {
    background-color: #f44336;
}

.student-icon {
    background-color: #2196F3;
}

.balance-icon {
    background-color: #9C27B0;
}

.card-content {
    flex-grow: 1;
}

.card-content h3 {
    font-size: 16px;
    margin: 0 0 5px 0;
    color: #666;
}

.card-value {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
    color: #333;
}

.positive-balance {
    color: #4CAF50;
}

.negative-balance {
    color: #f44336;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}

/* Modified chart card class */
.chart-card {
    /* Remove the full-width setting */
    /* grid-column: 1 / -1; */
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
}

/* Add specific chart container height */
#financeChart {
    height: 300px;
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 14px;
}

.dashboard-table th,
.dashboard-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.dashboard-table th {
    text-align: left;
    color: #666;
    font-weight: 600;
}

.no-data {
    text-align: center;
    color: #999;
    padding: 20px 0;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.badge.payment {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge.expense {
    background: #ffebee;
    color: #c62828;
}

.amount {
    font-weight: bold;
    text-align: right;
}

.income-amount {
    color: #2e7d32;
}

.expense-amount {
    color: #c62828;
}

.overdue-days {
    color: #c62828;
    font-weight: bold;
}

.card-footer {
    margin-top: 15px;
    text-align: right;
}

.btn-link {
    color: #2196F3;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.btn-link:hover {
    text-decoration: underline;
}

@media screen and (max-width: 768px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-card {
        max-width: 100%;
    }
}

@media screen and (max-width: 480px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if dashboard needs refresh
    if (localStorage.getItem('dashboard_needs_refresh') === 'true') {
        localStorage.removeItem('dashboard_needs_refresh');
        location.reload();
    }
    
    // Finance Chart
    const ctx = document.getElementById('financeChart').getContext('2d');
    
    // Prepare data for chart
    const months = <?php 
        $chartMonths = [];
        foreach ($monthlyIncome as $data) {
            $date = date_create_from_format('Y-m', $data['month']);
            $chartMonths[] = date_format($date, 'M Y');
        }
        echo json_encode($chartMonths); 
    ?>;
    
    const incomeData = <?php 
        $incomeValues = [];
        foreach ($monthlyIncome as $data) {
            $incomeValues[] = floatval($data['monthly_income']);
        }
        echo json_encode($incomeValues); 
    ?>;
    
    const expenseData = <?php 
        $expenseValues = [];
        $incomeMonths = array_column($monthlyIncome, 'month');
        
        foreach ($incomeMonths as $month) {
            $found = false;
            foreach ($monthlyExpenses as $expense) {
                if ($expense['month'] === $month) {
                    $expenseValues[] = floatval($expense['monthly_expense']);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $expenseValues[] = 0;
            }
        }
        echo json_encode($expenseValues); 
    ?>;
    
    const balanceData = incomeData.map((income, index) => {
        return income - (expenseData[index] || 0);
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Income',
                    data: incomeData,
                    backgroundColor: 'rgba(76, 175, 80, 0.6)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: expenseData,
                    backgroundColor: 'rgba(244, 67, 54, 0.6)',
                    borderColor: 'rgba(244, 67, 54, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Balance',
                    data: balanceData,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(33, 150, 243, 1)',
                    tension: 0.1,
                    borderWidth: 3,
                    pointBackgroundColor: 'rgba(33, 150, 243, 1)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += '$' + context.parsed.y.toLocaleString();
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // Function to refresh dashboard data
    window.refreshDashboardData = function() {
        location.reload();
    };
});
</script>

<?php include 'footer.php'; ?>