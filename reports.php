<?php
// reports.php - Enhanced Reports Page with Expandable Categories
require 'config.php';
require 'functions.php';
include 'header.php';

// Set default date range for reports
$currentYear = date('Y');
$startDate = date('Y-01-01'); // Start of current year
$endDate = date('Y-m-d'); // Today

// Get real report data
$plData = getProfitLossData($pdo, $startDate, $endDate);
$incomeByCustomer = getIncomeByCustomer($pdo, $startDate, $endDate);
$balanceSheetData = getBalanceSheetData($pdo);
$openInvoices = getOpenInvoices($pdo);
$incomeByCategory = getIncomeByCategory($pdo, $startDate, $endDate);

?>

<h1>Financial Reports</h1>
<div class="tab-container">
  <div class="tabs">
      <button class="tab-link active" onclick="openTab(event, 'plReport')">Profit & Loss</button>
      <button class="tab-link" onclick="openTab(event, 'incomeReport')">Income by Customer</button>
      <button class="tab-link" onclick="openTab(event, 'balanceSheet')">Balance Sheet</button>
      <button class="tab-link" onclick="openTab(event, 'openInvoices')">Open Invoices</button>
      <button class="tab-link" onclick="openTab(event, 'incomeByItem')">Income by Category</button>
  </div>
  
  <!-- Profit & Loss Report Tab -->
  <div id="plReport" class="tab-content" style="display: block;">
      <div class="card">
          <h3>Profit & Loss Report</h3>
          <div class="report-period">
              <label for="plDateFrom">From:</label>
              <input type="date" id="plDateFrom" value="<?= $startDate ?>">
              <label for="plDateTo">To:</label>
              <input type="date" id="plDateTo" value="<?= $endDate ?>">
              <button class="btn-filter" onclick="filterPLReport()">Apply Filter</button>
          </div>
          
          <div class="pl-summary">
              <div class="pl-income">
                  <h4>Income</h4>
                  <p>Total Income: $<?= number_format($plData['income'], 2) ?></p>
              </div>
              
              <div class="pl-expenses">
                  <h4>Expenses</h4>
                  <p>Total Expenses: $<?= number_format($plData['expenses'], 2) ?></p>
              </div>
              
              <div class="pl-net">
                  <h4>Net Income</h4>
                  <p>$<?= number_format($plData['net_income'], 2) ?></p>
              </div>
          </div>
          
          <div class="report-actions">
              <button class="btn-export" onclick="exportPLReport()">
                  <i class="fas fa-file-export"></i> Export to Excel
              </button>
              <button class="btn-print" onclick="printPLReport()">
                  <i class="fas fa-print"></i> Print Report
              </button>
          </div>
      </div>
  </div>
  
  <!-- Income by Customer Report Tab -->
  <div id="incomeReport" class="tab-content">
      <div class="card">
          <h3>Income by Customer Report</h3>
          <div class="report-controls">
              <label for="incomeDateFrom">From:</label>
              <input type="date" id="incomeDateFrom" value="<?= $startDate ?>">
              <label for="incomeDateTo">To:</label>
              <input type="date" id="incomeDateTo" value="<?= $endDate ?>">
              <button class="btn-filter" onclick="filterIncomeReport()">Apply Filter</button>
          </div>
          
          <div class="table-container">
              <table>
                  <thead>
                      <tr>
                          <th>Student</th>
                          <th>Total Payments</th>
                          <th>Last Payment</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach($incomeByCustomer as $row): ?>
                      <tr>
                          <td><?= htmlspecialchars($row['student_name']) ?></td>
                          <td>$<?= number_format($row['total_payments'], 2) ?></td>
                          <td><?= !empty($row['last_payment']) ? date('M d, Y', strtotime($row['last_payment'])) : 'N/A' ?></td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
          
          <div class="report-actions">
              <button class="btn-export" onclick="exportIncomeReport()">
                  <i class="fas fa-file-export"></i> Export to CSV
              </button>
          </div>
      </div>
  </div>
  
  <!-- Balance Sheet Report Tab -->
  <div id="balanceSheet" class="tab-content">
      <div class="card">
          <h3>Balance Sheet Report</h3>
          <div class="balance-date">
              As of: <?= date('F d, Y') ?>
          </div>
          
          <div class="balance-section">
              <h4>Assets</h4>
              <table>
                  <?php foreach($balanceSheetData['assets'] as $asset): ?>
                  <tr>
                      <td><?= htmlspecialchars($asset['account_name']) ?></td>
                      <td class="amount">$<?= number_format($asset['balance'], 2) ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <tr class="total">
                      <td>Total Assets</td>
                      <td class="amount">$<?= number_format($balanceSheetData['total_assets'], 2) ?></td>
                  </tr>
              </table>
          </div>
          
          <div class="balance-section">
              <h4>Liabilities</h4>
              <table>
                  <?php foreach($balanceSheetData['liabilities'] as $liability): ?>
                  <tr>
                      <td><?= htmlspecialchars($liability['account_name']) ?></td>
                      <td class="amount">$<?= number_format($liability['balance'], 2) ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <tr class="total">
                      <td>Total Liabilities</td>
                      <td class="amount">$<?= number_format($balanceSheetData['total_liabilities'], 2) ?></td>
                  </tr>
              </table>
          </div>
          
          <div class="balance-section">
              <h4>Equity</h4>
              <table>
                  <?php foreach($balanceSheetData['equity'] as $equity): ?>
                  <tr>
                      <td><?= htmlspecialchars($equity['account_name']) ?></td>
                      <td class="amount">$<?= number_format($equity['balance'], 2) ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <tr>
                      <td>Retained Earnings</td>
                      <td class="amount">$<?= number_format($balanceSheetData['retained_earnings'], 2) ?></td>
                  </tr>
                  <tr class="total">
                      <td>Total Equity</td>
                      <td class="amount">$<?= number_format($balanceSheetData['total_equity'], 2) ?></td>
                  </tr>
              </table>
          </div>
          
          <div class="balance-total">
              <table>
                  <tr>
                      <td>Total Liabilities + Equity</td>
                      <td class="amount">$<?= number_format($balanceSheetData['total_liabilities_equity'], 2) ?></td>
                  </tr>
              </table>
          </div>
          
          <div class="report-actions">
              <button class="btn-print" onclick="printBalanceSheet()">
                  <i class="fas fa-print"></i> Print Report
              </button>
          </div>
      </div>
  </div>
  
  <!-- Open Invoices Report Tab -->
  <div id="openInvoices" class="tab-content">
      <div class="card">
          <h3>Open Invoices Report</h3>
          <div class="report-controls">
              <label for="invoiceFilter">Filter:</label>
              <select id="invoiceFilter" onchange="filterOpenInvoices()">
                  <option value="all">All Open Invoices</option>
                  <option value="overdue">Overdue Invoices Only</option>
                  <option value="partial">Partially Paid Only</option>
              </select>
          </div>
          
          <div class="table-container">
              <table id="openInvoicesTable">
                  <thead>
                      <tr>
                          <th>Student</th>
                          <th>Invoice #</th>
                          <th>Issue Date</th>
                          <th>Due Date</th>
                          <th>Total Amount</th>
                          <th>Amount Paid</th>
                          <th>Balance Due</th>
                          <th>Status</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach($openInvoices as $invoice): 
                          $dueClass = (strtotime($invoice['due_date']) < time() && $invoice['balance'] > 0) ? 'overdue' : '';
                          $status = ($invoice['balance'] == 0) ? 'Paid' : ($invoice['paid_amount'] > 0 ? 'Partially Paid' : 'Unpaid');
                      ?>
                      <tr class="<?= $dueClass ?>" data-status="<?= strtolower($status) ?>">
                          <td><?= htmlspecialchars($invoice['student_name']) ?></td>
                          <td><?= $invoice['id'] ?></td>
                          <td><?= date('M d, Y', strtotime($invoice['invoice_date'])) ?></td>
                          <td class="<?= $dueClass ?>"><?= date('M d, Y', strtotime($invoice['due_date'])) ?></td>
                          <td>$<?= number_format($invoice['total_amount'], 2) ?></td>
                          <td>$<?= number_format($invoice['paid_amount'], 2) ?></td>
                          <td>$<?= number_format($invoice['balance'], 2) ?></td>
                          <td><?= $status ?></td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
          
          <div class="report-summary">
              <p><strong>Total Open Balance:</strong> $<?= number_format(array_sum(array_column($openInvoices, 'balance')), 2) ?></p>
              <p><strong>Number of Open Invoices:</strong> <?= count($openInvoices) ?></p>
          </div>
          
          <div class="report-actions">
              <button class="btn-export" onclick="exportOpenInvoices()">
                  <i class="fas fa-file-export"></i> Export to CSV
              </button>
              <button class="btn-print" onclick="printOpenInvoices()">
                  <i class="fas fa-print"></i> Print Report
              </button>
          </div>
      </div>
  </div>
  
  <!-- Income by Category Tab -->
  <div id="incomeByItem" class="tab-content">
    <div class="card">
        <h3>Income by Category Report</h3>
        <div class="report-controls">
            <label for="itemDateFrom">From:</label>
            <input type="date" id="itemDateFrom" value="<?= $startDate ?>">
            <label for="itemDateTo">To:</label>
            <input type="date" id="itemDateTo" value="<?= $endDate ?>">
            <button class="btn-filter" onclick="filterItemReport()">Apply Filter</button>
        </div>
        
        <div class="table-container">
            <table id="categoryTable">
                <thead>
                    <tr>
                        <th></th>
                        <th>Category</th>
                        <th>Total Quantity</th>
                        <th>Total Income</th>
                        <th>Average Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalQty = 0;
                    $totalIncome = 0;
                    ?>
                    <?php foreach($incomeByCategory as $category): ?>
                    <?php 
                    $totalQty += $category['total_quantity'];
                    $totalIncome += $category['total_income'];
                    ?>
                    <tr class="category-row" data-category-id="<?= $category['category_id'] ?>">
                        <td class="toggle-icon">
                            <?php if($category['has_subcategories']): ?>
                            <i class="fas fa-plus-circle toggle-icon"></i>
                            <?php else: ?>
                            <span class="no-toggle"></span>
                            <?php endif; ?>
                        </td>
                        <td class="category-name"><?= htmlspecialchars($category['category_name']) ?></td>
                        <td><?= number_format($category['total_quantity']) ?></td>
                        <td>$<?= number_format($category['total_income'], 2) ?></td>
                        <td>$<?= number_format($category['average_price'], 2) ?></td>
                    </tr>
                    
                    <!-- Subcategories will be loaded here via AJAX -->
                    <tr class="subcategories-row" id="subcategories-<?= $category['category_id'] ?>" style="display: none;">
                        <td colspan="5">
                            <div class="subcategories-container" id="subcategories-container-<?= $category['category_id'] ?>">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Loading subcategories...
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th><?= number_format($totalQty) ?></th>
                        <th>$<?= number_format($totalIncome, 2) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="report-actions">
            <button class="btn-export" onclick="exportItemReport()">
                <i class="fas fa-file-export"></i> Export to CSV
            </button>
        </div>
    </div>
  </div>
</div>

<script>
// Tab navigation
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

function filterPLReport() {
    const from = document.getElementById('plDateFrom').value;
    const to = document.getElementById('plDateTo').value;
    
    // AJAX call to fetch filtered P&L data
    fetch(`get_pl_report.php?from=${from}&to=${to}`)
        .then(response => response.json())
        .then(data => {
            // Update the P&L report with new data
            document.querySelector('.pl-income p').innerHTML = 
                `Total Income: $${data.income.toFixed(2)}`;
            document.querySelector('.pl-expenses p').innerHTML = 
                `Total Expenses: $${data.expenses.toFixed(2)}`;
            document.querySelector('.pl-net p').innerHTML = 
                `$${data.net_income.toFixed(2)}`;
        });
}

function exportPLReport() {
    const from = document.getElementById('plDateFrom').value;
    const to = document.getElementById('plDateTo').value;
    window.location.href = `export_pl_report.php?from=${from}&to=${to}`;
}

function printPLReport() {
    window.print();
}
function filterIncomeReport() {
    const from = document.getElementById('incomeDateFrom').value;
    const to = document.getElementById('incomeDateTo').value;
    
    // AJAX call to fetch filtered income data
    fetch(`get_income_report.php?from=${from}&to=${to}`)
        .then(response => response.json())
        .then(data => {
            // Update the income report table
            const tbody = document.querySelector('#incomeReport tbody');
            tbody.innerHTML = '';
            
            data.forEach(row => {
                const lastPayment = row.last_payment ? new Date(row.last_payment).toLocaleDateString() : 'N/A';
                tbody.innerHTML += `
                    <tr>
                        <td>${row.student_name}</td>
                        <td>$${row.total_payments.toFixed(2)}</td>
                        <td>${lastPayment}</td>
                    </tr>
                `;
            });
        });
}

function exportIncomeReport() {
    const from = document.getElementById('incomeDateFrom').value;
    const to = document.getElementById('incomeDateTo').value;
    window.location.href = `export_income_report.php?from=${from}&to=${to}`;
}

function printBalanceSheet() {
    window.print();
}

function filterOpenInvoices() {
    const filter = document.getElementById('invoiceFilter').value;
    const rows = document.querySelectorAll('#openInvoicesTable tbody tr');
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        
        if (filter === 'all') {
            row.style.display = '';
        } else if (filter === 'overdue') {
            row.style.display = row.classList.contains('overdue') ? '' : 'none';
        } else if (filter === 'partial') {
            row.style.display = (status === 'partially paid') ? '' : 'none';
        }
    });
}

function exportOpenInvoices() {
    const filter = document.getElementById('invoiceFilter').value;
    window.location.href = `export_open_invoices.php?filter=${filter}`;
}

function printOpenInvoices() {
    const printContent = document.getElementById('openInvoices').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h1 style="text-align: center;">Open Invoices Report</h1>
            <div>${printContent}</div>
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
}

// Income by Category functions
function filterItemReport() {
    const from = document.getElementById('itemDateFrom').value;
    const to = document.getElementById('itemDateTo').value;
    
    fetch(`get_income_by_category.php?from=${from}&to=${to}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#categoryTable tbody');
            const tfoot = document.querySelector('#categoryTable tfoot tr');
            tbody.innerHTML = '';
            
            let totalQty = 0;
            let totalIncome = 0;
            
            data.forEach(category => {
                totalQty += parseInt(category.total_quantity);
                totalIncome += parseFloat(category.total_income);
                
                tbody.innerHTML += `
                    <tr class="category-row" data-category-id="${category.category_id}">
                        <td class="toggle-icon">
                            ${category.has_subcategories ? 
                                '<i class="fas fa-plus-circle toggle-icon"></i>' : 
                                '<span class="no-toggle"></span>'}
                        </td>
                        <td class="category-name">${category.category_name}</td>
                        <td>${category.total_quantity}</td>
                        <td>$${category.total_income.toFixed(2)}</td>
                        <td>$${category.average_price.toFixed(2)}</td>
                    </tr>
                    <tr class="subcategories-row" id="subcategories-${category.category_id}" style="display: none;">
                        <td colspan="5">
                            <div class="subcategories-container" id="subcategories-container-${category.category_id}">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Loading subcategories...
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            // Update footer
            tfoot.innerHTML = `
                <th colspan="2">Total</th>
                <th>${totalQty}</th>
                <th>$${totalIncome.toFixed(2)}</th>
                <th></th>
            `;
            
            // Reattach event listeners
            attachCategoryToggleEvents();
        });
}

function exportItemReport() {
    const from = document.getElementById('itemDateFrom').value;
    const to = document.getElementById('itemDateTo').value;
    window.location.href = `export_income_by_category.php?from=${from}&to=${to}`;
}

// Load subcategories when category is clicked
function loadSubcategories(categoryId) {
    const container = document.getElementById(`subcategories-container-${categoryId}`);
    const row = document.getElementById(`subcategories-${categoryId}`);
    const icon = document.querySelector(`tr[data-category-id="${categoryId}"] .toggle-icon i`);
    
    // Show loading state
    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading subcategories...</div>';
    row.style.display = '';
    
    // Get date range
    const from = document.getElementById('itemDateFrom').value;
    const to = document.getElementById('itemDateTo').value;
    
    fetch(`get_subcategories.php?category_id=${categoryId}&from=${from}&to=${to}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<div class="no-subcategories">No subcategories found for this category</div>';
                return;
            }
            
            // Build subcategory table
            let html = `
                <table class="subcategories-table">
                    <thead>
                        <tr>
                            <th>Subcategory</th>
                            <th>Quantity</th>
                            <th>Income</th>
                            <th>Average Price</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(subcat => {
                html += `
                    <tr>
                        <td>${subcat.name}</td>
                        <td>${subcat.total_quantity}</td>
                        <td>$${subcat.total_income.toFixed(2)}</td>
                        <td>$${subcat.average_price.toFixed(2)}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
            icon.className = 'fas fa-minus-circle toggle-icon';
        })
        .catch(error => {
            container.innerHTML = `<div class="error">Error loading subcategories: ${error.message}</div>`;
        });
}

// Toggle category expansion
function toggleCategory(categoryRow) {
    const categoryId = categoryRow.dataset.categoryId;
    const subcatRow = document.getElementById(`subcategories-${categoryId}`);
    const icon = categoryRow.querySelector('.toggle-icon i');
    
    if (!icon) return; // No toggle icon, skip
    
    if (subcatRow.style.display === 'none') {
        // Load subcategories if not already loaded
        const container = document.getElementById(`subcategories-container-${categoryId}`);
        if (container.innerHTML.includes('Loading subcategories')) {
            loadSubcategories(categoryId);
        } else {
            subcatRow.style.display = '';
            icon.className = 'fas fa-minus-circle toggle-icon';
        }
    } else {
        subcatRow.style.display = 'none';
        icon.className = 'fas fa-plus-circle toggle-icon';
    }
}

// Attach event listeners to category rows
function attachCategoryToggleEvents() {
    document.querySelectorAll('.category-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Only toggle if not clicking on the toggle icon directly
            if (!e.target.closest('.toggle-icon')) {
                toggleCategory(this);
            }
        });
        
        // Add specific click handler for the toggle icon
        const icon = row.querySelector('.toggle-icon i');
        if (icon) {
            icon.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleCategory(row);
            });
        }
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    attachCategoryToggleEvents();
    
    // Expand first category by default
    const firstCategory = document.querySelector('.category-row');
    if (firstCategory) {
        setTimeout(() => {
            toggleCategory(firstCategory);
        }, 500);
    }
});

</script>

<style>
/* Add styles for expandable categories */
.toggle-icon {
    cursor: pointer;
    width: 30px;
    text-align: center;
}

.toggle-icon i {
    color: #3498db;
    font-size: 18px;
    transition: transform 0.3s ease;
}

.no-toggle {
    display: inline-block;
    width: 18px;
}

.category-row {
    cursor: pointer;
    background-color: #f8f9fa;
}

.category-row:hover {
    background-color: #e9ecef;
}

.category-name {
    font-weight: 600;
}

.subcategories-table {
    width: 100%;
    margin: 10px 0 10px 40px;
    border-collapse: collapse;
    background-color: #fff;
}

.subcategories-table th {
    background-color: #f1f1f1;
    padding: 8px 12px;
    text-align: left;
    font-weight: 600;
}

.subcategories-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
}

.subcategories-container {
    padding: 10px;
}

.loading-spinner {
    padding: 10px;
    text-align: center;
    color: #6c757d;
}

.no-subcategories {
    padding: 10px;
    text-align: center;
    font-style: italic;
    color: #6c757d;
}

.error {
    padding: 10px;
    color: #e74c3c;
    font-weight: 500;
}

/* Existing styles from previous reports */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

:root {
    --primary: #2c3e50;
    --secondary: #3498db;
    --accent: #1abc9c;
    --light: #ecf0f1;
    --dark: #34495e;
    --success: #2ecc71;
    --warning: #f39c12;
    --danger: #e74c3c;
    --card-bg: #ffffff;
    --border: #dfe6e9;
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
    color: #333;
    min-height: 100vh;
    padding: 20px;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    background: var(--card-bg);
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 30px;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    font-size: 20px;
    color: var(--primary);
    font-weight: 700;
}

.card-body {
    padding: 25px;
}

.report-period {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.report-period label {
    font-weight: 600;
    color: var(--dark);
    font-size: 15px;
}

.report-period input {
    padding: 10px 15px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 15px;
    background: var(--light);
}

.btn-filter {
    background: var(--secondary);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-filter:hover {
    background: #2980b9;
}

.pl-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.pl-income, .pl-expenses, .pl-net {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: var(--shadow);
    border-top: 4px solid var(--success);
    position: relative;
    overflow: hidden;
}

.pl-expenses {
    border-top-color: var(--warning);
}

.pl-net {
    border-top-color: var(--secondary);
}

.pl-summary h4 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.pl-summary p {
    font-size: 15px;
    color: var(--dark);
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
}

.pl-summary .total {
    font-weight: 700;
    font-size: 16px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--border);
}

.pl-net h4 {
    color: var(--secondary);
}

.pl-net p {
    font-size: 28px;
    font-weight: 700;
    color: var(--secondary);
    margin-top: 10px;
}

.report-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-export, .btn-print {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 15px;
}

.btn-export {
    background: var(--accent);
    color: white;
}

.btn-export:hover {
    background: #16a085;
}

.btn-print {
    background: var(--primary);
    color: white;
}

.btn-print:hover {
    background: #1a252f;
}

.table-container {
    overflow-x: auto;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 800px;
}

table th {
    background: var(--primary);
    color: white;
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    position: sticky;
    top: 0;
}

table td {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    color: var(--dark);
}

table tr:last-child td {
    border-bottom: none;
}

table tr:nth-child(even) {
    background-color: #f9fafb;
}

table tr:hover {
    background-color: #f1f9ff;
}

.amount {
    text-align: right;
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

.balance-section {
    margin-bottom: 30px;
}

.balance-section h4 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--primary);
    padding-left: 10px;
    border-left: 4px solid var(--accent);
}

.balance-total {
    margin-top: 25px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid var(--border);
}

.balance-total td {
    font-weight: 700;
    font-size: 17px;
    color: var(--primary);
}

.overdue {
    color: var(--danger);
    font-weight: 700;
}

.report-summary {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    margin-top: 25px;
    border: 1px solid var(--border);
}

.report-summary p {
    font-size: 16px;
    color: var(--dark);
    display: flex;
    gap: 10px;
}

.report-summary strong {
    color: var(--primary);
    min-width: 160px;
}

.stat-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.badge-success {
    background: rgba(46, 204, 113, 0.15);
    color: var(--success);
}

.badge-warning {
    background: rgba(243, 156, 18, 0.15);
    color: var(--warning);
}

.badge-danger {
    background: rgba(231, 76, 60, 0.15);
    color: var(--danger);
}

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-unpaid {
    background: var(--danger);
}

.status-partial {
    background: var(--warning);
}

.status-paid {
    background: var(--success);
}

.dashboard-footer {
    text-align: center;
    padding: 30px 0;
    color: var(--dark);
    font-size: 14px;
    border-top: 1px solid var(--border);
    margin-top: 40px;
}

@media (max-width: 768px) {
    header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .logo {
        justify-content: center;
    }
    
    .tabs {
        flex-direction: column;
    }
    
    .pl-summary {
        grid-template-columns: 1fr;
    }
    
    .report-period {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include 'footer.php'; ?>