<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'header.php';

// Fetch data for the forms (existing code)
$asset_accounts_stmt = $pdo->prepare("SELECT id, account_name, balance FROM accounts WHERE school_id = ? AND account_type = 'asset' ORDER BY account_name");
$asset_accounts_stmt->execute([$school_id]);
$accounts_list = $asset_accounts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payments sitting in "Undeposited Funds" (existing code)
$undeposited_stmt = $pdo->prepare("
    SELECT p.id, p.payment_date, p.amount, p.payment_method, s.name as student_name
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN accounts a ON p.coa_account_id = a.id
    WHERE p.school_id = ? AND a.account_name = 'Undeposited Funds' AND p.deposit_id IS NULL
    ORDER BY p.payment_date ASC
");
$undeposited_stmt->execute([$school_id]);
$undeposited_payments = $undeposited_stmt->fetchAll(PDO::FETCH_ASSOC);
$total_undeposited_amount = array_sum(array_column($undeposited_payments, 'amount'));


// --- NEW: Fetch data for the History Tab ---

// 1. Get all completed deposits
$deposits_history_stmt = $pdo->prepare("
    SELECT 
        d.deposit_date AS transaction_date,
        d.amount,
        d.memo,
        'Deposit' AS type,
        a.account_name AS detail
    FROM deposits d
    JOIN accounts a ON d.account_id = a.id
    WHERE d.school_id = ?
");
$deposits_history_stmt->execute([$school_id]);
$deposits_history = $deposits_history_stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Get all fund transfers (only showing the debit side for a cleaner log)
$transfers_history_stmt = $pdo->prepare("
    SELECT 
        e.transaction_date,
        e.amount,
        e.description AS memo,
        'Transfer' AS type,
        CONCAT('Transfer to ', a.account_name) AS detail
    FROM expenses e
    JOIN accounts a ON e.account_id = a.id
    WHERE e.school_id = ? AND e.type = 'transfer' AND e.transaction_type = 'debit'
");
$transfers_history_stmt->execute([$school_id]);
$transfers_history = $transfers_history_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Merge and sort the history records
$transaction_history = array_merge($deposits_history, $transfers_history);

// Sort the combined array by date in descending order
usort($transaction_history, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Banking & Accounting</title>
    <link rel="stylesheet" href="styles.css"> <style>
        /* ===== BANKING PAGE STYLES ===== */
        .deposit-summary {
            background: linear-gradient(135deg, hsl(206, 67%, 51%), hsl(195, 84%, 44%));
            color: var(--white);
            padding: 20px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        .deposit-summary h3 {
            border: none; color: var(--white); margin: 0 0 5px 0; padding: 0; font-size: 1.1rem; font-weight: 400; opacity: 0.9;
        }
        .deposit-summary .summary-amount {
            font-size: 1.8rem; font-weight: 700;
        }
        .transfer-form-grid {
            display: grid; grid-template-columns: 1fr auto 1fr; gap: 25px; align-items: center; margin-bottom: 20px;
        }
        .transfer-arrow {
            font-size: 2.5rem; color: var(--border); background-color: var(--light); border-radius: 50%;
            width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;
        }
        @media (max-width: 768px) {
            .transfer-form-grid { grid-template-columns: 1fr; }
            .transfer-arrow { transform: rotate(90deg); margin: 10px auto; }
        }
        .deposit-table tbody tr { transition: background-color 0.2s ease; cursor: pointer; }
        .deposit-table tbody tr:hover { background-color: #f1f9ff; }
        .deposit-table tr.selected { background-color: #e3f2fd !important; font-weight: 500; }
        .deposit-table tr.selected td { color: var(--primary); }
        .deposit-total-summary {
            text-align: right; margin-top: 20px; padding: 20px; background-color: #f8fafc;
            border-top: 3px solid var(--secondary); border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        .deposit-total-summary strong { font-size: 1.6rem; color: var(--primary); }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <div class="page-header-title">
            <h1><i class="fas fa-university"></i> Banking</h1>
            <p>Transfer funds, group payments for bank deposits, and view history.</p>
        </div>
    </div>

    <div class="tab-container">
        <div class="tabs">
            <button class="tab-link active" onclick="openTab(event, 'transfer')"><i class="fas fa-exchange-alt"></i> Transfer Funds</button>
            <button class="tab-link" onclick="openTab(event, 'deposit')"><i class="fas fa-piggy-bank"></i> Make Deposits</button>
            <button class="tab-link" onclick="openTab(event, 'history')"><i class="fas fa-history"></i> History</button>
        </div>

        <div id="transfer" class="tab-content active">
            <form action="process_transfer.php" method="POST">
                <div class="transfer-form-grid">
                    <div class="form-group">
                        <label for="from_account">Transfer Funds From</label>
                        <select name="from_account" id="from_account" required>
                            <option value="">-- Select Account --</option>
                            <?php foreach ($accounts_list as $account): ?>
                                <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_name']) ?> (Balance: $<?= number_format($account['balance'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="transfer-arrow"><i class="fas fa-long-arrow-alt-right"></i></div>
                    <div class="form-group">
                        <label for="to_account">Transfer Funds To</label>
                        <select name="to_account" id="to_account" required>
                            <option value="">-- Select Account --</option>
                            <?php foreach ($accounts_list as $account): ?>
                                <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="transfer_amount">Amount</label>
                    <input type="number" name="transfer_amount" id="transfer_amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="transfer_date">Date</label>
                    <input type="date" name="transfer_date" id="transfer_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="transfer_memo">Memo (Optional)</label>
                    <textarea name="transfer_memo" id="transfer_memo" rows="2"></textarea>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Transfer</button></div>
            </form>
        </div>

        <div id="deposit" class="tab-content">
            <div class="deposit-summary">
                <h3>Undeposited Funds</h3>
                <div class="summary-amount">$<?= number_format($total_undeposited_amount, 2) ?></div>
                <p><?= count($undeposited_payments) ?> payments waiting to be deposited.</p>
            </div>
            <form action="process_deposit.php" method="POST">
                <div class="form-group">
                    <label for="deposit_to_account">Deposit To Account</label>
                    <select name="deposit_to_account" id="deposit_to_account" required>
                         <option value="">-- Select Bank Account --</option>
                        <?php foreach ($accounts_list as $account): if($account['account_name'] !== 'Undeposited Funds'): ?>
                            <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_name']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="deposit_date">Deposit Date</label>
                    <input type="date" name="deposit_date" id="deposit_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="table-container">
                    <table class="deposit-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" onchange="toggleAllCheckboxes(this)"></th>
                                <th>Date</th>
                                <th>From Student</th>
                                <th>Method</th>
                                <th style="text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($undeposited_payments)): ?>
                                <tr><td colspan="5" class="text-center">No payments to deposit.</td></tr>
                            <?php else: ?>
                                <?php foreach ($undeposited_payments as $payment): ?>
                                <tr onclick="toggleCheckbox(this)">
                                    <td><input type="checkbox" name="payment_ids[]" value="<?= $payment['id'] ?>" class="deposit-checkbox" onchange="updateRowStyle(this)"></td>
                                    <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td><?= htmlspecialchars($payment['student_name']) ?></td>
                                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                    <td style="text-align:right;" data-amount="<?= $payment['amount'] ?>">$<?= number_format($payment['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <div class="deposit-total-summary">
                    <strong>Deposit Total: <span id="depositTotal">$0.00</span></strong>
                 </div>
                <div class="form-actions"><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Make Deposit</button></div>
            </form>
        </div>

        <div id="history" class="tab-content">
            <div class="card">
                <h3>Transaction History</h3>
                <p>A log of all bank deposits and internal fund transfers.</p>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Details / Memo</th>
                                <th style="text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaction_history)): ?>
                                <tr><td colspan="4" class="text-center">No transaction history found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transaction_history as $txn): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($txn['transaction_date'])) ?></td>
                                    <td><span class="badge badge-<?= strtolower(htmlspecialchars($txn['type'])) ?>"><?= htmlspecialchars($txn['type']) ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($txn['detail']) ?></strong>
                                        <?php if (!empty($txn['memo'])): ?>
                                            <br><small style="color: #6c757d;"><?= htmlspecialchars($txn['memo']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">$<?= number_format($txn['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
// Currency helper function
function formatCurrencyJS(amount) {
    const symbol = '<?= $_SESSION['currency_symbol'] ?? '$' ?>';
    return symbol + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function openTab(evt, tabName) {
    document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));
    document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove("active"));
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}
// Ensure the first tab is active on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.tab-link').click();
});


function toggleAllCheckboxes(masterCheckbox) {
    document.querySelectorAll('.deposit-checkbox').forEach(cb => {
        cb.checked = masterCheckbox.checked;
        updateRowStyle(cb);
    });
}

function updateDepositTotal() {
    let total = 0;
    document.querySelectorAll('.deposit-checkbox:checked').forEach(cb => {
        total += parseFloat(cb.closest('tr').querySelector('[data-amount]').dataset.amount);
    });
    document.getElementById('depositTotal').textContent = formatCurrencyJS(total);
}

function updateRowStyle(checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
        row.classList.add('selected');
    } else {
        row.classList.remove('selected');
    }
    updateDepositTotal();
}

function toggleCheckbox(rowElement) {
    const checkbox = rowElement.querySelector('.deposit-checkbox');
    checkbox.checked = !checkbox.checked;
    updateRowStyle(checkbox);
}
</script>
</body>
</html>