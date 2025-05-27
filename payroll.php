<?php
// payroll.php - Enhanced School Payroll Processing System
require 'config.php';
require 'functions.php';
include 'header.php';

// Handle employee management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addEmployee'])) {
    $employee_id = trim($_POST['employee_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];
    $position = trim($_POST['position']);
    $employment_type = $_POST['employment_type'];
    $hire_date = $_POST['hire_date'];
    $basic_salary = floatval($_POST['basic_salary']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $bank_account = trim($_POST['bank_account']);
    $kra_pin = trim($_POST['kra_pin']);
    $nhif_number = trim($_POST['nhif_number']);
    $nssf_number = trim($_POST['nssf_number']);
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, 
                          position, employment_type, hire_date, basic_salary, hourly_rate, bank_account, 
                          kra_pin, nhif_number, nssf_number, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if($stmt->execute([$employee_id, $first_name, $last_name, $email, $phone, $department, $position, 
                      $employment_type, $hire_date, $basic_salary, $hourly_rate, $bank_account, 
                      $kra_pin, $nhif_number, $nssf_number, $status])) {
        echo "<script>showAlert('Employee added successfully');</script>";
    } else {
        echo "<script>showAlert('Error adding employee');</script>";
    }
}

// Handle employee update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateEmployee'])) {
    $id = intval($_POST['emp_id']);
    $employee_id = trim($_POST['employee_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];
    $position = trim($_POST['position']);
    $employment_type = $_POST['employment_type'];
    $hire_date = $_POST['hire_date'];
    $basic_salary = floatval($_POST['basic_salary']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $bank_account = trim($_POST['bank_account']);
    $kra_pin = trim($_POST['kra_pin']);
    $nhif_number = trim($_POST['nhif_number']);
    $nssf_number = trim($_POST['nssf_number']);
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE employees SET employee_id = ?, first_name = ?, last_name = ?, email = ?, 
                          phone = ?, department = ?, position = ?, employment_type = ?, hire_date = ?, 
                          basic_salary = ?, hourly_rate = ?, bank_account = ?, kra_pin = ?, nhif_number = ?, 
                          nssf_number = ?, status = ? WHERE id = ?");
    if($stmt->execute([$employee_id, $first_name, $last_name, $email, $phone, $department, $position, 
                      $employment_type, $hire_date, $basic_salary, $hourly_rate, $bank_account, 
                      $kra_pin, $nhif_number, $nssf_number, $status, $id])) {
        echo "<script>showAlert('Employee updated successfully');</script>";
    } else {
        echo "<script>showAlert('Error updating employee');</script>";
    }
}

// Handle employee deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteEmployee'])) {
    $id = intval($_POST['emp_id']);
    $stmt = $pdo->prepare("UPDATE employees SET status = 'inactive' WHERE id = ?");
    if($stmt->execute([$id])) {
        echo "<script>showAlert('Employee deactivated successfully');</script>";
    } else {
        echo "<script>showAlert('Error deactivating employee');</script>";
    }
}

// Handle bulk payroll generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generateBulkPayroll'])) {
    $pay_period = $_POST['pay_period'];
    $pay_date = $_POST['bulk_pay_date'];
    $department_filter = $_POST['department_filter'];
    
    // Get employees based on filter
    $sql = "SELECT * FROM employees WHERE status = 'active'";
    $params = [];
    
    if ($department_filter !== 'all') {
        $sql .= " AND department = ?";
        $params[] = $department_filter;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $generated_count = 0;
    foreach ($employees as $employee) {
        // Check if payroll already exists for this period
        $check_stmt = $pdo->prepare("SELECT id FROM payroll WHERE employee_id = ? AND pay_period = ?");
        $check_stmt->execute([$employee['id'], $pay_period]);
        
        if ($check_stmt->rowCount() == 0) {
            // Calculate gross pay based on employment type
            if ($employee['employment_type'] === 'monthly') {
                $gross_pay = $employee['basic_salary'];
                $hours = 0;
                $rate = 0;
            } else {
                // For hourly/daily workers, use default hours (can be edited later)
                $hours = ($employee['employment_type'] === 'hourly') ? 40 : 22; // 40 hours/week or 22 days/month
                $rate = $employee['hourly_rate'];
                $gross_pay = $hours * $rate;
            }
            
            // Apply default deductions (PAYE, NHIF, NSSF)
            $paye = calculatePAYE($gross_pay);
            $nhif = calculateNHIF($gross_pay);
            $nssf = calculateNSSF($gross_pay);
            
            $total_deductions = $paye + $nhif + $nssf;
            $net_pay = $gross_pay - $total_deductions;
            
            // Store deduction breakdown
            $deduction_data = json_encode([
                'paye' => ['type' => 'calculated', 'value' => $paye, 'calculated' => $paye],
                'nhif' => ['type' => 'calculated', 'value' => $nhif, 'calculated' => $nhif],
                'nssf' => ['type' => 'calculated', 'value' => $nssf, 'calculated' => $nssf],
                'other' => ['type' => 'fixed', 'value' => 0, 'calculated' => 0]
            ]);
            
            // Insert payroll record
            $insert_stmt = $pdo->prepare("INSERT INTO payroll (employee_id, employee_name, employee_type, 
                                         hours, rate, gross_pay, tax, insurance, retirement, other_deduction, 
                                         total_deductions, net_pay, pay_date, pay_period, deduction_data, created_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if ($insert_stmt->execute([$employee['id'], $employee['first_name'] . ' ' . $employee['last_name'], 
                                     $employee['employment_type'], $hours, $rate, $gross_pay, $paye, $nhif, 
                                     $nssf, 0, $total_deductions, $net_pay, $pay_date, $pay_period, $deduction_data])) {
                $generated_count++;
            }
        }
    }
    
    echo "<script>showAlert('Generated payroll for $generated_count employees');</script>";
}

// Helper functions for Kenyan tax calculations
function calculatePAYE($gross_pay) {
    // Simplified PAYE calculation (Kenya rates)
    $monthly_gross = $gross_pay;
    $paye = 0;
    
    if ($monthly_gross <= 24000) {
        $paye = $monthly_gross * 0.10;
    } elseif ($monthly_gross <= 32333) {
        $paye = 2400 + ($monthly_gross - 24000) * 0.25;
    } else {
        $paye = 4483.25 + ($monthly_gross - 32333) * 0.30;
    }
    
    // Less personal relief
    $paye = max(0, $paye - 2400);
    
    return round($paye, 2);
}

function calculateNHIF($gross_pay) {
    // NHIF rates based on gross pay
    if ($gross_pay <= 5999) return 150;
    elseif ($gross_pay <= 7999) return 300;
    elseif ($gross_pay <= 11999) return 400;
    elseif ($gross_pay <= 14999) return 500;
    elseif ($gross_pay <= 19999) return 600;
    elseif ($gross_pay <= 24999) return 750;
    elseif ($gross_pay <= 29999) return 850;
    elseif ($gross_pay <= 34999) return 900;
    elseif ($gross_pay <= 39999) return 950;
    elseif ($gross_pay <= 44999) return 1000;
    elseif ($gross_pay <= 49999) return 1100;
    elseif ($gross_pay <= 59999) return 1200;
    elseif ($gross_pay <= 69999) return 1300;
    elseif ($gross_pay <= 79999) return 1400;
    elseif ($gross_pay <= 89999) return 1500;
    elseif ($gross_pay <= 99999) return 1600;
    else return 1700;
}

function calculateNSSF($gross_pay) {
    // NSSF contribution (6% of gross pay, max KSh 2,160)
    return min(2160, $gross_pay * 0.06);
}

// Handle deduction templates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveDeductionTemplate'])) {
    $template_name = trim($_POST['template_name']);
    $tax_type = $_POST['tax_type'];
    $tax_value = floatval($_POST['tax']);
    $insurance_type = $_POST['insurance_type'];
    $insurance_value = floatval($_POST['insurance']);
    $retirement_type = $_POST['retirement_type'];
    $retirement_value = floatval($_POST['retirement']);
    $other_type = $_POST['other_type'];
    $other_value = floatval($_POST['other_deduction']);
    
    $stmt = $pdo->prepare("INSERT INTO deduction_templates (name, tax_type, tax_value, insurance_type, insurance_value, 
                           retirement_type, retirement_value, other_type, other_value) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$template_name, $tax_type, $tax_value, $insurance_type, $insurance_value, 
                      $retirement_type, $retirement_value, $other_type, $other_value])) {
        echo "<script>showAlert('Deduction template saved successfully');</script>";
    } else {
        echo "<script>showAlert('Error saving deduction template');</script>";
    }
}

// Process form submission for adding or updating a payroll record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addPayroll']) || isset($_POST['updatePayroll'])) {
        $employee_id = intval($_POST['payroll_employee_id']);
        $employee_name = trim($_POST['employee_name']);
        $employee_type = $_POST['employee_type'];
        $hours = floatval($_POST['hours']);
        $rate = floatval($_POST['rate']);
        $gross_pay = floatval($_POST['gross_pay']);
        $pay_period = $_POST['pay_period'];
        
        // Deductions
        $tax_type = $_POST['tax_type'];
        $tax_value = floatval($_POST['tax']);
        $tax = ($tax_type === 'percentage') ? ($gross_pay * $tax_value / 100) : $tax_value;
        
        $insurance_type = $_POST['insurance_type'];
        $insurance_value = floatval($_POST['insurance']);
        $insurance = ($insurance_type === 'percentage') ? ($gross_pay * $insurance_value / 100) : $insurance_value;
        
        $retirement_type = $_POST['retirement_type'];
        $retirement_value = floatval($_POST['retirement']);
        $retirement = ($retirement_type === 'percentage') ? ($gross_pay * $retirement_value / 100) : $retirement_value;
        
        $other_type = $_POST['other_type'];
        $other_value = floatval($_POST['other_deduction']);
        $other_deduction = ($other_type === 'percentage') ? ($gross_pay * $other_value / 100) : $other_value;
        
        $total_deductions = $tax + $insurance + $retirement + $other_deduction;
        $net_pay = $gross_pay - $total_deductions;
        $pay_date = $_POST['pay_date'];
        
        $deduction_data = json_encode([
            'tax' => ['type' => $tax_type, 'value' => $tax_value, 'calculated' => $tax],
            'insurance' => ['type' => $insurance_type, 'value' => $insurance_value, 'calculated' => $insurance],
            'retirement' => ['type' => $retirement_type, 'value' => $retirement_value, 'calculated' => $retirement],
            'other' => ['type' => $other_type, 'value' => $other_value, 'calculated' => $other_deduction]
        ]);
        
        if (isset($_POST['addPayroll'])) {
            $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, employee_name, employee_type, hours, rate, gross_pay, 
                                  tax, insurance, retirement, other_deduction, total_deductions, net_pay, pay_date, 
                                  pay_period, deduction_data, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if($stmt->execute([$employee_id, $employee_name, $employee_type, $hours, $rate, $gross_pay, 
                              $tax, $insurance, $retirement, $other_deduction, $total_deductions, $net_pay, 
                              $pay_date, $pay_period, $deduction_data])){
                echo "<script>showAlert('Payroll record added successfully');</script>";
            } else {
                echo "<script>showAlert('Error adding payroll record');</script>";
            }
        } elseif (isset($_POST['updatePayroll'])) {
            $id = intval($_POST['record_id']);
            $stmt = $pdo->prepare("UPDATE payroll SET employee_id = ?, employee_name = ?, employee_type = ?, hours = ?, 
                                   rate = ?, gross_pay = ?, tax = ?, insurance = ?, retirement = ?, other_deduction = ?, 
                                   total_deductions = ?, net_pay = ?, pay_date = ?, pay_period = ?, deduction_data = ? 
                                   WHERE id = ?");
            if($stmt->execute([$employee_id, $employee_name, $employee_type, $hours, $rate, $gross_pay, 
                              $tax, $insurance, $retirement, $other_deduction, $total_deductions, $net_pay, 
                              $pay_date, $pay_period, $deduction_data, $id])){
                echo "<script>showAlert('Payroll record updated successfully');</script>";
            } else {
                echo "<script>showAlert('Error updating payroll record');</script>";
            }
        }
    } elseif (isset($_POST['deletePayroll'])) {
        $id = intval($_POST['record_id']);
        $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
        if($stmt->execute([$id])){
            echo "<script>showAlert('Payroll record deleted successfully');</script>";
        } else {
            echo "<script>showAlert('Error deleting payroll record');</script>";
        }
    } elseif (isset($_POST['deleteTemplate'])) {
        $id = intval($_POST['template_id']);
        $stmt = $pdo->prepare("DELETE FROM deduction_templates WHERE id = ?");
        if($stmt->execute([$id])){
            echo "<script>showAlert('Deduction template deleted successfully');</script>";
        } else {
            echo "<script>showAlert('Error deleting deduction template');</script>";
        }
    }
}

// Handle edit request
$editRecord = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT p.*, e.first_name, e.last_name FROM payroll p 
                          LEFT JOIN employees e ON p.employee_id = e.id WHERE p.id = ?");
    $stmt->execute([$editId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($editRecord['deduction_data']) && !empty($editRecord['deduction_data'])) {
        $deductionData = json_decode($editRecord['deduction_data'], true);
    }
}

// Fetch data
$stmt = $pdo->prepare("SELECT * FROM employees ORDER BY first_name, last_name");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM deduction_templates ORDER BY name");
$stmt->execute();
$deductionTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payroll records with employee details
$stmt = $pdo->prepare("SELECT p.*, COALESCE(CONCAT(e.first_name, ' ', e.last_name), p.employee_name) as full_name,
                       e.department, e.position 
                       FROM payroll p 
                       LEFT JOIN employees e ON p.employee_id = e.id 
                       ORDER BY p.pay_date DESC, p.created_at DESC");
$stmt->execute();
$payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for filtering
$stmt = $pdo->prepare("SELECT DISTINCT department FROM employees WHERE status = 'active' ORDER BY department");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-content {
    background-color: #fefefe;
    margin: 2% auto;
    padding: 0;
    border: none;
    border-radius: 8px;
    width: 90%;
    max-width: 1200px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg,rgb(226, 227, 232) 0%,rgb(88, 163, 255) 100%);
    color: white;
    padding: 20px;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
}

.close:hover {
    opacity: 0.7;
}

.employee-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.form-section h4 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

.employees-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.employees-table th,
.employees-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.employees-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.employees-table tr:hover {
    background-color: #f5f5f5;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.bulk-payroll-section {
    background: #e8f4fd;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #2196F3;
}

.deduction-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.deduction-controls select {
    min-width: 120px;
}

.deduction-controls input {
    min-width: 100px;
}

.calculated-amount {
    background: #e8f5e8;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    color: #2e7d32;
    min-width: 80px;
    text-align: center;
}

.payroll-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-card {
    background: linear-gradient(135deg,rgb(226, 227, 232) 0%,rgb(88, 163, 255) 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.summary-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
}

.summary-card .amount {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
}

.filter-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.employee-select-section {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #ffc107;
}

.employee-modal-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.employee-modal-tab {
    padding: 10px 20px;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.employee-modal-tab.active {
    border-bottom-color: #667eea;
    color: #667eea;
    font-weight: bold;
}

.employee-modal-tab:hover {
    background-color: #f8f9fa;
}

.employee-search {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-edit {
    background-color: #17a2b8;
    color: white;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
}

.employment-type-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.type-monthly {
    background-color: #d1ecf1;
    color: #0c5460;
}

.type-hourly {
    background-color: #d4edda;
    color: #155724;
}

.type-daily {
    background-color: #fff3cd;
    color: #856404;
}

@media (max-width: 768px) {
    .employee-form {
        grid-template-columns: 1fr;
    }
    
    .deduction-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .employee-modal-tabs {
        flex-wrap: wrap;
    }
}
</style>

<h2>School Payroll Management System</h2>

<!-- Payroll Summary -->
<div class="payroll-summary">
    <div class="summary-card">
        <h4>Total Employees</h4>
        <p class="amount"><?php echo count($employees); ?></p>
    </div>
    <div class="summary-card">
        <h4>Active Employees</h4>
        <p class="amount"><?php echo count(array_filter($employees, function($e) { return $e['status'] === 'active'; })); ?></p>
    </div>
    <div class="summary-card">
        <h4>This Month's Payroll</h4>
        <p class="amount">
            <?php 
            $currentMonth = date('Y-m');
            $monthlyTotal = 0;
            foreach($payrollRecords as $record) {
                if(strpos($record['pay_date'], $currentMonth) === 0) {
                    $monthlyTotal += $record['net_pay'];
                }
            }
            echo 'KSh ' . number_format($monthlyTotal, 2);
            ?>
        </p>
    </div>
    <div class="summary-card">
        <h4>Total Payroll Records</h4>
        <p class="amount"><?php echo count($payrollRecords); ?></p>
    </div>
    <div class="summary-card">
        <h4>Total Gross Pay</h4>
        <p class="amount">
            <?php 
            $totalGross = 0;
            foreach($payrollRecords as $record) {
                $totalGross += $record['gross_pay'];
            }
            echo 'KSh ' . number_format($totalGross, 2);
            ?>
        </p>
    </div>

    <div class="summary-card">
        <h4>Total Deductions</h4>
        <p class="amount">
            <?php 
            $totalDeductions = 0;
            foreach($payrollRecords as $record) {
                $totalDeductions += $record['total_deductions'];
            }
            echo 'KSh ' . number_format($totalDeductions, 2);
            ?>
        </p>
    </div>
</div>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link <?php echo (!isset($_GET['edit']) && !isset($_GET['templates'])) ? 'active' : ''; ?>" onclick="openTab(event, 'recordsTab')">Payroll Records</button>
        <button class="tab-link <?php echo (isset($_GET['edit'])) ? 'active' : ''; ?>" onclick="openTab(event, 'addPayrollTab')"><?php echo $editRecord ? 'Edit' : 'Add'; ?> Payroll Entry</button>
        <button class="tab-link" onclick="openTab(event, 'bulkPayrollTab')">Bulk Payroll Generation</button>
        <button class="tab-link <?php echo (isset($_GET['templates'])) ? 'active' : ''; ?>" onclick="openTab(event, 'deductionTemplatesTab')">Deduction Templates</button>
        <button class="tab-link" onclick="openEmployeeModal()">Manage Employees</button>
    </div>
    
    <!-- Payroll Records Tab -->
    <div id="recordsTab" class="tab-content" style="display: <?php echo (!isset($_GET['edit']) && !isset($_GET['templates'])) ? 'block' : 'none'; ?>">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Payroll Records</h3>
                <div class="filter-section" style="margin: 0;">
                    <label>Filter by Period:</label>
                    <input type="month" id="periodFilter" onchange="filterPayrollRecords()">
                    <label>Department:</label>
                    <select id="deptFilter" onchange="filterPayrollRecords()">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="clearFilters()" class="btn btn-sm btn-secondary">Clear Filters</button>
                </div>
            </div>
            
            <?php if(count($payrollRecords) > 0): ?>
            <div style="overflow-x: auto;">
                <table id="payrollTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Type</th>
                            <th>Period</th>
                            <th>Hours</th>
                            <th>Rate</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Pay Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payrollRecords as $record): ?>
                        <tr data-period="<?php echo date('Y-m', strtotime($record['pay_date'])); ?>" 
                            data-department="<?php echo htmlspecialchars($record['department'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($record['id']); ?></td>
                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($record['position'] ?? 'N/A'); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($record['employee_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($record['pay_period'] ?? date('Y-m', strtotime($record['pay_date']))); ?></td>
                            <td><?php echo htmlspecialchars($record['hours']); ?></td>
                            <td>KSh <?php echo number_format($record['rate'], 2); ?></td>
                            <td>KSh <?php echo number_format($record['gross_pay'], 2); ?></td>
                            <td>KSh <?php echo number_format($record['total_deductions'], 2); ?></td>
                            <td><strong>KSh <?php echo number_format($record['net_pay'], 2); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($record['pay_date'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="?edit=<?php echo $record['id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this payroll record?');">
                                        <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                        <button type="submit" name="deletePayroll" class="btn btn-sm btn-delete">Delete</button>
                                    </form>
                                    <button onclick="printPayslip(<?php echo $record['id']; ?>)" class="btn btn-sm btn-secondary">Print</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <p>No payroll records found. Start by adding employees and generating payroll records.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Payroll Tab -->
    <div id="addPayrollTab" class="tab-content" style="display: <?php echo (isset($_GET['edit'])) ? 'block' : 'none'; ?>">
        <div class="card">
            <h3><?php echo $editRecord ? 'Edit' : 'Add'; ?> Payroll Entry</h3>
            
            <?php if (!$editRecord): ?>
            <div class="employee-select-section">
                <h4>Select Employee</h4>
                <select id="employeeSelect" onchange="loadEmployeeData()" class="form-control">
                    <option value="">Choose an employee...</option>
                    <?php foreach($employees as $emp): ?>
                    <?php if($emp['status'] === 'active'): ?>
                    <option value="<?php echo $emp['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>"
                            data-type="<?php echo htmlspecialchars($emp['employment_type']); ?>"
                            data-salary="<?php echo $emp['basic_salary']; ?>"
                            data-rate="<?php echo $emp['hourly_rate']; ?>">
                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                    </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <form method="post" onsubmit="return validatePayrollForm()">
                <?php if ($editRecord): ?>
                <input type="hidden" name="record_id" value="<?php echo $editRecord['id']; ?>">
                <?php endif; ?>
                
                <div class="employee-form">
                    <div class="form-section">
                        <h4>Employee Information</h4>
                        <div class="form-group">
                            <label>Employee ID</label>
                            <input type="hidden" name="payroll_employee_id" id="payrollEmployeeId" 
                                   value="<?php echo $editRecord ? $editRecord['employee_id'] : ''; ?>">
                            <input type="text" name="employee_name" id="employeeName" 
                                   value="<?php echo $editRecord ? htmlspecialchars($editRecord['employee_name']) : ''; ?>" 
                                   placeholder="Employee Name" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Employment Type</label>
                            <select name="employee_type" id="employeeType" required class="form-control" onchange="updatePayCalculation()">
                                <option value="">Select Type</option>
                                <option value="monthly" <?php echo ($editRecord && $editRecord['employee_type'] === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                <option value="hourly" <?php echo ($editRecord && $editRecord['employee_type'] === 'hourly') ? 'selected' : ''; ?>>Hourly</option>
                                <option value="daily" <?php echo ($editRecord && $editRecord['employee_type'] === 'daily') ? 'selected' : ''; ?>>Daily</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pay Period</label>
                            <input type="month" name="pay_period" 
                                   value="<?php echo $editRecord ? htmlspecialchars($editRecord['pay_period']) : date('Y-m'); ?>" 
                                   required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Pay Date</label>
                            <input type="date" name="pay_date" 
                                   value="<?php echo $editRecord ? $editRecord['pay_date'] : date('Y-m-d'); ?>" 
                                   required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Pay Calculation</h4>
                        <div class="form-group" id="hoursGroup">
                            <label>Hours/Days Worked</label>
                            <input type="number" name="hours" id="hours" step="0.01" 
                                   value="<?php echo $editRecord ? $editRecord['hours'] : '0'; ?>" 
                                   class="form-control" onchange="calculatePay()">
                        </div>
                        <div class="form-group">
                            <label>Rate (per hour/day/month)</label>
                            <input type="number" name="rate" id="rate" step="0.01" 
                                   value="<?php echo $editRecord ? $editRecord['rate'] : '0'; ?>" 
                                   class="form-control" onchange="calculatePay()">
                        </div>
                        <div class="form-group">
                            <label>Gross Pay</label>
                            <input type="number" name="gross_pay" id="grossPay" step="0.01" 
                                   value="<?php echo $editRecord ? $editRecord['gross_pay'] : '0'; ?>" 
                                   class="form-control" onchange="calculateDeductions()">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Deductions</h4>
                        
                        <!-- Tax/PAYE -->
                        <div class="form-group">
                            <label>Tax (PAYE)</label>
                            <div class="deduction-controls">
                                <select name="tax_type" id="taxType" onchange="calculateDeductions()" class="form-control">
                                    <option value="calculated">Auto Calculate</option>
                                    <option value="percentage" <?php echo ($editRecord && isset($deductionData['tax']['type']) && $deductionData['tax']['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                    <option value="fixed" <?php echo ($editRecord && isset($deductionData['tax']['type']) && $deductionData['tax']['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                </select>
                                <input type="number" name="tax" id="taxValue" step="0.01" 
                                       value="<?php echo ($editRecord && isset($deductionData['tax']['value'])) ? $deductionData['tax']['value'] : '0'; ?>" 
                                       class="form-control" onchange="calculateDeductions()">
                                <span class="calculated-amount" id="taxCalculated">KSh 0.00</span>
                            </div>
                        </div>
                        
                        <!-- Insurance/NHIF -->
                        <div class="form-group">
                            <label>Insurance (NHIF)</label>
                            <div class="deduction-controls">
                                <select name="insurance_type" id="insuranceType" onchange="calculateDeductions()" class="form-control">
                                    <option value="calculated">Auto Calculate</option>
                                    <option value="percentage" <?php echo ($editRecord && isset($deductionData['insurance']['type']) && $deductionData['insurance']['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                    <option value="fixed" <?php echo ($editRecord && isset($deductionData['insurance']['type']) && $deductionData['insurance']['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                </select>
                                <input type="number" name="insurance" id="insuranceValue" step="0.01" 
                                       value="<?php echo ($editRecord && isset($deductionData['insurance']['value'])) ? $deductionData['insurance']['value'] : '0'; ?>" 
                                       class="form-control" onchange="calculateDeductions()">
                                <span class="calculated-amount" id="insuranceCalculated">KSh 0.00</span>
                            </div>
                        </div>
                        
                        <!-- Retirement/NSSF -->
                        <div class="form-group">
                            <label>Retirement (NSSF)</label>
                            <div class="deduction-controls">
                                <select name="retirement_type" id="retirementType" onchange="calculateDeductions()" class="form-control">
                                    <option value="calculated">Auto Calculate</option>
                                    <option value="percentage" <?php echo ($editRecord && isset($deductionData['retirement']['type']) && $deductionData['retirement']['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                    <option value="fixed" <?php echo ($editRecord && isset($deductionData['retirement']['type']) && $deductionData['retirement']['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                </select>
                                <input type="number" name="retirement" id="retirementValue" step="0.01" 
                                       value="<?php echo ($editRecord && isset($deductionData['retirement']['value'])) ? $deductionData['retirement']['value'] : '0'; ?>" 
                                       class="form-control" onchange="calculateDeductions()">
                                <span class="calculated-amount" id="retirementCalculated">KSh 0.00</span>
                            </div>
                        </div>
                        
                        <!-- Other Deductions -->
                        <div class="form-group">
                            <label>Other Deductions</label>
                            <div class="deduction-controls">
                                <select name="other_type" id="otherType" onchange="calculateDeductions()" class="form-control">
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage" <?php echo ($editRecord && isset($deductionData['other']['type']) && $deductionData['other']['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                </select>
                                <input type="number" name="other_deduction" id="otherValue" step="0.01" 
                                       value="<?php echo ($editRecord && isset($deductionData['other']['value'])) ? $deductionData['other']['value'] : '0'; ?>" 
                                       class="form-control" onchange="calculateDeductions()">
                                <span class="calculated-amount" id="otherCalculated">KSh 0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Summary</h4>
                        <div class="form-group">
                            <label>Total Deductions</label>
                            <input type="text" id="totalDeductions" readonly class="form-control" style="font-weight: bold; background-color: #f8f9fa;">
                        </div>
                        <div class="form-group">
                            <label>Net Pay</label>
                            <input type="text" id="netPay" readonly class="form-control" style="font-weight: bold; background-color: #e8f5e8; color: #2e7d32;">
                        </div>
                        
                        <div class="form-group">
                            <label>Apply Deduction Template (Optional)</label>
                            <select id="templateSelect" onchange="applyDeductionTemplate()" class="form-control">
                                <option value="">Choose a template...</option>
                                <?php foreach($deductionTemplates as $template): ?>
                                <option value="<?php echo $template['id']; ?>" 
                                        data-tax-type="<?php echo $template['tax_type']; ?>"
                                        data-tax-value="<?php echo $template['tax_value']; ?>"
                                        data-insurance-type="<?php echo $template['insurance_type']; ?>"
                                        data-insurance-value="<?php echo $template['insurance_value']; ?>"
                                        data-retirement-type="<?php echo $template['retirement_type']; ?>"
                                        data-retirement-value="<?php echo $template['retirement_value']; ?>"
                                        data-other-type="<?php echo $template['other_type']; ?>"
                                        data-other-value="<?php echo $template['other_value']; ?>">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="<?php echo $editRecord ? 'updatePayroll' : 'addPayroll'; ?>" class="btn btn-primary">
                        <?php echo $editRecord ? 'Update' : 'Add'; ?> Payroll Record
                    </button>
                    <a href="payroll.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Payroll Generation Tab -->
    <div id="bulkPayrollTab" class="tab-content">
        <div class="card">
            <div class="bulk-payroll-section">
                <h3>🚀 Bulk Payroll Generation</h3>
                <p>Generate payroll records for multiple employees at once. This will create payroll entries for all active employees based on their employment type and salary information.</p>
                
                <form method="post" onsubmit="return confirm('This will generate payroll records for selected employees. Continue?')">
                    <div class="employee-form">
                        <div class="form-section">
                            <h4>Generation Settings</h4>
                            <div class="form-group">
                                <label>Pay Period</label>
                                <input type="month" name="pay_period" value="<?php echo date('Y-m'); ?>" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Pay Date</label>
                                <input type="date" name="bulk_pay_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Department Filter</label>
                                <select name="department_filter" class="form-control">
                                    <option value="all">All Departments</option>
                                    <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Generation Rules</h4>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li><strong>Monthly Employees:</strong> Basic salary will be used as gross pay</li>
                                <li><strong>Hourly Employees:</strong> 40 hours × hourly rate (can be edited later)</li>
                                <li><strong>Daily Employees:</strong> 22 days × daily rate (can be edited later)</li>
                                <li><strong>Deductions:</strong> PAYE, NHIF, and NSSF will be auto-calculated</li>
                                <li><strong>Existing Records:</strong> Will be skipped (no duplicates)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" name="generateBulkPayroll" class="btn btn-primary">
                            Generate Bulk Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deduction Templates Tab -->
    <div id="deductionTemplatesTab" class="tab-content" style="display: <?php echo (isset($_GET['templates'])) ? 'block' : 'none'; ?>">
        <div class="card">
            <h3>Deduction Templates</h3>
            <p>Create and manage deduction templates to quickly apply standard deduction settings to payroll entries.</p>
            
            <form method="post">
                <div class="employee-form">
                    <div class="form-section">
                        <h4>Create New Template</h4>
                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" name="template_name" placeholder="e.g., Standard Teacher Deductions" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Tax Settings</h4>
                        <div class="deduction-controls">
                            <select name="tax_type" class="form-control">
                                <option value="calculated">Auto Calculate PAYE</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <input type="number" name="tax" step="0.01" placeholder="0.00" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Insurance Settings</h4>
                        <div class="deduction-controls">
                            <select name="insurance_type" class="form-control">
                                <option value="calculated">Auto Calculate NHIF</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <input type="number" name="insurance" step="0.01" placeholder="0.00" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Retirement Settings</h4>
                        <div class="deduction-controls">
                            <select name="retirement_type" class="form-control">
                                <option value="calculated">Auto Calculate NSSF</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <input type="number" name="retirement" step="0.01" placeholder="0.00" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Other Deductions</h4>
                        <div class="deduction-controls">
                            <select name="other_type" class="form-control">
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                            <input type="number" name="other_deduction" step="0.01" placeholder="0.00" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="saveDeductionTemplate" class="btn btn-primary">Save Template</button>
                </div>
            </form>
            
            <!-- Existing Templates -->
            <?php if(count($deductionTemplates) > 0): ?>
            <div style="margin-top: 40px;">
                <h4>Existing Templates</h4>
                <div style="overflow-x: auto;">
                    <table class="employees-table">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Tax</th>
                                <th>Insurance</th>
                                <th>Retirement</th>
                                <th>Other</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($deductionTemplates as $template): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($template['name']); ?></strong></td>
                                <td>
                                    <?php echo ucfirst($template['tax_type']); ?>
                                    <?php if($template['tax_value'] > 0): ?>
                                        (<?php echo $template['tax_type'] === 'percentage' ? $template['tax_value'].'%' : 'KSh '.number_format($template['tax_value'], 2); ?>)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo ucfirst($template['insurance_type']); ?>
                                    <?php if($template['insurance_value'] > 0): ?>
                                        (<?php echo $template['insurance_type'] === 'percentage' ? $template['insurance_value'].'%' : 'KSh '.number_format($template['insurance_value'], 2); ?>)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo ucfirst($template['retirement_type']); ?>
                                    <?php if($template['retirement_value'] > 0): ?>
                                        (<?php echo $template['retirement_type'] === 'percentage' ? $template['retirement_value'].'%' : 'KSh '.number_format($template['retirement_value'], 2); ?>)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo ucfirst($template['other_type']); ?>
                                    <?php if($template['other_value'] > 0): ?>
                                        (<?php echo $template['other_type'] === 'percentage' ? $template['other_value'].'%' : 'KSh '.number_format($template['other_value'], 2); ?>)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Delete this template?');">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <button type="submit" name="deleteTemplate" class="btn btn-sm btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Employee Management Modal -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Employee Management</h2>
            <button class="close" onclick="closeEmployeeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="employee-modal-tabs">
                <button class="employee-modal-tab active" onclick="switchEmployeeTab(event, 'employeeListTab')">Employee List</button>
                <button class="employee-modal-tab" onclick="switchEmployeeTab(event, 'addEmployeeTab')">Add Employee</button>
            </div>
            
            <!-- Employee List Tab -->
            <div id="employeeListTab" class="employee-tab-content">
                <div style="margin-bottom: 20px;">
                    <input type="text" id="employeeSearch" class="employee-search" placeholder="Search employees by name, ID, or department..." onkeyup="searchEmployees()">
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="employees-table" id="employeesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Type</th>
                                <th>Salary/Rate</th>
                                <th>Status</th>
                                <th>Hire Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($employees as $employee): ?>
                            <tr data-employee-search="<?php echo strtolower($employee['first_name'] . ' ' . $employee['last_name'] . ' ' . $employee['employee_id'] . ' ' . $employee['department']); ?>">
                                <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($employee['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                <td>
                                    <span class="employment-type-badge type-<?php echo $employee['employment_type']; ?>">
                                        <?php echo ucfirst($employee['employment_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($employee['employment_type'] === 'monthly'): ?>
                                        KSh <?php echo number_format($employee['basic_salary'], 2); ?>/month
                                    <?php else: ?>
                                        KSh <?php echo number_format($employee['hourly_rate'], 2); ?>/<?php echo $employee['employment_type'] === 'hourly' ? 'hour' : 'day'; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $employee['status']; ?>">
                                        <?php echo ucfirst($employee['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($employee['hire_date'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button onclick="editEmployee(<?php echo $employee['id']; ?>)" class="btn btn-sm btn-edit">Edit</button>
                                        <?php if($employee['status'] === 'active'): ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Deactivate this employee?');">
                                            <input type="hidden" name="emp_id" value="<?php echo $employee['id']; ?>">
                                            <button type="submit" name="deleteEmployee" class="btn btn-sm btn-delete">Deactivate</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Add Employee Tab -->
            <div id="addEmployeeTab" class="employee-tab-content" style="display: none;">
                <form method="post" id="employeeForm" onsubmit="return validateEmployeeForm()">
                    <input type="hidden" name="emp_id" id="editEmployeeId">
                    
                    <div class="employee-form">
                        <div class="form-section">
                            <h4>Personal Information</h4>
                            <div class="form-group">
                                <label>Employee ID</label>
                                <input type="text" name="employee_id" id="employeeId" required class="form-control" placeholder="e.g., EMP001">
                            </div>
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" id="firstName" required class="form-control" placeholder="First Name">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" id="lastName" required class="form-control" placeholder="Last Name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="email" required class="form-control" placeholder="employee@school.edu">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" placeholder="0712345678">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Employment Details</h4>
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department" id="department" required class="form-control">
                                    <option value="">Select Department</option>
                                    <option value="Administration">Administration</option>
                                    <option value="Teaching Staff">Teaching Staff</option>
                                    <option value="Support Staff">Support Staff</option>
                                    <option value="Security">Security</option>
                                    <option value="Kitchen">Kitchen</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Library">Library</option>
                                    <option value="Laboratory">Laboratory</option>
                                    <option value="Sports">Sports</option>
                                    <option value="Transport">Transport</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="position" id="position" required class="form-control" placeholder="e.g., Mathematics Teacher">
                            </div>
                            <div class="form-group">
                                <label>Employment Type</label>
                                <select name="employment_type" id="employmentType" required class="form-control" onchange="toggleSalaryFields()">
                                    <option value="">Select Type</option>
                                    <option value="monthly">Monthly Salary</option>
                                    <option value="hourly">Hourly Rate</option>
                                    <option value="daily">Daily Rate</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hire Date</label>
                                <input type="date" name="hire_date" id="hireDate" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="status" required class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Compensation</h4>
                            <div class="form-group" id="basicSalaryGroup">
                                <label>Basic Salary (Monthly)</label>
                                <input type="number" name="basic_salary" id="basicSalary" step="0.01" class="form-control" placeholder="50000.00">
                            </div>
                            <div class="form-group" id="hourlyRateGroup">
                                <label>Hourly/Daily Rate</label>
                                <input type="number" name="hourly_rate" id="hourlyRate" step="0.01" class="form-control" placeholder="500.00">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Banking & Statutory Information</h4>
                            <div class="form-group">
                                <label>Bank Account Number</label>
                                <input type="text" name="bank_account" id="bankAccount" class="form-control" placeholder="1234567890">
                            </div>
                            <div class="form-group">
                                <label>KRA PIN</label>
                                <input type="text" name="kra_pin" id="kraPin" class="form-control" placeholder="A000000000A">
                            </div>
                            <div class="form-group">
                                <label>NHIF Number</label>
                                <input type="text" name="nhif_number" id="nhifNumber" class="form-control" placeholder="1234567890">
                            </div>
                            <div class="form-group">
                                <label>NSSF Number</label>
                                <input type="text" name="nssf_number" id="nssfNumber" class="form-control" placeholder="1234567890">
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" name="addEmployee" id="employeeSubmitBtn" class="btn btn-primary">Add Employee</button>
                        <button type="button" onclick="resetEmployeeForm()" class="btn btn-secondary">Reset Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Tab management
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

// Employee modal management
function openEmployeeModal() {
    document.getElementById('employeeModal').style.display = 'block';
}

function closeEmployeeModal() {
    document.getElementById('employeeModal').style.display = 'none';
}

function switchEmployeeTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("employee-tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("employee-modal-tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

// Employee search functionality
function searchEmployees() {
    var input = document.getElementById('employeeSearch');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('employeesTable');
    var rows = table.getElementsByTagName('tr');
    
    for (var i = 1; i < rows.length; i++) {
        var searchData = rows[i].getAttribute('data-employee-search');
        if (searchData && searchData.indexOf(filter) > -1) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}

// Employee form management
function toggleSalaryFields() {
    var empType = document.getElementById('employmentType').value;
    var basicSalaryGroup = document.getElementById('basicSalaryGroup');
    var hourlyRateGroup = document.getElementById('hourlyRateGroup');
    
    if (empType === 'monthly') {
        basicSalaryGroup.style.display = 'block';
        hourlyRateGroup.style.display = 'none';
        document.getElementById('basicSalary').required = true;
        document.getElementById('hourlyRate').required = false;
    } else if (empType === 'hourly' || empType === 'daily') {
        basicSalaryGroup.style.display = 'none';
        hourlyRateGroup.style.display = 'block';
        document.getElementById('basicSalary').required = false;
        document.getElementById('hourlyRate').required = true;
        
        // Update label based on type
        var label = document.querySelector('#hourlyRateGroup label');
        if (empType === 'hourly') {
            label.textContent = 'Hourly Rate';
            document.getElementById('hourlyRate').placeholder = '500.00 per hour';
        } else {
            label.textContent = 'Daily Rate';
            document.getElementById('hourlyRate').placeholder = '2000.00 per day';
        }
    } else {
        basicSalaryGroup.style.display = 'none';
        hourlyRateGroup.style.display = 'none';
        document.getElementById('basicSalary').required = false;
        document.getElementById('hourlyRate').required = false;
    }
}

function validateEmployeeForm() {
    var empType = document.getElementById('employmentType').value;
    var basicSalary = parseFloat(document.getElementById('basicSalary').value);
    var hourlyRate = parseFloat(document.getElementById('hourlyRate').value);
    
    if (empType === 'monthly' && (isNaN(basicSalary) || basicSalary <= 0)) {
        showAlert('Please enter a valid basic salary for monthly employees');
        return false;
    }
    
    if ((empType === 'hourly' || empType === 'daily') && (isNaN(hourlyRate) || hourlyRate <= 0)) {
        showAlert('Please enter a valid rate for hourly/daily employees');
        return false;
    }
    
    return true;
}

function resetEmployeeForm() {
    document.getElementById('employeeForm').reset();
    document.getElementById('editEmployeeId').value = '';
    document.getElementById('employeeSubmitBtn').name = 'addEmployee';
    document.getElementById('employeeSubmitBtn').textContent = 'Add Employee';
    toggleSalaryFields();
}

function editEmployee(empId) {
    // Get employee data and populate form
    var table = document.getElementById('employeesTable');
    var rows = table.getElementsByTagName('tr');
    
    for (var i = 1; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var actions = cells[8].innerHTML;
        
        if (actions.includes('editEmployee(' + empId + ')')) {
            // Switch to add employee tab
            switchEmployeeTab({currentTarget: document.querySelector('.employee-modal-tab:nth-child(2)')}, 'addEmployeeTab');
            
            // Populate form with employee data
            // Note: In a real application, you would fetch this data via AJAX
            // For now, we'll just set up the form for editing
            document.getElementById('editEmployeeId').value = empId;
            document.getElementById('employeeSubmitBtn').name = 'updateEmployee';
            document.getElementById('employeeSubmitBtn').textContent = 'Update Employee';
            
            showAlert('Employee editing form prepared. Please fetch and populate data via AJAX for full functionality.');
            break;
        }
    }
}

// Payroll form management
function loadEmployeeData() {
    var select = document.getElementById('employeeSelect');
    var selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('payrollEmployeeId').value = selectedOption.value;
        document.getElementById('employeeName').value = selectedOption.getAttribute('data-name');
        document.getElementById('employeeType').value = selectedOption.getAttribute('data-type');
        
        var empType = selectedOption.getAttribute('data-type');
        var rate = 0;
        var hours = 0;
        
        if (empType === 'monthly') {
            rate = parseFloat(selectedOption.getAttribute('data-salary'));
            hours = 1;
            document.getElementById('hoursGroup').style.display = 'none';
        } else if (empType === 'hourly') {
            rate = parseFloat(selectedOption.getAttribute('data-rate'));
            hours = 40; // Default 40 hours per week
            document.getElementById('hoursGroup').style.display = 'block';
            document.querySelector('#hoursGroup label').textContent = 'Hours Worked';
        } else if (empType === 'daily') {
            rate = parseFloat(selectedOption.getAttribute('data-rate'));
            hours = 22; // Default 22 working days per month
            document.getElementById('hoursGroup').style.display = 'block';
            document.querySelector('#hoursGroup label').textContent = 'Days Worked';
        }
        
        document.getElementById('hours').value = hours;
        document.getElementById('rate').value = rate;
        
        calculatePay();
    }
}

function updatePayCalculation() {
    var empType = document.getElementById('employeeType').value;
    var hoursGroup = document.getElementById('hoursGroup');
    
    if (empType === 'monthly') {
        hoursGroup.style.display = 'none';
        document.getElementById('hours').value = 1;
    } else {
        hoursGroup.style.display = 'block';
        if (empType === 'hourly') {
            document.querySelector('#hoursGroup label').textContent = 'Hours Worked';
            document.getElementById('hours').value = 40;
        } else if (empType === 'daily') {
            document.querySelector('#hoursGroup label').textContent = 'Days Worked';
            document.getElementById('hours').value = 22;
        }
    }
    
    calculatePay();
}

function calculatePay() {
    var hours = parseFloat(document.getElementById('hours').value) || 0;
    var rate = parseFloat(document.getElementById('rate').value) || 0;
    var grossPay = hours * rate;
    
    document.getElementById('grossPay').value = grossPay.toFixed(2);
    calculateDeductions();
}

// Kenyan tax calculation functions (JavaScript versions)
function calculateKenyanPAYE(grossPay) {
    var monthlyGross = grossPay;
    var paye = 0;
    
    if (monthlyGross <= 24000) {
        paye = monthlyGross * 0.10;
    } else if (monthlyGross <= 32333) {
        paye = 2400 + (monthlyGross - 24000) * 0.25;
    } else {
        paye = 4483.25 + (monthlyGross - 32333) * 0.30;
    }
    
    // Less personal relief
    paye = Math.max(0, paye - 2400);
    
    return Math.round(paye * 100) / 100;
}

function calculateKenyanNHIF(grossPay) {
    if (grossPay <= 5999) return 150;
    else if (grossPay <= 7999) return 300;
    else if (grossPay <= 11999) return 400;
    else if (grossPay <= 14999) return 500;
    else if (grossPay <= 19999) return 600;
    else if (grossPay <= 24999) return 750;
    else if (grossPay <= 29999) return 850;
    else if (grossPay <= 34999) return 900;
    else if (grossPay <= 39999) return 950;
    else if (grossPay <= 44999) return 1000;
    else if (grossPay <= 49999) return 1100;
    else if (grossPay <= 59999) return 1200;
    else if (grossPay <= 69999) return 1300;
    else if (grossPay <= 79999) return 1400;
    else if (grossPay <= 89999) return 1500;
    else if (grossPay <= 99999) return 1600;
    else return 1700;
}

function calculateKenyanNSSF(grossPay) {
    return Math.min(2160, grossPay * 0.06);
}

function calculateDeductions() {
    var grossPay = parseFloat(document.getElementById('grossPay').value) || 0;
    var totalDeductions = 0;
    
    // Tax calculation
    var taxType = document.getElementById('taxType').value;
    var taxValue = parseFloat(document.getElementById('taxValue').value) || 0;
    var taxCalculated = 0;
    
    if (taxType === 'calculated') {
        taxCalculated = calculateKenyanPAYE(grossPay);
    } else if (taxType === 'percentage') {
        taxCalculated = grossPay * taxValue / 100;
    } else {
        taxCalculated = taxValue;
    }
    
    document.getElementById('taxCalculated').textContent = 'KSh ' + taxCalculated.toFixed(2);
    totalDeductions += taxCalculated;
    
    // Insurance calculation
    var insuranceType = document.getElementById('insuranceType').value;
    var insuranceValue = parseFloat(document.getElementById('insuranceValue').value) || 0;
    var insuranceCalculated = 0;
    
    if (insuranceType === 'calculated') {
        insuranceCalculated = calculateKenyanNHIF(grossPay);
    } else if (insuranceType === 'percentage') {
        insuranceCalculated = grossPay * insuranceValue / 100;
    } else {
        insuranceCalculated = insuranceValue;
    }
    
    document.getElementById('insuranceCalculated').textContent = 'KSh ' + insuranceCalculated.toFixed(2);
    totalDeductions += insuranceCalculated;
    
    // Retirement calculation
    var retirementType = document.getElementById('retirementType').value;
    var retirementValue = parseFloat(document.getElementById('retirementValue').value) || 0;
    var retirementCalculated = 0;
    
    if (retirementType === 'calculated') {
        retirementCalculated = calculateKenyanNSSF(grossPay);
    } else if (retirementType === 'percentage') {
        retirementCalculated = grossPay * retirementValue / 100;
    } else {
        retirementCalculated = retirementValue;
    }
    
    document.getElementById('retirementCalculated').textContent = 'KSh ' + retirementCalculated.toFixed(2);
    totalDeductions += retirementCalculated;
    
    // Other deductions calculation
    var otherType = document.getElementById('otherType').value;
    var otherValue = parseFloat(document.getElementById('otherValue').value) || 0;
    var otherCalculated = 0;
    
    if (otherType === 'percentage') {
        otherCalculated = grossPay * otherValue / 100;
    } else {
        otherCalculated = otherValue;
    }
    
    document.getElementById('otherCalculated').textContent = 'KSh ' + otherCalculated.toFixed(2);
    totalDeductions += otherCalculated;
    
    // Update totals
    var netPay = grossPay - totalDeductions;
    document.getElementById('totalDeductions').value = 'KSh ' + totalDeductions.toFixed(2);
    document.getElementById('netPay').value = 'KSh ' + netPay.toFixed(2);
}

function applyDeductionTemplate() {
    var select = document.getElementById('templateSelect');
    var selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('taxType').value = selectedOption.getAttribute('data-tax-type');
        document.getElementById('taxValue').value = selectedOption.getAttribute('data-tax-value');
        
        document.getElementById('insuranceType').value = selectedOption.getAttribute('data-insurance-type');
        document.getElementById('insuranceValue').value = selectedOption.getAttribute('data-insurance-value');
        
        document.getElementById('retirementType').value = selectedOption.getAttribute('data-retirement-type');
        document.getElementById('retirementValue').value = selectedOption.getAttribute('data-retirement-value');
        
        document.getElementById('otherType').value = selectedOption.getAttribute('data-other-type');
        document.getElementById('otherValue').value = selectedOption.getAttribute('data-other-value');
        
        calculateDeductions();
    }
}

function validatePayrollForm() {
    var employeeId = document.getElementById('payrollEmployeeId').value;
    var grossPay = parseFloat(document.getElementById('grossPay').value);
    
    if (!employeeId) {
        showAlert('Please select an employee');
        return false;
    }
    
    if (isNaN(grossPay) || grossPay <= 0) {
        showAlert('Please enter a valid gross pay amount');
        return false;
    }
    
    return true;
}

// Payroll records filtering
function filterPayrollRecords() {
    var periodFilter = document.getElementById('periodFilter').value;
    var deptFilter = document.getElementById('deptFilter').value;
    var table = document.getElementById('payrollTable');
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var showRow = true;
        
        if (periodFilter) {
            var rowPeriod = rows[i].getAttribute('data-period');
            if (rowPeriod !== periodFilter) {
                showRow = false;
            }
        }
        
        if (deptFilter && showRow) {
            var rowDept = rows[i].getAttribute('data-department');
            if (rowDept !== deptFilter) {
                showRow = false;
            }
        }
        
        rows[i].style.display = showRow ? '' : 'none';
    }
}

function clearFilters() {
    document.getElementById('periodFilter').value = '';
    document.getElementById('deptFilter').value = '';
    filterPayrollRecords();
}

// Print payslip functionality
function printPayslip(recordId) {
    window.open('print_payslip.php?id=' + recordId, '_blank', 'width=800,height=600');
}

// Alert function
function showAlert(message) {
    alert(message);
}

// Close modal when clicked outside
window.onclick = function(event) {
    var modal = document.getElementById('employeeModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSalaryFields();
    updatePayCalculation();
    calculateDeductions();
});
</script>