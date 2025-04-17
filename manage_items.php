<?php
require 'config.php';
require 'functions.php';
include 'header.php';

// Handle item deletion
if (isset($_POST['delete_item'])) {
    try {
        deleteItem($pdo, $_POST['item_id']);
        $success = "Item deleted successfully.";
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
    } catch (PDOException $e) {
        $error = "Error updating item: " . $e->getMessage();
    }
}

// Get all items
$items = getItemsWithSubItems($pdo);
?>

<div class="container">
    <h2>Manage Items</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
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
</style>

<script>
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

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editItemModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>

<?php include 'footer.php'; ?> 