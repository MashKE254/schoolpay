<?php
// reports.php - Separate page for Reports
require 'config.php';
require 'functions.php';
include 'header.php';

// Example data retrieval – replace these with your actual query functions
$plData = getProfitLossData($pdo, '2025-03-01', '2025-03-31');  // Profit & Loss sample data

// Dummy Income by Customer report data
$incomeByCustomer = [
    ['customer' => 'Parent A', 'income' => 1500.00],
    ['customer' => 'Parent B', 'income' => 1800.00]
];

// Dummy Balance Sheet report data
$balanceSheet = [
    'assets' => 60000.00,
    'liabilities' => 35000.00,
    'equity' => 25000.00
];
?>
<h2>Reports</h2>
<div class="tab-container">
  <div class="tabs">
      <button class="tab-link" onclick="openTab(event, 'plReport')">Profit & Loss</button>
      <button class="tab-link" onclick="openTab(event, 'incomeReport')">Income by Customer</button>
      <button class="tab-link" onclick="openTab(event, 'balanceSheet')">Balance Sheet</button>
  </div>
  
  <!-- Profit & Loss Report Tab -->
  <div id="plReport" class="tab-content">
      <div class="card">
          <h3>Profit & Loss Report</h3>
          <p><strong>Income:</strong> $<?php echo number_format($plData['income'], 2); ?></p>
          <p><strong>Expenses:</strong> $<?php echo number_format($plData['expenses'], 2); ?></p>
          <p><strong>Net Income:</strong> $<?php echo number_format($plData['net_income'], 2); ?></p>
          <p>
              <a href="#" onclick="showAlert('Export/Print P&L functionality coming soon!')">Export/Print Report</a>
          </p>
      </div>
  </div>
  
  <!-- Income by Customer Report Tab -->
  <div id="incomeReport" class="tab-content">
      <div class="card">
          <h3>Income by Customer Report</h3>
          <table>
              <thead>
                  <tr>
                      <th>Customer</th>
                      <th>Total Income</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach($incomeByCustomer as $row): ?>
                  <tr>
                      <td><?php echo htmlspecialchars($row['customer']); ?></td>
                      <td>$<?php echo number_format($row['income'],2); ?></td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
          <p>
              <a href="#" onclick="showAlert('Income by Customer Report export functionality coming soon!')">Export/Print Report</a>
          </p>
      </div>
  </div>
  
  <!-- Balance Sheet Report Tab -->
  <div id="balanceSheet" class="tab-content">
      <div class="card">
          <h3>Balance Sheet Report</h3>
          <p><strong>Assets:</strong> $<?php echo number_format($balanceSheet['assets'], 2); ?></p>
          <p><strong>Liabilities:</strong> $<?php echo number_format($balanceSheet['liabilities'], 2); ?></p>
          <p><strong>Equity:</strong> $<?php echo number_format($balanceSheet['equity'], 2); ?></p>
          <p>
              <a href="#" onclick="showAlert('Export/Print Balance Sheet functionality coming soon!')">Export/Print Report</a>
          </p>
      </div>
  </div>
</div>
<?php include 'footer.php'; ?>
