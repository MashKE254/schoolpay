<?php
/**
 * inventory.php - Professional Inventory Management with Uniform Orders
 *
 * This comprehensive module handles all aspects of school inventory, from standard
 * stock items to a complete made-to-order workflow for uniforms. It integrates
 * tightly with the school's financial system, automating accounting entries
 * for purchases, sales, and cost of goods sold.
 *
 * Features:
 * - Dashboard with key performance indicators (KPIs) like total stock value.
 * - Management of both standard ('Stock') and 'Made-to-Order' items.
 * - A full audit trail of all stock movements (purchases, issuances, etc.).
 * - A complete job order system for tracking uniform orders from parent request
 * to tailor production and final student delivery.
 * - Automated invoicing and accounting journal entries upon completion of uniform orders.
 * - Low-stock alerts and easy management of item categories.
 * - Bulk CSV upload for Made-to-Order items like uniforms.
 */

require_once 'config.php';
require_once 'functions.php';

// --- POST Request Handling ---
// All form submissions are processed here before any HTML is rendered.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $school_id = $_SESSION['school_id'] ?? null;
    if(!$school_id) { 
        header("Location: login.php"); 
        exit(); 
    }

    $action = $_POST['action'];
    $active_tab = $_POST['active_tab'] ?? 'dashboard';

    try {
        $pdo->beginTransaction();
        
        if ($action === 'add_category') {
            $stmt = $pdo->prepare("INSERT INTO inventory_categories (school_id, name) VALUES (?, ?)");
            $stmt->execute([$school_id, trim($_POST['category_name'])]);
            $_SESSION['success_message'] = "Category added successfully.";
            $active_tab = 'categories';
        } 
        elseif ($action === 'add_item' || $action === 'edit_item') {
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $item_type = $_POST['item_type'] ?? 'Stock';
            
            if ($action === 'add_item') {
                $stmt = $pdo->prepare("INSERT INTO inventory_items (school_id, category_id, sku, name, description, item_type, unit_price, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $category_id, trim($_POST['sku']), trim($_POST['name']), trim($_POST['description']), $item_type, (float)$_POST['unit_price'], (int)$_POST['reorder_level']]);
                $_SESSION['success_message'] = "Inventory item added successfully.";
            } else {
                $stmt = $pdo->prepare("UPDATE inventory_items SET category_id = ?, sku = ?, name = ?, description = ?, item_type = ?, unit_price = ?, reorder_level = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$category_id, trim($_POST['sku']), trim($_POST['name']), trim($_POST['description']), $item_type, (float)$_POST['unit_price'], (int)$_POST['reorder_level'], (int)$_POST['item_id'], $school_id]);
                $_SESSION['success_message'] = "Inventory item updated successfully.";
            }
            $active_tab = 'manage_stock';
        }
        elseif ($action === 'delete_item') {
            $item_id = (int)$_POST['item_id'];
            if ($item_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ? AND school_id = ?");
                $stmt->execute([$item_id, $school_id]);
                $_SESSION['success_message'] = "Inventory item deleted successfully.";
            }
            $active_tab = 'manage_stock';
        }
        elseif ($action === 'record_purchase') {
            $item_id = (int)$_POST['item_id'];
            $quantity = (int)$_POST['quantity'];
            $cost = (float)$_POST['cost'];

            updateInventoryStockLevel($pdo, $school_id, $item_id, $quantity, 'purchase', $cost, [
                'entity_type' => 'supplier',
                'notes' => 'Supplier: ' . trim($_POST['supplier_name']),
                'date' => $_POST['transaction_date'] . ' ' . date('H:i:s')
            ]);
            
            $invAccounts = getInventoryAccountIDs($pdo, $school_id);
            $payment_account_id = (int)$_POST['payment_account_id'];
            create_journal_entry($pdo, $school_id, $_POST['transaction_date'], "Purchase of inventory", $quantity * $cost, $invAccounts['asset'], $payment_account_id);

            $_SESSION['success_message'] = "Stock purchase recorded.";
            $active_tab = 'movements';
        }
        elseif ($action === 'issue_stock') {
            $item_id = (int)$_POST['item_id'];
            $quantity = (int)$_POST['quantity'];
            
            $stmt_item = $pdo->prepare("SELECT name, average_cost, unit_price FROM inventory_items WHERE id = ?");
            $stmt_item->execute([$item_id]);
            $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

            updateInventoryStockLevel($pdo, $school_id, $item_id, -$quantity, 'issuance', null, [
                'entity_type' => 'student',
                'entity_id' => (int)$_POST['student_id'],
                'date' => $_POST['transaction_date'] . ' ' . date('H:i:s')
            ]);
            
            $invAccounts = getInventoryAccountIDs($pdo, $school_id);
            $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
            $issuance_value = $quantity * $item['unit_price'];
            $cogs_value = $quantity * $item['average_cost'];
            
            create_journal_entry($pdo, $school_id, $_POST['transaction_date'], "Issuance of {$item['name']} to student", $issuance_value, $accounts_receivable_id, $invAccounts['sales']);
            create_journal_entry($pdo, $school_id, $_POST['transaction_date'], "COGS for issuance of {$item['name']}", $cogs_value, $invAccounts['cogs'], $invAccounts['asset']);

            $_SESSION['success_message'] = "Stock issued to student.";
            $active_tab = 'movements';
        }
        elseif ($action === 'create_uniform_order') {
            $items = $_POST['items'] ?? [];
            if (empty($items)) throw new Exception("Cannot create an empty order.");
            
            $total_amount = 0;
            foreach ($items as $item) $total_amount += (int)$item['quantity'] * (float)$item['price'];

            $stmt_order = $pdo->prepare("INSERT INTO uniform_orders (school_id, student_id, order_date, due_date, total_amount, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_order->execute([$school_id, (int)$_POST['student_id'], $_POST['order_date'], $_POST['due_date'], $total_amount, trim($_POST['notes'])]);
            $order_id = $pdo->lastInsertId();

            $stmt_item = $pdo->prepare("INSERT INTO uniform_order_items (order_id, item_id, quantity, unit_price, size) VALUES (?, ?, ?, ?, ?)");
            foreach($items as $item) $stmt_item->execute([$order_id, (int)$item['id'], (int)$item['quantity'], (float)$item['price'], trim($item['size'])]);
            
            log_audit($pdo, 'CREATE', 'uniform_orders', $order_id, ['data' => ['student_id' => $_POST['student_id'], 'total' => $total_amount]]);
            $_SESSION['success_message'] = "Uniform order #{$order_id} created successfully.";
            $active_tab = 'uniforms';
        }
        elseif ($action === 'update_order_status') {
            $order_id = (int)$_POST['order_id'];
            $new_status = $_POST['new_status'];
            
            $stmt = $pdo->prepare("UPDATE uniform_orders SET status = ? WHERE id = ? AND school_id = ?");
            $stmt->execute([$new_status, $order_id, $school_id]);
            
            if ($new_status === 'Completed') {
                createInvoiceFromUniformOrder($pdo, $school_id, $order_id);
                $_SESSION['success_message'] = "Order #{$order_id} completed and invoice generated.";
            } else {
                $_SESSION['success_message'] = "Order #{$order_id} status updated to '{$new_status}'.";
            }
            log_audit($pdo, 'UPDATE', 'uniform_orders', $order_id, ['data' => ['new_status' => $new_status]]);
            $active_tab = 'uniforms';
        }
        // --- NEW: BULK UNIFORM UPLOAD ---
        elseif ($action === 'bulk_upload_uniforms') {
            if (isset($_FILES['uniform_csv']) && $_FILES['uniform_csv']['error'] == UPLOAD_ERR_OK) {
                $file = fopen($_FILES['uniform_csv']['tmp_name'], 'r');
                fgetcsv($file); // Skip "GIRLS,,,,BOYS," header
                fgetcsv($file); // Skip "ITEM,PRICE,,,ITEM,PRICE" header

                $stmt_cat = $pdo->prepare("INSERT INTO inventory_categories (school_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $stmt_cat->execute([$school_id, 'Uniforms - Girls']);
                $girl_cat_id = $pdo->lastInsertId();
                $stmt_cat->execute([$school_id, 'Uniforms - Boys']);
                $boy_cat_id = $pdo->lastInsertId();

                $stmt_insert = $pdo->prepare("INSERT INTO inventory_items (school_id, category_id, sku, name, item_type, unit_price) VALUES (?, ?, ?, ?, 'Made-to-Order', ?) ON DUPLICATE KEY UPDATE unit_price = VALUES(unit_price), category_id = VALUES(category_id)");
                
                $processed = ['girls' => 0, 'boys' => 0];
                while (($data = fgetcsv($file)) !== false) {
                    // Process Girls' item (cols 0, 1)
                    $girl_item_name = trim($data[0] ?? '');
                    if (!empty($girl_item_name)) {
                        $price = (float)preg_replace('/[^0-9.]/', '', $data[1] ?? '0');
                        $sku = 'UNI-G-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $girl_item_name), 0, 5));
                        $stmt_insert->execute([$school_id, $girl_cat_id, $sku, $girl_item_name, $price]);
                        $processed['girls']++;
                    }
                    // Process Boys' item (cols 4, 5)
                    $boy_item_name = trim($data[4] ?? '');
                     if (!empty($boy_item_name)) {
                        $price = (float)preg_replace('/[^0-9.]/', '', $data[5] ?? '0');
                        $sku = 'UNI-B-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $boy_item_name), 0, 5));
                        $stmt_insert->execute([$school_id, $boy_cat_id, $sku, $boy_item_name, $price]);
                        $processed['boys']++;
                    }
                }
                $_SESSION['success_message'] = "Processed {$processed['girls']} girls' items and {$processed['boys']} boys' items.";
            } else {
                throw new Exception("File upload error or no file selected.");
            }
            $active_tab = 'manage_stock';
        }

        $pdo->commit();
        header("Location: inventory.php?tab=" . $active_tab);
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: inventory.php?tab=" . $active_tab);
        exit();
    }
}


// --- Data Fetching for Page Display ---
require_once 'header.php';

// Dashboard KPIs
$stmt_kpi = $pdo->prepare("
    SELECT 
        COUNT(id) as total_items,
        COALESCE(SUM(quantity_on_hand * average_cost), 0) as total_value,
        (SELECT COUNT(*) FROM inventory_items WHERE school_id = :school_id1 AND quantity_on_hand <= reorder_level AND reorder_level > 0 AND item_type = 'Stock') as low_stock_count
    FROM inventory_items WHERE school_id = :school_id2
");
$stmt_kpi->execute([':school_id1' => $school_id, ':school_id2' => $school_id]);
$kpis = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

$low_stock_items_stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE school_id = ? AND quantity_on_hand <= reorder_level AND reorder_level > 0 AND item_type = 'Stock' ORDER BY name LIMIT 5");
$low_stock_items_stmt->execute([$school_id]);
$low_stock_items = $low_stock_items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Data for Manage Stock Tab
$items_stmt = $pdo->prepare("SELECT i.*, c.name as category_name FROM inventory_items i LEFT JOIN inventory_categories c ON i.category_id = c.id WHERE i.school_id = ? ORDER BY i.name");
$items_stmt->execute([$school_id]);
$inventory_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Data for Uniforms Tab
$uniform_orders_stmt = $pdo->prepare("
    SELECT uo.*, s.name as student_name 
    FROM uniform_orders uo
    JOIN students s ON uo.student_id = s.id
    WHERE uo.school_id = ? ORDER BY uo.order_date DESC, uo.id DESC
");
$uniform_orders_stmt->execute([$school_id]);
$uniform_orders = $uniform_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

$made_to_order_items_stmt = $pdo->prepare("SELECT id, name, unit_price FROM inventory_items WHERE school_id = ? AND item_type = 'Made-to-Order' ORDER BY name");
$made_to_order_items_stmt->execute([$school_id]);
$made_to_order_items = $made_to_order_items_stmt->fetchAll(PDO::FETCH_ASSOC);


// Data for other tabs
$categories_stmt = $pdo->prepare("SELECT * FROM inventory_categories WHERE school_id = ? ORDER BY name");
$categories_stmt->execute([$school_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$students = getStudents($pdo, $school_id, null, null, 'active');
$asset_accounts = getAccountsByType($pdo, $school_id, 'asset');

?>
<style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .kpi-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); text-align: center; }
    .kpi-card .value { font-size: 2rem; font-weight: bold; }
    .kpi-card .label { color: #6c757d; }
    .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; color: white; display: inline-block; }
    .status-Processing { background-color: var(--secondary); }
    .status-With\.Tailor { background-color: #6c757d; }
    .status-Ready\.for\.Pickup { background-color: var(--warning); }
    .status-Completed { background-color: var(--success); }
</style>

<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
        <p>Track school supplies, uniforms, and other stock items.</p>
    </div>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
    <?php unset($_SESSION['error_message']); endif; ?>
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
    <?php unset($_SESSION['success_message']); endif; ?>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link" onclick="openTab(event, 'dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
        <button class="tab-link" onclick="openTab(event, 'manage_stock')"><i class="fas fa-box-open"></i> Manage Stock</button>
        <button class="tab-link" onclick="openTab(event, 'uniforms')"><i class="fas fa-tshirt"></i> Uniform Orders</button>
        <button class="tab-link" onclick="openTab(event, 'movements')"><i class="fas fa-history"></i> Stock Movements</button>
        <button class="tab-link" onclick="openTab(event, 'categories')"><i class="fas fa-tags"></i> Categories</button>
    </div>

    <div id="dashboard" class="tab-content">
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="value"><?= $kpis['total_items'] ?></div>
                <div class="label">Total Unique Items</div>
            </div>
            <div class="kpi-card">
                <div class="value"><?= format_currency($kpis['total_value']) ?></div>
                <div class="label">Total Stock Value</div>
            </div>
            <div class="kpi-card">
                <div class="value" style="color: <?= $kpis['low_stock_count'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $kpis['low_stock_count'] ?></div>
                <div class="label">Items Below Reorder Level</div>
            </div>
        </div>
        <div class="card">
            <h3>Quick Actions</h3>
            <div class="form-actions">
                <button class="btn-add" onclick="openModal('addItemModal')"><i class="fas fa-plus"></i> Add New Item</button>
                <button class="btn-success" onclick="openModal('recordPurchaseModal')"><i class="fas fa-truck-loading"></i> Record Purchase</button>
                <button class="btn-primary" onclick="openModal('issueStockModal')"><i class="fas fa-user-check"></i> Issue to Student</button>
                <button class="btn-info" onclick="openModal('newOrderModal')"><i class="fas fa-tshirt"></i> New Uniform Order</button>
            </div>
        </div>
        <div class="card" style="margin-top: 20px;">
            <h3>Low Stock Items</h3>
            <?php if(empty($low_stock_items)): ?>
                <p>All items are sufficiently stocked.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>SKU</th><th>Item Name</th><th>Qty on Hand</th><th>Reorder Level</th></tr></thead>
                    <tbody>
                        <?php foreach($low_stock_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['sku']) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td style="color: var(--danger); font-weight: bold;"><?= $item['quantity_on_hand'] ?></td>
                            <td><?= $item['reorder_level'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="manage_stock" class="tab-content">
        <div class="card">
            <h3>All Inventory Items</h3>
            <div class="table-actions">
                <button class="btn-add" onclick="openModal('addItemModal')"><i class="fas fa-plus"></i> Add New Item</button>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>SKU</th><th>Name</th><th>Type</th><th>Category</th><th>Qty on Hand</th><th>Avg. Cost</th><th>Unit Price</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($inventory_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['sku']) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($item['item_type']) ?></span></td>
                            <td><?= htmlspecialchars($item['category_name'] ?? 'N/A') ?></td>
                            <td><?= $item['item_type'] === 'Stock' ? $item['quantity_on_hand'] : 'N/A' ?></td>
                            <td><?= $item['item_type'] === 'Stock' ? format_currency($item['average_cost']) : 'N/A' ?></td>
                            <td><?= format_currency($item['unit_price']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon" onclick='openEditItemModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="active_tab" value="manage_stock">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card" style="margin-top:20px;">
            <h3>Bulk Upload Uniforms</h3>
            <p>Upload your uniform price list CSV. The system expects columns for Girls' items and Boys' items side-by-side. Existing items with the same name will be updated with the new price.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="bulk_upload_uniforms">
                <input type="hidden" name="active_tab" value="manage_stock">
                <div class="form-group">
                    <label for="uniform_csv">Uniform CSV File</label>
                    <input type="file" name="uniform_csv" id="uniform_csv" accept=".csv" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-upload"></i> Upload Uniforms</button>
                </div>
            </form>
        </div>
    </div>

    <div id="uniforms" class="tab-content">
        <div class="card">
            <h3>Uniform Orders</h3>
            <div class="table-actions">
                <button class="btn-add" onclick="openModal('newOrderModal')"><i class="fas fa-plus"></i> New Uniform Order</button>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Order #</th><th>Student</th><th>Order Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($uniform_orders)): ?>
                            <tr><td colspan="6" class="text-center">No uniform orders found.</td></tr>
                        <?php else: ?>
                            <?php foreach($uniform_orders as $order): ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['student_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                <td><?= format_currency($order['total_amount']) ?></td>
                                <td><span class="status-badge status-<?= str_replace(' ','.', $order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                                <td>
                                    <?php if ($order['status'] === 'Processing'): ?>
                                        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="active_tab" value="uniforms"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><input type="hidden" name="new_status" value="With Tailor"><button type="submit" class="btn-secondary btn-sm">Send to Tailor</button></form>
                                    <?php elseif ($order['status'] === 'With Tailor'): ?>
                                         <form method="POST" style="display:inline;"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="active_tab" value="uniforms"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><input type="hidden" name="new_status" value="Ready for Pickup"><button type="submit" class="btn-secondary btn-sm">Receive from Tailor</button></form>
                                    <?php elseif ($order['status'] === 'Ready for Pickup'): ?>
                                         <form method="POST" style="display:inline;" onsubmit="return confirm('This will issue the items and generate an invoice. Proceed?');"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="active_tab" value="uniforms"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><input type="hidden" name="new_status" value="Completed"><button type="submit" class="btn-success btn-sm">Issue to Student</button></form>
                                    <?php elseif ($order['status'] === 'Completed' && $order['invoice_id']): ?>
                                        <a href="view_invoice.php?id=<?= $order['invoice_id'] ?>" class="btn-secondary btn-sm">View Invoice</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="movements" class="tab-content">
        <div class="card">
            <h3>Stock Movement Log</h3>
             <div class="table-container">
                <table id="movements-table">
                    <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Qty Change</th><th>User</th><th>Notes</th></tr></thead>
                    <tbody id="movements-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="categories" class="tab-content">
         <div class="card">
            <h3>Inventory Categories</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <input type="hidden" name="active_tab" value="categories">
                <div class="form-group"><label>New Category Name</label><input type="text" name="category_name" required></div>
                <button type="submit" class="btn-primary">Add Category</button>
            </form>
            <hr>
            <table>
                <thead><tr><th>Name</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><!-- Edit/Delete buttons can be added here --></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALS -->
<div id="addItemModal" class="modal"><div class="modal-content"><form method="POST"><div class="modal-header"><h3>Add New Inventory Item</h3><span class="close" onclick="closeModal('addItemModal')">&times;</span></div><div class="modal-body"><input type="hidden" name="action" value="add_item"><input type="hidden" name="active_tab" value="manage_stock"><div class="form-group"><label>Item Type</label><select name="item_type"><option value="Stock">Standard Stock Item</option><option value="Made-to-Order">Made-to-Order (e.g., Uniform)</option></select></div><div class="form-group"><label>SKU</label><input type="text" name="sku" required></div><div class="form-group"><label>Item Name</label><input type="text" name="name" required></div><div class="form-group"><label>Category</label><select name="category_id"><option value="">-- No Category --</option><?php foreach($categories as $cat) echo "<option value='{$cat['id']}'>".htmlspecialchars($cat['name'])."</option>"; ?></select></div><div class="form-group"><label>Unit Price (Selling)</label><input type="number" name="unit_price" step="0.01" required></div><div class="form-group"><label>Reorder Level (for Stock items)</label><input type="number" name="reorder_level" value="0" required></div><div class="form-group"><label>Description</label><textarea name="description"></textarea></div></div><div class="modal-footer"><button type="submit" class="btn-success">Save Item</button></div></form></div></div>

<div id="editItemModal" class="modal"><div class="modal-content"><form method="POST"><div class="modal-header"><h3>Edit Inventory Item</h3><span class="close" onclick="closeModal('editItemModal')">&times;</span></div><div class="modal-body"><input type="hidden" name="action" value="edit_item"><input type="hidden" name="active_tab" value="manage_stock"><input type="hidden" name="item_id" id="edit_item_id"><div class="form-group"><label>Item Type</label><select name="item_type" id="edit_item_type"><option value="Stock">Standard Stock Item</option><option value="Made-to-Order">Made-to-Order (e.g., Uniform)</option></select></div><div class="form-group"><label>SKU</label><input type="text" name="sku" id="edit_sku" required></div><div class="form-group"><label>Item Name</label><input type="text" name="name" id="edit_name" required></div><div class="form-group"><label>Category</label><select name="category_id" id="edit_category_id"><option value="">-- No Category --</option><?php foreach($categories as $cat) echo "<option value='{$cat['id']}'>".htmlspecialchars($cat['name'])."</option>"; ?></select></div><div class="form-group"><label>Unit Price (Selling)</label><input type="number" name="unit_price" id="edit_unit_price" step="0.01" required></div><div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" id="edit_reorder_level" required></div><div class="form-group"><label>Description</label><textarea name="description" id="edit_description"></textarea></div></div><div class="modal-footer"><button type="submit" class="btn-primary">Update Item</button></div></form></div></div>

<div id="recordPurchaseModal" class="modal"><div class="modal-content"><form method="POST"><div class="modal-header"><h3>Record Stock Purchase</h3><span class="close" onclick="closeModal('recordPurchaseModal')">&times;</span></div><div class="modal-body"><input type="hidden" name="action" value="record_purchase"><input type="hidden" name="active_tab" value="movements"><div class="form-group"><label>Item</label><select name="item_id" required><?php foreach($inventory_items as $item) if($item['item_type']=='Stock') echo "<option value='{$item['id']}'>".htmlspecialchars($item['name'])." (SKU: {$item['sku']})</option>"; ?></select></div><div class="form-group"><label>Quantity Received</label><input type="number" name="quantity" min="1" required></div><div class="form-group"><label>Cost Per Item</label><input type="number" name="cost" step="0.01" required></div><div class="form-group"><label>Supplier Name</label><input type="text" name="supplier_name"></div><div class="form-group"><label>Payment Account</label><select name="payment_account_id" required><?php foreach($asset_accounts as $acc) echo "<option value='{$acc['id']}'>".htmlspecialchars($acc['account_name'])."</option>"; ?></select></div><div class="form-group"><label>Transaction Date</label><input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required></div></div><div class="modal-footer"><button type="submit" class="btn-success">Record Purchase</button></div></form></div></div>

<div id="issueStockModal" class="modal"><div class="modal-content"><form method="POST"><div class="modal-header"><h3>Issue Stock to Student</h3><span class="close" onclick="closeModal('issueStockModal')">&times;</span></div><div class="modal-body"><input type="hidden" name="action" value="issue_stock"><input type="hidden" name="active_tab" value="movements"><div class="form-group"><label>Item</label><select name="item_id" required><?php foreach($inventory_items as $item) if($item['item_type']=='Stock') echo "<option value='{$item['id']}'>".htmlspecialchars($item['name'])." (Available: {$item['quantity_on_hand']})</option>"; ?></select></div><div class="form-group"><label>Student</label><select name="student_id" required><?php foreach($students as $student) echo "<option value='{$student['id']}'>".htmlspecialchars($student['name'])."</option>"; ?></select></div><div class="form-group"><label>Quantity Issued</label><input type="number" name="quantity" min="1" required></div><div class="form-group"><label>Transaction Date</label><input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required></div><p><small>Note: This will create a journal entry debiting Accounts Receivable and crediting Inventory Sales.</small></p></div><div class="modal-footer"><button type="submit" class="btn-primary">Issue Stock</button></div></form></div></div>

<div id="newOrderModal" class="modal"><div class="modal-content" style="max-width: 800px;"><form method="POST"><div class="modal-header"><h3>New Uniform Order</h3><span class="close" onclick="closeModal('newOrderModal')">&times;</span></div><div class="modal-body"><input type="hidden" name="action" value="create_uniform_order"><input type="hidden" name="active_tab" value="uniforms">
    <div class="form-grid">
        <div class="form-group"><label>Student</label><select name="student_id" required><?php foreach($students as $s) echo "<option value='{$s['id']}'>".htmlspecialchars($s['name'])."</option>"; ?></select></div>
        <div class="form-group"><label>Order Date</label><input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="form-group"><label>Due Date</label><input type="date" name="due_date"></div>
    </div>
    <h4>Order Items</h4>
    <table class="items-table"><thead><tr><th>Item</th><th>Size</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead><tbody id="order-items-container"></tbody></table>
    <button type="button" class="btn-add" onclick="addOrderItem()">+ Add Item</button>
    <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
</div><div class="modal-footer"><button type="submit" class="btn-success">Create Order</button></div></form></div></div>

<template id="order-item-template">
    <tr>
        <td><select class="order-item-select" onchange="updateOrderItemPrice(this)"><?php foreach($made_to_order_items as $item) echo "<option value='{$item['id']}' data-price='{$item['unit_price']}'>".htmlspecialchars($item['name'])."</option>"; ?></select></td>
        <td><input type="text" class="order-item-size" placeholder="e.g., M, 12"></td>
        <td><input type="number" class="order-item-qty" value="1" min="1" oninput="updateOrderRowTotal(this)"></td>
        <td><input type="number" class="order-item-price" step="0.01" oninput="updateOrderRowTotal(this)"></td>
        <td class="order-item-total"></td>
        <td><button type="button" class="remove-item" onclick="this.closest('tr').remove()">×</button></td>
    </tr>
</template>

<script>
    function formatCurrencyJS(amount) { return `<?= $_SESSION['currency_symbol'] ?? '$' ?>${parseFloat(amount).toFixed(2)}`; }

    function openTab(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove('active'));
        document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
        if (tabName === 'movements') fetchMovements();
    }
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab') || 'dashboard';
        const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
        if (tabButton) tabButton.click(); else document.querySelector('.tab-link').click();
    });

    function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    
    function openEditItemModal(item) {
        document.getElementById('edit_item_id').value = item.id;
        document.getElementById('edit_sku').value = item.sku;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_item_type').value = item.item_type;
        document.getElementById('edit_category_id').value = item.category_id || '';
        document.getElementById('edit_unit_price').value = item.unit_price;
        document.getElementById('edit_reorder_level').value = item.reorder_level;
        document.getElementById('edit_description').value = item.description || '';
        openModal('editItemModal');
    }

    async function fetchMovements() {
        const tableBody = document.getElementById('movements-table-body');
        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading...</td></tr>';
        try {
            const response = await fetch('get_inventory_movements.php');
            const data = await response.json();
            if (data.success) {
                tableBody.innerHTML = '';
                if(data.movements.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No movements recorded yet.</td></tr>';
                }
                data.movements.forEach(m => {
                    const row = `<tr>
                        <td>${new Date(m.transaction_date).toLocaleString()}</td>
                        <td>${m.item_name}</td>
                        <td><span class="badge badge-secondary">${m.movement_type.replace('_',' ')}</span></td>
                        <td style="font-weight:bold; color:${m.quantity_changed > 0 ? 'var(--success)' : 'var(--danger)'}">${m.quantity_changed}</td>
                        <td>${m.user_name}</td>
                        <td>${m.notes || ''}</td>
                    </tr>`;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Error: ${data.error}</td></tr>`;
            }
        } catch (e) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Failed to load data.</td></tr>`;
        }
    }
    
    function addOrderItem() {
        const container = document.getElementById('order-items-container');
        const template = document.getElementById('order-item-template');
        const clone = template.content.cloneNode(true);
        
        const select = clone.querySelector('.order-item-select');
        const sizeInput = clone.querySelector('.order-item-size');
        const qtyInput = clone.querySelector('.order-item-qty');
        const priceInput = clone.querySelector('.order-item-price');
        
        const rowId = Date.now();
        select.name = `items[${rowId}][id]`;
        sizeInput.name = `items[${rowId}][size]`;
        qtyInput.name = `items[${rowId}][quantity]`;
        priceInput.name = `items[${rowId}][price]`;
        
        container.appendChild(clone);
        const newRow = container.lastElementChild;
        // Auto-select the first item if available
        if(newRow.querySelector('.order-item-select').options.length > 0) {
            newRow.querySelector('.order-item-select').selectedIndex = 0;
            updateOrderItemPrice(newRow.querySelector('.order-item-select'));
        }
    }

    function updateOrderItemPrice(selectElement) {
        const row = selectElement.closest('tr');
        const price = selectElement.options[selectElement.selectedIndex].dataset.price;
        row.querySelector('.order-item-price').value = parseFloat(price || 0).toFixed(2);
        updateOrderRowTotal(selectElement);
    }

    function updateOrderRowTotal(inputElement) {
        const row = inputElement.closest('tr');
        const qty = parseFloat(row.querySelector('.order-item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.order-item-price').value) || 0;
        row.querySelector('.order-item-total').textContent = formatCurrencyJS(qty * price);
    }
</script>

