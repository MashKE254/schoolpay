<?php
/**
 * expense_management.php
 *
 * A comprehensive page for managing school finances. It includes:
 * - Chart of Accounts management (Add/Edit/Delete).
 * - Manual Journal Entry creation.
 * - Bulk expense processing via CSV Requisition uploads.
 * - A dynamic, filterable General Ledger for all transactions.
 *
 * All POST logic is handled at the top of the script before any HTML output
 * to ensure redirects and session messages function correctly.
 */

require_once 'config.php';
require_once 'functions.php';

// --- POST Request Handling for Account Management (Add/Edit/Delete) ---
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $school_id = $_SESSION['school_id'] ?? null;
    $success_message = '';

    // Ensure the user is logged in and has a valid school context
    if(!$school_id){
        header("Location: login.php?error=session_expired");
        exit();
    }

    $action = $_POST['action'];
    try {
        $pdo->beginTransaction();

        if ($action === 'add_account') {
            $account_code = trim($_POST['account_code']);
            $account_name = trim($_POST['account_name']);
            $account_type = trim($_POST['account_type']);

            if (empty($account_code) || empty($account_name) || empty($account_type)) {
                 throw new Exception("Account code, name, and type are required.");
            }
            
            // Check if the account code is already used by ANY school, due to the global unique key constraint
            $stmt_check = $pdo->prepare("SELECT id FROM accounts WHERE account_code = ?");
            $stmt_check->execute([$account_code]);
            if ($stmt_check->fetch()) {
                // This message now correctly informs the user that the code is taken globally
                throw new Exception("The Account Code '" . htmlspecialchars($account_code) . "' is already in use across the system. Please choose a completely unique code.");
            }

            createAccount(
                $pdo,
                $school_id,
                $account_code,
                $account_name,
                $account_type,
                $_POST['opening_balance'] ?? 0
            );
            $success_message = "Account added successfully!";

        } elseif ($action === 'edit_account') {
            updateAccount(
                $pdo,
                $_POST['id'],
                trim($_POST['account_code']),
                trim($_POST['account_name']),
                trim($_POST['account_type']),
                $school_id
            );
            $success_message = "Account updated successfully!";

        } elseif ($action === 'delete_account') {
            deleteAccount($pdo, $_POST['id'], $school_id);
            $success_message = "Account deleted successfully!";
        }
        
        $pdo->commit();
        // Redirect back to the charts tab with a success message
        header("Location: expense_management.php?tab=charts&success=" . urlencode($success_message));
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Store the error message to display it on the page after redirecting (or rendering)
        $error_message = $e->getMessage();
    }
}

// Include the header file which starts the HTML document
require_once 'header.php';

// --- Data Fetching for Page Display ---
// This runs after all POST logic is complete.
$accounts = getChartOfAccounts($pdo, $school_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management System</title>
    <!-- Stylesheets are included in header.php -->
    <style>
        /* General Styles */
        .amount-header, .amount { text-align: right; }

        /* Account Form Styles */
        .account-form { 
            max-height: 0; 
            overflow: hidden; 
            transition: max-height 0.4s ease-in-out; 
            border-top: 1px solid var(--border); 
            margin-top: 15px; 
            padding-top: 0; 
        }
        .account-form.show { 
            max-height: 600px; /* Adjust as needed */
            padding-top: 20px; 
        }

        /* General Ledger Specific Styles */
        .ledger-filters { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 15px; 
            padding: 15px; 
            background-color: #f9f9f9; 
            border: 1px solid var(--border);
            border-radius: 6px; 
            margin-bottom: 20px; 
        }
        .ledger-summary { 
            display: flex; 
            gap: 25px; 
            font-weight: bold; 
            padding: 15px; 
            background-color: #f0f4f8; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            justify-content: flex-end;
            border: 1px solid var(--border);
        }
        #ledger-table-body .loading-row td { 
            text-align: center; 
            padding: 40px; 
            font-style: italic; 
            color: #777; 
        }
    </style>
</head>
<body>
    <!-- The opening <div class="container"> is in header.php -->
    
    <div class="page-header">
        <div class="page-header-title">
            <h1><i class="fas fa-receipt"></i> Expense Management</h1>
            <p>Track all expenses, make payments, and manage your chart of accounts.</p>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <div class="tab-container">
        <div class="tabs">
            <button class="tab-link" onclick="openTab(event, 'charts')"><i class="fas fa-sitemap"></i> Chart of Accounts</button>
            <button class="tab-link" onclick="openTab(event, 'journal')"><i class="fas fa-book"></i> Journal Entry</button>
            <button class="tab-link" onclick="openTab(event, 'requisition')"><i class="fas fa-file-invoice-dollar"></i> Requisition</button>
            <button class="tab-link active" onclick="openTab(event, 'ledger')"><i class="fas fa-book-open"></i> General Ledger</button>
        </div>

        <!-- Tab 1: Chart of Accounts -->
        <div id="charts" class="tab-content">
            <div class="card">
                <h2>Chart of Accounts</h2>
                <div class="table-actions">
                    <button type="button" class="btn-add" onclick="toggleAccountForm()"><i class="fas fa-plus"></i> Add New Account</button>
                </div>
                <div id="accountForm" class="account-form">
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <input type="hidden" name="action" value="add_account">
                        <div class="form-group"><label>Account Type</label><select name="account_type" required><option value="">-- Select Type --</option><option value="asset">Asset</option><option value="liability">Liability</option><option value="equity">Equity</option><option value="revenue">Revenue</option><option value="expense">Expense</option></select></div>
                        <div class="form-group"><label>Account Code</label><input type="text" name="account_code" required></div>
                        <div class="form-group"><label>Account Name</label><input type="text" name="account_name" required></div>
                        <div class="form-group"><label>Opening Balance</label><input type="number" step="0.01" name="opening_balance" value="0"></div>
                        <button type="submit" class="btn-success"><i class="fas fa-save"></i> Add Account</button>
                    </form>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th class="amount-header">Balance</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr><td colspan="5" class="text-center">No accounts found. Add one to get started.</td></tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?= htmlspecialchars($account['account_code']) ?></td>
                                    <td><?= htmlspecialchars($account['account_name']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($account['account_type'])) ?></td>
                                    <td class="amount">$<?= number_format($account['balance'], 2) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" title="Edit" onclick='openEditModal(<?= htmlspecialchars(json_encode($account), ENT_QUOTES, "UTF-8") ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this account? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete_account">
                                                <input type="hidden" name="id" value="<?= $account['id'] ?>">
                                                <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: Journal Entry -->
        <div id="journal" class="tab-content">
             <div class="card">
                <h2>Journal Entry</h2>
                <form id="journalForm" onsubmit="submitJournal(event)">
                    <div class="form-group"><label>Entry Date</label><input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="Enter a brief description for this journal entry..."></textarea></div>
                    <div id="journalLines">
                        <div class="journal-line" style="display:flex; gap:10px; margin-bottom:10px;">
                            <select name="account[]" required style="flex:3;"><option value="">Select Account</option><?php foreach ($accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?></select>
                            <input type="number" name="debit[]" step="0.01" placeholder="Debit" style="flex:1;">
                            <input type="number" name="credit[]" step="0.01" placeholder="Credit" style="flex:1;">
                        </div>
                    </div>
                    <div class="table-actions">
                        <button type="button" class="btn-add" onclick="addJournalLine()"><i class="fas fa-plus"></i> Add Line</button>
                    </div>
                    <div id="journalTotal" style="font-weight: 700; padding: 15px 0; text-align:right; font-size: 1.2rem;">Total Debit: $0.00 | Total Credit: $0.00</div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Post Entry</button></div>
                </form>
            </div>
        </div>

        <!-- Tab 3: Requisition -->
        <div id="requisition" class="tab-content">
            <div class="card">
                <h2>Process Weekly Requisition</h2>
                <p>Upload a formatted CSV file of your weekly expenses for bulk processing.</p>
                <form action="process_requisition.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="requisition_date">Transaction Date</label>
                        <input type="date" name="transaction_date" id="requisition_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_account_id">Pay From Account</label>
                        <select name="payment_account_id" required>
                            <option value="">-- Select Petty Cash / Bank --</option>
                            <?php 
                            // Only show Asset accounts as payment sources
                            $asset_accounts = array_filter($accounts, fn($acc) => $acc['account_type'] == 'asset');
                            foreach ($asset_accounts as $acc): 
                            ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="requisition_csv">Upload Requisition CSV</label>
                        <input type="file" name="requisition_csv" accept=".csv" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-success"><i class="fas fa-upload"></i> Upload & Categorize</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tab 4: General Ledger -->
        <div id="ledger" class="tab-content active">
            <div class="card">
                <h2>General Ledger</h2>
                <form id="ledger-filter-form">
                    <div class="ledger-filters">
                        <div class="form-group"><label>From</label><input type="date" name="date_from" class="form-control"></div>
                        <div class="form-group"><label>To</label><input type="date" name="date_to" class="form-control"></div>
                        <div class="form-group"><label>Account</label>
                            <select name="account_id" class="form-control">
                                <option value="">All Accounts</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Type</label>
                            <select name="transaction_type" class="form-control">
                                <option value="">All Types</option><option value="debit">Debit</option><option value="credit">Credit</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Search</label><input type="text" name="search" class="form-control" placeholder="Search descriptions..."></div>
                        <div class="form-actions" style="grid-column: 1 / -1; display:flex; gap:10px;">
                            <button type="submit" class="btn-success"><i class="fas fa-filter"></i> Filter</button>
                            <button type="reset" class="btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </div>
                </form>

                <div class="ledger-summary">
                    <div>Total Debits: <span id="total-debits">$0.00</span></div>
                    <div>Total Credits: <span id="total-credits">$0.00</span></div>
                    <div>Net Movement: <span id="net-movement">$0.00</span></div>
                </div>

                <div class="table-container">
                    <table id="ledger-table">
                        <thead><tr><th>Date</th><th>Account</th><th>Description</th><th class="amount-header">Debit</th><th class="amount-header">Credit</th><th>Receipt</th></tr></thead>
                        <tbody id="ledger-table-body">
                           <!-- Rows will be inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Account</h3>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="action" value="edit_account">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group"><label>Account Code</label><input type="text" name="account_code" id="edit_code" required></div>
                <div class="form-group"><label>Account Name</label><input type="text" name="account_name" id="edit_name" required></div>
                <div class="form-group"><label>Account Type</label>
                    <select name="account_type" id="edit_type" required>
                        <option value="asset">Asset</option><option value="liability">Liability</option>
                        <option value="equity">Equity</option><option value="revenue">Revenue</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-success"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Store accounts data from PHP for use in JavaScript
        const accountsData = <?= json_encode($accounts) ?>;

        /**
         * Handles switching between tabs.
         * @param {Event} evt The click event.
         * @param {string} tabName The ID of the tab content to display.
         */
        function openTab(evt, tabName) {
            document.querySelectorAll(".tab-content").forEach(tc => { tc.style.display = "none"; tc.classList.remove('active'); });
            document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove("active"));
            
            const tabToShow = document.getElementById(tabName);
            tabToShow.style.display = "block";
            tabToShow.classList.add('active');
            evt.currentTarget.classList.add("active");

            // If the General Ledger tab is opened, fetch its data.
            if (tabName === 'ledger') {
                fetchLedgerData();
            }
        }
        
        /**
         * On page load, check for a 'tab' URL parameter to open a specific tab.
         * This is useful for redirecting back to the correct view after a form submission.
         */
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab') || 'ledger'; // Default to ledger
            const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
            if (tabButton) {
                tabButton.click();
            } else {
                // Fallback to the first tab if the specified one doesn't exist
                document.querySelector('.tab-link').click();
            }
        });
        
        // --- Chart of Accounts Functions ---
        function toggleAccountForm() { document.getElementById('accountForm').classList.toggle('show'); }
        
        function openEditModal(account) {
            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
            modal.querySelector('#edit_id').value = account.id;
            modal.querySelector('#edit_code').value = account.account_code;
            modal.querySelector('#edit_name').value = account.account_name;
            modal.querySelector('#edit_type').value = account.account_type;
        }

        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
        
        // Close modal if user clicks outside of it
        window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = 'none'; };

        // --- Journal Entry Functions ---
        function addJournalLine() {
            const container = document.getElementById('journalLines');
            const newLine = document.createElement('div');
            newLine.className = 'journal-line';
            newLine.style.cssText = 'display:flex; gap:10px; margin-bottom:10px;';
            
            let optionsHTML = accountsData.map(acc => `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`).join('');
            
            newLine.innerHTML = `
                <select name="account[]" required style="flex:3;"><option value="">Select Account</option>${optionsHTML}</select>
                <input type="number" name="debit[]" step="0.01" placeholder="Debit" style="flex:1;">
                <input type="number" name="credit[]" step="0.01" placeholder="Credit" style="flex:1;">`;
            container.appendChild(newLine);
        }

        function calculateJournalTotals() {
            let totalDebit = 0, totalCredit = 0;
            document.querySelectorAll('.journal-line').forEach(line => {
                totalDebit += parseFloat(line.querySelector('[name="debit[]"]').value) || 0;
                totalCredit += parseFloat(line.querySelector('[name="credit[]"]').value) || 0;
            });
            document.getElementById('journalTotal').innerHTML = `Total Debit: $${totalDebit.toFixed(2)} | Total Credit: $${totalCredit.toFixed(2)}`;
            return { totalDebit, totalCredit };
        }

        document.getElementById('journalLines').addEventListener('input', (e) => {
            if (e.target.closest('.journal-line')) {
                const row = e.target.closest('.journal-line');
                const debitInput = row.querySelector('[name="debit[]"]');
                const creditInput = row.querySelector('[name="credit[]"]');
                // Ensure only one of debit or credit has a value per line
                if (e.target === debitInput && debitInput.value) creditInput.value = '';
                else if (e.target === creditInput && creditInput.value) debitInput.value = '';
                calculateJournalTotals();
            }
        });

        function submitJournal(e) {
            e.preventDefault();
            const { totalDebit, totalCredit } = calculateJournalTotals();
            if (Math.abs(totalDebit - totalCredit) > 0.01) {
                alert('Debit and Credit totals must match.');
                return;
            }
            if (totalDebit === 0) {
                 alert('Journal entry cannot be empty.');
                return;
            }
            // This function is a generic form submit handler
            submitForm(e, 'journal_entry.php', 'journalForm');
        }

        // --- General Ledger Logic ---
        const filterForm = document.getElementById('ledger-filter-form');
        const tableBody = document.getElementById('ledger-table-body');

        async function fetchLedgerData() {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            
            tableBody.innerHTML = `<tr class="loading-row"><td colspan="6"><i class="fas fa-spinner fa-spin"></i> Loading transactions...</td></tr>`;

            try {
                const response = await fetch(`expense_transactions.php?${params.toString()}`);
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                
                const data = await response.json();
                if (data.success) {
                    renderLedgerTable(data.transactions);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6">Error: ${data.error || 'Unknown error'}</td></tr>`;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                tableBody.innerHTML = `<tr><td colspan="6">Failed to load data. Please check the console for details.</td></tr>`;
            }
        }

        function renderLedgerTable(transactions) {
            tableBody.innerHTML = '';
            let totalDebits = 0;
            let totalCredits = 0;

            if (transactions.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px;">No transactions found for the selected criteria.</td></tr>`;
            } else {
                transactions.forEach(txn => {
                    const isDebit = txn.transaction_type === 'debit';
                    const amount = parseFloat(txn.amount);
                    const debitAmount = isDebit ? amount : 0;
                    const creditAmount = !isDebit ? amount : 0;

                    totalDebits += debitAmount;
                    totalCredits += creditAmount;

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${txn.transaction_date}</td>
                        <td>${txn.account_code} - ${txn.account_name}</td>
                        <td>${txn.description || ''}</td>
                        <td class="amount">${isDebit ? '$' + amount.toFixed(2) : '-'}</td>
                        <td class="amount">${!isDebit ? '$' + amount.toFixed(2) : '-'}</td>
                        <td>${txn.receipt_image_url ? `<a href="${txn.receipt_image_url}" target="_blank" class="btn-icon"><i class="fas fa-receipt"></i></a>` : ''}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }

            // Update summary totals
            document.getElementById('total-debits').textContent = '$' + totalDebits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('total-credits').textContent = '$' + totalCredits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const netMovement = totalDebits - totalCredits;
            const netMovementEl = document.getElementById('net-movement');
            netMovementEl.textContent = '$' + netMovement.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            netMovementEl.style.color = netMovement >= 0 ? 'var(--success-dark)' : 'var(--danger-dark)';
        }

        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchLedgerData();
        });

        filterForm.addEventListener('reset', () => {
            setTimeout(() => fetchLedgerData(), 0);
        });
    </script>
</body>
</html>
