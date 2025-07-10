<?php
// expense_management.php - Corrected Logic Order & Complete Code
// The POST handling logic is moved to the top of the file before any HTML output.
// session_start() is removed from here because it is already called in header.php
require_once 'config.php';
require_once 'functions.php';

// --- POST Request Handling for Account Management (Add/Edit/Delete) ---
// This block must run before any HTML is outputted to allow for header() redirects.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // We need to start the session here to access session variables before including the header
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $school_id = $_SESSION['school_id'] ?? null;
    // Basic check to ensure we have a school context
    if(!$school_id){
        // Redirect to login or show an error if no school_id is found in the session
        header("Location: login.php?error=session_expired");
        exit();
    }

    $action = $_POST['action'];
    try {
        if ($action === 'add_account') {
            if (empty(trim($_POST['account_code'])) || empty(trim($_POST['account_name'])) || empty(trim($_POST['account_type']))) {
                 throw new Exception("Account code, name, and type are required.");
            }
            createAccount(
                $pdo,
                $school_id,
                trim($_POST['account_code']),
                trim($_POST['account_name']),
                trim($_POST['account_type']),
                $_POST['opening_balance'] ?? 0
            );
        } elseif ($action === 'edit_account') {
            updateAccount(
                $pdo,
                $_POST['id'],
                trim($_POST['account_code']),
                trim($_POST['account_name']),
                trim($_POST['account_type']),
                $school_id
            );
        } elseif ($action === 'delete_account') {
            deleteAccount($pdo, $_POST['id'], $school_id);
        }
        
        // Redirect to prevent form resubmission on refresh
        header("Location: expense_management.php?success=1");
        exit();

    } catch (Exception $e) {
        // Store error message to display it later on the page
        $error_message = $e->getMessage();
    }
}

// Now include the header, which will output the HTML head and navigation.
// The $school_id variable will be available from this point onwards from header.php
require_once 'header.php';

// --- Data Fetching for Page Load ---
$accounts = getChartOfAccounts($pdo, $school_id);

// Unified query to get all transactions
$stmt = $pdo->prepare("
    SELECT p.id, p.payment_date AS transaction_date, CONCAT('Payment from ', s.name) AS description, p.amount, 'credit' AS transaction_type, a.account_code, a.account_name, NULL AS receipt_image_url
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN students s ON i.student_id = s.id
    LEFT JOIN accounts a ON p.coa_account_id = a.id
    WHERE p.school_id = :school_id1 AND p.coa_account_id IS NOT NULL
    UNION ALL
    SELECT e.id, e.transaction_date, e.description, e.amount, e.transaction_type, a.account_code, a.account_name, e.receipt_image_url
    FROM expenses e
    JOIN accounts a ON e.account_id = a.id
    WHERE e.school_id = :school_id2
    ORDER BY transaction_date DESC
");
$stmt->execute([':school_id1' => $school_id, ':school_id2' => $school_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .qr-modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .qr-modal-content { background-color: #fff; margin: 15% auto; padding: 20px; border-radius: 8px; text-align: center; width: 90%; max-width: 320px; }
        .qr-modal-content h3 { margin-top: 0; }
        #qrCodeImage { max-width: 100%; height: auto; margin-top: 15px; }
        .receipt-upload-area { display: flex; align-items: center; gap: 10px; }
        .receipt-preview { font-style: italic; color: #27ae60; margin-top: 10px; }
        .account-form { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-in-out; border-top: 1px solid var(--border); margin-top: 15px; padding-top: 0; }
        .account-form.show { max-height: 600px; padding-top: 20px; }
        
        /* Fix for balance alignment */
        .amount-header {
            text-align: right;
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

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Action completed successfully!</div>
    <?php endif; ?>

    <div class="tab-container">
        <div class="tabs">
            <button class="tab-link active" onclick="openTab(event, 'charts')"><i class="fas fa-chart-pie"></i> General Ledger</button>
            <button class="tab-link" onclick="openTab(event, 'journal')"><i class="fas fa-book"></i> Journal Entry</button>
            <button class="tab-link" onclick="openTab(event, 'service')"><i class="fas fa-hand-holding-usd"></i> Service Payments</button>
            <button class="tab-link" onclick="openTab(event, 'supplier')"><i class="fas fa-truck-loading"></i> Supplier Payments</button>
            <button class="tab-link" onclick="openTab(event, 'vehicle')"><i class="fas fa-car"></i> Vehicle Expenses</button>
            <button class="tab-link" onclick="openTab(event, 'history')"><i class="fas fa-history"></i> Transaction History</button>
        </div>

        <div id="charts" class="tab-content active">
            <div class="card">
                <h2>Chart of Accounts</h2>
                <div class="table-actions">
                    <button type="button" class="btn-add" onclick="toggleAccountForm()"><i class="fas fa-plus"></i> Add New Account</button>
                </div>
                <div id="accountForm" class="account-form">
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <input type="hidden" name="action" value="add_account">
                        <div class="form-group"><label>Account Type</label><select name="account_type" required><option value="asset">Asset</option><option value="liability">Liability</option><option value="equity">Equity</option><option value="revenue">Revenue</option><option value="expense">Expense</option></select></div>
                        <div class="form-group"><label>Account Code</label><input type="text" name="account_code" required></div>
                        <div class="form-group"><label>Account Name</label><input type="text" name="account_name" required></div>
                        <div class="form-group"><label>Opening Balance</label><input type="number" step="0.01" name="opening_balance" value="0"></div>
                        <button type="submit" class="btn-success"><i class="fas fa-plus"></i> Add Account</button>
                    </form>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th class="amount-header">Balance</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr><td colspan="5" class="text-center">No accounts found.</td></tr>
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
                                            <button class="btn-icon" title="Delete" onclick="confirmDelete(<?= $account['id'] ?>)"><i class="fas fa-trash"></i></button>
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
                <h2>Journal Entry</h2>
                <form id="journalForm" onsubmit="submitJournal(event)">
                    <div class="form-group"><label>Entry Date</label><input type="date" name="entry_date" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
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

        <div id="service" class="tab-content">
            <div class="card">
                <h2>Service Provider Payments</h2>
                <form id="serviceForm" onsubmit="submitForm(event, 'service_payment.php', 'serviceForm')" enctype="multipart/form-data">
                    <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date" required></div>
                    <div class="form-group"><label>Provider Name</label><input type="text" name="provider_name" required></div>
                    <div class="form-group"><label>Expense Account</label><select name="account_id" required><option value="">Select Account</option><?php foreach ($accounts as $acc): if($acc['account_type'] == 'expense'): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option><?php endif; endforeach; ?></select></div>
                    <div class="form-group"><label>Amount</label><input type="number" name="amount" step="0.01" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                    <div class="form-group"><label>Receipt Image</label>
                        <div class="receipt-upload-area">
                            <input type="file" name="receipt_image" accept="image/*" capture="environment">
                            <button type="button" class="btn" onclick="scanWithPhone('serviceForm')"><i class="fas fa-qrcode"></i> Scan with Phone</button>
                        </div>
                        <div class="receipt-preview" id="serviceForm_preview"></div>
                        <input type="hidden" name="receipt_image_path" id="serviceForm_path">
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Record Payment</button></div>
                </form>
            </div>
        </div>

        <div id="supplier" class="tab-content">
            <div class="card">
                <h2>Supplier Payments</h2>
                <form id="supplierForm" onsubmit="submitForm(event, 'supplier_payment.php', 'supplierForm')" enctype="multipart/form-data">
                    <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date" required></div>
                    <div class="form-group"><label>Supplier Name</label><input type="text" name="supplier_name" required></div>
                    <div class="form-group"><label>Invoice Number</label><input type="text" name="invoice_number"></div>
                    <div class="form-group"><label>Expense Account</label><select name="account_id" required><option value="">Select Account</option><?php foreach ($accounts as $acc): if($acc['account_type'] == 'expense'): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option><?php endif; endforeach; ?></select></div>
                    <div class="form-group"><label>Amount</label><input type="number" name="amount" step="0.01" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                    <div class="form-group"><label>Invoice/Receipt Image</label>
                        <div class="receipt-upload-area">
                            <input type="file" name="receipt_image" accept="image/*" capture="environment">
                            <button type="button" class="btn" onclick="scanWithPhone('supplierForm')"><i class="fas fa-qrcode"></i> Scan with Phone</button>
                        </div>
                        <div class="receipt-preview" id="supplierForm_preview"></div>
                        <input type="hidden" name="receipt_image_path" id="supplierForm_path">
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Record Payment</button></div>
                </form>
            </div>
        </div>

        <div id="vehicle" class="tab-content">
            <div class="card">
                <h2>Vehicle Expenses</h2>
                <form id="vehicleForm" onsubmit="submitForm(event, 'vehicle_expense.php', 'vehicleForm')" enctype="multipart/form-data">
                    <div class="form-group"><label>Expense Date</label><input type="date" name="expense_date" required></div>
                    <div class="form-group"><label>Vehicle ID/Registration</label><input type="text" name="vehicle_id" required></div>
                    <div class="form-group"><label>Expense Type</label><select name="expense_type" required><option value="fuel">Fuel</option><option value="maintenance">Maintenance</option><option value="insurance">Insurance</option><option value="repairs">Repairs</option><option value="other">Other</option></select></div>
                    <div class="form-group"><label>Expense Account</label><select name="account_id" required><option value="">Select Account</option><?php foreach ($accounts as $acc): if($acc['account_type'] == 'expense'): ?><option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?></option><?php endif; endforeach; ?></select></div>
                    <div class="form-group"><label>Amount</label><input type="number" name="amount" step="0.01" required></div>
                    <div class="form-group"><label>Odometer Reading</label><input type="number" name="odometer" step="0.1"></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                    <div class="form-group"><label>Receipt Image</label>
                         <div class="receipt-upload-area">
                            <input type="file" name="receipt_image" accept="image/*" capture="environment">
                            <button type="button" class="btn" onclick="scanWithPhone('vehicleForm')"><i class="fas fa-qrcode"></i> Scan with Phone</button>
                        </div>
                        <div class="receipt-preview" id="vehicleForm_preview"></div>
                        <input type="hidden" name="receipt_image_path" id="vehicleForm_path">
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-success"><i class="fas fa-save"></i> Record Expense</button></div>
                </form>
            </div>
        </div>

        <div id="history" class="tab-content">
            <div class="card">
                <h2>Transaction History</h2>
                <div class="table-container">
                    <table id="transactions-table">
                        <thead><tr><th>Date</th><th>Account</th><th>Description</th><th>Type</th><th class="amount-header">Debit</th><th class="amount-header">Credit</th><th>Receipt</th></tr></thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="7" class="text-center">No transactions found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($txn['transaction_date'])) ?></td>
                                    <td><?= htmlspecialchars($txn['account_code'] . ' - ' . $txn['account_name']) ?></td>
                                    <td><?= htmlspecialchars($txn['description']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($txn['transaction_type'])) ?></td>
                                    <td class="amount"><?= $txn['transaction_type'] === 'debit' ? '$' . number_format($txn['amount'], 2) : '-' ?></td>
                                    <td class="amount"><?= $txn['transaction_type'] === 'credit' ? '$' . number_format($txn['amount'], 2) : '-' ?></td>
                                    <td>
                                        <?php if (!empty($txn['receipt_image_url'])): ?>
                                            <a href="<?= htmlspecialchars($txn['receipt_image_url']) ?>" target="_blank" title="View Receipt" class="btn-icon"><i class="fas fa-receipt"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div id="editModal" class="modal"><div class="modal-content"><span class="close" onclick="closeEditModal()">&times;</span><h3>Edit Account</h3><form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"><input type="hidden" name="action" value="edit_account"><input type="hidden" name="id" id="edit_id"><div class="form-group"><label>Account Code</label><input type="text" name="account_code" id="edit_code" required></div><div class="form-group"><label>Account Name</label><input type="text" name="account_name" id="edit_name" required></div><div class="form-group"><label>Account Type</label><select name="account_type" id="edit_type" required><option value="asset">Asset</option><option value="liability">Liability</option><option value="equity">Equity</option><option value="revenue">Revenue</option><option value="expense">Expense</option></select></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button><button type="submit" class="btn-success"><i class="fas fa-save"></i> Save Changes</button></div></form></div></div>

    <!-- Delete Account Modal -->
    <div id="deleteModal" class="modal"><div class="modal-content"><span class="close" onclick="closeDeleteModal()">&times;</span><h3>Confirm Deletion</h3><p>Are you sure you want to delete this account? This action cannot be undone.</p><form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"><input type="hidden" name="action" value="delete_account"><input type="hidden" name="id" id="delete_id"><div class="form-actions"><button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button><button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Confirm Delete</button></div></form></div></div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <span class="close" onclick="closeQrModal()">&times;</span>
            <h3>Scan with your Phone</h3>
            <p>Open your phone's camera and scan the code below to upload the receipt.</p>
            <img id="qrCodeImage" src="" alt="QR Code">
            <p style="margin-top: 15px; font-style: italic;">Waiting for upload...</p>
        </div>
    </div>
    
    <!-- The closing </div> for "container" is in footer.php or at the end of the body -->
    <script>
        const accountsData = <?= json_encode($accounts) ?>;

        function openTab(evt, tabName) {
            document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));
            document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove("active"));
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('.tab-link').click();
        });

        function toggleAccountForm() {
            document.getElementById('accountForm').classList.toggle('show');
        }

        function openEditModal(account) {
            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
            modal.querySelector('#edit_id').value = account.id;
            modal.querySelector('#edit_code').value = account.account_code;
            modal.querySelector('#edit_name').value = account.account_name;
            modal.querySelector('#edit_type').value = account.account_type;
        }
        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

        function confirmDelete(id) {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'flex';
            modal.querySelector('#delete_id').value = id;
        }
        function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

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
            document.getElementById('journalTotal').innerHTML = `Total Debit: $${totalDebit.toFixed(2)} | Total Credit: $${totalCredit.toFixed(2)}`;
            return { totalDebit, totalCredit };
        }

        document.getElementById('journalLines').addEventListener('input', function(e) {
            if (e.target.closest('.journal-line')) {
                const row = e.target.closest('.journal-line');
                const debitInput = row.querySelector('[name="debit[]"]');
                const creditInput = row.querySelector('[name="credit[]"]');
                if (e.target === debitInput && debitInput.value) creditInput.value = '';
                else if (e.target === creditInput && creditInput.value) debitInput.value = '';
                calculateJournalTotals();
            }
        });

        let pollingInterval;
        let currentFormId;

        function scanWithPhone(formId) {
            currentFormId = formId;
            fetch('generate_qr.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('qrCodeImage').src = data.qrCode;
                        document.getElementById('qrModal').style.display = 'block';
                        startPolling(data.token);
                    } else {
                        alert('Error generating QR code: ' + data.error);
                    }
                })
                .catch(err => console.error('QR generation failed:', err));
        }

        function startPolling(token) {
            pollingInterval = setInterval(() => {
                fetch(`check_upload_status.php?token=${token}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'completed') {
                            stopPolling();
                            closeQrModal();
                            const previewEl = document.getElementById(`${currentFormId}_preview`);
                            const pathEl = document.getElementById(`${currentFormId}_path`);
                            const filename = data.filePath.split('/').pop();
                            previewEl.textContent = `✓ Uploaded: ${filename}`;
                            pathEl.value = data.filePath;
                            document.querySelector(`#${currentFormId} input[name='receipt_image']`).disabled = true;
                        }
                    })
                    .catch(err => console.error('Polling failed:', err));
            }, 3000);
        }

        function stopPolling() {
            clearInterval(pollingInterval);
        }

        function closeQrModal() {
            stopPolling();
            document.getElementById('qrModal').style.display = 'none';
        }

        function submitForm(event, url, formId) {
            event.preventDefault();
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            
            fetch(url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Entry recorded successfully!');
                    window.location.reload(); 
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Submission Error:', error);
                alert('An unexpected error occurred. Please check the console and try again.');
            });
        }
        
        function submitJournal(e) {
            e.preventDefault();
            const { totalDebit, totalCredit } = calculateJournalTotals();
            if (Math.abs(totalDebit - totalCredit) > 0.01) {
                alert('Debit and Credit totals must match.');
                return;
            }
            submitForm(e, 'journal_entry.php', 'journalForm');
        }
    </script>
</body>
</html>
