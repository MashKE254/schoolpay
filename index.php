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

<!-- Import necessary fonts and icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

<?php echo $refreshScript; ?>

<div class="dashboard-container">
    <!-- Enhanced Summary Cards with Animations -->
    <div class="summary-cards">
        <div class="summary-card income-card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-background"></div>
            <div class="card-icon">
                <i class="fas fa-arrow-trend-up"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Total Income</div>
                <div class="card-value">$<?php echo number_format($totalIncome, 2); ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +12.5% from last month
                </div>
            </div>
            <div class="card-chart">
                <div class="mini-chart" id="incomeChart"></div>
            </div>
        </div>
        
        <div class="summary-card expense-card" data-aos="fade-up" data-aos-delay="200">
            <div class="card-background"></div>
            <div class="card-icon">
                <i class="fas fa-arrow-trend-down"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Total Expenses</div>
                <div class="card-value">$<?php echo number_format($totalExpenses, 2); ?></div>
                <div class="card-trend negative">
                    <i class="fas fa-arrow-up"></i> +5.2% from last month
                </div>
            </div>
            <div class="card-chart">
                <div class="mini-chart" id="expenseChart"></div>
            </div>
        </div>
        
        <div class="summary-card student-card" data-aos="fade-up" data-aos-delay="300">
            <div class="card-background"></div>
            <div class="card-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Total Students</div>
                <div class="card-value"><?php echo number_format($totalStudents); ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +3.8% from last month
                </div>
            </div>
            <div class="progress-ring">
                <svg width="60" height="60">
                    <circle cx="30" cy="30" r="25" stroke="#e0e7ff" stroke-width="4" fill="none"/>
                    <circle cx="30" cy="30" r="25" stroke="#3b82f6" stroke-width="4" fill="none" 
                            stroke-dasharray="157" stroke-dashoffset="39" class="progress-circle"/>
                </svg>
            </div>
        </div>
        
        <div class="summary-card balance-card" data-aos="fade-up" data-aos-delay="400">
            <div class="card-background"></div>
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
            <div class="balance-indicator">
                <div class="indicator-bar <?php echo ($currentBalance >= 0) ? 'positive' : 'negative'; ?>"></div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Enhanced Financial Chart -->
        <div class="chart-container" data-aos="fade-up" data-aos-delay="500">
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
        
        <!-- Enhanced Recent Transactions -->
        <div class="transactions-card" data-aos="fade-up" data-aos-delay="600">
            <div class="card-header">
                <h3><i class="fas fa-clock-rotate-left"></i> Recent Transactions</h3>
                <a href="<?php echo ($recentTransactions[0]['type'] ?? '') == 'Payment' ? 'customer_center.php' : 'expenses.php'; ?>" class="view-all-btn">
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
        
        <!-- Enhanced Overdue Invoices -->
        <div class="overdue-card" data-aos="fade-up" data-aos-delay="700">
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
                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="invoice-link">
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
                <a href="customer_center.php?tab=invoices" class="view-all-btn">
                    Manage Invoices <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Enhanced Top Students -->
        <div class="students-card" data-aos="fade-up" data-aos-delay="800">
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
                                <a href="customer_center.php?view_student=<?php echo $student['id']; ?>" class="student-link">
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
                <a href="customer_center.php" class="view-all-btn">
                    View All Students <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

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
    
    // Check if dashboard needs refresh
    if (localStorage.getItem('dashboard_needs_refresh') === 'true') {
        localStorage.removeItem('dashboard_needs_refresh');
        location.reload();
    }
    
    // Enhanced Finance Chart
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
                                label += context.parsed.y.toLocaleString();
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
                            return value.toLocaleString();
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
            // Here you would typically reload data for the selected period
        });
    });
    
    // Chart period selector
    document.getElementById('chartPeriod')?.addEventListener('change', function() {
        // Here you would typically reload chart data for the selected period
        console.log('Period changed to:', this.value);
    });
    
    // Animate progress bars and counters
    setTimeout(() => {
        // Animate student ranking progress bars
        document.querySelectorAll('.progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
        
        // Animate counter values
        animateCounters();
    }, 500);
    
    // Function to refresh dashboard data
    window.refreshDashboardData = function() {
        // Add loading animation
        const refreshBtn = document.querySelector('.refresh-btn');
        const icon = refreshBtn.querySelector('i');
        
        icon.style.animation = 'spin 1s linear infinite';
        refreshBtn.disabled = true;
        
        setTimeout(() => {
            location.reload();
        }, 1000);
    };
    
    // Add loading states to action buttons
    document.querySelectorAll('.view-all-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.animation = 'pulse 0.5s ease-in-out';
            }
        });
    });
});

// Animate counter function
function animateCounters() {
    document.querySelectorAll('.card-value').forEach(counter => {
        const target = parseFloat(counter.textContent.replace(/[,$]/g, ''));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = counter.textContent; // Keep original format
                clearInterval(timer);
            } else {
                const formatted = current.toLocaleString();
                if (counter.textContent.includes(',')) {
                    counter.textContent = formatted.split('.')[0];
                } else {
                    counter.textContent = formatted.split('.')[0];
                }
            }
        }, 16);
    });
}

// Add CSS animation for refresh button
const style = document.createElement('style');
style.textContent = `
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);
</script>

<?php include 'footer.php'; ?>