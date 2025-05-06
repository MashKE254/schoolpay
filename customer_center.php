<?php
// customer_center.php - Customer Center
require 'config.php';
require 'functions.php';
include 'header.php';

// Get all items
$items = getItemsWithSubItems($pdo);

// Handle item deletion
if (isset($_POST['delete_item'])) {
try {
    deleteItem($pdo, $_POST['item_id']);
    $success = "Item deleted successfully.";
    // Refresh items list
    $items = getItemsWithSubItems($pdo);
} catch (PDOException $e) {
    $error = "Error deleting item: " . $e->getMessage();
}
}

// Handle item update
if (isset($_POST['update_item'])) {
try {
    updateItem(
        $pdo,
        $_POST['item_id'],
        $_POST['name'],
        $_POST['price'],
        $_POST['description'],
        $_POST['parent_id'] ?: null,
        $_POST['item_type']
    );
    $success = "Item updated successfully.";
    // Refresh items list
    $items = getItemsWithSubItems($pdo);
} catch (PDOException $e) {
    $error = "Error updating item: " . $e->getMessage();
}
}

// Process form submission for adding a new student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addStudent'])) {
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);

$stmt = $pdo->prepare("INSERT INTO students (name, email, phone, address) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$name, $email, $phone, $address])) {
    echo "<script>
        showAlert('Student added successfully!');
        
        // Refresh dashboard if it's open in another tab
        if (window.opener && !window.opener.closed) {
            try {
                window.opener.refreshDashboardData();
            } catch(e) {
                console.log('Could not refresh dashboard in parent window');
            }
        }
        
        // Or store flag in localStorage
        localStorage.setItem('dashboard_needs_refresh', 'true');
    </script>";
} else {
    echo "<script>showAlert('Error adding student.');</script>";
}
}

// Process delete student request
if (isset($_GET['delete_student'])) {
$student_id = intval($_GET['delete_student']);
$stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
if ($stmt->execute([$student_id])) {
    echo "<script>showAlert('Student deleted successfully!');</script>";
} else {
    echo "<script>showAlert('Error deleting student.');</script>";
}
}

// Process edit student request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editStudent'])) {
$student_id = intval($_POST['student_id']);
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);

$stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
if ($stmt->execute([$name, $email, $phone, $address, $student_id])) {
    echo "<script>showAlert('Student updated successfully!');</script>";
} else {
    echo "<script>showAlert('Error updating student.');</script>";
}
}

// Process payment submission - Fixed code with student_id inclusion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $student_id = intval($_POST['student_id']);
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'];
        $memo = trim($_POST['memo']);
        $invoice_ids = $_POST['invoice_ids'] ?? [];
        $payment_amounts = $_POST['payment_amounts'] ?? [];

        // Begin transaction
        $pdo->beginTransaction();
        
        for ($i = 0; $i < count($invoice_ids); $i++) {
            $invoice_id = intval($invoice_ids[$i]);
            $amount = floatval($payment_amounts[$i]);
            
            // Only process payments with amount > 0
            if ($amount > 0) {
                // Insert payment record - Added student_id to the insert statement
                $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, student_id, payment_date, amount, payment_method, memo) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$invoice_id, $student_id, $payment_date, $amount, $payment_method, $memo]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo "<script>showAlert('Payment recorded successfully!');</script>";

        // Refresh invoices list
        $invoices = getInvoices($pdo);
        
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        echo "<script>showAlert('Error recording payment: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Process statement generation (new functionality)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generateStatements'])) {
$statementType = $_POST['statement_type'];
$statementDate = $_POST['statement_date'];
$studentSelection = $_POST['student_selection'];
$dateFrom = $_POST['date_from'];
$dateTo = $_POST['date_to'];
$showTransactions = $_POST['show_transactions'];

// Here you would implement the logic to generate statements
// This is a placeholder for the actual implementation
$success = "Statements generated successfully.";
}

// Get student detail if viewing single student
$studentDetail = null;
$studentTransactions = [];
if (isset($_GET['view_student'])) {
$studentId = intval($_GET['view_student']);

// Replace the current transaction history building code in customer_center.php
// Find this section around line 175-238

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$studentDetail = $stmt->fetch(PDO::FETCH_ASSOC);

if ($studentDetail) {
    // Get student invoices with their original creation date
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.id as invoice_number,
            i.invoice_date,
            i.due_date,
            i.total_amount,
            COALESCE(SUM(p.amount), 0) as paid_amount,
            i.total_amount - COALESCE(SUM(p.amount), 0) as balance,
            'Invoice' as transaction_type
        FROM 
            invoices i
        LEFT JOIN 
            payments p ON i.id = p.invoice_id
        WHERE 
            i.student_id = ?
        GROUP BY 
            i.id
    ");
    $stmt->execute([$studentId]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student payments
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.payment_date as date,
            p.amount,
            p.payment_method,
            p.memo,
            i.id as invoice_number,
            'Payment' as transaction_type
        FROM 
            payments p
        JOIN 
            invoices i ON p.invoice_id = i.id
        WHERE 
            i.student_id = ?
        ORDER BY 
            p.payment_date ASC
    ");
    $stmt->execute([$studentId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a combined timeline of all transactions
    $allTransactions = [];
    
    // First, add all invoices
    foreach ($invoices as $invoice) {
        $allTransactions[] = [
            'id' => $invoice['id'],
            'date' => $invoice['invoice_date'],
            'type' => 'invoice',
            'description' => 'Invoice #' . $invoice['invoice_number'],
            'amount' => $invoice['total_amount'],
            'invoice_id' => $invoice['id'],
            'due_date' => $invoice['due_date'],
            // These will be calculated later
            'running_balance' => 0,
            'invoice_balance' => $invoice['total_amount']
        ];
    }
    
    // Then add all payments
    foreach ($payments as $payment) {
        $allTransactions[] = [
            'id' => $payment['id'],
            'date' => $payment['date'],
            'type' => 'payment',
            'description' => 'Payment for Invoice #' . $payment['invoice_number'] . ' (' . $payment['payment_method'] . ')',
            'amount' => $payment['amount'],
            'invoice_id' => $payment['invoice_number'],
            'memo' => $payment['memo'],
            // These will be calculated later
            'running_balance' => 0,
            'invoice_balance' => 0
        ];
    }
    
    // Sort all transactions by date (oldest first) to calculate running balances
    usort($allTransactions, function($a, $b) {
        $dateCompare = strtotime($a['date']) - strtotime($b['date']);
        // If same date, put invoices before payments
        if ($dateCompare === 0) {
            return ($a['type'] === 'invoice') ? -1 : 1;
        }
        return $dateCompare;
    });
    
    // Calculate running balances for each invoice
    $invoiceBalances = [];
    
    // Initialize invoice balances
    foreach ($invoices as $invoice) {
        $invoiceBalances[$invoice['id']] = $invoice['total_amount'];
    }
    
    // Calculate running balances for each transaction
    foreach ($allTransactions as &$transaction) {
        $invoiceId = $transaction['invoice_id'];
        
        if ($transaction['type'] === 'payment') {
            // Reduce the balance for this invoice
            $invoiceBalances[$invoiceId] -= $transaction['amount'];
        }
        
        // Store the current balance for this invoice
        $transaction['invoice_balance'] = $invoiceBalances[$invoiceId];
    }
    
    // Sort transactions by date (newest first) for display
    usort($allTransactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    $studentTransactions = $allTransactions;
    
    // Calculate student balance
    $totalInvoiced = 0;
    $totalPaid = 0;
    
    foreach ($studentTransactions as $trans) {
        if ($trans['type'] === 'invoice') {
            $totalInvoiced += $trans['amount'];
        } elseif ($trans['type'] === 'payment') {
            $totalPaid += $trans['amount'];
        }
    }
    
    $studentBalance = $totalInvoiced - $totalPaid;
}
}

// Retrieve existing invoices and students
$invoices = getInvoices($pdo);
$students = getStudents($pdo);
?>
<h2>Customer Center</h2>
<div class="tab-container">
<div class="tabs">
    <button class="tab-link <?php echo !isset($_GET['view_student']) ? 'active' : ''; ?>" onclick="openTab(event, 'students')">Students</button>
    <button class="tab-link" onclick="openTab(event, 'invoices')">Invoices</button>
    <button class="tab-link" onclick="openTab(event, 'items')">Items</button>
    <button class="tab-link" onclick="openTab(event, 'receive_payment')">Receive Payment</button>
    <button class="tab-link" onclick="openTab(event, 'statements')">Statements</button>
    <?php if (isset($_GET['view_student']) && $studentDetail): ?>
    <button class="tab-link active" onclick="openTab(event, 'student_detail')">Student Detail</button>
    <?php endif; ?>
</div>

<!-- Students Tab -->
<div id="students" class="tab-content" <?php echo !isset($_GET['view_student']) ? 'style="display: block;"' : ''; ?>>
    <div class="card">
        <h3>Students</h3>
        <div class="table-actions">
            <button class="btn-add" onclick="openAddModal()">Add New Student</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                    <tr>
                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                    <td>
                        <div class="student-name-container">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                            </div>
                            <a href="?view_student=<?php echo $student['id']; ?>" class="student-name-link">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </a>

                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                    <td><?php echo htmlspecialchars($student['address']); ?></td>
                    <td>
                        <button class="btn-edit" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>
                        <button class="btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Student Detail Tab -->
<?php if (isset($_GET['view_student']) && $studentDetail): ?>
<div id="student_detail" class="tab-content" style="display: block;">
    <div class="student-detail-header">
        <div class="back-button">
            <a href="customer_center.php" class="btn-secondary">← Back to Students</a>
        </div>
        <div class="student-info">
            <h3><?php echo htmlspecialchars($studentDetail['name']); ?></h3>
            <div class="info-section">
                <div class="info-item">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($studentDetail['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($studentDetail['phone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Address:</span>
                    <span class="value"><?php echo htmlspecialchars($studentDetail['address']); ?></span>
                </div>
            </div>
        </div>
        <div class="student-actions">
            <button class="btn-primary" onclick="window.location.href='create_invoice.php?student_id=<?php echo $studentDetail['id']; ?>'">Create Invoice</button>
            <button class="btn-primary" onclick="openReceivePaymentTab(<?php echo $studentDetail['id']; ?>)">Receive Payment</button>
        </div>
    </div>
    
    <div class="student-balance-summary">
        <div class="balance-card">
            <h4>Current Balance</h4>
            <span class="balance-amount <?php echo $studentBalance > 0 ? 'balance-due' : 'balance-zero'; ?>">
                $<?php echo number_format(abs($studentBalance), 2); ?>
            </span>
            <span class="balance-label">
                <?php echo $studentBalance > 0 ? 'BALANCE DUE' : ($studentBalance < 0 ? 'CREDIT BALANCE' : 'PAID IN FULL'); ?>
            </span>
        </div>
        <div class="balance-card">
            <h4>Total Invoiced</h4>
            <span class="balance-amount">$<?php echo number_format($totalInvoiced, 2); ?></span>
        </div>
        <div class="balance-card">
            <h4>Total Paid</h4>
            <span class="balance-amount">$<?php echo number_format($totalPaid, 2); ?></span>
        </div>
    </div>
    
    <div class="transaction-history">
        <h3>Transaction History</h3>
        <div class="transaction-filters">
            <div class="date-range">
                <label for="from_date">From:</label>
                <input type="date" id="from_date" name="from_date">
                <label for="to_date">To:</label>
                <input type="date" id="to_date" name="to_date">
                <button class="btn-filter" onclick="filterTransactions()">Filter</button>
                <button class="btn-reset" onclick="resetFilter()">Reset</button>
            </div>
            <div class="transaction-type-filter">
                <label><input type="checkbox" value="invoice" checked> Invoices</label>
                <label><input type="checkbox" value="payment" checked> Payments</label>
            </div>
        </div>
        
        <table class="transaction-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Transaction</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Balance</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($studentTransactions as $trans): ?>
        <tr class="transaction-row <?php echo $trans['type']; ?>">
            <td><?php echo date('M d, Y', strtotime($trans['date'])); ?></td>
            <td>
                <?php if($trans['type'] === 'invoice'): ?>
                    <span class="trans-tag invoice">Invoice</span>
                <?php else: ?>
                    <span class="trans-tag payment">Payment</span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($trans['description']); ?></td>
            <td class="amount <?php echo $trans['type'] === 'payment' ? 'payment-amount' : 'invoice-amount'; ?>">
                $<?php echo number_format(abs($trans['amount']), 2); ?>
            </td>
            <td>
                <?php if(isset($trans['invoice_balance'])): ?>
                    $<?php echo number_format($trans['invoice_balance'], 2); ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td>
                <?php if($trans['type'] === 'invoice'): ?>
                    <a href="view_invoice.php?id=<?php echo $trans['id']; ?>" class="btn-small">View</a>
                <?php elseif($trans['type'] === 'payment'): ?>
                    <button class="btn-small" onclick="viewPaymentDetail(<?php echo $trans['id']; ?>)">Detail</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    </div>
</div>
<?php endif; ?>

<!-- Invoices Tab -->
<div id="invoices" class="tab-content">
    <div class="card">
        <h3>Invoices</h3>
        <div class="table-actions">
            <a href="create_invoice.php" class="btn-primary">Create New Invoice</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($invoices as $inv): ?>
                <tr>
                    <td><?php echo htmlspecialchars($inv['id']); ?></td>
                    <td><?php echo htmlspecialchars($inv['student_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($inv['invoice_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                    <td>$<?php echo number_format($inv['total_amount'] ?? 0, 2); ?></td>
                    <td>
                        <?php
                        $status = 'Draft';
                        if (isset($inv['paid_amount']) && $inv['paid_amount'] > 0) {
                            if ($inv['paid_amount'] >= $inv['total_amount']) {
                                $status = 'Paid';
                            } else {
                                $status = 'Partially Paid';
                            }
                        }
                        echo $status;
                        ?>
                    </td>
                    <td>
                        <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn-small">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Items Tab -->
<div id="items" class="tab-content">
    <div class="items-list">
        <?php foreach ($items as $item): ?>
            <div class="item-card">
                <div class="item-header">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <div class="item-actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                            Edit
                        </button>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="delete_item" class="btn-delete">Delete</button>
                        </form>
                    </div>
                </div>
                <div class="item-details">
                    <p><strong>Price:</strong> $<?php echo number_format($item['price'], 2); ?></p>
                    <?php if (!empty($item['description'])): ?>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['sub_items'])): ?>
                    <div class="sub-items">
                        <h4>Sub-items:</h4>
                        <?php foreach ($item['sub_items'] as $sub_item): ?>
                            <div class="sub-item">
                                <div class="sub-item-header">
                                    <h5><?php echo htmlspecialchars($sub_item['name']); ?></h5>
                                    <div class="sub-item-actions">
                                        <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sub_item)); ?>)">
                                            Edit
                                        </button>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="item_id" value="<?php echo $sub_item['id']; ?>">
                                            <button type="submit" name="delete_item" class="btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="sub-item-details">
                                    <p><strong>Price:</strong> $<?php echo number_format($sub_item['price'], 2); ?></p>
                                    <?php if (!empty($sub_item['description'])): ?>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($sub_item['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Receive Payment Tab -->
<div id="receive_payment" class="tab-content">
    <div class="card">
        <h3>Receive Payment</h3>
        <form id="paymentForm" method="post" onsubmit="return handlePaymentSubmit(event)">
            <input type="hidden" name="process_payment" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student</label>
                    <select name="student_id" id="student_id" required onchange="loadUnpaidInvoices()">
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo (isset($_GET['view_student']) && $_GET['view_student'] == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Check">Check</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="memo">Memo</label>
                <textarea name="memo" id="memo" rows="2"></textarea>
            </div>
            
            <div class="unpaid-invoices">
                <h4>Unpaid Invoices</h4>
                <table id="unpaidInvoicesTable">
            <thead>
                <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Payment Amount</th>
                </tr>
            </thead>
            <tbody>
                        <!-- Invoices will be loaded here -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right"><strong>Total Payment:</strong></td>
                            <td><strong id="totalPayment">$0.00</strong></td>
                </tr>
                    </tfoot>
        </table>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Statements Tab -->
<div id="statements" class="tab-content">
    <div class="card">
        <h3>Create Statements</h3>
        <form id="statementForm" method="post">
            <input type="hidden" name="generateStatements" value="1">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="statement_type">Statement Type</label>
                    <select name="statement_type" id="statement_type" required>
                        <option value="balance_forward">Balance Forward</option>
                        <option value="open_item">Open Item</option>
                        <option value="transaction_detail">Transaction Detail</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="statement_date">Statement Date</label>
                    <input type="date" name="statement_date" id="statement_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="student_selection">Student Selection</label>
                    <select name="student_selection" id="student_selection" required onchange="toggleStudentSelection()">
                        <option value="all">All Students</option>
                        <option value="selected">Selected Students</option>
                        <option value="class">By Class</option>
                    </select>
                </div>
                
                <div class="form-group" id="class_selection" style="display: none;">
                    <label for="class_id">Class</label>
                    <select name="class_id" id="class_id">
                        <option value="">Select Class</option>
                        <!-- You would populate this from your classes table -->
                        <option value="1">Grade 10-A</option>
                        <option value="2">Grade 10-B</option>
                        <option value="3">Grade 11-A</option>
                        <option value="4">Grade 11-B</option>
                    </select>
                </div>
                
                <div class="form-group" id="student_multi_selection" style="display: none;">
                    <label for="selected_students">Select Students</label>
                    <select name="selected_students[]" id="selected_students" multiple style="height: 100px; width: 100%;">
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_from">Date Range</label>
                    <div style="display: flex;">
                        <input type="date" name="date_from" id="date_from" style="margin-right: 10px;">
                        <input type="date" name="date_to" id="date_to">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="show_transactions">Show</label>
                    <select name="show_transactions" id="show_transactions">
                        <option value="all">All Transactions</option>
                        <option value="open">Open Balances Only</option>
                        <option value="closed">Closed Balances Only</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sort_by">Sort By</label>
                    <select name="sort_by" id="sort_by">
                        <option value="name">Student Name</option>
                        <option value="id">Student ID</option>
                        <option value="class">Class</option>
                        <option value="balance">Balance Amount</option>
                    </select>
                </div>
            </div>
            
            <div class="checkbox-group">
                <h4>Statement Options</h4>
                <div class="checkbox-item">
                    <input type="checkbox" id="include_zero" name="include_zero" checked>
                    <label for="include_zero">Include students with zero balance</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="include_overdue" name="include_overdue">
                    <label for="include_overdue">Include overdue amounts column</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="include_message" name="include_message" checked>
                    <label for="include_message">Include message on statement</label>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="statement_message">Message to Display on Statement</label>
                <textarea name="statement_message" id="statement_message" rows="3">Thank you for your prompt payment. Please contact our finance office if you have any questions about your account.</textarea>
            </div>
            
            <h4>Statement Template</h4>
            <div class="template-options">
                <div class="template-item" onclick="selectTemplate('standard')">
                    <input type="radio" name="template" value="standard" id="template_standard" checked>
                    <label for="template_standard">
                        <img src="assets/img/template_standard.png" alt="Standard Template" onerror="this.src='/api/placeholder/150/80';">
                        <div>Standard</div>
                    </label>
                </div>
                <div class="template-item" onclick="selectTemplate('professional')">
                    <input type="radio" name="template" value="professional" id="template_professional">
                    <label for="template_professional">
                        <img src="assets/img/template_professional.png" alt="Professional Template" onerror="this.src='/api/placeholder/150/80';">
                        <div>Professional</div>
                    </label>
                </div>
                <div class="template-item" onclick="selectTemplate('compact')">
                    <input type="radio" name="template" value="compact" id="template_compact">
                    <label for="template_compact">
                        <img src="assets/img/template_compact.png" alt="Compact Template" onerror="this.src='/api/placeholder/150/80';">
                        <div>Compact</div>
                    </label>
                </div>
            </div>
            
            <div class="preview-area" id="statement_preview">
                <h3 style="text-align: center;">Statement Preview</h3>
                <p style="text-align: center; color: #666;">(Select students and generate statements to see preview)</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="resetForm()">Reset</button>
                <button type="button" class="btn-secondary" onclick="previewStatement()">Preview</button>
                <button type="submit" class="btn-primary">Generate Statements</button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- Add New Student Modal -->
<div id="addStudentModal" class="modal" style="display: none;">
<div class="modal-content">
    <span class="close" onclick="closeAddModal()">&times;</span>
    <h3>Add New Student</h3>
    <form id="addStudentForm" method="post">
        <input type="hidden" name="addStudent" value="1">
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone:</label>
            <input type="text" name="phone" id="phone" required>
        </div>
        
        <div class="form-group">
            <label for="address">Address:</label>
            <textarea name="address" id="address" rows="3" required></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Add Student</button>
            <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal" style="display: none;">
<div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h3>Edit Student</h3>
    <form id="editStudentForm" method="post">
        <input type="hidden" name="student_id" id="edit_student_id">
        <input type="hidden" name="editStudent" value="1">
        
        <div class="form-group">
            <label for="edit_name">Name:</label>
            <input type="text" name="name" id="edit_name" required>
        </div>
        
        <div class="form-group">
            <label for="edit_email">Email:</label>
            <input type="email" name="email" id="edit_email" required>
        </div>
        
        <div class="form-group">
            <label for="edit_phone">Phone:</label>
            <input type="text" name="phone" id="edit_phone" required>
        </div>
        
        <div class="form-group">
            <label for="edit_address">Address:</label>
            <textarea name="address" id="edit_address" rows="3" required></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Update Student</button>
            <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal" style="display: none;">
<div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h3>Edit Item</h3>
    <form id="editItemForm" method="post">
        <input type="hidden" name="item_id" id="edit_item_id">
        <input type="hidden" name="item_type" id="edit_item_type">
        
        <div class="form-group">
            <label for="edit_name">Item Name</label>
            <input type="text" name="name" id="edit_name" required>
        </div>
        
        <div class="form-group">
            <label for="edit_price">Price</label>
            <input type="number" name="price" id="edit_price" step="0.01" required>
        </div>
        
        <div class="form-group">
            <label for="edit_description">Description</label>
            <textarea name="description" id="edit_description" rows="3"></textarea>
        </div>
        
        <div class="form-group" id="edit_parent_item_group" style="display: none;">
            <label for="edit_parent_id">Parent Item</label>
            <select name="parent_id" id="edit_parent_id">
                <option value="">Select Parent Item</option>
                <?php foreach ($items as $parent): ?>
                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="update_item" class="btn-primary">Update Item</button>
            <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
        </div>
    </form>
</div>
</div>

<style>
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
width: 80%;
max-width: 500px;
border-radius: 8px;
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

.table-actions {
margin-bottom: 15px;
}

.btn-primary {
background: #2196F3;
color: white;
border: none;
padding: 8px 16px;
border-radius: 4px;
text-decoration: none;
display: inline-block;
cursor: pointer;
}

.btn-small {
background: #f5f5f5;
color: #333;
border: 1px solid #ddd;
padding: 4px 8px;
border-radius: 4px;
text-decoration: none;
font-size: 12px;
}

.status-draft { color: #666; }
.status-sent { color: #2196F3; }
.status-paid { color: #4CAF50; }
.status-overdue { color: #f44336; }
.status-cancelled { color: #666; }

.items-list {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
gap: 20px;
margin-top: 20px;
}

.item-card {
background: #fff;
border-radius: 8px;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
padding: 20px;
}

.item-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 15px;
}

.item-actions {
display: flex;
gap: 10px;
}

.sub-items {
margin-top: 15px;
padding-top: 15px;
border-top: 1px solid #eee;
}

.sub-item {
background: #f9f9f9;
padding: 10px;
border-radius: 4px;
margin-top: 10px;
}

.sub-item-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 5px;
}

.btn-edit {
background: #2196F3;
color: white;
border: none;
padding: 5px 10px;
border-radius: 4px;
cursor: pointer;
}

.btn-delete {
background: #f44336;
color: white;
border: none;
padding: 5px 10px;
border-radius: 4px;
cursor: pointer;
}

.form-group {
margin-bottom: 15px;
}

.form-group label {
display: block;
margin-bottom: 5px;
font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
width: 100%;
padding: 8px;
border: 1px solid #ddd;
border-radius: 4px;
box-sizing: border-box;
}

.form-row {
display: flex;
flex-wrap: wrap;
gap: 15px;
margin-bottom: 15px;
}

.form-row .form-group {
flex: 1;
min-width: 200px;
}

.form-actions {
margin-top: 20px;
display: flex;
justify-content: flex-end;
gap: 10px;
}

.unpaid-invoices {
margin-top: 20px;
}

.text-right {
text-align: right;
}

.btn-add {
background: #4CAF50;
color: white;
border: none;
padding: 8px 16px;
border-radius: 4px;
cursor: pointer;
}

.btn-secondary {
background: #f5f5f5;
color: #333;
border: 1px solid #ddd;
padding: 8px 16px;
border-radius: 4px;
cursor: pointer;
}

.tab-container {
width: 100%;
margin-top: 20px;
}

.tabs {
overflow: hidden;
border: 1px solid #ccc;
background-color: #f1f1f1;
border-top-left-radius: 4px;
border-top-right-radius: 4px;
}

.tab-link {
background-color: inherit;
float: left;
border: none;
outline: none;
cursor: pointer;
padding: 14px 16px;
transition: 0.3s;
font-size: 17px;
}

.tab-link:hover {
background-color: #ddd;
}

.tab-link.active {
background-color: #fff;
border-bottom: 2px solid #2196F3;
}

.tab-content {
display: none;
padding: 20px;
border: 1px solid #ccc;
border-top: none;
border-bottom-left-radius: 4px;
border-bottom-right-radius: 4px;
}

.template-options {
display: flex;
gap: 20px;
margin: 15px 0;
}

.template-item {
text-align: center;
cursor: pointer;
}

.template-item input[type="radio"] {
display: none;
}

.template-item label {
display: flex;
flex-direction: column;
align-items: center;
cursor: pointer;
}

.template-item img {
border: 2px solid #ddd;
border-radius: 4px;
padding: 5px;
width: 150px;
height: 80px;
object-fit: cover;
transition: all 0.3s;
}

.template-item input[type="radio"]:checked + label img {
border-color: #2196F3;
box-shadow: 0 0 5px rgba(33, 150, 243, 0.5);
}

.preview-area {
margin-top: 30px;
border: 1px solid #ddd;
padding: 20px;
border-radius: 4px;
background-color: #f9f9f9;
min-height: 200px;
}

.checkbox-group {
margin: 15px 0;
}

.checkbox-item {
margin-bottom: 8px;
}

/* Make sure tables look nice */
table {
width: 100%;
border-collapse: collapse;
margin-bottom: 20px;
}

table th, table td {
padding: 12px;
text-align: left;
border-bottom: 1px solid #ddd;
}

table th {
background-color: #f2f2f2;
font-weight: bold;
}

table tr:hover {
background-color: #f5f5f5;
}

.student-name-link {
    color: #2196F3;
    text-decoration: none;
    font-weight: 600;
    position: relative;
    padding: 2px 4px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.student-name-link:hover {
    background-color: #e3f2fd;
    color: #0d47a1;
}

.student-name-link:after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 0;
    background-color: #2196F3;
    transition: width 0.3s ease;
}

.student-name-link:hover:after {
    width: 100%;
}

@keyframes rippleEffect {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(0);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(2);
    }
}

.student-link-ripple {
    animation: rippleEffect 0.6s ease-out;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
.form-row {
    flex-direction: column;
}

.tab-link {
    width: 100%;
    text-align: center;
}

.item-header {
    flex-direction: column;
    align-items: flex-start;
}

.item-actions {
    margin-top: 10px;
}

.template-options {
    flex-direction: column;
}


}
</style>

<script>
function openTab(evt, tabName) {
var i, tabContent, tabLinks;
tabContent = document.getElementsByClassName("tab-content");
for (i = 0; i < tabContent.length; i++) {
    tabContent[i].style.display = "none";
}
tabLinks = document.getElementsByClassName("tab-link");
for (i = 0; i < tabLinks.length; i++) {
    tabLinks[i].className = tabLinks[i].className.replace(" active", "");
}
document.getElementById(tabName).style.display = "block";
evt.currentTarget.className += " active";
}

function openAddModal() {
document.getElementById('addStudentModal').style.display = 'block';
}

function createItem() {
    // Get form data
    const formData = new FormData(document.getElementById('addItemForm'));
    
    // Send AJAX request
    fetch('create_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        // Try to parse as JSON but have a fallback for text
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                // If it's not valid JSON, handle the text response
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid server response: ' + text.substring(0, 100) + '...');
            }
        });
    })
    .then(data => {
        if (data.success) {
            showAlert('Item created successfully!');
            closeAddItemModal();
            // Refresh the items list
            window.location.reload();
        } else {
            showAlert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error creating item: ' + error.message);
    });
    
    return false; // Prevent form submission
}

function closeAddModal() {
document.getElementById('addStudentModal').style.display = 'none';
}

function openEditModal(item) {
// Fill the form with item data
document.getElementById('edit_item_id').value = item.id;
document.getElementById('edit_name').value = item.name;
document.getElementById('edit_price').value = item.price;
document.getElementById('edit_description').value = item.description || '';
document.getElementById('edit_item_type').value = item.item_type || 'item';

// Show/hide parent item selector based on item type
if (item.parent_id || item.item_type === 'sub_item') {
    document.getElementById('edit_parent_item_group').style.display = 'block';
    document.getElementById('edit_parent_id').value = item.parent_id || '';
} else {
    document.getElementById('edit_parent_item_group').style.display = 'none';
}

document.getElementById('editItemModal').style.display = 'block';
}

function closeEditModal() {
document.getElementById('editItemModal').style.display = 'none';
}

function editStudent(student) {
document.getElementById('edit_student_id').value = student.id;
document.getElementById('edit_name').value = student.name;
document.getElementById('edit_email').value = student.email;
document.getElementById('edit_phone').value = student.phone;
document.getElementById('edit_address').value = student.address;

document.getElementById('editStudentModal').style.display = 'block';
}

function closeEditModal() {
document.getElementById('editStudentModal').style.display = 'none';
}

function deleteStudent(studentId) {
if (confirm('Are you sure you want to delete this student?')) {
    window.location.href = 'customer_center.php?delete_student=' + studentId;
}
}

function showAlert(message) {
alert(message);
}

function loadUnpaidInvoices() {
const studentId = document.getElementById('student_id').value;
if (!studentId) {
    document.getElementById('unpaidInvoicesTable').querySelector('tbody').innerHTML = '';
    document.getElementById('totalPayment').textContent = '$0.00';
    return;
}

// AJAX request to get unpaid invoices
const xhr = new XMLHttpRequest();
xhr.open('GET', 'get_unpaid_invoices.php?student_id=' + studentId, true);
xhr.onload = function() {
    if (this.status === 200) {
        const invoices = JSON.parse(this.responseText);
        let html = '';
        
        invoices.forEach(function(invoice) {
            // Ensure proper number conversion and handle null/undefined values
            const totalAmount = parseFloat(invoice.total_amount) || 0;
            const paidAmount = parseFloat(invoice.paid_amount) || 0;
            const balance = totalAmount - paidAmount;
            
            html += `
                <tr>
                    <td>${invoice.id}</td>
                    <td>${formatDate(invoice.invoice_date)}</td>
                    <td>${formatDate(invoice.due_date)}</td>
                    <td>$${totalAmount.toFixed(2)}</td>
                    <td>$${paidAmount.toFixed(2)}</td>
                    <td>$${balance.toFixed(2)}</td>
                    <td>
                        <input type="hidden" name="invoice_ids[]" value="${invoice.id}">
                        <input type="number" name="payment_amounts[]" class="payment-amount" 
                                max="${balance}" min="0" step="0.01" value="0" 
                                onchange="calculateTotal()">
                    </td>
                </tr>
            `;
        });
        
        document.getElementById('unpaidInvoicesTable').querySelector('tbody').innerHTML = html;
        calculateTotal();
    }
};
xhr.send();
}

function calculateTotal() {
const paymentInputs = document.querySelectorAll('.payment-amount');
let total = 0;

paymentInputs.forEach(function(input) {
    total += parseFloat(input.value) || 0;
});

document.getElementById('totalPayment').textContent = '$' + total.toFixed(2);
}

function formatDate(dateString) {
const date = new Date(dateString);
return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function handlePaymentSubmit(event) {
const total = parseFloat(document.getElementById('totalPayment').textContent.replace('$', ''));
if (total <= 0) {
    alert('Please enter at least one payment amount.');
    event.preventDefault();
    return false;
}
return true;
}

function toggleStudentSelection() {
const selection = document.getElementById('student_selection').value;
document.getElementById('class_selection').style.display = selection === 'class' ? 'block' : 'none';
document.getElementById('student_multi_selection').style.display = selection === 'selected' ? 'block' : 'none';
}

function selectTemplate(template) {
document.getElementById('template_' + template).checked = true;
}

function resetForm() {
document.getElementById('statementForm').reset();
document.getElementById('statement_preview').innerHTML = '<h3 style="text-align: center;">Statement Preview</h3><p style="text-align: center; color: #666;">(Select students and generate statements to see preview)</p>';
}

function previewStatement() {
// Here you would implement the preview functionality
// This is a placeholder that would be replaced with actual preview code
const statementType = document.getElementById('statement_type').value;
const template = document.querySelector('input[name="template"]:checked').value;

document.getElementById('statement_preview').innerHTML = `
    <h3 style="text-align: center;">Statement Preview</h3>
    <div style="text-align: center;">
        <p>Sample ${statementType} statement using ${template} template</p>
        <p>This is a preview of how your statement will look.</p>
    </div>
`;
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
// Any initialization code here
    
    // Add visual feedback when clicking on student names
    const studentLinks = document.querySelectorAll('.student-name-link');
    studentLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add a subtle ripple effect when clicking
            const ripple = document.createElement('span');
            ripple.classList.add('student-link-ripple');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(33, 150, 243, 0.3)';
            ripple.style.width = '100px';
            ripple.style.height = '100px';
            ripple.style.transform = 'translate(-50%, -50%)';
            ripple.style.pointerEvents = 'none';
            
            // Get position relative to the link
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Add random subtle background colors to student avatars for visual interest
    const avatars = document.querySelectorAll('.student-avatar');
    const colors = [
        { bg: '#e3f2fd', text: '#0d47a1' }, // Blue
        { bg: '#e8f5e9', text: '#2e7d32' }, // Green
        { bg: '#fff3e0', text: '#e65100' }, // Orange
        { bg: '#f3e5f5', text: '#7b1fa2' }, // Purple
        { bg: '#e0f7fa', text: '#006064' }  // Teal
    ];
    
    avatars.forEach(avatar => {
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        avatar.style.backgroundColor = randomColor.bg;
        avatar.style.color = randomColor.text;
    });
});
</script>

<?php include 'footer.php'; ?>