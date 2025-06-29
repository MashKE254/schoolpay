<?php
// Start output buffering at the very beginning
ob_start();

require 'config.php';
require 'functions.php';
include 'header.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createInvoice'])) {
    try {
        $invoice_type = $_POST['invoice_type']; // 'single' or 'class'
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        $notes = trim($_POST['notes']);
        
        // Process items
        $items = [];
        foreach ($_POST['item_id'] as $key => $item_id) {
            if (!empty($item_id) && !empty($_POST['quantity'][$key])) {
                $items[] = [
                    'item_id' => $item_id,
                    'quantity' => intval($_POST['quantity'][$key]),
                    'unit_price' => floatval($_POST['unit_price'][$key]),
                    'description' => $_POST['description'][$key] ?? ''
                ];
            }
        }
        
        if (empty($items)) {
            throw new Exception("At least one item is required");
        }
        
        if ($invoice_type === 'single') {
            // Single student invoice
            $student_id = intval($_POST['student_id']);
            $invoice_id = createInvoice($pdo, $student_id, $invoice_date, $due_date, $items, $notes);
            header("Location: view_invoice.php?id=" . $invoice_id);
            ob_end_flush();
            exit;
        } elseif ($invoice_type === 'class') {
            // Bulk invoicing for class
            $class_id = intval($_POST['class_id']);
            $students = getStudentsByClass($pdo, $class_id);
            
            if (empty($students)) {
                throw new Exception("No students found in this class");
            }
            
            $created_invoices = [];
            foreach ($students as $student) {
                $invoice_id = createInvoice($pdo, $student['id'], $invoice_date, $due_date, $items, $notes);
                $created_invoices[] = $invoice_id;
            }
            
            $_SESSION['success'] = "Created ".count($created_invoices)." invoices for the class";
            header("Location: create_invoice.php");
            ob_end_flush();
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get classes for dropdown
$classes = [];
$stmt = $pdo->query("SELECT id, name FROM classes");
if ($stmt) {
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process new item creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createItem'])) {
    try {
        $name = trim($_POST['item_name']);
        $price = floatval($_POST['item_price']);
        $description = trim($_POST['item_description']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $item_type = $parent_id ? 'child' : 'parent';
        
        $item_id = createItem($pdo, $name, $price, $description, $parent_id, $item_type);
        
        // Refresh the items list
        $items = getItemsWithSubItems($pdo);
        
        // Return the new item's data as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'item' => [
                'id' => $item_id,
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'parent_id' => $parent_id,
                'item_type' => $item_type
            ]
        ]);
        // Flush the output buffer and end it
        ob_end_flush();
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        // Flush the output buffer and end it
        ob_end_flush();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveTemplate'])) {
    $templateName = trim($_POST['template_name']);
    $items = json_encode($_POST['template_items']); // We'll store items as JSON
    
    $stmt = $pdo->prepare("INSERT INTO invoice_templates (name, items) VALUES (?, ?)");
    if ($stmt->execute([$templateName, $items])) {
        $success = "Template saved successfully!";
    } else {
        $error = "Error saving template.";
    }
}

// Get existing templates
$templates = [];
$stmt = $pdo->query("SELECT * FROM invoice_templates");
if ($stmt) {
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get students and items for dropdowns
$students = getStudents($pdo);
$items = getItemsWithSubItems($pdo);
?>

<div class="container">

<div class="form-group">
    <label>Invoice Type:</label>
    <div>
        <input type="radio" id="invoice_type_single" name="invoice_type" value="single" checked>
        <label for="invoice_type_single">Single Student</label>
        
        <input type="radio" id="invoice_type_class" name="invoice_type" value="class">
        <label for="invoice_type_class">Entire Class</label>
    </div>
</div>

<!-- Student Selection (shown by default) -->
<div id="single-student-section">
    <div class="form-group">
        <label for="student_id">Student</label>
        <select name="student_id" id="student_id" required>
            <option value="">Select Student</option>
            <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['id']; ?>">
                    <?php echo htmlspecialchars($student['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Class Selection (hidden by default) -->
<div id="class-section" style="display: none;">
    <div class="form-group">
        <label for="class_id">Class</label>
        <select name="class_id" id="class_id">
            <option value="">Select Class</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?php echo $class['id']; ?>">
                    <?php echo htmlspecialchars($class['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

    <div class="template-controls">
    <div class="form-group">
        <label for="template_select">Template:</label>
        <select id="template_select" onchange="loadTemplate(this.value)">
            <option value="">-- Select Template --</option>
            <?php foreach ($templates as $template): ?>
                <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="button" class="btn-secondary" onclick="openSaveTemplateModal()">Save as Template</button>
</div>

    <h2>Create New Invoice</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post" class="invoice-form">
        <div class="form-section">
            <h3>Invoice Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="invoice_date">Invoice Date</label>
                    <input type="date" name="invoice_date" id="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date" required>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Items</h3>
            <div id="items-container">
                <div class="item-row">
                    <div class="form-group">
                        <label>Item</label>
                        <select name="item_id[]" class="item-select" required>
                            <option value="">Select Item</option>
                            <option value="new" class="create-new-item">+ Create New Item</option>
                            <?php foreach ($items as $item): ?>
                                <?php if (empty($item['sub_items'])): ?>
                                    <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                                        <?php echo htmlspecialchars($item['name'] . ' ($' . number_format($item['price'], 2) . ')'); ?>
                                    </option>
                                <?php else: ?>
                                    <optgroup label="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php foreach ($item['sub_items'] as $sub_item): ?>
                                            <option value="<?php echo $sub_item['id']; ?>" data-price="<?php echo $sub_item['price']; ?>">
                                                <?php echo htmlspecialchars($item['name'] . ' - ' . $sub_item['name'] . ' ($' . number_format($sub_item['price'], 2) . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity[]" class="quantity" min="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Unit Price</label>
                        <input type="number" name="unit_price[]" class="unit-price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description[]" class="description">
                    </div>
                    
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="text" class="amount" readonly>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="remove-item" onclick="removeItem(this)">×</button>
                    </div>
                </div>
            </div>
            
            <button type="button" class="add-item-btn" onclick="addItem()">Add Item</button>
        </div>
        
        <div class="form-section">
            <h3>Notes</h3>
            <div class="form-group">
                <textarea name="notes" rows="3"></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <div class="total-section">
                <h3>Total Amount: $<span id="total-amount">0.00</span></h3>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="createInvoice" class="btn-primary">Create Invoice</button>
            <a href="customer_center.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- New Item Modal -->
<div id="newItemModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeNewItemModal()">&times;</span>
        <h3>Create New Item</h3>
        <form id="newItemForm" onsubmit="return createNewItem(event)">
            <div class="form-group">
                <label for="item_type">Item Type</label>
                <select name="item_type" id="item_type" onchange="toggleParentItem()">
                    <option value="parent">Parent Item</option>
                    <option value="child">Sub-Item</option>
                </select>
            </div>
            
            <div class="form-group" id="parentItemGroup" style="display: none;">
                <label for="parent_id">Parent Item</label>
                <select name="parent_id" id="parent_id">
                    <option value="">Select Parent Item</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" name="item_name" id="item_name" required>
            </div>
            
            <div class="form-group">
                <label for="item_price">Price</label>
                <input type="number" name="item_price" id="item_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="item_description">Description</label>
                <textarea name="item_description" id="item_description" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Create Item</button>
                <button type="button" class="btn-secondary" onclick="closeNewItemModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.invoice-form {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 4px;
}

.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 200px;
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

.item-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    margin-bottom: 10px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.item-row .form-group {
    flex: 1;
    min-width: 150px;
}

.add-item-btn {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 10px;
}

.remove-item {
    background: #f44336;
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
}

.total-section {
    text-align: right;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-primary {
    background: #2196F3;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    text-align: center;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
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
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

.create-new-item {
    font-weight: bold;
    color: #2196F3;
}

optgroup {
    font-weight: bold;
    color: #333;
}

optgroup option {
    font-weight: normal;
    padding-left: 20px;
}
</style>

<script>
function addItem() {
    const container = document.getElementById('items-container');
    const newRow = container.firstElementChild.cloneNode(true);
    
    // Clear values
    newRow.querySelector('.item-select').value = '';
    newRow.querySelector('.quantity').value = '1';
    newRow.querySelector('.unit-price').value = '';
    newRow.querySelector('.description').value = '';
    newRow.querySelector('.amount').value = '';
    
    container.appendChild(newRow);
    
    // Add event listeners to the new row
    addItemEventListeners(newRow);
    
    updateTotals();
}

function removeItem(button) {
    const row = button.closest('.item-row');
    const container = document.getElementById('items-container');
    
    // Don't remove if it's the only row
    if (container.children.length > 1) {
        row.remove();
        updateTotals();
    } else {
        alert("You must have at least one item.");
    }
}

function addItemEventListeners(row) {
    const itemSelect = row.querySelector('.item-select');
    const quantity = row.querySelector('.quantity');
    const unitPrice = row.querySelector('.unit-price');
    const amount = row.querySelector('.amount');
    
    // Update price when item is selected
    itemSelect.addEventListener('change', function() {
        if (this.value === 'new') {
            openNewItemModal();
            this.value = ''; // Reset the selection
        } else {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price;
            unitPrice.value = price;
            updateItemAmount(row);
        }
    });
    
    // Update amount when quantity or price changes
    quantity.addEventListener('input', () => updateItemAmount(row));
    unitPrice.addEventListener('input', () => updateItemAmount(row));
}

function updateItemAmount(row) {
    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
    const amount = quantity * unitPrice;
    row.querySelector('.amount').value = amount.toFixed(2);
    updateTotals();
}

function updateTotals() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const amount = parseFloat(row.querySelector('.amount').value) || 0;
        total += amount;
    });
    document.getElementById('total-amount').textContent = total.toFixed(2);
}

function openNewItemModal() {
    document.getElementById('newItemModal').style.display = 'block';
}

function closeNewItemModal() {
    document.getElementById('newItemModal').style.display = 'none';
}

function toggleParentItem() {
    const itemType = document.getElementById('item_type').value;
    const parentItemGroup = document.getElementById('parentItemGroup');
    const parentId = document.getElementById('parent_id');
    
    if (itemType === 'child') {
        parentItemGroup.style.display = 'block';
        parentId.required = true;
    } else {
        parentItemGroup.style.display = 'none';
        parentId.required = false;
        parentId.value = '';
    }
}
function openSaveTemplateModal() {
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const itemSelect = row.querySelector('.item-select');
        const quantity = row.querySelector('.quantity').value;
        const unitPrice = row.querySelector('.unit-price').value;
        const description = row.querySelector('.description').value;
        
        if (itemSelect.value) {
            items.push({
                item_id: itemSelect.value,
                quantity: quantity,
                unit_price: unitPrice,
                description: description
            });
        }
    });
    
    if (items.length === 0) {
        alert("Please add at least one item before saving as template");
        return;
    }
    
    document.getElementById('template_items').value = JSON.stringify(items);
    document.getElementById('saveTemplateModal').style.display = 'block';
}

function closeSaveTemplateModal() {
    document.getElementById('saveTemplateModal').style.display = 'none';
}

function loadTemplate(templateId) {
    if (!templateId) return;
    
    fetch(`get_template.php?id=${templateId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('items-container');
                
                // Keep first row as template, remove only additional rows
                while (container.children.length > 1) {
                    container.removeChild(container.lastChild);
                }
                
                // Add each item from the template
                data.items.forEach(item => {
                    addItemFromTemplate(item);
                });
                
                updateTotals();
            } else {
                alert('Error loading template: ' + data.error);
            }
        });
}

function addItemFromTemplate(item) {
    const container = document.getElementById('items-container');
    const templateRow = container.children[0]; // Use first row as template
    const newRow = templateRow.cloneNode(true);
    
    // Set values
    const itemSelect = newRow.querySelector('.item-select');
    itemSelect.value = item.item_id;
    
    // Find matching option and get its price
    const option = Array.from(itemSelect.options).find(opt => opt.value == item.item_id);
    if (option) {
        const price = option.dataset.price;
        newRow.querySelector('.unit-price').value = price;
    }
    
    newRow.querySelector('.quantity').value = item.quantity;
    newRow.querySelector('.description').value = item.description || '';
    
    // Append and add event listeners
    container.appendChild(newRow);
    addItemEventListeners(newRow);
    updateItemAmount(newRow);
}

function createNewItem(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('createItem', '1');
    
    fetch('create_invoice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add the new item to the dropdown
            const itemSelect = document.querySelector('.item-select');
            const newOption = document.createElement('option');
            newOption.value = data.item.id;
            
            // Format the display text based on whether it's a parent or sub-item
            let displayText = data.item.name;
            if (data.item.parent_id) {
                // Find the parent item name
                const parentItem = Array.from(itemSelect.options).find(opt => 
                    opt.value === data.item.parent_id.toString()
                );
                if (parentItem) {
                    displayText = parentItem.text.split(' ($')[0] + ' - ' + displayText;
                }
            }
            displayText += ' ($' + parseFloat(data.item.price).toFixed(2) + ')';
            
            newOption.textContent = displayText;
            newOption.dataset.price = data.item.price;
            
            // If it's a sub-item, add it to the appropriate optgroup
            if (data.item.parent_id) {
                const optgroup = itemSelect.querySelector(`optgroup[label="${data.item.parent_name}"]`);
                if (optgroup) {
                    optgroup.appendChild(newOption);
                } else {
                    // Create new optgroup if it doesn't exist
                    const newOptgroup = document.createElement('optgroup');
                    newOptgroup.label = data.item.parent_name;
                    newOptgroup.appendChild(newOption);
                    itemSelect.appendChild(newOptgroup);
                }
            } else {
                itemSelect.appendChild(newOption);
            }
            
            // Select the new item
            newOption.selected = true;
            
            // Update the price field
            const priceInput = document.querySelector('.unit-price');
            priceInput.value = data.item.price;
            
            // Close the modal
            closeNewItemModal();
            
            // Show success message
            alert('Item created successfully!');
        } else {
            alert('Error creating item: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error creating item: ' + error);
    });
    
    return false;
}

// Initialize event listeners for the first row
document.addEventListener('DOMContentLoaded', function() {
    // Set due date to 30 days from now
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 30);
    document.getElementById('due_date').value = dueDate.toISOString().split('T')[0];
    
    // Add event listeners to the first row
    const firstRow = document.querySelector('.item-row');
    if (firstRow) {
        addItemEventListeners(firstRow);
    }
    
    // Initialize totals
    updateTotals();
    
    // Toggle between single/class invoice sections
    document.querySelectorAll('input[name="invoice_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('single-student-section').style.display = 
                this.value === 'single' ? 'block' : 'none';
            document.getElementById('class-section').style.display = 
                this.value === 'class' ? 'block' : 'none';
        });
    });
});
</script>

<div id="saveTemplateModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeSaveTemplateModal()">&times;</span>
        <h3>Save as Template</h3>
        <form method="post">
            <input type="hidden" name="saveTemplate" value="1">
            <div class="form-group">
                <label for="template_name">Template Name</label>
                <input type="text" name="template_name" id="template_name" required>
            </div>
            <input type="hidden" name="template_items" id="template_items">
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Template</button>
                <button type="button" class="btn-secondary" onclick="closeSaveTemplateModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
// Flush the output buffer at the end if it hasn't been flushed yet
if (ob_get_level() > 0) {
    ob_end_flush();
}