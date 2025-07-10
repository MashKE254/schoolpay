<?php
require 'config.php';
require 'functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Payslip ID.");
}

$record_id = intval($_GET['id']);

$stmt = $pdo->prepare("
    SELECT p.*, e.first_name, e.last_name, e.employee_id, e.kra_pin, e.nssf_number, e.nhif_number, e.position
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    WHERE p.id = ?
");
$stmt->execute([$record_id]);
$payslip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payslip) {
    die("Payslip not found.");
}

$allowances = json_decode($payslip['allowances'], true);
$deductions = json_decode($payslip['deduction_data'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip for <?php echo htmlspecialchars($payslip['employee_name']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .payslip-container { background: white; width: 800px; margin: auto; padding: 40px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; }
        .payslip-details, .employee-details { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .details-box { width: 48%; }
        .details-box p { margin: 4px 0; font-size: 14px; }
        .details-box strong { display: inline-block; width: 120px; }
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .summary-table th { background: #f9f9f9; }
        .summary-table .amount { text-align: right; }
        .summary-table .total-row { font-weight: bold; background: #f0f0f0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777; }
        @media print {
            body { background: none; }
            .payslip-container { box-shadow: none; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <div class="header">
            <h1>Bloomsfield Kindergarten and School</h1>
            <p>Your School Address, Town, Country</p>
            <h2>Payslip</h2>
        </div>

        <div class="payslip-details">
            <div class="details-box">
                <p><strong>Payslip Period:</strong> <?php echo htmlspecialchars($payslip['pay_period']); ?></p>
                <p><strong>Payment Date:</strong> <?php echo date('d M, Y', strtotime($payslip['pay_date'])); ?></p>
            </div>
        </div>

        <div class="employee-details">
             <div class="details-box">
                <p><strong>Employee Name:</strong> <?php echo htmlspecialchars($payslip['employee_name']); ?></p>
                <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($payslip['employee_id']); ?></p>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($payslip['position']); ?></p>
             </div>
             <div class="details-box">
                <p><strong>KRA PIN:</strong> <?php echo htmlspecialchars($payslip['kra_pin']); ?></p>
                <p><strong>NHIF No:</strong> <?php echo htmlspecialchars($payslip['nhif_number']); ?></p>
                <p><strong>NSSF No:</strong> <?php echo htmlspecialchars($payslip['nssf_number']); ?></p>
             </div>
        </div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Earnings</th>
                    <th class="amount">Amount (KSh)</th>
                    <th>Deductions</th>
                    <th class="amount">Amount (KSh)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="amount"><?php echo number_format($payslip['gross_pay'] - ($allowances['house_allowance'] ?? 0) - ($allowances['transport_allowance'] ?? 0), 2); ?></td>
                    <td>PAYE (Tax)</td>
                    <td class="amount"><?php echo number_format($payslip['tax'], 2); ?></td>
                </tr>
                <tr>
                    <td>House Allowance</td>
                    <td class="amount"><?php echo number_format($allowances['house_allowance'] ?? 0, 2); ?></td>
                    <td>NHIF</td>
                    <td class="amount"><?php echo number_format($payslip['insurance'], 2); ?></td>
                </tr>
                 <tr>
                    <td>Transport Allowance</td>
                    <td class="amount"><?php echo number_format($allowances['transport_allowance'] ?? 0, 2); ?></td>
                    <td>NSSF</td>
                    <td class="amount"><?php echo number_format($payslip['retirement'], 2); ?></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td>Other Deductions</td>
                    <td class="amount"><?php echo number_format($payslip['other_deduction'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Gross Earnings</td>
                    <td class="amount"><?php echo number_format($payslip['gross_pay'], 2); ?></td>
                    <td>Total Deductions</td>
                    <td class="amount"><?php echo number_format($payslip['total_deductions'], 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <table class="summary-table total-row">
             <tr>
                <td>Net Pay</td>
                <td class="amount" style="font-size: 1.2em;">KSh <?php echo number_format($payslip['net_pay'], 2); ?></td>
            </tr>
        </table>

        <div class="footer">
            This is a computer-generated payslip and does not require a signature.
        </div>
    </div>
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Print Payslip</button>
    </div>
</body>
</html>