<?php
// customer_center.php - Customer Center
require 'config.php';
require 'functions.php';
include 'header.php';

// Get all items
$items = getItemsWithSubItems($pdo);

// Handle item deletion
if (isset($_POST['delete_item'])) {
    try {
        deleteItem($pdo, $_POST['item_id']);
        $success = "Item deleted successfully.";
        // Refresh items list
        $items = getItemsWithSubItems($pdo);
    } catch (PDOException $e) {
        $error = "Error deleting item: " . $e->getMessage();
    }
}

// Handle item update
if (isset($_POST['update_item'])) {
    try {
        updateItem(
            $pdo,
            $_POST['item_id'],
            $_POST['name'],
            $_POST['price'],
            $_POST['description'],
            $_POST['parent_id'] ?: null,
            $_POST['item_type']
        );
        $success = "Item updated successfully.";
        // Refresh items list
        $items = getItemsWithSubItems($pdo);
    } catch (PDOException $e) {
        $error = "Error updating item: " . $e->getMessage();
    }
}

// Process form submission for adding a new student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addStudent'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $stmt = $pdo->prepare("INSERT INTO students (name, email, phone, address) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $phone, $address])) {
        echo "<script>showAlert('Student added successfully!');</script>";
    } else {
        echo "<script>showAlert('Error adding student.');</script>";
    }
}

// Process delete student request
if (isset($_GET['delete_student'])) {
    $student_id = intval($_GET['delete_student']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    if ($stmt->execute([$student_id])) {
        echo "<script>showAlert('Student deleted successfully!');</script>";
    } else {
        echo "<script>showAlert('Error deleting student.');</script>";
    }
}

// Process edit student request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editStudent'])) {
    $student_id = intval($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    if ($stmt->execute([$name, $email, $phone, $address, $student_id])) {
        echo "<script>showAlert('Student updated successfully!');</script>";
    } else {
        echo "<script>showAlert('Error updating student.');</script>";
    }
}

// Retrieve existing invoices and students
$invoices = getInvoices($pdo);
$students = getStudents($pdo);
?>
<h2>Customer Center</h2>
<div class="tab-container">
  <div class="tabs">
      <button class="tab-link active" onclick="openTab(event, 'students')">Students</button>
      <button class="tab-link" onclick="openTab(event, 'invoices')">Invoices</button>
      <button class="tab-link" onclick="openTab(event, 'items')">Items</button>
      <button class="tab-link" onclick="openTab(event, 'receive_payment')">Receive Payment</button>
  </div>
  
  <!-- Students Tab -->
  <div id="students" class="tab-content" style="display: block;">
      <div class="card">
          <h3>Students</h3>
          <div class="table-actions">
              <button class="btn-add" onclick="openAddModal()">Add New Student</button>
          </div>
          <table>
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Address</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach($students as $student): ?>
                  <tr>
                      <td><?php echo htmlspecialchars($student['id']); ?></td>
                      <td><?php echo htmlspecialchars($student['name']); ?></td>
                      <td><?php echo htmlspecialchars($student['email']); ?></td>
                      <td><?php echo htmlspecialchars($student['phone']); ?></td>
                      <td><?php echo htmlspecialchars($student['address']); ?></td>
                      <td>
                          <button class="btn-edit" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>
                          <button class="btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>)">Delete</button>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
  </div>
  
  <!-- Invoices Tab -->
  <div id="invoices" class="tab-content">
      <div class="card">
          <h3>Invoices</h3>
          <div class="table-actions">
              <a href="create_invoice.php" class="btn-primary">Create New Invoice</a>
          </div>
          <table>
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Student</th>
                      <th>Date</th>
                      <th>Due Date</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach($invoices as $inv): ?>
                  <tr>
                      <td><?php echo htmlspecialchars($inv['id']); ?></td>
                      <td><?php echo htmlspecialchars($inv['student_name']); ?></td>
                      <td><?php echo date('M d, Y', strtotime($inv['invoice_date'])); ?></td>
                      <td><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                      <td>$<?php echo number_format($inv['total_amount'] ?? 0, 2); ?></td>
                      <td>
                          <?php
                          $status = 'Draft';
                          if (isset($inv['paid_amount']) && $inv['paid_amount'] > 0) {
                              if ($inv['paid_amount'] >= $inv['total_amount']) {
                                  $status = 'Paid';
                              } else {
                                  $status = 'Partially Paid';
                              }
                          }
                          echo $status;
                          ?>
                      </td>
                      <td>
                          <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn-small">View</a>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
  </div>
  
  <!-- Items Tab -->
  <div id="items" class="tab-content">
      <div class="items-list">
          <?php foreach ($items as $item): ?>
              <div class="item-card">
                  <div class="item-header">
                      <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                      <div class="item-actions">
                          <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                              Edit
                          </button>
                          <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                              <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                              <button type="submit" name="delete_item" class="btn-delete">Delete</button>
                          </form>
                      </div>
                  </div>
                  <div class="item-details">
                      <p><strong>Price:</strong> $<?php echo number_format($item['price'], 2); ?></p>
                      <?php if (!empty($item['description'])): ?>
                          <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                      <?php endif; ?>
                  </div>
                  <?php if (!empty($item['sub_items'])): ?>
                      <div class="sub-items">
                          <h4>Sub-items:</h4>
                          <?php foreach ($item['sub_items'] as $sub_item): ?>
                              <div class="sub-item">
                                  <div class="sub-item-header">
                                      <h5><?php echo htmlspecialchars($sub_item['name']); ?></h5>
                                      <div class="sub-item-actions">
                                          <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sub_item)); ?>)">
                                              Edit
                                          </button>
                                          <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                              <input type="hidden" name="item_id" value="<?php echo $sub_item['id']; ?>">
                                              <button type="submit" name="delete_item" class="btn-delete">Delete</button>
                                          </form>
                                      </div>
                                  </div>
                                  <div class="sub-item-details">
                                      <p><strong>Price:</strong> $<?php echo number_format($sub_item['price'], 2); ?></p>
                                      <?php if (!empty($sub_item['description'])): ?>
                                          <p><strong>Description:</strong> <?php echo htmlspecialchars($sub_item['description']); ?></p>
                                      <?php endif; ?>
                                  </div>
                              </div>
                          <?php endforeach; ?>
                      </div>
                  <?php endif; ?>
              </div>
          <?php endforeach; ?>
      </div>
  </div>
  
  <!-- Receive Payment Tab -->
  <div id="receive_payment" class="tab-content">
      <div class="card">
          <h3>Receive Payment</h3>
          <form id="paymentForm" method="post" onsubmit="return handlePaymentSubmit(event)">
              <div class="form-row">
                  <div class="form-group">
                      <label for="student_id">Student</label>
                      <select name="student_id" id="student_id" required onchange="loadUnpaidInvoices()">
                          <option value="">Select Student</option>
                          <?php foreach ($students as $student): ?>
                              <option value="<?php echo $student['id']; ?>">
                                  <?php echo htmlspecialchars($student['name']); ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  
                  <div class="form-group">
                      <label for="payment_date">Payment Date</label>
                      <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                  </div>
                  
                  <div class="form-group">
                      <label for="payment_method">Payment Method</label>
                      <select name="payment_method" id="payment_method" required>
                          <option value="Cash">Cash</option>
                          <option value="Bank Transfer">Bank Transfer</option>
                          <option value="Mobile Money">Mobile Money</option>
                          <option value="Check">Check</option>
                      </select>
                  </div>
              </div>
              
              <div class="form-group">
                  <label for="memo">Memo</label>
                  <textarea name="memo" id="memo" rows="2"></textarea>
              </div>
              
              <div class="unpaid-invoices">
                  <h4>Unpaid Invoices</h4>
                  <table id="unpaidInvoicesTable">
              <thead>
                  <tr>
                              <th>Invoice #</th>
                              <th>Date</th>
                              <th>Due Date</th>
                              <th>Total Amount</th>
                              <th>Paid Amount</th>
                              <th>Balance</th>
                              <th>Payment Amount</th>
                  </tr>
              </thead>
              <tbody>
                          <!-- Invoices will be loaded here -->
                      </tbody>
                      <tfoot>
                          <tr>
                              <td colspan="6" class="text-right"><strong>Total Payment:</strong></td>
                              <td><strong id="totalPayment">$0.00</strong></td>
                  </tr>
                      </tfoot>
          </table>
              </div>
              
              <div class="form-actions">
                  <button type="submit" class="btn-primary">Record Payment</button>
              </div>
          </form>
      </div>
  </div>
</div>

<!-- Add New Student Modal -->
<div id="addStudentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeAddModal()">&times;</span>
        <h3>Add New Student</h3>
        <form id="addStudentForm" method="post">
            <input type="hidden" name="addStudent" value="1">
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" name="phone" id="phone" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea name="address" id="address" rows="3" required></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Add Student</button>
                <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
            </div>
        </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Student</h3>
        <form id="editStudentForm" method="post">
            <input type="hidden" name="student_id" id="edit_student_id">
            <input type="hidden" name="editStudent" value="1">
            
            <div class="form-group">
                <label for="edit_name">Name:</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="form-group">
                <label for="edit_phone">Phone:</label>
                <input type="text" name="phone" id="edit_phone" required>
            </div>
            
            <div class="form-group">
                <label for="edit_address">Address:</label>
                <textarea name="address" id="edit_address" rows="3" required></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Update Student</button>
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Item</h3>
        <form id="editItemForm" method="post">
            <input type="hidden" name="item_id" id="edit_item_id">
            <input type="hidden" name="item_type" id="edit_item_type">
            
            <div class="form-group">
                <label for="edit_name">Item Name</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_price">Price</label>
                <input type="number" name="price" id="edit_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea name="description" id="edit_description" rows="3"></textarea>
            </div>
            
            <div class="form-group" id="edit_parent_item_group" style="display: none;">
                <label for="edit_parent_id">Parent Item</label>
                <select name="parent_id" id="edit_parent_id">
                    <option value="">Select Parent Item</option>
                    <?php foreach ($items as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_item" class="btn-primary">Update Item</button>
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.table-actions {
    margin-bottom: 15px;
}

.btn-primary {
    background: #2196F3;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.btn-small {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    padding: 4px 8px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
}

.status-draft { color: #666; }
.status-sent { color: #2196F3; }
.status-paid { color: #4CAF50; }
.status-overdue { color: #f44336; }
.status-cancelled { color: #666; }

.items-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.item-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.item-actions {
    display: flex;
    gap: 10px;
}

.sub-items {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.sub-item {
    background: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
}

.sub-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.btn-edit {
    background: #2196F3;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-delete {
    background: #f44336;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-add {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.unpaid-invoices {
    margin: 20px 0;
    overflow-x: auto;
}

.unpaid-invoices table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.unpaid-invoices th,
.unpaid-invoices td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: left;
}

.unpaid-invoices th {
    background-color: #f5f5f5;
}

.unpaid-invoices input[type="number"] {
    width: 100px;
    padding: 5px;
}

.text-right {
    text-align: right;
}
</style>

<script>
function openAddModal() {
    document.getElementById('addStudentModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addStudentModal').style.display = 'none';
}

function editStudent(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_address').value = student.address;
    document.getElementById('editStudentModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editStudentModal').style.display = 'none';
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        window.location.href = 'customer_center.php?delete_student=' + studentId;
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('addStudentModal')) {
        closeAddModal();
    }
    if (event.target == document.getElementById('editStudentModal')) {
        closeEditModal();
    }
}

function openEditModal(item) {
    document.getElementById('editItemModal').style.display = 'block';
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_price').value = item.price;
    document.getElementById('edit_description').value = item.description || '';
    
    // Check if item_type column exists
    const itemTypeGroup = document.getElementById('edit_parent_item_group');
    if (item.item_type) {
        document.getElementById('edit_item_type').value = item.item_type;
        if (item.item_type === 'child') {
            itemTypeGroup.style.display = 'block';
            document.getElementById('edit_parent_id').value = item.parent_id || '';
        } else {
            itemTypeGroup.style.display = 'none';
        }
    } else {
        itemTypeGroup.style.display = 'none';
    }
}

function closeEditModal() {
    document.getElementById('editItemModal').style.display = 'none';
}

function loadUnpaidInvoices() {
    const studentId = document.getElementById('student_id').value;
    if (!studentId) return;
    
    fetch(`get_unpaid_invoices.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#unpaidInvoicesTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(invoice => {
                const balance = invoice.total_amount - (invoice.paid_amount || 0);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${invoice.id}</td>
                    <td>${new Date(invoice.invoice_date).toLocaleDateString()}</td>
                    <td>${new Date(invoice.due_date).toLocaleDateString()}</td>
                    <td>$${parseFloat(invoice.total_amount).toFixed(2)}</td>
                    <td>$${(invoice.paid_amount || 0).toFixed(2)}</td>
                    <td>$${balance.toFixed(2)}</td>
                    <td>
                        <input type="number" 
                               name="invoice_payments[${invoice.id}]" 
                               class="payment-amount" 
                               min="0" 
                               max="${balance}" 
                               step="0.01"
                               onchange="updateTotalPayment()">
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            updateTotalPayment();
        });
}

function updateTotalPayment() {
    const inputs = document.querySelectorAll('.payment-amount');
    let total = 0;
    inputs.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('totalPayment').textContent = `$${total.toFixed(2)}`;
}

function handlePaymentSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('record_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment recorded successfully!');
            loadUnpaidInvoices(); // Refresh unpaid invoices
            updateDashboardBalances(); // Update dashboard balances
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error recording payment: ' + error);
    });
    
    return false;
}

function updateDashboardBalances() {
    fetch('get_dashboard_balances.php')
        .then(response => response.json())
        .then(data => {
            // Update the dashboard elements with new balances
            document.getElementById('totalBalance').textContent = `$${data.totalBalance.toFixed(2)}`;
            document.getElementById('totalPaid').textContent = `$${data.totalPaid.toFixed(2)}`;
        })
        .catch(error => {
            console.error('Error updating dashboard balances:', error);
        });
}
</script>
<?php include 'footer.php'; ?>
