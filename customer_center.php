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

// Process payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $student_id = intval($_POST['student_id']);
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'];
        $memo = trim($_POST['memo']);
        $invoice_ids = $_POST['invoice_ids'] ?? [];
        $payment_amounts = $_POST['payment_amounts'] ?? [];

        $pdo->beginTransaction();

        foreach ($invoice_ids as $index => $invoice_id) {
            $amount = floatval($payment_amounts[$index]);
            $invoice_id = intval($invoice_id);

            if ($amount > 0) {
                // Insert payment
                $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, student_id, payment_date, amount, payment_method, memo) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$invoice_id, $student_id, $payment_date, $amount, $payment_method, $memo]);

            }
        }

        $pdo->commit();
        echo "<script>showAlert('Payment recorded successfully!'); window.location.href = 'customer_center.php?view_student=' + $student_id;</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>showAlert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Process statement generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generateStatements'])) {
    try {
        // Get form data
        $statementType = $_POST['statement_type'];
        $dateFrom = $_POST['date_from'];
        $dateTo = $_POST['date_to'];
        $studentSelection = $_POST['student_selection'];
        $sortBy = $_POST['sort_by'];
        $includeZero = isset($_POST['include_zero']);
        $includeOverdue = isset($_POST['include_overdue']);
        $includeMessage = isset($_POST['include_message']);
        $statementMessage = $_POST['statement_message'];
        $template = $_POST['template'];

        // Get selected students
        $studentIds = [];
        if ($studentSelection === 'selected') {
            $studentIds = $_POST['selected_students'] ?? [];
        } elseif ($studentSelection === 'class') {
            $classId = $_POST['class_id'];
            $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id = ?");
            $stmt->execute([$classId]);
            $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else { // All students
            $stmt = $pdo->query("SELECT id FROM students");
            $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        if (empty($studentIds)) {
            throw new Exception("No students selected");
        }

        // Get student data with balances
        $students = [];
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sql = "SELECT 
                    s.id,
                    s.name,
                    s.email,
                    s.address,
                    COALESCE(SUM(i.total_amount), 0) AS total_invoiced,
                    COALESCE(SUM(i.paid_amount), 0) AS total_paid,
                    (COALESCE(SUM(i.total_amount), 0) - COALESCE(SUM(i.paid_amount), 0)) AS balance
                FROM students s
                LEFT JOIN invoices i ON s.id = i.student_id
                WHERE s.id IN ($placeholders)
                GROUP BY s.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($studentIds);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Apply filters
        $filteredStudents = array_filter($students, function($student) use ($includeZero) {
            if (!$includeZero && $student['balance'] == 0) return false;
            return true;
        });

        // Sort students
        usort($filteredStudents, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'name': return strcmp($a['name'], $b['name']);
                case 'id': return $a['id'] - $b['id'];
                case 'balance': return $b['balance'] - $a['balance'];
                default: return 0;
            }
        });

        // Get transactions for each student
        foreach ($filteredStudents as &$student) {
            $stmt = $pdo->prepare("
                SELECT 
                    i.id AS invoice_id,
                    i.invoice_date AS date,
                    'invoice' AS type,
                    i.total_amount AS amount,
                    i.balance,
                    NULL AS payment_method
                FROM invoices i
                WHERE i.student_id = ?
                AND (i.invoice_date BETWEEN ? AND ?)
                
                UNION ALL
                
                SELECT 
                    p.id AS payment_id,
                    p.payment_date AS date,
                    'payment' AS type,
                    p.amount AS amount,
                    NULL AS balance,
                    p.payment_method
                FROM payments p
                WHERE p.student_id = ?
                AND (p.payment_date BETWEEN ? AND ?)
                ORDER BY date
            ");

            $stmt->execute([
                $student['id'],
                $dateFrom,
                $dateTo,
                $student['id'],
                $dateFrom,
                $dateTo
            ]);
            
            $student['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Generate HTML statement
        ob_start();
        include "templates/statement_$template.php";
        $htmlContent = ob_get_clean();

        // Output PDF or HTML
        if ($_POST['action'] === 'download') {
            require_once 'vendor/autoload.php';
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml($htmlContent);
            $dompdf->render();
            $dompdf->stream("statements-".date('Ymd').".pdf");
        } else {
            echo $htmlContent;
        }
        exit();

    } catch (Exception $e) {
        $error = "Error generating statements: " . $e->getMessage();
        echo "<script>showAlert('".addslashes($error)."');</script>";
    }
}

// Get student detail if viewing single student
$studentDetail = null;
$studentTransactions = [];
if (isset($_GET['view_student'])) {
    $studentId = intval($_GET['view_student']);
    
    // Get student details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $studentDetail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($studentDetail) {
        // Replace the invoice query with:
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.id AS invoice_number,
            i.invoice_date,
            i.due_date,
            i.total_amount,
            i.paid_amount,
            i.balance
        FROM invoices i
        WHERE i.student_id = ?
    ");
        $stmt->execute([$studentId]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch payments
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.payment_date AS date,
                p.amount,
                p.payment_method,
                p.memo,
                p.invoice_id,
                r.id AS receipt_id,
                r.receipt_number
            FROM payments p
            LEFT JOIN payment_receipts r ON p.receipt_id = r.id
            WHERE p.student_id = ?
            ORDER BY p.payment_date ASC
        ");
        $stmt->execute([$studentId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

        // Build transaction history
        $allTransactions = [];
        foreach ($invoices as $invoice) {
            $allTransactions[] = [
                'id' => $invoice['id'] ?? 0,
                'date' => $invoice['invoice_date'] ?? '',
                'type' => 'invoice',
                'description' => 'Invoice #' . ($invoice['invoice_number'] ?? ''),
                'amount' => $invoice['total_amount'] ?? 0,
                'invoice_balance' => $invoice['balance'] ?? 0
            ];
        }
        foreach ($payments as $payment) {
            $allTransactions[] = [
                'id' => $payment['id'] ?? 0,
                'date' => $payment['date'] ?? '',
                'type' => 'payment',
                'description' => 'Payment for Invoice #' . ($payment['invoice_id'] ?? '') . ' (' . ($payment['payment_method'] ?? '') . ')',
                'amount' => $payment['amount'] ?? 0
            ];
        }

        if ($trans['type'] === 'payment'): ?>
        <a href="javascript:void(0);" onclick="viewReceipt(<?= $trans['receipt_id'] ?>)">
            Receipt #<?= $trans['receipt_number'] ?>
        </a>
        <?php endif;

        // Calculate balances
        usort($allTransactions, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        $studentTransactions = $allTransactions;

        // Initialize invoice balances using the calculated balances from the SQL query
        $invoiceBalances = [];
        foreach ($invoices as $invoice) {
            $invoiceBalances[$invoice['id']] = $invoice['total_amount'];
        }

        // Calculate running balances for each transaction
        $runningTotal = 0;
        foreach ($allTransactions as &$transaction) {
            $invoiceId = isset($transaction['invoice_id']) ? $transaction['invoice_id'] : (isset($transaction['id']) ? $transaction['id'] : null);

            if ($transaction['type'] === 'invoice') {
                $runningTotal += $transaction['amount'];
            } else if ($transaction['type'] === 'payment') {
                $runningTotal -= $transaction['amount'];
                // Reduce the balance for this invoice
                if (isset($invoiceBalances[$invoiceId])) {
                    $invoiceBalances[$invoiceId] -= $transaction['amount'];
                }
            }

            // Store the current balance for this invoice
            $transaction['invoice_balance'] = isset($invoiceBalances[$invoiceId]) ? $invoiceBalances[$invoiceId] : 0;
            $transaction['running_balance'] = $runningTotal;
        }

        // Sort transactions by date (newest first) for display
        usort($allTransactions, function($a, $b) {
            $dateCompare = strtotime($b['date']) - strtotime($a['date']);
            // If same date, put invoices before payments
            if ($dateCompare === 0) {
                return ($a['type'] === 'invoice') ? -1 : 1;
            }
            return $dateCompare;
        });

        $studentTransactions = $allTransactions;
        if (isset($_GET['view_student'])) {
            $studentId = intval($_GET['view_student']);
            // Re-fetch invoices and payments to get updated amounts
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    i.id AS invoice_number,
                    i.invoice_date,
                    i.due_date,
                    i.total_amount,
                    i.paid_amount,
                    i.balance  -- Use the existing generated column
                FROM invoices i
                WHERE i.student_id = ?
            ");
            $stmt->execute([$studentId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recalculate totals
        $totalInvoiced = array_sum(array_column($invoices, 'total_amount'));
        $totalPaid = array_sum(array_column($invoices, 'paid_amount'));
        $studentBalance = $totalInvoiced - $totalPaid;
    }
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
    <button class="tab-link" onclick="openTab(event, 'receipts')">Receipts</button>
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
        
        <?php foreach ($studentTransactions as $trans): ?>
        <tr>
            <td><?= date('M d, Y', strtotime($trans['date'])) ?></td>
            <td><?= ucfirst($trans['type']) ?></td>
            <td>
                <?php if ($trans['type'] === 'invoice'): ?>
                    Invoice #<?= $trans['id'] ?? 'N/A' ?>
                <?php else: ?>
                    Payment for Invoice #<?= $trans['invoice_id'] ?? 'N/A' ?>
                <?php endif; ?>
            </td>
            <td>$<?= number_format($trans['amount'] ?? 0, 2) ?></td>
            <td>
                <?php if ($trans['type'] === 'invoice'): ?>
                    $<?= number_format($trans['invoice_balance'] ?? 0, 2) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td>
                <?php if ($trans['type'] === 'invoice'): ?>
                    <a href="view_invoice.php?id=<?= $trans['id'] ?>" class="btn-small">View</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
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
            <button class="btn-secondary" id="downloadSelected">Download Selected</button>
            <button class="btn-secondary" id="sendWhatsApp">Send via WhatsApp</button>
        </div>
        <form id="invoiceSelectionForm">
            <table>
                <thead>
                    <tr>
                        <th><!-- Checkbox column --></th>
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
                        <td>
                            <input type="checkbox" name="selected_invoices[]" 
                                   value="<?php echo $inv['id']; ?>" 
                                   class="invoice-checkbox">
                        </td>
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
                            <a href="download_invoice.php?id=<?php echo $inv['id']; ?>" class="btn-small">Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
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

<!-- Receipts Tab -->
<div id="receipts" class="tab-content">
    <div class="card">
        <h3>Payment Receipts</h3>
        <div class="table-controls">
            <div class="search-box">
                <input type="text" id="receiptSearch" placeholder="Search receipts..." onkeyup="searchReceipts()">
                <button class="btn-search" onclick="searchReceipts()">🔍</button>
            </div>
            <div class="filter-controls">
                <label for="dateFilter">Date Range:</label>
                <input type="date" id="startDate">
                <input type="date" id="endDate">
                <button class="btn-filter" onclick="filterReceipts()">Filter</button>
            </div>
        </div>
        <table id="receiptsTable">
        <thead>
            <tr>
                <th>Receipt #</th>
                <th>Student</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $receipts = getAllReceipts($pdo);
            foreach ($receipts as $receipt) : ?>
                <tr data-receipt-id="<?= $receipt['id'] ?>">
                    <td><?= htmlspecialchars($receipt['receipt_number']) ?></td>
                    <td><?= htmlspecialchars($receipt['student_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($receipt['payment_date'])) ?></td>
                    <td>Ksh <?= number_format($receipt['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($receipt['payment_method']) ?></td>
                    <td>
                        <button class="btn-view" onclick="viewReceipt(<?= $receipt['id'] ?>)">View</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
        <!-- Inside receipts tab content -->
        <div class="pagination">
            <button id="prevPage" class="btn-pagination">Previous</button>
            <span id="currentPage">1</span>
            <button id="nextPage" class="btn-pagination">Next</button>
        </div>
    </div>
</div>
</div>

<!-- Updated Receipt Modal -->
<div id="receiptModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Payment Receipt</h3>
            <span class="close" onclick="closeReceiptModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="receiptContent" class="receipt-container"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" onclick="printReceipt()">Print Receipt</button>
            <button class="btn-secondary" onclick="closeReceiptModal()">Close</button>
        </div>
    </div>
</div>

<!-- Updated Add Student Modal -->
<div id="addStudentModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Add New Student</h3>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addStudentForm" method="post">
                <input type="hidden" name="addStudent" value="1">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" name="name" id="name" required class="modal-input">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required class="modal-input">
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" name="phone" id="phone" required class="modal-input">
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea name="address" id="address" rows="3" required class="modal-input"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Add Student</button>
                    <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Updated Edit Student Modal -->
<div id="editStudentModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Edit Student</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editStudentForm" method="post">
                <input type="hidden" name="student_id" id="edit_student_id">
                <input type="hidden" name="editStudent" value="1">
                <div class="form-group">
                    <label for="edit_name">Name:</label>
                    <input type="text" name="name" id="edit_name" required class="modal-input">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" name="email" id="edit_email" required class="modal-input">
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone:</label>
                    <input type="text" name="phone" id="edit_phone" required class="modal-input">
                </div>
                <div class="form-group">
                    <label for="edit_address">Address:</label>
                    <textarea name="address" id="edit_address" rows="3" required class="modal-input"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Update Student</button>
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
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

    fetch(`get_unpaid_invoices.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const invoices = data.data;
                let html = '';
                invoices.forEach(invoice => {
                    html += `
                        <tr>
                            <td>${invoice.id}</td>
                            <td>${new Date(invoice.invoice_date).toLocaleDateString()}</td>
                            <td>${new Date(invoice.due_date).toLocaleDateString()}</td>
                            <td>$${invoice.total_amount.toFixed(2)}</td>
                            <td>$${invoice.paid_amount.toFixed(2)}</td>
                            <td>$${(invoice.total_amount - invoice.paid_amount).toFixed(2)}</td>
                            <td>
                                <input type="hidden" name="invoice_ids[]" value="${invoice.id}">
                                <input type="number" name="payment_amounts[]" class="payment-amount" 
                                       min="0" max="${(invoice.total_amount - invoice.paid_amount).toFixed(2)}" 
                                       step="0.01" value="0" oninput="calculateTotal()">
                            </td>
                        </tr>
                    `;
                });
                document.querySelector('#unpaidInvoicesTable tbody').innerHTML = html;
                calculateTotal();
            } else {
                alert('Error loading invoices: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
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

// In customer_center.php
function handlePaymentSubmit(event) {
  event.preventDefault();
  
  const formData = new FormData(document.getElementById('paymentForm'));
  const total = parseFloat(document.getElementById('totalPayment').textContent.replace('$', ''));
  
  if (total <= 0) {
    alert('Please enter at least one payment amount.');
    return false;
  }

  // Show loading indicator
  const submitBtn = event.target.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Processing...';

  fetch('record_payment.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show receipt modal
      showReceipt({
        receipt_number: data.receipt_number,
        student_name: document.querySelector('#student_id option:checked').textContent,
        payment_date: document.getElementById('payment_date').value,
        amount: total,
        payment_method: document.getElementById('payment_method').value,
        memo: document.getElementById('memo').value
      });
      const footer = `
      <div class="form-actions">
                <button class="btn-primary" onclick="printReceipt()">Print Receipt</button>
                <button class="btn-secondary" onclick="closeReceiptModal()">Close</button>
                <button class="btn-view" onclick="openReceiptsTab()">View in Receipts Tab</button>
            </div>
        `;
      document.getElementById('receiptContent').insertAdjacentHTML('beforeend', footer);
      
      // Reset form and reload data
      document.getElementById('paymentForm').reset();
      loadUnpaidInvoices();
      submitBtn.disabled = false;
      submitBtn.textContent = 'Record Payment';
      
      // Optionally, you can redirect to the receipts tab
      openTab(event, 'receipts');
    } else {
      showAlert('Error: ' + (data.error || 'Payment failed'));
      submitBtn.disabled = false;
      submitBtn.textContent = 'Record Payment';
    }
  })
  .catch(error => {
    showAlert('Network error: ' + error.message);
    submitBtn.disabled = false;
    submitBtn.textContent = 'Record Payment';
  });
  
  return false;
}

function showReceipt(receipt) {
  const content = `
    <div class="receipt-header">
      <h2>Bloomfield School</h2>
      <p>123 School Street, Nairobi, Kenya</p>
      <p>Phone: +254 712 345 678</p>
    </div>
    
    <div class="receipt-details">
      <p><strong>Receipt No:</strong> ${receipt.receipt_number}</p>
      <p><strong>Date:</strong> ${new Date(receipt.payment_date).toLocaleDateString()}</p>
    </div>
    
    <div class="student-info">
      <p><strong>Student:</strong> ${receipt.student_name}</p>
      <p><strong>Receipt For:</strong> Payment Received</p>
    </div>
    
    <table class="receipt-items">
      <thead>
        <tr>
          <th>Description</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>School Fees Payment</td>
          <td>Ksh ${parseFloat(receipt.amount).toFixed(2)}</td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <th>Total Paid</th>
          <th>Ksh ${parseFloat(receipt.amount).toFixed(2)}</th>
        </tr>
      </tfoot>
    </table>
    
    <div class="payment-method">
      <p><strong>Payment Method:</strong> ${receipt.payment_method}</p>
      <p><strong>Memo:</strong> ${receipt.memo || 'N/A'}</p>
    </div>
    
    <div class="receipt-footer">
      <p>Thank you for your payment!</p>
    </div>
  `;
  
  document.getElementById('receiptContent').innerHTML = content;
  document.getElementById('receiptModal').style.display = 'block';
}

function openReceiptsTab() {
    
    // Switch to receipts tab
    const tabs = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');
    
    // Remove active class from all tabs
    tabs.forEach(tab => tab.classList.remove('active'));
    contents.forEach(content => content.style.display = 'none');
    
    // Activate receipts tab
    document.querySelector('[onclick="openTab(event, \'receipts\')"]').classList.add('active');
    document.getElementById('receipts').style.display = 'block';
    
    // Highlight the receipt in the table
    highlightReceipt(receipt.id);
}

function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}


function highlightReceipt(receiptId) {
    const rows = document.querySelectorAll('#receiptsTable tbody tr');
    rows.forEach(row => {
        if (row.dataset.receiptId == receiptId) {
            row.style.backgroundColor = '#e3f2fd';
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            row.style.backgroundColor = '';
        }
    });
}

function searchReceipts() {
    const searchTerm = document.getElementById('receiptSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#receiptsTable tbody tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const receiptNum = cells[0].textContent.toLowerCase();
        const studentName = cells[1].textContent.toLowerCase();
        const date = cells[2].textContent.toLowerCase();
        const amount = cells[3].textContent.toLowerCase();
        const method = cells[4].textContent.toLowerCase();
        
        if (receiptNum.includes(searchTerm) || 
            studentName.includes(searchTerm) || 
            date.includes(searchTerm) || 
            amount.includes(searchTerm) || 
            method.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterReceipts() {
    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);
    const rows = document.querySelectorAll('#receiptsTable tbody tr');
    
    rows.forEach(row => {
        const dateCell = row.querySelector('td:nth-child(3)');
        if (!dateCell) return;
        
        const receiptDate = new Date(dateCell.textContent);
        
        if ((!startDate || receiptDate >= startDate) && 
            (!endDate || receiptDate <= endDate)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function sortReceipts(column) {
    const table = document.getElementById('receiptsTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td:nth-child(${getColumnIndex(column)})`).textContent;
        const bValue = b.querySelector(`td:nth-child(${getColumnIndex(column)})`).textContent;
        
        // Handle numeric values
        if (column === 'amount') {
            return parseFloat(aValue.replace('Ksh ', '')) - parseFloat(bValue.replace('Ksh ', ''));
        }
        
        // Handle dates
        if (column === 'payment_date') {
            return new Date(aValue) - new Date(bValue);
        }
        
        // Default to string comparison
        return aValue.localeCompare(bValue);
    });
    
    // Clear and re-add sorted rows
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

function getColumnIndex(column) {
    const columns = {
        'receipt_number': 1,
        'student_name': 2,
        'payment_date': 3,
        'amount': 4,
        'payment_method': 5
    };
    return columns[column] || 1;
}

function viewReceipt(receiptId) {
    fetch(`get_receipt.php?id=${receiptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showReceipt(data.receipt);
            } else {
                showAlert('Receipt not found');
            }
        });
}

function printReceipt() {
  const printContent = document.getElementById('receiptContent').innerHTML;
  const originalContent = document.body.innerHTML;
  
  document.body.innerHTML = printContent;
  window.print();
  document.body.innerHTML = originalContent;
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
    const formData = new FormData(document.getElementById('statementForm'));
    formData.append('action', 'preview');

    fetch('customer_center.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('statement_preview').innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error generating preview');
    });
}

// Update form submission handler
document.getElementById('statementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'download');
    
    // Submit the form normally for PDF download
    const newForm = document.createElement('form');
    newForm.method = 'POST';
    newForm.action = 'customer_center.php';
    newForm.style.display = 'none';
    
    for (const [key, value] of formData) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        newForm.appendChild(input);
    }
    
    document.body.appendChild(newForm);
    newForm.submit();
});

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
// Any initialization code here
     // Add new event listeners
    document.getElementById('downloadSelected').addEventListener('click', downloadSelectedInvoices);
    document.getElementById('sendWhatsApp').addEventListener('click', sendViaWhatsApp);
    
    // Add "Select All" functionality
    const selectAll = document.createElement('input');
    selectAll.type = 'checkbox';
    selectAll.id = 'selectAllInvoices';
    selectAll.style.marginRight = '5px';
    
    const firstHeader = document.querySelector('#invoices th:first-child');
    if (firstHeader) {
        firstHeader.appendChild(selectAll);
    }
    
    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.invoice-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
});
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

// Add this after your other JavaScript functions
let currentPage = 1;
const rowsPerPage = 10;

function setupPagination() {
    const rows = document.querySelectorAll('#receiptsTable tbody tr');
    const pageCount = Math.ceil(rows.length / rowsPerPage);
    
    // Update pagination controls
    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) showPage(--currentPage);
    });
    
    document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < pageCount) showPage(++currentPage);
    });
    
    // Initial page display
    showPage(currentPage);
}

function showPage(page) {
    const rows = document.querySelectorAll('#receiptsTable tbody tr');
    const startIndex = (page - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    
    rows.forEach((row, index) => {
        row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
    });
    
    document.getElementById('currentPage').textContent = page;
}

// Initialize pagination when page loads
document.addEventListener('DOMContentLoaded', setupPagination);

function downloadSelectedInvoices() {
    const selected = [];
    document.querySelectorAll('.invoice-checkbox:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });

    if (selected.length === 0) {
        showAlert('Please select at least one invoice');
        return;
    }

    // Create a temporary form to submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'download_invoices.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'invoice_ids';
    input.value = JSON.stringify(selected);
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

function sendViaWhatsApp() {
    const selected = [];
    document.querySelectorAll('.invoice-checkbox:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });

    if (selected.length === 0) {
        showAlert('Please select at least one invoice');
        return;
    }

    // In a real implementation, this would send to your backend
    // For now, we'll simulate the behavior
    if (selected.length === 1) {
        // For single invoice, open WhatsApp directly
        const invoiceId = selected[0];
        const message = encodeURIComponent(`Please find your invoice attached: ${window.location.origin}/download_invoice.php?id=${invoiceId}`);
        window.open(`https://api.whatsapp.com/send?text=${message}`, '_blank');
    } else {
        showAlert('Preparing to send multiple invoices via WhatsApp...');
        // In real implementation, this would create a ZIP file and send
    }
}

</script>