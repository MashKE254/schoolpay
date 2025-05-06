<?php
// payroll.php - Enhanced Payroll Processing Page with Custom Deductions
require 'config.php';
require 'functions.php';
include 'header.php';

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
    
    // Save template to database
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
        $employee_name = trim($_POST['employee_name']);
        $employee_type = $_POST['employee_type']; // monthly or daily
        $hours = floatval($_POST['hours']);
        $rate = floatval($_POST['rate']);
        $gross_pay = $employee_type === 'monthly' ? floatval($_POST['gross_pay']) : $hours * $rate;
        
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
        
        // Calculate total deductions and net pay
        $total_deductions = $tax + $insurance + $retirement + $other_deduction;
        $net_pay = $gross_pay - $total_deductions;
        $pay_date = $_POST['pay_date'];
        
        // Store both the deduction type and the original value
        $deduction_data = json_encode([
            'tax' => ['type' => $tax_type, 'value' => $tax_value, 'calculated' => $tax],
            'insurance' => ['type' => $insurance_type, 'value' => $insurance_value, 'calculated' => $insurance],
            'retirement' => ['type' => $retirement_type, 'value' => $retirement_value, 'calculated' => $retirement],
            'other' => ['type' => $other_type, 'value' => $other_value, 'calculated' => $other_deduction]
        ]);
        
        if (isset($_POST['addPayroll'])) {
            // Add new record
            $stmt = $pdo->prepare("INSERT INTO payroll (employee_name, employee_type, hours, rate, gross_pay, 
                                  tax, insurance, retirement, other_deduction, total_deductions, net_pay, pay_date, deduction_data) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if($stmt->execute([$employee_name, $employee_type, $hours, $rate, $gross_pay, 
                              $tax, $insurance, $retirement, $other_deduction, $total_deductions, $net_pay, $pay_date, $deduction_data])){
                echo "<script>showAlert('Payroll record added successfully');</script>";
            } else {
                echo "<script>showAlert('Error adding payroll record');</script>";
            }
        } elseif (isset($_POST['updatePayroll'])) {
            // Update existing record
            $id = intval($_POST['record_id']);
            $stmt = $pdo->prepare("UPDATE payroll SET employee_name = ?, employee_type = ?, hours = ?, rate = ?, gross_pay = ?, 
                                   tax = ?, insurance = ?, retirement = ?, other_deduction = ?, total_deductions = ?, net_pay = ?, 
                                   pay_date = ?, deduction_data = ? WHERE id = ?");
            if($stmt->execute([$employee_name, $employee_type, $hours, $rate, $gross_pay, 
                              $tax, $insurance, $retirement, $other_deduction, $total_deductions, $net_pay, $pay_date, $deduction_data, $id])){
                echo "<script>showAlert('Payroll record updated successfully');</script>";
            } else {
                echo "<script>showAlert('Error updating payroll record');</script>";
            }
        }
    } elseif (isset($_POST['deletePayroll'])) {
        // Delete record
        $id = intval($_POST['record_id']);
        $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
        if($stmt->execute([$id])){
            echo "<script>showAlert('Payroll record deleted successfully');</script>";
        } else {
            echo "<script>showAlert('Error deleting payroll record');</script>";
        }
    } elseif (isset($_POST['deleteTemplate'])) {
        // Delete deduction template
        $id = intval($_POST['template_id']);
        $stmt = $pdo->prepare("DELETE FROM deduction_templates WHERE id = ?");
        if($stmt->execute([$id])){
            echo "<script>showAlert('Deduction template deleted successfully');</script>";
        } else {
            echo "<script>showAlert('Error deleting deduction template');</script>";
        }
    }
}

// Handle edit request (GET)
$editRecord = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE id = ?");
    $stmt->execute([$editId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Parse deduction data if available
    if (isset($editRecord['deduction_data']) && !empty($editRecord['deduction_data'])) {
        $deductionData = json_decode($editRecord['deduction_data'], true);
    }
}

// Fetch deduction templates
$stmt = $pdo->prepare("SELECT * FROM deduction_templates ORDER BY name");
$stmt->execute();
$deductionTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve payroll records using the helper function
$payrollRecords = getPayrollRecords($pdo);
?>

<h2>Payroll Processing</h2>
<div class="tab-container">
    <div class="tabs">
        <button class="tab-link <?php echo (!isset($_GET['edit']) && !isset($_GET['templates'])) ? 'active' : ''; ?>" onclick="openTab(event, 'recordsTab')">Payroll Records</button>
        <button class="tab-link <?php echo (isset($_GET['edit'])) ? 'active' : ''; ?>" onclick="openTab(event, 'addPayrollTab')"><?php echo $editRecord ? 'Edit' : 'Add'; ?> Payroll Entry</button>
        <button class="tab-link <?php echo (isset($_GET['templates'])) ? 'active' : ''; ?>" onclick="openTab(event, 'deductionTemplatesTab')">Deduction Templates</button>
    </div>
    
    <!-- Payroll Records Tab -->
    <div id="recordsTab" class="tab-content" style="display: <?php echo (!isset($_GET['edit']) && !isset($_GET['templates'])) ? 'block' : 'none'; ?>">
        <div class="card">
            <h3>Payroll Records</h3>
            <?php if(count($payrollRecords) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Type</th>
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
                    <tr>
                        <td><?php echo htmlspecialchars($record['id']); ?></td>
                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['employee_type']); ?></td>
                        <td><?php echo htmlspecialchars($record['hours']); ?></td>
                        <td>KSh <?php echo number_format($record['rate'], 2); ?></td>
                        <td>KSh <?php echo number_format($record['gross_pay'], 2); ?></td>
                        <td>KSh <?php echo number_format($record['total_deductions'], 2); ?></td>
                        <td>KSh <?php echo number_format($record['net_pay'], 2); ?></td>
                        <td><?php echo htmlspecialchars($record['pay_date']); ?></td>
                        <td>
                            <a href="payroll.php?edit=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                <button type="submit" name="deletePayroll" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No payroll records found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Payroll Entry Tab -->
    <div id="addPayrollTab" class="tab-content" style="display: <?php echo isset($_GET['edit']) ? 'block' : 'none'; ?>">
        <div class="card">
            <h3><?php echo $editRecord ? 'Edit' : 'Add'; ?> Payroll Entry</h3>
            
            <!-- Deduction Templates Dropdown -->
            <div class="form-group">
                <label for="deduction_template">Apply Deduction Template:</label>
                <select id="deduction_template" onchange="applyDeductionTemplate()">
                    <option value="">-- Select a Template --</option>
                    <?php foreach($deductionTemplates as $template): ?>
                    <option value="<?php echo htmlspecialchars($template['id']); ?>"
                            data-tax-type="<?php echo htmlspecialchars($template['tax_type']); ?>"
                            data-tax-value="<?php echo htmlspecialchars($template['tax_value']); ?>"
                            data-insurance-type="<?php echo htmlspecialchars($template['insurance_type']); ?>"
                            data-insurance-value="<?php echo htmlspecialchars($template['insurance_value']); ?>"
                            data-retirement-type="<?php echo htmlspecialchars($template['retirement_type']); ?>"
                            data-retirement-value="<?php echo htmlspecialchars($template['retirement_value']); ?>"
                            data-other-type="<?php echo htmlspecialchars($template['other_type']); ?>"
                            data-other-value="<?php echo htmlspecialchars($template['other_value']); ?>">
                        <?php echo htmlspecialchars($template['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <form action="payroll.php" method="post" id="payrollForm">
                <?php if($editRecord): ?>
                <input type="hidden" name="record_id" value="<?php echo $editRecord['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="employee_name">Employee Name:</label>
                    <input type="text" name="employee_name" id="employee_name" value="<?php echo $editRecord ? htmlspecialchars($editRecord['employee_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="employee_type">Employee Type:</label>
                    <select name="employee_type" id="employee_type" onchange="toggleEmployeeFields()" required>
                        <option value="daily" <?php echo ($editRecord && $editRecord['employee_type'] == 'daily') ? 'selected' : ''; ?>>Daily Worker</option>
                        <option value="monthly" <?php echo ($editRecord && $editRecord['employee_type'] == 'monthly') ? 'selected' : ''; ?>>Monthly Worker</option>
                    </select>
                </div>
                
                <div class="form-group" id="hoursField">
                    <label for="hours">Hours Worked:</label>
                    <input type="number" step="0.01" name="hours" id="hours" value="<?php echo $editRecord ? htmlspecialchars($editRecord['hours']) : ''; ?>" onchange="calculatePay()">
                </div>
                
                <div class="form-group" id="rateField">
                    <label for="rate">Hourly/Daily Rate (KSh):</label>
                    <input type="number" step="0.01" name="rate" id="rate" value="<?php echo $editRecord ? htmlspecialchars($editRecord['rate']) : ''; ?>" onchange="calculatePay()">
                </div>
                
                <div class="form-group" id="grossPayField">
                    <label for="gross_pay">Gross Pay (KSh):</label>
                    <input type="number" step="0.01" name="gross_pay" id="gross_pay" value="<?php echo $editRecord ? htmlspecialchars($editRecord['gross_pay']) : ''; ?>" onchange="updateDeductions()" required>
                </div>
                
                <h4>Deductions</h4>
                
                <!-- Tax Deduction -->
                <div class="form-group deduction-group">
                    <label for="tax">Tax Deduction:</label>
                    <div class="deduction-controls">
                        <select name="tax_type" id="tax_type" onchange="updateDeductionCalculation()">
                            <option value="fixed" <?php echo (isset($deductionData) && $deductionData['tax']['type'] == 'fixed') ? 'selected' : ''; ?>>KSh Amount</option>
                            <option value="percentage" <?php echo (isset($deductionData) && $deductionData['tax']['type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                        </select>
                        <input type="number" step="0.01" name="tax" id="tax" 
                               value="<?php echo isset($deductionData) ? htmlspecialchars($deductionData['tax']['value']) : '0.00'; ?>" 
                               onchange="updateDeductionCalculation()">
                        <span id="tax_calculated" class="calculated-amount">
                            <?php echo isset($deductionData) ? 'KSh ' . number_format($deductionData['tax']['calculated'], 2) : ''; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Insurance Deduction -->
                <div class="form-group deduction-group">
                    <label for="insurance">Insurance Deduction:</label>
                    <div class="deduction-controls">
                        <select name="insurance_type" id="insurance_type" onchange="updateDeductionCalculation()">
                            <option value="fixed" <?php echo (isset($deductionData) && $deductionData['insurance']['type'] == 'fixed') ? 'selected' : ''; ?>>KSh Amount</option>
                            <option value="percentage" <?php echo (isset($deductionData) && $deductionData['insurance']['type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                        </select>
                        <input type="number" step="0.01" name="insurance" id="insurance" 
                               value="<?php echo isset($deductionData) ? htmlspecialchars($deductionData['insurance']['value']) : '0.00'; ?>" 
                               onchange="updateDeductionCalculation()">
                        <span id="insurance_calculated" class="calculated-amount">
                            <?php echo isset($deductionData) ? 'KSh ' . number_format($deductionData['insurance']['calculated'], 2) : ''; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Retirement Deduction -->
                <div class="form-group deduction-group">
                    <label for="retirement">Retirement Deduction:</label>
                    <div class="deduction-controls">
                        <select name="retirement_type" id="retirement_type" onchange="updateDeductionCalculation()">
                            <option value="fixed" <?php echo (isset($deductionData) && $deductionData['retirement']['type'] == 'fixed') ? 'selected' : ''; ?>>KSh Amount</option>
                            <option value="percentage" <?php echo (isset($deductionData) && $deductionData['retirement']['type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                        </select>
                        <input type="number" step="0.01" name="retirement" id="retirement" 
                               value="<?php echo isset($deductionData) ? htmlspecialchars($deductionData['retirement']['value']) : '0.00'; ?>" 
                               onchange="updateDeductionCalculation()">
                        <span id="retirement_calculated" class="calculated-amount">
                            <?php echo isset($deductionData) ? 'KSh ' . number_format($deductionData['retirement']['calculated'], 2) : ''; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Other Deduction -->
                <div class="form-group deduction-group">
                    <label for="other_deduction">Other Deductions:</label>
                    <div class="deduction-controls">
                        <select name="other_type" id="other_type" onchange="updateDeductionCalculation()">
                            <option value="fixed" <?php echo (isset($deductionData) && $deductionData['other']['type'] == 'fixed') ? 'selected' : ''; ?>>KSh Amount</option>
                            <option value="percentage" <?php echo (isset($deductionData) && $deductionData['other']['type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                        </select>
                        <input type="number" step="0.01" name="other_deduction" id="other_deduction" 
                               value="<?php echo isset($deductionData) ? htmlspecialchars($deductionData['other']['value']) : '0.00'; ?>" 
                               onchange="updateDeductionCalculation()">
                        <span id="other_calculated" class="calculated-amount">
                            <?php echo isset($deductionData) ? 'KSh ' . number_format($deductionData['other']['calculated'], 2) : ''; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Save as Template -->
                <div class="form-group">
                    <div class="save-template-toggle">
                        <input type="checkbox" id="saveAsTemplate" onclick="toggleSaveTemplate()">
                        <label for="saveAsTemplate">Save these deductions as a template</label>
                    </div>
                    <div id="templateNameField" style="display: none;">
                        <input type="text" name="template_name" id="template_name" placeholder="Template Name">
                        <button type="button" class="btn btn-sm btn-success" onclick="saveDeductionTemplate()">Save Template</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="total_deductions">Total Deductions (KSh):</label>
                    <input type="number" step="0.01" name="total_deductions" id="total_deductions" value="<?php echo $editRecord ? htmlspecialchars($editRecord['total_deductions']) : '0.00'; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="net_pay">Net Pay (KSh):</label>
                    <input type="number" step="0.01" name="net_pay" id="net_pay" value="<?php echo $editRecord ? htmlspecialchars($editRecord['net_pay']) : '0.00'; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="pay_date">Pay Date:</label>
                    <input type="date" name="pay_date" id="pay_date" value="<?php echo $editRecord ? htmlspecialchars($editRecord['pay_date']) : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <?php if($editRecord): ?>
                    <input type="submit" name="updatePayroll" value="Update Payroll Record" class="btn btn-primary">
                    <a href="payroll.php" class="btn btn-secondary">Cancel</a>
                    <?php else: ?>
                    <input type="submit" name="addPayroll" value="Add Payroll Record" class="btn btn-success">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Deduction Templates Tab -->
    <div id="deductionTemplatesTab" class="tab-content" style="display: <?php echo isset($_GET['templates']) ? 'block' : 'none'; ?>">
        <div class="card">
            <h3>Deduction Templates</h3>
            <?php if(count($deductionTemplates) > 0): ?>
            <table>
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
                        <td><?php echo htmlspecialchars($template['name']); ?></td>
                        <td>
                            <?php 
                            echo ($template['tax_type'] === 'percentage') 
                                ? htmlspecialchars($template['tax_value']) . '%' 
                                : 'KSh ' . number_format($template['tax_value'], 2); 
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo ($template['insurance_type'] === 'percentage') 
                                ? htmlspecialchars($template['insurance_value']) . '%' 
                                : 'KSh ' . number_format($template['insurance_value'], 2); 
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo ($template['retirement_type'] === 'percentage') 
                                ? htmlspecialchars($template['retirement_value']) . '%' 
                                : 'KSh ' . number_format($template['retirement_value'], 2); 
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo ($template['other_type'] === 'percentage') 
                                ? htmlspecialchars($template['other_value']) . '%' 
                                : 'KSh ' . number_format($template['other_value'], 2); 
                            ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" name="deleteTemplate" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No deduction templates found. Create your first template from the Payroll Entry tab.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="payroll_scripts.js"></script>
<script>
// Add this to your payroll_scripts.js file or include it directly
function toggleEmployeeFields() {
    const employeeType = document.getElementById('employee_type').value;
    const hoursField = document.getElementById('hoursField');
    const rateField = document.getElementById('rateField');
    
    if (employeeType === 'monthly') {
        hoursField.style.display = 'none';
        document.getElementById('hours').value = '0';
        document.getElementById('grossPayField').style.display = 'block';
    } else {
        hoursField.style.display = 'block';
        rateField.style.display = 'block';
        document.getElementById('grossPayField').style.display = 'block';
    }
    
    calculatePay();
}

function calculatePay() {
    const employeeType = document.getElementById('employee_type').value;
    const hours = parseFloat(document.getElementById('hours').value) || 0;
    const rate = parseFloat(document.getElementById('rate').value) || 0;
    
    if (employeeType === 'daily') {
        const grossPay = hours * rate;
        document.getElementById('gross_pay').value = grossPay.toFixed(2);
    }
    
    updateDeductionCalculation();
}

function updateDeductionCalculation() {
    const grossPay = parseFloat(document.getElementById('gross_pay').value) || 0;
    
    // Calculate each deduction based on type (percentage or fixed)
    const taxType = document.getElementById('tax_type').value;
    const taxValue = parseFloat(document.getElementById('tax').value) || 0;
    const tax = (taxType === 'percentage') ? (grossPay * taxValue / 100) : taxValue;
    
    const insuranceType = document.getElementById('insurance_type').value;
    const insuranceValue = parseFloat(document.getElementById('insurance').value) || 0;
    const insurance = (insuranceType === 'percentage') ? (grossPay * insuranceValue / 100) : insuranceValue;
    
    const retirementType = document.getElementById('retirement_type').value;
    const retirementValue = parseFloat(document.getElementById('retirement').value) || 0;
    const retirement = (retirementType === 'percentage') ? (grossPay * retirementValue / 100) : retirementValue;
    
    const otherType = document.getElementById('other_type').value;
    const otherValue = parseFloat(document.getElementById('other_deduction').value) || 0;
    const other = (otherType === 'percentage') ? (grossPay * otherValue / 100) : otherValue;
    
    // Update calculated amounts display
    if (document.getElementById('tax_calculated')) {
        document.getElementById('tax_calculated').textContent = 'KSh ' + tax.toFixed(2);
    }
    if (document.getElementById('insurance_calculated')) {
        document.getElementById('insurance_calculated').textContent = 'KSh ' + insurance.toFixed(2);
    }
    if (document.getElementById('retirement_calculated')) {
        document.getElementById('retirement_calculated').textContent = 'KSh ' + retirement.toFixed(2);
    }
    if (document.getElementById('other_calculated')) {
        document.getElementById('other_calculated').textContent = 'KSh ' + other.toFixed(2);
    }
    
    // Calculate total deductions and net pay
    const totalDeductions = tax + insurance + retirement + other;
    document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
    
    const netPay = grossPay - totalDeductions;
    document.getElementById('net_pay').value = netPay.toFixed(2);
}

function toggleSaveTemplate() {
    const checkbox = document.getElementById('saveAsTemplate');
    const templateNameField = document.getElementById('templateNameField');
    
    if (checkbox.checked) {
        templateNameField.style.display = 'flex';
    } else {
        templateNameField.style.display = 'none';
    }
}

function saveDeductionTemplate() {
    const templateName = document.getElementById('template_name').value.trim();
    
    if (!templateName) {
        alert('Please enter a template name');
        return;
    }
    
    // Create form data and submit via AJAX
    const formData = new FormData();
    formData.append('saveDeductionTemplate', 'true');
    formData.append('template_name', templateName);
    
    // Add all deduction fields
    formData.append('tax_type', document.getElementById('tax_type').value);
    formData.append('tax', document.getElementById('tax').value);
    formData.append('insurance_type', document.getElementById('insurance_type').value);
    formData.append('insurance', document.getElementById('insurance').value);
    formData.append('retirement_type', document.getElementById('retirement_type').value);
    formData.append('retirement', document.getElementById('retirement').value);
    formData.append('other_type', document.getElementById('other_type').value);
    formData.append('other_deduction', document.getElementById('other_deduction').value);
    
    // Submit form
    fetch('payroll.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Reload the page to show the alert and refresh templates
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving template');
    });
}

function applyDeductionTemplate() {
    const select = document.getElementById('deduction_template');
    const selectedOption = select.options[select.selectedIndex];
    
    if (select.value === '') {
        return; // No template selected
    }
    
    // Get template data from data attributes
    const taxType = selectedOption.dataset.taxType;
    const taxValue = selectedOption.dataset.taxValue;
    const insuranceType = selectedOption.dataset.insuranceType;
    const insuranceValue = selectedOption.dataset.insuranceValue;
    const retirementType = selectedOption.dataset.retirementType;
    const retirementValue = selectedOption.dataset.retirementValue;
    const otherType = selectedOption.dataset.otherType;
    const otherValue = selectedOption.dataset.otherValue;
    
    // Apply values to form
    document.getElementById('tax_type').value = taxType;
    document.getElementById('tax').value = taxValue;
    document.getElementById('insurance_type').value = insuranceType;
    document.getElementById('insurance').value = insuranceValue;
    document.getElementById('retirement_type').value = retirementType;
    document.getElementById('retirement').value = retirementValue;
    document.getElementById('other_type').value = otherType;
    document.getElementById('other_deduction').value = otherValue;
    
    // Update calculations
    updateDeductionCalculation();
}

function openTab(evt, tabName) {
    // Hide all tab content
    const tabcontent = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    
    // Remove "active" class from all tab links
    const tablinks = document.getElementsByClassName("tab-link");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    
    // Show the current tab and add an "active" class to the button
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
    
    // Update URL if navigating to templates tab
    if (tabName === 'deductionTemplatesTab') {
        history.pushState(null, '', 'payroll.php?templates=1');
    } else if (tabName === 'recordsTab') {
        history.pushState(null, '', 'payroll.php');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleEmployeeFields();
    updateDeductionCalculation();
});
</script>

<?php include 'footer.php'; ?>