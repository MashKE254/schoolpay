<?php
// dashboard.php - Main Dashboard for School Finance Management System
require 'config.php';
require 'functions.php';
include 'header.php';

// Calculate total students - FIXED WITH ERROR HANDLING
$totalStudents = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalStudents = $result['total_students'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Student count error: " . $e->getMessage());
    $totalStudents = 0;
}

// Calculate total income (sum of all payments)
$totalIncome = 0;
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_income 
                          FROM payments 
                          WHERE payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)");
    $stmt->execute();
    $totalIncome = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Income calculation error: " . $e->getMessage());
    $totalIncome = 0;
}

// Calculate total expenses
$totalExpenses = 0;
try {
    $stmt = $pdo->query("SELECT SUM(amount) as total_expenses FROM expenses");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalExpenses = $result['total_expenses'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Expense calculation error: " . $e->getMessage());
    $totalExpenses = 0;
}

// Calculate current balance (income - expenses)
$currentBalance = $totalIncome - $totalExpenses;

// Get recent transactions (combining recent payments and expenses)
$recentTransactions = [];

// Get recent payments
try {
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
    if ($stmt) {
        $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $recentTransactions = array_merge($recentTransactions, $recentPayments);
    }
} catch (PDOException $e) {
    error_log("Recent payments error: " . $e->getMessage());
}

// Get recent expenses
try {
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
} catch (PDOException $e) {
    error_log("Recent expenses error: " . $e->getMessage());
}

// Sort transactions by date (newest first)
usort($recentTransactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$recentTransactions = array_slice($recentTransactions, 0, 5);

// Get overdue invoices
$overdueInvoices = [];
try {
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
    if ($stmt) {
        $overdueInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Overdue invoices error: " . $e->getMessage());
}

// Get monthly income for chart (last 6 months)
$monthlyIncome = [];
try {
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
    if ($stmt) {
        $monthlyIncome = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Monthly income error: " . $e->getMessage());
}

// Get monthly expenses for chart (last 6 months)
$monthlyExpenses = [];
try {
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
} catch (PDOException $e) {
    error_log("Monthly expenses error: " . $e->getMessage());
}

// Generate chart data for last 6 months
$currentDate = new DateTime();
$monthKeys = [];
$monthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $monthKeys[] = $date->format('Y-m');
    $monthLabels[] = $date->format('M');
}

$chartIncomeData = array_fill(0, 6, 0);
$chartExpenseData = array_fill(0, 6, 0);

foreach ($monthlyIncome as $income) {
    $index = array_search($income['month'], $monthKeys);
    if ($index !== false) {
        $chartIncomeData[$index] = (float)$income['monthly_income'];
    }
}

foreach ($monthlyExpenses as $expense) {
    $index = array_search($expense['month'], $monthKeys);
    if ($index !== false) {
        $chartExpenseData[$index] = (float)$expense['monthly_expense'];
    }
}

// Get top students by amount paid
$topStudents = [];
try {
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
    if ($stmt) {
        $topStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Top students error: " . $e->getMessage());
}

// Check if we should refresh the dashboard (used by other pages)
$refreshScript = '';
if (isset($_GET['refresh']) && $_GET['refresh'] == 'true') {
    $refreshScript = "<script>localStorage.removeItem('dashboard_needs_refresh');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Finance Dashboard</title>
    
    <!-- Import necessary fonts and icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === New Design Styles to Match reports.php === */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #1abc9c;
            --light: #ecf0f1;
            --dark: #34495e;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --card-bg: #ffffff;
            --border: #dfe6e9;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
            color: #333;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--secondary);
        }

        .dashboard-title h1 {
            font-size: 28px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
        }

        .dashboard-title h1 i {
            color: var(--secondary);
            font-size: 32px;
        }

        .dashboard-title p {
            color: var(--dark);
            font-size: 16px;
            margin-left: 47px;
        }

        .dashboard-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .refresh-btn {
            background: var(--light);
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: var(--secondary);
            color: white;
        }

        .refresh-btn i {
            transition: transform 0.3s ease;
        }

        .last-updated {
            background: rgba(52, 152, 219, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--secondary);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .income-card {
            border-top: 4px solid var(--success);
        }

        .expense-card {
            border-top: 4px solid var(--warning);
        }

        .student-card {
            border-top: 4px solid var(--secondary);
        }

        .balance-card {
            border-top: 4px solid var(--accent);
        }

        .card-icon {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 50px;
            height: 50px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--success);
        }

        .expense-card .card-icon {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .student-card .card-icon {
            background: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
        }

        .balance-card .card-icon {
            background: rgba(26, 188, 156, 0.1);
            color: var(--accent);
        }

        .card-label {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .card-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .income-card .card-value {
            color: var(--success);
        }

        .expense-card .card-value {
            color: var(--warning);
        }

        .student-card .card-value {
            color: var(--secondary);
        }

        .balance-card .card-value {
            color: var(--accent);
        }

        .card-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(16, 185, 129, 0.1);
            width: fit-content;
        }

        .positive {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .negative {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
        }

        .chart-container, .transactions-card, 
        .overdue-card, .students-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow);
            height: 100%;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-size: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .view-all-btn {
            background: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: var(--secondary);
            color: white;
        }

        .priority-indicator {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .priority-indicator span {
            background: var(--danger);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .transactions-list, .overdue-list, .students-ranking {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .transaction-item, .overdue-item, .student-rank-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .transaction-item:hover, .overdue-item:hover, .student-rank-item:hover {
            background: #e3f2fd;
            transform: translateX(5px);
        }

        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }

        .transaction-icon.payment {
            background: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .transaction-icon.expense {
            background: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }

        .transaction-details {
            flex: 1;
        }

        .transaction-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .transaction-meta {
            font-size: 13px;
            color: var(--dark);
        }

        .transaction-amount {
            font-weight: 700;
            font-size: 18px;
        }

        .transaction-amount.payment {
            color: var(--success);
        }

        .transaction-amount.expense {
            color: var(--danger);
        }

        .overdue-priority {
            margin-right: 15px;
        }

        .priority-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
        }

        .priority-dot.medium {
            background: var(--warning);
        }

        .priority-dot.high {
            background: var(--danger);
        }

        .priority-dot.critical {
            background: #c0392b;
        }

        .invoice-info {
            display: flex;
            flex-direction: column;
            margin-bottom: 6px;
        }

        .invoice-link {
            font-weight: 600;
            color: var(--secondary);
            text-decoration: none;
            margin-bottom: 4px;
        }

        .invoice-link:hover {
            text-decoration: underline;
        }

        .student-name {
            font-size: 14px;
            color: var(--dark);
        }

        .overdue-meta {
            font-size: 13px;
            color: var(--dark);
        }

        .days-overdue {
            color: var(--danger);
            font-weight: 600;
        }

        .overdue-amount {
            font-weight: 700;
            color: var(--danger);
            font-size: 18px;
        }

        .rank-number {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--secondary);
            font-size: 20px;
            margin-right: 15px;
            position: relative;
        }

        .rank-medal {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 24px;
        }

        .rank-1 {
            color: #ffd700; /* Gold */
        }

        .rank-2 {
            color: #c0c0c0; /* Silver */
        }

        .rank-3 {
            color: #cd7f32; /* Bronze */
        }

        .student-info {
            flex: 1;
        }

        .student-link {
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
        }

        .student-link:hover {
            color: var(--secondary);
        }

        .payment-progress {
            width: 100%;
        }

        .progress-bar {
            height: 8px;
            background: #e0e7ff;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--secondary);
            border-radius: 4px;
            transition: width 1s ease;
        }

        .student-amount {
            font-weight: 700;
            color: var(--secondary);
            font-size: 18px;
        }

        .card-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark);
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .empty-state small {
            font-size: 14px;
            color: #94a3b8;
        }

        .chart-wrapper {
            height: 300px;
            margin-top: 20px;
        }

        /* Responsive Adjustments */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .dashboard-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        /* Animation for progress bars */
        @keyframes progressFill {
            from { width: 0; }
            to { width: var(--progress-width); }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><i class="fas fa-chart-line"></i> Finance Dashboard</h1>
                <p>Real-time overview of your school's financial performance</p>
            </div>
            <div class="dashboard-actions">
                <button class="refresh-btn" onclick="refreshDashboardData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <div class="last-updated">
                    Last updated: <?php echo date('M d, Y h:i A'); ?>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <!-- Income Card -->
            <div class="summary-card income-card">
                <div class="card-icon">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Total Income</div>
                    <div class="card-value">$<?php echo number_format($totalIncome, 2); ?></div>
                    <div class="card-trend">
                        <i class="fas fa-calendar"></i> Last 6 months
                    </div>
                </div>
            </div>
            
            <!-- Expense Card -->
            <div class="summary-card expense-card">
                <div class="card-icon">
                    <i class="fas fa-arrow-trend-down"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Total Expenses</div>
                    <div class="card-value">$<?php echo number_format($totalExpenses, 2); ?></div>
                    <div class="card-trend">
                        <i class="fas fa-calendar"></i> Last 6 months
                    </div>
                </div>
            </div>
            
            <!-- Student Card -->
            <div class="summary-card student-card">
                <div class="card-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Total Students</div>
                    <div class="card-value"><?php echo number_format($totalStudents); ?></div>
                    <div class="card-trend">
                        <i class="fas fa-calendar"></i> Current total
                    </div>
                </div>
            </div>
            
            <!-- Balance Card -->
            <div class="summary-card balance-card">
                <div class="card-icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Net Balance</div>
                    <div class="card-value <?php echo ($currentBalance >= 0) ? 'positive' : 'negative'; ?>">
                        $<?php echo number_format(abs($currentBalance), 2); ?>
                        <?php echo ($currentBalance < 0) ? '(Deficit)' : ''; ?>
                    </div>
                    <div class="card-trend <?php echo ($currentBalance >= 0) ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-<?php echo ($currentBalance >= 0) ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo ($currentBalance >= 0) ? 'Healthy' : 'Needs Attention'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Financial Chart -->
            <div class="chart-container">
                <div class="card-header">
                    <h3><i class="fas fa-chart-area"></i> Financial Overview</h3>
                    <div class="chart-controls">
                        <select id="chartPeriod" class="select-input">
                            <option value="6">Last 6 Months</option>
                            <option value="12">Last 12 Months</option>
                            <option value="24">Last 24 Months</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="transactions-card">
                <div class="card-header">
                    <h3><i class="fas fa-clock-rotate-left"></i> Recent Transactions</h3>
                    <a href="#" class="view-all-btn">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="transactions-list">
                    <?php if (empty($recentTransactions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>No recent transactions</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recentTransactions as $index => $transaction): ?>
                            <div class="transaction-item" style="animation-delay: <?php echo ($index * 0.1); ?>s">
                                <div class="transaction-icon <?php echo strtolower($transaction['type']); ?>">
                                    <i class="fas fa-<?php echo ($transaction['type'] == 'Payment') ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-name"><?php echo htmlspecialchars($transaction['related_name']); ?></div>
                                    <div class="transaction-meta">
                                        <?php echo date('M d, Y', strtotime($transaction['date'])); ?>
                                        <?php if (!empty($transaction['reference_number'])): ?>
                                            • Ref: <?php echo htmlspecialchars($transaction['reference_number']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="transaction-amount <?php echo strtolower($transaction['type']); ?>">
                                    <?php echo ($transaction['type'] == 'Expense') ? '-' : '+'; ?>$<?php echo number_format($transaction['amount'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Overdue Invoices -->
            <div class="overdue-card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Overdue Invoices</h3>
                    <div class="priority-indicator high">
                        <span><?php echo count($overdueInvoices); ?></span> Overdue
                    </div>
                </div>
                <div class="overdue-list">
                    <?php if (empty($overdueInvoices)): ?>
                        <div class="empty-state success">
                            <i class="fas fa-check-circle"></i>
                            <p>No overdue invoices</p>
                            <small>All payments are up to date!</small>
                        </div>
                    <?php else: ?>
                        <?php foreach($overdueInvoices as $index => $invoice): ?>
                            <div class="overdue-item" style="animation-delay: <?php echo ($index * 0.1); ?>s">
                                <div class="overdue-priority">
                                    <div class="priority-dot <?php echo ($invoice['days_overdue'] > 30) ? 'critical' : (($invoice['days_overdue'] > 15) ? 'high' : 'medium'); ?>"></div>
                                </div>
                                <div class="overdue-details">
                                    <div class="invoice-info">
                                        <a href="#" class="invoice-link">
                                            Invoice #<?php echo $invoice['id']; ?>
                                        </a>
                                        <span class="student-name"><?php echo htmlspecialchars($invoice['student_name']); ?></span>
                                    </div>
                                    <div class="overdue-meta">
                                        Due: <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?> • 
                                        <span class="days-overdue"><?php echo $invoice['days_overdue']; ?> days late</span>
                                    </div>
                                </div>
                                <div class="overdue-amount">
                                    $<?php echo number_format($invoice['balance'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="#" class="view-all-btn">
                        Manage Invoices <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Top Students -->
            <div class="students-card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Top Contributing Students</h3>
                    <div class="period-toggle">
                        <button class="toggle-btn active" data-period="month">Month</button>
                        <button class="toggle-btn" data-period="year">Year</button>
                    </div>
                </div>
                <div class="students-ranking">
                    <?php if (empty($topStudents)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No payment data available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($topStudents as $index => $student): ?>
                            <div class="student-rank-item" style="animation-delay: <?php echo ($index * 0.1); ?>s">
                                <div class="rank-number">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <?php if ($index < 3): ?>
                                        <i class="fas fa-medal rank-medal rank-<?php echo $index + 1; ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="student-info">
                                    <a href="#" class="student-link">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </a>
                                    <div class="payment-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min(100, ($student['total_paid'] / max($topStudents[0]['total_paid'], 1)) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="student-amount">
                                    $<?php echo number_format($student['total_paid'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="#" class="view-all-btn">
                        View All Students <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass chart data to JavaScript -->
    <script>
        window.chartData = {
            labels: <?= json_encode($monthLabels) ?>,
            income: <?= json_encode($chartIncomeData) ?>,
            expenses: <?= json_encode($chartExpenseData) ?>
        };
    </script>

    <!-- Include Chart.js and AOS Animation Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: true,
                offset: 100
            });
            
            // Enhanced Finance Chart
            const ctx = document.getElementById('financeChart').getContext('2d');
            
            // Use actual chart data from PHP
            const months = window.chartData.labels;
            const incomeData = window.chartData.income;
            const expenseData = window.chartData.expenses;
            
            // Create gradient backgrounds
            const incomeGradient = ctx.createLinearGradient(0, 0, 0, 400);
            incomeGradient.addColorStop(0, 'rgba(16, 185, 129, 0.8)');
            incomeGradient.addColorStop(1, 'rgba(16, 185, 129, 0.1)');
            
            const expenseGradient = ctx.createLinearGradient(0, 0, 0, 400);
            expenseGradient.addColorStop(0, 'rgba(239, 68, 68, 0.8)');
            expenseGradient.addColorStop(1, 'rgba(239, 68, 68, 0.1)');
            
            const financeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Income',
                            data: incomeData,
                            backgroundColor: incomeGradient,
                            borderColor: '#10b981',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        },
                        {
                            label: 'Expenses',
                            data: expenseData,
                            backgroundColor: expenseGradient,
                            borderColor: '#ef4444',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ef4444',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 14,
                                    weight: 600
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#374151',
                            borderWidth: 1,
                            cornerRadius: 12,
                            padding: 16,
                            displayColors: true,
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
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                padding: 12,
                                font: {
                                    size: 12,
                                    weight: 500
                                },
                                color: '#64748b',
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 12,
                                font: {
                                    size: 12,
                                    weight: 500
                                },
                                color: '#64748b'
                            }
                        }
                    }
                }
            });
            
            // Period toggle functionality
            document.querySelectorAll('.toggle-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Animate progress bars
            setTimeout(() => {
                document.querySelectorAll('.progress-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 500);
            
            // Function to refresh dashboard data
            window.refreshDashboardData = function() {
                const refreshBtn = document.querySelector('.refresh-btn');
                const icon = refreshBtn.querySelector('i');
                
                icon.style.animation = 'spin 1s linear infinite';
                refreshBtn.disabled = true;
                
                setTimeout(() => {
                    location.reload();
                }, 1000);
            };
            
            // Add animation for refresh button
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>