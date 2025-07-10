<?php
// customer_center.php - Multi-Tenant Customer Center with Dynamic Class Creation & Audit Trail

// --- BLOCK 1: SETUP & PRE-PROCESSING ---
// This logic must run before any HTML is outputted to allow for header redirects.
require_once 'config.php';
require_once 'functions.php';

// Start session here because we need it for redirects before header.php is included.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for login status and get school_id
if (!isset($_SESSION['school_id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];


// --- BLOCK 2: ALL FORM & ACTION HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = false;
    $active_tab = 'students'; // Default tab unless changed by an action

    // --- Item Management ---
    if (isset($_POST['add_item'])) {
        $action_taken = true;
        $active_tab = 'items';
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $item_type = $parent_id ? 'child' : 'parent';
        $item_id = createItem($pdo, $school_id, $_POST['name'], $_POST['price'], $_POST['description'], $parent_id, $item_type);
        
        // Audit Log
        $log_data = ['name' => $_POST['name'], 'price' => $_POST['price'], 'parent_id' => $parent_id];
        log_audit($pdo, 'CREATE', 'items', $item_id, ['data' => $log_data]);
    }
    
    if (isset($_POST['update_item'])) {
        $action_taken = true;
        $active_tab = 'items';
        $item_id = intval($_POST['item_id']);
        
        // Get 'before' state for audit
        $stmt_old = $pdo->prepare("SELECT * FROM items WHERE id = ? AND school_id = ?");
        $stmt_old->execute([$item_id, $school_id]);
        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $item_type = $parent_id ? 'child' : 'parent';
        updateItem($pdo, $item_id, $_POST['name'], $_POST['price'], $_POST['description'], $parent_id, $item_type, $school_id);
        
        // Get 'after' state and log
        $new_data = ['name' => $_POST['name'], 'price' => $_POST['price'], 'description' => $_POST['description'], 'parent_id' => $parent_id];
        log_audit($pdo, 'UPDATE', 'items', $item_id, ['before' => $old_data, 'after' => $new_data]);
    }

    if (isset($_POST['delete_item'])) {
        $action_taken = true;
        $active_tab = 'items';
        $item_id = intval($_POST['item_id']);

        // Get data before deleting for audit
        $stmt_old = $pdo->prepare("SELECT * FROM items WHERE id = ? AND school_id = ?");
        $stmt_old->execute([$item_id, $school_id]);
        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

        deleteItem($pdo, $item_id, $school_id);
        log_audit($pdo, 'DELETE', 'items', $item_id, ['data' => $old_data]);
    }

    // --- Student Management ---
    if (isset($_POST['addStudent'])) {
        $action_taken = true;
        $active_tab = 'students';
        $class_id = (isset($_POST['class_id']) && is_numeric($_POST['class_id'])) ? intval($_POST['class_id']) : null;
        $stmt = $pdo->prepare("INSERT INTO students (school_id, student_id_no, name, email, class_id, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $_POST['student_id_no'], $_POST['name'], $_POST['email'], $class_id, $_POST['phone'], $_POST['address']]);
        $student_id = $pdo->lastInsertId();

        // Audit Log
        $log_data = ['student_id_no' => $_POST['student_id_no'], 'name' => $_POST['name'], 'class_id' => $class_id];
        log_audit($pdo, 'CREATE', 'students', $student_id, ['data' => $log_data]);
    }

    if (isset($_POST['editStudent'])) {
        $action_taken = true;
        $active_tab = 'students';
        $student_id = intval($_POST['student_id']);

        // Get 'before' state for audit
        $stmt_old = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
        $stmt_old->execute([$student_id, $school_id]);
        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

        $class_id = (isset($_POST['class_id']) && is_numeric($_POST['class_id'])) ? intval($_POST['class_id']) : null;
        $stmt = $pdo->prepare("UPDATE students SET student_id_no = ?, name = ?, email = ?, class_id = ?, phone = ?, address = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$_POST['student_id_no'], $_POST['name'], $_POST['email'], $class_id, $_POST['phone'], $_POST['address'], $student_id, $school_id]);

        // Get 'after' state and log
        $new_data = ['student_id_no' => $_POST['student_id_no'], 'name' => $_POST['name'], 'email' => $_POST['email'], 'class_id' => $class_id, 'phone' => $_POST['phone'], 'address' => $_POST['address']];
        log_audit($pdo, 'UPDATE', 'students', $student_id, ['before' => $old_data, 'after' => $new_data]);
    }
    
    // --- Payment Processing ---
    if (isset($_POST['process_payment'])) {
        $action_taken = true;
        $active_tab = 'receipts'; // Redirect to receipts tab after payment
        $student_id_payment = intval($_POST['student_id']);
        
        $pdo->beginTransaction();
        try {
            // Find or create "Undeposited Funds" account
            $undeposited_account_id = getUndepositedFundsAccountId($pdo, $school_id);
            $total_payment = array_sum(array_filter($_POST['payment_amounts'], 'is_numeric'));
            
            if ($total_payment > 0) {
                $receipt_number = 'REC-' . strtoupper(uniqid());
                
                // Create the master receipt record
                $stmt = $pdo->prepare("INSERT INTO payment_receipts (school_id, receipt_number, student_id, payment_date, amount, payment_method, memo, coa_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $receipt_number, $student_id_payment, $_POST['payment_date'], $total_payment, $_POST['payment_method'], $_POST['memo'], $undeposited_account_id]);
                $receiptId = $pdo->lastInsertId();

                // Log the creation of the receipt
                $log_data = ['receipt_number' => $receipt_number, 'student_id' => $student_id_payment, 'amount' => $total_payment, 'method' => $_POST['payment_method']];
                log_audit($pdo, 'CREATE', 'payment_receipts', $receiptId, ['data' => $log_data]);

                // Apply payments to individual invoices
                foreach ($_POST['invoice_ids'] as $index => $invoice_id) {
                    $amount = floatval($_POST['payment_amounts'][$index]);
                    if ($amount > 0) {
                        $stmt = $pdo->prepare("INSERT INTO payments (school_id, invoice_id, student_id, payment_date, amount, payment_method, memo, receipt_id, coa_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$school_id, $invoice_id, $student_id_payment, $_POST['payment_date'], $amount, $_POST['payment_method'], $_POST['memo'], $receiptId, $undeposited_account_id]);
                    }
                }
                
                // Update the balance of the Undeposited Funds account
                updateAccountBalance($pdo, $undeposited_account_id, $total_payment, 'debit', $school_id);
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // Store error in session to display after redirect
            $_SESSION['error_message'] = "Error processing payment: " . $e->getMessage();
        }
    }

    // Final redirect after any POST action
    if($action_taken) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=" . $active_tab);
        exit();
    }
}

// Handle deleting a student via GET request (can be converted to POST for better security)
if (isset($_GET['delete_student'])) {
    $student_id_to_delete = intval($_GET['delete_student']);
    
    // Get data for audit before deleting
    $stmt_old = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
    $stmt_old->execute([$student_id_to_delete, $school_id]);
    $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

    // Instead of DELETE, we set status to inactive
    $stmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE id = ? AND school_id = ?");
    $stmt->execute([$student_id_to_delete, $school_id]);

    log_audit($pdo, 'DELETE', 'students', $student_id_to_delete, ['data' => $old_data]);

    header("Location: customer_center.php?tab=students");
    exit();
}

// --- BLOCK 3: PAGE DISPLAY SETUP ---
// Now that all processing is done, we can include the header and start the page.
require_once 'header.php';

// Display and clear any session-based error messages
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// --- Data Fetching for Display ---
$items = getItemsWithSubItems($pdo, $school_id);
$classes = getClasses($pdo, $school_id);
$students = getStudents($pdo, $school_id);
$invoices = getInvoices($pdo, $school_id);
$all_receipts = getAllReceipts($pdo, $school_id);
$asset_accounts = getAccountsByType($pdo, $school_id, 'asset');

?>
<style>
    .student-view-container { display: flex; min-height: 600px; }
    .student-list-panel { width: 40%; min-width: 300px; flex-shrink: 0; overflow-y: auto; border-right: 1px solid var(--border); padding-right: 10px; }
    .student-detail-panel { flex-grow: 1; overflow-y: auto; padding-left: 20px; min-width: 300px; }
    .resizer { width: 10px; cursor: col-resize; background-color: #e9ecef; position: relative; transition: background-color 0.2s; }
    .resizer:hover { background-color: var(--secondary); }
    .student-list-panel table tr.active { background-color: #e3f2fd !important; font-weight: bold; }
    .student-list-panel table tr { cursor: pointer; }
    #student-detail-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #777; flex-direction: column; padding: 2rem; }
    #student-detail-content { display: none; }
    .student-detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
    .student-info h3 { margin-top: 0; }
    .student-balance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-bottom: 2rem; }
    .balance-card { background: #f8f9fa; padding: 20px; border-radius: 12px; text-align: center; border-top: 4px solid var(--secondary, #3498db); }
    .balance-card h4 { margin: 0 0 10px 0; color: #6c757d; font-size: 1rem; }
    .balance-amount { font-size: 1.75rem; font-weight: 700; }
    .balance-amount.balance-due { color: var(--danger, #e74c3c); }
    .balance-amount.balance-zero { color: var(--success, #2ecc71); }
    .create-class-container { display: none; margin-top: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 8px; border: 1px solid var(--border); align-items: center;}
    .create-class-container input { flex-grow: 1; margin-right: 10px; }
    .info-section { margin-top: 1rem; }
    .info-item { display: flex; gap: 8px; margin-bottom: 5px; color: #555; }
    .info-item .label { font-weight: 600; color: var(--primary); }
    .items-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
    .item-card { background: #f8f9fa; border-radius: 12px; border: 1px solid var(--border); }
    .item-header { padding: 15px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .item-title { font-size: 1.1rem; color: var(--primary); margin: 0; }
    .item-details { padding: 15px; }
    .sub-items-section { padding: 0 15px 15px; }
    .sub-items-title { font-size: 0.9rem; color: #666; margin-bottom: 10px; border-top: 1px dashed #ccc; padding-top: 10px; }
    .sub-item-card { display: flex; justify-content: space-between; align-items: center; padding: 8px; border-radius: 8px; }
    .sub-item-card:nth-child(even) { background-color: #fff; }
    @media print {
        body.receipt-modal-active > *:not(#viewReceiptModal) { display: none; }
        body.receipt-modal-active #viewReceiptModal,
        body.receipt-modal-active .modal-content { display: block !important; position: absolute; left: 0; top: 0; width: 100%; height: auto; box-shadow: none; border: none; }
        body.receipt-modal-active .modal-header,
        body.receipt-modal-active .modal-footer { display: none; }
    }
</style>

<h1>Customer Center</h1>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link" onclick="openTab(event, 'students')">Students</button>
        <button class="tab-link" onclick="openTab(event, 'invoices')">Invoices</button>
        <button class="tab-link" onclick="openTab(event, 'items')">Items & Services</button>
        <button class="tab-link" onclick="openTab(event, 'receive_payment')">Receive Payment</button>
        <button class="tab-link" onclick="openTab(event, 'statements')">Statements</button>
        <button class="tab-link" onclick="openTab(event, 'receipts')">Receipts</button>
    </div>

    <div id="students" class="tab-content">
        <div class="student-view-container">
            <div class="student-list-panel">
                 <h3>All Students</h3>
                 <div class="table-actions"><button class="btn-add" onclick="openModal('addStudentModal')"><i class="fas fa-plus"></i> Add Student</button></div>
                 <table>
                     <thead><tr><th>Student ID</th><th>Name</th><th>Phone Number</th></tr></thead>
                     <tbody id="student-list-body">
                         <?php foreach($students as $student): ?>
                             <tr onclick="viewStudentDetails(<?php echo $student['id']; ?>, this)">
                                 <td><?= htmlspecialchars($student['student_id_no']) ?></td>
                                 <td><?= htmlspecialchars($student['name']) ?></td>
                                 <td><?= htmlspecialchars($student['phone']) ?></td>
                             </tr>
                         <?php endforeach; ?>
                     </tbody>
                 </table>
            </div>
            <div class="resizer" id="drag-handle"></div>
            <div class="student-detail-panel" id="student-detail-panel">
                <div id="student-detail-placeholder"><i class="fas fa-user-circle" style="font-size: 4rem; margin-bottom: 1rem; color: #ccc;"></i><p>Select a student to view details.</p></div>
                <div id="student-detail-content">
                    <div class="student-detail-header">
                        <div class="student-info">
                            <h3 id="detail-student-name"></h3>
                            <div class="info-section">
                                <div class="info-item"><span class="label">Student ID:</span> <span class="value" id="detail-student-id-no"></span></div>
                                <div class="info-item"><span class="label">Email:</span> <span class="value" id="detail-student-email"></span></div>
                                <div class="info-item"><span class="label">Phone:</span> <span class="value" id="detail-student-phone"></span></div>
                                <div class="info-item"><span class="label">Address:</span> <span class="value" id="detail-student-address"></span></div>
                            </div>
                        </div>
                        <div class="student-actions">
                            <button class="btn-secondary" id="detail-edit-student-btn"><i class="fas fa-edit"></i> Edit Student</button>
                            <a href="#" id="detail-create-invoice-btn" class="btn-primary"><i class="fas fa-plus"></i> Create Invoice</a>
                        </div>
                    </div>
                    <div class="student-balance-summary">
                        <div class="balance-card"><h4 class="balance-label">Current Balance</h4><span id="detail-balance-amount" class="balance-amount"></span></div>
                        <div class="balance-card"><h4>Total Invoiced</h4><span id="detail-total-invoiced" class="balance-amount"></span></div>
                        <div class="balance-card"><h4>Total Paid</h4><span id="detail-total-paid" class="balance-amount"></span></div>
                    </div>
                    <div class="transaction-history"><h3>Transaction History</h3><table class="transaction-table"><thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Actions</th></tr></thead><tbody id="detail-transaction-body"></tbody></table></div>
                </div>
            </div>
        </div>
    </div>

    <div id="invoices" class="tab-content">
        <div class="card">
            <h3>Invoices</h3>
            <div class="table-actions"><a href="create_invoice.php" class="btn-add"><i class="fas fa-plus"></i> Create Invoice</a></div>
            <table>
                <thead><tr><th>ID</th><th>Student</th><th>Date</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="7" class="text-center">No invoices found.</td></tr>
                    <?php else: ?>
                        <?php foreach($invoices as $inv): ?>
                        <tr>
                            <td><?= htmlspecialchars($inv['id']) ?></td>
                            <td><?= htmlspecialchars($inv['student_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($inv['due_date'])) ?></td>
                            <td>$<?= number_format($inv['total_amount'] ?? 0, 2) ?></td>
                            <td><span class="status-badge status-<?= strtolower(str_replace(' ', '', $inv['status'])) ?>"><?= htmlspecialchars($inv['status']) ?></span></td>
                            <td>
                                <a href="view_invoice.php?id=<?= $inv['id'] ?>" class="btn-icon btn-view"><i class="fas fa-eye"></i></a>
                                <a href="download_invoice.php?id=<?= $inv['id'] ?>" class="btn-icon btn-download"><i class="fas fa-download"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="items" class="tab-content">
        <div class="card">
            <h3>Items & Services</h3>
            <div class="table-actions"><button class="btn-add" onclick="openModal('addItemModal')"><i class="fas fa-plus"></i> Add New Item</button></div>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-header">
                            <h3 class="item-title"><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="item-actions">
                                <button class="btn-icon" onclick="openEditItemModal(<?= htmlspecialchars(json_encode($item)) ?>)"><i class="fas fa-edit"></i></button>
                                <form method="post" onsubmit="return confirm('Delete this item and all its sub-items?');" style="display:inline;"><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><button type="submit" name="delete_item" class="btn-icon"><i class="fas fa-trash"></i></button></form>
                            </div>
                        </div>
                        <div class="item-details"><div class="item-property"><span class="item-label">Price:</span><span class="item-value">$<?= number_format($item['price'], 2) ?></span></div></div>
                        <?php if (!empty($item['sub_items'])): ?>
                            <div class="sub-items-section">
                                <h4 class="sub-items-title">Sub-items</h4>
                                <?php foreach ($item['sub_items'] as $sub_item): ?>
                                    <div class="sub-item-card">
                                        <div>
                                            <span><?= htmlspecialchars($sub_item['name']) ?></span>
                                            <strong style="margin-left: 10px;">$<?= number_format($sub_item['price'], 2) ?></strong>
                                        </div>
                                        <div class="item-actions">
                                            <button class="btn-icon" onclick="openEditItemModal(<?= htmlspecialchars(json_encode($sub_item)) ?>)"><i class="fas fa-edit"></i></button>
                                            <form method="post" onsubmit="return confirm('Delete this sub-item?');" style="display:inline;"><input type="hidden" name="item_id" value="<?= $sub_item['id'] ?>"><button type="submit" name="delete_item" class="btn-icon"><i class="fas fa-trash"></i></button></form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="receive_payment" class="tab-content">
        <div class="card">
            <h3>Receive Payment</h3>
            <form id="paymentForm" method="post">
                <input type="hidden" name="process_payment" value="1">
                <div class="form-group"><label for="student_id_payment">Student</label><select name="student_id" id="student_id_payment" class="form-control" required onchange="loadUnpaidInvoices()"><option value="">Select Student</option><?php foreach ($students as $student): ?><option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option><?php endforeach; ?></select></div>
                
                <div class="form-group"><label for="payment_date">Payment Date</label><input type="date" name="payment_date" id="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label for="payment_method">Payment Method</label><select name="payment_method" id="payment_method" class="form-control" required><option>Cash</option><option>Bank Transfer</option><option>Mobile Money</option><option>Check</option></select></div>
                <div class="form-group"><label for="memo">Memo</label><textarea name="memo" id="memo" rows="2" class="form-control"></textarea></div>
                <h4>Unpaid Invoices</h4>
                <table id="unpaidInvoicesTable" class="table"><thead><tr><th>#</th><th>Date</th><th>Due</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th></tr></thead><tbody></tbody><tfoot><tr><td colspan="6" style="text-align:right;"><strong>Total:</strong></td><td><strong id="totalPayment">$0.00</strong></td></tr></tfoot></table>
                <div class="form-actions"><button type="submit" class="btn-primary">Record Payment</button></div>
            </form>
        </div>
    </div>
    
    <div id="statements" class="tab-content">
        <div class="card">
            <h3>Create Statements</h3>
            <form id="statementForm" method="post" action="generate_statement.php" target="_blank">
                <div class="form-row"><div class="form-group"><label for="statement_student_id">Select Student</label><select name="student_id" id="statement_student_id" class="form-control" required><option value="">-- Select a Student --</option><?php foreach ($students as $student): ?><option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="statement_date_from">Date From</label><input type="date" name="date_from" id="statement_date_from" class="form-control" required></div><div class="form-group"><label for="statement_date_to">Date To</label><input type="date" name="date_to" id="statement_date_to" class="form-control" required value="<?= date('Y-m-d') ?>"></div></div>
                <div class="form-actions"><button type="submit" class="btn-primary">Generate Statement</button></div>
            </form>
        </div>
    </div>

    <div id="receipts" class="tab-content">
        <div class="card">
            <h3>Payment Receipts</h3>
            <table>
                <thead><tr><th>Receipt #</th><th>Student</th><th>Date</th><th>Amount</th><th>Method</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($all_receipts)): ?>
                        <tr><td colspan="6" class="text-center">No receipts found.</td></tr>
                    <?php else: ?>
                        <?php foreach($all_receipts as $receipt): ?>
                        <tr>
                            <td><?= htmlspecialchars($receipt['receipt_number']) ?></td>
                            <td><?= htmlspecialchars($receipt['student_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($receipt['payment_date'])) ?></td>
                            <td>$<?= number_format($receipt['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($receipt['payment_method']) ?></td>
                            <td><button class="btn-icon btn-view" onclick="viewReceipt(<?= $receipt['id'] ?>)"><i class="fas fa-eye"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Add New Student</h3><span class="close" onclick="closeModal('addStudentModal')">&times;</span></div>
        <form method="post">
            <input type="hidden" name="addStudent" value="1">
            <div class="modal-body">
                <div class="form-group"><label>Full Name</label><input type="text" name="name" required class="form-control"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                <div class="form-group"><label>Address</label><textarea name="address" rows="2" class="form-control"></textarea></div>
                <div class="form-group"><label>Student ID No.</label><input type="text" name="student_id_no" class="form-control"></div>
                <div class="form-group">
                    <label for="add_student_class_id">Class</label>
                    <select name="class_id" id="add_student_class_id" class="form-control class-select">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?><option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option><?php endforeach; ?>
                        <option value="create_new">-- Create New Class --</option>
                    </select>
                </div>
                <div class="create-class-container" id="add_create_class_container">
                    <input type="text" id="add_new_class_name" placeholder="New Class Name" class="form-control">
                    <button type="button" class="btn btn-primary" onclick="saveNewClass('add')">Save</button>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button><button type="submit" class="btn-primary">Add Student</button></div>
        </form>
    </div>
</div>

<div id="editStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Student</h3><span class="close" onclick="closeModal('editStudentModal')">&times;</span></div>
        <form id="editStudentForm" method="post">
            <input type="hidden" name="editStudent" value="1"><input type="hidden" name="student_id" id="edit_student_id">
            <div class="modal-body">
                <div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required class="form-control"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div>
                <div class="form-group"><label>Address</label><textarea name="address" id="edit_address" rows="2" class="form-control"></textarea></div>
                <div class="form-group"><label>Student ID No.</label><input type="text" name="student_id_no" id="edit_student_id_no" class="form-control"></div>
                <div class="form-group">
                    <label for="edit_class_id">Class</label>
                    <select name="class_id" id="edit_class_id" class="form-control class-select">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?><option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option><?php endforeach; ?>
                        <option value="create_new">-- Create New Class --</option>
                    </select>
                </div>
                <div class="create-class-container" id="edit_create_class_container">
                    <input type="text" id="edit_new_class_name" placeholder="New Class Name" class="form-control">
                    <button type="button" class="btn btn-primary" onclick="saveNewClass('edit')">Save</button>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button><button type="submit" class="btn-primary">Update Student</button></div>
        </form>
    </div>
</div>

<div id="addItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Add New Item/Service</h3><span class="close" onclick="closeModal('addItemModal')">&times;</span></div>
        <form method="post">
            <input type="hidden" name="add_item" value="1">
            <div class="modal-body">
                <div class="form-group"><label>Item Name</label><input type="text" name="name" required class="form-control"></div>
                <div class="form-group"><label>Price</label><input type="number" name="price" step="0.01" required class="form-control"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                <div class="form-group"><label>Parent Item (for sub-items)</label><select name="parent_id" class="form-control"><option value="">None (This is a main item)</option><?php foreach ($items as $parent_item): ?><option value="<?= $parent_item['id'] ?>"><?= htmlspecialchars($parent_item['name']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('addItemModal')">Cancel</button><button type="submit" class="btn-primary">Save Item</button></div>
        </form>
    </div>
</div>

<div id="editItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Item/Service</h3><span class="close" onclick="closeModal('editItemModal')">&times;</span></div>
        <form id="editItemForm" method="post">
            <input type="hidden" name="update_item" value="1"><input type="hidden" name="item_id" id="edit_item_id">
            <div class="modal-body">
                <div class="form-group"><label>Item Name</label><input type="text" name="name" id="edit_item_name" required class="form-control"></div>
                <div class="form-group"><label>Price</label><input type="number" name="price" id="edit_item_price" step="0.01" required class="form-control"></div>
                <div class="form-group"><label>Description</label><textarea name="description" id="edit_item_description" rows="2" class="form-control"></textarea></div>
                <div class="form-group"><label>Parent Item</label><select name="parent_id" id="edit_parent_id" class="form-control"><option value="">None (This is a main item)</option><?php foreach ($items as $parent_item): ?><option value="<?= $parent_item['id'] ?>"><?= htmlspecialchars($parent_item['name']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('editItemModal')">Cancel</button><button type="submit" class="btn-primary">Update Item</button></div>
        </form>
    </div>
</div>

<div id="viewReceiptModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Receipt Details</h3>
            <span class="close" onclick="closeModal('viewReceiptModal')">&times;</span>
        </div>
        <div class="modal-body" id="receipt-details-body">
            <p>Loading...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('viewReceiptModal')">Close</button>
            <button type="button" class="btn-primary" onclick="printReceipt()">Print Receipt</button>
        </div>
    </div>
</div>

<script>
let currentStudentData = {};
function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }

function viewStudentDetails(studentId, rowElement) {
    document.getElementById('student-detail-placeholder').style.display = 'flex';
    document.getElementById('student-detail-content').style.display = 'none';
    document.querySelectorAll('#student-list-body tr').forEach(row => row.classList.remove('active'));
    if (rowElement) rowElement.classList.add('active');

    fetch(`get_student_details.php?id=${studentId}`).then(response => response.json()).then(data => {
        if (data.success) {
            currentStudentData = data.student;
            document.getElementById('detail-student-name').textContent = data.student.name;
            document.getElementById('detail-student-id-no').textContent = data.student.student_id_no || 'N/A';
            document.getElementById('detail-student-email').textContent = data.student.email;
            document.getElementById('detail-student-phone').textContent = data.student.phone;
            document.getElementById('detail-student-address').textContent = data.student.address;
            document.getElementById('detail-create-invoice-btn').href = `create_invoice.php?student_id=${studentId}`;
            document.getElementById('detail-edit-student-btn').onclick = () => editStudent(currentStudentData);
            const balanceAmountEl = document.getElementById('detail-balance-amount');
            balanceAmountEl.textContent = '$' + Math.abs(data.summary.balance).toFixed(2);
            balanceAmountEl.className = 'balance-amount';
            if (data.summary.balance > 0) balanceAmountEl.classList.add('balance-due');
            else balanceAmountEl.classList.add('balance-zero');
            document.getElementById('detail-total-invoiced').textContent = '$' + data.summary.totalInvoiced.toFixed(2);
            document.getElementById('detail-total-paid').textContent = '$' + data.summary.totalPaid.toFixed(2);
            const transactionBody = document.getElementById('detail-transaction-body');
            transactionBody.innerHTML = '';
            if (data.transactions.length > 0) {
                data.transactions.forEach(trans => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${new Date(trans.date).toLocaleDateString()}</td><td>${trans.type}</td><td>${trans.description}</td><td>$${trans.amount.toFixed(2)}</td><td>...</td>`;
                    transactionBody.appendChild(tr);
                });
            } else {
                transactionBody.innerHTML = '<tr><td colspan="5" class="text-center">No transactions found.</td></tr>';
            }
            document.getElementById('student-detail-placeholder').style.display = 'none';
            document.getElementById('student-detail-content').style.display = 'block';
        }
    });
}

function editStudent(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_address').value = student.address;
    document.getElementById('edit_student_id_no').value = student.student_id_no || '';
    document.getElementById('edit_class_id').value = student.class_id || '';
    openModal('editStudentModal');
}

function openEditItemModal(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_item_name').value = item.name;
    document.getElementById('edit_item_price').value = item.price;
    document.getElementById('edit_item_description').value = item.description || '';
    document.getElementById('edit_parent_id').value = item.parent_id || '';
    openModal('editItemModal');
}

document.querySelectorAll('.class-select').forEach(select => {
    select.addEventListener('change', function() {
        const modalType = this.id.startsWith('add') ? 'add' : 'edit';
        const container = document.getElementById(`${modalType}_create_class_container`);
        container.style.display = this.value === 'create_new' ? 'flex' : 'none';
    });
});

function saveNewClass(modalType) {
    const inputId = `${modalType}_new_class_name`;
    const newClassName = document.getElementById(inputId).value.trim();
    if (newClassName === '') { alert('Please enter a class name.'); return; }
    const formData = new FormData();
    formData.append('class_name', newClassName);
    fetch('ajax_create_class.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => {
        if (data.success) {
            const newOption = new Option(data.name, data.id, true, true);
            document.querySelectorAll('.class-select').forEach(select => {
                select.insertBefore(newOption.cloneNode(true), select.querySelector('option[value="create_new"]'));
            });
            document.getElementById(`${modalType}_student_class_id`).value = data.id;
            document.getElementById(`${modalType}_create_class_container`).style.display = 'none';
            document.getElementById(inputId).value = '';
        } else { alert('Error: ' + data.message); }
    }).catch(error => console.error('Error:', error));
}

function loadUnpaidInvoices() {
    const studentId = document.getElementById('student_id_payment').value;
    const tbody = document.querySelector('#unpaidInvoicesTable tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    if (!studentId) { tbody.innerHTML = '<tr><td colspan="7" class="text-center">Please select a student.</td></tr>'; return; }
    fetch(`get_unpaid_invoices.php?student_id=${studentId}`).then(response => response.json()).then(data => {
        tbody.innerHTML = '';
        if (data.success && data.data.length > 0) {
            data.data.forEach(invoice => {
                tbody.innerHTML += `<tr><td>${invoice.id}</td><td>${new Date(invoice.invoice_date).toLocaleDateString()}</td><td>${new Date(invoice.due_date).toLocaleDateString()}</td><td>$${parseFloat(invoice.total_amount).toFixed(2)}</td><td>$${parseFloat(invoice.paid_amount).toFixed(2)}</td><td>$${invoice.balance.toFixed(2)}</td><td><input type="hidden" name="invoice_ids[]" value="${invoice.id}"><input type="number" name="payment_amounts[]" class="form-control payment-amount" min="0" max="${invoice.balance.toFixed(2)}" step="0.01" value="0" oninput="calculateTotal()"></td></tr>`;
            });
        } else { tbody.innerHTML = '<tr><td colspan="7" class="text-center">No unpaid invoices for this student.</td></tr>'; }
        calculateTotal();
    });
}

function calculateTotal() {
    const total = Array.from(document.querySelectorAll('#paymentForm .payment-amount')).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    document.getElementById('totalPayment').textContent = '$' + total.toFixed(2);
}

function viewReceipt(receiptId) {
    const modalBody = document.getElementById('receipt-details-body');
    modalBody.innerHTML = '<p style="text-align:center;">Loading receipt...</p>';
    openModal('viewReceiptModal');
    
    fetch(`get_receipt.php?id=${receiptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const r = data.receipt;
                modalBody.innerHTML = `
                    <div id="receipt-printable-area">
                        <div style="text-align: center; margin-bottom: 20px;">
                            ${r.school_logo_url ? `<img src="${r.school_logo_url}" alt="Logo" style="max-width: 120px; max-height: 60px;"><br>` : ''}
                            <h3 style="margin: 10px 0 0 0;">${r.school_name}</h3>
                            <p style="margin: 5px 0; font-size: 0.9em; color: #555;">${r.school_address || ''}</p>
                            <p style="margin: 5px 0; font-size: 0.9em; color: #555;">${r.school_phone || ''} | ${r.school_email || ''}</p>
                        </div>
                        <hr>
                        <h4 style="text-align: center; margin-top: 20px; text-transform: uppercase; letter-spacing: 1px;">Payment Receipt</h4>
                        <p><strong>Receipt #:</strong> ${r.receipt_number}</p>
                        <p><strong>Student:</strong> ${r.student_name}</p>
                        <p><strong>Date:</strong> ${new Date(r.payment_date).toLocaleDateString()}</p>
                        <h3 style="margin-top: 20px; color: var(--success);">Amount Paid: $${parseFloat(r.amount).toFixed(2)}</h3>
                        <p><strong>Method:</strong> ${r.payment_method}</p>
                        <p><strong>Memo:</strong> ${r.memo || 'N/A'}</p>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `<p class="alert alert-danger">Could not load receipt details. ${data.error || ''}</p>`;
            }
        })
        .catch(err => {
            modalBody.innerHTML = '<p class="alert alert-danger">An error occurred while fetching the receipt.</p>';
            console.error('Fetch Error:', err);
        });
}

function printReceipt() {
    document.body.classList.add('receipt-modal-active');
    window.print();
    document.body.classList.remove('receipt-modal-active');
}

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'students';
    const studentIdForPayment = params.get('student_id');

    const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
    if (tabButton) {
        tabButton.click();
    } else {
        const firstTab = document.querySelector('.tab-link');
        if(firstTab) firstTab.click();
    }

    if (tab === 'receive_payment' && studentIdForPayment) {
        const studentSelect = document.getElementById('student_id_payment');
        if (studentSelect) {
            studentSelect.value = studentIdForPayment;
            studentSelect.dispatchEvent(new Event('change'));
        }
    }

    const resizer = document.getElementById('drag-handle');
    const leftPanel = document.querySelector('.student-list-panel');
    let isResizing = false;
    resizer.addEventListener('mousedown', (e) => { 
        isResizing = true; 
        document.addEventListener('mousemove', handleMouseMove); 
        document.addEventListener('mouseup', () => { 
            isResizing = false; 
            document.removeEventListener('mousemove', handleMouseMove); 
        }, { once: true }); 
    });
    function handleMouseMove(e) { 
        if (!isResizing) return; 
        const containerRect = leftPanel.parentElement.getBoundingClientRect(); 
        const newLeftWidth = e.clientX - containerRect.left; 
        if (newLeftWidth > 250 && newLeftWidth < (containerRect.width - 300)) { 
            leftPanel.style.width = newLeftWidth + 'px'; 
        } 
    }
});
</script>