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
        if (empty($templateName) || empty($itemsJson)) {
            throw new Exception("Template name and items are required.");
        }
        $stmt = $pdo->prepare("INSERT INTO invoice_templates (school_id, name, items) VALUES (?, ?, ?)");
        if ($stmt->execute([$school_id, $templateName, $itemsJson])) {
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
        $invoice_type = $_POST['invoice_type'];
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        $notes = trim($_POST['notes']);

        $items = [];
        if (isset($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $key => $item_id) {
                if (!empty($item_id) && !empty($_POST['quantity'][$key])) {
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
            throw new Exception("An invoice must have at least one item.");
        }

        // In create_invoice.php, inside the handler for creating a single invoice

        if ($invoice_type === 'single') {
            $student_id = intval($_POST['student_id']);
            if (empty($student_id)) {
                throw new Exception("A student must be selected for a single invoice.");
            }
            
            // The createInvoice function already returns the new ID, which is perfect.
            $invoice_id = createInvoice($pdo, $school_id, $student_id, $invoice_date, $due_date, $items, $notes);

            // --- AUDIT TRAIL: Log the creation ---
            $invoice_data = [
                'id' => $invoice_id,
                'school_id' => $school_id,
                'student_id' => $student_id,
                'invoice_date' => $invoice_date,
                'due_date' => $due_date,
                'items' => $items, // Log the items included
                'notes' => $notes
            ];
            log_audit($pdo, 'CREATE', 'invoices', $invoice_id, ['data' => $invoice_data]);
            // ---
            
            header("Location: view_invoice.php?id=" . $invoice_id);
            exit;
        } elseif ($invoice_type === 'class') {
            $class_id = intval($_POST['class_id']);
            if (empty($class_id)) {
                throw new Exception("A class must be selected for bulk invoicing.");
            }
            $students_in_class = getStudentsByClass($pdo, $class_id, $school_id);
            if (empty($students_in_class)) {
                throw new Exception("No students found in the selected class.");
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
$students = getStudents($pdo, $school_id);
$items_list = getItemsWithSubItems($pdo, $school_id);
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
    
    .invoice-footer { display: flex; justify-content: space-between; margin-top: 2rem; gap: 2rem; }
    .notes-section { flex: 2; }
    .totals-section { flex: 1; max-width: 350px; }
    .totals-summary { background-color: var(--secondary-color); padding: 1.5rem; border-radius: 0.5rem; }
    .total-line { display: flex; justify-content: space-between; margin-bottom: 1rem; }
    .total-line.grand-total { font-size: 1.25rem; font-weight: bold; color: var(--text-dark); border-top: 2px solid var(--border-color); padding-top: 1rem; }
    
    .actions-bar { display: flex; justify-content: flex-end; align-items: center; padding: 1.5rem 0; margin-top: 1.5rem; border-top: 1px solid var(--border-color); gap: 1rem; }
    .template-controls { display: flex; gap: 0.5rem; }
    .btn-add-item { margin-top: 1rem; }
</style>

<template id="item-row-template">
    <tr>
        <td>
            <select name="item_id[]" class="item-select" required>
                <option value="">Select Item...</option>
                <option value="new" class="create-new-item-option">+ Add new</option>
                <?php foreach ($items_list as $item): ?>
                    <?php if (empty($item['sub_items'])): ?>
                        <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </option>
                    <?php else: ?>
                        <optgroup label="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php foreach ($item['sub_items'] as $sub_item): ?>
                                <option value="<?php echo $sub_item['id']; ?>" data-price="<?php echo $sub_item['price']; ?>">
                                    <?php
                                        $parentName = $item['name'];
                                        $subItemUniqueName = trim(str_replace($parentName, '', $sub_item['name']));
                                        echo htmlspecialchars($parentName . " (" . $subItemUniqueName . ")");
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description[]" class="description" placeholder="Item description"></td>
        <td><input type="number" name="quantity[]" class="quantity" min="1" value="1" required></td>
        <td><input type="number" name="unit_price[]" class="unit-price" step="0.01" required></td>
        <td class="amount-cell">$0.00</td>
        <td><button type="button" class="remove-item" onclick="removeItem(this)">×</button></td>
    </tr>
</template>

<div class="container">
<form method="post" id="invoice-form" class="invoice-creation-page">
    <input type="hidden" name="createInvoice" value="1">

    <header class="invoice-header">
        <div class="logo-container"><?php echo htmlspecialchars($current_school_name); ?></div>
        <h1>New Invoice</h1>
    </header>

    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if (isset($success) || isset($_GET['success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($success ?? 'Template saved successfully!'); ?></div><?php endif; ?>

    <div class="invoice-details-grid">
        <div class="form-group">
            <label for="student_id">Bill To</label>
            <select name="student_id" id="student_id">
                <option value="">Select Student...</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>" <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <div class="form-group">
                <label>Invoice For</label>
                <div class="radio-group">
                    <label><input type="radio" name="invoice_type" value="single" checked> Single Student</label>
                    <label><input type="radio" name="invoice_type" value="class"> Entire Class</label>
                </div>
            </div>
            <div id="class-section" class="form-group" style="display: none;">
                <label for="class_id" class="sr-only">Class</label>
                <select name="class_id" id="class_id">
                    <option value="">Select Class...</option>
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

    <table class="items-table">
        <thead><tr><th style="width: 30%;">Item</th><th style="width: 30%;">Description</th><th style="width: 10%;">Qty</th><th style="width: 15%;">Rate</th><th style="width: 15%; text-align: right;">Amount</th><th></th></tr></thead>
        <tbody id="items-container"></tbody>
    </table>

    <button type="button" class="btn-secondary btn-add-item" onclick="addItem()">+ Add line</button>

    <footer class="invoice-footer">
        <div class="notes-section">
            <div class="form-group"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="4" placeholder="Enter notes or payment instructions..."></textarea></div>
            <div class="form-group"><label for="template_select">Templates</label><div class="template-controls"><select id="template_select"><option value="">Load from template...</option><?php foreach ($templates as $template): ?><option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option><?php endforeach; ?></select><button type="button" class="btn-secondary" onclick="openSaveTemplateModal()">Save as Template</button></div></div>
        </div>
        <div class="totals-section">
            <div class="totals-summary"><div class="total-line"><span>Subtotal</span><span id="subtotal-amount">$0.00</span></div><div class="total-line grand-total"><span>Total</span><span id="total-amount">$0.00</span></div></div>
        </div>
    </footer>

    <div class="actions-bar">
        <a href="customer_center.php" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">Create Invoice</button>
    </div>
</form>
</div>

<div id="saveTemplateModal" class="modal" style="display: none;"><div class="modal-content"><div class="modal-header"><h3>Save Invoice as Template</h3><span class="close" onclick="closeModal('saveTemplateModal')">&times;</span></div><form id="saveTemplateForm" method="post"><input type="hidden" name="saveTemplate" value="1"><div class="modal-body"><p>Save the current set of items as a reusable template.</p><div class="form-group"><label for="template_name">Template Name</label><input type="text" name="template_name" id="template_name" required placeholder="e.g., Monthly Tuition Fees" class="form-control"></div><input type="hidden" name="template_items" id="template_items"></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('saveTemplateModal')">Cancel</button><button type="submit" class="btn-primary">Save Template</button></div></form></div></div>

<div id="newItemModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header"><h3>Create New Item/Service</h3><span class="close" onclick="closeNewItemModal()">&times;</span></div>
        <form id="newItemForm" onsubmit="event.preventDefault(); createNewItem();">
            <div class="modal-body">
                <div class="form-group"><label for="new_item_name">Item Name</label><input type="text" id="new_item_name" class="form-control" required></div>
                <div class="form-group"><label for="new_item_price">Price</label><input type="number" id="new_item_price" class="form-control" step="0.01" required></div>
                <div class="form-group"><label for="new_item_description">Description</label><textarea id="new_item_description" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label for="new_parent_id">Parent Item (for sub-items)</label><select id="new_parent_id" class="form-control"><option value="">None (This is a main item)</option><?php foreach ($items_list as $item): if(empty($item['parent_id'])): ?><option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></option><?php endif; endforeach; ?></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeNewItemModal()">Cancel</button><button type="submit" class="btn-primary">Create Item</button></div>
        </form>
    </div>
</div>

<script>
let activeItemSelect = null;

function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
function addItem() { const t=document.getElementById("items-container"),e=document.getElementById("item-row-template").content.cloneNode(!0);t.appendChild(e);const n=t.lastElementChild;addItemEventListeners(n),n.querySelector(".item-select").focus() }
function removeItem(t){t.closest("tr").remove(),updateTotals()}
function addItemEventListeners(t){t.querySelector(".item-select").addEventListener("change",function(){handleItemSelect(this,t)}),t.querySelector(".quantity").addEventListener("input",()=>updateItemAmount(t)),t.querySelector(".unit-price").addEventListener("input",()=>updateItemAmount(t))}
function handleItemSelect(t,e){if("new"===t.value)openNewItemModal(t);else{const n=t.options[t.selectedIndex];e.querySelector(".unit-price").value=n.dataset.price||"0.00",updateItemAmount(e)}}
function updateItemAmount(t){const e=parseFloat(t.querySelector(".quantity").value)||0,n=parseFloat(t.querySelector(".unit-price").value)||0;t.querySelector(".amount-cell").textContent="$"+(e*n).toFixed(2),updateTotals()}
function updateTotals(){let t=0;document.querySelectorAll("#items-container tr").forEach(e=>{const n=parseFloat(e.querySelector(".quantity").value)||0,o=parseFloat(e.querySelector(".unit-price").value)||0;t+=n*o}),document.getElementById("subtotal-amount").textContent="$"+t.toFixed(2),document.getElementById("total-amount").textContent="$"+t.toFixed(2)}

function openSaveTemplateModal() {
    const items = [];
    document.querySelectorAll('#items-container tr').forEach(row => {
        const itemSelect = row.querySelector('.item-select');
        if (itemSelect.value && itemSelect.value !== 'new') {
            items.push({ item_id: itemSelect.value, description: row.querySelector('.description').value, quantity: row.querySelector('.quantity').value, unit_price: row.querySelector('.unit-price').value });
        }
    });
    if (items.length === 0) { alert("Please add at least one item to save as a template."); return; }
    document.getElementById('template_items').value = JSON.stringify(items);
    openModal('saveTemplateModal');
}

document.getElementById('template_select').addEventListener('change', function() {
    const templateId = this.value;
    if (!templateId) return;
    fetch(`get_template.php?id=${templateId}`).then(res => res.json()).then(data => {
        if (data.success && data.items) {
            const container = document.getElementById('items-container');
            container.innerHTML = '';
            data.items.forEach(item => {
                addItem();
                const newRow = container.lastElementChild;
                newRow.querySelector('.item-select').value = item.item_id;
                newRow.querySelector('.description').value = item.description || '';
                newRow.querySelector('.quantity').value = item.quantity;
                newRow.querySelector('.unit-price').value = item.unit_price;
                updateItemAmount(newRow);
            });
            this.value = '';
        } else { alert('Error loading template: ' + (data.error || 'Unknown error')); }
    }).catch(error => console.error('Error fetching template:', error));
});

function openNewItemModal(selectElement) {
    activeItemSelect = selectElement;
    openModal('newItemModal');
    document.getElementById('new_item_name').focus();
}

function closeNewItemModal() {
    if (activeItemSelect) { activeItemSelect.value = ''; }
    document.getElementById('newItemForm').reset();
    closeModal('newItemModal');
    activeItemSelect = null;
}

function createNewItem() {
    const formData = new FormData();
    const parentId = document.getElementById('new_parent_id').value;
    formData.append('name', document.getElementById('new_item_name').value);
    formData.append('price', document.getElementById('new_item_price').value);
    formData.append('description', document.getElementById('new_item_description').value);
    formData.append('parent_id', parentId);
    
    fetch('create_item.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Item created successfully! Reloading to update the item list.');
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'An unknown error occurred.'));
        }
    })
    .catch(error => console.error('Error:', error));
}


document.addEventListener('DOMContentLoaded', function() {
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 30);
    document.getElementById('due_date').valueAsDate = dueDate;
    addItem();
    document.querySelectorAll('input[name="invoice_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const isSingle = (this.value === 'single');
            document.getElementById('student_id').closest('.form-group').style.display = isSingle ? 'block' : 'none';
            document.getElementById('class-section').style.display = isSingle ? 'none' : 'block';
            document.getElementById('student_id').required = isSingle;
            document.getElementById('class_id').required = !isSingle;
        });
    });
    document.querySelector('input[name="invoice_type"]:checked').dispatchEvent(new Event('change'));
});
</script>

<?php
include 'footer.php';
if (ob_get_level() > 0) ob_end_flush();
?>