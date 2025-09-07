<?php
// Start output buffering to prevent "headers already sent" errors.
ob_start();

// Include necessary configuration, functions, and header files.
require_once 'config.php';
require_once 'functions.php';
require_once 'header.php'; // This now handles session and sets $school_id

// =================================================================
// SECTION 1: PHP DATA PROCESSING
// =================================================================

// HANDLER 1.1: Save New Invoice Template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveTemplate'])) {
    try {
        $templateName = trim($_POST['template_name']);
        $itemsJson = $_POST['template_items'];
        // Get the class_id, allowing it to be null if not selected
        $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

        if (empty($templateName) || empty($itemsJson) || $itemsJson === '[]') {
            throw new Exception("Template name and at least one item are required.");
        }
        
        // Updated SQL query to include class_id
        $stmt = $pdo->prepare("INSERT INTO invoice_templates (school_id, name, class_id, items) VALUES (?, ?, ?, ?)");
        
        // Updated execute call with the new class_id variable
        if ($stmt->execute([$school_id, $templateName, $class_id, $itemsJson])) {
            // Redirect to prevent form resubmission and to show the new template in the list
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=template_saved");
            exit;
        } else {
            throw new Exception("Database error: Could not save the template.");
        }
    } catch (Exception $e) {
        $error = "Error saving template: " . $e->getMessage();
    }
}

// HANDLER 1.2: Create New Invoice (Single or Bulk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createInvoice'])) {
    try {
        // Use null coalescing operator to prevent warning and provide a safe default
        $invoice_type = $_POST['invoice_type'] ?? 'single';
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        $notes = trim($_POST['notes']);

        $items = [];
        if (isset($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $key => $item_id) {
                if (!empty($item_id) && isset($_POST['quantity'][$key]) && $_POST['quantity'][$key] > 0) {
                    $items[] = [
                        'item_id' => $item_id,
                        'description' => $_POST['description'][$key] ?? '',
                        'quantity' => intval($_POST['quantity'][$key]),
                        'unit_price' => floatval($_POST['unit_price'][$key])
                    ];
                }
            }
        }
        if (empty($items)) {
            throw new Exception("An invoice must have at least one valid item with a quantity greater than zero.");
        }

        if ($invoice_type === 'single') {
            $student_id = intval($_POST['student_id']);
            if (empty($student_id)) {
                throw new Exception("A student must be selected for a single invoice.");
            }
            
            $invoice_id = createInvoice($pdo, $school_id, $student_id, $invoice_date, $due_date, $items, $notes);

            log_audit($pdo, 'CREATE', 'invoices', $invoice_id, ['data' => [
                'id' => $invoice_id, 'student_id' => $student_id, 'date' => $invoice_date, 'items_count' => count($items)
            ]]);
            
            header("Location: view_invoice.php?id=" . $invoice_id);
            exit;
        } elseif ($invoice_type === 'class') {
            $class_id = intval($_POST['class_id']);
            if (empty($class_id)) {
                throw new Exception("A class must be selected for bulk invoicing.");
            }
            $students_in_class = getStudentsByClass($pdo, $class_id, $school_id);
            if (empty($students_in_class)) {
                throw new Exception("No active students found in the selected class.");
            }

            $created_count = 0;
            foreach ($students_in_class as $student) {
                createInvoice($pdo, $school_id, $student['id'], $invoice_date, $due_date, $items, $notes);
                $created_count++;
            }
            $_SESSION['success'] = "Successfully created " . $created_count . " invoices for the class.";
            header("Location: customer_center.php?tab=invoices");
            exit;
        }
    } catch (Exception $e) {
        $error = "Error creating invoice: " . $e->getMessage();
    }
}


// =================================================================
// SECTION 2: DATA RETRIEVAL FOR FORM
// =================================================================
$students = getStudents($pdo, $school_id, null, null, 'active');
// This function gets a simple list of all base fee items
$items_list = getItems($pdo, $school_id);
$classes = getClasses($pdo, $school_id);

$stmt = $pdo->prepare("SELECT id, name FROM invoice_templates WHERE school_id = ? ORDER BY name ASC");
$stmt->execute([$school_id]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<style>
    :root {
        --primary-color: #2c7be5;
        --secondary-color: #f1f4f8;
        --border-color: #d8e2ef;
        --text-color: #50668d;
        --text-dark: #12263f;
        --danger-color: #e63757;
        --white-color: #fff;
    }

    .invoice-creation-page {
        background-color: var(--white-color);
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        padding: 2rem;
        max-width: 1200px;
        margin: 2rem auto;
    }

    .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
    .invoice-header .logo-container { font-size: 1.5rem; font-weight: bold; color: var(--text-dark); }
    .invoice-header h1 { margin: 0; font-size: 2rem; text-align: right; }
    .invoice-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
    .form-group label { display: block; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; }
    .form-group input[type="text"], .form-group input[type="date"], .form-group input[type="number"], .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.375rem; background-color: var(--white-color); box-sizing: border-box; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(44, 123, 229, 0.25); outline: none; }
    
    .items-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .items-table thead { background-color: var(--secondary-color); }
    .items-table th { padding: 0.75rem; text-align: left; font-weight: 600; color: var(--text-dark); border-bottom: 2px solid var(--border-color); }
    .items-table td { padding: 0.5rem; vertical-align: top; }
    .items-table tbody tr { border-bottom: 1px solid var(--border-color); }
    .items-table td input, .items-table td select { width: 100%; padding: 0.5rem; border: 1px solid transparent; border-radius: 0.25rem; background-color: transparent; }
    .items-table td input:focus, .items-table td select:focus { border: 1px solid var(--primary-color); background-color: var(--white-color); }
    .items-table .amount-cell { font-weight: 600; text-align: right; padding: 1rem 0.75rem 0 0; }
    .items-table .remove-item { background: none; border: none; color: var(--danger-color); cursor: pointer; font-size: 1.25rem; opacity: 0.5; transition: opacity 0.2s; }
    .items-table tr:hover .remove-item { opacity: 1; }
    
    .invoice-footer { display: flex; justify-content: space-between; margin-top: 2rem; gap: 2rem; flex-wrap: wrap;}
    .notes-section { flex: 2; min-width: 300px; }
    .totals-section { flex: 1; max-width: 350px; min-width: 280px; }
    .totals-summary { background-color: var(--secondary-color); padding: 1.5rem; border-radius: 0.5rem; }
    .total-line { display: flex; justify-content: space-between; margin-bottom: 1rem; }
    .total-line.grand-total { font-size: 1.25rem; font-weight: bold; color: var(--text-dark); border-top: 2px solid var(--border-color); padding-top: 1rem; }
    
    .actions-bar { display: flex; justify-content: flex-end; align-items: center; padding: 1.5rem 0; margin-top: 1.5rem; border-top: 1px solid var(--border-color); gap: 1rem; }
    .template-controls { display: flex; gap: 0.5rem; }
</style>

<div class="container">
<form method="post" id="invoice-form" class="invoice-creation-page">
    <input type="hidden" name="createInvoice" value="1">

    <div class="invoice-header">
        <div class="logo-container"><?php echo htmlspecialchars($current_school_name); ?></div>
        <h1>New Invoice</h1>
    </div>

    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if (isset($success) || isset($_GET['success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($success ?? 'Template saved successfully!'); ?></div><?php endif; ?>

    <div class="invoice-details-grid">
         <div class="form-group">
            <label for="academic_year">Academic Year</label>
            <input type="text" id="academic_year" name="academic_year" class="form-control" value="<?= date('Y') . '-' . (date('Y') + 1) ?>" required onchange="loadFees()">
        </div>
        <div class="form-group">
            <label for="term">Term</label>
            <select id="term" name="term" class="form-control" required onchange="loadFees()">
                <option>Term 1</option>
                <option>Term 2</option>
                <option>Term 3</option>
            </select>
        </div>
        <div class="form-group">
            <label for="student_id">Bill To</label>
            <div id="student-section">
                <select name="student_id" id="student_id" class="form-control" onchange="loadFees()" required>
                    <option value="">Select Student to auto-load fees...</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
             <div id="class-section" class="form-group" style="display: none;">
                <select name="class_id" id="class_id" onchange="loadFees()">
                    <option value="">Select Class to auto-load fees...</option>
                    <?php foreach ($classes as $class): ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
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
    
    <div>
        <div class="form-group">
            <label>Invoice For</label>
            <div class="radio-group">
                <label><input type="radio" name="invoice_type" value="single" checked> Single Student</label>
                <label><input type="radio" name="invoice_type" value="class"> Entire Class</label>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead><tr><th style="width: 30%;">Item</th><th style="width: 30%;">Description</th><th style="width: 10%;">Qty</th><th style="width: 15%;">Rate</th><th style="width: 15%; text-align: right;">Amount</th><th></th></tr></thead>
        <tbody id="items-container"></tbody>
    </table>
    
    <button type="button" class="btn-secondary" id="add-item-btn" onclick="addItemRow(null, true)">+ Add Line</button>


    <div id="optional-items-container" style="margin-top: 1rem;"></div>

    <footer class="invoice-footer">
        <div class="notes-section">
            <div class="form-group"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="4" placeholder="Enter notes or payment instructions..."></textarea></div>
            <div class="form-group"><label for="template_select">Templates</label><div class="template-controls"><select id="template_select"><option value="">Load from template...</option><?php foreach ($templates as $template): ?><option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option><?php endforeach; ?></select><button type="button" class="btn-secondary" onclick="openSaveTemplateModal()">Save as Template</button></div></div>
        </div>
        <div class="totals-section">
            <div class="totals-summary"><div class="total-line"><span>Subtotal</span><span id="subtotal-amount">$0.00</span></div><div class="total-line grand-total"><span>Total</span><span id="total-amount">$0.00</span></div></div>
        </footer>

    <div class="actions-bar">
        <a href="customer_center.php" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">Create Invoice</button>
    </div>
</form>
</div>

<div id="saveTemplateModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Save Invoice as Template</h3>
            <span class="close" onclick="closeModal('saveTemplateModal')">&times;</span>
        </div>
        <form id="saveTemplateForm" method="post">
            <input type="hidden" name="saveTemplate" value="1">
            <div class="modal-body">
                <p>Save the current set of items as a reusable template.</p>
                <div class="form-group">
                    <label for="template_name">Template Name</label>
                    <input type="text" name="template_name" id="template_name" required placeholder="e.g., Grade 1 Term Fees" class="form-control">
                </div>
                <div class="form-group">
                    <label for="template_class_id">Link to Class (Optional)</label>
                    <select name="class_id" id="template_class_id" class="form-control">
                        <option value="">-- No Specific Class --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="template_items" id="template_items">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('saveTemplateModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>

<script>
// Pass PHP arrays to JavaScript
const allItems = <?php echo json_encode($items_list); ?>;
let isTemplateLoaded = false; // **NEW**: State variable to track if a template is active

function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
function removeItem(btn){ 
    btn.closest("tr").remove(); 
    isTemplateLoaded = false; // **MODIFICATION**: Unlock the form on manual change
    updateTotals(); 
}

function addItemEventListeners(row){
    row.querySelector(".item-select")?.addEventListener("change", function() {
        const selectedOption = this.options[this.selectedIndex];
        row.querySelector(".description").value = selectedOption.dataset.description || this.options[this.selectedIndex].text;
    });
    row.querySelector(".quantity").addEventListener("input", () => updateItemAmount(row));
    row.querySelector(".unit-price").addEventListener("input", () => updateItemAmount(row));
}

function updateItemAmount(row){
    const quantity = parseFloat(row.querySelector(".quantity").value) || 0;
    const unitPrice = parseFloat(row.querySelector(".unit-price").value) || 0;
    row.querySelector(".amount-cell").textContent = "$" + (quantity * unitPrice).toFixed(2);
    updateTotals();
}

function updateTotals(){
    let total = 0;
    document.querySelectorAll("#items-container tr").forEach(row => {
        const quantity = parseFloat(row.querySelector(".quantity").value) || 0;
        const unitPrice = parseFloat(row.querySelector(".unit-price").value) || 0;
        total += quantity * unitPrice;
    });
    document.getElementById("subtotal-amount").textContent = "$" + total.toFixed(2);
    document.getElementById("total-amount").textContent = "$" + total.toFixed(2);
}

function openSaveTemplateModal() {
    const items = [];
    document.querySelectorAll('#items-container tr').forEach(row => {
        const itemSelect = row.querySelector('.item-select');
        const itemIdInput = row.querySelector('input[name="item_id[]"]');
        const itemId = itemSelect ? itemSelect.value : (itemIdInput ? itemIdInput.value : null);

        if (itemId) {
            items.push({ 
                item_id: itemId, 
                description: row.querySelector('.description').value, 
                quantity: row.querySelector('.quantity').value, 
                unit_price: row.querySelector('.unit-price').value 
            });
        }
    });
    if (items.length === 0) { alert("Please add at least one item to save as a template."); return; }
    document.getElementById('template_items').value = JSON.stringify(items);
    openModal('saveTemplateModal');
}

/**
 * Universal function to add an item row to the invoice table.
 * @param {object|null} item - Object with item details (item_id, item_name, amount, etc.). If null, adds a blank row.
 * @param {boolean} isManual - If true, the row is fully editable with a dropdown. If false, it's for auto-loaded fees.
 */
function addItemRow(item = null, isManual = true) {
    // **MODIFICATION**: If adding a manual row, unlock the form from template mode
    if (isManual) {
        isTemplateLoaded = false;
    }

    const container = document.getElementById("items-container");
    const newRow = container.insertRow();
    
    let itemCellHtml;
    if (isManual) {
        let optionsHtml = allItems.map(i => `<option value="${i.id}" data-description="${i.description || ''}">${i.name}</option>`).join('');
        itemCellHtml = `<td><select name="item_id[]" class="item-select"><option value="">Select Item...</option>${optionsHtml}</select></td>`;
    } else {
        itemCellHtml = `<td><input type="hidden" name="item_id[]" value="${item.item_id}"><span>${item.item_name}</span></td>`;
    }

    newRow.innerHTML = `
        ${itemCellHtml}
        <td><input type="text" name="description[]" class="description" placeholder="Item description"></td>
        <td><input type="number" name="quantity[]" class="quantity" min="1" value="1" required></td>
        <td><input type="number" name="unit_price[]" class="unit-price" step="0.01" value="0.00" required></td>
        <td class="amount-cell">$0.00</td>
        <td><button type="button" class="remove-item" onclick="removeItem(this)">×</button></td>
    `;

    if (item) {
        if(isManual) newRow.querySelector('.item-select').value = item.item_id;
        newRow.querySelector('.description').value = item.description || item.item_name || '';
        newRow.querySelector('.quantity').value = item.quantity || 1;
        newRow.querySelector('.unit-price').value = parseFloat(item.amount || item.unit_price || 0).toFixed(2);
    }
    
    if (!isManual) {
         newRow.querySelector('.remove-item').style.display = 'none';
    }
    
    addItemEventListeners(newRow);
    updateItemAmount(newRow);
}


function loadFees() {
    // **MODIFICATION**: Prevent auto-loading if a template is active
    if (isTemplateLoaded) {
        return;
    }

    const isSingleStudentMode = document.querySelector('input[name="invoice_type"]:checked').value === 'single';
    const studentId = document.getElementById('student_id').value;
    const classId = document.getElementById('class_id').value;
    const academicYear = document.getElementById('academic_year').value;
    const term = document.getElementById('term').value;
    const itemsContainer = document.getElementById('items-container');
    const optionalContainer = document.getElementById('optional-items-container');
    
    itemsContainer.innerHTML = '';
    optionalContainer.innerHTML = '';

    if (!academicYear || !term) {
        updateTotals();
        return;
    }

    let fetchUrl = '';
    if (isSingleStudentMode && studentId) {
        fetchUrl = `get_student_fees.php?student_id=${studentId}&academic_year=${encodeURIComponent(academicYear)}&term=${encodeURIComponent(term)}`;
    } else if (!isSingleStudentMode && classId) {
        fetchUrl = `get_student_fees.php?class_id=${classId}&academic_year=${encodeURIComponent(academicYear)}&term=${encodeURIComponent(term)}`;
    } else {
        updateTotals();
        return;
    }

    fetch(fetchUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                data.mandatory_items.forEach(item => addItemRow(item, false));
                if (data.optional_items.length > 0) addOptionalItemsSelector(data.optional_items);
                updateTotals();
            } else {
                alert('Error loading fees: ' + data.error);
                updateTotals();
            }
        })
        .catch(err => console.error('Fetch Error:', err));
}

function addOptionalItemsSelector(optionalItems) {
    const container = document.getElementById('optional-items-container');
    let optionsHtml = optionalItems.map(item => 
        `<option value='${JSON.stringify(item)}'>${item.item_name} - $${parseFloat(item.amount).toFixed(2)}</option>`
    ).join('');
    container.innerHTML = `<hr><div class="form-group" style="max-width: 400px; display: inline-block;"><label>Add Optional Service</label><select id="optional-item-dropdown" class="form-control"><option value="">Select an optional item...</option>${optionsHtml}</select></div> <button type="button" class="btn-secondary" onclick="addSelectedOptionalItem()">+ Add to Invoice</button>`;
}

function addSelectedOptionalItem() {
    const dropdown = document.getElementById('optional-item-dropdown');
    if (dropdown.value) {
        const item = JSON.parse(dropdown.value);
        item.description = item.item_name; 
        addItemRow(item, true); 
        dropdown.selectedIndex = 0; 
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 30);
    document.getElementById('due_date').valueAsDate = dueDate;
    
    document.querySelectorAll('input[name="invoice_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const isSingle = (this.value === 'single');
            document.getElementById('student-section').style.display = isSingle ? 'block' : 'none';
            document.getElementById('class-section').style.display = isSingle ? 'none' : 'block';
            document.getElementById('student_id').required = isSingle;
            document.getElementById('class_id').required = !isSingle;
            
            isTemplateLoaded = false; // Always unlock when switching modes
            loadFees();
        });
    });

    document.getElementById('template_select').addEventListener('change', function() {
        const templateId = this.value;
        if (!templateId) return;

        fetch(`get_template.php?id=${templateId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('items-container').innerHTML = '';
                    document.getElementById('optional-items-container').innerHTML = '';
                    data.items.forEach(item => {
                        const fullItem = allItems.find(i => i.id == item.item_id);
                        if (fullItem) {
                            item.item_name = fullItem.name;
                            addItemRow(item, true);
                        }
                    });
                    // **MODIFICATION**: Lock the form after successfully loading template
                    isTemplateLoaded = true; 
                    updateTotals();
                    this.selectedIndex = 0; // Reset dropdown
                } else {
                    alert('Error loading template: ' + data.error);
                }
            });
    });
    
    document.querySelector('input[name="invoice_type"]:checked').dispatchEvent(new Event('change'));
    
    if (document.getElementById('student_id').value) {
        loadFees();
    }
});
</script>

<?php
include 'footer.php';
if (ob_get_level() > 0) ob_end_flush();
?>