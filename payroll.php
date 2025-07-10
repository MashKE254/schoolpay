<?php
// payroll.php - Enhanced School Payroll Processing System (Multi-Tenant)
require 'config.php';
require 'functions.php';
include 'header.php'; // Handles session start and sets $school_id

// ===================================================================================
// --- POST REQUEST HANDLING (SERVER-SIDE LOGIC) ---
// ===================================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = false;
    // --- Add or Update Employee ---
    if (isset($_POST['addEmployee']) || isset($_POST['updateEmployee'])) {
        $action_taken = true;
        $employee_id_post = trim($_POST['employee_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $department = $_POST['department'];
        $position = trim($_POST['position']);
        $employment_type = $_POST['employment_type'];
        $hire_date = $_POST['hire_date'];
        $basic_salary = floatval($_POST['basic_salary'] ?? 0);
        $house_allowance = floatval($_POST['house_allowance'] ?? 0);
        $transport_allowance = floatval($_POST['transport_allowance'] ?? 0);
        $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
        $bank_account = trim($_POST['bank_account']);
        $kra_pin = trim($_POST['kra_pin']);
        $nhif_number = trim($_POST['nhif_number']);
        $nssf_number = trim($_POST['nssf_number']);
        $status_post = $_POST['status'];

        if (isset($_POST['addEmployee'])) {
            $sql = "INSERT INTO employees (school_id, employee_id, first_name, last_name, email, phone, department, position, employment_type, hire_date, basic_salary, house_allowance, transport_allowance, hourly_rate, bank_account, kra_pin, nhif_number, nssf_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$school_id, $employee_id_post, $first_name, $last_name, $email, $phone, $department, $position, $employment_type, $hire_date, $basic_salary, $house_allowance, $transport_allowance, $hourly_rate, $bank_account, $kra_pin, $nhif_number, $nssf_number, $status_post]);
        } elseif (isset($_POST['updateEmployee'])) {
            $id = intval($_POST['emp_id']);
            $sql = "UPDATE employees SET employee_id=?, first_name=?, last_name=?, email=?, phone=?, department=?, position=?, employment_type=?, hire_date=?, basic_salary=?, house_allowance=?, transport_allowance=?, hourly_rate=?, bank_account=?, kra_pin=?, nhif_number=?, nssf_number=?, status=? WHERE id=? AND school_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$employee_id_post, $first_name, $last_name, $email, $phone, $department, $position, $employment_type, $hire_date, $basic_salary, $house_allowance, $transport_allowance, $hourly_rate, $bank_account, $kra_pin, $nhif_number, $nssf_number, $status_post, $id, $school_id]);
        }
    }

    // --- Deactivate Employee ---
    if (isset($_POST['deleteEmployee'])) {
        $action_taken = true;
        $id = intval($_POST['emp_id']);
        $stmt = $pdo->prepare("UPDATE employees SET status = 'inactive' WHERE id = ? AND school_id = ?");
        $stmt->execute([$id, $school_id]);
    }

    // --- Mark Payroll as Paid ---
    if (isset($_POST['markAsPaid'])) {
        $action_taken = true;
        $record_id = intval($_POST['record_id']);
        $stmt = $pdo->prepare("UPDATE payroll SET status = 'Paid' WHERE id = ? AND school_id = ? AND status = 'Draft'");
        $stmt->execute([$record_id, $school_id]);
    }

    // --- Process a new Payroll Record ---
    if (isset($_POST['addPayroll'])) {
        $action_taken = true;
        $employee_id = intval($_POST['payroll_employee_id']);
        $employee_name = trim($_POST['employee_name']);
        $employee_type = $_POST['employee_type'];
        $pay_period = $_POST['pay_period'];
        $pay_date = $_POST['pay_date'];
        $basic_salary = floatval($_POST['basic_salary'] ?? 0);
        $house_allowance = floatval($_POST['house_allowance'] ?? 0);
        $transport_allowance = floatval($_POST['transport_allowance'] ?? 0);
        $gross_pay = $basic_salary + $house_allowance + $transport_allowance;
        $allowances_data = json_encode(['house_allowance' => $house_allowance, 'transport_allowance' => $transport_allowance]);
        $paye = floatval($_POST['paye_calculated']);
        $nhif = floatval($_POST['nhif_calculated']);
        $nssf = floatval($_POST['nssf_calculated']);
        $other_deduction = floatval($_POST['other_deduction'] ?? 0);
        $total_deductions = $paye + $nhif + $nssf + $other_deduction;
        $net_pay = $gross_pay - $total_deductions;
        $deduction_data = json_encode(['paye' => ['calculated' => $paye], 'nhif' => ['calculated' => $nhif], 'nssf' => ['calculated' => $nssf], 'other' => ['calculated' => $other_deduction]]);

        $sql = "INSERT INTO payroll (school_id, employee_id, employee_name, employee_type, gross_pay, allowances, tax, insurance, retirement, other_deduction, total_deductions, net_pay, pay_date, pay_period, deduction_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$school_id, $employee_id, $employee_name, $employee_type, $gross_pay, $allowances_data, $paye, $nhif, $nssf, $other_deduction, $total_deductions, $net_pay, $pay_date, $pay_period, $deduction_data]);
    }
    
    if ($action_taken) {
        // Redirect to avoid form resubmission
        header("Location: payroll.php");
        exit();
    }
}

// ===================================================================================
// --- DATA FETCHING for rendering the page ---
// ===================================================================================
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE school_id = ? ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT p.*, e.department, e.position FROM payroll p LEFT JOIN employees e ON p.employee_id = e.id WHERE p.school_id = ? ORDER BY p.pay_date DESC, p.created_at DESC");
    $stmt->execute([$school_id]);
    $payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT DISTINCT department FROM employees WHERE status = 'active' AND school_id = ? ORDER BY department");
    $stmt->execute([$school_id]);
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT DISTINCT pay_period FROM payroll WHERE school_id = ? ORDER BY pay_period DESC");
    $stmt->execute([$school_id]);
    $payPeriods = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $activeEmployees = count(array_filter($employees, fn($e) => $e['status'] === 'active'));
    $currentMonthPeriod = date('Y-m');
    $monthlyTotal = array_reduce($payrollRecords, function($sum, $record) use ($currentMonthPeriod) {
        return strpos($record['pay_period'], $currentMonthPeriod) === 0 ? $sum + $record['net_pay'] : $sum;
    }, 0);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>
<style>
/* Additional styles for workflow and reporting elements from original file */
.status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8em; font-weight: 700; color: white; text-transform: uppercase; letter-spacing: 0.5px; }
.status-Draft { background-color: #8da0a5; }
.status-Paid { background-color: #2ecc71; }
.report-section, .filter-controls { padding: 20px; background-color: #f8f9fa; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--border); }
.filter-controls { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
#reportOutput table { margin-top: 15px; }
.employee-modal .modal-content { max-width: 1200px; }
.btn-group { flex-wrap: wrap; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
.summary-grid .summary-card { background: var(--card-bg); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow); }
.summary-card h4 { display: flex; align-items: center; gap: 10px; color: var(--primary); margin-bottom: 10px; }
.summary-card .amount { font-size: 2rem; font-weight: 700; color: var(--secondary); }
</style>

<h1>School Payroll Management System</h1>

<div class="summary-grid">
    <div class="summary-card">
        <h4><i class="fas fa-users"></i>Total Employees</h4>
        <p class="amount"><?php echo count($employees); ?></p>
    </div>
    <div class="summary-card">
        <h4><i class="fas fa-user-check"></i>Active Employees</h4>
        <p class="amount"><?php echo $activeEmployees; ?></p>
    </div>
    <div class="summary-card">
        <h4><i class="fas fa-wallet"></i>This Month's Payroll (Net)</h4>
        <p class="amount">KSh <?php echo number_format($monthlyTotal, 2); ?></p>
    </div>
    <div class="summary-card">
        <h4><i class="fas fa-file-invoice"></i>Total Payroll Records</h4>
        <p class="amount"><?php echo count($payrollRecords); ?></p>
    </div>
</div>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'recordsTab')"><i class="fas fa-history"></i> Payroll Records</button>
        <button class="tab-link" onclick="openTab(event, 'addPayrollTab')"><i class="fas fa-cogs"></i> Process Payroll</button>
        <button class="tab-link" onclick="openTab(event, 'reportsTab')"><i class="fas fa-chart-pie"></i> Payroll Reports</button>
        <button class="tab-link" onclick="openModal('employeeModal')"><i class="fas fa-user-friends"></i> Manage Employees</button>
    </div>

    <div id="recordsTab" class="tab-content active">
        <div class="card">
            <h3>Payroll History & Status</h3>
            <div class="table-container">
                <table id="payrollTable">
                    <thead>
                        <tr>
                            <th>Period</th><th>Employee Name</th><th>Department</th><th>Gross Pay (KSh)</th><th>Net Pay (KSh)</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payrollRecords)): ?>
                            <tr><td colspan="7" class="text-center">No payroll records found.</td></tr>
                        <?php else: ?>
                            <?php foreach($payrollRecords as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['pay_period']); ?></td>
                                <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                                <td class="amount"><?php echo number_format($record['gross_pay'], 2); ?></td>
                                <td class="amount"><strong><?php echo number_format($record['net_pay'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo str_replace(' ', '-', $record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <!-- <a href="print_payslip.php?id=<?php echo $record['id']; ?>" target="_blank" class="btn btn-sm btn-secondary" data-tooltip="View Payslip"><i class="fas fa-print"></i></a> -->
                                        <?php if ($record['status'] === 'Draft'): ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to mark this record as paid? This action cannot be undone.');">
                                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" name="markAsPaid" class="btn btn-sm btn-success" data-tooltip="Mark as Paid"><i class="fas fa-check-double"></i> Mark Paid</button>
                                            </form>
                                        <?php endif; ?>
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

    <div id="addPayrollTab" class="tab-content">
        <div class="card">
            <h3>Process New Payroll Record</h3>
            <form id="payrollForm" method="post" onsubmit="return validatePayrollForm()">
                <div class="form-section">
                    <h4>1. Select Employee & Period</h4>
                    <div class="form-group">
                        <label for="employeeSelect">Employee</label>
                        <select id="employeeSelect" name="payroll_employee_select" class="form-control" onchange="loadEmployeeDataForPayroll()" required>
                            <option value="">Choose an employee...</option>
                            <?php foreach($employees as $emp): if($emp['status'] === 'active'): ?>
                            <option value='<?php echo htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8'); ?>'>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                            </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                     <div class="form-group"><label for="pay_period">Pay Period</label><input type="month" name="pay_period" id="pay_period" class="form-control" value="<?php echo date('Y-m'); ?>" required></div>
                     <div class="form-group"><label for="pay_date">Pay Date</label><input type="date" name="pay_date" id="pay_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                </div>
                <input type="hidden" name="payroll_employee_id" id="payrollEmployeeId"><input type="hidden" name="employee_name" id="payrollEmployeeName"><input type="hidden" name="employee_type" id="payrollEmployeeType">
                <div class="form-section">
                    <h4>2. Earnings</h4>
                    <div class="form-group"><label for="payrollBasicSalary">Basic Salary</label><input type="number" step="0.01" class="form-control" name="basic_salary" id="payrollBasicSalary" onkeyup="calculatePayroll()" value="0"></div>
                    <div class="form-group"><label for="payrollHouseAllowance">House Allowance</label><input type="number" step="0.01" class="form-control" name="house_allowance" id="payrollHouseAllowance" onkeyup="calculatePayroll()" value="0"></div>
                    <div class="form-group"><label for="payrollTransportAllowance">Transport Allowance</label><input type="number" step="0.01" class="form-control" name="transport_allowance" id="payrollTransportAllowance" onkeyup="calculatePayroll()" value="0"></div>
                </div>
                <div class="form-section">
                    <h4>3. Deductions</h4>
                    <div class="form-group"><label>PAYE (Auto-calculated)</label><input type="text" id="payeCalculatedDisplay" class="form-control" readonly><input type="hidden" name="paye_calculated" id="payeCalculated"></div>
                    <div class="form-group"><label>NHIF (Auto-calculated)</label><input type="text" id="nhifCalculatedDisplay" class="form-control" readonly><input type="hidden" name="nhif_calculated" id="nhifCalculated"></div>
                    <div class="form-group"><label>NSSF (Auto-calculated)</label><input type="text" id="nssfCalculatedDisplay" class="form-control" readonly><input type="hidden" name="nssf_calculated" id="nssfCalculated"></div>
                    <div class="form-group"><label>Other Deductions (e.g., salary advance)</label><input type="number" step="0.01" name="other_deduction" id="otherDeduction" class="form-control" value="0" onkeyup="calculatePayroll()"></div>
                </div>
                <div class="form-section">
                    <h4>4. Summary</h4>
                    <div class="form-group"><label>Gross Pay</label><input type="text" id="payrollGrossPay" class="form-control" readonly style="font-weight:bold;"></div>
                    <div class="form-group"><label>Total Deductions</label><input type="text" id="payrollTotalDeductions" class="form-control" readonly style="font-weight:bold; color: var(--danger);"></div>
                    <div class="form-group"><label>Net Pay</label><input type="text" id="payrollNetPay" class="form-control" readonly style="font-weight:bold; color: var(--success); font-size: 1.2em;"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="addPayroll" class="btn btn-primary"><i class="fas fa-save"></i> Save as Draft</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('payrollForm').reset(); calculatePayroll();"><i class="fas fa-undo"></i> Reset</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="reportsTab" class="tab-content">
         <div class="card">
            <h3>Payroll Reports</h3>
            <div class="report-section">
                <h4>Generate Report</h4>
                 <div class="filter-controls">
                    <div class="form-group"><label>Report Type</label><select id="reportType" class="form-control"><option value="bank_advice">Bank Advice List</option><option value="statutory">Statutory Deductions (PAYE/NHIF/NSSF)</option></select></div>
                    <div class="form-group"><label>For Pay Period</label><select id="reportPeriod" class="form-control"><?php foreach($payPeriods as $period): ?><option value="<?php echo htmlspecialchars($period); ?>"><?php echo htmlspecialchars($period); ?></option><?php endforeach; ?></select></div>
                    <button class="btn btn-primary" onclick="generateReport()"><i class="fas fa-cogs"></i> Generate</button>
                </div>
            </div>
            <div id="reportOutput" class="table-container"><p class="text-center">Select a report type and period, then click Generate.</p></div>
        </div>
    </div>
</div>

<div id="employeeModal" class="modal employee-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Employee Management</h2>
            <button class="close" onclick="closeModal('employeeModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tabs">
                <button class="tab-link active" onclick="openSubTab(event, 'employeeListTab')">Employee List</button>
                <button class="tab-link" onclick="openSubTab(event, 'addEmployeeTab'); resetEmployeeForm();">Add New Employee</button>
            </div>
            
            <div id="employeeListTab" class="tab-content active">
                <div class="table-container">
                    <table id="employeesTable">
                        <thead><tr><th>Name</th><th>Department</th><th>Position</th><th>Salary/Rate (KSh)</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($employees as $employee): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong><br><small><?php echo htmlspecialchars($employee['employee_id']); ?></small></td>
                                <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                <td><?php echo ($employee['employment_type'] === 'monthly') ? number_format($employee['basic_salary'], 2).'/month' : number_format($employee['hourly_rate'], 2).'/'.$employee['employment_type']; ?></td>
                                <td><span class="badge badge-<?php echo $employee['status']; ?>"><?php echo ucfirst($employee['status']); ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editEmployee(<?php echo json_encode($employee); ?>)' class="btn btn-sm btn-edit">Edit</button>
                                        <?php if($employee['status'] === 'active'): ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this employee?');"><input type="hidden" name="emp_id" value="<?php echo $employee['id']; ?>"><button type="submit" name="deleteEmployee" class="btn btn-sm btn-danger">Deactivate</button></form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="addEmployeeTab" class="tab-content">
                <h3 id="employeeFormTitle">Add New Employee</h3>
                 <form id="employeeForm" method="post">
                    <input type="hidden" name="emp_id" id="editEmployeeId">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-section">
                            <h4>Personal Information</h4>
                             <div class="form-group"><label>Employee ID</label><input type="text" name="employee_id" id="formEmployeeId" required class="form-control"></div>
                             <div class="form-group"><label>First Name</label><input type="text" name="first_name" id="formFirstName" required class="form-control"></div>
                             <div class="form-group"><label>Last Name</label><input type="text" name="last_name" id="formLastName" required class="form-control"></div>
                             <div class="form-group"><label>Email</label><input type="email" name="email" id="formEmail" required class="form-control"></div>
                             <div class="form-group"><label>Phone</label><input type="text" name="phone" id="formPhone" class="form-control"></div>
                        </div>
                        <div class="form-section">
                            <h4>Employment Details</h4>
                            <div class="form-group"><label>Department</label><select name="department" id="formDepartment" required class="form-control"><option value="">Select Department</option><?php foreach($departments as $dept): ?><option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option><?php endforeach; ?><option value="Other">Other</option></select></div>
                            <div class="form-group"><label>Position</label><input type="text" name="position" id="formPosition" required class="form-control"></div>
                            <div class="form-group"><label>Employment Type</label><select name="employment_type" id="formEmploymentType" required class="form-control" onchange="toggleSalaryFields(this.value)"><option value="monthly">Monthly Salary</option><option value="hourly">Hourly Rate</option><option value="daily">Daily Rate</option></select></div>
                            <div class="form-group"><label>Hire Date</label><input type="date" name="hire_date" id="formHireDate" required class="form-control"></div>
                            <div class="form-group"><label>Status</label><select name="status" id="formStatus" required class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        </div>
                         <div class="form-section">
                            <h4>Compensation (KSh)</h4>
                            <div id="basicSalaryGroup" class="form-group"><label>Basic Salary (Monthly)</label><input type="number" name="basic_salary" id="formBasicSalary" step="0.01" class="form-control" value="0"></div>
                            <div id="allowancesGroup"><div class="form-group"><label>House Allowance</label><input type="number" name="house_allowance" id="formHouseAllowance" step="0.01" class="form-control" value="0"></div><div class="form-group"><label>Transport Allowance</label><input type="number" name="transport_allowance" id="formTransportAllowance" step="0.01" class="form-control" value="0"></div></div>
                            <div id="hourlyRateGroup" class="form-group" style="display: none;"><label>Hourly/Daily Rate</label><input type="number" name="hourly_rate" id="formHourlyRate" step="0.01" class="form-control" value="0"></div>
                         </div>
                         <div class="form-section">
                            <h4>Statutory Information</h4>
                             <div class="form-group"><label>Bank Account No.</label><input type="text" name="bank_account" id="formBankAccount" class="form-control"></div>
                             <div class="form-group"><label>KRA PIN</label><input type="text" name="kra_pin" id="formKraPin" class="form-control"></div>
                             <div class="form-group"><label>NHIF Number</label><input type="text" name="nhif_number" id="formNhifNumber" class="form-control"></div>
                             <div class="form-group"><label>NSSF Number</label><input type="text" name="nssf_number" id="formNssfNumber" class="form-control"></div>
                         </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="addEmployee" id="employeeSubmitBtn" class="btn btn-primary">Save Employee</button>
                        <button type="button" onclick="closeModal('employeeModal')" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const allPayrollRecords = <?php echo json_encode($payrollRecords); ?>;
function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
window.onclick = function(event) { if (event.target.classList.contains('modal')) { closeModal(event.target.id); } }
function openTab(evt, tabName) {
    document.querySelectorAll('.tab-container > .tab-content').forEach(tab => tab.style.display = 'none');
    document.querySelectorAll('.tab-container > .tabs > .tab-link').forEach(link => link.classList.remove('active'));
    document.getElementById(tabName).style.display = 'block';
    evt.currentTarget.classList.add('active');
}
function openSubTab(evt, tabName) {
    document.querySelectorAll('.modal-body > .tab-content').forEach(tab => tab.style.display = 'none');
    document.querySelectorAll('.modal-body > .tabs > .tab-link').forEach(link => link.classList.remove('active'));
    document.getElementById(tabName).style.display = 'block';
    evt.currentTarget.classList.add('active');
}
function toggleSalaryFields(empType) {
    document.getElementById('basicSalaryGroup').style.display = empType === 'monthly' ? 'block' : 'none';
    document.getElementById('allowancesGroup').style.display = empType === 'monthly' ? 'block' : 'none';
    document.getElementById('hourlyRateGroup').style.display = empType !== 'monthly' ? 'block' : 'none';
}
function resetEmployeeForm() {
    document.getElementById('employeeForm').reset();
    document.getElementById('editEmployeeId').value = '';
    document.getElementById('employeeFormTitle').innerText = 'Add New Employee';
    const submitBtn = document.getElementById('employeeSubmitBtn');
    submitBtn.name = 'addEmployee';
    submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Employee';
    toggleSalaryFields('monthly');
}
function editEmployee(employeeData) {
    resetEmployeeForm();
    document.getElementById('editEmployeeId').value = employeeData.id;
    document.getElementById('formEmployeeId').value = employeeData.employee_id;
    document.getElementById('formFirstName').value = employeeData.first_name;
    document.getElementById('formLastName').value = employeeData.last_name;
    document.getElementById('formEmail').value = employeeData.email;
    document.getElementById('formPhone').value = employeeData.phone;
    document.getElementById('formDepartment').value = employeeData.department;
    document.getElementById('formPosition').value = employeeData.position;
    document.getElementById('formEmploymentType').value = employeeData.employment_type;
    document.getElementById('formHireDate').value = employeeData.hire_date;
    document.getElementById('formStatus').value = employeeData.status;
    document.getElementById('formBasicSalary').value = employeeData.basic_salary || 0;
    document.getElementById('formHouseAllowance').value = employeeData.house_allowance || 0;
    document.getElementById('formTransportAllowance').value = employeeData.transport_allowance || 0;
    document.getElementById('formHourlyRate').value = employeeData.hourly_rate || 0;
    document.getElementById('formBankAccount').value = employeeData.bank_account;
    document.getElementById('formKraPin').value = employeeData.kra_pin;
    document.getElementById('formNhifNumber').value = employeeData.nhif_number;
    document.getElementById('formNssfNumber').value = employeeData.nssf_number;
    document.getElementById('employeeFormTitle').innerText = 'Edit Employee';
    const submitBtn = document.getElementById('employeeSubmitBtn');
    submitBtn.name = 'updateEmployee';
    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Employee';
    toggleSalaryFields(employeeData.employment_type);
    openSubTab({ currentTarget: document.querySelector('.modal-body .tab-link:nth-child(2)') }, 'addEmployeeTab');
}
function loadEmployeeDataForPayroll() {
    const select = document.getElementById('employeeSelect');
    if (!select.value) { document.getElementById('payrollForm').reset(); calculatePayroll(); return; };
    const employee = JSON.parse(select.value);
    document.getElementById('payrollEmployeeId').value = employee.id;
    document.getElementById('payrollEmployeeName').value = employee.first_name + ' ' + employee.last_name;
    document.getElementById('payrollEmployeeType').value = employee.employment_type;
    document.getElementById('payrollBasicSalary').value = employee.basic_salary || 0;
    document.getElementById('payrollHouseAllowance').value = employee.house_allowance || 0;
    document.getElementById('payrollTransportAllowance').value = employee.transport_allowance || 0;
    calculatePayroll();
}
function validatePayrollForm() {
    if(!document.getElementById('payrollEmployeeId').value) { alert('Please select an employee first.'); return false; }
    return true;
}
function calculateKenyanPAYE(grossPay) {
    if (grossPay <= 0) return 0; const relief = 2400; let paye = 0;
    if (grossPay <= 24000) { paye = grossPay * 0.10; }
    else if (grossPay <= 32333) { paye = 2400 + ((grossPay - 24000) * 0.25); }
    else if (grossPay <= 500000) { paye = 4483.25 + ((grossPay - 32333) * 0.30); }
    else if (grossPay <= 800000) { paye = 144783.33 + ((grossPay - 500000) * 0.325); }
    else { paye = 242283.33 + ((grossPay - 800000) * 0.35); }
    return Math.max(0, paye - relief);
}
function calculateKenyanNHIF(grossPay) {
    if (grossPay <= 5999) return 150; if (grossPay <= 7999) return 300; if (grossPay <= 11999) return 400; if (grossPay <= 14999) return 500; if (grossPay <= 19999) return 600; if (grossPay <= 24999) return 750; if (grossPay <= 29999) return 850; if (grossPay <= 34999) return 900; if (grossPay <= 39999) return 950; if (grossPay <= 44999) return 1000; if (grossPay <= 49999) return 1100; if (grossPay <= 59999) return 1200; if (grossPay <= 69999) return 1300; if (grossPay <= 79999) return 1400; if (grossPay <= 89999) return 1500; if (grossPay <= 99999) return 1600; return 1700;
}
function calculateKenyanNSSF(grossPay) {
    const tier1Limit = 7000; const tier2Limit = 36000; const rate = 0.06; let nssf = 0;
    nssf += Math.min(grossPay, tier1Limit) * rate;
    if (grossPay > tier1Limit) { nssf += Math.min(grossPay - tier1Limit, tier2Limit - tier1Limit) * rate; }
    return nssf;
}
function calculatePayroll() {
    const basic = parseFloat(document.getElementById('payrollBasicSalary').value) || 0;
    const house = parseFloat(document.getElementById('payrollHouseAllowance').value) || 0;
    const transport = parseFloat(document.getElementById('payrollTransportAllowance').value) || 0;
    const otherDed = parseFloat(document.getElementById('otherDeduction').value) || 0;
    const grossPay = basic + house + transport;
    const paye = calculateKenyanPAYE(grossPay);
    const nhif = calculateKenyanNHIF(grossPay);
    const nssf = calculateKenyanNSSF(grossPay);
    const totalDeductions = paye + nhif + nssf + otherDed;
    document.getElementById('payrollGrossPay').value = 'KSh ' + grossPay.toFixed(2);
    document.getElementById('payeCalculatedDisplay').value = 'KSh ' + paye.toFixed(2);
    document.getElementById('payeCalculated').value = paye.toFixed(2);
    document.getElementById('nhifCalculatedDisplay').value = 'KSh ' + nhif.toFixed(2);
    document.getElementById('nhifCalculated').value = nhif.toFixed(2);
    document.getElementById('nssfCalculatedDisplay').value = 'KSh ' + nssf.toFixed(2);
    document.getElementById('nssfCalculated').value = nssf.toFixed(2);
    document.getElementById('payrollTotalDeductions').value = 'KSh ' + totalDeductions.toFixed(2);
    document.getElementById('payrollNetPay').value = 'KSh ' + (grossPay - totalDeductions).toFixed(2);
}
function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const period = document.getElementById('reportPeriod').value;
    const reportOutput = document.getElementById('reportOutput');
    const filteredRecords = allPayrollRecords.filter(r => r.pay_period === period && r.status === 'Paid');
    let reportTitle = reportType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    let html = `<h4>${reportTitle} for ${period}</h4>`;
    if (filteredRecords.length === 0) {
        reportOutput.innerHTML = html + '<p class="text-center">No "Paid" records found for this period to generate the report.</p>';
        return;
    }
    if (reportType === 'bank_advice') {
        html += '<table class="table"><thead><tr><th>Employee Name</th><th class="amount">Net Pay (KSh)</th></tr></thead><tbody>';
        let totalNet = 0;
        filteredRecords.forEach(r => { html += `<tr><td>${r.employee_name}</td><td class="amount">${parseFloat(r.net_pay).toFixed(2)}</td></tr>`; totalNet += parseFloat(r.net_pay); });
        html += `<tr class="total-row" style="font-weight:bold;"><td>TOTAL</td><td class="amount">${totalNet.toFixed(2)}</td></tr></tbody></table>`;
    } else if (reportType === 'statutory') {
        html += '<table class="table"><thead><tr><th>Employee Name</th><th class="amount">PAYE</th><th class="amount">NHIF</th><th class="amount">NSSF</th></tr></thead><tbody>';
        let totalPAYE = 0, totalNHIF = 0, totalNSSF = 0;
        filteredRecords.forEach(r => { html += `<tr><td>${r.employee_name}</td><td class="amount">${parseFloat(r.tax).toFixed(2)}</td><td class="amount">${parseFloat(r.insurance).toFixed(2)}</td><td class="amount">${parseFloat(r.retirement).toFixed(2)}</td></tr>`; totalPAYE += parseFloat(r.tax); totalNHIF += parseFloat(r.insurance); totalNSSF += parseFloat(r.retirement); });
        html += `<tr style="font-weight:bold;"><td>TOTALS</td><td class="amount">${totalPAYE.toFixed(2)}</td><td class="amount">${totalNHIF.toFixed(2)}</td><td class="amount">${totalNSSF.toFixed(2)}</td></tr></tbody></table>`;
    }
    reportOutput.innerHTML = html;
}
document.addEventListener('DOMContentLoaded', () => {
    const firstTab = document.querySelector('.tab-container > .tabs > .tab-link');
    if(firstTab) firstTab.click();
    const firstSubTab = document.querySelector('.modal-body > .tabs > .tab-link');
    if(firstSubTab) firstSubTab.click();
});
</script>

<?php include 'footer.php'; ?>
