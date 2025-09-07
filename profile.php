<?php
// profile.php - User and School Profile Management
// All processing logic now comes BEFORE any HTML output.

require 'config.php';
require 'functions.php';
session_start(); // Start session here to access $_SESSION

// Ensure user is logged in
if (!isset($_SESSION['school_id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';
$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];

// --- BLOCK 1: FORM PROCESSING ---

// Handle Profile Update (Now with file upload logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $school_name = trim($_POST['school_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $logo_url = trim($_POST['existing_logo_url']); // Start with the existing URL

    if (empty($school_name)) {
        $errors[] = 'School name cannot be empty.';
    }

    // --- NEW: Handle Logo File Upload ---
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_info = $_FILES['school_logo'];
        $file_name = $file_info['name'];
        $file_tmp = $file_info['tmp_name'];
        $file_size = $file_info['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            if ($file_size < 5000000) { // Max 5MB
                // Create a unique filename to prevent overwrites
                $new_file_name = 'logo_' . $school_id . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    // If a new logo is uploaded successfully, delete the old one
                    if (!empty($logo_url) && file_exists($logo_url)) {
                        unlink($logo_url);
                    }
                    // Update the logo_url to the new path
                    $logo_url = $target_path;
                } else {
                    $errors[] = 'Failed to move the uploaded logo file.';
                }
            } else {
                $errors[] = 'The logo image file is too large (max 5MB).';
            }
        } else {
            $errors[] = 'Invalid file type for logo. Please upload a JPG, PNG, or GIF.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt_old_school = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
            $stmt_old_school->execute([$school_id]);
            $old_school_data = $stmt_old_school->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("UPDATE schools SET name = ? WHERE id = ?");
            $stmt->execute([$school_name, $school_id]);
            log_audit($pdo, 'UPDATE', 'schools', $school_id, ['before' => $old_school_data, 'after' => ['name' => $school_name]]);
            
            $stmt_old_details = $pdo->prepare("SELECT * FROM school_details WHERE school_id = ?");
            $stmt_old_details->execute([$school_id]);
            $old_details_data = $stmt_old_details->fetch(PDO::FETCH_ASSOC);

            if ($old_details_data) {
                $stmt = $pdo->prepare("UPDATE school_details SET address = ?, phone = ?, email = ?, logo_url = ? WHERE school_id = ?");
                $stmt->execute([$address, $phone, $email, $logo_url, $school_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO school_details (school_id, address, phone, email, logo_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $address, $phone, $email, $logo_url]);
            }
            log_audit($pdo, 'UPDATE', 'school_details', $school_id, ['before' => $old_details_data, 'after' => ['address' => $address, 'phone' => $phone, 'email' => $email, 'logo_url' => $logo_url]]);

            $pdo->commit();
            $success_message = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}


// Handle Bulk Student Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_add_students'])) {
    $upload_errors = [];
    $processed_count = 0;
    $skipped_count = 0;

    if (isset($_FILES['student_csv']['error']) && $_FILES['student_csv']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_csv']['tmp_name'];

        $pdo->beginTransaction();
        try {
            $handle = fopen($file, "r");
            $bom = "\xef\xbb\xbf"; // UTF-8 BOM
            if (fgets($handle, 4) !== $bom) {
                rewind($handle);
            }

            $header = fgetcsv($handle);
            if (!$header) throw new Exception("Cannot read the CSV header row.");

            $stmt_find_class = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND school_id = ?");
            $stmt_create_class = $pdo->prepare("INSERT INTO classes (school_id, name) VALUES (?, ?)");
            $stmt_find_student = $pdo->prepare("SELECT id FROM students WHERE student_id_no = ? AND school_id = ?");
            $stmt_insert_student = $pdo->prepare("INSERT INTO students (school_id, student_id_no, name, email, class_id, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");

            $line_number = 1;
            while (($data = fgetcsv($handle)) !== FALSE) {
                $line_number++;
                $student_id_no = trim($data[0] ?? '');
                $name = trim($data[1] ?? '');

                if (empty($student_id_no) || empty($name)) {
                    $upload_errors[] = "Skipped row {$line_number}: Student ID Number and Name are required.";
                    continue;
                }

                $stmt_find_student->execute([$student_id_no, $school_id]);
                if ($stmt_find_student->fetch()) {
                    $skipped_count++;
                    continue;
                }

                $email = trim($data[2] ?? '');
                $class_name = trim($data[3] ?? '');
                $phone = trim($data[4] ?? '');
                $address = trim($data[5] ?? '');
                $class_id = null;

                if (!empty($class_name)) {
                    $stmt_find_class->execute([$class_name, $school_id]);
                    $class = $stmt_find_class->fetch();
                    if ($class) {
                        $class_id = $class['id'];
                    } else {
                        $stmt_create_class->execute([$school_id, $class_name]);
                        $class_id = $pdo->lastInsertId();
                        log_audit($pdo, 'CREATE', 'classes', $class_id, ['data' => ['name' => $class_name, 'note' => 'Auto-created during student import.']]);
                    }
                }

                $stmt_insert_student->execute([$school_id, $student_id_no, $name, $email, $class_id, $phone, $address]);
                $processed_count++;
            }

            fclose($handle);
            log_audit($pdo, 'SYSTEM', 'students', null, ['data' => ["note" => "Bulk student upload processed {$processed_count} new students and skipped {$skipped_count} duplicates."]]);
            $pdo->commit();
            
            $success_msg = "Successfully imported {$processed_count} new students.";
            if($skipped_count > 0) $success_msg .= " Skipped {$skipped_count} students because their ID already exists.";
            $_SESSION['success_message'] = $success_msg;
            if(!empty($upload_errors)) $_SESSION['upload_errors'] = $upload_errors;

            header("Location: profile.php?student_upload_success=1");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $upload_errors[] = "A critical error occurred: " . $e->getMessage();
        }
    } else {
         if (isset($_POST['bulk_add_students'])) {
            $upload_errors[] = 'File upload failed. Error code: ' . ($_FILES['student_csv']['error'] ?? 'UNKNOWN');
         }
    }

    if (!empty($upload_errors)) {
        $_SESSION['upload_errors'] = $upload_errors;
        header("Location: profile.php?upload_error=1");
        exit();
    }
}

// Other POST handlers remain unchanged...
// Handle Delete Invoice Template, Update Invoice Template, Fee Structure Upload, Add/Delete Class...

// --- BLOCK 2: PAGE DISPLAY SETUP ---
include 'header.php';

// Fetch current school details for the form
$stmt = $pdo->prepare("SELECT s.name, sd.address, sd.phone, sd.email, sd.logo_url FROM schools s LEFT JOIN school_details sd ON s.id = sd.school_id WHERE s.id = ?");
$stmt->execute([$school_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) { die("Error: Could not retrieve school profile."); }

// Other data fetching remains...
$class_stmt = $pdo->prepare("SELECT id, name FROM classes WHERE school_id = ? ORDER BY name");
$class_stmt->execute([$school_id]);
$classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

$templates_stmt = $pdo->prepare("SELECT * FROM invoice_templates WHERE school_id = ? ORDER BY name");
$templates_stmt->execute([$school_id]);
$templates = $templates_stmt->fetchAll(PDO::FETCH_ASSOC);

$items_list = getItemsWithSubItems($pdo, $school_id);


// Check for success/error messages from redirects
if(isset($_GET['class_added'])) $success_message = 'New class added successfully!';
if(isset($_GET['class_deleted'])) $success_message = 'Class deleted successfully!';
if(isset($_GET['upload_success'])) $success_message = 'Fee structure uploaded successfully!';
if(isset($_GET['template_deleted'])) $success_message = 'Invoice template deleted successfully!';
if(isset($_GET['template_updated'])) $success_message = 'Invoice template updated successfully!';
if(isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if(isset($_GET['upload_error']) && isset($_SESSION['upload_errors'])) {
    $errors = array_merge($errors, $_SESSION['upload_errors']);
    unset($_SESSION['upload_errors']);
}
?>

<h1>My Profile</h1>
<p>Manage your school's information, classes, and templates.</p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success"><p><?php echo htmlspecialchars($success_message); ?></p></div>
<?php endif; ?>

<div class="card">
    <h3>School Information</h3>
    <form action="profile.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
            <label for="school_name">School Name</label>
            <input type="text" id="school_name" name="school_name" class="form-control" required value="<?php echo htmlspecialchars($profile['name']); ?>">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Public Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="school_logo">School Logo</label>
            <?php if (!empty($profile['logo_url']) && file_exists($profile['logo_url'])): ?>
                <div style="margin-bottom: 15px;">
                    <img src="<?php echo htmlspecialchars($profile['logo_url']); ?>" alt="Current School Logo" style="max-width: 150px; max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                    <p><small>Current logo. Upload a new image below to replace it.</small></p>
                </div>
            <?php endif; ?>
            <input type="file" id="school_logo" name="school_logo" class="form-control" accept="image/png, image/jpeg, image/gif">
            <input type="hidden" name="existing_logo_url" value="<?php echo htmlspecialchars($profile['logo_url'] ?? ''); ?>">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<div class="card">
    <h3><i class="fas fa-users"></i> Bulk Add Students via CSV</h3>
    <p>Upload a CSV file to quickly add multiple students. Required columns in order: <strong>student_id_no, name, email, class_name, phone, address</strong>. The first row must be the header. The system will skip students with an existing Student ID Number.</p>
    <form action="profile.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="bulk_add_students" value="1">
        <div class="form-group">
            <label for="student_csv">Select CSV File</label>
            <input type="file" id="student_csv" name="student_csv" class="form-control" required accept=".csv, text/csv">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fas fa-upload"></i> Upload and Process Students</button>
        </div>
    </form>
</div>

<div class="card">
    <h3><i class="fas fa-file-csv"></i> Fee Structure Upload</h3>
    <p>Upload your fee structure CSV file. The file should be a grid with fee types in the first column and classes in the top row.</p>
    <form action="profile.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="fee_structure_csv">Select CSV File</label>
            <input type="file" id="fee_structure_csv" name="fee_structure_csv" class="form-control" required accept=".csv, text/csv">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload and Process Fees</button>
        </div>
    </form>
</div>


<div class="card">
    <h3>Manage Classes</h3>
    <p>Add or remove school classes. These will be available when adding or editing students.</p>
    <form action="profile.php" method="post" style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 20px;">
        <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
            <label for="class_name" style="margin-bottom: 5px;">New Class Name</label>
            <input type="text" id="class_name" name="class_name" class="form-control" required>
        </div>
        <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
    </form>
    <hr>
    <h4>Existing Classes</h4>
    <?php if (empty($classes)): ?>
        <p>No classes have been added yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['name']); ?></td>
                        <td style="text-align: right;">
                            <form action="profile.php" method="post" onsubmit="return confirm('Are you sure you want to delete this class?');" style="display:inline;">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <button type="submit" name="delete_class" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Manage Invoice Templates</h3>
    <p>Edit or delete reusable templates for creating invoices quickly.</p>
    <?php if (empty($templates)): ?>
        <p>No invoice templates have been saved yet. You can save one from the 'Create Invoice' page.</p>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Template Name</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($template['name']); ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons">
                                    <button class="btn-icon btn-edit" onclick='openEditTemplateModal(<?= htmlspecialchars(json_encode($template), ENT_QUOTES, "UTF-8") ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="profile.php" method="post" onsubmit="return confirm('Are you sure you want to delete this template?');" style="display:inline;">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <button type="submit" name="delete_template" class="btn-icon btn-delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3><i class="fas fa-shield-alt"></i> Administrative Tools</h3>
    <p>Access high-level system logs and security features.</p>
    <div class="form-actions">
        <a href="audit_trail.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> View System Audit Trail
        </a>
    </div>
</div>

<div id="editTemplateModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Edit Invoice Template</h3>
            <span class="close" onclick="closeModal('editTemplateModal')">&times;</span>
        </div>
        <form id="editTemplateForm" method="post" onsubmit="prepareTemplateUpdate()">
            <input type="hidden" name="update_template" value="1">
            <input type="hidden" name="template_id" id="edit_template_id">
            <input type="hidden" name="template_items_json" id="edit_template_items_json">

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_template_name">Template Name</label>
                    <input type="text" id="edit_template_name" name="template_name" class="form-control" required>
                </div>
                
                <h4>Template Items</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Item</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 20%;">Rate</th>
                            <th style="width: 20%; text-align: right;">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="edit-template-items-container"></tbody>
                </table>
                <button type="button" class="btn-secondary btn-add-item" onclick="addTemplateItem()">+ Add line</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('editTemplateModal')">Cancel</button>
                <button type="submit" class="btn-primary">Update Template</button>
            </div>
        </form>
    </div>
</div>

<template id="edit-item-row-template">
    <tr>
        <td>
            <select name="item_id" class="item-select" required>
                <option value="">Select Item...</option>
                <?php foreach ($items_list as $item): ?>
                    <?php if (empty($item['sub_items'])): ?>
                        <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </option>
                    <?php else: ?>
                        <optgroup label="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php foreach ($item['sub_items'] as $sub_item): ?>
                                <option value="<?php echo $sub_item['id']; ?>" data-price="<?php echo $sub_item['price']; ?>">
                                    <?php echo htmlspecialchars($item['name'] . " (" . $sub_item['name'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="quantity" class="quantity" min="1" value="1" required></td>
        <td><input type="number" name="unit_price" class="unit-price" step="0.01" required></td>
        <td class="amount-cell text-right">$0.00</td>
        <td><button type="button" class="remove-item" onclick="this.closest('tr').remove()">×</button></td>
    </tr>
</template>


<?php include 'footer.php'; ?>

<script>
function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }

function openEditTemplateModal(template) {
    document.getElementById('edit_template_id').value = template.id;
    document.getElementById('edit_template_name').value = template.name;

    const itemsContainer = document.getElementById('edit-template-items-container');
    itemsContainer.innerHTML = ''; 

    const items = JSON.parse(template.items);

    if (items && items.length > 0) {
        items.forEach(item => {
            const newRow = addTemplateItem(); 
            newRow.querySelector('.item-select').value = item.item_id;
            newRow.querySelector('.quantity').value = item.quantity;
            newRow.querySelector('.unit-price').value = item.unit_price;
            updateTemplateItemAmount(newRow); 
        });
    }

    openModal('editTemplateModal');
}

function addTemplateItem() {
    const container = document.getElementById('edit-template-items-container');
    const templateNode = document.getElementById('edit-item-row-template').content.cloneNode(true);
    const newRow = templateNode.querySelector('tr');
    
    newRow.querySelector('.item-select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value && selectedOption.dataset.price) {
            newRow.querySelector('.unit-price').value = selectedOption.dataset.price;
        }
        updateTemplateItemAmount(newRow);
    });
    newRow.querySelector('.quantity').addEventListener('input', () => updateTemplateItemAmount(newRow));
    newRow.querySelector('.unit-price').addEventListener('input', () => updateTemplateItemAmount(newRow));

    container.appendChild(templateNode);
    return container.lastElementChild;
}

function updateTemplateItemAmount(row) {
    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
    const amountCell = row.querySelector('.amount-cell');
    amountCell.textContent = '$' + (quantity * unitPrice).toFixed(2);
}

function prepareTemplateUpdate() {
    const items = [];
    document.querySelectorAll('#edit-template-items-container tr').forEach(row => {
        const item_id = row.querySelector('.item-select').value;
        const quantity = row.querySelector('.quantity').value;
        const unit_price = row.querySelector('.unit-price').value;
        
        if (item_id && quantity > 0) {
            items.push({ item_id, quantity, unit_price });
        }
    });
    
    document.getElementById('edit_template_items_json').value = JSON.stringify(items);
}
</script>