<?php
/**
 * customer_center.php - v5.4 - Professional Grade School Finance Hub
 *
 * This comprehensive page manages all customer-facing aspects of the school's finances.
 * All form processing logic is handled at the top of the script before any HTML output
 * to ensure redirects and session messages function correctly.
 *
 * Features:
 * - Student Management: CRUD, status changes, bulk actions, and a detailed split-view.
 * - Class Management: Create/edit classes, define promotion paths, promote individual or all classes with automated invoicing. Includes drag-and-drop reordering.
 * - Fee Structure Management: Assign mandatory or optional fee items with specific prices to classes for a given academic term. Includes bulk CSV upload for both base items and fee structures.
 * - Invoice Template Management: Create/edit templates and link them to classes for auto-invoicing.
 * - Payment Processing: Payments default to an "Undeposited Funds" account.
 * - Statement Generation: Create financial statements for students or classes.
 * - Receipt Viewing: A log of all generated payment receipts.
 * - Bulk & Individual Messaging: Communicate via SMS using Africa's Talking, with support for sending direct links to invoices and statements. Now with on-the-fly token generation for legacy records.
 */

// --- BLOCK 1: SETUP & PRE-PROCESSING ---
require_once 'config.php';
require_once 'functions.php';
require_once __DIR__ . '/vendor/autoload.php';
use AfricasTalking\SDK\AfricasTalking;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['school_id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];


// --- BLOCK 2: ALL FORM & ACTION HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = true; // Assume an action is taken
    $active_tab = $_POST['active_tab'] ?? 'students'; // Default to students tab

    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Ensure no nested transactions
        }
        $pdo->beginTransaction();

        // --- Bulk Base Items Upload Handler ---
        if (isset($_POST['upload_base_items'])) {
            $active_tab = 'fee_structure';
            if (isset($_FILES['base_items_csv']) && $_FILES['base_items_csv']['error'] == UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['base_items_csv']['tmp_name'];
                
                $file = fopen($fileTmpPath, 'r');
                fgetcsv($file); // Skip header row

                $stmt_insert = $pdo->prepare(
                    "INSERT INTO items (school_id, name, description) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE description=VALUES(description)"
                );

                $processed_count = 0;
                while (($data = fgetcsv($file)) !== FALSE) {
                    if (count($data) < 2) continue; // Skip malformed rows
                    
                    $item_name = trim($data[0]);
                    $item_description = trim($data[1]);

                    if (!empty($item_name)) {
                        $stmt_insert->execute([$school_id, $item_name, $item_description]);
                        $processed_count++;
                    }
                }
                
                fclose($file);
                $_SESSION['success_message'] = "{$processed_count} base items have been successfully uploaded/updated.";

            } else {
                throw new Exception("File upload error. Please choose a valid CSV file and try again.");
            }
        }
        // --- Bulk Fee Structure Upload Handler ---
        elseif (isset($_POST['upload_fee_structure'])) {
            $active_tab = 'fee_structure';
            $academic_year = $_POST['academic_year_hidden'] ?? date('Y') . '-' . (date('Y') + 1);
            $term = $_POST['term_hidden'] ?? 'Term 1';

            if (isset($_FILES['fee_structure_csv']) && $_FILES['fee_structure_csv']['error'] == UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['fee_structure_csv']['tmp_name'];
                
                $file = fopen($fileTmpPath, 'r');
                fgetcsv($file); // Skip header row

                $stmt_item = $pdo->prepare("SELECT id FROM items WHERE school_id = ? AND name = ?");
                $stmt_class = $pdo->prepare("SELECT id FROM classes WHERE school_id = ? AND name = ?");
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO fee_structure_items (school_id, class_id, item_id, academic_year, term, amount, is_mandatory)
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE amount=VALUES(amount), is_mandatory=VALUES(is_mandatory)"
                );

                $row_count = 1;
                while (($data = fgetcsv($file)) !== FALSE) {
                    $row_count++;
                    if (count($data) < 4) continue;
                    
                    $class_name = trim($data[0]);
                    $item_name = trim($data[1]);
                    $amount = floatval($data[2]);
                    $is_mandatory = intval($data[3]);

                    $stmt_class->execute([$school_id, $class_name]);
                    $class_id = $stmt_class->fetchColumn();
                    if (!$class_id) throw new Exception("Class '{$class_name}' not found on row {$row_count}. Please ensure class names in the CSV match exactly.");

                    $stmt_item->execute([$school_id, $item_name]);
                    $item_id = $stmt_item->fetchColumn();
                    if (!$item_id) throw new Exception("Item '{$item_name}' not found on row {$row_count}. Please create all base items first.");

                    $stmt_insert->execute([$school_id, $class_id, $item_id, $academic_year, $term, $amount, $is_mandatory]);
                }
                
                fclose($file);
                $_SESSION['success_message'] = "Fee structure uploaded successfully for {$academic_year}, {$term}.";

            } else {
                throw new Exception("File upload error. Please try again.");
            }
        }
        // --- Fee Structure Management ---
        elseif (isset($_POST['create_base_item'])) {
            $stmt = $pdo->prepare("INSERT INTO items (school_id, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$school_id, trim($_POST['item_name']), trim($_POST['item_description'])]);
            $item_id = $pdo->lastInsertId();
            log_audit($pdo, 'CREATE', 'items', $item_id, ['data' => ['name' => $_POST['item_name']]]);
            $_SESSION['success_message'] = "Base fee item created successfully.";
            $active_tab = 'fee_structure';
        } 
        elseif (isset($_POST['assign_fee_item'])) {
            $item_id = intval($_POST['item_id']);
            $class_ids = $_POST['class_ids'] ?? [];
            $amount = floatval($_POST['amount']);
            $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
            $academic_year = trim($_POST['academic_year']);
            $term = trim($_POST['term']);

            if (empty($class_ids)) throw new Exception("You must select at least one class.");
            if (empty($item_id) || empty($academic_year) || empty($term)) throw new Exception("Missing required fields.");

            $stmt = $pdo->prepare(
                "INSERT INTO fee_structure_items (school_id, class_id, item_id, academic_year, term, amount, is_mandatory) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE amount=VALUES(amount), is_mandatory=VALUES(is_mandatory)"
            );

            foreach ($class_ids as $class_id) {
                $stmt->execute([$school_id, $class_id, $item_id, $academic_year, $term, $amount, $is_mandatory]);
            }
            $_SESSION['success_message'] = "Fee item assigned to " . count($class_ids) . " class(es) successfully.";
            $active_tab = 'fee_structure';
        }
        elseif (isset($_POST['update_fee_item'])) {
            $fee_id = intval($_POST['fee_id']);
            $amount = floatval($_POST['amount']);
            $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE fee_structure_items SET amount = ?, is_mandatory = ? WHERE id = ? AND school_id = ?");
            $stmt->execute([$amount, $is_mandatory, $fee_id, $school_id]);
            $_SESSION['success_message'] = "Fee item updated successfully.";
            $active_tab = 'fee_structure';
        }
        elseif (isset($_POST['remove_fee_item'])) {
            $fee_id = intval($_POST['fee_id']);
            $stmt = $pdo->prepare("DELETE FROM fee_structure_items WHERE id = ? AND school_id = ?");
            $stmt->execute([$fee_id, $school_id]);
            $_SESSION['success_message'] = "Fee item removed from class successfully.";
            $active_tab = 'fee_structure';
        }

        // --- Student Management ---
        elseif (isset($_POST['addStudent'])) {
            $class_id = (isset($_POST['class_id']) && is_numeric($_POST['class_id'])) ? intval($_POST['class_id']) : null;
            $token = bin2hex(random_bytes(32));

            // Combine first, middle, last name into full name for backwards compatibility
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $full_name = trim("$first_name $middle_name $last_name");
            $full_name = preg_replace('/\s+/', ' ', $full_name); // Remove extra spaces

            $stmt = $pdo->prepare("INSERT INTO students (
                school_id, student_id_no, name, first_name, middle_name, last_name, nemis_no,
                email, class_id, phone, gender, date_of_birth, birth_cert_no, nationality, religion, photo_url,
                residential_address, postal_address,
                father_first_name, father_middle_name, father_last_name, father_contact, father_email,
                mother_first_name, mother_middle_name, mother_last_name, mother_contact, mother_email,
                guardian_first_name, guardian_middle_name, guardian_last_name, guardian_contact, guardian_email,
                doctor_name, doctor_contact, doctor_email, preferred_hospital, health_insurance_provider, allergies, long_term_condition,
                transport_zone, trip, picking_point,
                sponsor, sponsor_contact, sponsor_email, food_preference, token
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $school_id,
                $_POST['student_id_no'] ?? '',
                $full_name,
                $first_name,
                $middle_name,
                $last_name,
                $_POST['nemis_no'] ?? '',
                $_POST['email'] ?? '',
                $class_id,
                $_POST['phone'] ?? '',
                $_POST['gender'] ?? null,
                !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                $_POST['birth_cert_no'] ?? '',
                $_POST['nationality'] ?? '',
                $_POST['religion'] ?? '',
                $_POST['photo_url'] ?? '',
                $_POST['residential_address'] ?? '',
                $_POST['postal_address'] ?? '',
                $_POST['father_first_name'] ?? '',
                $_POST['father_middle_name'] ?? '',
                $_POST['father_last_name'] ?? '',
                $_POST['father_contact'] ?? '',
                $_POST['father_email'] ?? '',
                $_POST['mother_first_name'] ?? '',
                $_POST['mother_middle_name'] ?? '',
                $_POST['mother_last_name'] ?? '',
                $_POST['mother_contact'] ?? '',
                $_POST['mother_email'] ?? '',
                $_POST['guardian_first_name'] ?? '',
                $_POST['guardian_middle_name'] ?? '',
                $_POST['guardian_last_name'] ?? '',
                $_POST['guardian_contact'] ?? '',
                $_POST['guardian_email'] ?? '',
                $_POST['doctor_name'] ?? '',
                $_POST['doctor_contact'] ?? '',
                $_POST['doctor_email'] ?? '',
                $_POST['preferred_hospital'] ?? '',
                $_POST['health_insurance_provider'] ?? '',
                $_POST['allergies'] ?? '',
                $_POST['long_term_condition'] ?? '',
                $_POST['transport_zone'] ?? '',
                $_POST['trip'] ?? '',
                $_POST['picking_point'] ?? '',
                $_POST['sponsor'] ?? '',
                $_POST['sponsor_contact'] ?? '',
                $_POST['sponsor_email'] ?? '',
                $_POST['food_preference'] ?? '',
                $token
            ]);
            $_SESSION['success_message'] = "Student added successfully.";

        } elseif (isset($_POST['editStudent'])) {
            $student_id = intval($_POST['student_id']);
            $class_id = (isset($_POST['class_id']) && is_numeric($_POST['class_id'])) ? intval($_POST['class_id']) : null;

            // Combine first, middle, last name into full name for backwards compatibility
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $full_name = trim("$first_name $middle_name $last_name");
            $full_name = preg_replace('/\s+/', ' ', $full_name);

            $stmt = $pdo->prepare("UPDATE students SET
                student_id_no = ?, name = ?, first_name = ?, middle_name = ?, last_name = ?, nemis_no = ?,
                email = ?, class_id = ?, phone = ?, gender = ?, date_of_birth = ?, birth_cert_no = ?,
                nationality = ?, religion = ?, photo_url = ?, residential_address = ?, postal_address = ?,
                father_first_name = ?, father_middle_name = ?, father_last_name = ?, father_contact = ?, father_email = ?,
                mother_first_name = ?, mother_middle_name = ?, mother_last_name = ?, mother_contact = ?, mother_email = ?,
                guardian_first_name = ?, guardian_middle_name = ?, guardian_last_name = ?, guardian_contact = ?, guardian_email = ?,
                doctor_name = ?, doctor_contact = ?, doctor_email = ?, preferred_hospital = ?,
                health_insurance_provider = ?, allergies = ?, long_term_condition = ?,
                transport_zone = ?, trip = ?, picking_point = ?,
                sponsor = ?, sponsor_contact = ?, sponsor_email = ?, food_preference = ?, status = ?
                WHERE id = ? AND school_id = ?");

            $stmt->execute([
                $_POST['student_id_no'] ?? '',
                $full_name,
                $first_name,
                $middle_name,
                $last_name,
                $_POST['nemis_no'] ?? '',
                $_POST['email'] ?? '',
                $class_id,
                $_POST['phone'] ?? '',
                $_POST['gender'] ?? null,
                !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                $_POST['birth_cert_no'] ?? '',
                $_POST['nationality'] ?? '',
                $_POST['religion'] ?? '',
                $_POST['photo_url'] ?? '',
                $_POST['residential_address'] ?? '',
                $_POST['postal_address'] ?? '',
                $_POST['father_first_name'] ?? '',
                $_POST['father_middle_name'] ?? '',
                $_POST['father_last_name'] ?? '',
                $_POST['father_contact'] ?? '',
                $_POST['father_email'] ?? '',
                $_POST['mother_first_name'] ?? '',
                $_POST['mother_middle_name'] ?? '',
                $_POST['mother_last_name'] ?? '',
                $_POST['mother_contact'] ?? '',
                $_POST['mother_email'] ?? '',
                $_POST['guardian_first_name'] ?? '',
                $_POST['guardian_middle_name'] ?? '',
                $_POST['guardian_last_name'] ?? '',
                $_POST['guardian_contact'] ?? '',
                $_POST['guardian_email'] ?? '',
                $_POST['doctor_name'] ?? '',
                $_POST['doctor_contact'] ?? '',
                $_POST['doctor_email'] ?? '',
                $_POST['preferred_hospital'] ?? '',
                $_POST['health_insurance_provider'] ?? '',
                $_POST['allergies'] ?? '',
                $_POST['long_term_condition'] ?? '',
                $_POST['transport_zone'] ?? '',
                $_POST['trip'] ?? '',
                $_POST['picking_point'] ?? '',
                $_POST['sponsor'] ?? '',
                $_POST['sponsor_contact'] ?? '',
                $_POST['sponsor_email'] ?? '',
                $_POST['food_preference'] ?? '',
                $_POST['status'] ?? 'active',
                $student_id,
                $school_id
            ]);
            $_SESSION['success_message'] = "Student updated successfully.";
        } elseif (isset($_POST['deactivate_student'])) {
            $stmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE id = ? AND school_id = ?");
            $stmt->execute([$_POST['student_id'], $school_id]);
            $_SESSION['success_message'] = "Student deactivated.";
        } elseif (isset($_POST['reactivate_student'])) {
            $stmt = $pdo->prepare("UPDATE students SET status = 'active' WHERE id = ? AND school_id = ?");
            $stmt->execute([$_POST['student_id'], $school_id]);
            $_SESSION['success_message'] = "Student reactivated.";
        } elseif (isset($_POST['bulk_action_submit'])) {
            $bulk_action = $_POST['bulk_action'];
            $student_ids = $_POST['student_ids'] ?? [];
            if (!empty($student_ids)) {
                $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
                $params = array_merge($student_ids, [$school_id]);
                if ($bulk_action === 'delete') {
                    $stmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE id IN ($placeholders) AND school_id = ?");
                    $stmt->execute($params);
                    $_SESSION['success_message'] = count($student_ids) . " students deactivated.";
                } elseif ($bulk_action === 'activate') {
                    $stmt = $pdo->prepare("UPDATE students SET status = 'active' WHERE id IN ($placeholders) AND school_id = ?");
                    $stmt->execute($params);
                     $_SESSION['success_message'] = count($student_ids) . " students activated.";
                }
            }
        }

        // --- Class Management ---
        elseif (isset($_POST['add_class'])) {
            $stmt = $pdo->prepare("INSERT INTO classes (school_id, name) VALUES (?, ?)");
            $stmt->execute([$school_id, trim($_POST['class_name'])]);
            $_SESSION['success_message'] = "Class added successfully.";
        } elseif (isset($_POST['update_classes'])) {
            foreach ($_POST['class_name'] as $class_id => $name) {
                $next_class_id = !empty($_POST['next_class_id'][$class_id]) ? $_POST['next_class_id'][$class_id] : null;
                // Get the display order sent from the form
                $display_order = isset($_POST['class_order'][$class_id]) ? intval($_POST['class_order'][$class_id]) : 0;
                
                $stmt = $pdo->prepare("UPDATE classes SET name = ?, next_class_id = ?, display_order = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([trim($name), $next_class_id, $display_order, $class_id, $school_id]);
            }
            $_SESSION['success_message'] = "Class promotion paths and order updated.";
        }
        elseif (isset($_POST['archive_class'])) {
            $class_id = intval($_POST['class_id']);
            $stmt = $pdo->prepare("UPDATE classes SET is_archived = 1 WHERE id = ? AND school_id = ?");
            $stmt->execute([$class_id, $school_id]);
            $_SESSION['success_message'] = "Class archived successfully.";
        }
        elseif (isset($_POST['unarchive_class'])) {
            $class_id = intval($_POST['class_id']);
            $stmt = $pdo->prepare("UPDATE classes SET is_archived = 0 WHERE id = ? AND school_id = ?");
            $stmt->execute([$class_id, $school_id]);
            $_SESSION['success_message'] = "Class unarchived successfully.";
        }
        elseif (isset($_POST['generate_bulk_invoices'])) {
            $class_id = intval($_POST['class_id']);
            $template_id = intval($_POST['template_id']);
            $due_date = $_POST['due_date'];

            if ($class_id > 0 && $template_id > 0 && !empty($due_date)) {
                $stmt_students = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
                $stmt_students->execute([$class_id, $school_id]);
                $students_in_class = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                foreach ($students_in_class as $student) {
                    createInvoiceFromTemplate($pdo, $school_id, $student['id'], $template_id, $due_date);
                }
                $_SESSION['success_message'] = "Bulk invoices for " . count($students_in_class) . " students generated successfully.";
            } else {
                $_SESSION['error_message'] = "Missing required information for bulk invoice generation.";
            }
        }
        elseif (isset($_POST['promote_class'])) {
            $active_tab = 'classes';
            $class_id_to_promote = intval($_POST['class_id_to_promote']);
            $new_academic_year = trim($_POST['new_academic_year']);
            $new_term = trim($_POST['new_term']);
            $new_due_date = trim($_POST['new_due_date']);

            if (empty($class_id_to_promote) || empty($new_academic_year) || empty($new_term) || empty($new_due_date)) {
                throw new Exception("All fields are required for promotion.");
            }

            // Find the destination class
            $stmt_next_class = $pdo->prepare("SELECT next_class_id FROM classes WHERE id = ? AND school_id = ?");
            $stmt_next_class->execute([$class_id_to_promote, $school_id]);
            $next_class_id = $stmt_next_class->fetchColumn();

            if (empty($next_class_id)) {
                throw new Exception("The selected class does not have a 'Next Class' promotion path defined. Please set it first.");
            }

            // Get all active students in the class to be promoted
            $stmt_students = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
            $stmt_students->execute([$class_id_to_promote, $school_id]);
            $students_to_promote = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($students_to_promote)) {
                throw new Exception("There are no active students in the selected class to promote.");
            }

            $promoted_count = 0;
            $errors = [];

            // The main loop
            foreach ($students_to_promote as $student) {
                try {
                    promoteStudentAndCreateInvoice(
                        $pdo,
                        $school_id,
                        $student['id'],
                        $next_class_id,
                        $new_academic_year,
                        $new_term,
                        $new_due_date
                    );
                    $promoted_count++;
                } catch (Exception $e) {
                    $errors[] = "Could not promote student " . $student['name'] . ": " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                 $_SESSION['warning_message'] = "Promotion completed with some errors. Successfully promoted: {$promoted_count}. Errors: " . implode('; ', $errors);
            } else {
                 $_SESSION['success_message'] = "Successfully promoted and invoiced {$promoted_count} students.";
            }
        }
        elseif (isset($_POST['promote_all_classes'])) {
            $active_tab = 'classes';
            $new_academic_year = trim($_POST['new_academic_year']);
            $new_term = trim($_POST['new_term']);
            $new_due_date = trim($_POST['new_due_date']);

            if (empty($new_academic_year) || empty($new_term) || empty($new_due_date)) {
                throw new Exception("Academic Year, Term, and Due Date are required to promote all classes.");
            }

            // Fetch all non-archived classes that have a promotion path defined.
            // CRITICAL: Order by display_order DESC to promote higher classes first (e.g., Grade 2->3 before Grade 1->2).
            $stmt_classes = $pdo->prepare(
                "SELECT id, name, next_class_id FROM classes 
                 WHERE school_id = ? AND is_archived = 0 AND next_class_id IS NOT NULL 
                 ORDER BY display_order DESC"
            );
            $stmt_classes->execute([$school_id]);
            $classes_to_promote = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

            if (empty($classes_to_promote)) {
                throw new Exception("No classes with a defined promotion path were found.");
            }

            $total_promoted_count = 0;
            $class_summary = [];
            $errors = [];

            // Outer loop: Iterate through each class
            foreach ($classes_to_promote as $class) {
                $class_id_to_promote = $class['id'];
                $next_class_id = $class['next_class_id'];
                
                // Get all active students in the current class
                $stmt_students = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
                $stmt_students->execute([$class_id_to_promote, $school_id]);
                $students_to_promote = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                if (empty($students_to_promote)) {
                    continue; // Skip empty classes
                }

                $class_promoted_count = 0;
                // Inner loop: Promote each student in the class
                foreach ($students_to_promote as $student) {
                    try {
                        promoteStudentAndCreateInvoice(
                            $pdo,
                            $school_id,
                            $student['id'],
                            $next_class_id,
                            $new_academic_year,
                            $new_term,
                            $new_due_date
                        );
                        $class_promoted_count++;
                    } catch (Exception $e) {
                        $errors[] = "Could not promote student " . $student['name'] . " from class " . $class['name'] . ": " . $e->getMessage();
                    }
                }
                
                if ($class_promoted_count > 0) {
                    $total_promoted_count += $class_promoted_count;
                    $class_summary[] = "{$class_promoted_count} student(s) from " . htmlspecialchars($class['name']);
                }
            }

            if (!empty($errors)) {
                 $_SESSION['warning_message'] = "Promotion completed with some errors. Total successfully promoted: {$total_promoted_count}. Errors: " . implode('; ', $errors);
            } else {
                 $_SESSION['success_message'] = "Successfully promoted {$total_promoted_count} students across " . count($class_summary) . " classes. (" . implode(', ', $class_summary) . ").";
            }
        }

        // --- Invoice Template Management ---
        elseif (isset($_POST['save_template'])) {
            $template_name = trim($_POST['template_name']);
            $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
            $items_json = json_encode($_POST['items'] ?? []);

            $stmt = $pdo->prepare("INSERT INTO invoice_templates (school_id, name, items, class_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$school_id, $template_name, $items_json, $class_id]);
            $_SESSION['success_message'] = "Template created successfully.";
        }
        elseif (isset($_POST['update_template'])) {
            $template_id = intval($_POST['template_id']);
            $template_name = trim($_POST['template_name']);
            $template_items_json = $_POST['template_items_json'];

            if ($template_id > 0 && !empty($template_name)) {
                $stmt = $pdo->prepare("UPDATE invoice_templates SET name = ?, items = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$template_name, $template_items_json, $template_id, $school_id]);
                $_SESSION['success_message'] = "Invoice template updated successfully!";
                $active_tab = 'templates';
            } else {
                $_SESSION['error_message'] = "Template name cannot be empty for an update.";
            }
        }
        elseif (isset($_POST['delete_template'])) {
            $template_id = intval($_POST['template_id']);
            if ($template_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM invoice_templates WHERE id = ? AND school_id = ?");
                $stmt->execute([$template_id, $school_id]);
                $_SESSION['success_message'] = "Invoice template deleted successfully!";
                $active_tab = 'templates';
            }
        }

        // --- Payment Promise Management ---
        elseif (isset($_POST['add_promise'])) {
            $stmt = $pdo->prepare(
                "INSERT INTO payment_promises (school_id, student_id, invoice_id, promise_date, promised_due_date, promised_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([ $school_id, $_POST['promise_student_id'], $_POST['promise_invoice_id'], $_POST['promise_date'], $_POST['promised_due_date'], $_POST['promised_amount'], $_POST['notes'] ]);
            $_SESSION['success_message'] = "Payment promise recorded successfully.";
        }

        // --- Payment Processing ---
        elseif (isset($_POST['process_payment'])) {
            $student_id_payment = intval($_POST['student_id']);
            $payment_date = $_POST['payment_date'];
            $total_payment = 0;
            $undeposited_funds_id = getUndepositedFundsAccountId($pdo, $school_id);
            if ($undeposited_funds_id <= 0) throw new Exception("The required 'Undeposited Funds' account is missing.");
            
            if (isset($_POST['invoice_ids'])) {
                foreach ($_POST['invoice_ids'] as $index => $invoice_id) {
                    $amount = floatval($_POST['payment_amounts'][$index]);
                    if ($amount > 0) {
                        $total_payment += $amount;
                        $receipt_number = 'REC-' . strtoupper(uniqid());
                        $stmt_receipt = $pdo->prepare("INSERT INTO payment_receipts (school_id, receipt_number, student_id, payment_date, amount, payment_method, memo, coa_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_receipt->execute([$school_id, $receipt_number, $student_id_payment, $payment_date, $amount, $_POST['payment_method'], $_POST['memo'], $undeposited_funds_id]);
                        $receiptId = $pdo->lastInsertId();
                        $stmt_payment = $pdo->prepare("INSERT INTO payments (school_id, invoice_id, student_id, payment_date, amount, payment_method, memo, receipt_id, coa_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_payment->execute([$school_id, $invoice_id, $student_id_payment, $payment_date, $amount, $_POST['payment_method'], $_POST['memo'], $receiptId, $undeposited_funds_id]);
                    }
                }
            }
            if ($total_payment > 0) {
                $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
                $stmt_student = $pdo->prepare("SELECT name FROM students WHERE id = ?"); 
                $stmt_student->execute([$student_id_payment]);
                $student_name = $stmt_student->fetchColumn();
                $description = "Fee payment from {$student_name}.";
                create_journal_entry($pdo, $school_id, $payment_date, $description, $total_payment, $undeposited_funds_id, $accounts_receivable_id);
            }
            $_SESSION['success_message'] = "Payment recorded successfully.";
        }
        
        // --- Single Student Message Handler ---
        elseif (isset($_POST['send_single_message'])) {
            $student_id_messaging = intval($_POST['student_id']);
            $phone_number = $_POST['phone_number'] ?? '';
            $message_template = trim($_POST['message_body']);
        
            if (empty($message_template)) throw new Exception("The message body cannot be empty.");
            if (empty($student_id_messaging) || empty($phone_number)) throw new Exception("Missing student ID or phone number.");
        
            $formatted_phone = formatPhoneNumberForAT($phone_number);
            if (!$formatted_phone) throw new Exception("The student's phone number '{$phone_number}' is invalid.");
        
            $stmt_student = $pdo->prepare("SELECT name FROM students WHERE id = ? AND school_id = ?");
            $stmt_student->execute([$student_id_messaging, $school_id]);
            $student = $stmt_student->fetch(PDO::FETCH_ASSOC);
            if (!$student) throw new Exception("Student not found.");
            
            $school_name = $_SESSION['school_name'] ?? 'Your School';
            $balance = getStudentBalance($pdo, $student_id_messaging, $school_id);
            $personalized_message = str_ireplace(
                ['[school_name]', '[student_name]', '[balance]'], 
                [$school_name, $student['name'], number_format($balance, 2)], 
                $message_template
            );
            
            $result = sendBulkSms([$formatted_phone], $personalized_message);
            
            if (is_array($result) && isset($result['status']) && $result['status'] === 'success') {
                $recipient = $result['data']->SMSMessageData->Recipients[0] ?? null;
                $delivery_status = $recipient->status ?? 'Submitted';
                // Codes 100-102 are all success states (Processed, Sent, Queued)
                if ($recipient && in_array($recipient->statusCode, [100, 101, 102])) {
                    $_SESSION['success_message'] = "Message for " . $student['name'] . " submitted to network. Status: " . $delivery_status;
                } else {
                    throw new Exception("Message submission failed for " . $student['name'] . ". API Status: " . $delivery_status);
                }
            } else {
                $error_message = $result['error'] ?? 'Unknown API Error';
                throw new Exception("Failed to send message. Reason: {$error_message}");
            }
        }
        // --- Bulk Messaging (Re-engineered for Links) ---
        elseif (isset($_POST['send_bulk_message'])) {
            $send_to_group = $_POST['send_to_group'];
            $class_id_messaging = $_POST['class_id_messaging'] ?? null;
            $message_template = trim($_POST['message_body']);
            
            if (strpos($send_to_group, '_link') !== false && !defined('BASE_URL')) {
                throw new Exception("Error: BASE_URL is not defined in config.php. This is required for sending links via SMS.");
            }
            if (empty($message_template)) {
                throw new Exception("The message body cannot be empty.");
            }

            $link_type = 'none';
            $base_query = "SELECT s.id, s.phone, s.name, s.token FROM students s ";
            $params = [$school_id];

            switch ($send_to_group) {
                case 'all':
                    $base_query .= " WHERE s.school_id = ? AND s.status = 'active'";
                    break;
                case 'class':
                    if (empty($class_id_messaging)) throw new Exception("Please select a class.");
                    $base_query .= " WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'";
                    $params[] = $class_id_messaging;
                    break;
                case 'unpaid':
                    $base_query = "SELECT DISTINCT s.id, s.phone, s.name, s.token FROM students s JOIN invoices i ON s.id = i.student_id WHERE s.school_id = ? AND i.balance > 0.01 AND s.status = 'active'";
                    break;
                case 'unpaid_invoices_link':
                    $link_type = 'invoice';
                    $base_query = "SELECT DISTINCT s.id, s.phone, s.name, s.token FROM students s JOIN invoices i ON s.id = i.student_id WHERE s.school_id = ? AND i.balance > 0.01 AND s.status = 'active'";
                    break;
                case 'all_statements_link':
                    $link_type = 'statement';
                    $base_query .= " WHERE s.school_id = ? AND s.status = 'active'";
                    break;
                case 'class_statements_link':
                    $link_type = 'statement';
                    if (empty($class_id_messaging)) throw new Exception("Please select a class for sending statement links.");
                    $base_query .= " WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'";
                    $params[] = $class_id_messaging;
                    break;
                default:
                    throw new Exception("Invalid recipient group selected.");
            }
            
            $stmt = $pdo->prepare($base_query);
            $stmt->execute($params);
            $students_to_message = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students_to_message)) {
                throw new Exception("No students found for the selected recipient group.");
            }
            
            $link_data = [];
            if ($link_type === 'statement') {
                $link_data['date_from'] = $_POST['statement_link_date_from'] ?? date('Y-m-01');
                $link_data['date_to'] = $_POST['statement_link_date_to'] ?? date('Y-m-d');
                if (empty($link_data['date_from']) || empty($link_data['date_to'])) {
                    throw new Exception("Please provide a 'From' and 'To' date for the statements.");
                }
            }
            
            $school_name = $_SESSION['school_name'] ?? 'Your School';
            $successful_sends = 0; $failed_sends = 0; $failed_numbers = [];
            
            foreach ($students_to_message as $student) {
                $formatted_phone = formatPhoneNumberForAT($student['phone']);
                if ($formatted_phone) {
                    $balance = getStudentBalance($pdo, $student['id'], $school_id);
                    $link = '';

                    // Generate link if required
                    if ($link_type === 'invoice') {
                        // Fetch the ID and TOKEN of the oldest unpaid invoice
                        $stmt_oldest_unpaid = $pdo->prepare("SELECT id, token FROM invoices WHERE student_id = ? AND school_id = ? AND balance > 0.01 ORDER BY invoice_date ASC LIMIT 1");
                        $stmt_oldest_unpaid->execute([$student['id'], $school_id]);
                        $invoice_info = $stmt_oldest_unpaid->fetch(PDO::FETCH_ASSOC);

                        if ($invoice_info) {
                            $invoice_token = $invoice_info['token'];
                            $invoice_id = $invoice_info['id'];

                            // If token is missing for this old invoice, generate, update, and use it now
                            if (empty($invoice_token)) {
                                $invoice_token = bin2hex(random_bytes(32));
                                $stmt_update_inv_token = $pdo->prepare("UPDATE invoices SET token = ? WHERE id = ?");
                                $stmt_update_inv_token->execute([$invoice_token, $invoice_id]);
                            }
                            $link = BASE_URL . "/view_invoice.php?token=" . $invoice_token;
                        }
                    } elseif ($link_type === 'statement') {
                        $student_token = $student['token'];
                        
                        // If token is missing for this old student record, generate, update, and use it now
                        if (empty($student_token)) {
                            $student_token = bin2hex(random_bytes(32));
                            $stmt_update_std_token = $pdo->prepare("UPDATE students SET token = ? WHERE id = ?");
                            $stmt_update_std_token->execute([$student_token, $student['id']]);
                        }
                        
                        // Now that we're sure we have a token, create the link
                        $link = BASE_URL . "/generate_statements.php?token=" . $student_token . "&date_from=" . urlencode($link_data['date_from']) . "&date_to=" . urlencode($link_data['date_to']);
                    }
                    
                    $personalized_message = str_ireplace(
                        ['[school_name]', '[student_name]', '[balance]', '[link]'], 
                        [$school_name, $student['name'], number_format($balance, 2), $link], 
                        $message_template
                    );
                    $result = sendBulkSms([$formatted_phone], $personalized_message);
                    
                    if (is_array($result) && isset($result['status']) && $result['status'] === 'success') {
                         $recipient = $result['data']->SMSMessageData->Recipients[0] ?? null;
                         if ($recipient && in_array($recipient->statusCode, [100, 101, 102])) {
                             $successful_sends++;
                         } else {
                            $failed_sends++; $failed_numbers[] = $student['phone'] . ' (API Status: ' . ($recipient->status ?? 'Unknown') . ')';
                         }
                    } else {
                        $failed_sends++; $failed_numbers[] = $student['phone'] . ' (Error: ' . ($result['error'] ?? 'N/A') . ')';
                    }

                } else { 
                    $failed_sends++; 
                    $failed_numbers[] = $student['phone'] . ' (Invalid Format)'; 
                }
            }
            
            $feedback = "Message sending complete. Successful: {$successful_sends}. Failed: {$failed_sends}.";
            if ($failed_sends > 0) { 
                $_SESSION['warning_message'] = $feedback . " Failed numbers: " . implode(', ', $failed_numbers); 
            } else { 
                $_SESSION['success_message'] = $feedback; 
            }
        } else {
            $action_taken = false;
        }
        
        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
    
    if($action_taken) {
        $redirect_url = $_SERVER['PHP_SELF'] . "?tab=" . $active_tab;
        if ($active_tab == 'fee_structure') {
            $fs_year = $_GET['fs_year'] ?? date('Y') . '-' . (date('Y') + 1);
            $fs_term = $_GET['fs_term'] ?? 'Term 1';
            $redirect_url .= "&fs_year=" . urlencode($fs_year) . "&fs_term=" . urlencode($fs_term);
        }
        header("Location: " . $redirect_url);
        exit();
    }
}


// --- BLOCK 3: PAGE DISPLAY SETUP ---
require_once 'header.php';

if (isset($_SESSION['error_message'])) {
    echo '<div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg flex items-start gap-3">
        <i class="fas fa-times-circle text-red-500 mt-0.5"></i>
        <div class="text-red-700">' . htmlspecialchars($_SESSION['error_message']) . '</div>
    </div>';
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    echo '<div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg flex items-start gap-3">
        <i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>
        <div class="text-emerald-700">' . htmlspecialchars($_SESSION['success_message']) . '</div>
    </div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['warning_message'])) {
    echo '<div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
        <div class="text-amber-700">' . htmlspecialchars($_SESSION['warning_message']) . '</div>
    </div>';
    unset($_SESSION['warning_message']);
}

// Data for Students Tab
$filter_name = $_GET['filter_name'] ?? ''; $filter_class_id = $_GET['filter_class_id'] ?? ''; $filter_status = $_GET['filter_status'] ?? 'active';
$students = getStudents($pdo, $school_id, $filter_name, $filter_class_id, $filter_status);
$all_students_for_dropdown = getStudents($pdo, $school_id, null, null, 'active');

// Data for Classes Tab
$show_archived = isset($_GET['show_archived']) ? true : false;
$stmt_classes = $pdo->prepare("SELECT c1.*, c2.name as next_class_name FROM classes c1 LEFT JOIN classes c2 ON c1.next_class_id = c2.id WHERE c1.school_id = ? AND c1.is_archived = ? ORDER BY c1.display_order ASC, c1.name ASC");
$stmt_classes->execute([$school_id, $show_archived ? 1 : 0]);
$classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
$stmt_all_classes = $pdo->prepare("SELECT id, name FROM classes WHERE school_id = ? AND is_archived = 0 ORDER BY name");
$stmt_all_classes->execute([$school_id]);
$all_classes_for_dropdown = $stmt_all_classes->fetchAll(PDO::FETCH_ASSOC);

// Data for Invoices Tab
$filter_invoice_student_id = $_GET['filter_invoice_student_id'] ?? ''; $filter_invoice_status = $_GET['filter_invoice_status'] ?? ''; $filter_invoice_start_date = $_GET['filter_invoice_start_date'] ?? ''; $filter_invoice_end_date = $_GET['filter_invoice_end_date'] ?? '';
$invoices = getInvoices($pdo, $school_id, $filter_invoice_student_id, $filter_invoice_status, $filter_invoice_start_date, $filter_invoice_end_date);

// Data for Fee Structure Tab
$current_academic_year = $_GET['fs_year'] ?? date('Y') . '-' . (date('Y') + 1);
$current_term = $_GET['fs_term'] ?? 'Term 1';
$stmt_fee_structure = $pdo->prepare("SELECT fsi.*, i.name as item_name, c.name as class_name FROM fee_structure_items fsi JOIN items i ON fsi.item_id = i.id JOIN classes c ON fsi.class_id = c.id WHERE fsi.school_id = ? AND fsi.academic_year = ? AND fsi.term = ? ORDER BY c.name, fsi.is_mandatory DESC, i.name ASC");
$stmt_fee_structure->execute([$school_id, $current_academic_year, $current_term]);
$fee_structure_items_raw = $stmt_fee_structure->fetchAll(PDO::FETCH_ASSOC);
$fee_structure_by_class = [];
foreach($fee_structure_items_raw as $item) { $fee_structure_by_class[$item['class_id']]['name'] = $item['class_name']; $fee_structure_by_class[$item['class_id']]['items'][] = $item; }
$base_items_stmt = $pdo->prepare("SELECT id, name FROM items WHERE school_id = ? ORDER BY name");
$base_items_stmt->execute([$school_id]);
$base_items = $base_items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Data for Other Tabs
$invoice_templates = getInvoiceTemplates($pdo, $school_id);
$all_receipts = getAllReceipts($pdo, $school_id);
$message_templates = [
    "Gentle Fee Reminder" => "Dear Parent, this is a friendly reminder from [school_name] that school fees are due. Your current balance is [balance]. Thank you.",
    "Overdue Balance Notice" => "Dear Parent, our records from [school_name] show an outstanding fee balance of [balance] for [student_name]. Please make a payment at your earliest convenience. Thank you.",
    "Parent-Teacher Meeting" => "Greetings from [school_name]. We invite you to a Parent-Teacher meeting on [Date] at [Time] to discuss [student_name]'s academic progress. We look forward to seeing you.",
];

?>
<style>
    /* General Styles */
    .student-view-container { display: flex; min-height: 600px; border: 1px solid var(--border); overflow: hidden; position: relative; }
    .student-list-panel { width: 40%; min-width: 350px; flex-shrink: 0; overflow-y: auto; }
    .student-detail-panel { flex-grow: 1; overflow-y: auto; padding-left: 10px; min-width: 400px; }
    #resizer { width: 10px; background-color: #f1f5f9; cursor: col-resize; }
    #student-detail-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #777; flex-direction: column; padding: 2rem; }
    #student-detail-content { display: none; }
    .student-list-panel table tr.active { background-color: #e3f2fd !important; font-weight: bold; }
    .student-list-panel table tr { cursor: pointer; }
    .filter-controls { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 12px; border: 1px solid var(--border); }
    .filter-controls .form-group { margin-bottom: 0; }
    .student-detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
    .student-balance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-bottom: 2rem; }
    .balance-card { background: #f8f9fa; padding: 20px; border-radius: 12px; text-align: center; border-top: 4px solid var(--secondary, #3498db); }
    .balance-card h4 { margin: 0 0 10px 0; color: #6c757d; font-size: 1rem; }
    .balance-amount { font-size: 1.75rem; font-weight: 700; }
    .balance-amount.balance-due { color: var(--danger, #e74c3c); }
    .badge.badge-active { background-color: var(--success); color: var(--white); }
    .badge.badge-inactive { background-color: #6c757d; color: var(--white); }
    .btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; text-decoration: none; color: #fff; background-color: #6c757d; border: none; }
    .btn-add { display: inline-flex; align-items: center; gap: 8px; }

    /* Accordion for Fee Structure */
    .accordion-header { background-color: #f8f9fa; padding: 1rem 1.5rem; border: 1px solid #e9ecef; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; font-size: 1.1rem; }
    .accordion-content { padding: 1.5rem; border: 1px solid #e9ecef; border-top: none; display: none; }
    .accordion-content h4 { margin-top: 0; color: var(--primary); }
    .total-fees { font-size: 0.9rem; color: #6c757d; font-weight: normal; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
    .class-checkbox-group { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
    .class-checkbox-group label { display: block; }

    /* Draggable table styles */
    .drag-handle { cursor: move; vertical-align: middle; color: #aaa; padding-right: 10px; }
    .sortable-ghost { background-color: #e3f2fd; opacity: 0.7; }
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
            <i class="fas fa-users text-2xl text-white"></i>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Customer Center</h1>
            <p class="text-gray-500 text-sm mt-1">Manage students, classes, invoices, payments, and communications.</p>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="border-b border-gray-200">
        <nav class="flex flex-wrap gap-1 p-2" aria-label="Tabs">
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'students')">
                <i class="fas fa-users"></i> Students
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'classes')">
                <i class="fas fa-school"></i> Classes
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'invoices')">
                <i class="fas fa-file-invoice"></i> Invoices
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'fee_structure')">
                <i class="fas fa-sitemap"></i> Fee Structure
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'templates')">
                <i class="fas fa-paste"></i> Templates
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'receive_payment')">
                <i class="fas fa-hand-holding-usd"></i> Payments
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'statements')">
                <i class="fas fa-file-alt"></i> Statements
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'receipts')">
                <i class="fas fa-receipt"></i> Receipts
            </button>
            <button class="tab-link flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-all" onclick="openTab(event, 'bulk_messaging')">
                <i class="fas fa-paper-plane"></i> Messaging
            </button>
        </nav>
    </div>

<div id="students" class="tab-content h-full bg-white p-6 min-h-screen font-sans text-zinc-950">
    
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">Students Directory</h2>
                <p class="text-sm text-zinc-500">Manage student enrollment, status, and class assignments.</p>
            </div>
            <button onclick="openModal('addStudentModal')" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 disabled:pointer-events-none disabled:opacity-50 bg-zinc-900 text-zinc-50 shadow hover:bg-zinc-900/90 h-9 px-4 py-2 gap-2">
                <i class="fas fa-plus text-xs"></i> Add New Student
            </button>
        </div>

        <div class="flex flex-col md:flex-row gap-3 items-center">
            <form method="get" class="flex flex-col md:flex-row w-full gap-3">
                <input type="hidden" name="tab" value="students">
                
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                    <input type="text" name="filter_name" value="<?= htmlspecialchars($filter_name) ?>" 
                           class="flex h-9 w-full rounded-md border border-zinc-200 bg-transparent px-9 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950" 
                           placeholder="Search students...">
                </div>

                <select name="filter_class_id" class="h-9 w-full md:w-[180px] rounded-md border border-zinc-200 bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-950">
                    <option value="">All Classes</option>
                    <?php foreach($all_classes_for_dropdown as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= ($filter_class_id == $class['id']) ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="filter_status" class="h-9 w-full md:w-[150px] rounded-md border border-zinc-200 bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-950">
                    <option value="active" <?= ($filter_status == 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filter_status == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    <option value="all" <?= ($filter_status == 'all') ? 'selected' : '' ?>>All Status</option>
                </select>

                <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-zinc-200 bg-white shadow-sm hover:bg-zinc-100 h-9 px-4 py-2 transition-colors">
                    Filter
                </button>
            </form>
        </div>

                        <div class="flex items-center justify-between px-4 py-3 bg-zinc-50/50 border-t border-zinc-200">
                    <div class="flex items-center gap-2">
                        <select name="bulk_action" class="h-8 w-[130px] rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-950">
                            <option value="">Bulk Actions</option>
                            <option value="delete">Deactivate</option>
                            <option value="activate">Activate</option>
                        </select>
                        <button type="submit" name="bulk_action_submit" class="h-8 px-3 rounded-md border border-zinc-200 bg-white text-xs font-medium hover:bg-zinc-100 transition-colors">
                            Apply
                        </button>
                    </div>
                    <p class="text-xs text-zinc-500"><?= count($students) ?> students found</p>
                </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <div class="rounded-md border border-zinc-200">
            <form id="bulk-student-form" method="post">
                <input type="hidden" name="active_tab" value="students">
                
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b bg-zinc-50/50">
                            <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                <th class="h-12 px-4 text-left align-middle font-medium text-zinc-500 w-10">
                                    <input type="checkbox" id="select-all-students" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-950">
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-zinc-500">Student Profile</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-zinc-500">Class</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-zinc-500">Status</th>
                                <th class="h-12 px-4 text-right align-middle font-medium text-zinc-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <?php foreach($students as $student): 
                                  $studentJson = htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr onclick="openViewModal(<?= $studentJson ?>)" class="border-b transition-colors hover:bg-zinc-50/50 cursor-pointer">
                                <td class="p-4 align-middle" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-950">
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[13px] font-medium text-zinc-900 border border-zinc-200">
                                            <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-medium leading-none mb-1"><?= htmlspecialchars($student['name']) ?></span>
                                            <span class="text-xs text-zinc-500 font-mono">ID: <?= htmlspecialchars($student['student_id_no'] ?: 'N/A') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle text-zinc-600">
                                    <?= htmlspecialchars($student['class_name'] ?? 'Unassigned') ?>
                                </td>
                                <td class="p-4 align-middle">
                                    <?php if($student['status'] === 'active'): ?>
                                        <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-emerald-100 text-emerald-800">
                                            Active
                                        </div>
                                    <?php else: ?>
                                        <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors border-transparent bg-zinc-100 text-zinc-600">
                                            Inactive
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 align-middle text-right" onclick="event.stopPropagation()">
                                    <button type="button" onclick="editStudent(<?= $studentJson ?>)" class="inline-flex items-center justify-center rounded-md h-8 w-8 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">
                                        <i class="fas fa-ellipsis-h text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>


            </form>
        </div>
    </div>
</div>

<div id="viewStudentModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-white/80 backdrop-blur-sm transition-opacity opacity-0" id="viewModalBackdrop"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg transform overflow-hidden rounded-lg border border-zinc-200 bg-white p-6 shadow-lg transition-all opacity-0 translate-y-4" id="viewModalPanel">
                
                <div class="flex flex-col space-y-1.5 text-center sm:text-left mb-6">
                    <h3 class="text-lg font-semibold leading-none tracking-tight" id="modal-name">Student Profile</h3>
                    <p class="text-sm text-zinc-500" id="modal-id">Student Details</p>
                </div>

                <div class="grid gap-6 py-4">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 rounded-full bg-zinc-100 border border-zinc-200 flex items-center justify-center text-xl font-semibold text-zinc-600" id="modal-avatar"></div>
                        <div>
                            <div id="modal-status-pill"></div>
                            <p class="text-sm text-zinc-500 mt-1" id="modal-class-display"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="space-y-3">
                            <p class="text-zinc-500 font-medium">Guardian</p>
                            <p class="text-zinc-900" id="modal-parent">N/A</p>
                        </div>
                        <div class="space-y-3">
                            <p class="text-zinc-500 font-medium">Phone</p>
                            <p class="text-zinc-900" id="modal-phone">N/A</p>
                        </div>
                        <div class="space-y-3">
                            <p class="text-zinc-500 font-medium">Email</p>
                            <p class="text-zinc-900 truncate" id="modal-email">N/A</p>
                        </div>
                        <div class="space-y-3">
                            <p class="text-zinc-500 font-medium">Enrollment Date</p>
                            <p class="text-zinc-900" id="modal-joined">N/A</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 mt-6">
                    <button onclick="closeViewModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-zinc-200 bg-white h-9 px-4 py-2 hover:bg-zinc-100">
                        Close
                    </button>
                    <button id="modal-edit-btn" class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-zinc-900 text-zinc-50 h-9 px-4 py-2 hover:bg-zinc-900/90 mb-2 sm:mb-0">
                        Edit Profile
                    </button>
                </div>

                <button onclick="closeViewModal()" class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2">
                    <i class="fas fa-times text-sm"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openViewModal(student) {
        const modal = document.getElementById('viewStudentModal');
        const backdrop = document.getElementById('viewModalBackdrop');
        const panel = document.getElementById('viewModalPanel');

        // Populate Data
        document.getElementById('modal-name').innerText = student.name;
        document.getElementById('modal-id').innerText = 'ID: ' + (student.student_id_no || 'N/A');
        document.getElementById('modal-class').innerText = student.class_name || 'Unassigned';
        document.getElementById('modal-avatar').innerText = student.name.charAt(0).toUpperCase();
        
// Status Badge styling for the new UI
const statusEl = document.getElementById('modal-status-pill');
const classEl = document.getElementById('modal-class-display');
classEl.innerText = student.class_name || 'Unassigned';

if (student.status === 'active') {
    statusEl.className = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-transparent bg-emerald-100 text-emerald-800';
    statusEl.innerText = 'Active';
} else {
    statusEl.className = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-transparent bg-zinc-100 text-zinc-600';
    statusEl.innerText = 'Inactive';
}

        // Placeholder data (Since your PHP loop didn't explicitly show these fields in the table, 
        // you might need to ensure they exist in the JSON object)
        document.getElementById('modal-parent').innerText = student.parent_name || 'N/A';
        document.getElementById('modal-phone').innerText = student.phone || 'N/A';
        document.getElementById('modal-email').innerText = student.email || 'N/A';
        document.getElementById('modal-joined').innerText = student.created_at ? new Date(student.created_at).toLocaleDateString() : 'N/A';

        // Configure Edit Button
        const editBtn = document.getElementById('modal-edit-btn');
        editBtn.onclick = function() {
            closeViewModal();
            editStudent(student); // Calls your existing edit function
        };

        // Show Modal with Animation
        modal.classList.remove('hidden');
        // Small timeout to allow browser to render hidden->block before adding opacity classes
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
        }, 10);
    }

    function closeViewModal() {
        const modal = document.getElementById('viewStudentModal');
        const backdrop = document.getElementById('viewModalBackdrop');
        const panel = document.getElementById('viewModalPanel');

        // Reverse Animation
        backdrop.classList.add('opacity-0');
        panel.classList.add('opacity-0', 'translate-y-4', 'scale-95');

        // Hide after animation finishes
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>

<div class="max-w-5xl mx-auto p-6 font-sans text-zinc-950">
    
    <div class="mb-8">
        <h2 class="text-3xl font-bold tracking-tight">Classes Management</h2>
        <p class="text-sm text-zinc-500 mt-1">Configure class structures, promotion paths, and financial reporting.</p>
    </div>

    <div class="inline-flex h-10 items-center justify-center rounded-md bg-zinc-100 p-1 text-zinc-500 mb-8">
        <button onclick="switchTab('class-directory')" id="nav-class-directory" 
            class="tab-trigger inline-flex items-center justify-center whitespace-nowrap rounded-sm px-4 py-1.5 text-sm font-medium transition-all bg-white text-zinc-950 shadow-sm">
            Class List
        </button>
        <button onclick="switchTab('class-finance')" id="nav-class-finance" 
            class="tab-trigger inline-flex items-center justify-center whitespace-nowrap rounded-sm px-4 py-1.5 text-sm font-medium transition-all hover:text-zinc-900">
            Finance & Dashboard
        </button>
        <button onclick="switchTab('class-ops')" id="nav-class-ops" 
            class="tab-trigger inline-flex items-center justify-center whitespace-nowrap rounded-sm px-4 py-1.5 text-sm font-medium transition-all hover:text-zinc-900">
            Add New Class
        </button>
    </div>

    <div id="tab-viewport">

        <div id="class-directory" class="tab-pane space-y-6">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold italic text-zinc-500">Class Directory</h3>
                <div class="flex items-center space-x-2 bg-zinc-100/50 p-1.5 px-3 rounded-md border border-zinc-200">
                    <input type="checkbox" id="show_archived" class="h-4 w-4 rounded border-zinc-300 text-zinc-900" 
                           onchange="window.location.href = this.checked ? '?tab=classes&show_archived=1' : '?tab=classes';">
                    <label for="show_archived" class="text-xs font-medium">Show Archived</label>
                </div>
            </div>

            <form action="customer_center.php" method="POST">
                <input type="hidden" name="active_tab" value="classes">
                <input type="hidden" name="update_classes" value="1">
                
                <div class="rounded-md border border-zinc-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50/50 border-b border-zinc-200">
                            <tr>
                                <th class="h-10 px-4 text-left font-medium text-zinc-500 w-[50px]">Order</th>
                                <th class="h-10 px-4 text-left font-medium text-zinc-500">Class Name</th>
                                <th class="h-10 px-4 text-left font-medium text-zinc-500">Promotion Path</th>
                                <th class="h-10 px-4 text-right font-medium text-zinc-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="class-sortable-list" class="divide-y divide-zinc-200">
                            <?php $order_index = 0; foreach ($classes as $class): ?>
                            <tr class="hover:bg-zinc-50/50 transition-colors">
                                <td class="p-4 drag-handle text-zinc-400"><i class="fas fa-grip-vertical"></i></td>
                                <td class="p-4">
                                    <input type="text" name="class_name[<?= $class['id'] ?>]" value="<?= htmlspecialchars($class['name']) ?>" 
                                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:ring-1 focus:ring-zinc-950">
                                    <input type="hidden" name="class_order[<?= $class['id'] ?>]" value="<?= $order_index++ ?>">
                                </td>
                                <td class="p-4">
                                    <select name="next_class_id[<?= $class['id'] ?>]" class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:ring-1 focus:ring-zinc-950">
                                        <option value="">-- Final Class --</option>
                                        <?php foreach ($all_classes_for_dropdown as $opt): if ($class['id'] != $opt['id']): ?>
                                            <option value="<?= $opt['id'] ?>" <?= ($class['next_class_id'] == $opt['id']) ? 'selected' : '' ?>><?= htmlspecialchars($opt['name']) ?></option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                </td>
                                <td class="p-4 text-right">
                                    <button type="submit" name="<?= $class['is_archived'] ? 'unarchive_class' : 'archive_class' ?>" class="text-zinc-400 hover:text-zinc-900">
                                        <i class="fas <?= $class['is_archived'] ? 'fa-box-open' : 'fa-archive' ?> text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="submit" class="bg-zinc-900 text-zinc-50 px-4 py-2 rounded-md text-sm font-medium hover:bg-zinc-900/90 shadow">Save List Changes</button>
                </div>
            </form>
        </div>

        <div id="class-finance" class="tab-pane hidden space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1 rounded-xl border border-zinc-200 p-6 space-y-4 shadow-sm">
                    <div>
                        <h3 class="font-semibold text-lg">Bulk Invoicing</h3>
                        <p class="text-xs text-zinc-500">Generate fee structures for entire classes.</p>
                    </div>
                    <form action="customer_center.php" method="POST" class="space-y-3">
                        <input type="hidden" name="generate_bulk_invoices" value="1">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase text-zinc-400">Target Class</label>
                            <select name="class_id" class="h-9 w-full rounded-md border border-zinc-200 text-sm">
                                <?php foreach ($all_classes_for_dropdown as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase text-zinc-400">Due Date</label>
                            <input type="date" name="due_date" class="h-9 w-full rounded-md border border-zinc-200 text-sm" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                        <button type="submit" class="w-full bg-zinc-900 text-zinc-50 h-9 rounded-md text-sm font-medium">Generate Invoices</button>
                    </form>
                </div>

                <div class="md:col-span-2 rounded-xl border border-zinc-200 bg-zinc-50/30 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-semibold text-lg">Class Analytics</h3>
                        <select id="class_dashboard_select" class="h-8 rounded-md border-zinc-200 text-xs" onchange="loadClassDashboard(this.value)">
                            <option value="">Select a Class...</option>
                            <?php foreach ($all_classes_for_dropdown as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="class_dashboard_content" class="h-48 flex items-center justify-center border border-dashed border-zinc-300 rounded-lg bg-white">
                        <p class="text-zinc-400 text-sm italic">Metrics will appear here after selection</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="class-ops" class="tab-pane hidden max-w-md mx-auto">
            <div class="rounded-xl border border-zinc-200 p-8 shadow-lg bg-white">
                <div class="mb-6 text-center">
                    <div class="w-12 h-12 bg-zinc-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-plus text-zinc-600"></i>
                    </div>
                    <h3 class="text-xl font-bold">Add New Class</h3>
                    <p class="text-sm text-zinc-500">Create a new academic group for enrollment.</p>
                </div>
                <form action="customer_center.php" method="POST" class="space-y-4">
                    <input type="hidden" name="add_class" value="1">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Class Name</label>
                        <input type="text" name="class_name" placeholder="e.g. Nursery B" required
                               class="h-10 w-full rounded-md border border-zinc-200 px-3 focus:ring-1 focus:ring-zinc-950">
                    </div>
                    <button type="submit" class="w-full bg-zinc-900 text-zinc-50 h-10 rounded-md font-medium shadow-sm hover:bg-zinc-800 transition-colors">
                        Register Class
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tabId) {
    const targetPane = document.getElementById(tabId);
    const targetButton = document.getElementById('nav-' + tabId);

    // 1. Safety Check: If the tab doesn't exist, stop the function
    if (!targetPane) {
        console.error(`Tab pane with id "${tabId}" not found.`);
        return;
    }

    // 2. Hide all tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.add('hidden');
    });

    // 3. Show the selected pane
    targetPane.classList.remove('hidden');

    // 4. Reset all navigation buttons
    document.querySelectorAll('.tab-trigger').forEach(btn => {
        btn.classList.remove('bg-white', 'text-zinc-950', 'shadow-sm');
        btn.classList.add('hover:text-zinc-900');
    });

    // 5. Apply active style to the button (if it exists)
    if (targetButton) {
        targetButton.classList.add('bg-white', 'text-zinc-950', 'shadow-sm');
        targetButton.classList.remove('hover:text-zinc-900');
    }
}
</script>

    <div id="invoices" class="tab-content">
        <div class="card">
            <h3>Invoices</h3>
            <div class="table-actions"><a href="create_invoice.php" class="btn-add"><i class="fas fa-plus"></i> Create Invoice</a></div>
            <form method="get" class="filter-controls">
                <input type="hidden" name="tab" value="invoices">
                <div class="form-group"><label>Student</label><select name="filter_invoice_student_id" class="form-control"><option value="">All Students</option><?php foreach($all_students_for_dropdown as $student): ?><option value="<?= $student['id'] ?>" <?= ($filter_invoice_student_id == $student['id']) ? 'selected' : '' ?>><?= htmlspecialchars($student['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Status</label><select name="filter_invoice_status" class="form-control"><option value="">All</option><option value="Paid" <?= ($filter_invoice_status == 'Paid') ? 'selected' : '' ?>>Paid</option><option value="Partially Paid" <?= ($filter_invoice_status == 'Partially Paid') ? 'selected' : '' ?>>Partially Paid</option><option value="Unpaid" <?= ($filter_invoice_status == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option><option value="Overdue" <?= ($filter_invoice_status == 'Overdue') ? 'selected' : '' ?>>Overdue</option></select></div>
                <div class="form-group"><label>From</label><input type="date" name="filter_invoice_start_date" value="<?= htmlspecialchars($filter_invoice_start_date) ?>" class="form-control"></div>
                <div class="form-group"><label>To</label><input type="date" name="filter_invoice_end_date" value="<?= htmlspecialchars($filter_invoice_end_date) ?>" class="form-control"></div>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
            <div class="table-container">
                <table>
                    <thead><tr><th>ID</th><th>Student</th><th>Date</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($invoices as $inv): ?>
                        <tr>
                            <td><?= htmlspecialchars($inv['id']) ?></td>
                            <td><?= htmlspecialchars($inv['student_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($inv['due_date'])) ?></td>
                            <td>$<?= number_format($inv['total_amount'] ?? 0, 2) ?></td>
                            <td><span class="status-badge status-<?= strtolower(str_replace(' ', '', $inv['status'])) ?>"><?= htmlspecialchars($inv['status']) ?></span></td>
                            <td><a href="view_invoice.php?id=<?= $inv['id'] ?>" class="btn-icon btn-view"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="fee_structure" class="tab-content">
        <div class="card">
            <h3>Fee Structure Management</h3>
            <p>Assign mandatory or optional fees to different classes for a specific academic period.</p>
            
            <form method="get" class="filter-controls">
                <input type="hidden" name="tab" value="fee_structure">
                <div class="form-group">
                    <label for="fs_year">Academic Year</label>
                    <input type="text" id="fs_year" name="fs_year" class="form-control" value="<?= htmlspecialchars($current_academic_year) ?>">
                </div>
                <div class="form-group">
                    <label for="fs_term">Term</label>
                    <select id="fs_term" name="fs_term" class="form-control">
                        <option value="Term 1" <?= $current_term == 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= $current_term == 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= $current_term == 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Load Structure</button>
                <button type="button" class="btn-add" onclick="openModal('assignFeeItemModal')"><i class="fas fa-plus"></i> Assign Fee Item</button>
                <button type="button" class="btn-secondary" onclick="openModal('createBaseItemModal')"><i class="fas fa-tag"></i> Create New Base Item</button>
            </form>

            <hr style="margin: 2rem 0;">
            <h4>Bulk Upload Tools</h4>
            <div class="form-grid">
                <form method="post" enctype="multipart/form-data" class="filter-controls">
                    <input type="hidden" name="active_tab" value="fee_structure">
                    <p><strong>Step 1: Upload Base Items</strong><br>CSV format: <strong>item_name, item_description</strong></p>
                    <div class="form-group">
                        <label for="base_items_csv">Base Items CSV File</label>
                        <input type="file" name="base_items_csv" id="base_items_csv" class="form-control" required accept=".csv">
                    </div>
                    <button type="submit" name="upload_base_items" class="btn-info"><i class="fas fa-upload"></i> Upload Base Items</button>
                </form>
                <form method="post" enctype="multipart/form-data" class="filter-controls">
                    <input type="hidden" name="active_tab" value="fee_structure">
                    <input type="hidden" name="academic_year_hidden" value="<?= htmlspecialchars($current_academic_year) ?>">
                    <input type="hidden" name="term_hidden" value="<?= htmlspecialchars($current_term) ?>">
                    <p><strong>Step 2: Upload Fee Structure</strong><br>CSV format: <strong>class_name, item_name, amount, is_mandatory (1 or 0)</strong></p>
                    <div class="form-group">
                        <label for="fee_structure_csv">Fee Structure CSV File</label>
                        <input type="file" name="fee_structure_csv" id="fee_structure_csv" class="form-control" required accept=".csv">
                    </div>
                    <button type="submit" name="upload_fee_structure" class="btn-success"><i class="fas fa-upload"></i> Upload & Save Structure</button>
                </form>
            </div>

            <div id="fee-structure-accordion" style="margin-top: 2rem;">
                <?php if (empty($fee_structure_by_class)): ?>
                    <p>No fee structure defined for the selected academic year and term.</p>
                <?php else: ?>
                    <?php foreach ($all_classes_for_dropdown as $class): 
                        if (!isset($fee_structure_by_class[$class['id']])) continue;
                        $class_fees = $fee_structure_by_class[$class['id']];
                        $total_mandatory_fee = 0;
                        foreach($class_fees['items'] as $item) {
                            if ($item['is_mandatory']) $total_mandatory_fee += $item['amount'];
                        }
                    ?>
                        <div class="accordion-item">
                            <button type="button" class="accordion-header">
                                <?= htmlspecialchars($class_fees['name']) ?>
                                <span class="total-fees">Mandatory Total: $<?= number_format($total_mandatory_fee, 2) ?></span>
                            </button>
                            <div class="accordion-content">
                                <h4>Mandatory Fees</h4>
                                <table class="table">
                                    <?php foreach ($class_fees['items'] as $item): if ($item['is_mandatory']): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td class="amount">$<?= number_format($item['amount'], 2) ?></td>
                                        <td>
                                            <button class="btn-icon btn-edit" title="Edit" onclick='openEditFeeModal(<?= json_encode($item) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Remove this fee from the class?');">
                                                <input type="hidden" name="active_tab" value="fee_structure"><input type="hidden" name="remove_fee_item" value="1"><input type="hidden" name="fee_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-icon btn-delete" title="Remove"><i class="fas fa-times"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endif; endforeach; ?>
                                </table>

                                <h4 style="margin-top: 1.5rem;">Optional Fees</h4>
                                <table class="table">
                                    <?php foreach ($class_fees['items'] as $item): if (!$item['is_mandatory']): ?>
                                     <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td class="amount">$<?= number_format($item['amount'], 2) ?></td>
                                        <td>
                                            <button class="btn-icon btn-edit" title="Edit" onclick='openEditFeeModal(<?= json_encode($item) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Remove this fee from the class?');">
                                                <input type="hidden" name="active_tab" value="fee_structure"><input type="hidden" name="remove_fee_item" value="1"><input type="hidden" name="fee_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-icon btn-delete" title="Remove"><i class="fas fa-times"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endif; endforeach; ?>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="templates" class="tab-content">
        <div class="card">
            <h3>Manage Invoice Templates</h3>
            <p>Edit or delete reusable templates for creating invoices quickly.</p>
            <div class="table-actions"><a href="create_invoice.php" class="btn-add"><i class="fas fa-plus"></i> Create New Template</a></div>
            <?php if (empty($invoice_templates)): ?>
                <p>No invoice templates have been saved yet. You can save one from the 'Create Invoice' page.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Linked Class</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice_templates as $template): ?>
                                <tr>
                                    <td><?= htmlspecialchars($template['name']); ?></td>
                                    <td>
                                        <?php if ($template['class_name']): ?>
                                            <span class="badge badge-success"><?= htmlspecialchars($template['class_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not Linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-edit" onclick='openEditTemplateModal(<?= htmlspecialchars(json_encode($template), ENT_QUOTES, "UTF-8") ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="customer_center.php" method="post" onsubmit="return confirm('Are you sure you want to delete this template?');" style="display:inline;">
                                                <input type="hidden" name="active_tab" value="templates">
                                                <input type="hidden" name="delete_template" value="1">
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
    </div>
    <div id="receive_payment" class="tab-content">
        <div class="card">
            <h3>Receive Payment</h3>
            <p>All payments recorded here will be added to "Undeposited Funds". You can group them into a bank deposit from the Banking page.</p>
            <form id="paymentForm" method="post">
                <input type="hidden" name="active_tab" value="receipts">
                <input type="hidden" name="process_payment" value="1">
                <div class="form-group"><label for="student_id_payment">Student</label><select name="student_id" id="student_id_payment" class="form-control" required onchange="loadUnpaidData()"><option value="">Select Student</option><?php foreach ($all_students_for_dropdown as $student): ?><option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option><?php endforeach; ?></select></div>
                
                <div class="form-group"><label for="payment_date">Payment Date</label><input type="date" name="payment_date" id="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label for="payment_method">Payment Method</label><select name="payment_method" id="payment_method" class="form-control" required><option>Cash</option><option>Bank Transfer</option><option>Mobile Money</option><option>Check</option></select></div>
                <div class="form-group"><label for="memo">Memo</label><textarea name="memo" id="memo" rows="2" class="form-control"></textarea></div>
                
                <h4>Unpaid Invoices</h4>
                <table id="unpaidInvoicesTable" class="table"><thead><tr><th>#</th><th>Date</th><th>Due</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th></tr></thead><tbody></tbody></table>
                
                <div style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-top: 1rem;">Total Payment: <span id="totalPayment"><?= format_currency(0) ?></span></div>
                <div class="form-actions"><button type="submit" class="btn-primary">Record Payment</button></div>
            </form>
        </div>
    </div>
    <div id="statements" class="tab-content">
        <div class="card">
            <h3>Create Statements</h3>
            <form id="statementForm" method="post" action="generate_statements.php" target="_blank">
                <div class="filter-controls">
                    <div class="form-group"><label for="statement_type">Generate For</label><select name="statement_type" id="statement_type" class="form-control" required><option value="all">All Students</option><option value="class">A Specific Class</option><option value="student">A Specific Student</option></select></div>
                    <div class="form-group" id="statement_class_selector" style="display: none;"><label for="statement_class_id">Select Class</label><select name="class_id" id="statement_class_id" class="form-control"><option value="">-- Select Class --</option><?php foreach ($all_classes_for_dropdown as $class): ?><option value="<?= $class['id']; ?>"><?= htmlspecialchars($class['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group" id="statement_student_selector" style="display: none;"><label for="statement_student_id">Select Student</label><select name="student_id" id="statement_student_id" class="form-control"><option value="">-- Select Student --</option><?php foreach ($all_students_for_dropdown as $student): ?><option value="<?= $student['id']; ?>"><?= htmlspecialchars($student['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="statement_date_from">Date From</label><input type="date" name="date_from" id="statement_date_from" class="form-control" required></div>
                    <div class="form-group"><label for="statement_date_to">Date To</label><input type="date" name="date_to" id="statement_date_to" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                    <button type="submit" class="btn-primary"><i class="fas fa-file-alt"></i> Generate Statements</button>
                </div>
            </form>
        </div>
    </div>
    <div id="receipts" class="tab-content">
        <div class="card">
            <h3>Payment Receipts</h3>
            <table>
                <thead><tr><th>Receipt #</th><th>Student</th><th>Date</th><th>Amount</th><th>Method</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($all_receipts as $receipt): ?>
                    <tr>
                        <td><?= htmlspecialchars($receipt['receipt_number']) ?></td>
                        <td><?= htmlspecialchars($receipt['student_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($receipt['payment_date'])) ?></td>
                        <td>$<?= number_format($receipt['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($receipt['payment_method']) ?></td>
                        <td><button class="btn-icon btn-view" onclick="viewReceipt(<?= $receipt['id'] ?>)"><i class="fas fa-eye"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="bulk_messaging" class="tab-content">
        <div class="card">
            <h3>Bulk Messaging</h3>
            <p>Select a recipient group, choose a template or write a custom message, and send. Placeholders [school_name], [student_name], [balance], and [link] will be replaced automatically.</p>
            <form id="bulkMessageForm" method="post">
                <input type="hidden" name="active_tab" value="bulk_messaging">
                <input type="hidden" name="send_bulk_message" value="1">
                <div class="form-group">
                    <label for="send_to_group">Send To</label>
                    <select name="send_to_group" id="send_to_group" class="form-control" required>
                        <option value="">-- Select Recipient Group --</option>
                        <optgroup label="Standard Messages">
                            <option value="class">Students in a Specific Class</option>
                            <option value="unpaid">Students with Unpaid Invoices</option>
                            <option value="all">All Active Students</option>
                        </optgroup>
                        <optgroup label="Messages with Links">
                            <option value="unpaid_invoices_link">Send Invoice Links to Students with Unpaid Balances</option>
                            <option value="class_statements_link">Send Statement Links to a Specific Class</option>
                            <option value="all_statements_link">Send Statement Links to All Active Students</option>
                        </optgroup>
                    </select>
                </div>

                <div id="class-messaging-container" class="form-group" style="display: none;">
                    <label for="class_id_messaging">Select Class</label>
                    <select name="class_id_messaging" id="class_id_messaging" class="form-control">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($all_classes_for_dropdown as $class): ?>
                            <option value="<?= $class['id']; ?>"><?= htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="statement-date-range-container" class="form-grid" style="display: none; margin-top: 1rem; gap: 1rem;">
                    <div class="form-group">
                        <label for="statement_link_date_from">Statement From</label>
                        <input type="date" name="statement_link_date_from" id="statement_link_date_from" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="form-group">
                        <label for="statement_link_date_to">Statement To</label>
                        <input type="date" name="statement_link_date_to" id="statement_link_date_to" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message_template_select">Message Templates (Optional)</label>
                    <select id="message_template_select" class="form-control">
                        <option value="">-- Select a pre-written message --</option>
                        <?php foreach ($message_templates as $title => $template_text): ?>
                            <option value="<?= htmlspecialchars($template_text) ?>"><?= htmlspecialchars($title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message_body">Message</label>
                    <textarea name="message_body" id="message_body" rows="6" class="form-control" required placeholder="Select a template or type your message here..."></textarea>
                    <small>You can use placeholders: [school_name], [student_name], [balance], and [link]. The [link] placeholder is only for link-based message groups.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Send Bulk Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- All Modals -->
<div id="addStudentModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity" onclick="closeModal('addStudentModal')"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-5xl my-8">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 z-20">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-user-plus text-blue-600"></i> Add New Student
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Fill in the details below. Student ID will be generated automatically.</p>
                    </div>
                    <button onclick="closeModal('addStudentModal')" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                        <i class="fas fa-times text-lg w-5 h-5 flex items-center justify-center"></i>
                    </button>
                </div>

                <form method="post" class="flex flex-col md:flex-row h-[75vh]">
                    <input type="hidden" name="active_tab" value="students">
                    <input type="hidden" name="addStudent" value="1">

                    <div class="w-full md:w-64 bg-gray-50 border-r border-gray-200 p-4 flex flex-col gap-2 overflow-y-auto">
                        <button type="button" onclick="switchTab('add', 'personal')" class="tab-btn-add active text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="personal">
                            <i class="fas fa-user w-5"></i> Personal Info
                        </button>
                        <button type="button" onclick="switchTab('add', 'parents')" class="tab-btn-add text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="parents">
                            <i class="fas fa-users w-5"></i> Parents/Guardian
                        </button>
                        <button type="button" onclick="switchTab('add', 'medical')" class="tab-btn-add text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="medical">
                            <i class="fas fa-heartbeat w-5"></i> Medical
                        </button>
                        <button type="button" onclick="switchTab('add', 'transport')" class="tab-btn-add text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="transport">
                            <i class="fas fa-bus w-5"></i> Transport
                        </button>
                        <button type="button" onclick="switchTab('add', 'other')" class="tab-btn-add text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="other">
                            <i class="fas fa-info-circle w-5"></i> Other
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 bg-white relative">
                        
                        <div id="add-personal" class="tab-content-add space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Basic Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="First Name">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input type="text" name="middle_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Middle Name">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Last Name">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">NEMIS No.</label>
                                    <input type="text" name="nemis_no" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="NEMIS">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                                    <select name="class_id" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Select Class</option>
                                        <?php foreach ($all_classes_for_dropdown as $class): ?>
                                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Gender</label>
                                    <select name="gender" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Select</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nationality</label>
                                    <input type="text" name="nationality" value="Kenyan" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Religion</label>
                                    <input type="text" name="religion" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div id="add-parents" class="tab-content-add hidden space-y-8">
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                <h4 class="text-sm font-bold text-blue-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <i class="fas fa-male"></i> Father's Information <span class="text-xs normal-case font-normal text-red-500 ml-2">(Required)</span>
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="father_first_name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Middle Name</label>
                                        <input type="text" name="father_middle_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="father_last_name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                        <input type="text" name="father_contact" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="+254...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                                        <input type="email" name="father_email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl border border-gray-200">
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <i class="fas fa-female"></i> Mother's Information
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">First Name</label>
                                        <input type="text" name="mother_first_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Middle Name</label>
                                        <input type="text" name="mother_middle_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Last Name</label>
                                        <input type="text" name="mother_last_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Phone Number</label>
                                        <input type="text" name="mother_contact" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="+254...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                                        <input type="email" name="mother_email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="add-medical" class="tab-content-add hidden space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Medical History</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Doctor's Name</label>
                                    <input type="text" name="doctor_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Doctor's Contact</label>
                                    <input type="text" name="doctor_contact" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Hospital</label>
                                    <input type="text" name="preferred_hospital" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Known Allergies</label>
                                    <textarea name="allergies" rows="3" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Long-term Conditions</label>
                                    <textarea name="long_term_condition" rows="3" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div id="add-transport" class="tab-content-add hidden space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Transport Logistics</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Transport Zone</label>
                                    <input type="text" name="transport_zone" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Trip Type</label>
                                    <select name="trip" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="none">No Transport</option>
                                        <option value="both">Two Way (Morning & Evening)</option>
                                        <option value="morning">Morning Only</option>
                                        <option value="evening">Evening Only</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Pickup / Drop-off Point</label>
                                    <input type="text" name="picking_point" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                         <div id="add-other" class="tab-content-add hidden space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Additional Info</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Sponsor Name</label>
                                    <input type="text" name="sponsor" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Sponsor Contact</label>
                                    <input type="text" name="sponsor_contact" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Dietary Requirements</label>
                                    <input type="text" name="food_preference" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="e.g. Vegetarian, Halal">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="bg-gray-50 border-t border-gray-200 p-4  justify-end gap-3 sticky bottom-0 z-20">
                        <button type="button" onclick="closeModal('addStudentModal')" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm transition-colors flex items-center gap-2">
                            <i class="fas fa-save"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="editStudentModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity" onclick="closeModal('editStudentModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-5xl my-8">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 z-20">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600"></i> Edit Student Profile
                        </h3>
                    </div>
                    <button onclick="closeModal('editStudentModal')" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                        <i class="fas fa-times text-lg w-5 h-5 flex items-center justify-center"></i>
                    </button>
                </div>

                <form id="editStudentForm" method="post" class="flex flex-col md:flex-row h-[75vh]">
                    <input type="hidden" name="active_tab" value="students">
                    <input type="hidden" name="editStudent" value="1">
                    <input type="hidden" name="student_id" id="edit_student_id">

                    <div class="w-full md:w-64 bg-gray-50 border-r border-gray-200 p-4 flex flex-col gap-2 overflow-y-auto">
                        <button type="button" onclick="switchTab('edit', 'personal')" class="tab-btn-edit active text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="personal">
                            <i class="fas fa-user w-5"></i> Personal Info
                        </button>
                        <button type="button" onclick="switchTab('edit', 'parents')" class="tab-btn-edit text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="parents">
                            <i class="fas fa-users w-5"></i> Parents
                        </button>
                        <button type="button" onclick="switchTab('edit', 'medical')" class="tab-btn-edit text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="medical">
                            <i class="fas fa-heartbeat w-5"></i> Medical
                        </button>
                        <button type="button" onclick="switchTab('edit', 'transport')" class="tab-btn-edit text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="transport">
                            <i class="fas fa-bus w-5"></i> Transport
                        </button>
                        <button type="button" onclick="switchTab('edit', 'other')" class="tab-btn-edit text-left px-4 py-3 rounded-lg text-sm font-medium transition-all flex items-center gap-3 hover:bg-white hover:shadow-sm" data-tab="other">
                            <i class="fas fa-info-circle w-5"></i> Other
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 bg-white relative">
                        
                        <div id="edit-personal" class="tab-content-edit space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Basic Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" id="edit_first_name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input type="text" name="middle_name" id="edit_middle_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" id="edit_last_name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Student ID (Read Only)</label>
                                    <input type="text" name="student_id_no" id="edit_student_id_no" readonly class="w-full rounded-lg border-gray-200 bg-gray-50 text-gray-500 sm:text-sm cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">NEMIS No.</label>
                                    <input type="text" name="nemis_no" id="edit_nemis_no" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Class</label>
                                    <select name="class_id" id="edit_class_id" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Select Class</option>
                                        <?php foreach ($all_classes_for_dropdown as $class): ?>
                                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="edit_status" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="edit-parents" class="tab-content-edit hidden space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Father's Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" name="father_first_name" id="edit_father_first_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" name="father_last_name" id="edit_father_last_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="text" name="father_contact" id="edit_father_contact" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                            
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2 mt-6">Mother's Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" name="mother_first_name" id="edit_mother_first_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" name="mother_last_name" id="edit_mother_last_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="text" name="mother_contact" id="edit_mother_contact" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div id="edit-medical" class="tab-content-edit hidden space-y-6">
                             <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Medical</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Doctor Name</label>
                                    <input type="text" name="doctor_name" id="edit_doctor_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Medical Conditions</label>
                                    <input type="text" name="long_term_condition" id="edit_long_term_condition" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                             </div>
                        </div>
                        
                        <div id="edit-transport" class="tab-content-edit hidden space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Transport</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Zone</label>
                                    <input type="text" name="transport_zone" id="edit_transport_zone" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                             </div>
                        </div>

                         <div id="edit-other" class="tab-content-edit hidden space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Other</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Sponsor</label>
                                    <input type="text" name="sponsor" id="edit_sponsor" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                             </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border-t border-gray-200 p-4 flex justify-end gap-3 sticky bottom-0 z-20">
                        <button type="button" onclick="closeModal('editStudentModal')" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm transition-colors flex items-center gap-2">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab Switching Logic
    function switchTab(modalType, tabName) {
        // 1. Hide all tab contents for this modal
        const contents = document.querySelectorAll(`.tab-content-${modalType}`);
        contents.forEach(content => content.classList.add('hidden'));

        // 2. Remove active state from all buttons
        const buttons = document.querySelectorAll(`.tab-btn-${modalType}`);
        buttons.forEach(btn => {
            btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-600', 'border-l-4', 'border-blue-600');
            btn.classList.add('hover:bg-white'); // reset hover
        });

        // 3. Show specific content
        document.getElementById(`${modalType}-${tabName}`).classList.remove('hidden');

        // 4. Add active state to clicked button
        const activeBtn = document.querySelector(`.tab-btn-${modalType}[data-tab="${tabName}"]`);
        if(activeBtn) {
            activeBtn.classList.add('bg-white', 'shadow-sm', 'text-blue-600', 'border-l-4', 'border-blue-600');
            activeBtn.classList.remove('hover:bg-white');
        }
    }

    // Initialize first tabs on load (optional, or call on modal open)
    document.addEventListener('DOMContentLoaded', () => {
        switchTab('add', 'personal');
        switchTab('edit', 'personal');
    });

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
    
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        // Reset tabs when opening
        if(id === 'addStudentModal') switchTab('add', 'personal');
        if(id === 'editStudentModal') switchTab('edit', 'personal');
    }
</script>


<div id="viewReceiptModal" class="modal"><div class="modal-content" style="max-width: 500px;"><div class="modal-header"><h3>Receipt Details</h3><span class="close" onclick="closeModal('viewReceiptModal')">&times;</span></div><div class="modal-body" id="receipt-details-body"><p>Loading...</p></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('viewReceiptModal')">Close</button><button type="button" class="btn-primary" onclick="printReceipt()">Print Receipt</button></div></div></div>
<div id="editTemplateModal" class="modal"><div class="modal-content" style="max-width: 900px;"><div class="modal-header"><h3>Edit Invoice Template</h3><span class="close" onclick="closeModal('editTemplateModal')">&times;</span></div><form id="editTemplateForm" method="post" onsubmit="prepareTemplateUpdate()"><input type="hidden" name="active_tab" value="templates"><input type="hidden" name="update_template" value="1"><input type="hidden" name="template_id" id="edit_template_id"><input type="hidden" name="template_items_json" id="edit_template_items_json"><div class="modal-body"><div class="form-group"><label for="edit_template_name">Template Name</label><input type="text" id="edit_template_name" name="template_name" class="form-control" required></div><div class="form-group"><label for="edit_template_class_id">Link to Class</label><select name="class_id" id="edit_template_class_id" class="form-control"><option value="">-- No Link --</option><?php foreach ($all_classes_for_dropdown as $class): ?><option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option><?php endforeach; ?></select></div><h4>Template Items</h4><table class="items-table"><thead><tr><th style="width: 40%;">Item</th><th style="width: 15%;">Qty</th><th style="width: 20%;">Rate</th><th style="width: 20%; text-align: right;">Amount</th><th></th></tr></thead><tbody id="edit-template-items-container"></tbody></table><button type="button" class="btn-secondary btn-add-item" onclick="addTemplateItem()">+ Add line</button></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('editTemplateModal')">Cancel</button><button type="submit" class="btn-primary">Update Template</button></div></form></div></div>
<div id="addPromiseModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Record a Payment Promise</h3><span class="close" onclick="closeModal('addPromiseModal')">&times;</span></div><form method="post"><input type="hidden" name="active_tab" value="students"><input type="hidden" name="add_promise" value="1"><input type="hidden" name="promise_student_id" id="promise_student_id"><input type="hidden" name="promise_invoice_id" id="promise_invoice_id"><div class="modal-body"><p>For Invoice #<strong id="promise_invoice_id_display"></strong></p><div class="form-group"><label for="promised_amount">Promised Amount</label><input type="number" name="promised_amount" id="promised_amount" step="0.01" required class="form-control"></div><div class="form-group"><label for="promised_due_date">Promised Payment Date</label><input type="date" name="promised_due_date" id="promised_due_date" required class="form-control"></div><div class="form-group"><label for="promise_date">Date of Promise</label><input type="date" name="promise_date" id="promise_date" value="<?= date('Y-m-d') ?>" required class="form-control"></div><div class="form-group"><label for="notes">Notes</label><textarea name="notes" id="notes" rows="3" class="form-control" placeholder="e.g., Spoke with parent on the phone."></textarea></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('addPromiseModal')">Cancel</button><button type="submit" class="btn-primary">Save Promise</button></div></form></div></div>

<div id="assignFeeItemModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Assign Fee Item to Class(es)</h3><span class="close" onclick="closeModal('assignFeeItemModal')">&times;</span></div><form method="post"><input type="hidden" name="active_tab" value="fee_structure"><input type="hidden" name="assign_fee_item" value="1"><input type="hidden" name="academic_year" value="<?= htmlspecialchars($current_academic_year) ?>"><input type="hidden" name="term" value="<?= htmlspecialchars($current_term) ?>"><div class="modal-body"><div class="form-grid"><div class="form-group"><label>Academic Year</label><input type="text" value="<?= htmlspecialchars($current_academic_year) ?>" class="form-control" readonly></div><div class="form-group"><label>Term</label><input type="text" value="<?= htmlspecialchars($current_term) ?>" class="form-control" readonly></div></div><div class="form-group"><label for="assign_item_id">Fee Item</label><select name="item_id" id="assign_item_id" required class="form-control"><option value="">-- Select Base Item --</option><?php foreach($base_items as $item): ?><option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="assign_amount">Amount</label><input type="number" name="amount" id="assign_amount" step="0.01" required class="form-control"></div><div class="form-group"><label for="assign_class_ids">Assign to Classes</label><div class="class-checkbox-group"><?php foreach($all_classes_for_dropdown as $class): ?><label><input type="checkbox" name="class_ids[]" value="<?= $class['id'] ?>"> <?= htmlspecialchars($class['name']) ?></label><?php endforeach; ?></div></div><div class="form-group"><label><input type="checkbox" name="is_mandatory" value="1" checked> This fee is mandatory</label></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('assignFeeItemModal')">Cancel</button><button type="submit" class="btn-primary">Assign to Class(es)</button></div></form></div></div>
<div id="editFeeItemModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Edit Assigned Fee</h3><span class="close" onclick="closeModal('editFeeItemModal')">&times;</span></div><form id="editFeeForm" method="post"><input type="hidden" name="active_tab" value="fee_structure"><input type="hidden" name="update_fee_item" value="1"><input type="hidden" name="fee_id" id="edit_fee_id"><div class="modal-body"><p><strong>Class:</strong> <span id="edit_fee_class_name"></span></p><p><strong>Item:</strong> <span id="edit_fee_item_name"></span></p><div class="form-group"><label for="edit_fee_amount">Amount</label><input type="number" name="amount" id="edit_fee_amount" step="0.01" required class="form-control"></div><div class="form-group"><label><input type="checkbox" name="is_mandatory" id="edit_fee_is_mandatory" value="1"> This fee is mandatory</label></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('editFeeItemModal')">Cancel</button><button type="submit" class="btn-primary">Update Fee</button></div></form></div></div>
<div id="createBaseItemModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Create New Base Item</h3><span class="close" onclick="closeModal('createBaseItemModal')">&times;</span></div><form method="post"><input type="hidden" name="active_tab" value="fee_structure"><input type="hidden" name="create_base_item" value="1"><div class="modal-body"><p>Create a generic fee item (e.g., "Tuition Fee", "Transport - Zone C"). You can assign prices to it for different classes later.</p><div class="form-group"><label for="item_name">Item Name</label><input type="text" name="item_name" id="item_name" required class="form-control"></div><div class="form-group"><label for="item_description">Description</label><textarea name="item_description" id="item_description" rows="2" class="form-control"></textarea></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('createBaseItemModal')">Cancel</button><button type="submit" class="btn-primary">Create Item</button></div></form></div></div>

<div id="sendMessageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="sendMessageModalTitle">Send Message</h3>
            <span class="close" onclick="closeModal('sendMessageModal')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="active_tab" value="students">
            <input type="hidden" name="send_single_message" value="1">
            <input type="hidden" name="student_id" id="single_message_student_id">
            <input type="hidden" name="phone_number" id="single_message_phone_number">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="single_message_template_select">Message Templates (Optional)</label>
                    <select id="single_message_template_select" class="form-control" onchange="document.getElementById('single_message_body').value = this.value;">
                        <option value="">-- Select a pre-written message --</option>
                        <?php foreach ($message_templates as $title => $template_text): ?>
                            <option value="<?= htmlspecialchars($template_text) ?>"><?= htmlspecialchars($title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="single_message_body">Message</label>
                    <textarea name="message_body" id="single_message_body" rows="6" class="form-control" required placeholder="Type your message here..."></textarea>
                    <small>You can use placeholders: [school_name], [student_name], [balance].</small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('sendMessageModal')">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Send Message</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<?php include 'footer.php'; ?>

<script>
// --- Currency Helper Function ---
function formatCurrencyJS(amount) {
    const symbol = '<?= $_SESSION['currency_symbol'] ?? '$' ?>';
    return symbol + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// --- Core UI Functions ---
function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
function openTab(evt, tabName) {
    document.querySelectorAll(".tab-content").forEach(tc => tc.style.display = "none");
    document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove("active"));
    document.getElementById(tabName).style.display = "block";
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add("active");
    }
    if (history.pushState) {
        let newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + tabName;
        window.history.pushState({path:newurl},'',newurl);
    }
}

// --- Student Management ---
function viewStudentDetails(studentId, rowElement) {
    document.getElementById('student-detail-placeholder').style.display = 'flex';
    document.getElementById('student-detail-content').style.display = 'none';
    document.querySelectorAll('.student-list-panel tr').forEach(row => row.classList.remove('active'));
    if (rowElement) rowElement.classList.add('active');
    fetch(`get_student_details.php?id=${studentId}`).then(response => response.json()).then(data => {
        if (data.success) {
            const contentDiv = document.getElementById('student-detail-content');
            let historyRows = '';
            if(data.history.length > 0) {
                data.history.forEach(item => {
                    let rowHtml = '<tr>';
                    const itemDate = new Date(item.date).toLocaleDateString();
                    switch(item.type) {
                        case 'invoice':
                            const balance = parseFloat(item.data.total_amount) - parseFloat(item.data.amount_paid);
                            rowHtml += `<td class="transaction-icon-cell"><i class="fas fa-file-invoice transaction-icon icon-invoice"></i></td><td><p class="transaction-date">${itemDate}</p></td><td><p class="transaction-title">Invoice #${item.data.id} Generated</p><p class="transaction-meta">Due: ${new Date(item.data.due_date).toLocaleDateString()} | Status: ${item.data.status}</p></td><td class="transaction-amount amount-debit">${formatCurrencyJS(item.data.total_amount)}</td><td class="action-buttons"><a href="view_invoice.php?id=${item.data.id}" class="btn-icon btn-view" title="View Invoice"><i class="fas fa-eye"></i></a><button class="btn-icon btn-add" title="Add Promise" onclick="openPromiseModal(${item.data.id}, ${studentId}, ${balance.toFixed(2)})"><i class="fas fa-handshake"></i></button></td>`;
                            break;
                        case 'payment':
                            rowHtml += `<td class="transaction-icon-cell"><i class="fas fa-check-circle transaction-icon icon-payment"></i></td><td><p class="transaction-date">${itemDate}</p></td><td><p class="transaction-title">Payment Received</p><p class="transaction-meta">Receipt #${item.data.receipt_number || 'N/A'} | Method: ${item.data.payment_method}</p></td><td class="transaction-amount amount-credit">-${formatCurrencyJS(item.data.amount)}</td><td class="action-buttons">${item.data.receipt_id ? `<button class="btn-icon btn-view" title="View Receipt" onclick="viewReceipt(${item.data.receipt_id})"><i class="fas fa-receipt"></i></button>` : ''}</td>`;
                            break;
                        case 'promise':
                             rowHtml += `<td class="transaction-icon-cell"><i class="fas fa-handshake transaction-icon icon-promise"></i></td><td><p class="transaction-date">${itemDate}</p></td><td><p class="transaction-title">Payment Promise Made</p><p class="transaction-meta">Promised ${formatCurrencyJS(item.data.promised_amount)} for Invoice #${item.data.invoice_id} by ${new Date(item.data.promised_due_date).toLocaleDateString()}</p></td><td class="transaction-amount"></td><td class="action-buttons"></td>`;
                            break;
                    }
                    rowHtml += '</tr>';
                    historyRows += rowHtml;
                });
            } else { historyRows = '<tr><td colspan="5" class="text-center">No financial history found.</td></tr>'; }
            let balanceClass = data.summary.balance > 0.01 ? 'balance-due' : (data.summary.balance < -0.01 ? 'balance-credit' : 'balance-zero');
            const formattedBalance = Math.abs(data.summary.balance).toFixed(2);
            const balanceSign = data.summary.balance < 0 ? '-' : '';
            contentDiv.innerHTML = `<div class="student-detail-header"><div><h3>${data.student.name}</h3><p>ID: ${data.student.student_id_no || 'N/A'} | Status: <span class="badge badge-${data.student.status}">${data.student.status}</span></p></div><div class="action-buttons"><a href="create_invoice.php?student_id=${studentId}" class="btn btn-primary"><i class="fas fa-plus"></i> New Invoice</a><a href="#receive_payment" onclick="preparePaymentForStudent(${studentId}, '${data.student.name}')" class="btn btn-success"><i class="fas fa-hand-holding-usd"></i> Receive Payment</a><button onclick="openSendMessageModal(${data.student.id}, '${data.student.name}', '${data.student.phone || ''}')" class="btn btn-info"><i class="fas fa-paper-plane"></i> Send Message</button></div></div><div class="student-balance-summary"><div class="balance-card"><h4>Current Balance</h4><span class="balance-amount ${balanceClass}">${balanceSign}${formatCurrencyJS(Math.abs(data.summary.balance))}</span></div><div class="balance-card"><h4>Total Invoiced</h4><span class="balance-amount">${formatCurrencyJS(data.summary.totalInvoiced)}</span></div><div class="balance-card"><h4>Total Paid</h4><span class="balance-amount">${formatCurrencyJS(data.summary.totalPaid)}</span></div></div><h3>Transaction History</h3><div class="table-container"><table class="transaction-history-table"><thead><tr><th></th><th>Date</th><th>Details</th><th class="amount-header">Amount</th><th>Actions</th></tr></thead><tbody>${historyRows}</tbody></table></div>`;
            document.getElementById('student-detail-placeholder').style.display = 'none';
            contentDiv.style.display = 'block';
        } else { alert('Error fetching student details.'); }
    });
}
function preparePaymentForStudent(studentId, studentName) {
    document.querySelector('.tab-link[onclick*="receive_payment"]').click();
    const studentSelect = document.getElementById('student_id_payment');
    studentSelect.value = studentId;
    studentSelect.dispatchEvent(new Event('change'));
}
// --- Student Form Tab Switching ---
function openStudentFormTab(evt, formType, tabName) {
    // Hide all sections for this form type
    const sections = document.querySelectorAll(`#${formType}StudentModal .student-form-section`);
    sections.forEach(section => section.classList.remove('active'));

    // Remove active class from all tab buttons
    const tabBtns = document.querySelectorAll(`#${formType}StudentModal .student-tab-btn`);
    tabBtns.forEach(btn => btn.classList.remove('active'));

    // Show the selected section
    const targetSection = document.getElementById(`${formType}-${tabName}`);
    if (targetSection) {
        targetSection.classList.add('active');
    }

    // Mark the clicked button as active
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    }
}

function editStudent(student) {
    // Reset tabs to show Personal first
    openStudentFormTab(null, 'edit', 'personal');
    document.querySelector('#editStudentModal .student-tab-btn').classList.add('active');

    // Basic Info
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_first_name').value = student.first_name || '';
    document.getElementById('edit_middle_name').value = student.middle_name || '';
    document.getElementById('edit_last_name').value = student.last_name || '';
    document.getElementById('edit_student_id_no').value = student.student_id_no || '';
    document.getElementById('edit_nemis_no').value = student.nemis_no || '';
    document.getElementById('edit_class_id').value = student.class_id || '';
    document.getElementById('edit_gender').value = student.gender || '';
    document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
    document.getElementById('edit_birth_cert_no').value = student.birth_cert_no || '';
    document.getElementById('edit_nationality').value = student.nationality || '';
    document.getElementById('edit_religion').value = student.religion || '';
    document.getElementById('edit_status').value = student.status || 'active';
    document.getElementById('edit_photo_url').value = student.photo_url || '';

    // Address Info
    document.getElementById('edit_residential_address').value = student.residential_address || '';
    document.getElementById('edit_postal_address').value = student.postal_address || '';
    document.getElementById('edit_email').value = student.email || '';
    document.getElementById('edit_phone').value = student.phone || '';

    // Father's Info
    document.getElementById('edit_father_first_name').value = student.father_first_name || '';
    document.getElementById('edit_father_middle_name').value = student.father_middle_name || '';
    document.getElementById('edit_father_last_name').value = student.father_last_name || '';
    document.getElementById('edit_father_contact').value = student.father_contact || '';
    document.getElementById('edit_father_email').value = student.father_email || '';

    // Mother's Info
    document.getElementById('edit_mother_first_name').value = student.mother_first_name || '';
    document.getElementById('edit_mother_middle_name').value = student.mother_middle_name || '';
    document.getElementById('edit_mother_last_name').value = student.mother_last_name || '';
    document.getElementById('edit_mother_contact').value = student.mother_contact || '';
    document.getElementById('edit_mother_email').value = student.mother_email || '';

    // Guardian's Info
    document.getElementById('edit_guardian_first_name').value = student.guardian_first_name || '';
    document.getElementById('edit_guardian_middle_name').value = student.guardian_middle_name || '';
    document.getElementById('edit_guardian_last_name').value = student.guardian_last_name || '';
    document.getElementById('edit_guardian_contact').value = student.guardian_contact || '';
    document.getElementById('edit_guardian_email').value = student.guardian_email || '';

    // Medical Info
    document.getElementById('edit_doctor_name').value = student.doctor_name || '';
    document.getElementById('edit_doctor_contact').value = student.doctor_contact || '';
    document.getElementById('edit_doctor_email').value = student.doctor_email || '';
    document.getElementById('edit_preferred_hospital').value = student.preferred_hospital || '';
    document.getElementById('edit_health_insurance_provider').value = student.health_insurance_provider || '';
    document.getElementById('edit_allergies').value = student.allergies || '';
    document.getElementById('edit_long_term_condition').value = student.long_term_condition || '';

    // Transport Info
    document.getElementById('edit_transport_zone').value = student.transport_zone || '';
    document.getElementById('edit_trip').value = student.trip || '';
    document.getElementById('edit_picking_point').value = student.picking_point || '';

    // Other Info
    document.getElementById('edit_sponsor').value = student.sponsor || '';
    document.getElementById('edit_sponsor_contact').value = student.sponsor_contact || '';
    document.getElementById('edit_sponsor_email').value = student.sponsor_email || '';
    document.getElementById('edit_food_preference').value = student.food_preference || '';

    openModal('editStudentModal');
}
function openPromiseModal(invoiceId, studentId, balance) {
    document.getElementById('promise_invoice_id').value = invoiceId;
    document.getElementById('promise_student_id').value = studentId;
    document.getElementById('promise_invoice_id_display').textContent = invoiceId;
    document.getElementById('promised_amount').value = balance;
    document.getElementById('promised_amount').max = balance;
    openModal('addPromiseModal');
}
function openSendMessageModal(studentId, studentName, phoneNumber) {
    if (!phoneNumber) {
        alert('This student does not have a phone number on record.');
        return;
    }
    document.getElementById('sendMessageModalTitle').innerText = 'Send Message to ' + studentName;
    document.getElementById('single_message_student_id').value = studentId;
    document.getElementById('single_message_phone_number').value = phoneNumber;
    document.getElementById('single_message_body').value = ''; // Clear previous message
    document.getElementById('single_message_template_select').value = ''; // Reset template dropdown
    openModal('sendMessageModal');
}

// --- Fee Structure & Template Management ---
function openEditFeeModal(feeData) {
    document.getElementById('edit_fee_id').value = feeData.id;
    document.getElementById('edit_fee_class_name').textContent = feeData.class_name;
    document.getElementById('edit_fee_item_name').textContent = feeData.item_name;
    document.getElementById('edit_fee_amount').value = parseFloat(feeData.amount).toFixed(2);
    document.getElementById('edit_fee_is_mandatory').checked = (feeData.is_mandatory == 1);
    openModal('editFeeItemModal');
}
function openEditTemplateModal(template) {
    document.getElementById('edit_template_id').value = template.id;
    document.getElementById('edit_template_name').value = template.name;
    document.getElementById('edit_template_class_id').value = template.class_id || '';
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
        if (this.value && selectedOption.dataset.price) newRow.querySelector('.unit-price').value = selectedOption.dataset.price;
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
    row.querySelector('.amount-cell').textContent = formatCurrencyJS(quantity * unitPrice);
}
function prepareTemplateUpdate() {
    const items = [];
    document.querySelectorAll('#edit-template-items-container tr').forEach(row => {
        const item_id = row.querySelector('.item-select').value;
        const quantity = row.querySelector('.quantity').value;
        const unit_price = row.querySelector('.unit-price').value;
        if (item_id && quantity > 0) items.push({ item_id, quantity, unit_price });
    });
    document.getElementById('edit_template_items_json').value = JSON.stringify(items);
}

// --- Payment & Receipt Functions ---
function loadUnpaidData() { loadUnpaidInvoices(document.getElementById('student_id_payment').value); }
function loadUnpaidInvoices(studentId) {
    const tbody = document.querySelector('#unpaidInvoicesTable tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    if (!studentId) { tbody.innerHTML = '<tr><td colspan="7" class="text-center">Please select a student.</td></tr>'; return; }
    fetch(`get_unpaid_invoices.php?student_id=${studentId}`).then(response => response.json()).then(data => {
        tbody.innerHTML = '';
        if (data.success && data.data.length > 0) {
            data.data.forEach(invoice => {
                tbody.innerHTML += `<tr><td>${invoice.id}</td><td>${new Date(invoice.invoice_date).toLocaleDateString()}</td><td>${new Date(invoice.due_date).toLocaleDateString()}</td><td>${formatCurrencyJS(invoice.total_amount)}</td><td>${formatCurrencyJS(invoice.amount_paid)}</td><td>${formatCurrencyJS(invoice.balance)}</td><td><input type="hidden" name="invoice_ids[]" value="${invoice.id}"><input type="number" name="payment_amounts[]" class="form-control payment-amount" min="0" step="0.01" value="0" oninput="calculateTotal()"></td></tr>`;
            });
        } else { tbody.innerHTML = '<tr><td colspan="7" class="text-center">No unpaid invoices.</td></tr>'; }
        calculateTotal();
    });
}
function calculateTotal() {
    const total = Array.from(document.querySelectorAll('.payment-amount')).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    document.getElementById('totalPayment').textContent = formatCurrencyJS(total);
}
function viewReceipt(receiptId) {
    const modalBody = document.getElementById('receipt-details-body');
    modalBody.innerHTML = '<p style="text-align:center;">Loading receipt...</p>';
    openModal('viewReceiptModal');
    fetch(`get_receipt.php?id=${receiptId}`).then(response => response.json()).then(data => {
        if (data.success) {
            const r = data.receipt;
            modalBody.innerHTML = `<div id="receipt-printable-area"><div style="text-align: center; margin-bottom: 20px;">${r.school_logo_url ? `<img src="${r.school_logo_url}" alt="Logo" style="max-width: 120px; max-height: 60px;"><br>` : ''}<h3 style="margin: 10px 0 0 0;">${r.school_name}</h3><p style="margin: 5px 0; font-size: 0.9em; color: #555;">${r.school_address || ''}</p></div><hr><h4 style="text-align: center; margin-top: 20px;">PAYMENT RECEIPT</h4><p><strong>Receipt #:</strong> ${r.receipt_number}</p><p><strong>Student:</strong> ${r.student_name}</p><p><strong>Date:</strong> ${new Date(r.payment_date).toLocaleDateString()}</p><h3 style="margin-top: 20px; color: var(--success);">Amount Paid: ${formatCurrencyJS(r.amount)}</h3><p><strong>Method:</strong> ${r.payment_method}</p><p><strong>Memo:</strong> ${r.memo || 'N/A'}</p></div>`;
        } else { modalBody.innerHTML = `<p class="alert alert-danger">Could not load receipt details.</p>`; }
    });
}
function printReceipt() { document.body.classList.add('receipt-modal-active'); window.print(); document.body.classList.remove('receipt-modal-active'); }

// --- Class Dashboard Functions ---
function loadClassDashboard(classId) {
    const dashboardContent = document.getElementById('class_dashboard_content');
    if (!classId) {
        dashboardContent.innerHTML = '<p>Select a class to view its financial summary and student details.</p>';
        return;
    }
    dashboardContent.innerHTML = '<p style="text-align:center;">Loading class data...</p>';
    fetch(`get_class_details.php?class_id=${classId}`).then(response => response.json()).then(data => {
        if (data.success) {
            const classData = data.data;
            dashboardContent.innerHTML = `<div class="student-balance-summary" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));"><div class="balance-card"><h4>Total Invoiced</h4><span class="balance-amount">${formatCurrencyJS(classData.summary.totalInvoiced)}</span></div><div class="balance-card"><h4>Total Paid</h4><span class="balance-amount" style="color: var(--success);">${formatCurrencyJS(classData.summary.totalPaid)}</span></div><div class="balance-card"><h4>Outstanding Balance</h4><span class="balance-amount balance-due">${formatCurrencyJS(classData.summary.balance)}</span></div></div><h5>Students in ${classData.name}</h5><div class="table-container"><table><thead><tr><th>Name</th><th>Student ID</th><th>Outstanding Balance</th><th>Actions</th></tr></thead><tbody>${classData.students.map(student => `<tr><td>${student.name}</td><td>${student.student_id_no || 'N/A'}</td><td class="${student.balance > 0 ? 'text-danger' : 'text-success'} font-weight-bold">${formatCurrencyJS(student.balance)}</td><td><button onclick="viewStudentDetails(${student.id}, null)" class="btn-icon btn-view" title="View Student Details"><i class="fas fa-eye"></i></button></td></tr>`).join('')}</tbody></table></div>`;
        } else { dashboardContent.innerHTML = `<p class="alert alert-danger">Error loading class details: ${data.error || 'Unknown error'}</p>`; }
    }).catch(error => { console.error('Fetch Error:', error); dashboardContent.innerHTML = '<p class="alert alert-danger">A network or server error occurred.</p>'; });
}

// --- Event Listeners & Initializers ---
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'students';
    const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
    if (tabButton) tabButton.click(); else document.querySelector('.tab-link').click();

    document.getElementById('select-all-students')?.addEventListener('change', function() { document.querySelectorAll('.student-checkbox').forEach(checkbox => checkbox.checked = this.checked); });
    const statementTypeSelect = document.getElementById('statement_type');
    if(statementTypeSelect) {
        statementTypeSelect.addEventListener('change', function() {
            document.getElementById('statement_class_selector').style.display = this.value === 'class' ? 'block' : 'none';
            document.getElementById('statement_student_selector').style.display = this.value === 'student' ? 'block' : 'none';
        });
        statementTypeSelect.dispatchEvent(new Event('change'));
    }
    
    // Resizable panel
    const resizer = document.getElementById('resizer'), leftPanel = document.getElementById('left-panel'), container = document.getElementById('resizable-container');
    let isResizing = false;
    if(resizer) {
        resizer.addEventListener('mousedown', e => { e.preventDefault(); isResizing = true; window.addEventListener('mousemove', handleMouseMove); window.addEventListener('mouseup', stopResizing); });
        function handleMouseMove(e) { if (!isResizing) return; let newLeftWidth = e.clientX - container.getBoundingClientRect().left; if (newLeftWidth < 350) newLeftWidth = 350; if (newLeftWidth > (container.clientWidth - 400)) newLeftWidth = container.clientWidth - 400; leftPanel.style.width = newLeftWidth + 'px'; }
        function stopResizing() { isResizing = false; window.removeEventListener('mousemove', handleMouseMove); window.removeEventListener('mouseup', stopResizing); }
    }

    const showArchivedCheckbox = document.getElementById('show_archived');
    if (new URLSearchParams(window.location.search).get('show_archived') === '1') showArchivedCheckbox.checked = true;

    // Accordion for Fee Structure
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            header.classList.toggle('active');
            content.style.display = content.style.display === 'block' ? 'none' : 'block';
        });
    });

    const sendToGroup = document.getElementById('send_to_group');
    if (sendToGroup) {
        sendToGroup.addEventListener('change', function() {
            const classContainer = document.getElementById('class-messaging-container');
            const dateContainer = document.getElementById('statement-date-range-container');
            const messageBody = document.getElementById('message_body');
            const templateSelect = document.getElementById('message_template_select');
            const selectedValue = this.value;

            // Show/hide Class selector
            classContainer.style.display = (selectedValue === 'class' || selectedValue === 'class_statements_link') ? 'block' : 'none';
            
            // Show/hide Date Range selector for statements
            dateContainer.style.display = (selectedValue === 'class_statements_link' || selectedValue === 'all_statements_link') ? 'grid' : 'none';

            // Suggest a message template when a link option is chosen
            if (selectedValue === 'unpaid_invoices_link') {
                messageBody.value = "Dear Parent, kindly find the invoice for [student_name] here: [link]. Your current balance is [balance]. Thank you, [school_name].";
                templateSelect.value = ''; // Reset template dropdown
            } else if (selectedValue.includes('statements_link')) {
                messageBody.value = "Dear Parent, you can view the financial statement for [student_name] here: [link]. Thank you, [school_name].";
                templateSelect.value = ''; // Reset template dropdown
            }
        });
        // Trigger the change event on page load to set the initial state
        sendToGroup.dispatchEvent(new Event('change')); 
    }

    // Ensure the message template select still works
    const templateSelect = document.getElementById('message_template_select');
    const messageBody = document.getElementById('message_body');
    if (templateSelect && messageBody) {
        templateSelect.addEventListener('change', function() {
            if (this.value) { // Only update if a template is selected
                messageBody.value = this.value;
            }
        });
    }

    // Initialize SortableJS for the classes table
    const classList = document.getElementById('class-sortable-list');
    if (classList) {
        new Sortable(classList, {
            animation: 150,
            handle: '.drag-handle', // Restrict dragging to the handle
            ghostClass: 'sortable-ghost', // Class for the drop placeholder
            onEnd: function () {
                // After dragging ends, update the hidden order inputs
                const rows = classList.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    const classId = row.getAttribute('data-class-id');
                    if (classId) {
                        const orderInput = row.querySelector(`input[name='class_order[${classId}]']`);
                        if (orderInput) {
                            orderInput.value = index;
                        }
                    }
                });
            }
        });
    }
});
</script>