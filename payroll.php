<?php
// payroll.php - Enhanced Payroll Processing Page
require 'config.php';
require 'functions.php';
include 'header.php';

// Process form submission for adding or updating a payroll record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addPayroll']) || isset($_POST['updatePayroll'])) {
        $employee_name = trim($_POST['employee_name']);
        $employee_type = $_POST['employee_type']; // monthly or daily
        $hours = floatval($_POST['hours']);
        $rate = floatval($_POST['rate']);
        $gross_pay = $employee_type === 'monthly' ? floatval($_POST['gross_pay']) : $hours * $rate;
        
        // Deductions
        $tax = floatval($_POST['tax']);
        $insurance = floatval($_POST['insurance']);
        $retirement = floatval($_POST['retirement']);
        $other_deduction = floatval($_POST['other_deduction']);
        $total_deductions = $tax + $insurance + $retirement + $other_deduction;
        
        // Calculate net pay
        $net_pay = $gross_pay - $total_deductions;
        $pay_date = $_POST['pay_date'];
        
        if (isset($_POST['addPayroll'])) {
            // Add new record
            $stmt = $pdo->prepare("INSERT INTO payroll (employee_name, employee_type, hours, rate, gross_pay, tax, insurance, retirement, other_deduction, total_deductions, net_pay, pay_date) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if($stmt->execute([$employee_name, $employee_type, $hours, $rate, $gross_pay, $tax, $insurance, $retirement, $other_deduction, $total_deductions, $net_pay, $pay_date])){
                echo "<script>showAlert('Payroll record added successfully');</script>";
            } else {
                echo "<script>showAlert('Error adding payroll record');</script>";
            }
        } elseif (isset($_POST['updatePayroll'])) {
            // Update existing record
            $id = intval($_POST['record_id']);
            $stmt = $pdo->prepare("UPDATE payroll SET employee_name = ?, employee_type = ?, hours = ?, rate = ?, gross_pay = ?, 
                                   tax = ?, insurance = ?, retirement = ?, other_deduction = ?, total_deductions = ?, net_pay = ?, pay_date = ? 
                                   WHERE id = ?");
            if($stmt->execute([$employee_name, $employee_type, $hours, $rate, $gross_pay, $tax, $insurance, $retirement, $other_deduction, $total_deductions, $net_pay, $pay_date, $id])){
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
    }
}

// Handle edit request (GET)
$editRecord = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE id = ?");
    $stmt->execute([$editId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Retrieve payroll records using the helper function
$payrollRecords = getPayrollRecords($pdo);
?>

<h2>Payroll Processing</h2>
<div class="tab-container">
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'recordsTab')">Payroll Records</button>
        <button class="tab-link" onclick="openTab(event, 'addPayrollTab')"><?php echo $editRecord ? 'Edit' : 'Add'; ?> Payroll Entry</button>
    </div>
    
    <!-- Payroll Records Tab -->
    <div id="recordsTab" class="tab-content" style="display: block;">
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
                        <td>$<?php echo number_format($record['rate'], 2); ?></td>
                        <td>$<?php echo number_format($record['gross_pay'], 2); ?></td>
                        <td>$<?php echo number_format($record['total_deductions'], 2); ?></td>
                        <td>$<?php echo number_format($record['net_pay'], 2); ?></td>
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
    <div id="addPayrollTab" class="tab-content" style="display: <?php echo $editRecord ? 'block' : 'none'; ?>">
        <div class="card">
            <h3><?php echo $editRecord ? 'Edit' : 'Add'; ?> Payroll Entry</h3>
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
                    <label for="rate">Hourly/Daily Rate:</label>
                    <input type="number" step="0.01" name="rate" id="rate" value="<?php echo $editRecord ? htmlspecialchars($editRecord['rate']) : ''; ?>" onchange="calculatePay()">
                </div>
                
                <div class="form-group" id="grossPayField">
                    <label for="gross_pay">Gross Pay:</label>
                    <input type="number" step="0.01" name="gross_pay" id="gross_pay" value="<?php echo $editRecord ? htmlspecialchars($editRecord['gross_pay']) : ''; ?>" onchange="updateDeductions()" required>
                </div>
                
                <h4>Deductions</h4>
                <div class="form-group">
                    <label for="tax">Tax Deduction:</label>
                    <input type="number" step="0.01" name="tax" id="tax" value="<?php echo $editRecord ? htmlspecialchars($editRecord['tax']) : '0.00'; ?>" onchange="calculateNetPay()">
                </div>
                
                <div class="form-group">
                    <label for="insurance">Insurance Deduction:</label>
                    <input type="number" step="0.01" name="insurance" id="insurance" value="<?php echo $editRecord ? htmlspecialchars($editRecord['insurance']) : '0.00'; ?>" onchange="calculateNetPay()">
                </div>
                
                <div class="form-group">
                    <label for="retirement">Retirement Deduction:</label>
                    <input type="number" step="0.01" name="retirement" id="retirement" value="<?php echo $editRecord ? htmlspecialchars($editRecord['retirement']) : '0.00'; ?>" onchange="calculateNetPay()">
                </div>
                
                <div class="form-group">
                    <label for="other_deduction">Other Deductions:</label>
                    <input type="number" step="0.01" name="other_deduction" id="other_deduction" value="<?php echo $editRecord ? htmlspecialchars($editRecord['other_deduction']) : '0.00'; ?>" onchange="calculateNetPay()">
                </div>
                
                <div class="form-group">
                    <label for="total_deductions">Total Deductions:</label>
                    <input type="number" step="0.01" name="total_deductions" id="total_deductions" value="<?php echo $editRecord ? htmlspecialchars($editRecord['total_deductions']) : '0.00'; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="net_pay">Net Pay:</label>
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
</div>

<script>
// Open tab functionality
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

// Toggle employee type fields
function toggleEmployeeFields() {
    var employeeType = document.getElementById("employee_type").value;
    
    if (employeeType === "monthly") {
        document.getElementById("hoursField").style.display = "none";
        document.getElementById("rateField").style.display = "none";
        document.getElementById("grossPayField").style.display = "block";
    } else {
        document.getElementById("hoursField").style.display = "block";
        document.getElementById("rateField").style.display = "block";
        document.getElementById("grossPayField").style.display = "none";
    }
}

// Calculate pay based on hours and rate for daily workers
function calculatePay() {
    var employeeType = document.getElementById("employee_type").value;
    
    if (employeeType === "daily") {
        var hours = parseFloat(document.getElementById("hours").value) || 0;
        var rate = parseFloat(document.getElementById("rate").value) || 0;
        var grossPay = hours * rate;
        
        document.getElementById("gross_pay").value = grossPay.toFixed(2);
        calculateNetPay();
    }
}

// Calculate net pay based on gross pay and deductions
function calculateNetPay() {
    var grossPay = parseFloat(document.getElementById("gross_pay").value) || 0;
    var tax = parseFloat(document.getElementById("tax").value) || 0;
    var insurance = parseFloat(document.getElementById("insurance").value) || 0;
    var retirement = parseFloat(document.getElementById("retirement").value) || 0;
    var otherDeduction = parseFloat(document.getElementById("other_deduction").value) || 0;
    
    var totalDeductions = tax + insurance + retirement + otherDeduction;
    var netPay = grossPay - totalDeductions;
    
    document.getElementById("total_deductions").value = totalDeductions.toFixed(2);
    document.getElementById("net_pay").value = netPay.toFixed(2);
}

// Update deductions based on a percentage of gross pay if needed
function updateDeductions() {
    // Example: You can implement automatic tax calculation based on gross pay
    var grossPay = parseFloat(document.getElementById("gross_pay").value) || 0;
    
    // Example: Set tax as 15% of gross pay (modify as needed)
    // document.getElementById("tax").value = (grossPay * 0.15).toFixed(2);
    
    calculateNetPay();
}

// Initialize form on page load
document.addEventListener("DOMContentLoaded", function() {
    toggleEmployeeFields();
    calculateNetPay();
    
    // Make the correct tab active on page load
    <?php if($editRecord): ?>
    var tabLinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tabLinks.length; i++) {
        tabLinks[i].className = tabLinks[i].className.replace(" active", "");
    }
    tabLinks[1].className += " active";
    <?php endif; ?>
});
</script>

<?php include 'footer.php'; ?>