<?php
/**
 * expense_management.php
 *
 * A comprehensive page for managing school finances. It includes:
 * - Chart of Accounts management (Add/Edit/Delete).
 * - Manual Journal Entry creation.
 * - Vehicle Expense, Supplier Payment, and Service Payment entry with QR Code upload.
 * - Bulk expense processing via CSV Requisition uploads.
 * - A dynamic, filterable General Ledger for all transactions.
 *
 * All POST logic for account management is handled at the top of the script
 * before any HTML output to ensure redirects and session messages function correctly.
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
            
            // Check if the account code is already used by THIS school
            $stmt_check = $pdo->prepare("SELECT id FROM accounts WHERE account_code = ? AND school_id = ?");
            $stmt_check->execute([$account_code, $school_id]);
            if ($stmt_check->fetch()) {
                throw new Exception("The Account Code '" . htmlspecialchars($account_code) . "' is already in use for your school. Please choose a unique code.");
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

// Filter accounts by type for use in form dropdowns
$asset_accounts = array_filter($accounts, fn($acc) => $acc['account_type'] == 'asset');
$expense_accounts = array_filter($accounts, fn($acc) => $acc['account_type'] == 'expense');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management System</title>
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
        
        /* Grid layout for forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        /* Receipt Upload Controls */
        .receipt-upload-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .receipt-filename {
            margin-left: 10px;
            font-style: italic;
            color: #555;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #receipt-thumbnail {
            max-height: 40px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        /* General Ledger Styles */
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
        #ledger-table-body .loading-row td,
        #ledger-table-body .no-results-row td { 
            text-align: center; 
            padding: 40px; 
            font-style: italic; 
            color: #777; 
        }

        /* QR Code Modal Styles */
        .qr-modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.6); 
            justify-content: center; 
            align-items: center; 
        }
        .qr-modal-content { 
            background-color: #fff; 
            padding: 25px; 
            border: 1px solid #888; 
            width: 90%; 
            max-width: 400px; 
            text-align: center; 
            border-radius: 8px; 
            position: relative; 
        }
        .qr-modal .close { 
            position: absolute; 
            top: 10px; 
            right: 20px; 
            color: #aaa; 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        #qr-code-img { 
            max-width: 100%; 
            height: auto; 
            margin-top: 15px; 
            border: 1px solid #ddd; 
        }
        #qr-status { 
            margin-top: 15px; 
            font-weight: bold; 
            color: #333; 
        }
        .variance-favorable { color: #28a745; }
        .variance-unfavorable { color: #dc3545; }
    </style>
</head>
<body>
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
            <button class="tab-link" onclick="openTab(event, 'vehicle')"><i class="fas fa-truck"></i> Vehicle Expense</button>
            <button class="tab-link" onclick="openTab(event, 'supplier')"><i class="fas fa-dolly"></i> Supplier Payment</button>
            <button class="tab-link" onclick="openTab(event, 'service')"><i class="fas fa-concierge-bell"></i> Service Payment</button>
            <button class="tab-link active" onclick="openTab(event, 'ledger')"><i class="fas fa-book-open"></i> General Ledger</button>
        </div>

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
                                    <td class="amount"><?= format_currency($account['balance']) ?></td>
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
        
        <div id="journal" class="tab-content">
             <div class="card">
                <h2>Manual Journal Entry</h2>
                <form id="journalForm" onsubmit="submitJournal(event)">
                    <div class="form-group"><label>Entry Date</label><input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2" placeholder="Enter a brief description for this journal entry..."></textarea></div>
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
                            <?php foreach ($asset_accounts as $acc): ?>
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

        <div id="vehicle" class="tab-content">
            <div class="card">
                <h2>Log Vehicle Expense</h2>
                <form id="vehicleExpenseForm" onsubmit="submitForm(event, 'vehicle_expense.php', 'vehicleExpenseForm')">
                    <input type="hidden" name="uploaded_receipt_path" class="uploaded-receipt-path">
                    <div class="form-grid">
                        <div class="form-group"><label for="vex_date">Date</label><input type="date" id="vex_date" name="expense_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="form-group"><label for="vex_vehicle">Vehicle ID</label><input type="text" id="vex_vehicle" name="vehicle_id" placeholder="e.g., KDA 123X" required></div>
                        <div class="form-group"><label for="vex_pay_from">Pay From</label>
                            <select id="vex_pay_from" name="payment_account_id" required>
                                <option value="">-- Select Asset Account --</option>
                                <?php foreach ($asset_accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="vex_type">Expense Type</label>
                            <select id="vex_type" name="expense_type" required>
                                <option value="">-- Select Type --</option><option value="fuel">Fuel</option><option value="maintenance">Maintenance</option><option value="insurance">Insurance</option><option value="repairs">Repairs</option><option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label for="vex_account">Expense Account</label>
                             <select id="vex_account" name="account_id" required>
                                <option value="">-- Select Expense Account --</option>
                                <?php foreach ($expense_accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="vex_amount">Amount</label><input type="number" id="vex_amount" name="amount" step="0.01" required></div>
                        <div class="form-group"><label for="vex_odometer">Odometer (Optional)</label><input type="number" id="vex_odometer" name="odometer" placeholder="e.g., 150234"></div>
                        <div class="form-group">
                            <label>Receipt (Optional)</label>
                            <div class="receipt-upload-controls">
                                <input type="file" name="receipt_image" accept="image/*" class="receipt-file-input" style="display:none;">
                                <button type="button" class="btn-secondary" onclick="showQrCode(this)"><i class="fas fa-mobile-alt"></i> Take with Phone</button>
                                <button type="button" class="btn-secondary receipt-action-btn" data-action="upload"><i class="fas fa-folder-open"></i> Upload File</button>
                                <span class="receipt-filename"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;"><label for="vex_desc">Description (Optional)</label><textarea id="vex_desc" name="description" rows="2" placeholder="e.g., Replaced front brake pads..."></textarea></div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Post Expense</button></div>
                </form>
            </div>
        </div>

        <div id="supplier" class="tab-content">
             <div class="card">
                <h2>Log Supplier Payment</h2>
                <form id="supplierPaymentForm" onsubmit="submitForm(event, 'supplier_payment.php', 'supplierPaymentForm')">
                    <input type="hidden" name="uploaded_receipt_path" class="uploaded-receipt-path">
                    <div class="form-grid">
                        <div class="form-group"><label for="sup_date">Date</label><input type="date" id="sup_date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="form-group"><label for="sup_name">Supplier Name</label><input type="text" id="sup_name" name="supplier_name" placeholder="e.g., Naivas Supermarket" required></div>
                        <div class="form-group"><label for="sup_pay_from">Pay From</label>
                            <select id="sup_pay_from" name="payment_account_id" required>
                                <option value="">-- Select Asset Account --</option>
                                <?php foreach ($asset_accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                         <div class="form-group"><label for="sup_account">Expense Category</label>
                             <select id="sup_account" name="account_id" required>
                                <option value="">-- Select Expense Account --</option>
                                <?php foreach ($expense_accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="sup_amount">Amount</label><input type="number" id="sup_amount" name="amount" step="0.01" required></div>
                        <div class="form-group"><label for="sup_invoice">Invoice # (Optional)</label><input type="text" id="sup_invoice" name="invoice_number" placeholder="e.g., INV-1023"></div>
                        <div class="form-group">
                            <label>Receipt (Optional)</label>
                            <div class="receipt-upload-controls">
                                <input type="file" name="receipt_image" accept="image/*" class="receipt-file-input" style="display:none;">
                                <button type="button" class="btn-secondary" onclick="showQrCode(this)"><i class="fas fa-mobile-alt"></i> Take with Phone</button>
                                <button type="button" class="btn-secondary receipt-action-btn" data-action="upload"><i class="fas fa-folder-open"></i> Upload File</button>
                                <span class="receipt-filename"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;"><label for="sup_desc">Description (Optional)</label><textarea id="sup_desc" name="description" rows="2" placeholder="e.g., Purchase of weekly groceries..."></textarea></div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Post Payment</button></div>
                </form>
            </div>
        </div>
        
        <div id="service" class="tab-content">
            <div class="card">
                <h2>Log Service Payment</h2>
                <form id="servicePaymentForm" onsubmit="submitForm(event, 'service_payment.php', 'servicePaymentForm')">
                    <input type="hidden" name="uploaded_receipt_path" class="uploaded-receipt-path">
                     <div class="form-grid">
                        <div class="form-group"><label for="ser_date">Date</label><input type="date" id="ser_date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="form-group"><label for="ser_name">Provider Name</label><input type="text" id="ser_name" name="provider_name" placeholder="e.g., Kenya Power, Nairobi Water" required></div>
                        <div class="form-group"><label for="ser_pay_from">Pay From</label>
                            <select id="ser_pay_from" name="payment_account_id" required>
                                <option value="">-- Select Asset Account --</option>
                                <?php foreach ($asset_accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                         <div class="form-group"><label for="ser_account">Expense Category</label>
                             <select id="ser_account" name="account_id" required>
                                <option value="">-- Select Expense Account --</option>
                                <?php foreach ($expense_accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="ser_amount">Amount</label><input type="number" id="ser_amount" name="amount" step="0.01" required></div>
                        <div class="form-group">
                            <label>Receipt (Optional)</label>
                            <div class="receipt-upload-controls">
                                <input type="file" name="receipt_image" accept="image/*" class="receipt-file-input" style="display:none;">
                                <button type="button" class="btn-secondary" onclick="showQrCode(this)"><i class="fas fa-mobile-alt"></i> Take with Phone</button>
                                <button type="button" class="btn-secondary receipt-action-btn" data-action="upload"><i class="fas fa-folder-open"></i> Upload File</button>
                                <span class="receipt-filename"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;"><label for="ser_desc">Description</label><textarea id="ser_desc" name="description" rows="2" placeholder="e.g., Payment for Electricity Bill..." required></textarea></div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Post Payment</button></div>
                </form>
            </div>
        </div>
        
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
                           </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
    
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <span class="close" onclick="closeQrModal()">&times;</span>
            <h3>Scan with your Phone</h3>
            <p>Open your phone's camera and scan the code below.</p>
            <img id="qr-code-img" src="" alt="QR Code">
            <div id="qr-status"><i class="fas fa-spinner fa-spin"></i> Waiting for scan...</div>
        </div>
    </div>
    
    <script>
        // Store accounts data from PHP for use in JavaScript
        const accountsData = <?= json_encode($accounts) ?>;
        const currencySymbol = '<?= $_SESSION['currency_symbol'] ?? '$' ?>';

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

            if (tabName === 'ledger') {
                fetchLedgerData();
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab') || 'ledger'; 
            const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
            if (tabButton) {
                tabButton.click();
            } else {
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
        
        window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = 'none'; };

        // --- Journal Entry Functions ---
        function addJournalLine() {
            const container = document.getElementById('journalLines');
            const newLine = document.createElement('div');
            newLine.className = 'journal-line';
            newLine.style.cssText = 'display:flex; gap:10px; margin-bottom:10px;';
            let optionsHTML = accountsData.map(acc => `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`).join('');
            newLine.innerHTML = `<select name="account[]" required style="flex:3;"><option value="">Select Account</option>${optionsHTML}</select><input type="number" name="debit[]" step="0.01" placeholder="Debit" style="flex:1;"><input type="number" name="credit[]" step="0.01" placeholder="Credit" style="flex:1;">`;
            container.appendChild(newLine);
        }

        function calculateJournalTotals() {
            let totalDebit = 0, totalCredit = 0;
            document.querySelectorAll('.journal-line').forEach(line => {
                totalDebit += parseFloat(line.querySelector('[name="debit[]"]').value) || 0;
                totalCredit += parseFloat(line.querySelector('[name="credit[]"]').value) || 0;
            });
            document.getElementById('journalTotal').innerHTML = `Total Debit: ${formatCurrencyJS(totalDebit)} | Total Credit: ${formatCurrencyJS(totalCredit)}`;
            return { totalDebit, totalCredit };
        }

        document.getElementById('journalLines').addEventListener('input', (e) => {
            if (e.target.closest('.journal-line')) {
                const row = e.target.closest('.journal-line');
                const debitInput = row.querySelector('[name="debit[]"]');
                const creditInput = row.querySelector('[name="credit[]"]');
                if (e.target === debitInput && debitInput.value) creditInput.value = '';
                else if (e.target === creditInput && creditInput.value) debitInput.value = '';
                calculateJournalTotals();
            }
        });

        function submitJournal(e) {
            e.preventDefault();
            const { totalDebit, totalCredit } = calculateJournalTotals();
            if (Math.abs(totalDebit - totalCredit) > 0.01) {
                alert('Debit and Credit totals must match.'); return;
            }
            if (totalDebit === 0) {
                 alert('Journal entry cannot be empty.'); return;
            }
            submitForm(e, 'journal_entry.php', 'journalForm');
        }
        
        // --- Generic Async Form Submission Handler ---
        async function submitForm(e, url, formId) {
            e.preventDefault();
            const form = document.getElementById(formId);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitButton.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(url, { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                
                const result = await response.json();
                if (result.success) {
                    alert('Transaction posted successfully!');
                    form.reset();
                    form.querySelectorAll('.receipt-filename').forEach(span => span.textContent = '');
                    form.querySelectorAll('.uploaded-receipt-path').forEach(input => input.value = '');
                    if (document.getElementById('ledger').classList.contains('active')) {
                        fetchLedgerData();
                    }
                } else {
                    alert(`Error: ${result.error || 'An unknown error occurred.'}`);
                }
            } catch (error) {
                console.error('Submission error:', error);
                alert('A network or server error occurred. Please check the console and try again.');
            } finally {
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            }
        }

        // --- Receipt Upload Buttons & QR Code Feature ---
        let qrPollingInterval;
        let activeQrForm = null;

        document.body.addEventListener('click', function(e) {
            if (e.target.matches('.receipt-action-btn')) {
                const fileInput = e.target.closest('.receipt-upload-controls').querySelector('.receipt-file-input');
                if (!fileInput) return;
                fileInput.removeAttribute('capture');
                fileInput.click();
            }
        });

        document.body.addEventListener('change', function(e) {
            if (e.target.matches('.receipt-file-input')) {
                const form = e.target.closest('form');
                if (!form) return;
                const filenameSpan = form.querySelector('.receipt-filename');
                if (filenameSpan) {
                    if (e.target.files && e.target.files.length > 0) {
                        filenameSpan.textContent = e.target.files[0].name;
                        form.querySelector('.uploaded-receipt-path').value = '';
                    } else {
                        filenameSpan.textContent = '';
                    }
                }
            }
        });
        
        async function showQrCode(button) {
            const modal = document.getElementById('qrModal');
            const qrImg = document.getElementById('qr-code-img');
            const qrStatus = document.getElementById('qr-status');
            activeQrForm = button.closest('form');

            qrImg.src = '';
            qrStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating QR Code...';
            modal.style.display = 'flex';

            try {
                const response = await fetch('generate_qr.php');
                const data = await response.json();
                if (data.success) {
                    qrImg.style.display = 'block';
                    qrImg.src = data.qr_code;
                    qrStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for phone scan...';
                    startPolling(data.token);
                } else {
                    qrImg.style.display = 'none';
                    qrStatus.innerHTML = `<span style="color:red;">Error: ${data.error || 'Could not generate QR code. Check server logs.'}</span>`;
                }
            } catch (error) {
                qrImg.style.display = 'none';
                qrStatus.innerHTML = '<span style="color:red;">Error: Could not connect to the server.</span>';
                console.error(error);
            }
        }

        function startPolling(token) {
            let attempts = 0;
            const maxAttempts = 100; // Poll for 5 minutes (100 attempts * 3 seconds)

            qrPollingInterval = setInterval(async () => {
                if (attempts++ > maxAttempts) {
                    closeQrModal('QR Code expired. Please try again.');
                    return;
                }
                try {
                    // *** THIS IS THE FIX ***
                    // Added { credentials: 'same-origin' } to ensure the session cookie is sent.
                    const response = await fetch(`check_upload_status.php?token=${token}`, { credentials: 'same-origin' });
                    // *** END OF FIX ***
                    
                    if (!response.ok) {
                        // Handle non-200 responses, like the 401 you were seeing
                        if (response.status === 401) {
                             closeQrModal('Authentication error. Please refresh the page and try again.');
                        }
                        return; // Stop this attempt if the response was not OK
                    }

                    const data = await response.json();
                    if (data.status === 'completed') {
                        const filenameSpan = activeQrForm.querySelector('.receipt-filename');
                        const hiddenPathInput = activeQrForm.querySelector('.uploaded-receipt-path');
                        if (filenameSpan && hiddenPathInput) {
                            filenameSpan.innerHTML = `<span>${data.filePath.split('/').pop()}</span> <img id="receipt-thumbnail" src="${data.filePath}" alt="Thumbnail">`;
                            hiddenPathInput.value = data.filePath;
                            activeQrForm.querySelector('.receipt-file-input').value = ''; 
                        }
                        closeQrModal();
                    } else if (data.status === 'expired') {
                        closeQrModal('QR Code expired. Please try again.');
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                    closeQrModal('A connection error occurred during polling.');
                }
            }, 3000);
        }

        function closeQrModal(message = null) {
            if (qrPollingInterval) clearInterval(qrPollingInterval);
            document.getElementById('qrModal').style.display = 'none';
            activeQrForm = null;
            if (message) alert(message);
        }

        // --- General Ledger Logic ---
        const filterForm = document.getElementById('ledger-filter-form');
        const tableBody = document.getElementById('ledger-table-body');
        const totalDebitsSpan = document.getElementById('total-debits');
        const totalCreditsSpan = document.getElementById('total-credits');
        const netMovementSpan = document.getElementById('net-movement');

        function formatCurrencyJS(amount) {
            return currencySymbol + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        async function fetchLedgerData() {
            tableBody.innerHTML = '<tr class="loading-row"><td colspan="6"><i class="fas fa-spinner fa-spin"></i> Loading transactions...</td></tr>';
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);

            try {
                const response = await fetch(`expense_transactions.php?${params.toString()}`);
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                const data = await response.json();

                if (data.success) {
                    renderLedgerTable(data.transactions);
                    // Update summary
                    const debits = data.summary.total_debits;
                    const credits = data.summary.total_credits;
                    const net = debits - credits;

                    totalDebitsSpan.textContent = formatCurrencyJS(debits);
                    totalCreditsSpan.textContent = formatCurrencyJS(credits);
                    netMovementSpan.textContent = formatCurrencyJS(net);

                    if (net >= 0) {
                        netMovementSpan.classList.add('variance-favorable');
                        netMovementSpan.classList.remove('variance-unfavorable');
                    } else {
                        netMovementSpan.classList.add('variance-unfavorable');
                        netMovementSpan.classList.remove('variance-favorable');
                    }

                } else {
                    tableBody.innerHTML = `<tr class="no-results-row"><td colspan="6">Error: ${data.error}</td></tr>`;
                }
            } catch (error) {
                console.error('Error fetching ledger data:', error);
                tableBody.innerHTML = `<tr class="no-results-row"><td colspan="6">Failed to load data. Please check the console for errors.</td></tr>`;
            }
        }

        function renderLedgerTable(transactions) {
            tableBody.innerHTML = ''; // Clear previous results or loading indicator

            if (!transactions || transactions.length === 0) {
                tableBody.innerHTML = '<tr class="no-results-row"><td colspan="6">No transactions found for the selected criteria.</td></tr>';
                return;
            }

            transactions.forEach(tx => {
                const row = document.createElement('tr');
                const debitAmount = tx.transaction_type === 'debit' ? formatCurrencyJS(tx.amount) : '';
                const creditAmount = tx.transaction_type === 'credit' ? formatCurrencyJS(tx.amount) : '';

                let receiptLink = '';
                if (tx.receipt_image_url) {
                    receiptLink = `<a href="${tx.receipt_image_url}" target="_blank" class="btn-icon" title="View Receipt"><i class="fas fa-receipt"></i></a>`;
                }

                row.innerHTML = `
                    <td>${tx.transaction_date}</td>
                    <td>${tx.account_code} - ${tx.account_name}</td>
                    <td>${tx.description}</td>
                    <td class="amount">${debitAmount}</td>
                    <td class="amount">${creditAmount}</td>
                    <td>${receiptLink}</td>
                `;
                tableBody.appendChild(row);
            });
        }

        filterForm.addEventListener('submit', (e) => { e.preventDefault(); fetchLedgerData(); });
        filterForm.addEventListener('reset', () => { setTimeout(() => fetchLedgerData(), 0); });
        
    </script>
</body>
</html>