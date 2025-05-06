<?php
// Start with session and includes
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
            
            // Set success message instead of redirecting
            $success_message = "Account added successfully!";
        } catch (PDOException $e) {
            $error_messages[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get all accounts (do this after handling the form to show newly added accounts)
$accounts = $pdo->query("SELECT * FROM accounts ORDER BY account_code ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all transactions for the history tab - UPDATED to use expenses table
$transactions = $pdo->query("
    SELECT 
        et.id, 
        et.transaction_date, 
        et.description, 
        et.amount, 
        et.type,
        a.account_code,
        a.account_name,
        et.transaction_type
    FROM 
        expenses et
    JOIN 
        accounts a ON et.account_id = a.id
    ORDER BY 
        et.transaction_date DESC, 
        et.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Now include the header (after all processing is done)
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Financial Management System</title>

    <style>
    /* Global Styles */
:root {
  --primary-color: #4a6fa5;
  --primary-light: #6789bd;
  --primary-dark: #365785;
  --secondary-color: #67a57f;
  --danger-color: #d9534f;
  --warning-color: #f0ad4e;
  --success-color: #5cb85c;
  --gray-light: #f8f9fa;
  --gray: #e9ecef;
  --gray-dark: #343a40;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  --border-radius: 8px;
}

body {
  font-family: 'Roboto', 'Segoe UI', sans-serif;
  line-height: 1.6;
  color: #333;
  background-color: #f5f7fa;
  margin: 0;
  padding: 0;
}

h2 {
  color: var(--primary-dark);
  margin: 1.5rem 0;
  font-weight: 600;
  font-size: 2rem;
  text-align: center;
  position: relative;
  padding-bottom: 10px;
}

h2:after {
  content: '';
  position: absolute;
  width: 80px;
  height: 3px;
  background-color: var(--primary-color);
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
}

/* Tab Container and Navigation */
.tab-nav {
  display: flex;
  background-color: var(--gray-light);
  border-bottom: 1px solid var(--gray);
  margin-bottom: 20px;
}

.tab-nav button {
  padding: 15px 25px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  color: var(--gray-dark);
  transition: all 0.3s ease;
  flex: 1;
  text-align: center;
  border-bottom: 3px solid transparent;
  border-radius: 0;
  margin-right: 0;
}

.tab-nav button:hover {
  background-color: rgba(74, 111, 165, 0.1);
  color: var(--primary-color);
}

.tab-nav button.active {
  color: var(--primary-color);
  border-bottom: 3px solid var(--primary-color);
  background-color: white;
}

/* Tab Content */
.tab {
  display: none;
  padding: 20px;
  background-color: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  animation: fadeIn 0.5s ease;
  border: none;
}

.tab.active {
  display: block;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Form Styling */
.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--gray-dark);
}

input, select, textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--gray);
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: border 0.3s ease, box-shadow 0.3s ease;
  box-sizing: border-box;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: var(--primary-light);
  box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
}

/* Button Styling */
button {
  padding: 12px 20px;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
  background-color: var(--primary-color);
  color: white;
}

button:hover {
  background-color: var(--primary-dark);
}

button[type="submit"] {
  background-color: var(--success-color);
  color: white;
  width: auto;
  padding: 12px 24px;
  margin-top: 10px;
  display: inline-block;
}

button[type="submit"]:hover {
  background-color: #4cae4c;
}

button[type="button"] {
  background-color: var(--secondary-color);
  color: white;
  margin-bottom: 15px;
}

button[type="button"]:hover {
  background-color: #528c66;
}

/* Table Styling */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  font-size: 0.95rem;
  box-shadow: var(--shadow);
  border-radius: var(--border-radius);
  overflow: hidden;
}

th, td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid var(--gray);
}

th {
  background-color: var(--gray-light);
  color: var(--primary-dark);
  font-weight: 600;
  white-space: nowrap;
}

tr:hover {
  background-color: rgba(74, 111, 165, 0.05);
}

.total-row {
  font-weight: bold;
  background-color: var(--gray-light);
  padding: 10px;
  border-radius: var(--border-radius);
  margin: 15px 0;
  text-align: right;
}

/* Journal Entry Specific Styling */
.journal-line {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 10px;
  margin-bottom: 10px;
  align-items: center;
}

/* Alert/Error Styling */
.error {
  color: var(--danger-color);
  margin-top: 5px;
  font-size: 0.9rem;
}

.success-message {
  background-color: var(--success-color);
  color: white;
  padding: 10px;
  border-radius: var(--border-radius);
  margin-bottom: 15px;
}

.error-message {
  background-color: var(--danger-color);
  color: white;
  padding: 10px;
  border-radius: var(--border-radius);
  margin-bottom: 15px;
}

/* Add Account Form */
.add-account-section {
  margin-bottom: 30px;
  border: 1px solid var(--gray);
  padding: 20px;
  border-radius: var(--border-radius);
  background-color: var(--gray-light);
}

.toggle-form {
  margin-bottom: 15px;
}

.account-form {
  display: none;
}

.account-form.show {
  display: block;
}

/* Transaction History Specific Styling */
.transaction-credit {
  color: var(--success-color);
  font-weight: 600;
}

.transaction-debit {
  color: var(--danger-color);
  font-weight: 600;
}

/* Filter controls */
.filter-controls {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.filter-controls .form-group {
  flex: 1;
  min-width: 200px;
  margin-bottom: 0;
}

.search-box {
  margin-bottom: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .tab-nav {
    flex-direction: column;
  }
  
  .tab-nav button {
    width: 100%;
  }
  
  table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
  
  button {
    display: block;
    width: 100%;
    margin-bottom: 10px;
  }
  
  .journal-line {
    grid-template-columns: 1fr;
  }

  .filter-controls {
    flex-direction: column;
  }
}
</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="tab-nav">
        <button class="active" onclick="openTab('charts')">Chart of Accounts</button>
        <button onclick="openTab('journal')">Journal Entry</button>
        <button onclick="openTab('service')">Service Payments</button>
        <button onclick="openTab('supplier')">Supplier Payments</button>
        <button onclick="openTab('vehicle')">Vehicle Expenses</button>
        <button onclick="openTab('history')">Transaction History</button>
    </div>

    <!-- Chart of Accounts Tab -->
    <div id="charts" class="tab active">
        <h2>Chart of Accounts</h2>
        
        <!-- Add Account Section -->
        <div class="add-account-section">
            <!-- Display success message if there is one -->
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Display error messages if there are any -->
            <?php if (!empty($error_messages)): ?>
                <div class="error-message">
                    <?php foreach ($error_messages as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <button type="button" class="toggle-form" onclick="toggleAccountForm()">
                <i class="fas fa-plus"></i> Add New Account
            </button>
            
            <div id="accountForm" class="account-form <?= (!empty($error_messages) || !empty($success_message)) ? 'show' : '' ?>">
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
                    <button type="submit">Add Account</button>
                </form>
            </div>
        </div>
        
        <table>
            <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Balance</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($accounts)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No accounts found. Add your first account above.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                <?php
                    // Get debits and credits for this account from expenses
                    $stmt = $pdo->prepare("
                        SELECT 
                            SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit,
                            SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit
                        FROM expenses 
                        WHERE account_id = ?
                    ");
                    $stmt->execute([$account['id']]);
                    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $totalDebit = $totals['total_debit'] ?? 0;
                    $totalCredit = $totals['total_credit'] ?? 0;
                ?>
                <tr data-account-id="<?= $account['id'] ?>">
                    <td><?= htmlspecialchars($account['account_code']) ?></td>
                    <td><?= htmlspecialchars($account['account_name']) ?></td>
                    <td><?= htmlspecialchars($account['account_type']) ?></td>
                    <td class="transaction-debit"><?= number_format($totalDebit, 2) ?></td>
                    <td class="transaction-credit"><?= number_format($totalCredit, 2) ?></td>
                    <td><?= number_format($account['balance'], 2) ?></td>
                    <td><button onclick="viewAccountTransactions(<?= $account['id'] ?>)"><i class="fas fa-eye"></i> View</button></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <!-- Journal Entry Tab -->
    <div id="journal" class="tab">
        <h2>Journal Entry</h2>
        <form id="journalForm" onsubmit="submitJournal(event)">
            <div class="form-group">
                <label>Entry Date</label>
                <input type="date" name="entry_date" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>
            <div id="journalLines">
                <div class="journal-line">
                    <select name="account[]" required>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="debit[]" step="0.01" placeholder="Debit">
                    <input type="number" name="credit[]" step="0.01" placeholder="Credit">
                </div>
            </div>
            <button type="button" onclick="addJournalLine()">Add Line</button>
            <div id="journalTotal" class="total-row"></div>
            <button type="submit">Post Entry</button>
        </form>
    </div>

    <!-- Service Payments Tab -->
    <div id="service" class="tab">
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
                <textarea name="description"></textarea>
            </div>
            <button type="submit">Record Payment</button>
        </form>
    </div>

    <!-- Supplier Payments Tab -->
    <div id="supplier" class="tab">
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
                <textarea name="description"></textarea>
            </div>
            <button type="submit">Record Payment</button>
        </form>
    </div>

    <!-- Vehicle Expenses Tab -->
    <div id="vehicle" class="tab">
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
                <textarea name="description"></textarea>
            </div>
            <button type="submit">Record Expense</button>
        </form>
    </div>

    <!-- Transaction History Tab -->
    <div id="history" class="tab">
        <h2>Transaction History</h2>
        
        <!-- Filter and Search Controls -->
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
        
        <div class="search-box">
            <input type="text" id="transaction-search" placeholder="Search by description..." style="width: 100%;">
        </div>
        
        <table id="transactions-table">
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th>Description</th>
                <th>Type</th>
                <th>Debit</th>
                <th>Credit</th>
            </tr>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No transactions found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $txn): ?>
                <tr data-account="<?= htmlspecialchars($txn['account_code']) ?>" data-type="<?= htmlspecialchars($txn['transaction_type']) ?>">
                    <td><?= date('Y-m-d', strtotime($txn['transaction_date'])) ?></td>
                    <td><?= htmlspecialchars($txn['account_code']) ?> - <?= htmlspecialchars($txn['account_name']) ?></td>
                    <td><?= htmlspecialchars($txn['description']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($txn['transaction_type'])) ?></td>
                    <td class="transaction-debit">
                        <?= $txn['transaction_type'] === 'debit' ? number_format($txn['amount'], 2) : '-' ?>
                    </td>
                    <td class="transaction-credit">
                        <?= $txn['transaction_type'] === 'credit' ? number_format($txn['amount'], 2) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

<script>
// Tab navigation
function openTab(tabName) {
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-nav button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Toggle Account Form
function toggleAccountForm() {
    const form = document.getElementById('accountForm');
    form.classList.toggle('show');
}

// Journal Entry functions
function addJournalLine() {
    const line = document.createElement('div');
    line.className = 'journal-line';
    line.innerHTML = `
        <select name="account[]" required>
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
        `Total Debit: ${totalDebit.toFixed(2)} | Total Credit: ${totalCredit.toFixed(2)}`;
    return totalDebit === totalCredit;
}

async function submitJournal(e) {
    e.preventDefault();
    if (!calculateTotals()) {
        alert('Debit and Credit totals must match');
        return;
    }
    
    const formData = new FormData(e.target);
    try {
        const response = await fetch('journal_entry.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Journal entry posted successfully');
            e.target.reset();
            // Refresh page to update account balances
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Network error: ' + error.message);
    }
}

// Service payment submit function
async function submitServicePayment(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    try {
        const response = await fetch('service_payment.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Service payment recorded successfully');
            e.target.reset();
            // Refresh page to update account balances
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Network error: ' + error.message);
    }
}

// Supplier payment submit function
async function submitSupplierPayment(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    try {
        const response = await fetch('supplier_payment.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Supplier payment recorded successfully');
            e.target.reset();
            // Refresh page to update account balances
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Network error: ' + error.message);
    }
}

// Vehicle expense submit function
async function submitVehicleExpense(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    try {
        const response = await fetch('vehicle_expense.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Vehicle expense recorded successfully');
            e.target.reset();
            // Refresh page to update account balances
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Network error: ' + error.message);
    }
}

// View account transactions function
function viewAccountTransactions(accountId) {
    // Switch to history tab
    openTab('history');
    
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
    
    const rows = document.querySelectorAll('#transactions-table tr:not(:first-child)');
    
    rows.forEach(row => {
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
    });
    
    // Show message if no results
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const noResultsRow = document.getElementById('no-results-row');
    
    if (visibleRows.length === 0) {
        if (!noResultsRow) {
            const table = document.getElementById('transactions-table');
            const newRow = table.insertRow();
            newRow.id = 'no-results-row';
            const cell = newRow.insertCell();
            cell.colSpan = 6;
            cell.style.textAlign = 'center';
            cell.textContent = 'No transactions match your filters.';
        }
    } else if (noResultsRow) {
        noResultsRow.remove();
    }
}

// Event listeners for transaction filtering
document.addEventListener('DOMContentLoaded', function() {
    // Calculate totals for journal entries
    calculateTotals();
    
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
    
    // Format currency values in tables
    document.querySelectorAll('td:nth-child(5), td:nth-child(6)').forEach(cell => {
        if (cell.textContent !== '-') {
            const value = parseFloat(cell.textContent.replace(/,/g, ''));
            if (!isNaN(value)) {
                cell.textContent = value.toLocaleString('en-US', {
                    style: 'decimal',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }
    });
});

// Add data-attribute to account rows for filtering
document.addEventListener('DOMContentLoaded', function() {
    const accountRows = document.querySelectorAll('#charts table tr:not(:first-child)');
    accountRows.forEach((row, index) => {
        // Add account-id attribute for account filtering
        row.setAttribute('data-account-id', index + 1);
    });
});

// Export functions (for CSV export feature)
function exportTransactionHistory() {
    // Get visible rows only
    const table = document.getElementById('transactions-table');
    const rows = Array.from(table.querySelectorAll('tr:not([style*="display: none"])'));
    
    if (rows.length <= 1) {
        alert('No data to export');
        return;
    }
    
    // Create CSV content
    let csvContent = 'data:text/csv;charset=utf-8,';
    
    // Add headers
    const headers = Array.from(rows[0].querySelectorAll('th')).map(th => `"${th.textContent}"`);
    csvContent += headers.join(',') + '\r\n';
    
    // Add data rows
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const rowData = Array.from(row.querySelectorAll('td')).map(td => `"${td.textContent}"`);
        csvContent += rowData.join(',') + '\r\n';
    }
    
    // Create download link
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'transaction_history.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print function
function printTransactionHistory() {
    const printContent = document.getElementById('history').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h1 style="text-align: center;">Transaction History</h1>
            <div>${printContent}</div>
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    
    // Reattach event listeners
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotals();
    });
}
</script>

<!-- Add Export and Print buttons to the history tab -->
<div style="margin-top: 20px; text-align: right;">
    <button onclick="exportTransactionHistory()"><i class="fas fa-file-export"></i> Export CSV</button>
    <button onclick="printTransactionHistory()"><i class="fas fa-print"></i> Print</button>
</div>

</body>
</html>