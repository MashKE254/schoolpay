<?php
/**
 * payroll.php - v2.2 - Professional, Unified Employee & Payroll System
 *
 * This script is a complete hub for all employee-related tasks, combining and enhancing
 * the functionality of the previous employees.php and payroll.php files.
 *
 * All form processing logic is now handled at the top of the script BEFORE any HTML output
 * to prevent "Headers already sent" errors.
 *
 * Features:
 * - A tabbed interface for a comprehensive Dashboard, Employee Management, Payroll Runs, History, and Settings.
 * - Full CRUD (Create, Read, Update, Deactivate) functionality for employee records with detailed profiles.
 * - Management of custom, recurring allowances and deductions for each employee.
 * - A settings panel to manage both custom payroll items and update the rates/brackets for statutory deductions.
 * - Automated calculation of Kenyan statutory deductions (PAYE, NHIF, NSSF, Housing Levy) based on configurable settings.
 * - Separate processing for monthly salaried staff and weekly-paid daily staff.
 * - Correct, balanced, double-entry accounting integration with the general ledger for all payroll transactions.
 * - On-the-fly generation and viewing of detailed, professional payslips for each payroll record.
 */

require_once 'config.php';
require_once 'functions.php';

// --- IMPORTANT: SESSION START & ALL POST LOGIC MUST BE BEFORE HEADER.PHP ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['school_id'])) {
    header("Location: login.php");
    exit();
}
$school_id = $_SESSION['school_id'];


// ===================================================================================
// --- POST REQUEST HANDLING (SERVER-SIDE LOGIC) ---
// ===================================================================================

$error_message = '';
$success_message = '';
$active_tab_on_post = 'employees'; // Default tab to return to after action

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
            $daily_rate = !empty($_POST['daily_rate']) ? floatval($_POST['daily_rate']) : 0;
            $kra_pin = trim($_POST['kra_pin']);
            $nhif_number = trim($_POST['nhif_number']);
            $nssf_number = trim($_POST['nssf_number']);
            $bank_name = trim($_POST['bank_name']);
            $bank_branch = trim($_POST['bank_branch']);
            $bank_account_number = trim($_POST['bank_account_number']);
            $notes = trim($_POST['notes']);
            $status = $_POST['status'] ?? 'active';

            if ($action === 'add_employee') {
                $stmt = $pdo->prepare(
                    "INSERT INTO employees (school_id, employee_id, first_name, last_name, email, phone, department, position, employment_type, hire_date, basic_salary, daily_rate, kra_pin, nhif_number, nssf_number, bank_name, bank_branch, bank_account_number, notes, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$school_id, $employee_id_post, $first_name, $last_name, $email, $phone, $department, $position, $employment_type, $hire_date, $basic_salary, $daily_rate, $kra_pin, $nhif_number, $nssf_number, $bank_name, $bank_branch, $bank_account_number, $notes, $status]);
                $success_message = "Employee added successfully!";
            } else { // update_employee
                $id_to_update = (int)$_POST['id'];
                $stmt = $pdo->prepare(
                    "UPDATE employees SET employee_id=?, first_name=?, last_name=?, email=?, phone=?, department=?, position=?, employment_type=?, hire_date=?, basic_salary=?, daily_rate=?, kra_pin=?, nhif_number=?, nssf_number=?, bank_name=?, bank_branch=?, bank_account_number=?, notes=?, status=?
                     WHERE id = ? AND school_id = ?"
                );
                $stmt->execute([$employee_id_post, $first_name, $last_name, $email, $phone, $department, $position, $employment_type, $hire_date, $basic_salary, $daily_rate, $kra_pin, $nhif_number, $nssf_number, $bank_name, $bank_branch, $bank_account_number, $notes, $status, $id_to_update, $school_id]);
                $success_message = "Employee updated successfully!";
            }
        }
        // --- ACTION: Deactivate Employee (Soft Delete) ---
        elseif ($action === 'deactivate_employee') {
            $id_to_delete = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE employees SET status = 'inactive' WHERE id = ? AND school_id = ?");
            $stmt->execute([$id_to_delete, $school_id]);
            $success_message = "Employee deactivated successfully.";
        }
        // --- ACTION: Add/Update recurring payroll item for an employee ---
        elseif ($action === 'save_employee_meta') {
            $employee_id_meta = (int)$_POST['employee_id'];
            $meta_id = (int)$_POST['meta_id']; // This is employee_payroll_meta id for updates
            $payroll_meta_id = (int)$_POST['payroll_meta_id'];
            $amount = floatval($_POST['amount']);

            if ($meta_id > 0) { // Update existing
                 $stmt = $pdo->prepare("UPDATE employee_payroll_meta SET payroll_meta_id=?, amount=? WHERE id=? AND employee_id=? AND school_id=?");
                 $stmt->execute([$payroll_meta_id, $amount, $meta_id, $employee_id_meta, $school_id]);
                 $success_message = "Payroll item updated.";
            } else { // Insert new
                $stmt = $pdo->prepare("INSERT INTO employee_payroll_meta (school_id, employee_id, payroll_meta_id, amount) VALUES (?,?,?,?)");
                $stmt->execute([$school_id, $employee_id_meta, $payroll_meta_id, $amount]);
                $success_message = "Payroll item added to employee.";
            }
             $active_tab_on_post = 'employees&view_id=' . $employee_id_meta; // Redirect back to the employee profile
        }
        // --- ACTION: Delete a recurring payroll item from an employee ---
        elseif ($action === 'delete_employee_meta') {
            $meta_id = (int)$_POST['meta_id'];
            $employee_id_meta = (int)$_POST['employee_id'];
            $stmt = $pdo->prepare("DELETE FROM employee_payroll_meta WHERE id=? AND school_id=?");
            $stmt->execute([$meta_id, $school_id]);
            $success_message = "Payroll item removed.";
            $active_tab_on_post = 'employees&view_id=' . $employee_id_meta;
        }

        // --- ACTION: Run Monthly Payroll ---
        elseif ($action === 'run_monthly_payroll') {
            $active_tab_on_post = 'run_payroll';
            $pay_period = $_POST['pay_period'];
            $pay_date = date('Y-m-t', strtotime($pay_period . '-01'));
            $payment_account_id = (int)$_POST['payroll_payment_account_id'];
            $one_off_earnings = $_POST['one_off_earning'] ?? [];
            $one_off_deductions = $_POST['one_off_deduction'] ?? [];

            if(empty($payment_account_id)) {
                throw new Exception("You must select a bank account to pay salaries from.");
            }

            // Get or Create necessary accounts for payroll liabilities and expenses
            $expense_account = getOrCreateAccount($pdo, $school_id, 'Salaries & Wages', 'expense', '6010');
            $paye_liability = getOrCreateAccount($pdo, $school_id, 'PAYE Payable', 'liability', '2100');
            $nhif_liability = getOrCreateAccount($pdo, $school_id, 'NHIF Payable', 'liability', '2110');
            $nssf_liability = getOrCreateAccount($pdo, $school_id, 'NSSF Payable', 'liability', '2120');
            $levy_liability = getOrCreateAccount($pdo, $school_id, 'Housing Levy Payable', 'liability', '2130');

            // Fetch employees to be paid
            $employees_stmt = $pdo->prepare("SELECT * FROM employees WHERE school_id = ? AND status = 'active' AND employment_type = 'monthly'");
            $employees_stmt->execute([$school_id]);
            $employees_to_pay = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($employees_to_pay)) {
                throw new Exception("No active monthly employees found to process payroll.");
            }
            
            // Fetch all defined payroll items (allowances/deductions)
            $payroll_meta_stmt = $pdo->prepare("SELECT * FROM payroll_meta WHERE school_id=?");
            $payroll_meta_stmt->execute([$school_id]);
            $all_payroll_metas_raw = $payroll_meta_stmt->fetchAll(PDO::FETCH_ASSOC);
            $all_payroll_metas = [];
            foreach($all_payroll_metas_raw as $meta) {
                $all_payroll_metas[$meta['id']] = $meta;
            }


            // Fetch recurring items for all employees at once
            $employee_ids = array_column($employees_to_pay, 'id');
            if (!empty($employee_ids)) {
                $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
                $recurring_meta_stmt = $pdo->prepare("SELECT * FROM employee_payroll_meta WHERE school_id=? AND employee_id IN ($placeholders)");
                $recurring_meta_stmt->execute(array_merge([$school_id], $employee_ids));
                
                $employee_recurring_items = [];
                while ($row = $recurring_meta_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $employee_recurring_items[$row['employee_id']][] = $row;
                }
            } else {
                 $employee_recurring_items = [];
            }


            foreach($employees_to_pay as $emp) {
                $payslip_data = [
                    'earnings' => [],
                    'deductions' => [],
                    'summary' => []
                ];
                
                $payslip_data['earnings'][] = ['name' => 'Basic Salary', 'amount' => (float)$emp['basic_salary']];
                
                $total_earnings = (float)$emp['basic_salary'];
                $total_deductions = 0;

                // 1. Add Recurring Items
                if (isset($employee_recurring_items[$emp['id']])) {
                    foreach ($employee_recurring_items[$emp['id']] as $item) {
                        $meta_info = $all_payroll_metas[$item['payroll_meta_id']];
                        if ($meta_info['type'] === 'Earning') {
                            $payslip_data['earnings'][] = ['name' => $meta_info['name'], 'amount' => (float)$item['amount']];
                            $total_earnings += (float)$item['amount'];
                        } else { // Deduction
                            $payslip_data['deductions'][] = ['name' => $meta_info['name'], 'amount' => (float)$item['amount']];
                            $total_deductions += (float)$item['amount'];
                        }
                    }
                }
                
                // 2. Add One-off Items
                if(isset($one_off_earnings[$emp['id']]) && floatval($one_off_earnings[$emp['id']]) > 0){
                    $one_off_amount = floatval($one_off_earnings[$emp['id']]);
                    $payslip_data['earnings'][] = ['name' => 'Bonus/Other Earning', 'amount' => $one_off_amount];
                    $total_earnings += $one_off_amount;
                }
                if(isset($one_off_deductions[$emp['id']]) && floatval($one_off_deductions[$emp['id']]) > 0){
                    $one_off_amount = floatval($one_off_deductions[$emp['id']]);
                    $payslip_data['deductions'][] = ['name' => 'Advance/Other Deduction', 'amount' => $one_off_amount];
                    $total_deductions += $one_off_amount;
                }

                if ($total_earnings <= 0) continue; // Skip if no earnings

                // 3. Calculate Statutory Deductions
                $statutory = calculate_kenyan_deductions($pdo, $school_id, $total_earnings);
                $total_deductions += $statutory['total_deductions'];
                $net_pay = $total_earnings - $total_deductions;

                // 4. Populate payslip data for storage
                $payslip_data['deductions'][] = ['name' => 'PAYE', 'amount' => $statutory['paye']];
                $payslip_data['deductions'][] = ['name' => 'NHIF', 'amount' => $statutory['nhif']];
                $payslip_data['deductions'][] = ['name' => 'NSSF', 'amount' => $statutory['nssf']];
                $payslip_data['deductions'][] = ['name' => 'Housing Levy', 'amount' => $statutory['housing_levy']];
                $payslip_data['summary'] = [
                    'total_earnings' => $total_earnings,
                    'total_deductions' => $total_deductions,
                    'net_pay' => $net_pay,
                    'pay_period' => date('F Y', strtotime($pay_period)),
                    'pay_date' => $pay_date
                ];

                // 5. Insert into payroll history table
                $stmt_payroll = $pdo->prepare(
                    "INSERT INTO payroll (school_id, employee_id, employee_name, pay_period, pay_date, gross_pay, paye, nhif, nssf, housing_levy, total_deductions, net_pay, payslip_data, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Processed')"
                );
                $stmt_payroll->execute([$school_id, $emp['id'], $emp['first_name'].' '.$emp['last_name'], $pay_period, $pay_date, $total_earnings, $statutory['paye'], $statutory['nhif'], $statutory['nssf'], $statutory['housing_levy'], $total_deductions, $net_pay, json_encode($payslip_data)]);

                // 6. Create the balanced journal entry
                $description = "Monthly salary for {$emp['first_name']} {$emp['last_name']} ({$pay_period})";
                create_single_expense_entry($pdo, $school_id, $pay_date, $description, $total_earnings, $expense_account, 'debit');
                create_single_expense_entry($pdo, $school_id, $pay_date, "Net pay to {$emp['first_name']}", $net_pay, $payment_account_id, 'credit');
                if ($statutory['paye'] > 0) create_single_expense_entry($pdo, $school_id, $pay_date, "PAYE for {$emp['first_name']}", $statutory['paye'], $paye_liability, 'credit');
                if ($statutory['nhif'] > 0) create_single_expense_entry($pdo, $school_id, $pay_date, "NHIF for {$emp['first_name']}", $statutory['nhif'], $nhif_liability, 'credit');
                if ($statutory['nssf'] > 0) create_single_expense_entry($pdo, $school_id, $pay_date, "NSSF for {$emp['first_name']}", $statutory['nssf'], $nssf_liability, 'credit');
                if ($statutory['housing_levy'] > 0) create_single_expense_entry($pdo, $school_id, $pay_date, "Housing Levy for {$emp['first_name']}", $statutory['housing_levy'], $levy_liability, 'credit');
            }
            $success_message = "Monthly payroll processed successfully and general ledger updated!";
        }
        // --- ACTION: Run Weekly Payroll for Casuals ---
        elseif ($action === 'run_weekly_payroll') {
            $active_tab_on_post = 'run_payroll';
            $week_ending_date = $_POST['week_ending_date'];
            $payment_account_id = (int)$_POST['payment_account_id'];
            $days_worked = $_POST['days_worked'] ?? [];
            $pay_period = date('Y-m', strtotime($week_ending_date));
            $total_casual_payout = 0;

            if (empty($payment_account_id)) {
                throw new Exception("You must select an account to pay casuals from.");
            }
             $expense_account = getOrCreateAccount($pdo, $school_id, 'Salaries & Wages', 'expense', '6010');

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
                    $stmt = $pdo->prepare("INSERT INTO payroll (school_id, employee_id, employee_name, pay_period, pay_date, gross_pay, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Processed')");
                    $stmt->execute([$school_id, $emp['id'], $emp['first_name'].' '.$emp['last_name'], $pay_period, $week_ending_date, $gross_pay, $gross_pay]);
                }
            }

            if ($total_casual_payout > 0) {
                $description = "Weekly casual wages payment for week ending " . $week_ending_date;
                create_journal_entry($pdo, $school_id, $week_ending_date, $description, $total_casual_payout, $expense_account, $payment_account_id);
            }
            $success_message = "Weekly payroll processed successfully!";
        }
        
        $pdo->commit();
        $redirect_params = is_string($active_tab_on_post) ? $active_tab_on_post : 'employees';
        header("Location: payroll.php?tab=" . $redirect_params . "&success_msg=" . urlencode($success_message));
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

// --- Now we can safely include the header and start generating the page ---
require_once 'header.php';

// ===================================================================================
// --- DATA FETCHING for Page Load ---
// ===================================================================================

$all_employees = getEmployees($pdo, $school_id);
$payroll_history = getPayrollRecords($pdo, $school_id);
$asset_accounts = getAccountsByType($pdo, $school_id, 'asset');

// Data for Payroll Run tab
$monthly_employees_for_payroll = array_filter($all_employees, fn($e) => $e['employment_type'] === 'monthly' && $e['status'] === 'active');
$daily_employees_for_payroll = array_filter($all_employees, fn($e) => $e['employment_type'] === 'daily' && $e['status'] === 'active');

// Data for Employee Profile View
$view_employee = null;
$employee_payroll_items = [];
if (isset($_GET['view_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND school_id = ?");
    $stmt->execute([$_GET['view_id'], $school_id]);
    $view_employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if($view_employee) {
        $stmt_items = $pdo->prepare(
            "SELECT epm.*, pm.name, pm.type 
             FROM employee_payroll_meta epm 
             JOIN payroll_meta pm ON epm.payroll_meta_id = pm.id
             WHERE epm.employee_id = ? AND epm.school_id = ?"
        );
        $stmt_items->execute([$view_employee['id'], $school_id]);
        $employee_payroll_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }
}

$payroll_meta_options_stmt = $pdo->prepare("SELECT * FROM payroll_meta WHERE school_id = ? AND is_system = 0 ORDER BY type, name");
$payroll_meta_options_stmt->execute([$school_id]);
$payroll_meta_options = $payroll_meta_options_stmt->fetchAll(PDO::FETCH_ASSOC);

// *** NEW: Data for Dashboard ***
$dashboard_metrics = getPayrollDashboardMetrics($pdo, $school_id);

?>

<style>
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
    fieldset { border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    legend { font-weight: bold; color: var(--primary); padding: 0 10px; }
    .amount-header, .amount { text-align: right; font-family: 'Courier New', Courier, monospace; }
    .badge.badge-daily { background-color: #ffc107; color: #212529; }
    .badge.badge-monthly { background-color: #17a2b8; color: white; }
    .badge.badge-processed, .badge.badge-paid { background-color: #28a745; color: white; }
    .badge.badge-active, .badge.badge-success { background-color: #28a745; color: white; }
    .badge.badge-inactive, .badge.badge-secondary { background-color: #6c757d; color: white; }
    .badge.badge-info { background-color: #17a2b8; color: white; }
    .badge.badge-warning { background-color: #ffc107; color: #212529; }
    .total-row { background-color: #f8f9fa; font-size: 1.1em; }

    /* MODAL SCROLLING FIX */
    .modal .modal-content.large .modal-body {
        max-height: 65vh; /* Adjust this value as needed */
        overflow-y: auto;
        padding-right: 15px; /* Add some padding for the scrollbar */
    }
    .modal-footer {
        border-top: 1px solid #e5e5e5;
        padding-top: 15px;
        margin-top: 15px;
    }
    /* END MODAL SCROLLING FIX */


    /* Payslip Modal Styles */
    #payslip-printable { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
    .payslip-header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
    .payslip-header h3 { margin: 0; color: var(--primary); }
    .payslip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .payslip-details p { margin: 4px 0; font-size: 0.9em; }
    .payslip-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
    .payslip-table th, .payslip-table td { padding: 8px; border-bottom: 1px solid #eee; }
    .payslip-table th { background-color: #f8f9fa; text-align: left; }
    .payslip-summary { margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; }
    .payslip-summary table { width: 50%; float: right; }

    /* Dashboard Cards */
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .dashboard-card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; display: flex; align-items: center; gap: 15px; }
    .dashboard-card .icon { font-size: 2rem; color: var(--primary); background-color: #e3f2fd; padding: 15px; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; }
    .dashboard-card .info .value { font-size: 1.5rem; font-weight: 700; }
    .dashboard-card .info .label { font-size: 0.9rem; color: #6c757d; }

    /* Settings Tab Styles */
    #settings .form-grid { grid-template-columns: 1fr 1fr; }
    #statutorySettingsForm textarea { font-family: 'Courier New', Courier, monospace; font-size: 0.9em; }
    .list-styled { list-style: none; padding-left: 0; }
    .list-styled li { background: #f8f9fa; padding: 8px 12px; border-radius: 4px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; }
</style>

<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-users-cog"></i> Employee & Payroll Hub</h1>
        <p>A central module for managing employee records, payroll, and statutory compliance.</p>
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
        <button class="tab-link active" onclick="openTab(event, 'dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
        <button class="tab-link" onclick="openTab(event, 'employees')"><i class="fas fa-users"></i> Employee Management</button>
        <button class="tab-link" onclick="openTab(event, 'run_payroll')"><i class="fas fa-cogs"></i> Run Payroll</button>
        <button class="tab-link" onclick="openTab(event, 'history')"><i class="fas fa-history"></i> Payroll History</button>
        <button class="tab-link" onclick="openTab(event, 'settings')"><i class="fas fa-sliders-h"></i> Settings</button>
    </div>

    <div id="dashboard" class="tab-content active">
        <div class="card">
            <h3>Payroll At a Glance (<?= date('F Y') ?>)</h3>
             <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="info">
                        <div class="value"><?= $dashboard_metrics['active_employees'] ?></div>
                        <div class="label">Active Employees</div>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="info">
                        <div class="value"><?= format_currency($dashboard_metrics['total_paid_this_month']) ?></div>
                        <div class="label">Total Net Pay This Month</div>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-percent"></i></div>
                    <div class="info">
                        <div class="value"><?= format_currency($dashboard_metrics['total_deductions_this_month']) ?></div>
                        <div class="label">Statutory Deductions This Month</div>
                    </div>
                </div>
                 <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="info">
                        <div class="value"><?= $dashboard_metrics['payrolls_run_this_month'] ?></div>
                        <div class="label">Payrolls Processed This Month</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="employees" class="tab-content">
        <?php if ($view_employee): ?>
            <div class="card">
                 <div class="page-header-title" style="margin-bottom: 20px;">
                    <a href="payroll.php?tab=employees" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Employee List</a>
                    <h2 style="margin-top: 15px;">Employee Profile: <?= htmlspecialchars($view_employee['first_name'] . ' ' . $view_employee['last_name']) ?></h2>
                </div>

                <fieldset>
                    <legend>Personal & Contact Information</legend>
                    <div class="form-grid">
                        <p><strong>Employee ID:</strong> <?= htmlspecialchars($view_employee['employee_id']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($view_employee['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($view_employee['phone']) ?></p>
                    </div>
                </fieldset>
                 <fieldset>
                    <legend>Employment Details</legend>
                    <div class="form-grid">
                        <p><strong>Department:</strong> <?= htmlspecialchars($view_employee['department']) ?></p>
                        <p><strong>Position:</strong> <?= htmlspecialchars($view_employee['position']) ?></p>
                        <p><strong>Hire Date:</strong> <?= htmlspecialchars($view_employee['hire_date']) ?></p>
                        <p><strong>Type:</strong> <?= ucfirst($view_employee['employment_type']) ?></p>
                        <p><strong>Status:</strong> <span class="badge badge-<?= $view_employee['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($view_employee['status']) ?></span></p>
                    </div>
                </fieldset>
                 <fieldset>
                    <legend>Financial & Statutory Information</legend>
                    <div class="form-grid">
                        <?php if ($view_employee['employment_type'] === 'monthly'): ?>
                        <p><strong>Basic Salary:</strong> <?= format_currency($view_employee['basic_salary']) ?></p>
                        <?php else: ?>
                        <p><strong>Daily Rate:</strong> <?= format_currency($view_employee['daily_rate']) ?></p>
                        <?php endif; ?>
                        <p><strong>KRA PIN:</strong> <?= htmlspecialchars($view_employee['kra_pin']) ?></p>
                        <p><strong>NHIF Number:</strong> <?= htmlspecialchars($view_employee['nhif_number']) ?></p>
                        <p><strong>NSSF Number:</strong> <?= htmlspecialchars($view_employee['nssf_number']) ?></p>
                        <p><strong>Bank:</strong> <?= htmlspecialchars($view_employee['bank_name']) ?> - <?= htmlspecialchars($view_employee['bank_branch']) ?></p>
                        <p><strong>Account #:</strong> <?= htmlspecialchars($view_employee['bank_account_number']) ?></p>
                    </div>
                </fieldset>
                
                <div class="card" style="margin-top: 2rem;">
                     <h4>Recurring Allowances & Deductions</h4>
                     <table class="table">
                        <thead><tr><th>Item</th><th>Type</th><th class="amount-header">Amount</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($employee_payroll_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><span class="badge badge-<?= $item['type'] === 'Earning' ? 'info' : 'warning' ?>"><?= $item['type'] ?></span></td>
                                <td class="amount"><?= format_currency($item['amount']) ?></td>
                                <td>
                                    <form action="payroll.php" method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_employee_meta">
                                        <input type="hidden" name="meta_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="employee_id" value="<?= $view_employee['id'] ?>">
                                        <button type="submit" class="btn-icon btn-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                     <form action="payroll.php" method="POST" style="margin-top:20px; border-top:1px solid #eee; padding-top:20px;">
                         <h5>Add New Item</h5>
                         <input type="hidden" name="action" value="save_employee_meta">
                         <input type="hidden" name="employee_id" value="<?= $view_employee['id'] ?>">
                         <input type="hidden" name="meta_id" value="0">
                         <div class="form-grid">
                            <div class="form-group">
                                <label>Item</label>
                                <select name="payroll_meta_id" class="form-control" required>
                                    <option value="">-- Select --</option>
                                    <optgroup label="Earnings">
                                        <?php foreach($payroll_meta_options as $opt) if($opt['type']==='Earning') echo "<option value='{$opt['id']}'>".htmlspecialchars($opt['name'])."</option>"; ?>
                                    </optgroup>
                                     <optgroup label="Deductions">
                                        <?php foreach($payroll_meta_options as $opt) if($opt['type']==='Deduction') echo "<option value='{$opt['id']}'>".htmlspecialchars($opt['name'])."</option>"; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (Monthly)</label>
                                <input type="number" name="amount" step="0.01" class="form-control" required>
                            </div>
                         </div>
                         <div class="form-actions">
                             <button type="submit" class="btn-success">Add Item</button>
                         </div>
                     </form>
                </div>

            </div>
        <?php else: ?>
            <div class="card">
                <h2>All Employees</h2>
                 <div class="table-actions">
                    <button type="button" class="btn-add" onclick="openModal('employeeFormModal')"><i class="fas fa-user-plus"></i> Add New Employee</button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th><th>Employee ID</th><th>Position</th><th>Type</th><th>Status</th><th>Actions</th>
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
                                    <a href="?tab=employees&view_id=<?= $emp['id'] ?>" class="btn-icon" title="View Profile"><i class="fas fa-eye"></i></a>
                                    <button class="btn-icon" title="Edit" onclick='editEmployee(<?= json_encode($emp) ?>)'><i class="fas fa-edit"></i></button>
                                    <?php if ($emp['status'] === 'active'): ?>
                                    <form action="payroll.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this employee?');">
                                        <input type="hidden" name="action" value="deactivate_employee">
                                        <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                        <button type="submit" class="btn-icon btn-danger" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="run_payroll" class="tab-content">
        <div class="card">
            <h3>Process Payroll</h3>
            <hr>
            <h4>Run Monthly Payroll (Salaried Staff)</h4>
            <p>This will generate payslips and create balanced journal entries for all active 'monthly' employees.</p>
            <form action="payroll.php" method="POST" onsubmit="return confirm('Are you sure you want to run payroll for the selected month? This action will create financial records and cannot be undone.');">
                <input type="hidden" name="action" value="run_monthly_payroll">
                <div class="form-grid">
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
                </div>
                
                <h5 style="margin-top: 2rem;">One-Off Earnings & Deductions (Optional)</h5>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th class="amount-header">One-Off Earning (Bonus, etc.)</th>
                                <th class="amount-header">One-Off Deduction (Advance, etc.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($monthly_employees_for_payroll as $emp): ?>
                            <tr>
                                <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                <td><input type="number" name="one_off_earning[<?= $emp['id'] ?>]" class="form-control amount" step="0.01" placeholder="0.00"></td>
                                <td><input type="number" name="one_off_deduction[<?= $emp['id'] ?>]" class="form-control amount" step="0.01" placeholder="0.00"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-success"><i class="fas fa-cogs"></i> Run Monthly Payroll</button>
                </div>
            </form>
            
            <hr style="margin: 3rem 0;">

            <h4>Process Weekly Wages (Daily-Rate Staff)</h4>
            <p>This will create a single batch payment from your chosen account for all casuals listed below.</p>
            <form action="payroll.php" method="POST" onsubmit="return confirm('Are you sure you want to process this weekly payment?');">
                <input type="hidden" name="action" value="run_weekly_payroll">
                 <div class="form-grid">
                    <div class="form-group">
                        <label for="week_ending_date">Week Ending Date</label>
                        <input type="date" name="week_ending_date" id="week_ending_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_account_id">Pay From Account (e.g., Petty Cash)</label>
                        <select name="payment_account_id" class="form-control" required>
                            <option value="">-- Select Payment Account --</option>
                            <?php foreach ($asset_accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> (Balance: <?= format_currency($acc['balance']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                            <?php foreach ($daily_employees_for_payroll as $emp): ?>
                            <tr class="casual-row">
                                <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                <td class="amount" data-rate="<?= (float)($emp['daily_rate'] ?? 0) ?>"><?= format_currency($emp['daily_rate'] ?? 0) ?></td>
                                <td><input type="number" name="days_worked[<?= $emp['id'] ?>]" class="form-control days-worked-input" min="0" max="7" value="0" step="1"></td>
                                <td class="amount row-total"><?= format_currency(0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row"><td colspan="3" style="text-align:right; font-weight: bold;">Total Weekly Payout:</td><td id="grand-total" class="amount" style="font-weight: bold;"><?= format_currency(0) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
                 <div class="form-actions">
                    <button type="submit" class="btn-success"><i class="fas fa-hand-holding-usd"></i> Process Weekly Payment</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="history" class="tab-content">
        <div class="card">
            <h2>Payroll History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Pay Date</th><th>Employee</th><th>Type</th><th>Period</th>
                            <th class="amount-header">Gross Pay</th><th class="amount-header">Deductions</th><th class="amount-header">Net Pay</th><th>Status</th><th>Payslip</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payroll_history as $record): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($record['pay_date'])) ?></td>
                            <td><?= htmlspecialchars($record['employee_name']) ?></td>
                            <td><span class="badge badge-<?= strtolower($record['employee_type'] ?? 'monthly') ?>"><?= htmlspecialchars(ucfirst($record['employee_type'] ?? 'monthly')) ?></span></td>
                            <td><?= htmlspecialchars($record['pay_period']) ?></td>
                            <td class="amount"><?= format_currency($record['gross_pay']) ?></td>
                            <td class="amount"><?= format_currency($record['total_deductions']) ?></td>
                            <td class="amount"><strong><?= format_currency($record['net_pay']) ?></strong></td>
                            <td><span class="badge badge-<?= strtolower($record['status']) ?>"><?= htmlspecialchars($record['status']) ?></span></td>
                            <td>
                                <?php if(($record['employee_type'] ?? 'monthly') === 'monthly' && !empty($record['payslip_data'])): ?>
                                    <button class="btn-icon" title="View Payslip" onclick='viewPayslip(<?= htmlspecialchars(json_encode($record), ENT_QUOTES) ?>)'>
                                        <i class="fas fa-receipt"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="settings" class="tab-content">
        <div class="card">
            <h2>Payroll Settings</h2>
            <div class="form-grid">
                <div id="payrollMetaContainer">
                    </div>
                <div id="statutorySettingsContainer">
                    <h4>Statutory Settings</h4>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Edit these values only when official government rates change. Incorrect values can lead to non-compliance.
                    </div>
                    <form id="statutorySettingsForm" onsubmit="saveStatutorySettings(event)">
                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" class="btn-success"><i class="fas fa-save"></i> Save Statutory Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="employeeFormModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="employee-form-title-in-modal">Add New Employee</h3>
            <span class="close" onclick="closeModal('employeeFormModal')">&times;</span>
        </div>
        <form id="employee-form" action="payroll.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="employee_action" value="add_employee">
                <input type="hidden" name="id" id="employee_id_hidden">
                
                <fieldset><legend>Personal Details</legend>
                    <div class="form-grid">
                        <div class="form-group"><label>First Name*</label><input type="text" name="first_name" id="first_name" class="form-control" required></div>
                        <div class="form-group"><label>Last Name*</label><input type="text" name="last_name" id="last_name" class="form-control" required></div>
                        <div class="form-group"><label>Employee ID*</label><input type="text" name="employee_id" id="employee_id" class="form-control" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" id="email" class="form-control"></div>
                        <div class="form-group"><label>Phone</label><input type="tel" name="phone" id="phone" class="form-control"></div>
                    </div>
                </fieldset>

                <fieldset><legend>Employment Details</legend>
                    <div class="form-grid">
                        <div class="form-group"><label>Hire Date*</label><input type="date" name="hire_date" id="hire_date" class="form-control" required></div>
                        <div class="form-group"><label>Department</label><input type="text" name="department" id="department" class="form-control"></div>
                        <div class="form-group"><label>Position*</label><input type="text" name="position" id="position" class="form-control" required></div>
                        <div class="form-group"><label>Employment Type*</label>
                            <select name="employment_type" id="employment_type" class="form-control" onchange="toggleSalaryFields()" required>
                                <option value="monthly">Monthly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                        <div class="form-group" id="monthly_fields"><label>Basic Salary*</label><input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control"></div>
                        <div class="form-group" id="daily_fields" style="display:none;"><label>Daily Rate*</label><input type="number" step="0.01" name="daily_rate" id="daily_rate" class="form-control"></div>
                         <div class="form-group"><label>Status</label>
                            <select name="status" id="status" class="form-control" required><option value="active">Active</option><option value="inactive">Inactive</option></select>
                        </div>
                    </div>
                </fieldset>
                
                <fieldset><legend>Statutory & Bank Details</legend>
                    <div class="form-grid">
                        <div class="form-group"><label>KRA PIN</label><input type="text" name="kra_pin" id="kra_pin" class="form-control"></div>
                        <div class="form-group"><label>NHIF Number</label><input type="text" name="nhif_number" id="nhif_number" class="form-control"></div>
                        <div class="form-group"><label>NSSF Number</label><input type="text" name="nssf_number" id="nssf_number" class="form-control"></div>
                        <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" id="bank_name" class="form-control"></div>
                        <div class="form-group"><label>Bank Branch</label><input type="text" name="bank_branch" id="bank_branch" class="form-control"></div>
                        <div class="form-group"><label>Bank Account Number</label><input type="text" name="bank_account_number" id="bank_account_number" class="form-control"></div>
                    </div>
                </fieldset>

                 <fieldset><legend>Notes</legend>
                    <div class="form-group"><textarea name="notes" id="notes" rows="3" class="form-control"></textarea></div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('employeeFormModal')">Cancel</button>
                <button type="submit" id="employee-form-submit" class="btn-success"><i class="fas fa-plus"></i> Add Employee</button>
            </div>
        </form>
    </div>
</div>

<div id="payslipModal" class="modal">
    <div class="modal-content large" id="payslip-content">
        </div>
</div>

<script>
    function openTab(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
        document.querySelectorAll(".tab-link").forEach(link => link.classList.remove("active"));
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.classList.add("active");

        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        if(tabName !== 'employees') {
            url.searchParams.delete('view_id');
        }
        window.history.pushState({}, '', url);

        // -- FIX IS HERE --
        // This block ensures the settings loaders are always called when the tab is activated.
        if (tabName === 'settings') {
            loadPayrollSettings();
            loadStatutorySettings(); 
        }
    }

    function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    
    // This function resets the employee form to its "Add New" state.
    function resetEmployeeForm() {
        const form = document.getElementById('employee-form');
        form.reset();
        document.getElementById('employee-form-title-in-modal').textContent = 'Add New Employee';
        form.querySelector('#employee_action').value = 'add_employee';
        form.querySelector('#employee_id_hidden').value = '';
        form.querySelector('#employee-form-submit').innerHTML = '<i class="fas fa-plus"></i> Add Employee';
        toggleSalaryFields();
    }
    
    document.addEventListener('click', function(event) {
        if (event.target.closest('.btn-add')) {
            resetEmployeeForm();
            openModal('employeeFormModal');
        }
    });

    function editEmployee(employee) {
        const form = document.getElementById('employee-form');
        form.reset();
        document.getElementById('employee-form-title-in-modal').textContent = 'Edit Employee: ' + employee.first_name + ' ' + employee.last_name;
        form.querySelector('#employee_action').value = 'update_employee';
        form.querySelector('#employee_id_hidden').value = employee.id;
        form.querySelector('#employee-form-submit').innerHTML = '<i class="fas fa-save"></i> Update Employee';
        
        for (const key in employee) {
            const el = form.querySelector(`[name="${key}"]`);
            if (el) {
                el.value = employee[key];
            }
        }
        toggleSalaryFields();
        openModal('employeeFormModal');
    }

    function toggleSalaryFields() {
        const type = document.getElementById('employment_type').value;
        const monthlyFields = document.getElementById('monthly_fields');
        const dailyFields = document.getElementById('daily_fields');
        const basicSalaryInput = document.getElementById('basic_salary');
        const dailyRateInput = document.getElementById('daily_rate');

        if (type === 'monthly') {
            monthlyFields.style.display = 'block';
            dailyFields.style.display = 'none';
            basicSalaryInput.required = true;
            dailyRateInput.required = false;
        } else {
            monthlyFields.style.display = 'none';
            dailyFields.style.display = 'block';
            basicSalaryInput.required = false;
            dailyRateInput.required = true;
        }
    }

    function calculateCasualsPay() {
        let grandTotal = 0;
        document.querySelectorAll('.casual-row').forEach(row => {
            const rate = parseFloat(row.querySelector('[data-rate]').dataset.rate);
            const days = parseFloat(row.querySelector('.days-worked-input').value) || 0;
            const rowTotal = rate * days;
            row.querySelector('.row-total').textContent = '<?= $_SESSION['currency_symbol'] ?? '$' ?>' + rowTotal.toFixed(2);
            grandTotal += rowTotal;
        });
        document.getElementById('grand-total').textContent = '<?= $_SESSION['currency_symbol'] ?? '$' ?>' + grandTotal.toFixed(2);
    }

    document.getElementById('casuals-table-body')?.addEventListener('input', (event) => {
        if (event.target.classList.contains('days-worked-input')) {
            calculateCasualsPay();
        }
    });


    function viewPayslip(record) {
        const payslipData = JSON.parse(record.payslip_data);
        
        let earningsHtml = '';
        payslipData.earnings.forEach(item => {
            earningsHtml += `<tr><td>${item.name}</td><td class="amount">${parseFloat(item.amount).toFixed(2)}</td></tr>`;
        });

        let deductionsHtml = '';
        payslipData.deductions.forEach(item => {
            deductionsHtml += `<tr><td>${item.name}</td><td class="amount">${parseFloat(item.amount).toFixed(2)}</td></tr>`;
        });

        const modalContent = document.getElementById('payslip-content');
        modalContent.innerHTML = `
            <div class="modal-header">
                <h3>Payslip</h3>
                <span class="close" onclick="closeModal('payslipModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="payslip-printable">
                    <div class="payslip-header">
                        <h3><?= htmlspecialchars($_SESSION['school_name'] ?? 'Your School') ?></h3>
                        <p>Payslip for ${payslipData.summary.pay_period}</p>
                    </div>
                    <div class="payslip-grid">
                        <div class="payslip-details">
                            <p><strong>Employee Name:</strong> ${record.employee_name}</p>
                        </div>
                        <div class="payslip-details" style="text-align: right;">
                             <p><strong>Pay Date:</strong> ${payslipData.summary.pay_date}</p>
                             <p><strong>Pay Period:</strong> ${payslipData.summary.pay_period}</p>
                        </div>
                    </div>
                    <div class="payslip-grid">
                         <div>
                            <table class="payslip-table"><thead><tr><th>Earnings</th><th class="amount-header">Amount</th></tr></thead><tbody>${earningsHtml}</tbody></table>
                         </div>
                         <div>
                             <table class="payslip-table"><thead><tr><th>Deductions</th><th class="amount-header">Amount</th></tr></thead><tbody>${deductionsHtml}</tbody></table>
                         </div>
                    </div>
                    <div class="payslip-summary">
                        <table>
                            <tr><td><strong>Total Earnings:</strong></td><td class="amount">${parseFloat(payslipData.summary.total_earnings).toFixed(2)}</td></tr>
                            <tr><td><strong>Total Deductions:</strong></td><td class="amount">${parseFloat(payslipData.summary.total_deductions).toFixed(2)}</td></tr>
                            <tr><td><h4>Net Pay:</h4></td><td class="amount"><h4>${parseFloat(payslipData.summary.net_pay).toFixed(2)}</h4></td></tr>
                        </table>
                        <div style="clear:both;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('payslipModal')">Close</button>
                <button type="button" class="btn-primary" onclick="printPayslip()"><i class="fas fa-print"></i> Print</button>
            </div>
        `;
        openModal('payslipModal');
    }

    function printPayslip() {
        const content = document.getElementById('payslip-printable').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Payslip</title>');
        printWindow.document.write('<style>.amount{text-align:right;} .payslip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; } table{width:100%; border-collapse:collapse;} td,th{padding:5px; border-bottom:1px solid #ccc;} h3,h4{margin:5px 0;}</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    
    // --- Payroll Settings Tab Functions ---
    async function loadPayrollSettings() {
        const container = document.getElementById('payrollMetaContainer');
        container.innerHTML = '<p>Loading settings...</p>';
        try {
            const response = await fetch('payroll_api.php?action=get_meta_items');
            const data = await response.json();
            if(data.success) {
                renderSettings(data.data);
            } else {
                container.innerHTML = `<p class="alert-danger">${data.error}</p>`;
            }
        } catch (e) {
            container.innerHTML = `<p class="alert-danger">Failed to load settings.</p>`;
        }
    }
    
    function renderSettings(items) {
        let earningsHtml = items.filter(i => i.type === 'Earning').map(i => {
            const deleteButton = i.is_system == 1 ? '<span title="System Item" style="cursor: help;">🔒</span>' : `<button class="btn-icon btn-danger btn-sm" onclick="deleteMetaItem(${i.id})">&times;</button>`;
            return `<li>${i.name} ${deleteButton}</li>`;
        }).join('');

        let deductionsHtml = items.filter(i => i.type === 'Deduction').map(i => {
            const deleteButton = i.is_system == 1 ? '<span title="System Item" style="cursor: help;">🔒</span>' : `<button class="btn-icon btn-danger btn-sm" onclick="deleteMetaItem(${i.id})">&times;</button>`;
            return `<li>${i.name} ${deleteButton}</li>`;
        }).join('');

        const container = document.getElementById('payrollMetaContainer');
        container.innerHTML = `
            <h4>Custom Allowances & Deductions</h4>
            <div class="form-grid" style="grid-template-columns: 1fr;">
                <div>
                    <h5>Allowances / Earnings</h5>
                    <ul class="list-styled">${earningsHtml || '<li>No items defined.</li>'}</ul>
                </div>
                <div>
                    <h5>Deductions</h5>
                    <ul class="list-styled">${deductionsHtml || '<li>No items defined.</li>'}</ul>
                </div>
            </div>
            <hr>
            <h5>Add New Custom Item</h5>
            <form id="addMetaForm" onsubmit="addMetaItem(event)">
                <div class="form-grid" style="grid-template-columns: 2fr 1fr auto; align-items: flex-end;">
                    <div class="form-group"><label>Item Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Item Type</label><select name="type" class="form-control" required><option value="Earning">Earning (Allowance)</option><option value="Deduction">Deduction</option></select></div>
                    <button type="submit" class="btn-success">Add Item</button>
                </div>
            </form>
        `;
    }

    async function addMetaItem(event) {
        event.preventDefault();
        const form = document.getElementById('addMetaForm');
        const formData = new FormData(form);
        try {
            const response = await fetch('payroll_api.php?action=add_meta_item', { method: 'POST', body: formData });
            const data = await response.json();
            if(data.success) {
                loadPayrollSettings();
                form.reset();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (e) {
            alert('Failed to save item.');
        }
    }
    
     async function deleteMetaItem(id) {
        if(!confirm('Are you sure you want to delete this payroll item? This will also remove it from all employees.')) return;
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('payroll_api.php?action=delete_meta_item', { method: 'POST', body: formData });
            const data = await response.json();
            if(data.success) {
                loadPayrollSettings();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (e) {
            alert('Failed to delete item.');
        }
    }

    // --- Statutory Settings Tab Functions ---
    async function loadStatutorySettings() {
        const container = document.getElementById('statutorySettingsForm');
        // Clear previous content except for the button
        container.querySelectorAll('.form-group').forEach(el => el.remove());
        
        try {
            const response = await fetch('payroll_api.php?action=get_payroll_settings');
            const data = await response.json();
            if(data.success) {
                let formHtml = '';
                data.data.forEach(setting => {
                    formHtml += `<div class="form-group">
                        <label for="setting-${setting.setting_key}">${setting.description}</label>`;
                    
                    if (setting.setting_key.includes('brackets')) {
                        // Use textarea for JSON data for easier editing
                        const prettyJson = JSON.stringify(JSON.parse(setting.setting_value), null, 2);
                        formHtml += `<textarea name="settings[${setting.setting_key}]" id="setting-${setting.setting_key}" rows="8" class="form-control">${prettyJson}</textarea>`;
                    } else {
                        formHtml += `<input type="text" name="settings[${setting.setting_key}]" id="setting-${setting.setting_key}" value="${setting.setting_value}" class="form-control">`;
                    }
                    formHtml += `</div>`;
                });
                container.insertAdjacentHTML('afterbegin', formHtml);
            }
        } catch (e) {
            console.error("Error loading statutory settings:", e);
            container.insertAdjacentHTML('afterbegin', `<p class="alert-danger">Failed to load statutory settings.</p>`);
        }
    }

    async function saveStatutorySettings(event) {
        event.preventDefault();
        const form = document.getElementById('statutorySettingsForm');
        const formData = new FormData(form);
        
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Saving...';

        try {
            const response = await fetch('payroll_api.php?action=save_payroll_settings', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if(data.success) {
                alert(data.message);
            } else {
                alert('Error: ' + data.error);
            }
        } catch(e) {
            alert('An unexpected error occurred. Please check the console.');
            console.error(e);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-save"></i> Save Statutory Settings';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'dashboard';
        const viewId = urlParams.get('view_id');

        // Always reset the form when the modal is opened from the main "Add" button
        document.querySelector('.btn-add')?.addEventListener('click', resetEmployeeForm);

        if(tab === 'employees' && viewId) {
             const tabButton = document.querySelector(`.tab-link[onclick*="employees"]`);
             if (tabButton) tabButton.click();
        } else {
             const tabButton = document.querySelector(`.tab-link[onclick*="${tab}"]`);
             if (tabButton) { tabButton.click(); } else { document.querySelector('.tab-link').click(); }
        }
        
        toggleSalaryFields();
    });
</script>