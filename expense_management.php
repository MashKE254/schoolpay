<?php
// expense_management.php - Expense Management / Accountant Center
require 'config.php';
require 'functions.php';
include 'header.php';

// Add this debugging function
function debug_log($message) {
    // Uncomment this for debugging
    // echo "<div class='alert alert-info'>Debug: " . htmlspecialchars($message) . "</div>";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_account'])) {
        $account_code = $_POST['account_code'];
        $account_name = $_POST['account_name'];
        $account_type = $_POST['account_type'];
        $starting_balance = floatval($_POST['starting_balance']);
        
        $result = createAccountWithBalance($pdo, $account_code, $account_name, $account_type, $starting_balance);
        if ($result === true) {
            echo '<div class="alert alert-success">Account created successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">' . htmlspecialchars($result) . '</div>';
        }
    } elseif (isset($_POST['edit_account'])) {
        $id = $_POST['account_id'];
        $account_code = $_POST['account_code'];
        $account_name = $_POST['account_name'];
        $account_type = $_POST['account_type'];
        
        if (updateAccount($pdo, $id, $account_code, $account_name, $account_type)) {
            echo '<div class="alert alert-success">Account updated successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Failed to update account.</div>';
        }
    } elseif (isset($_POST['delete_account'])) {
        $id = $_POST['account_id'];
        
        if (deleteAccount($pdo, $id)) {
            echo '<div class="alert alert-success">Account deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Failed to delete account. Make sure the account has no transactions.</div>';
        }
    } elseif (isset($_POST['add_journal_entry'])) {
        $date = $_POST['date'];
        $debit_account = $_POST['debit_account'];
        $credit_account = $_POST['credit_account'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        
        debug_log("Creating journal entry: Debit: $debit_account, Credit: $credit_account, Amount: $amount");
        
        if (createJournalEntry($pdo, $date, $debit_account, $credit_account, $amount, $description)) {
            echo '<div class="alert alert-success">Journal entry added successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Failed to add journal entry.</div>';
        }
    } elseif (isset($_POST['pay_supplier'])) {
        $date = $_POST['date'];
        $supplier_name = $_POST['supplier_name'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $description = $_POST['description'];
        
        // Get the Accounts Payable account ID
        $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '2000'");
        $stmt->execute();
        $ap_account_id = $stmt->fetchColumn();
        
        // Get the Cash/Bank account ID based on payment method
        $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ?");
        $stmt->execute([$payment_method === 'Cash' ? '1000' : '1001']);
        $cash_account_id = $stmt->fetchColumn();
        
        debug_log("Supplier payment: AP account: $ap_account_id, Cash account: $cash_account_id, Amount: $amount");
        
        if ($ap_account_id && $cash_account_id) {
            if (createJournalEntry($pdo, $date, $ap_account_id, $cash_account_id, $amount, "Payment to $supplier_name: $description")) {
                echo '<div class="alert alert-success">Supplier payment recorded successfully!</div>';
                // Force reload journal entries
                $journalEntries = getJournalEntries($pdo, true);
            } else {
                echo '<div class="alert alert-danger">Failed to record supplier payment.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Required accounts not found. Please set up Accounts Payable and Cash/Bank accounts.</div>';
        }
    } elseif (isset($_POST['pay_vendor'])) {
        $date = $_POST['date'];
        $vendor_name = $_POST['vendor_name'];
        $expense_category = $_POST['expense_category'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $description = $_POST['description'];
        
        // Get the expense account ID
        $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE account_name = ?");
        $stmt->execute([$expense_category]);
        $expense_account_id = $stmt->fetchColumn();
        
        // Get the Cash/Bank account ID based on payment method
        $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ?");
        $stmt->execute([$payment_method === 'Cash' ? '1000' : '1001']);
        $cash_account_id = $stmt->fetchColumn();
        
        debug_log("Vendor payment: Expense account: $expense_account_id, Cash account: $cash_account_id, Amount: $amount");
        
        if ($expense_account_id && $cash_account_id) {
            if (createJournalEntry($pdo, $date, $expense_account_id, $cash_account_id, $amount, "Payment to $vendor_name for $expense_category: $description")) {
                echo '<div class="alert alert-success">Vendor payment recorded successfully!</div>';
                // Force reload journal entries
                $journalEntries = getJournalEntries($pdo, true);
            } else {
                echo '<div class="alert alert-danger">Failed to record vendor payment.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Required accounts not found. Please set up Expense and Cash/Bank accounts.</div>';
        }
    } elseif (isset($_POST['add_activity'])) {
        $activity_name = $_POST['activity_name'];
        $account_code = $_POST['account_code'];
        $category = $_POST['category'];
        
        // Check if account code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chart_of_accounts WHERE account_code = ?");
        $stmt->execute([$account_code]);
        if ($stmt->fetchColumn() > 0) {
            echo '<div class="alert alert-danger">Account code already exists. Please use a different code.</div>';
        } else {
            // Create new expense account
            $stmt = $pdo->prepare("
                INSERT INTO chart_of_accounts (account_code, account_name, account_type)
                VALUES (?, ?, 'Expense')
            ");
            if ($stmt->execute([$account_code, $activity_name])) {
                echo '<div class="alert alert-success">New expense category added successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Failed to add new expense category.</div>';
            }
        }
    } elseif (isset($_POST['add_category_type'])) {
        $category_name = $_POST['category_name'];
        $account_code_prefix = $_POST['account_code_prefix'];
        
        // Check if account code prefix already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE account_code_prefix = ?");
        $stmt->execute([$account_code_prefix]);
        if ($stmt->fetchColumn() > 0) {
            echo '<div class="alert alert-danger">Category type already exists. Please use a different code prefix.</div>';
        } else {
            // Create new category type
            $stmt = $pdo->prepare("
                INSERT INTO expense_categories (category_name, account_code_prefix)
                VALUES (?, ?)
            ");
            if ($stmt->execute([$category_name, $account_code_prefix])) {
                echo '<div class="alert alert-success">New category type added successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Failed to add new category type.</div>';
            }
        }
    }
}

// Modify function to allow forced refresh
function getJournalEntries($pdo, $force_refresh = false) {
    static $entries = null;
    
    if ($entries === null || $force_refresh) {
        $stmt = $pdo->prepare("
            SELECT je.id, je.date, je.amount, je.description,
                   deb.account_name as debit_account_name,
                   cred.account_name as credit_account_name
            FROM journal_entries je
            JOIN chart_of_accounts deb ON je.debit_account = deb.id
            JOIN chart_of_accounts cred ON je.credit_account = cred.id
            ORDER BY je.date DESC, je.id DESC
        ");
        $stmt->execute();
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $entries;
}

// Get fresh data on page load
$journalEntries = getJournalEntries($pdo, true);
$accounts = getChartOfAccountsWithBalances($pdo);

// Get all expense accounts
$stmt = $pdo->prepare("
    SELECT account_code, account_name 
    FROM chart_of_accounts 
    WHERE account_type = 'Expense' 
    AND (
        account_code LIKE '51%' OR  -- Activities
        account_code LIKE '52%' OR  -- Books
        account_code LIKE '53%' OR  -- Uniforms
        account_code LIKE '54%' OR  -- Stationery
        account_code LIKE '55%'     -- Other Supplies
    )
    ORDER BY account_name
");
$stmt->execute();
$expense_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all category types
$stmt = $pdo->prepare("
    SELECT category_name, account_code_prefix 
    FROM expense_categories 
    ORDER BY category_name
");
$stmt->execute();
$category_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if required accounts exist
$stmt = $pdo->prepare("SELECT COUNT(*) FROM chart_of_accounts WHERE account_code IN ('1000', '1001', '2000')");
$stmt->execute();
$required_accounts_count = $stmt->fetchColumn();
if ($required_accounts_count < 3) {
    echo '<div class="alert alert-warning">Some required accounts (Cash, Bank, or Accounts Payable) may be missing. Please set them up in the Chart of Accounts.</div>';
}
?>
<h2>Expense Management / Accountant Center</h2>
<div class="tab-container">
  <div class="tabs">
      <button class="tab-link" onclick="openTab(event, 'journalTab')">Journal Entries</button>
      <button class="tab-link" onclick="openTab(event, 'coaTab')">Chart of Accounts</button>
      <button class="tab-link" onclick="openTab(event, 'supplierTab')">Pay Suppliers</button>
      <button class="tab-link" onclick="openTab(event, 'vendorTab')">Pay Vendors</button>
  </div>
    
  <div id="journalTab" class="tab-content">
        <div class="card">
            <h3>Add New Journal Entry</h3>
            <form method="POST" action="">
                <input type="hidden" name="add_journal_entry" value="1">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="debit_account">Debit Account:</label>
                    <select id="debit_account" name="debit_account" required class="form-control">
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>">
                                <?php echo htmlspecialchars($account['account_name'] . ' (' . $account['account_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="credit_account">Credit Account:</label>
                    <select id="credit_account" name="credit_account" required class="form-control">
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>">
                                <?php echo htmlspecialchars($account['account_name'] . ' (' . $account['account_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" required class="form-control" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Journal Entry</button>
            </form>
        </div>
        
      <div class="card">
          <h3>Journal Entries</h3>
            <table class="table">
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Date</th>
                      <th>Debit Account</th>
                      <th>Credit Account</th>
                      <th>Amount</th>
                      <th>Description</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach($journalEntries as $entry): ?>
                  <tr>
                      <td><?php echo htmlspecialchars($entry['id']); ?></td>
                      <td><?php echo htmlspecialchars($entry['date']); ?></td>
                      <td><?php echo htmlspecialchars($entry['debit_account_name']); ?></td>
                      <td><?php echo htmlspecialchars($entry['credit_account_name']); ?></td>
                      <td>$<?php echo number_format($entry['amount'], 2); ?></td>
                      <td><?php echo htmlspecialchars($entry['description'] ?? ''); ?></td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
  </div>
    
  <div id="coaTab" class="tab-content">
        <div class="card">
            <h3>Add New Account</h3>
            <form method="POST" action="">
                <input type="hidden" name="add_account" value="1">
                <div class="form-group">
                    <label for="account_code">Account Code:</label>
                    <input type="text" id="account_code" name="account_code" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="account_name">Account Name:</label>
                    <input type="text" id="account_name" name="account_name" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="account_type">Account Type:</label>
                    <select id="account_type" name="account_type" required class="form-control">
                        <option value="Asset">Asset</option>
                        <option value="Liability">Liability</option>
                        <option value="Equity">Equity</option>
                        <option value="Revenue">Revenue</option>
                        <option value="Expense">Expense</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="starting_balance">Starting Balance:</label>
                    <input type="number" id="starting_balance" name="starting_balance" class="form-control" step="0.01" min="0" value="0">
                    <small class="form-text text-muted">Enter the initial balance for this account. For Assets and Expenses, enter a positive number. For Liabilities, Equity, and Revenue, enter a negative number.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Account</button>
            </form>
        </div>
        
      <div class="card">
          <h3>Chart of Accounts</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Account Type</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($accounts as $account): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                        <td>$<?php echo number_format($account['balance'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editAccount(<?php echo $account['id']; ?>, '<?php echo addslashes($account['account_code']); ?>', '<?php echo addslashes($account['account_name']); ?>', '<?php echo $account['account_type']; ?>')">Edit</button>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="delete_account" value="1">
                                <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this account?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="supplierTab" class="tab-content">
        <div class="card">
            <h3>Record Supplier Payment</h3>
            <form method="POST" action="">
                <input type="hidden" name="pay_supplier" value="1">
                <div class="form-group">
                    <label for="date">Payment Date:</label>
                    <input type="date" id="date" name="date" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="supplier_name">Supplier Name:</label>
                    <input type="text" id="supplier_name" name="supplier_name" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" required class="form-control" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method" required class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Enter payment details or reference number"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Record Payment</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Recent Supplier Payments</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Get supplier payments from journal entries - Use fresh query
                    $stmt = $pdo->prepare("
                        SELECT je.date, je.description, je.amount, 
                               CASE 
                                   WHEN je.credit_account = (SELECT id FROM chart_of_accounts WHERE account_code = '1000') THEN 'Cash'
                                   WHEN je.credit_account = (SELECT id FROM chart_of_accounts WHERE account_code = '1001') THEN 'Bank'
                                   ELSE 'Other'
                               END as payment_method
                        FROM journal_entries je
                        WHERE je.debit_account = (SELECT id FROM chart_of_accounts WHERE account_code = '2000')
                        ORDER BY je.date DESC, je.id DESC
                        LIMIT 50
                    ");
                    $stmt->execute();
                    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach($payments as $payment): 
                        // Extract supplier name from description
                        $description = $payment['description'];
                        $supplier_name = '';
                        if (preg_match('/Payment to (.*?):/', $description, $matches)) {
                            $supplier_name = $matches[1];
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['date']); ?></td>
                        <td><?php echo htmlspecialchars($supplier_name); ?></td>
                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($description); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="vendorTab" class="tab-content">
        <div class="card">
            <h3>Record Vendor Payment</h3>
            <form method="POST" action="">
                <input type="hidden" name="pay_vendor" value="1">
                <div class="form-group">
                    <label for="date">Payment Date:</label>
                    <input type="date" id="date" name="date" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="vendor_name">Vendor Name:</label>
                    <input type="text" id="vendor_name" name="vendor_name" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="expense_category">Expense Category:</label>
                    <select id="expense_category" name="expense_category" required class="form-control">
                        <option value="">Select Category</option>
                        <?php foreach($expense_categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['account_name']); ?>">
                                <?php echo htmlspecialchars($category['account_name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="add_new">+ Add New Category</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" required class="form-control" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method" required class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Enter payment details or reference number"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Record Payment</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Recent Vendor Payments</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Use fresh query
                    $stmt = $pdo->prepare("
                        SELECT je.date, je.description, je.amount, 
                               CASE 
                                   WHEN je.credit_account = (SELECT id FROM chart_of_accounts WHERE account_code = '1000') THEN 'Cash'
                                   WHEN je.credit_account = (SELECT id FROM chart_of_accounts WHERE account_code = '1001') THEN 'Bank'
                                   ELSE 'Other'
                               END as payment_method,
                               ca.account_name as category
                        FROM journal_entries je
                        JOIN chart_of_accounts ca ON je.debit_account = ca.id
                        WHERE ca.account_type = 'Expense'
                        AND (
                            ca.account_code LIKE '51%' OR
                            ca.account_code LIKE '52%' OR
                            ca.account_code LIKE '53%' OR
                            ca.account_code LIKE '54%' OR
                            ca.account_code LIKE '55%'
                        )
                        ORDER BY je.date DESC, je.id DESC
                        LIMIT 50
                    ");
                    $stmt->execute();
                    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach($payments as $payment): 
                        // Extract vendor name from description
                        $description = $payment['description'];
                        $vendor_name = '';
                        if (preg_match('/Payment to (.*?) for/', $description, $matches)) {
                            $vendor_name = $matches[1];
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['date']); ?></td>
                        <td><?php echo htmlspecialchars($vendor_name); ?></td>
                        <td><?php echo htmlspecialchars($payment['category']); ?></td>
                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($description); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
      </div>
  </div>
</div>

<!-- Edit Account Modal -->
<div id="editAccountModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Account</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_account" value="1">
            <input type="hidden" name="account_id" id="edit_account_id">
            <div class="form-group">
                <label for="edit_account_code">Account Code:</label>
                <input type="text" id="edit_account_code" name="account_code" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="edit_account_name">Account Name:</label>
                <input type="text" id="edit_account_name" name="account_name" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="edit_account_type">Account Type:</label>
                <select id="edit_account_type" name="account_type" required class="form-control">
                    <option value="Asset">Asset</option>
                    <option value="Liability">Liability</option>
                    <option value="Equity">Equity</option>
                    <option value="Revenue">Revenue</option>
                    <option value="Expense">Expense</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Account</button>
        </form>
    </div>
</div>

<!-- Add New Category Modal -->
<div id="addCategoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeAddCategoryModal()">&times;</span>
        <h3>Add New Expense Category</h3>
        <form method="POST" action="">
            <input type="hidden" name="add_activity" value="1">
            <div class="form-group">
                <label for="category">Category Type:</label>
                <select id="category" name="category" required class="form-control">
                    <option value="">Select Category Type</option>
                    <?php foreach($category_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['account_code_prefix']); ?>">
                            <?php echo htmlspecialchars($type['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="add_new">+ Add New Category Type</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="activity_name">Category Name:</label>
                <input type="text" id="activity_name" name="activity_name" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="account_code">Account Code:</label>
                <input type="text" id="account_code" name="account_code" required class="form-control" pattern="5[1-9][0-9]{2}" title="Account code must start with 51-59 followed by two digits">
                <small class="form-text text-muted">Must start with the category prefix followed by two digits (e.g., 5105, 5201, etc.)</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Category</button>
        </form>
    </div>
</div>

<!-- Add New Category Type Modal -->
<div id="addCategoryTypeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeAddCategoryTypeModal()">&times;</span>
        <h3>Add New Category Type</h3>
        <form method="POST" action="">
            <input type="hidden" name="add_category_type" value="1">
            <div class="form-group">
                <label for="category_name">Category Type Name:</label>
                <input type="text" id="category_name" name="category_name" required class="form-control" placeholder="e.g., Transportation, Equipment">
            </div>
            
            <div class="form-group">
                <label for="account_code_prefix">Account Code Prefix:</label>
                <input type="text" id="account_code_prefix" name="account_code_prefix" required class="form-control" pattern="5[1-9]" title="Prefix must be between 51 and 59">
                <small class="form-text text-muted">Must be a number between 51 and 59 (e.g., 56 for Transportation)</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Category Type</button>
        </form>
    </div>
</div>

<style>
.tab-container {
    margin: 20px 0;
}
.tabs {
    margin-bottom: 20px;
}
.tab-link {
    padding: 10px 20px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    cursor: pointer;
}
.tab-link.active {
    background: #007bff;
    color: white;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.form-group {
    margin-bottom: 15px;
}
.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: left;
}
.table th {
    background: #f8f9fa;
}
.alert {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.alert-success {
    background: #d4edda;
    color: #155724;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
}
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    border-radius: 5px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.supplier-payment-form {
    max-width: 600px;
    margin: 0 auto;
}

.payment-history {
    margin-top: 30px;
}

.payment-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.payment-summary h4 {
    margin-top: 0;
    color: #333;
}

.payment-summary .amount {
    font-size: 24px;
    font-weight: bold;
    color: #28a745;
}

.vendor-payment-form {
    max-width: 600px;
    margin: 0 auto;
}

.vendor-history {
    margin-top: 30px;
}

.vendor-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.vendor-summary h4 {
    margin-top: 0;
    color: #333;
}

.vendor-summary .amount {
    font-size: 24px;
    font-weight: bold;
    color: #28a745;
}

.activity-type-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}

.category-type-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}
</style>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].className = tabcontent[i].className.replace(" active", "");
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).className += " active";
    evt.currentTarget.className += " active";
}

// Open the first tab by default
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.tab-link').click();
});

function editAccount(id) {
    // Get account details via AJAX or from a data attribute
    // For simplicity, we'll just show the modal
    document.getElementById('edit_account_id').value = id;
    document.getElementById('editAccountModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editAccountModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('editAccountModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Add supplier payment form validation
document.addEventListener('DOMContentLoaded', function() {
    const supplierForm = document.querySelector('form[name="pay_supplier"]');
    if (supplierForm) {
        supplierForm.addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            if (isNaN(amount) || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount greater than 0');
            }
        });
    }
});

// Add vendor payment form validation
document.addEventListener('DOMContentLoaded', function() {
    const vendorForm = document.querySelector('form[name="pay_vendor"]');
    if (vendorForm) {
        vendorForm.addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            if (isNaN(amount) || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount greater than 0');
            }
        });
    }
});

// Add new category modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('expense_category');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'add_new') {
                document.getElementById('addCategoryModal').style.display = 'block';
                this.value = ''; // Reset the select
            }
        });
    }
    
    // Update account code based on category selection
    const categoryType = document.getElementById('category');
    const accountCode = document.getElementById('account_code');
    if (categoryType && accountCode) {
        categoryType.addEventListener('change', function() {
            if (this.value && this.value !== 'add_new') {
                accountCode.value = this.value + '00';
            }
        });
    }
});

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addCategoryModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Validate account code format
document.getElementById('account_code').addEventListener('input', function(e) {
    const pattern = /^5[1-9]\d{2}$/;
    if (!pattern.test(this.value)) {
        this.setCustomValidity('Account code must start with 51-59 followed by two digits');
    } else {
        this.setCustomValidity('');
    }
});

// Add new category type modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'add_new') {
                document.getElementById('addCategoryTypeModal').style.display = 'block';
                this.value = ''; // Reset the select
            }
        });
    }
    
    // Update account code based on category selection
    const categoryType = document.getElementById('category');
    const accountCode = document.getElementById('account_code');
    if (categoryType && accountCode) {
        categoryType.addEventListener('change', function() {
            if (this.value && this.value !== 'add_new') {
                accountCode.value = this.value + '00';
            }
        });
    }
    
    // Validate account code prefix
    const accountCodePrefix = document.getElementById('account_code_prefix');
    if (accountCodePrefix) {
        accountCodePrefix.addEventListener('input', function(e) {
            const pattern = /^5[1-9]$/;
            if (!pattern.test(this.value)) {
                this.setCustomValidity('Prefix must be a number between 51 and 59');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});

function closeAddCategoryTypeModal() {
    document.getElementById('addCategoryTypeModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addCategoryTypeModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Validate account code format
document.getElementById('account_code').addEventListener('input', function(e) {
    const pattern = /^5[1-9]\d{2}$/;
    if (!pattern.test(this.value)) {
        this.setCustomValidity('Account code must start with 51-59 followed by two digits');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'footer.php'; ?>
