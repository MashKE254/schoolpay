<?php
// profile.php - User and School Profile Management
// All processing logic now comes BEFORE any HTML output.

require 'config.php';
require 'functions.php';
session_start(); // Start session here to access $_SESSION

// Ensure user is logged in
if (!isset($_SESSION['school_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';
$school_id = $_SESSION['school_id'];

// --- BLOCK 1: FORM PROCESSING ---

// Handle Fee Structure CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fee_structure_csv'])) {
    $upload_errors = [];
    $processed_items = 0;

    if (isset($_FILES['fee_structure_csv']['error']) && $_FILES['fee_structure_csv']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fee_structure_csv']['tmp_name'];

        $pdo->beginTransaction();
        try {
            $handle = fopen($file, "r");

            // BOM check
            $bom = "\xef\xbb\xbf";
            if (fgets($handle, 4) !== $bom) {
                rewind($handle);
            }

            // Read header row for Class Names
            $class_headers = fgetcsv($handle);
            if (!$class_headers || count($class_headers) < 2) {
                throw new Exception("CSV Format Error: Could not read the header row, or it has fewer than two columns. Please ensure the first row contains the class names, starting from the second column.");
            }
            array_shift($class_headers); // Remove the first column header (which should be empty)

            $line_number = 1;
            while (($row_data = fgetcsv($handle)) !== FALSE) {
                $line_number++;
                if (count($row_data) < 2 || empty(trim($row_data[0]))) {
                    continue; // Skip empty or malformed rows
                }

                $category_name = trim($row_data[0]);
                
                // Find or create the parent item for the category
                $stmt_find_parent = $pdo->prepare("SELECT id FROM items WHERE name = ? AND school_id = ? AND parent_id IS NULL");
                $stmt_find_parent->execute([$category_name, $school_id]);
                $parent_item = $stmt_find_parent->fetch();
                $parent_id = null;

                if ($parent_item) {
                    $parent_id = $parent_item['id'];
                } else {
                    $stmt_create_parent = $pdo->prepare("INSERT INTO items (school_id, name, price, item_type) VALUES (?, ?, 0.00, 'parent')");
                    $stmt_create_parent->execute([$school_id, $category_name]);
                    $parent_id = $pdo->lastInsertId();
                }

                // Loop through the prices in the current row
                foreach($class_headers as $index => $class_name) {
                    $class_name = trim($class_name);
                    if (empty($class_name)) continue;

                    // Prices start from the second column (index 1) in the data row
                    $price_raw = $row_data[$index + 1] ?? '0';
                    $price_sanitized = str_replace(['$', ',', ' '], '', $price_raw);
                    $price = filter_var($price_sanitized, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                    
                    if ($price === null) {
                        $upload_errors[] = "Skipped on row {$line_number}: Invalid price format '{$price_raw}' for '{$category_name} - {$class_name}'.";
                        continue;
                    }
                    if ($price <= 0) continue; // Intentionally skip zero-price items

                    $item_name = $class_name;
                    $description = "Fee for {$category_name} ({$class_name})";
                    
                    // Check if this specific sub-item already exists
                    $stmt_find_child = $pdo->prepare("SELECT id FROM items WHERE name = ? AND parent_id = ? AND school_id = ?");
                    $stmt_find_child->execute([$item_name, $parent_id, $school_id]);
                    $existing_item = $stmt_find_child->fetch();

                    if ($existing_item) {
                        $stmt_update_child = $pdo->prepare("UPDATE items SET price = ?, description = ? WHERE id = ?");
                        $stmt_update_child->execute([$price, $description, $existing_item['id']]);
                    } else {
                        $stmt_insert_child = $pdo->prepare("INSERT INTO items (school_id, name, price, description, parent_id, item_type) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert_child->execute([$school_id, $item_name, $price, $description, $parent_id, 'child']);
                    }
                    $processed_items++;
                }
            }

            if ($processed_items === 0) {
                $upload_errors[] = "The file was read successfully, but no valid fee items with a price greater than zero were found. Please check your file's content and structure.";
            }

            if (!empty($upload_errors)) {
                // If there were non-fatal errors, we still don't want to save partial data.
                throw new Exception("Please fix the errors listed above and re-upload the file.");
            }
            
            $pdo->commit();
            fclose($handle);
            header("Location: profile.php?upload_success=1&count={$processed_items}");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $upload_errors[] = "A critical error occurred: " . $e->getMessage();
        }
    } else {
        $upload_errors[] = 'File upload failed. Error code: ' . $_FILES['fee_structure_csv']['error'];
    }
    
    $_SESSION['upload_errors'] = $upload_errors;
    header("Location: profile.php?upload_error=1");
    exit();
}


// Handle Add Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $new_class_name = trim($_POST['class_name']);
    if (!empty($new_class_name)) {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND school_id = ?");
        $stmt->execute([$new_class_name, $school_id]);
        if ($stmt->fetch()) {
            $errors[] = 'This class name already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO classes (school_id, name) VALUES (?, ?)");
            $stmt->execute([$school_id, $new_class_name]);
            header("Location: profile.php?class_added=1");
            exit();
        }
    } else {
        $errors[] = 'Class name cannot be empty.';
    }
}

// Handle Delete Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    $class_id_to_delete = $_POST['class_id'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ? AND school_id = ?");
    $stmt->execute([$class_id_to_delete, $school_id]);
    header("Location: profile.php?class_deleted=1");
    exit();
}


// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $school_name = trim($_POST['school_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $logo_url = trim($_POST['logo_url']);

    if (empty($school_name)) {
        $errors[] = 'School name cannot be empty.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE schools SET name = ? WHERE id = ?");
            $stmt->execute([$school_name, $school_id]);
            $stmt = $pdo->prepare("SELECT id FROM school_details WHERE school_id = ?");
            $stmt->execute([$school_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE school_details SET address = ?, phone = ?, email = ?, logo_url = ? WHERE school_id = ?");
                $stmt->execute([$address, $phone, $email, $logo_url, $school_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO school_details (school_id, address, phone, email, logo_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $address, $phone, $email, $logo_url]);
            }
            $pdo->commit();
            $success_message = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}

// --- BLOCK 2: PAGE DISPLAY SETUP ---
include 'header.php';

// Fetch current school details for the form
$stmt = $pdo->prepare("
    SELECT s.name, sd.address, sd.phone, sd.email, sd.logo_url
    FROM schools s
    LEFT JOIN school_details sd ON s.id = sd.school_id
    WHERE s.id = ?
");
$stmt->execute([$school_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die("Error: Could not retrieve school profile.");
}

// Fetch current classes for the school
$class_stmt = $pdo->prepare("SELECT id, name FROM classes WHERE school_id = ? ORDER BY name");
$class_stmt->execute([$school_id]);
$classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success/error messages from redirects
if(isset($_GET['class_added'])) $success_message = 'New class added successfully!';
if(isset($_GET['class_deleted'])) $success_message = 'Class deleted successfully!';
if(isset($_GET['upload_success'])) $success_message = 'Fee structure uploaded and processed successfully!';
if(isset($_GET['upload_error']) && isset($_SESSION['upload_errors'])) {
    $errors = array_merge($errors, $_SESSION['upload_errors']);
    unset($_SESSION['upload_errors']);
}
?>

<h1>My Profile</h1>
<p>Manage your school's information and fee structure.</p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <p><?php echo htmlspecialchars($success_message); ?></p>
    </div>
<?php endif; ?>

<div class="card">
    <h3>School Information</h3>
    <form action="profile.php" method="post">
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
            <label for="logo_url">School Logo URL</label>
            <input type="text" id="logo_url" name="logo_url" class="form-control" placeholder="https://example.com/logo.png" value="<?php echo htmlspecialchars($profile['logo_url'] ?? ''); ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload and Process</button>
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
    <h3><i class="fas fa-shield-alt"></i> Administrative Tools</h3>
    <p>Access high-level system logs and security features.</p>
    <div class="form-actions">
        <a href="audit_trail.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> View System Audit Trail
        </a>
    </div>
</div>
<?php include 'footer.php'; ?>