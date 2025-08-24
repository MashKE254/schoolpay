<?php
/**
 * budget.php - Professional Budget Creation & Monitoring (v2.1)
 *
 * All form processing logic is handled before any HTML output to allow for redirects.
 * Gracefully handles cases where no revenue or expense accounts are budgeted.
 * CSS fix for header alignment.
 */

require_once 'config.php';
require_once 'functions.php';

// Session start is handled by config or functions, but ensure it's active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['school_id'])) {
    header("Location: login.php");
    exit();
}
$school_id = $_SESSION['school_id'];
$error_message = '';

// --- ALL POST REQUEST HANDLING (MOVED TO TOP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        $pdo->beginTransaction();

        if ($action === 'create_budget' && !empty($_POST['budget_name'])) {
            $name = trim($_POST['budget_name']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $budgeted_amounts = $_POST['budgeted_amount'] ?? [];

            // 1. Create the main budget record
            $stmt = $pdo->prepare("INSERT INTO budgets (school_id, name, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$school_id, $name, $start_date, $end_date]);
            $budget_id = $pdo->lastInsertId();

            // 2. Insert budget lines for each account with an amount
            $stmt_line = $pdo->prepare("INSERT INTO budget_lines (budget_id, account_id, budgeted_amount) VALUES (?, ?, ?)");
            foreach ($budgeted_amounts as $account_id => $amount) {
                if (!empty($amount) && is_numeric($amount)) {
                    $stmt_line->execute([$budget_id, $account_id, (float)$amount]);
                }
            }
            $pdo->commit();
            header("Location: budget.php?success=Budget created successfully!&view_budget=" . $budget_id);
            exit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}


// --- DATA FETCHING for Page Load (AFTER logic) ---
require_once 'header.php'; // HTML output begins here

// Get all available budgets for the dropdown
$budgets_stmt = $pdo->prepare("SELECT id, name, start_date, end_date FROM budgets WHERE school_id = ? AND status = 'active' ORDER BY start_date DESC");
$budgets_stmt->execute([$school_id]);
$available_budgets = $budgets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine which budget to display
$selected_budget_id = $_GET['view_budget'] ?? ($available_budgets[0]['id'] ?? null);
$budget_data = [];
$selected_budget_info = null;

if ($selected_budget_id) {
    // Get the selected budget's high-level info
    $info_stmt = $pdo->prepare("SELECT * FROM budgets WHERE id = ? AND school_id = ?");
    $info_stmt->execute([$selected_budget_id, $school_id]);
    $selected_budget_info = $info_stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_budget_info) {
        $budget_data = getBudgetVsActualsData($pdo, $selected_budget_id, $selected_budget_info['start_date'], $selected_budget_info['end_date'], $school_id);
    }
}

// Data for 'Create Budget' tab
$revenue_accounts = getAccountsByType($pdo, $school_id, 'revenue');
$expense_accounts = getAccountsByType($pdo, $school_id, 'expense');

?>

<style>
    .budget-report .progress-bar {
        background-color: #e9ecef;
        border-radius: 8px;
        height: 20px;
        width: 100%;
        overflow: hidden;
    }
    .budget-report .progress-fill {
        height: 100%;
        background-color: var(--secondary);
        text-align: center;
        color: white;
        font-size: 0.8em;
        line-height: 20px;
        white-space: nowrap;
        transition: width 0.6s ease;
    }
    /* Favorable variance (e.g., expenses under budget) */
    .budget-report .progress-fill.favorable { background-color: var(--success); }
    /* Unfavorable variance (e.g., expenses over budget) */
    .budget-report .progress-fill.unfavorable { background-color: var(--danger); }
    /* Nearing budget limit */
    .budget-report .progress-fill.warning { background-color: var(--warning); }
    .budget-report .variance-favorable { color: var(--success); font-weight: 600; }
    .budget-report .variance-unfavorable { color: var(--danger); font-weight: 600; }
    .budget-report tfoot tr { background-color: #f8f9fa; font-weight: 700; }
    .budget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap; 
        gap: 15px;
    }
    .budget-selector .form-group { margin-bottom: 0; }
    .budget-summary {
        background: #f1f5f9;
        border-radius: var(--border-radius);
        padding: 15px;
        text-align: center;
        margin-bottom: 30px;
    }
    /* --- FIX: Added this rule for header alignment --- */
    .budget-report .amount-header {
        text-align: right;
    }
</style>

<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-calculator"></i> Budgeting</h1>
        <p>Create and monitor your school's financial budgets.</p>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'overview')"><i class="fas fa-chart-pie"></i> Budget Overview</button>
        <button class="tab-link" onclick="openTab(event, 'create')"><i class="fas fa-plus"></i> Create New Budget</button>
    </div>

    <div id="overview" class="tab-content active">
        <div class="card">
            <div class="budget-header">
                <h2>Budget vs. Actuals Report</h2>
                <form class="budget-selector" method="GET">
                    <div class="form-group">
                        <label for="view_budget" style="display:inline-block; margin-right: 10px;">Select Budget:</label>
                        <select name="view_budget" id="view_budget" onchange="this.form.submit()">
                            <?php if (empty($available_budgets)): ?>
                                <option>No budgets created</option>
                            <?php else: ?>
                                <?php foreach ($available_budgets as $budget): ?>
                                    <option value="<?= $budget['id'] ?>" <?= ($budget['id'] == $selected_budget_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($budget['name']) ?> (<?= date('M Y', strtotime($budget['start_date'])) ?> - <?= date('M Y', strtotime($budget['end_date'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if ($selected_budget_info && !empty($budget_data)): ?>
                <div class="budget-summary">
                    <h4><?= htmlspecialchars($selected_budget_info['name']) ?></h4>
                    <p>Period: <?= date('F d, Y', strtotime($selected_budget_info['start_date'])) ?> to <?= date('F d, Y', strtotime($selected_budget_info['end_date'])) ?></p>
                </div>
                
                <h3>Revenue</h3>
                <div class="table-container budget-report">
                    <table>
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="amount-header">Budgeted</th>
                                <th class="amount-header">Actual</th>
                                <th class="amount-header">Variance</th>
                                <th style="width: 25%;">Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($budget_data['revenue']['lines'])): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 20px;">No revenue accounts were included in this budget.</td></tr>
                            <?php else: ?>
                                <?php foreach($budget_data['revenue']['lines'] as $line): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($line['account_name']) ?></td>
                                        <td class="amount">$<?= number_format($line['budgeted'], 2) ?></td>
                                        <td class="amount">$<?= number_format($line['actual'], 2) ?></td>
                                        <td class="amount <?= $line['variance_class'] ?>">$<?= number_format(abs($line['variance']), 2) ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill <?= $line['progress_class'] ?>" style="width:<?= $line['progress_percent'] ?>%;"><?= round($line['progress_percent']) ?>%</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Revenue</td>
                                <td class="amount">$<?= number_format($budget_data['revenue']['totals']['budgeted'], 2) ?></td>
                                <td class="amount">$<?= number_format($budget_data['revenue']['totals']['actual'], 2) ?></td>
                                <td class="amount <?= $budget_data['revenue']['totals']['variance_class'] ?>">$<?= number_format(abs($budget_data['revenue']['totals']['variance']), 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h3 style="margin-top: 30px;">Expenses</h3>
                <div class="table-container budget-report">
                    <table>
                         <thead>
                            <tr>
                                <th>Account</th>
                                <th class="amount-header">Budgeted</th>
                                <th class="amount-header">Actual</th>
                                <th class="amount-header">Variance</th>
                                <th style="width: 25%;">Performance</th>
                            </tr>
                        </thead>
                         <tbody>
                            <?php if (empty($budget_data['expense']['lines'])): ?>
                                 <tr><td colspan="5" style="text-align:center; padding: 20px;">No expense accounts were included in this budget.</td></tr>
                            <?php else: ?>
                                <?php foreach($budget_data['expense']['lines'] as $line): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($line['account_name']) ?></td>
                                        <td class="amount">$<?= number_format($line['budgeted'], 2) ?></td>
                                        <td class="amount">$<?= number_format($line['actual'], 2) ?></td>
                                        <td class="amount <?= $line['variance_class'] ?>">$<?= number_format(abs($line['variance']), 2) ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill <?= $line['progress_class'] ?>" style="width:<?= $line['progress_percent'] ?>%;"><?= round($line['progress_percent']) ?>%</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Expenses</td>
                                <td class="amount">$<?= number_format($budget_data['expense']['totals']['budgeted'], 2) ?></td>
                                <td class="amount">$<?= number_format($budget_data['expense']['totals']['actual'], 2) ?></td>
                                <td class="amount <?= $budget_data['expense']['totals']['variance_class'] ?>">$<?= number_format(abs($budget_data['expense']['totals']['variance']), 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h3 style="margin-top: 30px;">Net Income Summary</h3>
                 <div class="table-container budget-report">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="amount-header">Budgeted</th>
                                <th class="amount-header">Actual</th>
                                <th class="amount-header">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Net Income (Revenue - Expenses)</td>
                                <td class="amount">$<?= number_format($budget_data['net']['budgeted'], 2) ?></td>
                                <td class="amount">$<?= number_format($budget_data['net']['actual'], 2) ?></td>
                                <td class="amount <?= $budget_data['net']['variance_class'] ?>">$<?= number_format(abs($budget_data['net']['variance']), 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No budget selected or no data available.</p>
                    <small>Please create a budget or select one from the dropdown above to view the report.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="create" class="tab-content">
        <div class="card">
            <h2>Create New Budget</h2>
            <form action="budget.php" method="POST">
                <input type="hidden" name="action" value="create_budget">
                <div class="form-group">
                    <label for="budget_name">Budget Name</label>
                    <input type="text" name="budget_name" id="budget_name" placeholder="e.g., 2025-2026 Annual Budget" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?= date('Y-m-01') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?= date('Y-m-t') ?>" required>
                    </div>
                </div>

                <h3>Revenue Accounts</h3>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Account</th><th class="amount-header">Budgeted Amount</th></tr></thead>
                        <tbody>
                            <?php foreach($revenue_accounts as $account): ?>
                            <tr>
                                <td><?= htmlspecialchars($account['account_name']) ?></td>
                                <td><input type="number" step="0.01" name="budgeted_amount[<?= $account['id'] ?>]" class="amount" placeholder="0.00"></td>
                            </tr>
                             <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h3 style="margin-top: 30px;">Expense Accounts</h3>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Account</th><th class="amount-header">Budgeted Amount</th></tr></thead>
                        <tbody>
                            <?php foreach($expense_accounts as $account): ?>
                            <tr>
                                <td><?= htmlspecialchars($account['account_name']) ?></td>
                                <td><input type="number" step="0.01" name="budgeted_amount[<?= $account['id'] ?>]" class="amount" placeholder="0.00"></td>
                            </tr>
                             <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                 <div class="form-actions">
                    <button type="submit" class="btn-success"><i class="fas fa-save"></i> Create Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove('active'));
        document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }
    
    // Ensure the correct tab is active on page load based on URL parameter or default to the first
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab') || 'overview';
        const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
        if (tabButton) {
            tabButton.click();
        } else {
            // Fallback to the first tab if the specified one doesn't exist
            document.querySelector('.tab-link').click();
        }
    });
</script>