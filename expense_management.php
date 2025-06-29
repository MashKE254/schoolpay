<?php
session_start();
require 'config.php';
require 'functions.php';

// Initialize message variables
$success_message = '';
$error_messages = [];

// Handle new account submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_account') {
    $account_code = $_POST['account_code'];
    $account_name = $_POST['account_name'];
    $account_type = $_POST['account_type'];
    $opening_balance = $_POST['opening_balance'] ?? 0;
    
    // Validate inputs
    $errors = [];
    
    // Check if account code already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE account_code = ?");
    $stmt->execute([$account_code]);
    if ($stmt->fetchColumn() > 0) {
        $error_messages[] = "Account code already exists";
    }
    
    if (empty($error_messages)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO accounts (account_code, account_name, account_type, balance) VALUES (?, ?, ?, ?)");
            $stmt->execute([$account_code, $account_name, $account_type, $opening_balance]);
            
            // Set success message
            $success_message = "Account added successfully!";
        } catch (PDOException $e) {
            $error_messages[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle account edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_account') {
    $id = $_POST['id'];
    $account_code = $_POST['account_code'];
    $account_name = $_POST['account_name'];
    $account_type = $_POST['account_type'];
    
    // Check if new code exists for other accounts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE account_code = ? AND id != ?");
    $stmt->execute([$account_code, $id]);
    if ($stmt->fetchColumn() > 0) {
        $error_messages[] = "Account code already exists for another account";
    }

    if (empty($error_messages)) {
        try {
            $stmt = $pdo->prepare("UPDATE accounts SET account_code = ?, account_name = ?, account_type = ? WHERE id = ?");
            $stmt->execute([$account_code, $account_name, $account_type, $id]);
            $success_message = "Account updated successfully!";
        } catch (PDOException $e) {
            $error_messages[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $id = $_POST['id'];
    
    try {
        // Check if account has transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE account_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error_messages[] = "Cannot delete account with existing transactions";
        } else {
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Account deleted successfully!";
        }
    } catch (PDOException $e) {
        $error_messages[] = "Database error: " . $e->getMessage();
    }
}

// Get all accounts
$accounts = $pdo->query("SELECT * FROM accounts ORDER BY account_code ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all transactions for the history tab - FIXED with correct joins
$transactions = $pdo->query("
    SELECT 
        p.id, 
        p.payment_date AS transaction_date,
        CONCAT('Payment from ', s.name) AS description,
        p.amount,
        'credit' AS transaction_type,
        a.account_code,
        a.account_name
    FROM 
        payments p
    JOIN 
        invoices i ON p.invoice_id = i.id
    JOIN 
        students s ON i.student_id = s.id
    JOIN 
        accounts a ON p.coa_account_id = a.id
    UNION ALL
    SELECT 
        e.id, 
        e.transaction_date,
        e.description,
        e.amount,
        e.transaction_type,
        a.account_code,
        a.account_name
    FROM 
        expenses e
    JOIN 
        accounts a ON e.account_id = a.id
    ORDER BY 
        transaction_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Style for the collapsible form */
        .account-form {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out;
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 0;
        }
        .account-form.show {
            max-height: 500px; /* Adjust this value to fit your form content */
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Expense Management System</h1>
        
        <div class="tab-container">
            <div class="tabs">
                <button class="tab-link active" onclick="openTab(event, 'charts')">
                    <i class="fas fa-chart-pie"></i> General Ledger
                </button>
                <button class="tab-link" onclick="openTab(event, 'journal')">
                    <i class="fas fa-book"></i> Journal Entry
                </button>
                <button class="tab-link" onclick="openTab(event, 'service')">
                    <i class="fas fa-hand-holding-usd"></i> Service Payments
                </button>
                <button class="tab-link" onclick="openTab(event, 'supplier')">
                    <i class="fas fa-truck-loading"></i> Supplier Payments
                </button>
                <button class="tab-link" onclick="openTab(event, 'vehicle')">
                    <i class="fas fa-car"></i> Vehicle Expenses
                </button>
                <button class="tab-link" onclick="openTab(event, 'history')">
                    <i class="fas fa-history"></i> Transaction History
                </button>
            </div>

            <div id="charts" class="tab-content active">
                <div class="card">
                    <h2>General Ledger</h2>
                    
                    <div class="add-account-section">
                        <?php if (!empty($success_message)): ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_messages)): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php foreach ($error_messages as $error): ?>
                                    <p><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <button type="button" class="toggle-form" onclick="toggleAccountForm()">
                            <i class="fas fa-plus"></i> Add New Account
                        </button>
                        
                        <div id="accountForm" class="account-form">
                            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                                <input type="hidden" name="action" value="add_account">
                                <div class="form-group">
                                    <label for="account_code">Account Code</label>
                                    <input type="text" id="account_code" name="account_code" required>
                                </div>
                                <div class="form-group">
                                    <label for="account_name">Account Name</label>
                                    <input type="text" id="account_name" name="account_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="account_type">Account Type</label>
                                    <select id="account_type" name="account_type" required>
                                        <option value="Assets">Assets</option>
                                        <option value="Liabilities">Liabilities</option>
                                        <option value="Equity">Equity</option>
                                        <option value="Revenue">Revenue</option>
                                        <option value="Expenses">Expenses</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="opening_balance">Opening Balance</label>
                                    <input type="number" id="opening_balance" name="opening_balance" step="0.01" value="0.00">
                                </div>
                                <button type="submit" class="btn-success">
                                    <i class="fas fa-plus-circle"></i> Add Account
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Account Name</th>
                                    <th>Type</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="7" class="no-results">No accounts found. Add your first account above.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $totalDebit = 0;
                                    $totalCredit = 0;
                                    $totalBalance = 0;
                                    ?>
                                    <?php foreach ($accounts as $account): ?>
                                    <?php
                                        // Calculate debits and credits for this account
                                        $stmt = $pdo->prepare("
                                            SELECT 
                                                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit,
                                                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit
                                            FROM expenses 
                                            WHERE account_id = ?
                                        ");
                                        $stmt->execute([$account['id']]);
                                        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        $accountDebit = $totals['total_debit'] ?? 0;
                                        $accountCredit = $totals['total_credit'] ?? 0;
                                        $accountBalance = $account['balance'];
                                        
                                        $totalDebit += $accountDebit;
                                        $totalCredit += $accountCredit;
                                        $totalBalance += $accountBalance;
                                    ?>
                                    <tr data-account-id="<?= $account['id'] ?>">
                                        <td><?= htmlspecialchars($account['account_code']) ?></td>
                                        <td><?= htmlspecialchars($account['account_name']) ?></td>
                                        <td><?= htmlspecialchars($account['account_type']) ?></td>
                                        <td class="transaction-debit">$<?= number_format($accountDebit, 2) ?></td>
                                        <td class="transaction-credit">$<?= number_format($accountCredit, 2) ?></td>
                                        <td>$<?= number_format($accountBalance, 2) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-edit" onclick="openEditModal(<?= $account['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-delete" onclick="confirmDelete(<?= $account['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="3"><strong>Totals</strong></td>
                                        <td class="transaction-debit"><strong>$<?= number_format($totalDebit, 2) ?></strong></td>
                                        <td class="transaction-credit"><strong>$<?= number_format($totalCredit, 2) ?></strong></td>
                                        <td><strong>$<?= number_format($totalBalance, 2) ?></strong></td>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="journal" class="tab-content">
                <div class="card">
                    <h2>Journal Entry</h2>
                    <form id="journalForm" onsubmit="submitJournal(event)">
                        <div class="form-group">
                            <label>Entry Date</label>
                            <input type="date" name="entry_date" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        <div id="journalLines">
                            <div class="journal-line">
                                <select name="account[]" required>
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="debit[]" step="0.01" placeholder="Debit">
                                <input type="number" name="credit[]" step="0.01" placeholder="Credit">
                            </div>
                        </div>
                        <button type="button" class="btn-filter" onclick="addJournalLine()">
                            <i class="fas fa-plus"></i> Add Line
                        </button>
                        <div id="journalTotal" class="form-group" style="font-weight: 700; padding: 15px 0;">
                            Total Debit: $0.00 | Total Credit: $0.00
                        </div>
                        <button type="submit" class="btn-success">
                            <i class="fas fa-save"></i> Post Entry
                        </button>
                    </form>
                </div>
            </div>

            <div id="service" class="tab-content">
                <div class="card">
                    <h2>Service Provider Payments</h2>
                    <form id="serviceForm" onsubmit="submitServicePayment(event)">
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input type="date" name="payment_date" required>
                        </div>
                        <div class="form-group">
                            <label>Provider Name</label>
                            <input type="text" name="provider_name" required>
                        </div>
                        <div class="form-group">
                            <label>Account</label>
                            <select name="account_id" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn-success">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                    </form>
                </div>
            </div>

            <div id="supplier" class="tab-content">
                <div class="card">
                    <h2>Supplier Payments</h2>
                    <form id="supplierForm" onsubmit="submitSupplierPayment(event)">
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input type="date" name="payment_date" required>
                        </div>
                        <div class="form-group">
                            <label>Supplier Name</label>
                            <input type="text" name="supplier_name" required>
                        </div>
                        <div class="form-group">
                            <label>Invoice Number</label>
                            <input type="text" name="invoice_number">
                        </div>
                        <div class="form-group">
                            <label>Account</label>
                            <select name="account_id" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn-success">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                    </form>
                </div>
            </div>

            <div id="vehicle" class="tab-content">
                <div class="card">
                    <h2>Vehicle Expenses</h2>
                    <form id="vehicleForm" onsubmit="submitVehicleExpense(event)">
                        <div class="form-group">
                            <label>Expense Date</label>
                            <input type="date" name="expense_date" required>
                        </div>
                        <div class="form-group">
                            <label>Vehicle ID/Registration</label>
                            <input type="text" name="vehicle_id" required>
                        </div>
                        <div class="form-group">
                            <label>Expense Type</label>
                            <select name="expense_type" required>
                                <option value="fuel">Fuel</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="insurance">Insurance</option>
                                <option value="repairs">Repairs</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account</label>
                            <select name="account_id" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Odometer Reading</label>
                            <input type="number" name="odometer" step="0.1">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn-success">
                            <i class="fas fa-save"></i> Record Expense
                        </button>
                    </form>
                </div>
            </div>

            <div id="history" class="tab-content">
                <div class="card">
                    <h2>Transaction History</h2>
                    
                    <div class="filter-controls">
                        <div class="form-group">
                            <label for="filter-date-from">From Date</label>
                            <input type="date" id="filter-date-from">
                        </div>
                        <div class="form-group">
                            <label for="filter-date-to">To Date</label>
                            <input type="date" id="filter-date-to">
                        </div>
                        <div class="form-group">
                            <label for="filter-account">Account</label>
                            <select id="filter-account">
                                <option value="">All Accounts</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= htmlspecialchars($acc['account_code']) ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter-type">Transaction Type</label>
                            <select id="filter-type">
                                <option value="">All Types</option>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" id="transaction-search" placeholder="Search by description..." style="width: 100%;">
                    </div>
                    
                    <div class="table-container">
                        <table id="transactions-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="6" class="no-results">No transactions found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $totalDebit = 0;
                                    $totalCredit = 0;
                                    ?>
                                    <?php foreach ($transactions as $txn): ?>
                                    <tr data-account="<?= htmlspecialchars($txn['account_code']) ?>" data-type="<?= htmlspecialchars($txn['transaction_type']) ?>">
                                        <td><?= date('Y-m-d', strtotime($txn['transaction_date'])) ?></td>
                                        <td><?= htmlspecialchars($txn['account_code']) ?> - <?= htmlspecialchars($txn['account_name']) ?></td>
                                        <td><?= htmlspecialchars($txn['description']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($txn['transaction_type'])) ?></td>
                                        <td class="transaction-debit">
                                            <?= $txn['transaction_type'] === 'debit' ? '$' . number_format($txn['amount'], 2) : '-' ?>
                                        </td>
                                        <td class="transaction-credit">
                                            <?= $txn['transaction_type'] === 'credit' ? '$' . number_format($txn['amount'], 2) : '-' ?>
                                        </td>
                                    </tr>
                                    <?php 
                                    if ($txn['transaction_type'] === 'debit') {
                                        $totalDebit += $txn['amount'];
                                    } else {
                                        $totalCredit += $txn['amount'];
                                    }
                                    ?>
                                    <?php endforeach; ?>
                                    <tr class="summary-row">
                                        <td colspan="4"><strong>Totals</strong></td>
                                        <td class="transaction-debit"><strong>$<?= number_format($totalDebit, 2) ?></strong></td>
                                        <td class="transaction-credit"><strong>$<?= number_format($totalCredit, 2) ?></strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="report-actions">
                        <button class="btn-export" onclick="exportTransactionHistory()">
                            <i class="fas fa-file-export"></i> Export CSV
                        </button>
                        <button class="btn-print" onclick="printTransactionHistory()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Account</h3>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="action" value="edit_account">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Account Code</label>
                    <input type="text" name="account_code" id="edit_code" required>
                </div>
                <div class="form-group">
                    <label>Account Name</label>
                    <input type="text" name="account_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_type" id="edit_type" required>
                        <option value="Assets">Assets</option>
                        <option value="Liabilities">Liabilities</option>
                        <option value="Equity">Equity</option>
                        <option value="Revenue">Revenue</option>
                        <option value="Expenses">Expenses</option>
                    </select>
                </div>
                <button type="submit" class="btn-success">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h3>Confirm Account Deletion</h3>
            <p>Are you sure you want to delete this account? This action cannot be undone.</p>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="action" value="delete_account">
                <input type="hidden" name="id" id="delete_id">
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn-delete">
                        <i class="fas fa-trash"></i> Confirm Delete
                    </button>
                    <button type="button" class="btn" onclick="closeDeleteModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Tab navigation
    function openTab(evt, tabName) {
        // Hide all tab-content
        var tabContent = document.getElementsByClassName("tab-content");
        for (var i = 0; i < tabContent.length; i++) {
            tabContent[i].classList.remove("active");
        }
        
        // Remove active class from all tab-links
        var tabLinks = document.getElementsByClassName("tab-link");
        for (var i = 0; i < tabLinks.length; i++) {
            tabLinks[i].className = tabLinks[i].className.replace(" active", "");
        }
        
        // Show the current tab, and add an "active" class to the button
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.className += " active";
    }

    // Toggle Account Form
    function toggleAccountForm() {
        const form = document.getElementById('accountForm');
        form.classList.toggle('show');
    }

    // Edit Account Modal
    function openEditModal(id) {
        // In a real application, we would fetch account details via AJAX
        // For this demo, we'll just show the modal
        document.getElementById('editModal').style.display = 'block';
        
        // Set the ID in the form
        document.getElementById('edit_id').value = id;
        
        // Find the account in our accounts data (for demo)
        const accountRow = document.querySelector(`tr[data-account-id="${id}"]`);
        if (accountRow) {
            const code = accountRow.querySelector('td:first-child').textContent;
            const name = accountRow.querySelector('td:nth-child(2)').textContent;
            const type = accountRow.querySelector('td:nth-child(3)').textContent;
            
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_type').value = type;
        }
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Delete Account Modal
    function confirmDelete(id) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }

    // Journal Entry functions
    function addJournalLine() {
        const line = document.createElement('div');
        line.className = 'journal-line';
        line.innerHTML = `
            <select name="account[]" required>
                <option value="">Select Account</option>
                <?php foreach ($accounts as $acc): ?>
                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="debit[]" step="0.01" placeholder="Debit">
            <input type="number" name="credit[]" step="0.01" placeholder="Credit">
        `;
        document.getElementById('journalLines').appendChild(line);
        calculateTotals();
    }

    function calculateTotals() {
        let totalDebit = 0, totalCredit = 0;
        document.querySelectorAll('.journal-line').forEach(line => {
            const debit = parseFloat(line.querySelector('[name="debit[]"]').value) || 0;
            const credit = parseFloat(line.querySelector('[name="credit[]"]').value) || 0;
            totalDebit += debit;
            totalCredit += credit;
        });
        document.getElementById('journalTotal').textContent = 
            `Total Debit: $${totalDebit.toFixed(2)} | Total Credit: $${totalCredit.toFixed(2)}`;
        return totalDebit === totalCredit;
    }

    function submitJournal(e) {
        e.preventDefault();
        if (!calculateTotals()) {
            alert('Debit and Credit totals must match');
            return;
        }
        
        // In a real application, we would submit via AJAX
        alert('Journal entry would be submitted in a real application');
        e.target.reset();
        document.getElementById('journalTotal').textContent = 'Total Debit: $0.00 | Total Credit: $0.00';
    }

    // Service payment submit function
    function submitServicePayment(e) {
        e.preventDefault();
        alert('Service payment would be recorded in a real application');
        e.target.reset();
    }

    // Supplier payment submit function
    function submitSupplierPayment(e) {
        e.preventDefault();
        alert('Supplier payment would be recorded in a real application');
        e.target.reset();
    }

    // Vehicle expense submit function
    function submitVehicleExpense(e) {
        e.preventDefault();
        alert('Vehicle expense would be recorded in a real application');
        e.target.reset();
    }

    // View account transactions function
    function viewAccountTransactions(accountId) {
        // Switch to history tab
        openTab(event, 'history');
        
        // Get the account code from the table for filtering
        const accountRow = document.querySelector(`tr[data-account-id="${accountId}"]`);
        if (accountRow) {
            const accountCode = accountRow.querySelector('td:first-child').textContent;
            // Set the filter
            document.getElementById('filter-account').value = accountCode;
            // Trigger the filter
            filterTransactions();
        }
    }

    // Transaction History Filter Functions
    function filterTransactions() {
        const dateFrom = document.getElementById('filter-date-from').value;
        const dateTo = document.getElementById('filter-date-to').value;
        const accountFilter = document.getElementById('filter-account').value;
        const typeFilter = document.getElementById('filter-type').value;
        const searchTerm = document.getElementById('transaction-search').value.toLowerCase();
        
        const rows = document.querySelectorAll('#transactions-table tbody tr');
        
        let visibleRows = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('summary-row')) {
                return; // Skip summary row
            }
            
            let showRow = true;
            
            // Date range filter
            if (dateFrom) {
                const rowDate = new Date(row.cells[0].textContent);
                const fromDate = new Date(dateFrom);
                if (rowDate < fromDate) showRow = false;
            }
            
            if (dateTo && showRow) {
                const rowDate = new Date(row.cells[0].textContent);
                const toDate = new Date(dateTo);
                toDate.setDate(toDate.getDate() + 1); // Include the end date
                if (rowDate > toDate) showRow = false;
            }
            
            // Account filter
            if (accountFilter && showRow) {
                const rowAccount = row.getAttribute('data-account');
                if (rowAccount !== accountFilter) showRow = false;
            }
            
            // Transaction type filter
            if (typeFilter && showRow) {
                const rowType = row.getAttribute('data-type');
                if (rowType !== typeFilter) showRow = false;
            }
            
            // Description search
            if (searchTerm && showRow) {
                const description = row.cells[2].textContent.toLowerCase();
                if (!description.includes(searchTerm)) showRow = false;
            }
            
            // Show or hide row
            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleRows++;
        });
        
        // Show message if no results
        const noResultsRow = document.getElementById('no-results-row');
        
        if (visibleRows === 0 && !noResultsRow) {
            const tableBody = document.querySelector('#transactions-table tbody');
            const newRow = document.createElement('tr');
            newRow.id = 'no-results-row';
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'no-results';
            cell.textContent = 'No transactions match your filters.';
            newRow.appendChild(cell);
            tableBody.appendChild(newRow);
        } else if (visibleRows > 0 && noResultsRow) {
            noResultsRow.remove();
        }
    }

    // Export functions
    function exportTransactionHistory() {
        alert('Transaction history would be exported to CSV in a real application');
    }

    // Print function
    function printTransactionHistory() {
        alert('Transaction history would be printed in a real application');
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners for transaction filtering
        document.getElementById('filter-date-from').addEventListener('change', filterTransactions);
        document.getElementById('filter-date-to').addEventListener('change', filterTransactions);
        document.getElementById('filter-account').addEventListener('change', filterTransactions);
        document.getElementById('filter-type').addEventListener('change', filterTransactions);
        
        // Add debounced search for transaction descriptions
        const searchInput = document.getElementById('transaction-search');
        let debounceTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(filterTransactions, 300);
        });
        
        // Additional event listeners for journal entries
        document.getElementById('journalLines').addEventListener('input', function(e) {
            // Make sure only one field (debit or credit) has a value in each row
            if (e.target.name === 'debit[]' && e.target.value) {
                const row = e.target.closest('.journal-line');
                const creditInput = row.querySelector('[name="credit[]"]');
                creditInput.value = '';
            } else if (e.target.name === 'credit[]' && e.target.value) {
                const row = e.target.closest('.journal-line');
                const debitInput = row.querySelector('[name="debit[]"]');
                debitInput.value = '';
            }
            
            calculateTotals();
        });
        
        // Set default dates to current month
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        document.getElementById('filter-date-from').valueAsDate = firstDay;
        document.getElementById('filter-date-to').valueAsDate = lastDay;
        
        // Filter transactions on page load
        filterTransactions();
    });
    </script>
</body>
</html>