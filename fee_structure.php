<?php
// fee_structure.php - Professional Fee Management Interface
require_once 'config.php';
require_once 'functions.php';
include 'header.php';

// --- 1. Handle Form Submission (Save/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_structure'])) {
    try {
        $pdo->beginTransaction();
        
        $class_id = $_POST['class_id'];
        $academic_year = $_POST['academic_year'];
        $term = $_POST['term'];
        
        // Validation
        if (empty($class_id) || empty($academic_year) || empty($term)) {
            throw new Exception("Please select Class, Year, and Term.");
        }

        // 1. Clear existing structure for this specific combination to avoid duplicates
        $stmt_del = $pdo->prepare("DELETE FROM fee_structure_items WHERE school_id = ? AND class_id = ? AND academic_year = ? AND term = ?");
        $stmt_del->execute([$school_id, $class_id, $academic_year, $term]);

        // 2. Insert new selections
        if (isset($_POST['items'])) {
            $stmt_insert = $pdo->prepare("INSERT INTO fee_structure_items (school_id, class_id, academic_year, term, item_id, amount, is_mandatory) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['items'] as $item_id => $data) {
                // Only process if the "enabled" checkbox was checked
                if (isset($data['enabled']) && $data['enabled'] == '1') {
                    $amount = floatval($data['amount']);
                    $is_mandatory = isset($data['is_mandatory']) ? 1 : 0;
                    
                    $stmt_insert->execute([
                        $school_id, 
                        $class_id, 
                        $academic_year, 
                        $term, 
                        $item_id, 
                        $amount, 
                        $is_mandatory
                    ]);
                }
            }
        }

        $pdo->commit();
        $success_msg = "Fee structure saved successfully for " . htmlspecialchars("$academic_year - $term");
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

// --- 2. Handle Cloning (Professional Feature) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clone_structure'])) {
    try {
        $source_term = $_POST['source_term'];
        $target_term = $_POST['target_term'];
        $year = $_POST['academic_year']; // Assuming cloning within same year for simplicity
        
        if($source_term == $target_term) throw new Exception("Source and Target terms cannot be the same.");

        // Fetch source data
        $stmt_src = $pdo->prepare("SELECT * FROM fee_structure_items WHERE school_id = ? AND academic_year = ? AND term = ?");
        $stmt_src->execute([$school_id, $year, $source_term]);
        $source_items = $stmt_src->fetchAll(PDO::FETCH_ASSOC);

        if(empty($source_items)) throw new Exception("No fee structure found for Source Term.");

        $pdo->beginTransaction();
        
        // Insert for target term (careful not to duplicate if exists, maybe clear first?)
        // For safety, let's only insert if not exists or use ON DUPLICATE UPDATE logic if complex.
        // Simple approach: Clear target first for those classes found in source.
        
        $insert_stmt = $pdo->prepare("INSERT INTO fee_structure_items (school_id, class_id, item_id, academic_year, term, amount, is_mandatory) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $count = 0;
        foreach ($source_items as $row) {
            // Check if exists to avoid error
            $check = $pdo->prepare("SELECT id FROM fee_structure_items WHERE school_id=? AND class_id=? AND item_id=? AND academic_year=? AND term=?");
            $check->execute([$school_id, $row['class_id'], $row['item_id'], $year, $target_term]);
            if(!$check->fetch()) {
                $insert_stmt->execute([$school_id, $row['class_id'], $row['item_id'], $year, $target_term, $row['amount'], $row['is_mandatory']]);
                $count++;
            }
        }
        
        $pdo->commit();
        $success_msg = "Successfully cloned $count items from $source_term to $target_term.";

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Clone failed: " . $e->getMessage();
    }
}

// --- 3. Data Retrieval ---
$classes = getClasses($pdo, $school_id);
$base_items = getItems($pdo, $school_id); // Use your existing getItems function

// Defaults
$selected_class = $_GET['class_id'] ?? '';
$selected_year = $_GET['academic_year'] ?? date('Y') . '-' . (date('Y') + 1);
$selected_term = $_GET['term'] ?? 'Term 1';

// Fetch current structure if filters are set
$current_structure = [];
if ($selected_class && $selected_year && $selected_term) {
    $stmt = $pdo->prepare("SELECT item_id, amount, is_mandatory FROM fee_structure_items WHERE school_id = ? AND class_id = ? AND academic_year = ? AND term = ?");
    $stmt->execute([$school_id, $selected_class, $selected_year, $selected_term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $r) {
        $current_structure[$r['item_id']] = $r;
    }
}
?>

<div class="container main-content">
    <div class="page-header-row">
        <div>
            <h2><i class="fas fa-layer-group"></i> Fee Structure Manager</h2>
            <p class="text-muted">Define mandatory and optional fees for each class and term.</p>
        </div>
        <button type="button" class="btn-secondary" onclick="document.getElementById('cloneModal').style.display='block'">
            <i class="fas fa-copy"></i> Clone Structure
        </button>
    </div>

    <?php if (isset($success_msg)): ?><div class="alert alert-success"><?= $success_msg ?></div><?php endif; ?>
    <?php if (isset($error_msg)): ?><div class="alert alert-danger"><?= $error_msg ?></div><?php endif; ?>

    <div class="card p-3 mb-4">
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($selected_year) ?>" placeholder="YYYY-YYYY">
                </div>
                <div class="form-group col-md-3">
                    <label>Term</label>
                    <select name="term" class="form-control">
                        <?php foreach(['Term 1', 'Term 2', 'Term 3'] as $t): ?>
                            <option value="<?= $t ?>" <?= $selected_term == $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Class / Grade</label>
                    <select name="class_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Select Class to Edit --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>" <?= $selected_class == $cls['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cls['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn-primary w-100">Load</button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($selected_class): ?>
    <form method="POST" action="fee_structure.php">
        <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class) ?>">
        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($selected_year) ?>">
        <input type="hidden" name="term" value="<?= htmlspecialchars($selected_term) ?>">
        
        <div class="card table-card">
            <div class="card-header bg-light">
                <h3 class="card-title">Configure Fees for <?= htmlspecialchars($selected_year . ' ' . $selected_term) ?></h3>
            </div>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="5%"><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th width="35%">Item Name</th>
                        <th width="15%">Base Price</th>
                        <th width="20%">Fee Amount</th>
                        <th width="15%">Type</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($base_items as $item): 
                        $is_active = isset($current_structure[$item['id']]);
                        $val = $current_structure[$item['id']] ?? [];
                        $amount = $is_active ? $val['amount'] : $item['price'];
                        $is_mandatory = $is_active ? $val['is_mandatory'] : 1; // Default to mandatory
                    ?>
                    <tr class="<?= $is_active ? 'table-active-row' : '' ?>">
                        <td>
                            <input type="checkbox" name="items[<?= $item['id'] ?>][enabled]" value="1" 
                                   class="row-checkbox" <?= $is_active ? 'checked' : '' ?> onchange="toggleRow(this)">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($item['description'] ?? '') ?></small>
                        </td>
                        <td class="text-muted">$<?= number_format($item['price'], 2) ?></td>
                        <td>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="items[<?= $item['id'] ?>][amount]" 
                                       class="form-control amount-input" value="<?= $amount ?>" <?= !$is_active ? 'disabled' : '' ?>>
                            </div>
                        </td>
                        <td>
                            <select name="items[<?= $item['id'] ?>][is_mandatory]" class="form-control status-select" <?= !$is_active ? 'disabled' : '' ?>>
                                <option value="1" <?= $is_mandatory ? 'selected' : '' ?>>Mandatory</option>
                                <option value="0" <?= !$is_mandatory ? 'selected' : '' ?>>Optional</option>
                            </select>
                        </td>
                        <td>
                            <span class="badge <?= $is_active ? 'badge-success' : 'badge-secondary' ?> status-badge">
                                <?= $is_active ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer text-right">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total: <b id="totalCalc">$0.00</b></span>
                    <button type="submit" name="save_structure" class="btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-arrow-up fa-3x mb-3 text-muted"></i>
            <h3>Select a Class to Begin</h3>
            <p>Choose a class, year, and term above to configure the fee structure.</p>
        </div>
    <?php endif; ?>
</div>

<div id="cloneModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Clone Fee Structure</h3>
            <span class="close" onclick="document.getElementById('cloneModal').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <div class="modal-body">
                <p>Copy fee structures from one term to another for <strong>ALL</strong> classes in the selected year.</p>
                <input type="hidden" name="clone_structure" value="1">
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="<?= $selected_year ?>">
                </div>
                <div class="form-group">
                    <label>Copy FROM (Source)</label>
                    <select name="source_term" class="form-control">
                        <option>Term 1</option><option>Term 2</option><option>Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Copy TO (Target)</label>
                    <select name="target_term" class="form-control">
                        <option>Term 2</option><option>Term 1</option><option>Term 3</option>
                    </select>
                </div>
                <div class="alert alert-warning text-small">
                    <i class="fas fa-exclamation-triangle"></i> This will add missing items to the target term. It will not delete existing configurations.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="document.getElementById('cloneModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-primary">Clone Fees</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Additions to your existing styles */
    .page-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .form-row { display: flex; flex-wrap: wrap; margin-right: -10px; margin-left: -10px; }
    .form-row > [class*='col-'] { padding-right: 10px; padding-left: 10px; }
    .col-md-3 { flex: 0 0 25%; max-width: 25%; }
    .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; }
    .col-md-2 { flex: 0 0 16.666%; max-width: 16.666%; }
    
    .table-active-row { background-color: #f8fbff; }
    .input-group { display: flex; align-items: center; }
    .input-group-text { background: #e9ecef; border: 1px solid #ced4da; padding: 0.375rem 0.75rem; border-radius: 0.25rem 0 0 0.25rem; color: #495057; }
    .input-group input { border-radius: 0 0.25rem 0.25rem 0; border-left: 0; }
    
    .badge-success { background-color: #28a745; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
    .badge-secondary { background-color: #6c757d; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
    
    .empty-state { text-align: center; padding: 50px; background: #fff; border-radius: 10px; border: 2px dashed #dfe6e9; color: #7f8c8d; }
</style>

<script>
function toggleRow(checkbox) {
    const row = checkbox.closest('tr');
    const inputs = row.querySelectorAll('input.amount-input, select.status-select');
    const badge = row.querySelector('.status-badge');
    
    if (checkbox.checked) {
        row.classList.add('table-active-row');
        inputs.forEach(input => input.disabled = false);
        badge.className = 'badge badge-success status-badge';
        badge.textContent = 'Active';
    } else {
        row.classList.remove('table-active-row');
        inputs.forEach(input => input.disabled = true);
        badge.className = 'badge badge-secondary status-badge';
        badge.textContent = 'Inactive';
    }
    calculateTotal();
}

function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => {
        if (cb !== source) {
            cb.checked = source.checked;
            toggleRow(cb);
        }
    });
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
        const row = cb.closest('tr');
        const amount = parseFloat(row.querySelector('.amount-input').value) || 0;
        total += amount;
    });
    document.getElementById('totalCalc').textContent = '$' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Initial Calc
document.addEventListener('DOMContentLoaded', calculateTotal);
document.querySelectorAll('.amount-input').forEach(input => {
    input.addEventListener('input', calculateTotal);
});
</script>

<?php include 'footer.php'; ?>