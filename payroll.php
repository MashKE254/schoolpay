<?php
/**
 * payroll.php - Unified Employee Management and Payroll Processing System
 * This script provides a complete hub for all employee-related tasks:
 * - A tabbed interface for Employee List, Add/Edit Employee, Monthly Payroll, Weekly Casuals, and Payroll History.
 * - Full CRUD (Create, Read, Update, Delete) functionality for employee records.
 * - Automated calculation of Kenyan statutory deductions for payroll.
 * - Separate, streamlined processing for salaried and casual employees.
 * - Correct, balanced, double-entry integration with the accounting ledger.
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'header.php'; // Handles session start and sets $school_id

// ===================================================================================
// --- POST REQUEST HANDLING (SERVER-SIDE LOGIC) ---
// ===================================================================================

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        $pdo->beginTransaction();

        // --- ACTION: Add or Update Employee ---
        if ($action === 'add_employee' || $action === 'update_employee') {
            // Sanitize and validate inputs
            $employee_id_post = trim($_POST['employee_id']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $department = $_POST['department'];
            $position = trim($_POST['position']);
            $employment_type = $_POST['employment_type'];
            $hire_date = $_POST['hire_date'];
            $basic_salary = !empty($_POST['basic_salary']) ? floatval($_POST['basic_salary']) : 0;
            $house_allowance = !empty($_POST['house_allowance']) ? floatval($_POST['house_allowance']) : 0;
            $transport_allowance = !empty($_POST['transport_allowance']) ? floatval($_POST['transport_allowance']) : 0;
            $daily_rate = !empty($_POST['daily_rate']) ? floatval($_POST['daily_rate']) : 0;
            $kra_pin = trim($_POST['kra_pin']);
            $nhif_number = trim($_POST['nhif_number']);
            $nssf_number = trim($_POST['nssf_number']);
            $status = $_POST['status'] ?? 'active';

            if ($action === 'add_employee') {
                $stmt = $pdo->prepare(
                    "INSERT INTO employees (school_id, employee_id, first_name, last_name, email, phone, department, position, employment_type, hire_date, basic_salary, house_allowance, transport_allowance, daily_rate, kra_pin, nhif_number, nssf_number, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$school_id, $employee_id_post, $first_name, $last_name, $email, $phone, $department, $position, $employment_type, $hire_date, $basic_salary, $house_allowance, $transport_allowance, $daily_rate, $kra_pin, $nhif_number, $nssf_number, $status]);
                $success_message = "Employee added successfully!";
            } else { // update_employee
                $id_to_update = (int)$_POST['id'];
                $stmt = $pdo->prepare(
                    "UPDATE employees SET employee_id=?, first_name=?, last_name=?, email=?, phone=?, department=?, position=?, employment_type=?, hire_date=?, basic_salary=?, house_allowance=?, transport_allowance=?, daily_rate=?, kra_pin=?, nhif_number=?, nssf_number=?, status=?
                     WHERE id = ? AND school_id = ?"
                );
                $stmt->execute([$employee_id_post, $first_name, $last_name, $email, $phone, $department, $position, $employment_type, $hire_date, $basic_salary, $house_allowance, $transport_allowance, $daily_rate, $kra_pin, $nhif_number, $nssf_number, $status, $id_to_update, $school_id]);
                $success_message = "Employee updated successfully!";
            }
        }
        // --- ACTION: Delete Employee (Soft Delete by setting status to inactive) ---
        elseif ($action === 'delete_employee') {
            $id_to_delete = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE employees SET status = 'inactive' WHERE id = ? AND school_id = ?");
            $stmt->execute([$id_to_delete, $school_id]);
            $success_message = "Employee deactivated successfully.";
        }
        
        // --- ACTION: Run Monthly Payroll ---
        elseif ($action === 'run_monthly_payroll') {
            $pay_period = $_POST['pay_period'];
            $pay_date = date('Y-m-t', strtotime($pay_period . '-01'));
            $payment_account_id = (int)$_POST['payroll_payment_account_id'];

            if(empty($payment_account_id)) {
                throw new Exception("You must select a bank account to pay salaries from.");
            }

            // Get or Create necessary accounts for payroll liabilities and expenses
            $expense_account = getOrCreateAccount($pdo, $school_id, 'Salaries & Wages', 'expense', '6010');
            $paye_liability = getOrCreateAccount($pdo, $school_id, 'PAYE Payable', 'liability', '2100');
            $nhif_liability = getOrCreateAccount($pdo, $school_id, 'NHIF Payable', 'liability', '2110');
            $nssf_liability = getOrCreateAccount($pdo, $school_id, 'NSSF Payable', 'liability', '2120');
            $levy_liability = getOrCreateAccount($pdo, $school_id, 'Housing Levy Payable', 'liability', '2130');

            $monthly_employees_stmt = $pdo->prepare("SELECT * FROM employees WHERE school_id = ? AND status = 'active' AND employment_type = 'monthly'");
            $monthly_employees_stmt->execute([$school_id]);
            $monthly_employees = $monthly_employees_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($monthly_employees)) {
                throw new Exception("No active monthly employees found to process payroll.");
            }

            foreach($monthly_employees as $emp) {
                $gross_pay = (float)$emp['basic_salary'] + (float)$emp['house_allowance'] + (float)$emp['transport_allowance'];
                if ($gross_pay <= 0) continue; // Skip employees with no salary defined

                $deductions = calculate_kenyan_deductions($gross_pay);
                
                // 1. Create the historical payroll record
                $stmt_payroll = $pdo->prepare("INSERT INTO payroll (school_id, employee_id, employee_name, employee_type, pay_period, pay_date, gross_pay, tax, insurance, retirement, other_deduction, total_deductions, net_pay, status) VALUES (?, ?, ?, 'monthly', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Processed')");
                $stmt_payroll->execute([$school_id, $emp['id'], $emp['first_name'].' '.$emp['last_name'], $pay_period, $pay_date, $deductions['gross_pay'], $deductions['paye'], $deductions['nhif'], $deductions['nssf'], $deductions['housing_levy'], $deductions['total_deductions'], $deductions['net_pay']]);

                // 2. Create the balanced, multi-line journal entry for the ledger
                $description = "Monthly salary for {$emp['first_name']} {$emp['last_name']} ({$pay_period})";
                
                // DEBIT: The total gross pay is an expense to the company
                create_single_expense_entry($pdo, $school_id, $pay_date, $description, $deductions['gross_pay'], $expense_account, 'debit');

                // CREDIT: These are the liabilities and the cash payout
                create_single_expense_entry($pdo, $school_id, $pay_date, "Net pay to {$emp['first_name']}", $deductions['net_pay'], $payment_account_id, 'credit');
                create_single_expense_entry($pdo, $school_id, $pay_date, "PAYE for {$emp['first_name']}", $deductions['paye'], $paye_liability, 'credit');
                create_single_expense_entry($pdo, $school_id, $pay_date, "NHIF for {$emp['first_name']}", $deductions['nhif'], $nhif_liability, 'credit');
                create_single_expense_entry($pdo, $school_id, $pay_date, "NSSF for {$emp['first_name']}", $deductions['nssf'], $nssf_liability, 'credit');
                create_single_expense_entry($pdo, $school_id, $pay_date, "Housing Levy for {$emp['first_name']}", $deductions['housing_levy'], $levy_liability, 'credit');
            }
            $success_message = "Monthly payroll processed successfully and general ledger updated!";
        
        } 
        // --- ACTION: Run Weekly Payroll for Casuals ---
        elseif ($action === 'run_weekly_payroll') {
            $week_ending_date = $_POST['week_ending_date'];
            $payment_account_id = (int)$_POST['payment_account_id'];
            $days_worked = $_POST['days_worked'] ?? [];
            $pay_period = date('Y-m', strtotime($week_ending_date));
            $total_casual_payout = 0;

            if (empty($payment_account_id)) {
                throw new Exception("You must select an account to pay casuals from.");
            }

            $daily_employees_stmt = $pdo->prepare("SELECT * FROM employees WHERE school_id = ? AND status = 'active' AND employment_type = 'daily'");
            $daily_employees_stmt->execute([$school_id]);
            $daily_employees = $daily_employees_stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($daily_employees as $emp) {
                if (isset($days_worked[$emp['id']]) && (int)$days_worked[$emp['id']] > 0) {
                    $days = (int)$days_worked[$emp['id']];
                    $rate = (float)($emp['daily_rate'] ?? 0);
                    $gross_pay = $days * $rate;
                    $total_casual_payout += $gross_pay;
                    // Create payroll record for history
                    $stmt = $pdo->prepare("INSERT INTO payroll (school_id, employee_id, employee_name, employee_type, pay_period, pay_date, gross_pay, net_pay, status, notes) VALUES (?, ?, ?, 'daily', ?, ?, ?, ?, 'Paid', ?)");
                    $stmt->execute([$school_id, $emp['id'], $emp['first_name'].' '.$emp['last_name'], $pay_period, $week_ending_date, $gross_pay, $gross_pay, "Weekly payment for $days days @ $$rate/day."]);
                }
            }

            if ($total_casual_payout > 0) {
                $salary_expense_account_id = getOrCreateAccount($pdo, $school_id, 'Salaries & Wages', 'expense', '6010');
                $description = "Weekly casual wages payment for week ending " . $week_ending_date;
                // Create the balanced journal entry
                create_journal_entry($pdo, $school_id, $week_ending_date, $description, $total_casual_payout, $salary_expense_account_id, $payment_account_id);
            }
            $success_message = "Weekly payroll processed successfully!";
        }

        $pdo->commit();
        header("Location: payroll.php?success_msg=" . urlencode($success_message));
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() == 23000) {
             $error_message = "Error: The Employee ID '" . htmlspecialchars($_POST['employee_id'] ?? '') . "' is already in use. Please choose a unique Employee ID.";
        } else {
            $error_message = "A database error occurred: " . $e->getMessage();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_message = "An error occurred: " . $e->getMessage();
    }
}

// ===================================================================================
// --- DATA FETCHING for Page Load ---
// ===================================================================================

$all_employees = getEmployees($pdo, $school_id);
$daily_employees_ui = array_filter($all_employees, fn($e) => $e['employment_type'] === 'daily');
$payroll_history = getPayrollRecords($pdo, $school_id);
$asset_accounts = getAccountsByType($pdo, $school_id, 'asset');
?>

<!-- =================================================================================== -->
<!-- --- HTML & UI STRUCTURE --- -->
<!-- =================================================================================== -->
<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-users"></i> Employees & Payroll</h1>
        <p>Manage employee records and process all payroll runs.</p>
    </div>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>
<?php if (isset($_GET['success_msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success_msg']) ?></div>
<?php endif; ?>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'employee_list')"><i class="fas fa-list-ul"></i> Employee List</button>
        <button class="tab-link" onclick="openTab(event, 'add_employee')"><i class="fas fa-user-plus"></i> Add/Edit Employee</button>
        <button class="tab-link" onclick="openTab(event, 'monthly_payroll')"><i class="fas fa-calendar-alt"></i> Monthly Payroll</button>
        <button class="tab-link" onclick="openTab(event, 'weekly_payroll')"><i class="fas fa-calendar-day"></i> Weekly Casuals</button>
        <button class="tab-link" onclick="openTab(event, 'history')"><i class="fas fa-history"></i> Payroll History</button>
    </div>

    <!-- ======================= Employee List Tab ======================= -->
    <div id="employee_list" class="tab-content active">
        <div class="card">
            <h2>All Employees</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Position</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_employees as $emp): ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                            <td><?= htmlspecialchars($emp['employee_id']) ?></td>
                            <td><?= htmlspecialchars($emp['position']) ?></td>
                            <td><span class="badge badge-<?= strtolower($emp['employment_type']) ?>"><?= htmlspecialchars(ucfirst($emp['employment_type'])) ?></span></td>
                            <td><span class="badge badge-<?= $emp['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($emp['status']) ?></span></td>
                            <td>
                                <button class="btn-icon" title="Edit" onclick='editEmployee(<?= json_encode($emp) ?>)'><i class="fas fa-edit"></i></button>
                                <form action="payroll.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this employee?');">
                                    <input type="hidden" name="action" value="delete_employee">
                                    <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                    <button type="submit" class="btn-icon btn-danger" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================= Add/Edit Employee Tab ======================= -->
    <div id="add_employee" class="tab-content">
        <div class="card">
            <h2 id="employee-form-title">Add New Employee</h2>
            <form id="employee-form" action="payroll.php" method="POST">
                <input type="hidden" name="action" id="employee_action" value="add_employee">
                <input type="hidden" name="id" id="employee_id_hidden">
                
                <div class="form-grid">
                    <div class="form-group"><label>First Name*</label><input type="text" name="first_name" id="first_name" required></div>
                    <div class="form-group"><label>Last Name*</label><input type="text" name="last_name" id="last_name" required></div>
                    <div class="form-group"><label>Employee ID*</label><input type="text" name="employee_id" id="employee_id" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" id="email"></div>
                    <div class="form-group"><label>Phone</label><input type="tel" name="phone" id="phone"></div>
                    <div class="form-group"><label>Hire Date*</label><input type="date" name="hire_date" id="hire_date" required></div>
                    <div class="form-group"><label>Department</label><input type="text" name="department" id="department"></div>
                    <div class="form-group"><label>Position*</label><input type="text" name="position" id="position" required></div>
                    <div class="form-group"><label>Employment Type*</label>
                        <select name="employment_type" id="employment_type" onchange="toggleSalaryFields()" required>
                            <option value="monthly">Monthly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                     <div class="form-group"><label>Status</label>
                        <select name="status" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div id="monthly_fields">
                     <fieldset>
                        <legend>Monthly Salary</legend>
                        <div class="form-grid">
                           <div class="form-group"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary" id="basic_salary"></div>
                           <div class="form-group"><label>House Allowance</label><input type="number" step="0.01" name="house_allowance" id="house_allowance"></div>
                           <div class="form-group"><label>Transport Allowance</label><input type="number" step="0.01" name="transport_allowance" id="transport_allowance"></div>
                        </div>
                    </fieldset>
                </div>
                <div id="daily_fields" style="display:none;">
                     <fieldset>
                        <legend>Daily Rate</legend>
                        <div class="form-grid">
                           <div class="form-group"><label>Daily Rate</label><input type="number" step="0.01" name="daily_rate" id="daily_rate"></div>
                        </div>
                    </fieldset>
                </div>
                
                <fieldset>
                    <legend>Statutory Information</legend>
                    <div class="form-grid">
                        <div class="form-group"><label>KRA PIN</label><input type="text" name="kra_pin" id="kra_pin"></div>
                        <div class="form-group"><label>NHIF Number</label><input type="text" name="nhif_number" id="nhif_number"></div>
                        <div class="form-group"><label>NSSF Number</label><input type="text" name="nssf_number" id="nssf_number"></div>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" id="employee-form-submit" class="btn-success"><i class="fas fa-plus"></i> Add Employee</button>
                    <button type="button" class="btn-secondary" onclick="resetEmployeeForm()">Clear Form</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ======================= Monthly Payroll Tab ======================= -->
    <div id="monthly_payroll" class="tab-content">
        <div class="card">
            <h2>Run Monthly Payroll for Salaried Staff</h2>
            <p>This will generate payslips and create balanced journal entries for all active 'monthly' employees.</p>
            <form action="payroll.php" method="POST" onsubmit="return confirm('Are you sure you want to run payroll for the selected month? This action will create financial records and cannot be undone.');">
                <input type="hidden" name="action" value="run_monthly_payroll">
                <div class="form-group">
                    <label for="pay_period">Select Pay Month</label>
                    <input type="month" id="pay_period" name="pay_period" class="form-control" value="<?= date('Y-m') ?>" required>
                </div>
                 <div class="form-group">
                    <label for="payroll_payment_account_id">Pay From Bank Account</label>
                    <select name="payroll_payment_account_id" id="payroll_payment_account_id" class="form-control" required>
                        <option value="">-- Select Bank Account --</option>
                        <?php foreach ($asset_accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-success"><i class="fas fa-cogs"></i> Run Monthly Payroll</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================= Weekly Casuals Tab ======================= -->
    <div id="weekly_payroll" class="tab-content">
        <div class="card">
            <h2>Process Weekly Wages for Casuals</h2>
            <p>This will create a single batch payment from your chosen account for all casuals listed below. The transaction will be recorded in your general ledger.</p>
            <form action="payroll.php" method="POST" onsubmit="return confirm('Are you sure you want to process this weekly payment? This will create a financial transaction.');">
                <input type="hidden" name="action" value="run_weekly_payroll">
                 <div class="form-group">
                    <label for="week_ending_date">Week Ending Date</label>
                    <input type="date" name="week_ending_date" id="week_ending_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="payment_account_id">Pay From Account (e.g., Petty Cash)</label>
                    <select name="payment_account_id" class="form-control" required>
                        <option value="">-- Select Payment Account --</option>
                        <?php foreach ($asset_accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> (Balance: $<?= number_format($acc['balance'], 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="table-container">
                     <table>
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th class="amount-header">Daily Rate</th>
                                <th style="width: 150px;" class="amount-header">Days Worked</th>
                                <th class="amount-header">Total Pay</th>
                            </tr>
                        </thead>
                        <tbody id="casuals-table-body">
                            <?php foreach ($daily_employees_ui as $emp): ?>
                            <tr class="casual-row">
                                <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                <td class="amount" data-rate="<?= (float)($emp['daily_rate'] ?? 0) ?>">$<?= number_format((float)($emp['daily_rate'] ?? 0), 2) ?></td>
                                <td><input type="number" name="days_worked[<?= $emp['id'] ?>]" class="form-control days-worked-input" min="0" max="7" value="0" step="1"></td>
                                <td class="amount row-total">$0.00</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row"><td colspan="3" style="text-align:right; font-weight: bold;">Total Weekly Payout:</td><td id="grand-total" class="amount" style="font-weight: bold;">$0.00</td></tr>
                        </tfoot>
                    </table>
                </div>
                 <div class="form-actions">
                    <button type="submit" class="btn-success"><i class="fas fa-hand-holding-usd"></i> Process Weekly Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================= Payroll History Tab ======================= -->
    <div id="history" class="tab-content">
        <div class="card">
            <h2>Payroll History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Pay Date</th><th>Employee</th><th>Type</th><th>Period</th>
                            <th class="amount-header">Gross Pay</th><th class="amount-header">Deductions</th><th class="amount-header">Net Pay</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payroll_history as $record): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($record['pay_date'])) ?></td>
                            <td><?= htmlspecialchars($record['employee_name']) ?></td>
                            <td><span class="badge badge-<?= strtolower($record['employee_type'] ?? 'monthly') ?>"><?= htmlspecialchars(ucfirst($record['employee_type'] ?? 'Monthly')) ?></span></td>
                            <td><?= htmlspecialchars($record['pay_period']) ?></td>
                            <td class="amount">$<?= number_format($record['gross_pay'], 2) ?></td>
                            <td class="amount">$<?= number_format($record['total_deductions'], 2) ?></td>
                            <td class="amount"><strong>$<?= number_format($record['net_pay'], 2) ?></strong></td>
                            <td><span class="badge badge-<?= strtolower($record['status']) ?>"><?= htmlspecialchars($record['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================================== -->
<!-- --- JAVASCRIPT & STYLES --- -->
<!-- =================================================================================== -->
<style>
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
    fieldset { border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    legend { font-weight: bold; color: var(--primary); padding: 0 10px; }
    .amount-header, .amount { text-align: right; font-family: 'Courier New', Courier, monospace; }
    .total-row { background-color: #f8f9fa; font-size: 1.1em; }
    .badge.badge-daily { background-color: #ffc107; color: #212529; }
    .badge.badge-monthly { background-color: #17a2b8; color: white; }
    .badge.badge-processed, .badge.badge-paid { background-color: #28a745; color: white; }
    .badge.badge-active { background-color: #28a745; color: white; }
    .badge.badge-inactive { background-color: #6c757d; color: white; }
</style>

<script>
    function openTab(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
        document.querySelectorAll(".tab-link").forEach(link => link.classList.remove("active"));
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.classList.add("active");
    }

    function toggleSalaryFields() {
        const type = document.getElementById('employment_type').value;
        document.getElementById('monthly_fields').style.display = (type === 'monthly') ? 'block' : 'none';
        document.getElementById('daily_fields').style.display = (type === 'daily') ? 'block' : 'none';
    }

    function calculateCasualsPay() {
        let grandTotal = 0;
        document.querySelectorAll('.casual-row').forEach(row => {
            const rate = parseFloat(row.querySelector('[data-rate]').dataset.rate);
            const days = parseFloat(row.querySelector('.days-worked-input').value) || 0;
            const rowTotal = rate * days;
            row.querySelector('.row-total').textContent = '$' + rowTotal.toFixed(2);
            grandTotal += rowTotal;
        });
        document.getElementById('grand-total').textContent = '$' + grandTotal.toFixed(2);
    }

    function resetEmployeeForm() {
        document.getElementById('employee-form').reset();
        document.getElementById('employee-form-title').textContent = 'Add New Employee';
        document.getElementById('employee_action').value = 'add_employee';
        document.getElementById('employee_id_hidden').value = '';
        document.getElementById('employee-form-submit').innerHTML = '<i class="fas fa-plus"></i> Add Employee';
        document.getElementById('employee-form-submit').classList.remove('btn-warning');
        document.getElementById('employee-form-submit').classList.add('btn-success');
        toggleSalaryFields();
    }

    function editEmployee(employee) {
        // Switch to the Add/Edit tab
        document.querySelector('button[onclick*="add_employee"]').click();

        // Populate the form
        document.getElementById('employee-form-title').textContent = 'Edit Employee: ' + employee.first_name + ' ' + employee.last_name;
        document.getElementById('employee_action').value = 'update_employee';
        document.getElementById('employee_id_hidden').value = employee.id;
        
        document.getElementById('first_name').value = employee.first_name;
        document.getElementById('last_name').value = employee.last_name;
        document.getElementById('employee_id').value = employee.employee_id;
        document.getElementById('email').value = employee.email;
        document.getElementById('phone').value = employee.phone;
        document.getElementById('hire_date').value = employee.hire_date;
        document.getElementById('department').value = employee.department;
        document.getElementById('position').value = employee.position;
        document.getElementById('employment_type').value = employee.employment_type;
        document.getElementById('status').value = employee.status;
        
        document.getElementById('basic_salary').value = employee.basic_salary;
        document.getElementById('house_allowance').value = employee.house_allowance;
        document.getElementById('transport_allowance').value = employee.transport_allowance;
        document.getElementById('daily_rate').value = employee.daily_rate;

        document.getElementById('kra_pin').value = employee.kra_pin;
        document.getElementById('nhif_number').value = employee.nhif_number;
        document.getElementById('nssf_number').value = employee.nssf_number;

        // Change button text and color
        const submitBtn = document.getElementById('employee-form-submit');
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Employee';
        submitBtn.classList.remove('btn-success');
        submitBtn.classList.add('btn-warning');

        toggleSalaryFields();
        window.scrollTo(0, 0); // Scroll to top
    }

    document.addEventListener('DOMContentLoaded', () => {
        const firstTab = document.querySelector('.tab-container .tab-link');
        if (firstTab) { firstTab.click(); }

        const casualsTable = document.getElementById('casuals-table-body');
        if (casualsTable) {
            casualsTable.addEventListener('input', (event) => {
                if (event.target.classList.contains('days-worked-input')) {
                    calculateCasualsPay();
                }
            });
        }
    });
</script>
