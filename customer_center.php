<?php
/**
 * customer_center.php - v3.1 - Professional Grade Student & Class Management Hub
 *
 * This comprehensive page manages all customer-facing aspects of the school's finances.
 * All form processing logic is handled at the top of the script before any HTML output
 * to ensure redirects and session messages function correctly.
 *
 * Features:
 * - Student Management: CRUD, status changes, bulk actions, and a detailed split-view.
 * - Class Management: Create/edit classes and define the promotion path for automation.
 * - Item & Service Management: Full CRUD for invoice line items.
 * - Invoice Template Management: Create/edit templates and link them to classes for auto-invoicing.
 * - Payment Processing: Payments default to an "Undeposited Funds" account.
 * - Statement Generation: Create financial statements for students or classes.
 * - Receipt Viewing: A log of all generated payment receipts.
 * - Bulk Messaging: Communicate with student groups via SMS using Africa's Talking.
 */

// --- BLOCK 1: SETUP & PRE-PROCESSING ---
require_once 'config.php';
require_once 'functions.php'; // Ensure functions.php contains createInvoiceFromTemplate
require_once __DIR__ . '/vendor/autoload.php'; // Make sure the SDK is installed via Composer
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
        $pdo->beginTransaction();

        // --- Item Management ---
        if (isset($_POST['add_item'])) {
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $item_type = $parent_id ? 'child' : 'parent';
            $item_id = createItem($pdo, $school_id, $_POST['name'], $_POST['price'], $_POST['description'], $parent_id, $item_type);
            log_audit($pdo, 'CREATE', 'items', $item_id, ['data' => ['name' => $_POST['name'], 'price' => $_POST['price']]]);
            $_SESSION['success_message'] = "Item created successfully.";
        } elseif (isset($_POST['update_item'])) {
            $item_id = intval($_POST['item_id']);
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $item_type = $parent_id ? 'child' : 'parent';
            updateItem($pdo, $item_id, $_POST['name'], $_POST['price'], $_POST['description'], $parent_id, $item_type, $school_id);
            log_audit($pdo, 'UPDATE', 'items', $item_id, ['data' => ['name' => $_POST['name'], 'price' => $_POST['price']]]);
            $_SESSION['success_message'] = "Item updated successfully.";
        } elseif (isset($_POST['delete_item'])) {
            $item_id = intval($_POST['item_id']);
            deleteItem($pdo, $item_id, $school_id);
            log_audit($pdo, 'DELETE', 'items', $item_id, []);
            $_SESSION['success_message'] = "Item deleted successfully.";
        }

        // --- Student Management ---
        elseif (isset($_POST['addStudent'])) {
            $class_id = (isset($_POST['class_id']) && is_numeric($_POST['class_id'])) ? intval($_POST['class_id']) : null;
            $stmt = $pdo->prepare("INSERT INTO students (school_id, student_id_no, name, email, class_id, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $_POST['student_id_no'], $_POST['name'], $_POST['email'], $class_id, $_POST['phone'], $_POST['address']]);
            $_SESSION['success_message'] = "Student added successfully.";
        } elseif (isset($_POST['editStudent'])) {
            $student_id = intval($_POST['student_id']);
            $class_id = (isset($_POST['class_id']) && is_numeric($_POST['class_id'])) ? intval($_POST['class_id']) : null;
            $stmt = $pdo->prepare("UPDATE students SET student_id_no = ?, name = ?, email = ?, class_id = ?, phone = ?, address = ?, status = ? WHERE id = ? AND school_id = ?");
            $stmt->execute([$_POST['student_id_no'], $_POST['name'], $_POST['email'], $class_id, $_POST['phone'], $_POST['address'], $_POST['status'], $student_id, $school_id]);
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
                $stmt = $pdo->prepare("UPDATE classes SET name = ?, next_class_id = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([trim($name), $next_class_id, $class_id, $school_id]);
            }
            $_SESSION['success_message'] = "Class promotion paths updated.";
        }
        // --- Class Archiving ---
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
        // --- Bulk Invoice Generation ---
        elseif (isset($_POST['generate_bulk_invoices'])) {
            $class_id = intval($_POST['class_id']);
            $template_id = intval($_POST['template_id']);
            $due_date = $_POST['due_date'];

            if ($class_id > 0 && $template_id > 0 && !empty($due_date)) {
                $stmt_template = $pdo->prepare("SELECT items FROM invoice_templates WHERE id = ? AND school_id = ?");
                $stmt_template->execute([$template_id, $school_id]);
                $template_items = $stmt_template->fetchColumn();

                if ($template_items) {
                    $stmt_students = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
                    $stmt_students->execute([$class_id, $school_id]);
                    $students_in_class = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($students_in_class as $student) {
                        createInvoiceFromTemplate($pdo, $school_id, $student['id'], $template_id, $due_date);
                    }
                    $_SESSION['success_message'] = "Bulk invoices for " . count($students_in_class) . " students generated successfully.";
                } else {
                    $_SESSION['error_message'] = "Invoice template not found.";
                }
            } else {
                $_SESSION['error_message'] = "Missing required information for bulk invoice generation.";
            }
        }


        // --- Invoice Template Management ---
        elseif (isset($_POST['save_template'])) {
            $template_name = trim($_POST['template_name']);
            $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
            $template_id = !empty($_POST['template_id']) ? intval($_POST['template_id']) : null;
            $items_json = json_encode($_POST['items'] ?? []);

            if ($template_id) { // Update existing
                $stmt = $pdo->prepare("UPDATE invoice_templates SET name = ?, items = ?, class_id = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$template_name, $items_json, $class_id, $template_id, $school_id]);
                $_SESSION['success_message'] = "Template updated successfully.";
            } else { // Create new
                $stmt = $pdo->prepare("INSERT INTO invoice_templates (school_id, name, items, class_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$school_id, $template_name, $items_json, $class_id]);
                $_SESSION['success_message'] = "Template created successfully.";
            }
        }
        elseif (isset($_POST['update_template'])) {
            $template_id = intval($_POST['template_id']);
            $template_name = trim($_POST['template_name']);
            $template_items_json = $_POST['template_items_json'];

            if ($template_id > 0 && !empty($template_name) && !empty($template_items_json)) {
                $stmt_old = $pdo->prepare("SELECT * FROM invoice_templates WHERE id = ? AND school_id = ?");
                $stmt_old->execute([$template_id, $school_id]);
                $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

                if($old_data) {
                    $stmt = $pdo->prepare("UPDATE invoice_templates SET name = ?, items = ? WHERE id = ? AND school_id = ?");
                    $stmt->execute([$template_name, $template_items_json, $template_id, $school_id]);

                    $new_data = ['name' => $template_name, 'items' => $template_items_json];
                    log_audit($pdo, 'UPDATE', 'invoice_templates', $template_id, ['before' => $old_data, 'after' => $new_data]);
                }
                $_SESSION['success_message'] = "Invoice template updated successfully!";
                $active_tab = 'templates';
            } else {
                $_SESSION['error_message'] = "Template name and items cannot be empty for an update.";
            }
        }
        elseif (isset($_POST['delete_template'])) {
            $template_id = intval($_POST['template_id']);
            if ($template_id > 0) {
                $stmt_old = $pdo->prepare("SELECT * FROM invoice_templates WHERE id = ? AND school_id = ?");
                $stmt_old->execute([$template_id, $school_id]);
                $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

                if ($old_data) {
                    $stmt = $pdo->prepare("DELETE FROM invoice_templates WHERE id = ? AND school_id = ?");
                    $stmt->execute([$template_id, $school_id]);
                    log_audit($pdo, 'DELETE', 'invoice_templates', $template_id, ['data' => $old_data]);
                }
                $_SESSION['success_message'] = "Invoice template deleted successfully!";
                $active_tab = 'templates';
            }
        }

        // --- Payment Promise Management ---
        elseif (isset($_POST['add_promise'])) {
            $stmt = $pdo->prepare(
                "INSERT INTO payment_promises (school_id, student_id, invoice_id, promise_date, promised_due_date, promised_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $school_id,
                $_POST['promise_student_id'],
                $_POST['promise_invoice_id'],
                $_POST['promise_date'],
                $_POST['promised_due_date'],
                $_POST['promised_amount'],
                $_POST['notes']
            ]);
            $_SESSION['success_message'] = "Payment promise recorded successfully.";
        }

        // --- Payment Processing (UPDATED LOGIC) ---
        elseif (isset($_POST['process_payment'])) {
            $student_id_payment = intval($_POST['student_id']);
            $payment_date = $_POST['payment_date'];
            $total_payment = 0;

            // Programmatically get the "Undeposited Funds" account ID.
            $undeposited_funds_id = getUndepositedFundsAccountId($pdo, $school_id);

            if ($undeposited_funds_id <= 0) {
                throw new Exception("The required 'Undeposited Funds' account is missing.");
            }

            // Handle Invoice Payments
            if (isset($_POST['invoice_ids'])) {
                foreach ($_POST['invoice_ids'] as $index => $invoice_id) {
                    $amount = floatval($_POST['payment_amounts'][$index]);
                    if ($amount > 0) {
                        $total_payment += $amount;
                        $receipt_number = 'REC-' . strtoupper(uniqid());
                        
                        // Insert into payment_receipts table
                        $stmt_receipt = $pdo->prepare("INSERT INTO payment_receipts (school_id, receipt_number, student_id, payment_date, amount, payment_method, memo, coa_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_receipt->execute([$school_id, $receipt_number, $student_id_payment, $payment_date, $amount, $_POST['payment_method'], $_POST['memo'], $undeposited_funds_id]);
                        $receiptId = $pdo->lastInsertId();

                        // Insert into payments table
                        $stmt_payment = $pdo->prepare("INSERT INTO payments (school_id, invoice_id, student_id, payment_date, amount, payment_method, memo, receipt_id, coa_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_payment->execute([$school_id, $invoice_id, $student_id_payment, $payment_date, $amount, $_POST['payment_method'], $_POST['memo'], $receiptId, $undeposited_funds_id]);
                        
                        // Check for promises on this invoice and update them
                        $promise_stmt = $pdo->prepare("SELECT * FROM payment_promises WHERE invoice_id = ? AND status = 'Pending' AND promised_due_date >= ?");
                        $promise_stmt->execute([$invoice_id, $payment_date]);
                        foreach($promise_stmt->fetchAll(PDO::FETCH_ASSOC) as $promise) {
                            $update_promise = $pdo->prepare("UPDATE payment_promises SET status = 'Kept' WHERE id = ?");
                            $update_promise->execute([$promise['id']]);
                        }
                    }
                }
            }
            
            // Journal Entry for Invoice Payments
            if ($total_payment > 0) {
                $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
                $stmt_student = $pdo->prepare("SELECT name FROM students WHERE id = ?"); 
                $stmt_student->execute([$student_id_payment]);
                $student_name = $stmt_student->fetchColumn();
                $description = "Fee payment from {$student_name}.";

                // Debit Undeposited Funds (asset increases), Credit Accounts Receivable (asset decreases).
                create_journal_entry($pdo, $school_id, $payment_date, $description, $total_payment, $undeposited_funds_id, $accounts_receivable_id);
            }
            $_SESSION['success_message'] = "Payment recorded successfully.";
        }
        
        // --- Bulk Messaging with Africa's Talking---
        elseif (isset($_POST['send_bulk_message'])) {
            $send_to_group = $_POST['send_to_group'];
            $class_id_messaging = $_POST['class_id_messaging'] ?? null;
            $message = trim($_POST['message_body']);

            if (empty($message)) {
                throw new Exception("The message body cannot be empty.");
            }

            // 1. Fetch Student Phone Numbers based on the selected group
            $students_phones_raw = [];
            if ($send_to_group === 'all') {
                $stmt = $pdo->prepare("SELECT phone, name FROM students WHERE school_id = ? AND status = 'active'");
                $stmt->execute([$school_id]);
                $students_phones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($send_to_group === 'class' && !empty($class_id_messaging)) {
                $stmt = $pdo->prepare("SELECT phone, name FROM students WHERE school_id = ? AND class_id = ? AND status = 'active'");
                $stmt->execute([$school_id, $class_id_messaging]);
                $students_phones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($send_to_group === 'unpaid') {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.phone, s.name 
                    FROM students s 
                    JOIN invoices i ON s.id = i.student_id 
                    WHERE s.school_id = ? AND i.balance > 0.01 AND s.status = 'active'
                ");
                $stmt->execute([$school_id]);
                $students_phones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($students_phones_raw)) {
                throw new Exception("No students found for the selected recipient group.");
            }
            
            // 2. Format Phone Numbers and Personalize Messages
            $school_name = $_SESSION['school_name'] ?? 'Your School';
            $final_message = str_replace('[school_name]', $school_name, $message);
            $final_message = preg_replace('/\[student_name\]/i', 'Parent', $final_message);

            $recipient_numbers = [];
            $invalidNumbers = []; // Track invalid numbers for error reporting
            
            foreach ($students_phones_raw as $student) {
                $formatted_phone = formatPhoneNumberForAT($student['phone']);
                if ($formatted_phone) {
                    $recipient_numbers[] = $formatted_phone;
                } else {
                    // Track invalid numbers for debugging
                    $invalidNumbers[] = $student['phone'];
                }
            }
            
            // Remove duplicate numbers just in case
            $recipient_numbers = array_unique($recipient_numbers);

            if (empty($recipient_numbers)) {
                $invalidCount = count($invalidNumbers);
                $example = $invalidCount > 0 ? $invalidNumbers[0] : 'N/A';
                throw new Exception("No valid phone numbers found. $invalidCount numbers were invalid. Example: $example");
            }

            // 3. Call the centralized function to send the message
            try {
                $result = sendBulkSms($recipient_numbers, $final_message);

                // First, check if the function returned a custom error array
                if (is_array($result) && isset($result['error'])) {
                    throw new Exception($result['error']);
                }

                // NEW: IMPROVED API RESPONSE HANDLING
                if (is_object($result) && isset($result->data)) {
                    // Handle successful response with recipients
                    if (isset($result->data->Recipients)) {
                        $successful_sends = count($result->data->Recipients);
                        if ($successful_sends > 0) {
                            $_SESSION['success_message'] = "Bulk message sent to " . $successful_sends . " recipients successfully!";
                        } else {
                            // This will now correctly trigger only if the API truly processes zero recipients.
                            // This can happen if all numbers are invalid (e.g., blacklisted, not registered on sandbox)
                            $invalidList = implode(', ', array_slice($invalidNumbers, 0, 5)); // Show first 5 invalid numbers
                            throw new Exception("The API processed the request but did not send to any recipients. Please check the validity of the phone numbers. First 5 invalid: $invalidList");
                        }
                    }
                    // Handle error responses from API
                    elseif (isset($result->data->message)) {
                        throw new Exception("API Error: " . $result->data->message);
                    }
                    // Handle general success messages
                    elseif (isset($result->data->SMSMessageData->Message)) {
                        $_SESSION['success_message'] = "Message queued: " . $result->data->SMSMessageData->Message;
                    }
                    else {
                        throw new Exception("API response missing recipient data and error message");
                    }
                } 
                // Handle array-formatted responses
                elseif (is_array($result) && isset($result['data'])) {
                    if (isset($result['data']['Recipients'])) {
                        $successful_sends = count($result['data']['Recipients']);
                        $_SESSION['success_message'] = "Bulk message sent to " . $successful_sends . " recipients successfully!";
                    }
                    elseif (isset($result['data']['message'])) {
                        throw new Exception("API Error: " . $result['data']['message']);
                    }
                    else {
                        throw new Exception("Unexpected API response format");
                    }
                }
                else {
                    throw new Exception("Unexpected API response format");
                }

            } catch (Exception $e) {
                // This will catch any exceptions from the function or the logic above
                throw new Exception("Africa's Talking API Error: " . $e->getMessage());
            }
        } else {
            $action_taken = false; // No relevant action was found
        }
        
        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
    
    if($action_taken) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=" . $active_tab);
        exit();
    }
}


// --- BLOCK 3: PAGE DISPLAY SETUP ---
require_once 'header.php';

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['warning_message'])) {
    echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['warning_message']) . '</div>';
    unset($_SESSION['warning_message']);
}

$items = getItemsWithSubItems($pdo, $school_id);
$show_archived = isset($_GET['show_archived']) ? true : false;
$stmt_classes = $pdo->prepare("SELECT c1.*, c2.name as next_class_name FROM classes c1 LEFT JOIN classes c2 ON c1.next_class_id = c2.id WHERE c1.school_id = ? AND c1.is_archived = ? ORDER BY c1.name");
$stmt_classes->execute([$school_id, $show_archived ? 1 : 0]);
$classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

$invoice_templates = getInvoiceTemplates($pdo, $school_id);

$filter_name = $_GET['filter_name'] ?? '';
$filter_class_id = $_GET['filter_class_id'] ?? '';
$filter_status = $_GET['filter_status'] ?? 'active';
$students = getStudents($pdo, $school_id, $filter_name, $filter_class_id, $filter_status);
$all_students_for_dropdown = getStudents($pdo, $school_id, null, null, 'active');

$filter_invoice_student_id = $_GET['filter_invoice_student_id'] ?? '';
$filter_invoice_status = $_GET['filter_invoice_status'] ?? '';
$filter_invoice_start_date = $_GET['filter_invoice_start_date'] ?? '';
$filter_invoice_end_date = $_GET['filter_invoice_end_date'] ?? '';
$invoices = getInvoices($pdo, $school_id, $filter_invoice_student_id, $filter_invoice_status, $filter_invoice_start_date, $filter_invoice_end_date);

$all_receipts = getAllReceipts($pdo, $school_id);
$asset_accounts = getAccountsByType($pdo, $school_id, 'asset');

// Define pre-written SMS message templates
$message_templates = [
    "Gentle Fee Reminder" => "Dear Parent, this is a friendly reminder from [school_name] that school fees are due on [Date]. Kindly ensure your balance is cleared on time. Thank you.",
    "Overdue Balance Notice" => "Dear Parent, our records from [school_name] show an outstanding fee balance for your child. Please make the payment at your earliest convenience to avoid service interruptions. Thank you.",
    "Payment Confirmation" => "Dear Parent, we have received your recent payment. Thank you for your continued partnership with [school_name]. Your child's new balance is [Balance].",
    "Parent-Teacher Meeting" => "Greetings from [school_name]. We invite you to a Parent-Teacher meeting on [Date] at [Time] to discuss your child's academic progress. We look forward to seeing you.",
    "School Reopening" => "Dear Parent, this is to notify you that [school_name] will reopen for the new term on [Date]. We are excited to welcome all students back.",
    "School Closing" => "Dear Parent, please note that [school_name] will close for the holidays on [Date]. Report cards can be collected from the office. We wish you and your family a wonderful break.",
    "Event Reminder" => "Reminder from [school_name]: Our annual Sports Day is on [Date] starting at [Time]. Parents are encouraged to attend and support our students. Come and cheer them on!",
    "Urgent Announcement" => "Urgent notice from [school_name]: Due to unforeseen circumstances, the school will be closed on [Date]. Regular classes will resume on [Date]. We apologize for any inconvenience."
];

?>
<style>
    .student-view-container { display: flex; min-height: 600px; border: 1px solid var(--border); overflow: hidden; position: relative; }
    .student-list-panel { width: 40%; min-width: 350px; flex-shrink: 0; overflow-y: auto; border-right: none; padding-right: 0; }
    .student-detail-panel { flex-grow: 1; overflow-y: auto; padding-left: 10px; min-width: 400px; }
    #resizer { width: 10px; background-color: #f1f5f9; cursor: col-resize; position: relative; transition: background-color 0.2s ease; }
    #resizer:hover { background-color: #e2e8f0; }
    #resizer::before { content: ''; position: absolute; left: 4px; top: 50%; transform: translateY(-50%); width: 2px; height: 40px; background-color: #cbd5e1; border-radius: 2px; }
    #student-detail-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #777; flex-direction: column; padding: 2rem; }
    #student-detail-content { display: none; }
    .student-list-panel table tr.active { background-color: #e3f2fd !important; font-weight: bold; }
    .student-list-panel table tr { cursor: pointer; }
    .filter-controls { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 12px; border: 1px solid var(--border); }
    .filter-controls .form-group { margin-bottom: 0; }
    .bulk-actions-container { display: flex; gap: 15px; align-items: center; margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 12px; }
    .student-detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
    .student-info h3 { margin-top: 0; }
    .student-balance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-bottom: 2rem; }
    .balance-card { background: #f8f9fa; padding: 20px; border-radius: 12px; text-align: center; border-top: 4px solid var(--secondary, #3498db); }
    .balance-card h4 { margin: 0 0 10px 0; color: #6c757d; font-size: 1rem; }
    .balance-amount { font-size: 1.75rem; font-weight: 700; }
    .balance-amount.balance-due { color: var(--danger, #e74c3c); }
    .balance-amount.balance-zero { color: var(--success, #2ecc71); }
    .items-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
    .item-card { background: #f8f9fa; border-radius: 12px; border: 1px solid var(--border); }
    .item-header { padding: 15px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .item-title { font-size: 1.1rem; color: var(--primary); margin: 0; }
    .item-details { padding: 15px; }
    .sub-items-section { padding: 0 15px 15px; }
    .sub-items-title { font-size: 0.9rem; color: #666; margin-bottom: 10px; border-top: 1px dashed #ccc; padding-top: 10px; }
    .sub-item-card { display: flex; justify-content: space-between; align-items: center; padding: 8px; border-radius: 8px; }
    .sub-item-card:nth-child(even) { background-color: #fff; }
    .promise-badge { display: inline-block; font-size: 0.8em; padding: 4px 8px; border-radius: 12px; background-color: #fffbe6; color: #713f12; border: 1px solid #fde68a; margin-left: 10px; }
    .badge.badge-active { background-color: var(--success); color: var(--white); }
    .badge.badge-inactive { background-color: #6c757d; color: var(--white); }
    .action-buttons .btn-icon, .btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; text-decoration: none; color: #fff; background-color: #6c757d; border: none; transition: all 0.2s ease-in-out; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .action-buttons .btn-icon:hover, .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    .btn-view { background-color: #3498db; }
    .btn-download { background-color: #2ecc71; }
    .btn-add { display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background-color: var(--primary, #007bff); color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s ease-in-out; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .btn-add:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); background-color: #0056b3; }
    .badge.badge-active { background-color: var(--success); color: var(--white); }
    .badge.badge-inactive { background-color: #6c757d; color: var(--white); }
    .transaction-history-table tbody tr { border-bottom: 1px solid #f0f0f0; }
    .transaction-history-table tbody tr:last-child { border-bottom: none; }
    .transaction-icon-cell { width: 60px; text-align: center; vertical-align: middle; }
    .transaction-icon { font-size: 1.2rem; width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
    .icon-invoice { color: var(--secondary); background-color: rgba(52, 152, 219, 0.1); }
    .icon-payment { color: var(--success); background-color: rgba(46, 204, 113, 0.1); }
    .icon-promise { color: var(--warning); background-color: rgba(243, 156, 18, 0.1); }
    .transaction-details p { margin: 0; }
    .transaction-title { font-weight: 600; }
    .transaction-meta { font-size: 0.85rem; color: #777; }
    .transaction-amount { font-weight: 600; text-align: right; }
    .amount-debit { color: var(--primary); }
    .amount-credit { color: var(--success); }
    .transaction-date { font-weight: 600; font-size: 0.9rem; color: #555; }
    .balance-amount.balance-credit { color: var(--secondary, #3498db); }
</style>

<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-users"></i> Customer Center</h1>
        <p>Manage students, classes, invoices, payments, and communications.</p>
    </div>
</div>

<div class="tab-container">
    <div class="tabs">
        <button class="tab-link" onclick="openTab(event, 'students')"><i class="fas fa-users"></i> Students</button>
        <button class="tab-link" onclick="openTab(event, 'classes')"><i class="fas fa-school"></i> Manage Classes</button>
        <button class="tab-link" onclick="openTab(event, 'invoices')"><i class="fas fa-file-invoice"></i> Invoices</button>
        <button class="tab-link" onclick="openTab(event, 'items')"><i class="fas fa-tags"></i> Items & Services</button>
        <button class="tab-link" onclick="openTab(event, 'templates')"><i class="fas fa-paste"></i> Invoice Templates</button>
        <button class="tab-link" onclick="openTab(event, 'receive_payment')"><i class="fas fa-hand-holding-usd"></i> Receive Payment</button>
        <button class="tab-link" onclick="openTab(event, 'statements')"><i class="fas fa-file-alt"></i> Statements</button>
        <button class="tab-link" onclick="openTab(event, 'receipts')"><i class="fas fa-receipt"></i> Receipts</button>
        <button class="tab-link" onclick="openTab(event, 'bulk_messaging')"><i class="fas fa-paper-plane"></i> Bulk Messaging</button>
    </div>

    <div id="students" class="tab-content">
        <div class="student-view-container" id="resizable-container">
            <div class="student-list-panel" id="left-panel">
                <h3>All Students</h3>
                <div class="table-actions"><button class="btn-add" onclick="openModal('addStudentModal')"><i class="fas fa-plus"></i> Add Student</button></div>
                
                <form method="get" class="filter-controls">
                    <input type="hidden" name="tab" value="students">
                    <div class="form-group"><label for="filter_name">Name/ID</label><input type="text" name="filter_name" id="filter_name" value="<?= htmlspecialchars($filter_name) ?>" class="form-control"></div>
                    <div class="form-group"><label for="filter_class_id">Class</label><select name="filter_class_id" id="filter_class_id" class="form-control"><option value="">All</option><?php foreach($classes as $class): ?><option value="<?= $class['id'] ?>" <?= ($filter_class_id == $class['id']) ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="filter_status">Status</label><select name="filter_status" id="filter_status" class="form-control"><option value="active" <?= ($filter_status == 'active') ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($filter_status == 'inactive') ? 'selected' : '' ?>>Inactive</option><option value="all" <?= ($filter_status == 'all') ? 'selected' : '' ?>>All</option></select></div>
                    <button type="submit" class="btn-primary">Filter</button>
                </form>

                <form id="bulk-student-form" method="post">
                    <input type="hidden" name="active_tab" value="students">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all-students"></th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): ?>
                                    <tr onclick="viewStudentDetails(<?= $student['id']; ?>, this)">
                                        <td><input type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>" class="student-checkbox" onclick="event.stopPropagation();"></td>
                                        <td><?= htmlspecialchars($student['name']) ?><br><small><?= htmlspecialchars($student['student_id_no']) ?></small></td>
                                        <td><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></td>
                                        <td><span class="badge badge-<?= htmlspecialchars($student['status']) ?>"><?= htmlspecialchars(ucfirst($student['status'])) ?></span></td>
                                        <td>
                                            <div class="action-buttons" onclick="event.stopPropagation();">
                                                <button type="button" class="btn-icon btn-edit" title="Edit" onclick='editStudent(<?= htmlspecialchars(json_encode($student), ENT_QUOTES, "UTF-8") ?>)'><i class="fas fa-edit"></i></button>
                                                <form method="post" onsubmit="return confirm('Change this student\'s status?');" style="display:inline;">
                                                    <input type="hidden" name="active_tab" value="students">
                                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                                    <?php if ($student['status'] === 'active'): ?>
                                                        <button type="submit" name="deactivate_student" class="btn-icon btn-delete" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                                    <?php else: ?>
                                                        <button type="submit" name="reactivate_student" class="btn-icon btn-success" title="Activate"><i class="fas fa-user-check"></i></button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bulk-actions-container">
                        <div class="form-group"><label for="bulk_action">Bulk Action:</label><select name="bulk_action" id="bulk_action" class="form-control"><option value="">Select Action</option><option value="delete">Deactivate Selected</option><option value="activate">Activate Selected</option></select></div>
                        <button type="submit" name="bulk_action_submit" class="btn-danger">Apply</button>
                    </div>
                </form>
            </div>
            <div id="resizer"></div>
            <div class="student-detail-panel" id="right-panel">
                <div id="student-detail-placeholder"><i class="fas fa-arrow-left" style="font-size: 2rem; margin-bottom: 1rem; color: #ccc;"></i><p>Select a student to view details.</p></div>
                <div id="student-detail-content"></div>
            </div>
        </div>
    </div>

    <div id="classes" class="tab-content">
        <div class="card">
            <h3>Manage Classes and Promotion Path</h3>
            <p>Set the "Next Class" for each class to define the automatic promotion path. Students in a class with no "Next Class" will not be promoted during bulk actions.</p>
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4>All Classes</h4>
                    <div class="form-group">
                        <label for="show_archived" class="form-check-label">
                            <input type="checkbox" id="show_archived" name="show_archived" class="form-check-input" onchange="window.location.href = this.checked ? 'customer_center.php?tab=classes&show_archived=1' : 'customer_center.php?tab=classes';"> Show Archived
                        </label>
                    </div>
                </div>
                <form action="customer_center.php" method="POST">
                    <input type="hidden" name="active_tab" value="classes">
                    <input type="hidden" name="update_classes" value="1">
                    <table>
                        <thead><tr><th>Class Name</th><th>Next Class (for Promotion)</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><input type="text" name="class_name[<?= $class['id'] ?>]" value="<?= htmlspecialchars($class['name']) ?>" class="form-control"></td>
                                <td>
                                    <select name="next_class_id[<?= $class['id'] ?>]" class="form-control">
                                        <option value="">-- None (Final Class) --</option>
                                        <?php foreach ($classes as $next_class_option): if ($class['id'] != $next_class_option['id']): ?>
                                            <option value="<?= $next_class_option['id'] ?>" <?= ($class['next_class_id'] == $next_class_option['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($next_class_option['name']) ?>
                                            </option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to <?= $class['is_archived'] ? 'unarchive' : 'archive' ?> this class?');" style="display:inline;">
                                        <input type="hidden" name="active_tab" value="classes">
                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                        <?php if ($class['is_archived']): ?>
                                            <button type="submit" name="unarchive_class" class="btn-icon btn-success" title="Unarchive Class"><i class="fas fa-box-open"></i></button>
                                        <?php else: ?>
                                            <button type="submit" name="archive_class" class="btn-icon btn-delete" title="Archive Class"><i class="fas fa-archive"></i></button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="form-actions"><button type="submit" class="btn-primary">Save Changes</button></div>
                </form>
            </div>
            <hr style="margin: 2rem 0;">
            <h4>Add New Class</h4>
            <form action="customer_center.php" method="POST">
                 <input type="hidden" name="active_tab" value="classes">
                <input type="hidden" name="add_class" value="1">
                <div class="form-group"><label for="class_name">New Class Name</label><input type="text" name="class_name" id="class_name" class="form-control" required></div>
                <div class="form-actions"><button type="submit" class="btn-success">Add Class</button></div>
            </form>

            <hr style="margin: 2rem 0;">
            <h4>Bulk Invoice Generation for Classes</h4>
            <p>Quickly generate invoices for all students in a class using an existing template. This action cannot be undone.</p>
            <form action="customer_center.php" method="POST">
                <input type="hidden" name="active_tab" value="classes">
                <input type="hidden" name="generate_bulk_invoices" value="1">
                <div class="form-group">
                    <label for="class_id_for_invoices">Select Class</label>
                    <select name="class_id" id="class_id_for_invoices" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php
                        // Re-fetch all classes (active and archived) for this dropdown
                        $stmt_all_classes = $pdo->prepare("SELECT id, name FROM classes WHERE school_id = ? ORDER BY name");
                        $stmt_all_classes->execute([$school_id]);
                        $all_classes_for_dropdown = $stmt_all_classes->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($all_classes_for_dropdown as $class): ?>
                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="template_id_for_invoices">Select Invoice Template</label>
                    <select name="template_id" id="template_id_for_invoices" class="form-control" required>
                        <option value="">-- Select Template --</option>
                        <?php foreach ($invoice_templates as $template): ?>
                            <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="due_date_for_invoices">Invoice Due Date</label>
                    <input type="date" name="due_date" id="due_date_for_invoices" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-warning" onclick="return confirm('Are you sure you want to generate invoices for this class? This cannot be undone.');"><i class="fas fa-plus-circle"></i> Generate Invoices</button>
                </div>
            </form>

            <hr style="margin: 2rem 0;">
            <h4>Class Financial Dashboard</h4>
            <div class="form-group">
                <label for="class_dashboard_select">Select a Class to View</label>
                <select id="class_dashboard_select" class="form-control" onchange="loadClassDashboard(this.value)">
                    <option value="">-- Select a Class --</option>
                    <?php foreach ($all_classes_for_dropdown as $class): ?>
                        <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="class_dashboard_content" style="margin-top: 2rem;">
                <p>Select a class to view its financial summary and student details.</p>
            </div>

        </div>
    </div>
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
    <div id="items" class="tab-content">
        <div class="card">
            <h3>Items & Services</h3>
            <div class="table-actions"><button class="btn-add" onclick="openModal('addItemModal')"><i class="fas fa-plus"></i> Add New Item</button></div>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-header">
                            <h3 class="item-title"><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="item-actions">
                                <button class="btn-icon" onclick="openEditItemModal(<?= htmlspecialchars(json_encode($item)) ?>)"><i class="fas fa-edit"></i></button>
                                <form method="post" onsubmit="return confirm('Delete this item and all its sub-items?');" style="display:inline;">
                                    <input type="hidden" name="active_tab" value="items">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="delete_item" class="btn-icon"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="item-details"><p><strong>Price:</strong> $<?= number_format($item['price'], 2) ?></p></div>
                        <?php if (!empty($item['sub_items'])): ?>
                            <div class="sub-items-section">
                                <h4 class="sub-items-title">Sub-items</h4>
                                <?php foreach ($item['sub_items'] as $sub_item): ?>
                                    <div class="sub-item-card">
                                        <span><?= htmlspecialchars($sub_item['name']) ?> - <strong>$<?= number_format($sub_item['price'], 2) ?></strong></span>
                                        <div class="item-actions">
                                            <button class="btn-icon" onclick="openEditItemModal(<?= htmlspecialchars(json_encode($sub_item)) ?>)"><i class="fas fa-edit"></i></button>
                                            <form method="post" onsubmit="return confirm('Delete this sub-item?');" style="display:inline;">
                                                <input type="hidden" name="active_tab" value="items">
                                                <input type="hidden" name="item_id" value="<?= $sub_item['id'] ?>">
                                                <button type="submit" name="delete_item" class="btn-icon"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
                
                <h4 style="margin-top: 2rem;">Open Pledges</h4>
                <table id="unpaidPledgesTable" class="table"><thead><tr><th>Campaign</th><th>Date</th><th>Pledged</th><th>Paid</th><th>Balance</th><th>Payment</th></tr></thead><tbody></tbody></table>
                
                <div style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-top: 1rem;">Total Payment: <span id="totalPayment">$0.00</span></div>
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
            <p>Select a recipient group, choose a template or write a custom message, and send.</p>
            <form id="bulkMessageForm" method="post">
                <input type="hidden" name="active_tab" value="bulk_messaging">
                <input type="hidden" name="send_bulk_message" value="1">
                <div class="form-group">
                    <label for="send_to_group">Send To</label>
                    <select name="send_to_group" id="send_to_group" class="form-control" required>
                        <option value="">-- Select Recipient Group --</option>
                        <option value="class">Students in a Specific Class</option>
                        <option value="unpaid">Students with Unpaid Invoices</option>
                        <option value="all">All Active Students</option>
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
                    <small>You can use placeholders like [school_name]. Note: [student_name] will be replaced with 'Parent' in bulk messages.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Send Bulk Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="addStudentModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Add New Student</h3><span class="close" onclick="closeModal('addStudentModal')">&times;</span></div><form method="post"><input type="hidden" name="active_tab" value="students"><input type="hidden" name="addStudent" value="1"><div class="modal-body"><div class="form-group"><label>Full Name</label><input type="text" name="name" required class="form-control"></div><div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div><div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div><div class="form-group"><label>Address</label><textarea name="address" rows="2" class="form-control"></textarea></div><div class="form-group"><label>Student ID No.</label><input type="text" name="student_id_no" class="form-control"></div><div class="form-group"><label for="add_student_class_id">Class</label><select name="class_id" id="add_student_class_id" class="form-control class-select"><option value="">Select Class</option><?php foreach ($all_classes_for_dropdown as $class): ?><option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option><?php endforeach; ?></select></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button><button type="submit" class="btn-primary">Add Student</button></div></form></div></div>
<div id="editStudentModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Edit Student</h3><span class="close" onclick="closeModal('editStudentModal')">&times;</span></div><form id="editStudentForm" method="post"><input type="hidden" name="active_tab" value="students"><input type="hidden" name="editStudent" value="1"><input type="hidden" name="student_id" id="edit_student_id"><div class="modal-body"><div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required class="form-control"></div><div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" class="form-control"></div><div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div><div class="form-group"><label>Address</label><textarea name="address" id="edit_address" rows="2" class="form-control"></textarea></div><div class="form-group"><label>Student ID No.</label><input type="text" name="student_id_no" id="edit_student_id_no" class="form-control"></div><div class="form-group"><label>Status</label><select name="status" id="edit_status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div><div class="form-group"><label for="edit_class_id">Class</label><select name="class_id" id="edit_class_id" class="form-control class-select"><option value="">Select Class</option><?php foreach ($all_classes_for_dropdown as $class): ?><option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option><?php endforeach; ?></select></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button><button type="submit" class="btn-primary">Update Student</button></div></form></div></div>
<div id="addItemModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Add New Item/Service</h3><span class="close" onclick="closeModal('addItemModal')">&times;</span></div><form method="post"><input type="hidden" name="active_tab" value="items"><input type="hidden" name="add_item" value="1"><div class="modal-body"><div class="form-group"><label>Item Name</label><input type="text" name="name" required class="form-control"></div><div class="form-group"><label>Price</label><input type="number" name="price" step="0.01" required class="form-control"></div><div class="form-group"><label>Description</label><textarea name="description" rows="2" class="form-control"></textarea></div><div class="form-group"><label>Parent Item (for sub-items)</label><select name="parent_id" class="form-control"><option value="">None (This is a main item)</option><?php foreach ($items as $parent_item): ?><option value="<?= $parent_item['id'] ?>"><?= htmlspecialchars($parent_item['name']) ?></option><?php endforeach; ?></select></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('addItemModal')">Cancel</button><button type="submit" class="btn-primary">Save Item</button></div></form></div></div>
<div id="editItemModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Edit Item/Service</h3><span class="close" onclick="closeModal('editItemModal')">&times;</span></div><form id="editItemForm" method="post"><input type="hidden" name="active_tab" value="items"><input type="hidden" name="update_item" value="1"><input type="hidden" name="item_id" id="edit_item_id"><div class="modal-body"><div class="form-group"><label>Item Name</label><input type="text" name="name" id="edit_item_name" required class="form-control"></div><div class="form-group"><label>Price</label><input type="number" name="price" id="edit_item_price" step="0.01" required class="form-control"></div><div class="form-group"><label>Description</label><textarea name="description" id="edit_item_description" rows="2" class="form-control"></textarea></div><div class="form-group"><label>Parent Item</label><select name="parent_id" id="edit_parent_id" class="form-control"><option value="">None (This is a main item)</option><?php foreach ($items as $parent_item): ?><option value="<?= $parent_item['id'] ?>"><?= htmlspecialchars($parent_item['name']) ?></option><?php endforeach; ?></select></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('editItemModal')">Cancel</button><button type="submit" class="btn-primary">Update Item</button></div></form></div></div>
<div id="viewReceiptModal" class="modal"><div class="modal-content" style="max-width: 500px;"><div class="modal-header"><h3>Receipt Details</h3><span class="close" onclick="closeModal('viewReceiptModal')">&times;</span></div><div class="modal-body" id="receipt-details-body"><p>Loading...</p></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('viewReceiptModal')">Close</button><button type="button" class="btn-primary" onclick="printReceipt()">Print Receipt</button></div></div></div>

<div id="editTemplateModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Edit Invoice Template</h3>
            <span class="close" onclick="closeModal('editTemplateModal')">&times;</span>
        </div>
        <form id="editTemplateForm" method="post" onsubmit="prepareTemplateUpdate()">
            <input type="hidden" name="active_tab" value="templates">
            <input type="hidden" name="update_template" value="1">
            <input type="hidden" name="template_id" id="edit_template_id">
            <input type="hidden" name="template_items_json" id="edit_template_items_json">

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_template_name">Template Name</label>
                    <input type="text" id="edit_template_name" name="template_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_template_class_id">Link to Class</label>
                    <select name="class_id" id="edit_template_class_id" class="form-control">
                        <option value="">-- No Link --</option>
                        <?php foreach ($all_classes_for_dropdown as $class): ?>
                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                <?php foreach ($items as $item): ?>
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

<div id="addPromiseModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Record a Payment Promise</h3><span class="close" onclick="closeModal('addPromiseModal')">&times;</span></div><form method="post"><input type="hidden" name="active_tab" value="students"><input type="hidden" name="add_promise" value="1"><input type="hidden" name="promise_student_id" id="promise_student_id"><input type="hidden" name="promise_invoice_id" id="promise_invoice_id"><div class="modal-body"><p>For Invoice #<strong id="promise_invoice_id_display"></strong></p><div class="form-group"><label for="promised_amount">Promised Amount</label><input type="number" name="promised_amount" id="promised_amount" step="0.01" required class="form-control"></div><div class="form-group"><label for="promised_due_date">Promised Payment Date</label><input type="date" name="promised_due_date" id="promised_due_date" required class="form-control"></div><div class="form-group"><label for="promise_date">Date of Promise</label><input type="date" name="promise_date" id="promise_date" value="<?= date('Y-m-d') ?>" required class="form-control"></div><div class="form-group"><label for="notes">Notes</label><textarea name="notes" id="notes" rows="3" class="form-control" placeholder="e.g., Spoke with parent on the phone."></textarea></div></div><div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeModal('addPromiseModal')">Cancel</button><button type="submit" class="btn-primary">Save Promise</button></div></form></div></div>

<?php include 'footer.php'; ?>

<script>
// --- Core UI Functions ---
function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
function openTab(evt, tabName) {
    document.querySelectorAll(".tab-content").forEach(tc => tc.style.display = "none");
    document.querySelectorAll(".tab-link").forEach(tl => tl.classList.remove("active"));
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.classList.add("active");
    history.replaceState(null, '', `?tab=${tabName}`);
}

// --- Student Management ---
function viewStudentDetails(studentId, rowElement) {
    document.getElementById('student-detail-placeholder').style.display = 'flex';
    document.getElementById('student-detail-content').style.display = 'none';
    document.querySelectorAll('.student-list-panel tr').forEach(row => row.classList.remove('active'));
    if (rowElement) rowElement.classList.add('active');

    fetch(`get_student_details.php?id=${studentId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const contentDiv = document.getElementById('student-detail-content');
            let historyRows = '';
            if(data.history.length > 0) {
                data.history.forEach(item => {
                    let rowHtml = '<tr>';
                    const itemDate = new Date(item.date).toLocaleDateString();

                    switch(item.type) {
                        case 'invoice':
                            const balance = parseFloat(item.data.total_amount) - parseFloat(item.data.paid_amount);
                            rowHtml += `
                                <td class="transaction-icon-cell"><i class="fas fa-file-invoice transaction-icon icon-invoice"></i></td>
                                <td><p class="transaction-date">${itemDate}</p></td>
                                <td>
                                    <p class="transaction-title">Invoice #${item.data.id} Generated</p>
                                    <p class="transaction-meta">Due: ${new Date(item.data.due_date).toLocaleDateString()} | Status: ${item.data.status}</p>
                                </td>
                                <td class="transaction-amount amount-debit">$${parseFloat(item.data.total_amount).toFixed(2)}</td>
                                <td class="action-buttons">
                                    <a href="view_invoice.php?id=${item.data.id}" class="btn-icon btn-view" title="View Invoice"><i class="fas fa-eye"></i></a>
                                    <button class="btn-icon btn-add" title="Add Promise" onclick="openPromiseModal(${item.data.id}, ${studentId}, ${balance.toFixed(2)})"><i class="fas fa-handshake"></i></button>
                                </td>
                            `;
                            break;
                        case 'payment':
                            rowHtml += `
                                <td class="transaction-icon-cell"><i class="fas fa-check-circle transaction-icon icon-payment"></i></td>
                                <td><p class="transaction-date">${itemDate}</p></td>
                                <td>
                                    <p class="transaction-title">Payment Received</p>
                                    <p class="transaction-meta">Receipt #${item.data.receipt_number || 'N/A'} | Method: ${item.data.payment_method}</p>
                                </td>
                                <td class="transaction-amount amount-credit">-$${parseFloat(item.data.amount).toFixed(2)}</td>
                                <td class="action-buttons">
                                    ${item.data.receipt_id ? `<button class="btn-icon btn-view" title="View Receipt" onclick="viewReceipt(${item.data.receipt_id})"><i class="fas fa-receipt"></i></button>` : ''}
                                </td>
                            `;
                            break;
                        case 'promise':
                             rowHtml += `
                                <td class="transaction-icon-cell"><i class="fas fa-handshake transaction-icon icon-promise"></i></td>
                                <td><p class="transaction-date">${itemDate}</p></td>
                                <td>
                                    <p class="transaction-title">Payment Promise Made</p>
                                    <p class="transaction-meta">Promised $${parseFloat(item.data.promised_amount).toFixed(2)} for Invoice #${item.data.invoice_id} by ${new Date(item.data.promised_due_date).toLocaleDateString()}</p>
                                </td>
                                <td class="transaction-amount"></td>
                                <td class="action-buttons"></td>
                            `;
                            break;
                    }
                    rowHtml += '</tr>';
                    historyRows += rowHtml;
                });
            } else {
                historyRows = '<tr><td colspan="5" class="text-center">No financial history found for this student.</td></tr>';
            }

            let balanceClass = 'balance-zero';
            if (data.summary.balance > 0.01) {
                balanceClass = 'balance-due';
            } else if (data.summary.balance < -0.01) {
                balanceClass = 'balance-credit';
            }
            const formattedBalance = Math.abs(data.summary.balance).toFixed(2);
            const balanceSign = data.summary.balance < 0 ? '-' : '';

            contentDiv.innerHTML = `
                <div class="student-detail-header">
                    <div><h3>${data.student.name}</h3><p>ID: ${data.student.student_id_no || 'N/A'} | Status: <span class="badge badge-${data.student.status}">${data.student.status}</span></p></div>
                    <div class="action-buttons">
                        <a href="create_invoice.php?student_id=${studentId}" class="btn btn-primary"><i class="fas fa-plus"></i> New Invoice</a>
                        <a href="#receive_payment" onclick="preparePaymentForStudent(${studentId}, '${data.student.name}')" class="btn btn-success"><i class="fas fa-hand-holding-usd"></i> Receive Payment</a>
                    </div>
                </div>
                <div class="student-balance-summary">
                    <div class="balance-card"><h4>Current Balance</h4><span class="balance-amount ${balanceClass}">${balanceSign}$${formattedBalance}</span></div>
                    <div class="balance-card"><h4>Total Invoiced</h4><span class="balance-amount">$${data.summary.totalInvoiced.toFixed(2)}</span></div>
                    <div class="balance-card"><h4>Total Paid</h4><span class="balance-amount">$${data.summary.totalPaid.toFixed(2)}</span></div>
                </div>
                
                <h3>Transaction History</h3>
                <div class="table-container">
                    <table class="transaction-history-table">
                        <thead><tr><th></th><th>Date</th><th>Details</th><th class="amount-header">Amount</th><th>Actions</th></tr></thead>
                        <tbody>${historyRows}</tbody>
                    </table>
                </div>
            `;

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

function editStudent(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_address').value = student.address;
    document.getElementById('edit_student_id_no').value = student.student_id_no || '';
    document.getElementById('edit_class_id').value = student.class_id || '';
    document.getElementById('edit_status').value = student.status || 'active';
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

// --- Item & Template Management ---
function openEditItemModal(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_item_name').value = item.name;
    document.getElementById('edit_item_price').value = item.price;
    document.getElementById('edit_item_description').value = item.description || '';
    document.getElementById('edit_parent_id').value = item.parent_id || '';
    openModal('editItemModal');
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


// --- Payment & Receipt Functions ---
function loadUnpaidData() {
    const studentId = document.getElementById('student_id_payment').value;
    loadUnpaidInvoices(studentId);
}

function loadUnpaidInvoices(studentId) {
    const tbody = document.querySelector('#unpaidInvoicesTable tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    if (!studentId) { tbody.innerHTML = '<tr><td colspan="7" class="text-center">Please select a student.</td></tr>'; return; }
    fetch(`get_unpaid_invoices.php?student_id=${studentId}`).then(response => response.json()).then(data => {
        tbody.innerHTML = '';
        if (data.success && data.data.length > 0) {
            data.data.forEach(invoice => {
                tbody.innerHTML += `<tr><td>${invoice.id}</td><td>${new Date(invoice.invoice_date).toLocaleDateString()}</td><td>${new Date(invoice.due_date).toLocaleDateString()}</td><td>$${parseFloat(invoice.total_amount).toFixed(2)}</td><td>$${parseFloat(invoice.paid_amount).toFixed(2)}</td><td>$${invoice.balance.toFixed(2)}</td><td><input type="hidden" name="invoice_ids[]" value="${invoice.id}"><input type="number" name="payment_amounts[]" class="form-control payment-amount" min="0" step="0.01" value="0" oninput="calculateTotal()"></td></tr>`;
            });
        } else { tbody.innerHTML = '<tr><td colspan="7" class="text-center">No unpaid invoices for this student.</td></tr>'; }
        calculateTotal();
    });
}

function calculateTotal() {
    const total = Array.from(document.querySelectorAll('.payment-amount')).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    document.getElementById('totalPayment').textContent = '$' + total.toFixed(2);
}

function viewReceipt(receiptId) {
    const modalBody = document.getElementById('receipt-details-body');
    modalBody.innerHTML = '<p style="text-align:center;">Loading receipt...</p>';
    openModal('viewReceiptModal');
    fetch(`get_receipt.php?id=${receiptId}`).then(response => response.json()).then(data => {
        if (data.success) {
            const r = data.receipt;
            modalBody.innerHTML = `<div id="receipt-printable-area"><div style="text-align: center; margin-bottom: 20px;">${r.school_logo_url ? `<img src="${r.school_logo_url}" alt="Logo" style="max-width: 120px; max-height: 60px;"><br>` : ''}<h3 style="margin: 10px 0 0 0;">${r.school_name}</h3><p style="margin: 5px 0; font-size: 0.9em; color: #555;">${r.school_address || ''}</p></div><hr><h4 style="text-align: center; margin-top: 20px;">PAYMENT RECEIPT</h4><p><strong>Receipt #:</strong> ${r.receipt_number}</p><p><strong>Student:</strong> ${r.student_name}</p><p><strong>Date:</strong> ${new Date(r.payment_date).toLocaleDateString()}</p><h3 style="margin-top: 20px; color: var(--success);">Amount Paid: $${parseFloat(r.amount).toFixed(2)}</h3><p><strong>Method:</strong> ${r.payment_method}</p><p><strong>Memo:</strong> ${r.memo || 'N/A'}</p></div>`;
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
    fetch(`get_class_details.php?class_id=${classId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            dashboardContent.innerHTML = `
                <div class="balance-summary-container" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                    <div class="balance-card">
                        <h4>Total Invoiced</h4>
                        <span class="balance-amount">$${data.summary.totalInvoiced.toFixed(2)}</span>
                    </div>
                    <div class="balance-card">
                        <h4>Total Paid</h4>
                        <span class="balance-amount">$${data.summary.totalPaid.toFixed(2)}</span>
                    </div>
                    <div class="balance-card">
                        <h4>Outstanding Balance</h4>
                        <span class="balance-amount balance-due">$${data.summary.outstandingBalance.toFixed(2)}</span>
                    </div>
                </div>
                <h5>Students in this Class</h5>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Student ID</th>
                                <th>Outstanding Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.students.map(student => `
                                <tr>
                                    <td>${student.name}</td>
                                    <td>${student.student_id_no || 'N/A'}</td>
                                    <td>$${student.balance.toFixed(2)}</td>
                                    <td><a href="customer_center.php?tab=students&filter_name=${student.name}" class="btn-icon btn-view" title="View Student"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            dashboardContent.innerHTML = '<p class="alert alert-danger">Error loading class details.</p>';
        }
    });
}


// --- Event Listeners & Initializers ---
document.addEventListener('DOMContentLoaded', function() {
    // Tab activation
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'students';
    const tabButton = document.querySelector(`.tab-link[onclick*="'${tab}'"]`);
    if (tabButton) tabButton.click(); else document.querySelector('.tab-link').click();

    // Student bulk actions
    document.getElementById('select-all-students')?.addEventListener('change', function() {
        document.querySelectorAll('.student-checkbox').forEach(checkbox => checkbox.checked = this.checked);
    });

    // Statement form logic
    const statementTypeSelect = document.getElementById('statement_type');
    if(statementTypeSelect) {
        statementTypeSelect.addEventListener('change', function() {
            document.getElementById('statement_class_selector').style.display = this.value === 'class' ? 'block' : 'none';
            document.getElementById('statement_student_selector').style.display = this.value === 'student' ? 'block' : 'none';
        });
        statementTypeSelect.dispatchEvent(new Event('change'));
    }

    // Messaging form logic
    const sendToGroup = document.getElementById('send_to_group');
    if (sendToGroup) {
        sendToGroup.addEventListener('change', function() {
            document.getElementById('class-messaging-container').style.display = this.value === 'class' ? 'block' : 'none';
        });
    }

    // Resizable panel logic
    const resizer = document.getElementById('resizer'), leftPanel = document.getElementById('left-panel'), container = document.getElementById('resizable-container');
    let isResizing = false;
    if(resizer) {
        resizer.addEventListener('mousedown', e => { e.preventDefault(); isResizing = true; window.addEventListener('mousemove', handleMouseMove); window.addEventListener('mouseup', stopResizing); });
        function handleMouseMove(e) {
            if (!isResizing) return;
            let newLeftWidth = e.clientX - container.getBoundingClientRect().left;
            if (newLeftWidth < 350) newLeftWidth = 350;
            if (newLeftWidth > (container.clientWidth - 400)) newLeftWidth = container.clientWidth - 400;
            leftPanel.style.width = newLeftWidth + 'px';
        }
        function stopResizing() { isResizing = false; window.removeEventListener('mousemove', handleMouseMove); window.removeEventListener('mouseup', stopResizing); }
    }

    // Set the initial state of the "Show Archived" checkbox
    const showArchivedCheckbox = document.getElementById('show_archived');
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show_archived') === '1') {
        showArchivedCheckbox.checked = true;
    } else {
        showArchivedCheckbox.checked = false;
    }

    // --- NEW: Logic for Bulk Message Templates ---
    const templateSelect = document.getElementById('message_template_select');
    const messageBody = document.getElementById('message_body');

    if (templateSelect && messageBody) {
        templateSelect.addEventListener('change', function() {
            // Get the text content of the selected option
            const selectedTemplate = this.value;
            
            // Update the textarea with the selected template
            // If the user selects the placeholder, it clears the textarea
            messageBody.value = selectedTemplate;
        });
    }
});
</script>