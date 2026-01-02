<?php
ob_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'header.php'; // Ensure this includes Tailwind CSS CDN or your local build

// =================================================================
// SECTION 1: PHP DATA PROCESSING (Your Logic)
// =================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveTemplate'])) {
    try {
        $templateName = trim($_POST['template_name']);
        $itemsJson = $_POST['template_items'];
        $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

        if (empty($templateName) || empty($itemsJson) || $itemsJson === '[]') {
            throw new Exception("Template name and items are required.");
        }
        
        $stmt = $pdo->prepare("INSERT INTO invoice_templates (school_id, name, class_id, items) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$school_id, $templateName, $class_id, $itemsJson])) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=template_saved");
            exit;
        }
    } catch (Exception $e) { $error = $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createInvoice'])) {
    try {
        $invoice_type = $_POST['invoice_type'] ?? 'single';
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        $notes = trim($_POST['notes']);

        $items = [];
        if (isset($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $key => $item_id) {
                if (!empty($item_id) && $_POST['quantity'][$key] > 0) {
                    $items[] = [
                        'item_id' => $item_id,
                        'description' => $_POST['description'][$key] ?? '',
                        'quantity' => intval($_POST['quantity'][$key]),
                        'unit_price' => floatval($_POST['unit_price'][$key])
                    ];
                }
            }
        }
        if (empty($items)) throw new Exception("At least one valid item is required.");

        if ($invoice_type === 'single') {
            $student_id = intval($_POST['student_id']);
            $invoice_id = createInvoice($pdo, $school_id, $student_id, $invoice_date, $due_date, $items, $notes);
            header("Location: view_invoice.php?id=" . $invoice_id);
            exit;
        } elseif ($invoice_type === 'class') {
            $class_id = intval($_POST['class_id']);
            $students_in_class = getStudentsByClass($pdo, $class_id, $school_id);
            foreach ($students_in_class as $student) {
                createInvoice($pdo, $school_id, $student['id'], $invoice_date, $due_date, $items, $notes);
            }
            $_SESSION['success'] = "Bulk invoices created successfully.";
            header("Location: customer_center.php?tab=invoices");
            exit;
        }
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// Data Retrieval
$students = getStudents($pdo, $school_id, null, null, 'active');
$items_list = getItems($pdo, $school_id);
$classes = getClasses($pdo, $school_id);
$stmt = $pdo->prepare("SELECT id, name FROM invoice_templates WHERE school_id = ? ORDER BY name ASC");
$stmt->execute([$school_id]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">



<div class="min-h-screen bg-gray-50/50 py-12 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="mb-6">
    <a href="customer_center.php" class="inline-flex items-center group text-sm font-medium text-gray-500 hover:text-black transition-colors">
        <i class="fas fa-arrow-left mr-2 text-[10px] group-hover:-translate-x-1 transition-transform"></i>
        Back
    </a>
</div>
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" id="invoice-form" class="space-y-8">
            <input type="hidden" name="createInvoice" value="1">

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                
                <div class="p-8 border-b border-gray-100 bg-gray-50/30 flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight"><?= htmlspecialchars($current_school_name) ?></h2>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mt-1">Invoice Generator</p>
                    </div>
                    <div class="text-right">
                        <span class="text-4xl font-light text-gray-200 uppercase tracking-tighter">Draft</span>
                    </div>
                </div>

                <div class="p-8 space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Academic Period</label>
                                <div class="grid grid-cols-2 gap-2 mt-1">
                                    <input type="text" name="academic_year" class="block w-full rounded-md border-gray-200 text-sm focus:ring-black focus:border-black" value="<?= date('Y') . '-' . (date('Y') + 1) ?>" required>
                                    <select name="term" class="block w-full rounded-md border-gray-200 text-sm">
                                        <option>Term 1</option><option>Term 2</option><option>Term 3</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Bill To</label>
                                <div class="flex p-1 bg-gray-100 rounded-lg w-max mb-3 mt-1">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="invoice_type" value="single" class="sr-only peer" checked>
                                        <span class="px-4 py-1.5 text-xs font-medium rounded-md peer-checked:bg-white peer-checked:shadow-sm inline-block transition-all">Student</span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="invoice_type" value="class" class="sr-only peer">
                                        <span class="px-4 py-1.5 text-xs font-medium rounded-md peer-checked:bg-white peer-checked:shadow-sm inline-block transition-all">Entire Class</span>
                                    </label>
                                </div>

                                <div id="student-section">
                                    <select name="student_id" id="student_id" class="block w-full rounded-md border-gray-200 text-sm focus:ring-black focus:border-black" onchange="loadFees()">
                                        <option value="">Search for a student...</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="class-section" style="display:none">
                                    <select name="class_id" id="class_id" class="block w-full rounded-md border-gray-200 text-sm focus:ring-black focus:border-black" onchange="loadFees()">
                                        <option value="">Select a class...</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 pt-8 border-t border-gray-50">
                        <div>
                            <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Invoice Date</label>
                            <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" class="block w-full border-0 p-0 mt-1 text-sm font-medium focus:ring-0">
                        </div>
                        <div class="border-l border-gray-100 pl-8">
                            <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="block w-full border-0 p-0 mt-1 text-sm font-medium focus:ring-0">
                        </div>
                    </div>

                    <div class="pt-4">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="text-[10px] font-bold uppercase text-gray-400 tracking-widest border-b border-gray-100">
                                    <th class="pb-4 w-[30%]">Service Item</th>
                                    <th class="pb-4 w-[30%]">Description</th>
                                    <th class="pb-4 w-[10%] text-center">Qty</th>
                                    <th class="pb-4 w-[15%] text-right">Rate</th>
                                    <th class="pb-4 w-[15%] text-right">Amount</th>
                                    <th class="pb-4 w-[40px]"></th>
                                </tr>
                            </thead>
                            <tbody id="items-container" class="divide-y divide-gray-50">
                                </tbody>
                        </table>
                        <button type="button" onclick="addItemRow(null, true)" class="mt-6 inline-flex items-center text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors uppercase tracking-tighter">
                            <i class="fas fa-plus-circle mr-2"></i> Add Custom Line
                        </button>
                    </div>

                    <div id="optional-items-container" class="py-4"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-16 pt-10 border-t border-gray-100">
                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Notes</label>
                                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-200 text-sm focus:ring-black focus:border-black" placeholder="Payment terms, bank details, etc..."></textarea>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                <label class="text-[10px] font-bold text-gray-500 uppercase">Load from Template</label>
                                <div class="flex gap-2 mt-2">
                                    <select id="template_select" class="block w-full rounded-md border-gray-200 text-xs">
                                        <option value="">Choose a template...</option>
                                        <?php foreach ($templates as $t): ?>
                                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" onclick="openSaveTemplateModal()" class="px-3 py-1 bg-white border border-gray-200 rounded text-[10px] font-bold hover:bg-gray-100 transition-all uppercase">Save</button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 bg-gray-50/50 p-6 rounded-2xl h-fit">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 font-medium">Subtotal</span>
                                <span id="subtotal-amount" class="text-gray-900 font-mono">KSH 0.00</span>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                                <span class="text-base font-bold text-gray-900">Total Due</span>
                                <span id="total-amount" class="text-2xl font-black text-black font-mono tracking-tighter">KSH 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-900 px-8 py-4 flex justify-between items-center">
                    <a href="customer_center.php" class="text-xs font-bold text-gray-400 hover:text-white uppercase tracking-widest transition-colors">Discard Draft</a>
                    <button type="submit" class="bg-white text-black px-8 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-100 transition-all shadow-xl">
                        Generate & Finalize
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="saveTemplateModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">Save as Template</h3>
            <button onclick="closeModal('saveTemplateModal')" class="text-gray-400 hover:text-black">&times;</button>
        </div>
        <form id="saveTemplateForm" method="post" class="p-6 space-y-4">
            <input type="hidden" name="saveTemplate" value="1">
            <input type="hidden" name="template_items" id="template_items">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Template Name</label>
                <input type="text" name="template_name" required class="mt-1 block w-full rounded-md border-gray-200 text-sm" placeholder="e.g. Grade 1 Term 1 Fees">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Class Link (Optional)</label>
                <select name="class_id" class="mt-1 block w-full rounded-md border-gray-200 text-sm">
                    <option value="">None</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('saveTemplateModal')" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800">Save Template</button>
            </div>
        </form>
    </div>
</div>

<script>
const allItems = <?= json_encode($items_list); ?>;
let isTemplateLoaded = false;

function formatCurrencyJS(amount) {
    return 'KSH ' + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
}

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function addItemRow(item = null, isManual = true) {
    if (isManual) isTemplateLoaded = false;
    const container = document.getElementById("items-container");
    const row = document.createElement('tr');
    row.className = "group hover:bg-gray-50/50 transition-colors";
    
    let itemHtml = isManual 
        ? `<td class="py-4"><select name="item_id[]" class="item-select w-full border-gray-200 rounded-md text-sm"><option value="">Select Item...</option>${allItems.map(i => `<option value="${i.id}" data-description="${i.description || ''}">${i.name}</option>`).join('')}</select></td>`
        : `<td class="py-4 font-semibold text-gray-900"><input type="hidden" name="item_id[]" value="${item.item_id}"><span>${item.item_name}</span></td>`;

    row.innerHTML = `
        ${itemHtml}
        <td class="py-4 px-2"><input type="text" name="description[]" class="description w-full border-transparent bg-transparent focus:border-gray-200 rounded-md text-sm" placeholder="Details"></td>
        <td class="py-4 px-2"><input type="number" name="quantity[]" class="quantity w-full border-transparent bg-transparent focus:border-gray-200 rounded-md text-sm text-center" value="1"></td>
        <td class="py-4 px-2"><input type="number" name="unit_price[]" class="unit-price w-full border-transparent bg-transparent focus:border-gray-200 rounded-md text-sm text-right font-mono" step="0.01" value="0.00"></td>
        <td class="py-4 text-right font-mono font-bold amount-cell text-gray-900">0.00</td>
        <td class="py-4 text-right">
            <button type="button" onclick="this.closest('tr').remove(); updateTotals();" class="text-gray-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                <i class="fas fa-times-circle"></i>
            </button>
        </td>
    `;

    container.appendChild(row);
    if (item) {
        if(isManual && row.querySelector('.item-select')) row.querySelector('.item-select').value = item.item_id;
        row.querySelector('.description').value = item.description || item.item_name || '';
        row.querySelector('.quantity').value = item.quantity || 1;
        row.querySelector('.unit-price').value = parseFloat(item.amount || item.unit_price || 0).toFixed(2);
    }
    
    row.querySelector(".quantity").addEventListener("input", () => updateRow(row));
    row.querySelector(".unit-price").addEventListener("input", () => updateRow(row));
    if(isManual) row.querySelector(".item-select").addEventListener("change", function() {
        row.querySelector(".description").value = this.options[this.selectedIndex].dataset.description || this.options[this.selectedIndex].text;
    });
    updateRow(row);
}

function updateRow(row) {
    const q = parseFloat(row.querySelector(".quantity").value) || 0;
    const p = parseFloat(row.querySelector(".unit-price").value) || 0;
    row.querySelector(".amount-cell").textContent = (q * p).toFixed(2);
    updateTotals();
}

function updateTotals() {
    let total = 0;
    document.querySelectorAll(".amount-cell").forEach(cell => total += parseFloat(cell.textContent) || 0);
    document.getElementById("subtotal-amount").textContent = formatCurrencyJS(total);
    document.getElementById("total-amount").textContent = formatCurrencyJS(total);
}

function openSaveTemplateModal() {
    const items = [];
    document.querySelectorAll('#items-container tr').forEach(row => {
        const id = row.querySelector('[name="item_id[]"]').value;
        if (id) items.push({ 
            item_id: id, 
            description: row.querySelector('.description').value, 
            quantity: row.querySelector('.quantity').value, 
            unit_price: row.querySelector('.unit-price').value 
        });
    });
    if (!items.length) return alert("Add items first");
    document.getElementById('template_items').value = JSON.stringify(items);
    openModal('saveTemplateModal');
}

function loadFees() {
    if (isTemplateLoaded) return;
    const type = document.querySelector('input[name="invoice_type"]:checked').value;
    const sid = document.getElementById('student_id').value;
    const cid = document.getElementById('class_id').value;
    const yr = document.getElementsByName('academic_year')[0].value;
    const trm = document.getElementsByName('term')[0].value;

    if ((type === 'single' && !sid) || (type === 'class' && !cid)) return;

    fetch(`get_student_fees.php?${type}_id=${type === 'single' ? sid : cid}&academic_year=${yr}&term=${trm}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('items-container').innerHTML = '';
                data.mandatory_items.forEach(i => addItemRow(i, false));
                updateTotals();
            }
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const d = new Date(); d.setDate(d.getDate() + 30);
    document.getElementById('due_date').valueAsDate = d;

    document.querySelectorAll('input[name="invoice_type"]').forEach(r => {
        r.addEventListener('change', e => {
            document.getElementById('student-section').style.display = e.target.value === 'single' ? 'block' : 'none';
            document.getElementById('class-section').style.display = e.target.value === 'class' ? 'block' : 'none';
            isTemplateLoaded = false;
            loadFees();
        });
    });

    document.getElementById('template_select').addEventListener('change', function() {
        if (!this.value) return;
        fetch(`get_template.php?id=${this.value}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('items-container').innerHTML = '';
                    data.items.forEach(item => {
                        const base = allItems.find(i => i.id == item.item_id);
                        if (base) { item.item_name = base.name; addItemRow(item, true); }
                    });
                    isTemplateLoaded = true;
                    updateTotals();
                    this.selectedIndex = 0;
                }
            });
    });
});
</script>

<?php include 'footer.php'; ob_end_flush(); ?>