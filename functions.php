<?php
// functions.php - Final, Consolidated, and Multi-Tenant Aware
// Contains all helper functions for the application, each defined only once.

require_once 'config.php';
require_once 'vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;

/**
 * Formats a phone number to be compatible with Africa's Talking API.
 * Ensures the number is in the international format (e.g., +254712345678).
 *
 * @param string $phoneNumber The phone number to format.
 * @return string The formatted phone number, or an empty string if invalid.
 */
function formatPhoneNumberForAT(string $phoneNumber): string
{
    $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    if (empty($cleanNumber)) {
        return '';
    }

    // Handle numbers with country code prefix
    if (preg_match('/^(?:254|\\+254|0)?(7\\d{8})$/', $cleanNumber, $matches)) {
        return '+254' . $matches[1];
    }

    // Handle 9-digit numbers starting with 7 (without prefix)
    if (preg_match('/^(7\\d{8})$/', $cleanNumber)) {
        return '+254' . $cleanNumber;
    }

    return '';
}

/**
 * Sends a bulk SMS message using the Africa's Talking API.
 *
 * @param array $recipients An array of phone numbers in international format (e.g., ['+2547...','+2547...']).
 * @param string $message The text message to be sent.
 * @return array|string The result from the API or an error message.
 */
function sendBulkSms(array $recipients, string $message) {
    // Check if credentials are defined
    if (!defined('AT_USERNAME') || !defined('AT_API_KEY') || !defined('AT_SENDER_ID')) {
        return ['error' => "Africa's Talking API credentials (Username, API Key, or Sender ID) are not defined in config.php"];
    }
     if (AT_SENDER_ID === 'MYSCHOOL' || empty(AT_SENDER_ID)) {
        return ['error' => "Please set your approved Africa's Talking Sender ID in config.php before sending messages."];
    }
    
    // Define options to force the use of TLS version 1.2
    $curl_options = [
        'curl' => [
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ],
    ];
    
    try {
        // Initialize the SDK with your username and API key
        $AT = new AfricasTalking(AT_USERNAME, AT_API_KEY);
        
        // Get the SMS service
        $sms = $AT->sms();

        // Prepare the options for the API call, now including the Sender ID
        $options = [
            'to'      => implode(',', $recipients),
            'message' => $message,
            'from'    => AT_SENDER_ID // Explicitly set the Sender ID
        ];

        // Attempt to send the message
        $result = $sms->send($options);
        
        // THE REDUNDANT BLOCK HAS BEEN REMOVED FROM HERE.
        
        return $result;
        
    } catch (Exception $e) {
        // If an error occurs, return the error message for debugging
        error_log("Africa's Talking API Error: " . $e->getMessage());
        // Return a generic error format that customer_center.php can handle
        return ['error' => $e->getMessage()];
    }
}

 function getUndepositedFundsAccountId(PDO $pdo, int $school_id): int {
    // 1. Try to find the existing account
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE school_id = ? AND account_name = 'Undeposited Funds'");
    $stmt->execute([$school_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        // 2. If found, return its ID
        return (int)$account['id'];
    } else {
        // 3. If not found, create it
        // Generate a unique account code to avoid conflicts
        $new_account_code = '1900-' . $school_id;
        
        $stmt_create = $pdo->prepare(
            "INSERT INTO accounts (school_id, account_code, account_name, account_type, balance) 
             VALUES (?, ?, 'Undeposited Funds', 'asset', 0.00)"
        );
        $stmt_create->execute([$school_id, $new_account_code]);
        
        $new_account_id = (int)$pdo->lastInsertId();

        if ($new_account_id > 0) {
            // Log the automatic creation of this critical account
            log_audit($pdo, 'SYSTEM', 'accounts', $new_account_id, ['data' => ['note' => 'Auto-created Undeposited Funds account.']]);
            return $new_account_id;
        } else {
            throw new Exception("Could not create a required 'Undeposited Funds' account.");
        }
    }
}

function getAccountsByType(PDO $pdo, int $school_id, string $account_type): array {
    $stmt = $pdo->prepare(
        "SELECT id, account_code, account_name, balance 
         FROM accounts 
         WHERE school_id = ? AND account_type = ? 
         ORDER BY account_name ASC"
    );
    $stmt->execute([$school_id, $account_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function format_audit_details(array $log): string
{
    $details = json_decode($log['details'], true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($details)) {
        return 'No details available.';
    }

    $output = '<div class="details-container"><ul>';

    switch ($log['action_type']) {
        case 'UPDATE':
            $before = $details['before'] ?? [];
            $after = $details['after'] ?? [];
            $has_changes = false;

            foreach ($after as $key => $newValue) {
                // Ignore keys we don't want to show in the log
                if (in_array($key, ['password', 'updated_at'])) continue;

                $oldValue = $before[$key] ?? null;

                if ($newValue != $oldValue) {
                    $has_changes = true;
                    $output .= '<li>';
                    $output .= 'Changed <strong class="field-name">' . htmlspecialchars($key) . '</strong>';
                    $output .= ' from <span class="old-value">' . htmlspecialchars($oldValue ?? 'NULL') . '</span>';
                    $output .= ' to <span class="new-value">' . htmlspecialchars($newValue ?? 'NULL') . '</span>';
                    $output .= '</li>';
                }
            }
            if (!$has_changes) $output .= '<li>No displayable fields were changed.</li>';
            break;

        case 'CREATE':
        case 'DELETE':
            $data = $details['data'] ?? [];
            if (empty($data)) {
                 $output .= '<li>No data recorded.</li>';
            } else {
                foreach ($data as $key => $value) {
                    if (in_array($key, ['password'])) continue;
                    if (is_array($value)) $value = json_encode($value); // Handle nested arrays like invoice items
                    $output .= '<li><strong class="field-name">' . htmlspecialchars($key) . '</strong>: ' . htmlspecialchars($value ?? 'NULL') . '</li>';
                }
            }
            break;

        default:
            $output .= '<li>' . htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT)) . '</li>';
            break;
    }

    $output .= '</ul></div>';
    return $output;
}


function log_audit(PDO $pdo, string $action_type, string $target_table, ?int $target_id, array $details) {
    // Session must be started on the calling page
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $school_id = $_SESSION['school_id'] ?? 0;
    $user_id = $_SESSION['user_id'] ?? 0; // Assuming you store user_id in session on login
    $user_name = $_SESSION['user_name'] ?? 'System'; // Assuming you store user_name
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // Don't log if critical session info is missing
    if ($school_id === 0 || $user_id === 0) {
        return;
    }

    $sql = "INSERT INTO audit_log 
                (school_id, user_id, user_name, ip_address, action_type, target_table, target_id, details) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $school_id,
            $user_id,
            $user_name,
            $ip_address,
            $action_type,
            $target_table,
            $target_id,
            json_encode($details)
        ]);
    } catch (PDOException $e) {
        // In a real production system, you might log this error to a file
        // For now, we'll fail silently to not interrupt the user's action.
        error_log('Audit Log Failed: ' . $e->getMessage());
    }
}

// =================================================================
// DASHBOARD & GENERAL SUMMARY FUNCTIONS
// =================================================================

function getDashboardSummary(PDO $pdo, int $school_id, string $start_date, string $end_date): array {
    try {
        // Total students is not dependent on date
        $stmt_students = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ? AND status = 'active'");
        $stmt_students->execute([$school_id]);
        $total_students = $stmt_students->fetchColumn();

        // Total income for the selected period
        $stmt_income = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE school_id = ? AND payment_date BETWEEN ? AND ?");
        $stmt_income->execute([$school_id, $start_date, $end_date]);
        $total_income = $stmt_income->fetchColumn();

        // **FIXED**: Total expenses for the selected period, now correctly filtered by account type
        $stmt_expenses = $pdo->prepare("
            SELECT COALESCE(SUM(e.amount), 0) 
            FROM expenses e
            JOIN accounts a ON e.account_id = a.id
            WHERE e.school_id = ? 
              AND e.transaction_type = 'debit' 
              AND a.account_type = 'expense'
              AND e.transaction_date BETWEEN ? AND ?
        ");
        $stmt_expenses->execute([$school_id, $start_date, $end_date]);
        $total_expenses = $stmt_expenses->fetchColumn();

        // --- NEW QUERY: Calculate total outstanding fees ---
        // This is a cumulative total and is not affected by the date range.
        $stmt_outstanding = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE school_id = ?");
        $stmt_outstanding->execute([$school_id]);
        $outstanding_fees = $stmt_outstanding->fetchColumn();


        return [
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'current_balance' => $total_income - $total_expenses,
            'total_students' => $total_students,
            'outstanding_fees' => $outstanding_fees // NEW: Add the new value to the return array
        ];
    } catch (PDOException $e) {
        error_log("Error in getDashboardSummary: " . $e->getMessage());
        return [
            'total_income' => 0, 
            'total_expenses' => 0, 
            'current_balance' => 0, 
            'total_students' => 0,
            'outstanding_fees' => 0 // NEW: Ensure a default value on error
        ];
    }
}


// =================================================================
// STUDENT & CLASS FUNCTIONS
// =================================================================

function getStudents(PDO $pdo, int $school_id, ?string $filter_name = null, $filter_class_id = null, ?string $filter_status = null): array {
    $sql = "SELECT s.*, c.name as class_name 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.school_id = :school_id";
    $params = [':school_id' => $school_id];

    if (!empty($filter_name)) {
        $sql .= " AND (s.name LIKE :filter_name OR s.student_id_no LIKE :filter_name)";
        $params[':filter_name'] = '%' . $filter_name . '%';
    }

    if (!empty($filter_class_id) && is_numeric($filter_class_id)) {
        $sql .= " AND s.class_id = :filter_class_id";
        $params[':filter_class_id'] = (int)$filter_class_id;
    }

    // Add the new status filter logic
    if (!empty($filter_status) && in_array($filter_status, ['active', 'inactive'])) {
        $sql .= " AND s.status = :filter_status";
        $params[':filter_status'] = $filter_status;
    }

    $sql .= " ORDER BY s.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getStudentById($pdo, $student_id, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
    $stmt->execute([$student_id, $school_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_invoice_status_color($status) {
    switch ($status) {
        case 'Unpaid':
        case 'Overdue':
            return 'danger';
        case 'Partially Paid':
            return 'warning';
        case 'Paid':
            return 'success';
        default:
            return 'secondary';
    }
}

function getClasses($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY name");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentsByClass($pdo, $class_id, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ? AND school_id = ?");
    $stmt->execute([$class_id, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentInvoices($pdo, $student_id, $school_id) {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               (SELECT SUM(quantity * unit_price) FROM invoice_items WHERE invoice_id = i.id) as total_amount
        FROM invoices i
        WHERE i.student_id = ? AND i.school_id = ?
        ORDER BY i.invoice_date DESC
    ");
    $stmt->execute([$student_id, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentTransactions($pdo, $student_id, $school_id) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.payment_date as date, p.amount, p.payment_method, p.memo, p.invoice_id, i.id as invoice_number
        FROM payments p
        LEFT JOIN invoices i ON p.invoice_id = i.id
        WHERE p.student_id = ? AND p.school_id = ?
        ORDER BY p.payment_date ASC
    ");
    $stmt->execute([$student_id, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =================================================================
// INVOICE & PAYMENT FUNCTIONS
// =================================================================

function getInvoices(PDO $pdo, int $school_id, $student_id = null, ?string $status = null, ?string $start_date = null, ?string $end_date = null): array {
    $sql = "SELECT 
                i.id, i.invoice_date, i.due_date, i.total_amount, i.paid_amount, i.status, s.name as student_name
            FROM invoices i
            JOIN students s ON i.student_id = s.id
            WHERE i.school_id = :school_id";
    
    $params = [':school_id' => $school_id];

    if (!empty($student_id) && is_numeric($student_id)) {
        $sql .= " AND i.student_id = :student_id";
        $params[':student_id'] = (int)$student_id;
    }
    if (!empty($status)) {
        if ($status === 'Unpaid') {
            // A broader definition for "Unpaid" to include draft, sent, overdue, etc.
             $sql .= " AND i.status IN ('Draft', 'Sent', 'Overdue', 'Partially Paid')";
        } else {
            $sql .= " AND i.status = :status";
            $params[':status'] = $status;
        }
    }
    if (!empty($start_date)) {
        $sql .= " AND i.invoice_date >= :start_date";
        $params[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $sql .= " AND i.invoice_date <= :end_date";
        $params[':end_date'] = $end_date;
    }

    $sql .= " ORDER BY i.invoice_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInvoiceDetails(PDO $pdo, int $invoice_id, int $school_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            i.id, i.school_id, i.student_id, i.invoice_number, i.invoice_date, i.due_date, i.total_amount, i.status, /* THE FIX IS HERE: Added i.school_id */
            s.name as student_name, s.address as student_address,
            sc.name as school_name,
            c.name as class_name
        FROM invoices i
        JOIN students s ON i.student_id = s.id
        JOIN schools sc ON i.school_id = sc.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE i.id = ? AND i.school_id = ?
    ");
    $stmt->execute([$invoice_id, $school_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice) {
        $stmt_items = $pdo->prepare("
            SELECT
                ii.quantity, ii.unit_price,
                it.name as item_name, it.description
            FROM invoice_items ii
            JOIN items it ON ii.item_id = it.id
            WHERE ii.invoice_id = ? AND ii.school_id = ?
        ");
        $stmt_items->execute([$invoice_id, $school_id]);
        $invoice['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }

    return $invoice;
}
function createInvoice($pdo, $school_id, $student_id, $invoice_date, $due_date, $items, $notes = '') {
    // NOTE: Transaction handling has been removed from this function. 
    // It should be called from within an existing transaction.
    
    // 1. Calculate the total amount from the items array.
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
    }

    // 2. Get the new, school-specific invoice number
    $invoice_number = getInvoiceNumber($pdo, $school_id);
    
    // 2.5 Generate a secure, unique token for the invoice
    $token = bin2hex(random_bytes(32));

    // 3. Add the invoice_number AND the calculated total_amount to the INSERT statement
    $stmt = $pdo->prepare(
        "INSERT INTO invoices (school_id, student_id, invoice_number, invoice_date, due_date, total_amount, notes, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$school_id, $student_id, $invoice_number, $invoice_date, $due_date, $total_amount, $notes, $token]);
    $invoice_id = $pdo->lastInsertId();
    
    if (!$invoice_id) {
        throw new Exception("Database failed to create the invoice record for student ID {$student_id}.");
    }

    // 4. Insert invoice items and track one-time/annual fees
    $stmt_items = $pdo->prepare("INSERT INTO invoice_items (school_id, invoice_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    $stmt_check_fee_type = $pdo->prepare("SELECT fee_frequency FROM items WHERE id = ?");
    $stmt_track_onetime = $pdo->prepare("INSERT INTO one_time_fees_billed (school_id, student_id, item_id, invoice_id, academic_year, billed_date, amount) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE invoice_id=VALUES(invoice_id)");
    $stmt_track_annual = $pdo->prepare("INSERT INTO annual_fees_billed (school_id, student_id, item_id, invoice_id, academic_year, billed_date, amount) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE invoice_id=VALUES(invoice_id)");

    // Get current academic year for tracking
    $current_academic_year = date('Y') . '-' . (date('Y') + 1);

    foreach ($items as $item) {
        $item_id = $item['item_id'];
        $quantity = $item['quantity'] ?? 1;
        $unit_price = $item['unit_price'];

        // Insert invoice item
        $stmt_items->execute([$school_id, $invoice_id, $item_id, $quantity, $unit_price]);

        // Track one-time and annual fees (skip if item_id is 0 - transport/activities)
        if ($item_id > 0) {
            $stmt_check_fee_type->execute([$item_id]);
            $fee_frequency = $stmt_check_fee_type->fetchColumn();

            if ($fee_frequency === 'one_time') {
                $stmt_track_onetime->execute([
                    $school_id,
                    $student_id,
                    $item_id,
                    $invoice_id,
                    $current_academic_year,
                    $invoice_date,
                    $quantity * $unit_price
                ]);
            } elseif ($fee_frequency === 'annual') {
                $stmt_track_annual->execute([
                    $school_id,
                    $student_id,
                    $item_id,
                    $invoice_id,
                    $current_academic_year,
                    $invoice_date,
                    $quantity * $unit_price
                ]);
            }
        }
    }

    // 5. The Accounting Logic (Journal Entry)
    if ($total_amount > 0) {
        // Get or create the necessary accounts
        $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
        $tuition_revenue_id = getOrCreateAccount($pdo, $school_id, 'Tuition Revenue', 'revenue', '4000');
        
        // Create a description for the journal entry
        $description = "Invoice #{$invoice_number} created for student ID {$student_id}.";
        
        // Debit Accounts Receivable (asset increases), Credit Tuition Revenue (revenue increases)
        create_journal_entry($pdo, $school_id, $invoice_date, $description, $total_amount, $accounts_receivable_id, $tuition_revenue_id);
    }
    
    return $invoice_id; // Return the new invoice ID
}

function getUnpaidInvoices($pdo, $student_id, $school_id) {
    $stmt = $pdo->prepare("
        SELECT i.id, i.invoice_date, i.due_date, i.total_amount, i.paid_amount, (i.total_amount - i.paid_amount) AS balance
        FROM invoices i
        WHERE i.student_id = ? AND i.school_id = ? AND (i.total_amount - i.paid_amount) > 0.009
        ORDER BY i.invoice_date ASC
    ");
    $stmt->execute([$student_id, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInvoiceBalance($pdo, $invoice_id, $school_id) {
    $stmt = $pdo->prepare("SELECT (total_amount - paid_amount) AS balance FROM invoices WHERE id = ? AND school_id = ?");
    $stmt->execute([$invoice_id, $school_id]);
    return $stmt->fetchColumn();
}

function getAllReceipts($pdo, $school_id) {
    $stmt = $pdo->prepare("
        SELECT r.*, s.name AS student_name
        FROM payment_receipts r
        JOIN students s ON s.id = r.student_id
        WHERE r.school_id = ?
        ORDER BY r.payment_date DESC
    ");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReceiptDetails($pdo, $receipt_id, $school_id) {
    $stmt = $pdo->prepare("
        SELECT 
            r.*, 
            s.name AS student_name,
            sch.name as school_name,
            sch_d.address as school_address,
            sch_d.phone as school_phone,
            sch_d.email as school_email,
            sch_d.logo_url as school_logo_url
        FROM payment_receipts r
        JOIN students s ON s.id = r.student_id
        LEFT JOIN schools sch ON r.school_id = sch.id
        LEFT JOIN school_details sch_d ON r.school_id = sch_d.school_id
        WHERE r.id = ? AND r.school_id = ?
    ");
    $stmt->execute([$receipt_id, $school_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


// =================================================================
// ITEMS & CATEGORIES FUNCTIONS
// =================================================================

function getItems($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE school_id = ? ORDER BY name");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createItem($pdo, $school_id, $name, $price, $description = '', $parent_id = null, $item_type = 'parent') {
    $stmt = $pdo->prepare("INSERT INTO items (school_id, name, price, description, parent_id, item_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$school_id, $name, $price, $description, $parent_id, $item_type]);
    return $pdo->lastInsertId();
}

function getItemsWithSubItems($pdo, $school_id) {
    // In the new system, the concept of parent/child items is replaced by the Fee Structure table.
    // This function's role is now to get a simple, flat list of all base items
    // to be used in dropdowns, like when creating an invoice manually.
    // The name is kept for compatibility with pages that still call it.
    
    $stmt = $pdo->prepare("SELECT id, name, description FROM items WHERE school_id = ? ORDER BY name");
    $stmt->execute([$school_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // To maintain compatibility with the old template structure that expects a 'sub_items' key,
    // we will format the output similarly but without the hierarchy.
    foreach ($items as &$item) {
        $item['price'] = '0.00'; // Price is now managed in fee_structure_items
        $item['sub_items'] = []; // There are no sub-items anymore
    }

    return $items;
}

function updateItem($pdo, $item_id, $name, $price, $description, $parent_id, $item_type, $school_id) {
    $stmt = $pdo->prepare("UPDATE items SET name = ?, price = ?, description = ?, parent_id = ?, item_type = ? WHERE id = ? AND school_id = ?");
    return $stmt->execute([$name, $price, $description, $parent_id, $item_type, $item_id, $school_id]);
}

function deleteItem(PDO $pdo, int $item_id, int $school_id): bool {
    try {
        $pdo->beginTransaction();
        $stmt_children = $pdo->prepare("DELETE FROM items WHERE parent_id = ? AND school_id = ?");
        $stmt_children->execute([$item_id, $school_id]);
        $stmt_parent = $pdo->prepare("DELETE FROM items WHERE id = ? AND school_id = ?");
        $stmt_parent->execute([$item_id, $school_id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }   
}

// =================================================================
// PAYROLL AND EMPLOYEE FUNCTIONS
// =================================================================

/**
 * Calculates Kenyan statutory deductions based on gross pay using settings from the database.
 *
 * @param PDO $pdo The database connection object.
 * @param int $school_id The ID of the current school.
 * @param float $gross_pay The total gross earnings for the month.
 * @return array An array containing all calculated payroll components.
 */
function calculate_kenyan_deductions(PDO $pdo, int $school_id, float $gross_pay): array {
    // 1. Fetch all payroll settings for the school at once
    $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM payroll_settings WHERE school_id = ?");
    $stmt_settings->execute([$school_id]);
    $settings_raw = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    // Prepare a settings array with defaults in case some are missing
    $settings = [
        'nssf_rate' => (float)($settings_raw['nssf_rate'] ?? 0.06),
        'nssf_cap' => (float)($settings_raw['nssf_cap'] ?? 1080),
        'housing_levy_rate' => (float)($settings_raw['housing_levy_rate'] ?? 0.015),
        'personal_relief' => (float)($settings_raw['personal_relief'] ?? 2400),
        'insurance_relief_rate' => (float)($settings_raw['insurance_relief_rate'] ?? 0.15),
        'nhif_brackets' => json_decode($settings_raw['nhif_brackets'] ?? '[]', true),
        'paye_brackets' => json_decode($settings_raw['paye_brackets'] ?? '[]', true),
    ];

    // 2. NSSF Calculation
    $nssf = min($gross_pay * $settings['nssf_rate'], $settings['nssf_cap']);
    $taxable_pay = $gross_pay - $nssf;

    // 3. NHIF Calculation (from JSON brackets)
    $nhif = 0;
    foreach ($settings['nhif_brackets'] as $bracket) {
        if ($bracket['max_gross'] === "Infinity" || $gross_pay <= $bracket['max_gross']) {
            $nhif = $bracket['deduction'];
            break;
        }
    }

    // 4. PAYE Calculation (from JSON brackets)
    $paye = 0;
    $annual_taxable_pay = $taxable_pay * 12;

    foreach ($settings['paye_brackets'] as $bracket) {
        if ($bracket['max_annual'] === "Infinity" || $annual_taxable_pay <= $bracket['max_annual']) {
            $paye = $bracket['base_tax'] + (($annual_taxable_pay - $bracket['prev_max']) * $bracket['rate']);
            break;
        }
    }
    
    $monthly_paye = $paye / 12;
    $insurance_relief = $nhif * $settings['insurance_relief_rate'];
    $final_paye = max(0, $monthly_paye - ($settings['personal_relief'] + $insurance_relief));

    // 5. Housing Levy Calculation
    $housing_levy = $gross_pay * $settings['housing_levy_rate'];

    // 6. Total Deductions
    $total_deductions_statutory = $final_paye + $nhif + $nssf + $housing_levy;

    return [
        'paye' => round($final_paye, 2),
        'nhif' => round($nhif, 2),
        'nssf' => round($nssf, 2),
        'housing_levy' => round($housing_levy, 2),
        'total_deductions' => round($total_deductions_statutory, 2)
    ];
}


function getPayrollRecords(PDO $pdo, $school_id): array {
    $stmt = $pdo->prepare("SELECT p.*, e.department, e.position FROM payroll p LEFT JOIN employees e ON p.employee_id = e.id WHERE p.school_id = ? ORDER BY p.pay_date DESC");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEmployees(PDO $pdo, int $school_id): array {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE school_id = ? ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// *** NEW FUNCTION ***
function getPayrollDashboardMetrics(PDO $pdo, int $school_id): array {
    $current_month_period = date('Y-m');

    $stmt_active = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE school_id = ? AND status = 'active'");
    $stmt_active->execute([$school_id]);
    $active_employees = $stmt_active->fetchColumn();

    $stmt_payroll = $pdo->prepare("
        SELECT 
            COUNT(id) as payrolls_run,
            COALESCE(SUM(net_pay), 0) as total_paid,
            COALESCE(SUM(total_deductions), 0) as total_deductions
        FROM payroll 
        WHERE school_id = ? AND pay_period = ?
    ");
    $stmt_payroll->execute([$school_id, $current_month_period]);
    $payroll_data = $stmt_payroll->fetch(PDO::FETCH_ASSOC);

    return [
        'active_employees' => $active_employees,
        'payrolls_run_this_month' => $payroll_data['payrolls_run'],
        'total_paid_this_month' => $payroll_data['total_paid'],
        'total_deductions_this_month' => $payroll_data['total_deductions']
    ];
}


// =================================================================
// REPORTING FUNCTIONS
// =================================================================

function generateCustomReport(PDO $pdo, int $school_id, array $options): array
{
    $report_type = $options['report_type'] ?? '';
    $selected_columns = $options['columns'] ?? [];
    if (empty($report_type) || empty($selected_columns)) {
        return ['headers' => [], 'data' => []];
    }

    // --- Whitelists for Security ---
    $allowed_columns = [
        'payments' => ['r.receipt_number', 's.name', 'p.payment_date', 'p.amount', 'p.payment_method', 'c.name'],
        'invoices' => ['i.invoice_number', 's.name', 'i.invoice_date', 'i.due_date', 'i.total_amount', 'i.paid_amount', 'i.balance', 'i.status', 'c.name'],
        'expenses' => ['a.account_name', 'e.transaction_date', 'e.description', 'e.amount', 'e.payment_method'],
        'students' => ['s.student_id_no', 's.name', 'c.name', 's.status', 's.phone', 's.email'],
    ];

    $column_map = [
        'payments' => ['r.receipt_number' => 'Receipt #', 's.name' => 'Student', 'p.payment_date' => 'Date', 'p.amount' => 'Amount', 'p.payment_method' => 'Method', 'c.name' => 'Class'],
        'invoices' => ['i.invoice_number' => 'Invoice #', 's.name' => 'Student', 'i.invoice_date' => 'Date', 'i.due_date' => 'Due Date', 'i.total_amount' => 'Total', 'i.paid_amount' => 'Paid', 'i.balance' => 'Balance', 'i.status' => 'Status', 'c.name' => 'Class'],
        'expenses' => ['a.account_name' => 'Account', 'e.transaction_date' => 'Date', 'e.description' => 'Description', 'e.amount' => 'Amount', 'e.payment_method' => 'Method'],
        'students' => ['s.student_id_no' => 'Student ID', 's.name' => 'Name', 'c.name' => 'Class', 's.status' => 'Status', 's.phone' => 'Phone', 's.email' => 'Email'],
    ];

    // --- Build Query ---
    $query = "";
    $params = [':school_id' => $school_id];
    $headers = [];

    // 1. Validate and build SELECT clause
    $select_clause = [];
    foreach ($selected_columns as $col) {
        if (in_array($col, $allowed_columns[$report_type])) {
            // *** THE FIX: Use an alias to prevent column name collisions ***
            $alias = str_replace('.', '_', $col);
            $select_clause[] = "$col AS $alias"; 
            $headers[] = $column_map[$report_type][$col];
        }
    }
    if (empty($select_clause)) throw new Exception("No valid columns selected.");
    
    $query .= "SELECT " . implode(', ', $select_clause);

    // 2. Build FROM and JOIN clauses
    switch ($report_type) {
        case 'payments':
            $query .= " FROM payments p 
                        JOIN students s ON p.student_id = s.id 
                        LEFT JOIN classes c ON s.class_id = c.id
                        LEFT JOIN payment_receipts r ON p.receipt_id = r.id";
            break;
        case 'invoices':
            $query .= " FROM invoices i
                        JOIN students s ON i.student_id = s.id
                        LEFT JOIN classes c ON s.class_id = c.id";
            break;
        case 'expenses':
            $query .= " FROM expenses e
                        JOIN accounts a ON e.account_id = a.id";
            break;
        case 'students':
            $query .= " FROM students s
                        LEFT JOIN classes c ON s.class_id = c.id";
            break;
    }

    // 3. Build WHERE clause
    $where_conditions = [];
    switch ($report_type) {
        case 'payments': $where_conditions[] = "p.school_id = :school_id"; break;
        case 'invoices': $where_conditions[] = "i.school_id = :school_id"; break;
        case 'expenses': $where_conditions[] = "e.school_id = :school_id AND a.account_type = 'expense'"; break;
        case 'students': $where_conditions[] = "s.school_id = :school_id"; break;
    }
    
    // Date filter
    if (!empty($options['start_date']) && !empty($options['end_date'])) {
        $date_field = '';
        if ($report_type === 'payments') $date_field = 'p.payment_date';
        if ($report_type === 'invoices') $date_field = 'i.invoice_date';
        if ($report_type === 'expenses') $date_field = 'e.transaction_date';
        
        if ($date_field) {
            $where_conditions[] = "$date_field BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $options['start_date'];
            $params[':end_date'] = $options['end_date'];
        }
    }

    // Other filters
    if (!empty($options['student_id'])) {
        $where_conditions[] = "s.id = :student_id";
        $params[':student_id'] = $options['student_id'];
    }
    if (!empty($options['class_id'])) {
        $where_conditions[] = "c.id = :class_id";
        $params[':class_id'] = $options['class_id'];
    }
    if (!empty($options['status'])) {
        $status_field = ($report_type === 'students') ? 's.status' : 'i.status';
        if ($options['status'] === 'Unpaid'){
             $where_conditions[] = "$status_field IN ('Draft', 'Sent', 'Overdue', 'Partially Paid')";
        } else {
            $where_conditions[] = "$status_field = :status";
            $params[':status'] = $options['status'];
        }
    }

    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    // 4. Build ORDER BY clause
    $sort_by = $options['sort_by'] ?? '';
    if (!empty($sort_by) && in_array($sort_by, $allowed_columns[$report_type])) {
        $sort_order = ($options['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY $sort_by $sort_order";
    }

    // 5. Execute and return
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['headers' => $headers, 'data' => $data];
}


// These are other reporting functions used by the standard reports.
function getArAgingData(PDO $pdo, int $school_id) {
    // This is a consolidated function to fetch AR Aging data
    $arAgingData = [];
    $arStmt = $pdo->prepare("
        SELECT s.name as student_name, (i.total_amount - i.paid_amount) as balance, i.due_date
        FROM invoices i
        JOIN students s ON i.student_id = s.id
        WHERE i.school_id = ? AND (i.total_amount - i.paid_amount) > 0.01
    ");
    $arStmt->execute([$school_id]);
    
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    while ($row = $arStmt->fetch(PDO::FETCH_ASSOC)) {
        $dueDate = new DateTime($row['due_date']);
        $dueDate->setTime(0, 0, 0);
        $studentName = $row['student_name'];
        if (!isset($arAgingData[$studentName])) {
            $arAgingData[$studentName] = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, 'older' => 0, 'total' => 0];
        }

        if ($dueDate >= $today) {
            $arAgingData[$studentName]['current'] += $row['balance'];
        } else {
            $daysOverdue = $today->diff($dueDate)->days;
            if ($daysOverdue <= 30) $arAgingData[$studentName]['30'] += $row['balance'];
            elseif ($daysOverdue <= 60) $arAgingData[$studentName]['60'] += $row['balance'];
            elseif ($daysOverdue <= 90) $arAgingData[$studentName]['90'] += $row['balance'];
            else $arAgingData[$studentName]['older'] += $row['balance'];
        }
        $arAgingData[$studentName]['total'] += $row['balance'];
    }
    return $arAgingData;
}

function getStudentBalanceReport(PDO $pdo, int $school_id) {
    $stmt = $pdo->prepare("
        SELECT s.name, s.phone, c.name as class_name, SUM(i.total_amount - i.paid_amount) as total_balance
        FROM students s
        LEFT JOIN invoices i ON s.id = i.student_id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.school_id = ? AND i.school_id = ?
        GROUP BY s.id, s.name, s.phone, c.name
        HAVING total_balance > 0.01
        ORDER BY total_balance DESC
    ");
    $stmt->execute([$school_id, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPayrollSummary(PDO $pdo, int $school_id, string $payPeriod) {
    $stmt = $pdo->prepare("
        SELECT COUNT(id) as employee_count, COALESCE(SUM(gross_pay), 0) as total_gross,
               COALESCE(SUM(total_deductions), 0) as total_deductions, COALESCE(SUM(net_pay), 0) as total_net
        FROM payroll WHERE school_id = ? AND pay_period = ?
    ");
    $stmt->execute([$school_id, $payPeriod]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStatutoryDeductionsReport(PDO $pdo, int $school_id, string $payPeriod) {
    // *** THIS IS THE FIX ***
    // The query now selects the correct, new column names.
    $stmt = $pdo->prepare("SELECT employee_name, paye, nhif, nssf, housing_levy FROM payroll WHERE school_id = ? AND pay_period = ?");
    $stmt->execute([$school_id, $payPeriod]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getPaymentPromisesReport(PDO $pdo, int $school_id) {
    $stmt = $pdo->prepare("
        SELECT pp.*, s.name as student_name FROM payment_promises pp
        JOIN students s ON pp.student_id = s.id
        WHERE pp.school_id = ? AND pp.status IN ('Pending', 'Broken') ORDER BY pp.promised_due_date ASC
    ");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepositSummary(PDO $pdo, int $school_id, string $start_date, string $end_date) {
    $stmt = $pdo->prepare("
        SELECT d.deposit_date, d.amount, d.memo, a.account_name FROM deposits d
        JOIN accounts a ON d.account_id = a.id
        WHERE d.school_id = ? AND d.deposit_date BETWEEN ? AND ? ORDER BY d.deposit_date DESC
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProfitLossData($pdo, $startDate, $endDate, $school_id) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN ? AND ? AND school_id = ?");
    $stmt->execute([$startDate, $endDate, $school_id]);
    $income = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE transaction_date BETWEEN ? AND ? AND school_id = ?");
    $stmt->execute([$startDate, $endDate, $school_id]);
    $expenses = $stmt->fetchColumn();

    return ['income' => $income, 'expenses' => $expenses, 'net_income' => $income - $expenses];
}


function getDetailedPLData($pdo, $startDate, $endDate, $school_id) {
    $data = [
        'revenue' => ['accounts' => [], 'total' => 0],
        'expense' => ['accounts' => [], 'total' => 0],
        'net_income' => 0
    ];
    
    $stmt_total_revenue = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN :startDate AND :endDate AND school_id = :school_id");
    $stmt_total_revenue->execute([':startDate' => $startDate, ':endDate' => $endDate, ':school_id' => $school_id]);
    $total_revenue = $stmt_total_revenue->fetchColumn();
    
    $income_categories = getIncomeByCategory($pdo, $startDate, $endDate, $school_id);
    foreach($income_categories as $cat) {
        $data['revenue']['accounts'][] = [
            'account_name' => $cat['category_name'],
            'total' => $cat['total_income']
        ];
    }
    $data['revenue']['total'] = $total_revenue;

    $sql_expenses = "
        SELECT a.account_name, SUM(e.amount) as total
        FROM expenses e
        JOIN accounts a ON e.account_id = a.id
        WHERE e.school_id = :school_id
          AND a.account_type = 'expense'
          AND e.transaction_type = 'debit'
          AND e.transaction_date BETWEEN :startDate AND :endDate
        GROUP BY a.id, a.account_name
        ORDER BY a.account_name;
    ";
    $stmt_expenses = $pdo->prepare($sql_expenses);
    $stmt_expenses->execute([':school_id' => $school_id, ':startDate' => $startDate, ':endDate' => $endDate]);
    $data['expense']['accounts'] = $stmt_expenses->fetchAll(PDO::FETCH_ASSOC);
    $data['expense']['total'] = array_sum(array_column($data['expense']['accounts'], 'total'));

    $data['net_income'] = $data['revenue']['total'] - $data['expense']['total'];

    return $data;
}


function getIncomeByCustomer($pdo, $startDate, $endDate, $school_id) {
    $stmt = $pdo->prepare("
        SELECT s.name AS student_name, COALESCE(SUM(p.amount), 0) AS total_payments, MAX(p.payment_date) AS last_payment
        FROM students s
        LEFT JOIN payments p ON s.id = p.student_id AND p.payment_date BETWEEN ? AND ?
        WHERE s.school_id = ?
        GROUP BY s.id
        ORDER BY total_payments DESC
    ");
    $stmt->execute([$startDate, $endDate, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBalanceSheetData($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT account_name, balance FROM accounts WHERE account_type = 'asset' AND school_id = ?");
    $stmt->execute([$school_id]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT account_name, balance FROM accounts WHERE account_type = 'liability' AND school_id = ?");
    $stmt->execute([$school_id]);
    $liabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT account_name, balance FROM accounts WHERE account_type = 'equity' AND school_id = ?");
    $stmt->execute([$school_id]);
    $equity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_assets = array_sum(array_column($assets, 'balance'));
    $total_liabilities = array_sum(array_column($liabilities, 'balance'));
    $total_equity = array_sum(array_column($equity, 'balance'));

    $plData = getDetailedPLData($pdo, '1970-01-01', date('Y-m-d'), $school_id);
    $retained_earnings = $plData['net_income'];
    
    $total_equity_with_retained = $total_equity + $retained_earnings;

    return [
        'assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity,
        'total_assets' => $total_assets, 'total_liabilities' => $total_liabilities, 'total_equity' => $total_equity_with_retained,
        'retained_earnings' => $retained_earnings,
        'total_liabilities_equity' => $total_liabilities + $total_equity_with_retained
    ];
}


function getOpenInvoicesReport($pdo, $school_id) {
    $stmt = $pdo->prepare("
        SELECT i.id, s.name AS student_name, i.invoice_date, i.due_date, i.total_amount, i.paid_amount, (i.total_amount - i.paid_amount) AS balance
        FROM invoices i
        JOIN students s ON i.student_id = s.id
        WHERE i.school_id = ? AND (i.total_amount - i.paid_amount) > 0
        ORDER BY i.due_date ASC
    ");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getIncomeByCategory($pdo, $startDate, $endDate, $school_id) {
    // NEW, CORRECTED FUNCTION for the updated database schema.
    // This query directly joins invoice_items with the simple 'items' table
    // and groups by the item's name to categorize income.
    $stmt = $pdo->prepare("
        SELECT
            i.name AS category_name,
            SUM(ii.quantity) AS total_quantity,
            SUM(ii.quantity * ii.unit_price) AS total_income,
            AVG(ii.unit_price) AS average_price
        FROM invoice_items ii
        JOIN invoices inv ON ii.invoice_id = inv.id
        JOIN items i ON ii.item_id = i.id
        WHERE inv.invoice_date BETWEEN ? AND ? AND ii.school_id = ?
        GROUP BY i.id, i.name
        ORDER BY total_income DESC
    ");
    $stmt->execute([$startDate, $endDate, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generates a report of income grouped by item and then by class.
 *
 * @param PDO    $pdo        The database connection object.
 * @param int    $school_id  The ID of the current school.
 * @param string $start_date The start date for the report period.
 * @param string $end_date   The end date for the report period.
 * @return array             An array of income data.
 */
function getIncomeByItemAndClass(PDO $pdo, int $school_id, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT
            i.name AS item_name,
            c.name AS class_name,
            SUM(ii.quantity * ii.unit_price) AS total_income
        FROM invoice_items ii
        JOIN invoices inv ON ii.invoice_id = inv.id
        JOIN items i ON ii.item_id = i.id
        JOIN students s ON inv.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE
            ii.school_id = :school_id
            AND inv.invoice_date BETWEEN :start_date AND :end_date
        GROUP BY
            c.name, i.name
        ORDER BY
            c.name ASC, total_income DESC
    ");
    $stmt->execute([
        ':school_id' => $school_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getSubcategories($pdo, $categoryId, $startDate, $endDate, $school_id) {
    $stmt = $pdo->prepare("
        SELECT i.name, SUM(ii.quantity) AS total_quantity, SUM(ii.quantity * ii.unit_price) AS total_income, AVG(ii.unit_price) AS average_price
        FROM invoice_items ii
        JOIN invoices inv ON ii.invoice_id = inv.id
        JOIN items i ON ii.item_id = i.id
        WHERE i.parent_id = ? AND inv.invoice_date BETWEEN ? AND ? AND i.school_id = ?
        GROUP BY i.id, i.name
        ORDER BY i.name
    ");
    $stmt->execute([$categoryId, $startDate, $endDate, $school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =================================================================
// ACCOUNTING & EXPENSE FUNCTIONS
// =================================================================

function create_journal_entry(PDO $pdo, int $school_id, string $date, string $description, float $amount, int $debit_account_id, int $credit_account_id) {
    create_single_expense_entry($pdo, $school_id, $date, $description, $amount, $debit_account_id, 'debit');
    create_single_expense_entry($pdo, $school_id, $date, $description, $amount, $credit_account_id, 'credit');
}

function create_single_expense_entry(PDO $pdo, int $school_id, string $date, string $description, float $amount, int $account_id, string $type) {
    if ($amount > 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO expenses (school_id, transaction_date, description, amount, account_id, type, transaction_type) 
             VALUES (?, ?, ?, ?, ?, 'journal', ?)"
        );
        $stmt->execute([$school_id, $date, $description, $amount, $account_id, $type]);
        updateAccountBalance($pdo, $account_id, $amount, $type, $school_id);
    }
}

function getOrCreateAccount(PDO $pdo, int $school_id, string $account_name, string $account_type, string $account_code): int {
    $stmt_find = $pdo->prepare("SELECT id FROM accounts WHERE school_id = ? AND account_name = ? AND account_type = ?");
    $stmt_find->execute([$school_id, $account_name, $account_type]);
    $account = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        return (int)$account['id'];
    } else {
        $stmt_code_check = $pdo->prepare("SELECT id FROM accounts WHERE account_code = ?");
        $stmt_code_check->execute([$account_code]);
        
        if ($stmt_code_check->fetch()) {
            $account_code = $account_code . '-' . $school_id . '-' . substr(strtoupper($account_type), 0, 1);
        }

        $stmt_create = $pdo->prepare("INSERT INTO accounts (school_id, account_code, account_name, account_type, balance) VALUES (?, ?, ?, ?, 0.00)");
        $stmt_create->execute([$school_id, $account_code, $account_name, $account_type]);
        return (int)$pdo->lastInsertId();
    }
}

function getChartOfAccounts($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE school_id = ? ORDER BY account_type, account_code");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createAccount($pdo, $school_id, $account_code, $account_name, $account_type, $starting_balance = 0) {
    $stmt = $pdo->prepare("INSERT INTO accounts (school_id, account_code, account_name, account_type, balance) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$school_id, $account_code, $account_name, $account_type, $starting_balance]);
}

function updateAccount($pdo, $id, $account_code, $account_name, $account_type, $school_id) {
    $stmt = $pdo->prepare("UPDATE accounts SET account_code = ?, account_name = ?, account_type = ? WHERE id = ? AND school_id = ?");
    return $stmt->execute([$account_code, $account_name, $account_type, $id, $school_id]);
}

function deleteAccount($pdo, $id, $school_id) {
    $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ? AND school_id = ?");
    return $stmt->execute([$id, $school_id]);
}

function updateAccountBalance($pdo, $account_id, $amount, $transaction_type, $school_id) {
    $stmt = $pdo->prepare("SELECT account_type FROM accounts WHERE id = ? AND school_id = ?");
    $stmt->execute([$account_id, $school_id]);
    $account_type = $stmt->fetchColumn();

    if (!$account_type) return;

    $balance_adjustment = 0;
    
    if (in_array($account_type, ['asset', 'expense'])) {
        if ($transaction_type === 'debit') {
            $balance_adjustment = $amount;
        } else { // 'credit'
            $balance_adjustment = -$amount;
        }
    } 
    else {
        if ($transaction_type === 'credit') {
            $balance_adjustment = $amount;
        } else { // 'debit'
            $balance_adjustment = -$amount;
        }
    }

    $update_stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND school_id = ?");
    $update_stmt->execute([$balance_adjustment, $account_id, $school_id]);
}

// =================================================================
// UTILITY & FORMATTING FUNCTIONS
// =================================================================

function getBudgetVsActualsData($pdo, $budget_id, $start_date, $end_date, $school_id) {
    $report_data = [
        'revenue' => ['lines' => [], 'totals' => ['budgeted' => 0, 'actual' => 0, 'variance' => 0]],
        'expense' => ['lines' => [], 'totals' => ['budgeted' => 0, 'actual' => 0, 'variance' => 0]],
        'net' => ['budgeted' => 0, 'actual' => 0, 'variance' => 0]
    ];

    $lines_stmt = $pdo->prepare("
        SELECT bl.account_id, bl.budgeted_amount, a.account_name, a.account_type
        FROM budget_lines bl
        JOIN accounts a ON bl.account_id = a.id
        WHERE bl.budget_id = ?
    ");
    $lines_stmt->execute([$budget_id]);
    $budget_lines = $lines_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($budget_lines)) {
        return [];
    }

    $account_ids = array_column($budget_lines, 'account_id');
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    
    $actuals_stmt = $pdo->prepare("
        SELECT account_id, transaction_type, SUM(amount) as total_actual
        FROM expenses
        WHERE school_id = ? 
          AND account_id IN ($placeholders)
          AND transaction_date BETWEEN ? AND ?
        GROUP BY account_id, transaction_type
    ");
    $params = array_merge([$school_id], $account_ids, [$start_date, $end_date]);
    $actuals_stmt->execute($params);
    $actuals_raw = $actuals_stmt->fetchAll(PDO::FETCH_ASSOC);

    $actuals = [];
    foreach ($actuals_raw as $row) {
        $amount = (float)$row['total_actual'];
        if (!isset($actuals[$row['account_id']])) {
            $actuals[$row['account_id']] = 0;
        }
        if ($row['transaction_type'] == 'credit') {
            $actuals[$row['account_id']] += $amount;
        } else {
            $actuals[$row['account_id']] -= $amount;
        }
    }
    
    foreach ($budget_lines as $line) {
        $type = $line['account_type'];
        if (!isset($report_data[$type])) continue;
        
        $budgeted = (float)$line['budgeted_amount'];
        $actual = abs($actuals[$line['account_id']] ?? 0.00);

        $variance = ($type === 'revenue') ? ($actual - $budgeted) : ($budgeted - $actual);

        $progress_percent = ($budgeted > 0) ? ($actual / $budgeted) * 100 : 0;
        $progress_class = '';
        $variance_class = ($variance >= 0) ? 'variance-favorable' : 'variance-unfavorable';

        if ($type === 'expense') {
            if ($progress_percent > 100) $progress_class = 'unfavorable';
            elseif ($progress_percent > 85) $progress_class = 'warning';
            else $progress_class = 'favorable';
        } else {
             if ($progress_percent >= 100) $progress_class = 'favorable';
             elseif ($progress_percent >= 75) $progress_class = 'warning';
        }
        
        $report_data[$type]['lines'][] = [
            'account_name' => $line['account_name'],
            'budgeted' => $budgeted,
            'actual' => $actual,
            'variance' => $variance,
            'variance_class' => $variance_class,
            'progress_percent' => $progress_percent,
            'progress_class' => $progress_class
        ];

        $report_data[$type]['totals']['budgeted'] += $budgeted;
        $report_data[$type]['totals']['actual'] += $actual;
    }

    $report_data['revenue']['totals']['variance'] = $report_data['revenue']['totals']['actual'] - $report_data['revenue']['totals']['budgeted'];
    $report_data['revenue']['totals']['variance_class'] = ($report_data['revenue']['totals']['variance'] >= 0) ? 'variance-favorable' : 'variance-unfavorable';
    
    $report_data['expense']['totals']['variance'] = $report_data['expense']['totals']['budgeted'] - $report_data['expense']['totals']['actual'];
    $report_data['expense']['totals']['variance_class'] = ($report_data['expense']['totals']['variance'] >= 0) ? 'variance-favorable' : 'variance-unfavorable';

    $report_data['net']['budgeted'] = $report_data['revenue']['totals']['budgeted'] - $report_data['expense']['totals']['budgeted'];
    $report_data['net']['actual'] = $report_data['revenue']['totals']['actual'] - $report_data['expense']['totals']['actual'];
    $report_data['net']['variance'] = $report_data['net']['actual'] - $report_data['net']['budgeted'];
    $report_data['net']['variance_class'] = ($report_data['net']['variance'] >= 0) ? 'variance-favorable' : 'variance-unfavorable';

    return $report_data;
}
function getInvoiceTemplates(PDO $pdo, int $school_id): array {
    $stmt = $pdo->prepare("
        SELECT it.*, c.name as class_name 
        FROM invoice_templates it
        LEFT JOIN classes c ON it.class_id = c.id
        WHERE it.school_id = ? 
        ORDER BY it.name ASC
    ");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function format_currency($amount, $symbol = null) {
    if ($symbol === null) {
        $symbol = $_SESSION['currency_symbol'] ?? 'Ksh';
    }
    
    $formatted_amount = number_format((float)$amount, 2);
    
    if (trim($symbol) === 'Ksh') {
        return htmlspecialchars($symbol) . ' ' . $formatted_amount;
    }
    
    return htmlspecialchars($symbol) . $formatted_amount;
}
function getInvoiceNumber($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE school_id = :school_id ORDER BY id DESC LIMIT 1");
    $stmt->execute(['school_id' => $school_id]);
    $latest_invoice_number = $stmt->fetchColumn();

    if (!$latest_invoice_number) {
        $new_number = 1;
    } else {
        $parts = explode('-', $latest_invoice_number);
        $last_number = end($parts);
        $new_number = intval($last_number) + 1;
    }

    return "INV-SCH" . $school_id . "-" . str_pad($new_number, 3, '0', STR_PAD_LEFT);
}

function createInvoiceFromTemplate(PDO $pdo, int $school_id, int $student_id, int $template_id, string $due_date): ?int {
    try {
        $stmt_template = $pdo->prepare("SELECT name, items FROM invoice_templates WHERE id = ? AND school_id = ?");
        $stmt_template->execute([$template_id, $school_id]);
        $template = $stmt_template->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            throw new Exception("Invoice template not found.");
        }

        $items = json_decode($template['items'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($items) || empty($items)) {
            return null;
        }

        $invoice_number = getInvoiceNumber($pdo, $school_id);
        $invoice_date = date('Y-m-d');
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        $stmt_invoice = $pdo->prepare(
            "INSERT INTO invoices (school_id, student_id, invoice_number, invoice_date, due_date, total_amount, paid_amount, status) 
             VALUES (?, ?, ?, ?, ?, ?, 0.00, 'Unpaid')"
        );
        $stmt_invoice->execute([$school_id, $student_id, $invoice_number, $invoice_date, $due_date, $total_amount]);
        $invoice_id = $pdo->lastInsertId();

        if (!$invoice_id) {
            throw new Exception("Failed to create the main invoice record.");
        }
        
        $stmt_items = $pdo->prepare(
            "INSERT INTO invoice_items (school_id, invoice_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            if (!isset($item['item_id'], $item['unit_price'])) continue;
            
            $quantity = $item['quantity'] ?? 1;
            $unit_price = $item['unit_price'];

            $stmt_items->execute([$school_id, $invoice_id, $item['item_id'],
                                  $quantity, $unit_price]);
        }

        // *** THIS IS THE CRITICAL FIX ***
        if ($total_amount > 0) {
            $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
            $tuition_revenue_id = getOrCreateAccount($pdo, $school_id, 'Tuition Revenue', 'revenue', '4000');
            $description = "Invoice #{$invoice_number} generated for student ID {$student_id}.";
            
            // Debit Accounts Receivable (asset increases), Credit Tuition Revenue (revenue increases)
            create_journal_entry($pdo, $school_id, $invoice_date, $description, $total_amount, $accounts_receivable_id, $tuition_revenue_id);
        }
        // *** END OF FIX ***
        
        log_audit($pdo, 'CREATE', 'invoices', $invoice_id, ['data' => [
            'student_id' => $student_id,
            'template_id' => $template_id,
            'invoice_number' => $invoice_number,
            'total_amount' => $total_amount
        ]]);

        return (int)$invoice_id;

    } catch (Exception $e) {
        throw $e;
    }
}

function getTotalOut(PDO $pdo, int $school_id): float {
    $stmt = $pdo->prepare(
        "SELECT SUM(balance) as total_out FROM accounts WHERE school_id = ? AND account_type = 'expense'"
    );
    $stmt->execute([$school_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)($result['total_out'] ?? 0.0);
}

function getTotalExpected(PDO $pdo, int $school_id): float {
    $stmt = $pdo->prepare(
        "SELECT SUM(total_amount) as total_expected FROM invoices WHERE school_id = ?"
    );
    $stmt->execute([$school_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)($result['total_expected'] ?? 0.0);
}

function getTotalPaid(PDO $pdo, int $school_id): float {
    $stmt = $pdo->prepare(
        "SELECT SUM(amount) as total_paid FROM payment_receipts WHERE school_id = ?"
    );
    $stmt->execute([$school_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)($result['total_paid'] ?? 0.0);
}

function getAgingReceivablesSummary(PDO $pdo, int $school_id): array {
    $summary = [
        'current' => 0,
        '1-30' => 0,
        '31-90' => 0,
        '90+' => 0
    ];

    $stmt_current = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE school_id = ? AND balance > 0.01 AND due_date >= CURDATE()");
    $stmt_current->execute([$school_id]);
    $summary['current'] = (float)$stmt_current->fetchColumn();

    $stmt_30 = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE school_id = ? AND balance > 0.01 AND due_date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() - INTERVAL 1 DAY");
    $stmt_30->execute([$school_id]);
    $summary['1-30'] = (float)$stmt_30->fetchColumn();

    $stmt_90 = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE school_id = ? AND balance > 0.01 AND due_date BETWEEN CURDATE() - INTERVAL 90 DAY AND CURDATE() - INTERVAL 31 DAY");
    $stmt_90->execute([$school_id]);
    $summary['31-90'] = (float)$stmt_90->fetchColumn();

    $stmt_90plus = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE school_id = ? AND balance > 0.01 AND due_date < CURDATE() - INTERVAL 90 DAY");
    $stmt_90plus->execute([$school_id]);
    $summary['90+'] = (float)$stmt_90plus->fetchColumn();
    
    return $summary;
}

function getCollectionRateForPeriod(PDO $pdo, int $school_id, string $start_date, string $end_date): float {
    $stmt_paid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE school_id = ? AND payment_date BETWEEN ? AND ?");
    $stmt_paid->execute([$school_id, $start_date, $end_date]);
    $totalPaid = (float)$stmt_paid->fetchColumn();

    $stmt_invoiced = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE school_id = ? AND invoice_date BETWEEN ? AND ?");
    $stmt_invoiced->execute([$school_id, $start_date, $end_date]);
    $totalInvoiced = (float)$stmt_invoiced->fetchColumn();

    if ($totalInvoiced == 0) {
        return 100.0;
    }
    
    return ($totalPaid / $totalInvoiced) * 100;
}

function getUpcomingPromises(PDO $pdo, int $school_id, int $days_ahead = 30): float {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(promised_amount), 0) 
        FROM payment_promises 
        WHERE school_id = ? AND status = 'Pending' 
        AND promised_due_date BETWEEN CURDATE() AND CURDATE() + INTERVAL ? DAY
    ");
    $stmt->execute([$school_id, $days_ahead]);
    return (float)$stmt->fetchColumn();
}

function getTotalAssetBalance(PDO $pdo, int $school_id): float {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM accounts WHERE school_id = ? AND account_type = 'asset'");
    $stmt->execute([$school_id]);
    return (float)$stmt->fetchColumn();
}

function getUndepositedFundsBalance(PDO $pdo, int $school_id): float {
    $accountId = getUndepositedFundsAccountId($pdo, $school_id);
    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
    $stmt->execute([$accountId]);
    return (float)$stmt->fetchColumn();
}

function getNewStudentsForPeriod(PDO $pdo, int $school_id, string $start_date, string $end_date): int {
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM students WHERE school_id = ? AND created_at BETWEEN ? AND ?");
    $stmt->execute([$school_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return (int)$stmt->fetchColumn();
}

/**
 * Calculates the current financial balance for a single student.
 *
 * @param PDO $pdo The database connection object.
 * @param int $student_id The ID of the student.
 * @param int $school_id The ID of the school.
 * @return float The calculated balance (invoiced - paid).
 */
function getStudentBalance(PDO $pdo, int $student_id, int $school_id): float {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_amount), 0) as totalInvoiced,
            COALESCE(SUM(paid_amount), 0) as totalPaid
        FROM invoices 
        WHERE student_id = ? AND school_id = ?
    ");
    $stmt->execute([$student_id, $school_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$summary) {
        return 0.0;
    }

    return (float)($summary['totalInvoiced'] - $summary['totalPaid']);
}

function updateInventoryStockLevel(PDO $pdo, int $school_id, int $item_id, int $quantity_changed, string $movement_type, ?float $new_cost = null, array $options = []) {
    // 1. Fetch current item details
    $stmt_item = $pdo->prepare("SELECT quantity_on_hand, average_cost, unit_price FROM inventory_items WHERE id = ? AND school_id = ? FOR UPDATE");
    $stmt_item->execute([$item_id, $school_id]);
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception("Inventory item not found.");
    }

    $current_qty = (int)$item['quantity_on_hand'];
    $current_avg_cost = (float)$item['average_cost'];
    
    // 2. Check for sufficient stock for reductions
    if ($quantity_changed < 0 && ($current_qty + $quantity_changed < 0)) {
        throw new Exception("Insufficient stock for this operation.");
    }
    
    $new_qty = $current_qty + $quantity_changed;
    $new_avg_cost = $current_avg_cost;

    // 3. Recalculate average cost for stock additions (purchases)
    if ($movement_type === 'purchase' && $quantity_changed > 0 && $new_cost !== null) {
        $total_value_before = $current_qty * $current_avg_cost;
        $value_of_addition = $quantity_changed * $new_cost;
        if ($new_qty > 0) {
            $new_avg_cost = ($total_value_before + $value_of_addition) / $new_qty;
        } else {
            $new_avg_cost = $new_cost;
        }
    }
    
    // 4. Update the inventory item record
    $stmt_update = $pdo->prepare("UPDATE inventory_items SET quantity_on_hand = ?, average_cost = ? WHERE id = ? AND school_id = ?");
    $stmt_update->execute([$new_qty, $new_avg_cost, $item_id, $school_id]);
    
    // 5. Log the movement
    $stmt_log = $pdo->prepare(
        "INSERT INTO inventory_movements (school_id, item_id, user_id, movement_type, quantity_changed, cost_at_time, price_at_time, related_entity_type, related_entity_id, notes, transaction_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_log->execute([
        $school_id,
        $item_id,
        $_SESSION['user_id'],
        $movement_type,
        $quantity_changed,
        ($movement_type === 'purchase') ? $new_cost : $current_avg_cost, // Use new cost for purchase, avg cost for others
        ($movement_type === 'issuance') ? (float)$item['unit_price'] : null, // Record selling price only on issuance
        $options['entity_type'] ?? null,
        $options['entity_id'] ?? null,
        $options['notes'] ?? null,
        $options['date'] ?? date('Y-m-d H:i:s')
    ]);
    
    log_audit($pdo, 'UPDATE', 'inventory_items', $item_id, ['data' => [
        'movement_type' => $movement_type,
        'quantity_changed' => $quantity_changed,
        'new_quantity_on_hand' => $new_qty
    ]]);
}

/**
 * Ensures that essential inventory-related accounts exist in the Chart of Accounts.
 *
 * @param PDO $pdo The database connection object.
 * @param int $school_id The ID of the school.
 * @return array An associative array of the required account IDs.
 */
function getInventoryAccountIDs(PDO $pdo, int $school_id): array {
    return [
        'asset' => getOrCreateAccount($pdo, $school_id, 'Inventory Asset', 'asset', '1300'),
        'cogs' => getOrCreateAccount($pdo, $school_id, 'Cost of Goods Sold', 'expense', '5000'),
        'sales' => getOrCreateAccount($pdo, $school_id, 'Inventory Sales', 'revenue', '4100')
    ];
}

function createInvoiceFromUniformOrder(PDO $pdo, int $school_id, int $order_id): int {
    // 1. Fetch the order and its items
    $stmt_order = $pdo->prepare("SELECT * FROM uniform_orders WHERE id = ? AND school_id = ?");
    $stmt_order->execute([$order_id, $school_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Uniform order not found.");
    }
    
    $stmt_items = $pdo->prepare("
        SELECT uoi.quantity, uoi.unit_price, i.id as item_id, i.name, uoi.size
        FROM uniform_order_items uoi
        JOIN inventory_items i ON uoi.item_id = i.id
        WHERE uoi.order_id = ?
    ");
    $stmt_items->execute([$order_id]);
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($order_items)) {
        throw new Exception("Cannot create an invoice for an empty order.");
    }

    // 2. Format items for the createInvoice function
    $invoice_items = [];
    $invoice_notes = "Uniform order #{$order_id}.\nItems:\n";
    foreach ($order_items as $item) {
        $invoice_items[] = [
            'item_id' => $item['item_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price']
        ];
        $invoice_notes .= "- " . $item['name'] . " (Size: " . $item['size'] . ") x " . $item['quantity'] . "\n";
    }

    // 3. Use the existing createInvoice function
    $invoice_id = createInvoice(
        $pdo,
        $school_id,
        $order['student_id'],
        $order['order_date'], // Use order date as invoice date
        $order['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
        $invoice_items,
        $invoice_notes
    );

    // 4. Link the new invoice back to the uniform order
    $stmt_link = $pdo->prepare("UPDATE uniform_orders SET invoice_id = ? WHERE id = ?");
    $stmt_link->execute([$invoice_id, $order_id]);

    return $invoice_id;
}

function promoteStudentAndCreateInvoice(PDO $pdo, int $school_id, int $student_id, int $new_class_id, string $new_academic_year, string $term, string $due_date): int {
    // Initialize the item list for the new invoice
    $new_invoice_items = [];
    
    // --- Step 1: Promote the student to the new class ---
    $stmt_promote = $pdo->prepare("UPDATE students SET class_id = ? WHERE id = ? AND school_id = ?");
    $stmt_promote->execute([$new_class_id, $student_id, $school_id]);

    // --- Step 2: Get all MANDATORY fee items for the NEW class for the specified term ---
    $stmt_new_fees = $pdo->prepare(
        "SELECT fsi.item_id, i.name as item_name, fsi.amount
         FROM fee_structure_items fsi
         JOIN items i ON fsi.item_id = i.id
         WHERE fsi.class_id = ? AND fsi.academic_year = ? AND fsi.term = ? AND fsi.is_mandatory = 1 AND fsi.school_id = ?"
    );
    $stmt_new_fees->execute([$new_class_id, $new_academic_year, $term, $school_id]);
    $new_mandatory_fees = $stmt_new_fees->fetchAll(PDO::FETCH_ASSOC);

    // Add these new mandatory fees to our new invoice item list
    $new_mandatory_item_ids = array_column($new_mandatory_fees, 'item_id');
    foreach ($new_mandatory_fees as $fee) {
        $new_invoice_items[] = [
            'item_id' => $fee['item_id'],
            'description' => $fee['item_name'],
            'quantity' => 1,
            'unit_price' => $fee['amount']
        ];
    }

    // --- Step 3: Find the last invoice and carry over its OPTIONAL items ---
    $stmt_last_invoice = $pdo->prepare("SELECT id FROM invoices WHERE student_id = ? AND school_id = ? ORDER BY id DESC LIMIT 1");
    $stmt_last_invoice->execute([$student_id, $school_id]);
    $last_invoice_id = $stmt_last_invoice->fetchColumn();

    if ($last_invoice_id) {
        // Get all items from the last invoice
        $stmt_old_items = $pdo->prepare(
            "SELECT ii.item_id, ii.quantity, ii.unit_price, i.name as item_name, i.description
             FROM invoice_items ii
             JOIN items i ON ii.item_id = i.id
             WHERE ii.invoice_id = ? AND ii.school_id = ?"
        );
        $stmt_old_items->execute([$last_invoice_id, $school_id]);
        $old_invoice_items = $stmt_old_items->fetchAll(PDO::FETCH_ASSOC);

        // Get the list of all mandatory items in the system to filter them out
        $stmt_all_mandatory = $pdo->prepare("SELECT DISTINCT item_id FROM fee_structure_items WHERE school_id = ? AND is_mandatory = 1");
        $stmt_all_mandatory->execute([$school_id]);
        $all_mandatory_ids = $stmt_all_mandatory->fetchAll(PDO::FETCH_COLUMN);
        
        // Also fetch the 'Balance Brought Forward' item ID to ensure we don't copy it
        $balance_bf_item_id = $pdo->query("SELECT id FROM items WHERE name = 'Balance Brought Forward' AND school_id = $school_id")->fetchColumn();

        foreach ($old_invoice_items as $old_item) {
            // Carry over an item if it's NOT a mandatory fee for any class (i.e., it's a truly optional item like skating, transport, etc.)
            // AND it's not the "Balance Brought Forward" item itself.
            $is_optional = !in_array($old_item['item_id'], $all_mandatory_ids);
            $is_balance_bf = ($old_item['item_id'] == $balance_bf_item_id);

            if ($is_optional && !$is_balance_bf) {
                 $new_invoice_items[] = [
                    'item_id' => $old_item['item_id'],
                    'description' => $old_item['description'] ?: $old_item['item_name'], // Use description if available, else name
                    'quantity' => $old_item['quantity'],
                    'unit_price' => $old_item['unit_price']
                ];
            }
        }
    }

    // --- Step 4: Create the new invoice with the final, combined item list ---
    $invoice_notes = "New invoice generated after promotion for the {$new_academic_year} {$term} term.";
    $new_invoice_id = createInvoice($pdo, $school_id, $student_id, date('Y-m-d'), $due_date, $new_invoice_items, $invoice_notes);
    
    if(!$new_invoice_id) {
        throw new Exception("Failed to create a new invoice for student ID {$student_id}.");
    }

    return $new_invoice_id;
}

function getOrCreateSystemItem(PDO $pdo, int $school_id, string $item_name): int {
    $stmt = $pdo->prepare("SELECT id FROM items WHERE school_id = ? AND name = ?");
    $stmt->execute([$school_id, $item_name]);
    $item_id = $stmt->fetchColumn();

    if ($item_id) {
        return (int)$item_id;
    } else {
        // Item doesn't exist, so create it
        $stmt_create = $pdo->prepare(
            "INSERT INTO items (school_id, name, description) VALUES (?, ?, ?)"
        );
        $stmt_create->execute([$school_id, $item_name, 'An automatically generated item to carry over outstanding balances.']);
        $new_item_id = $pdo->lastInsertId();
        if ($new_item_id) {
            log_audit($pdo, 'CREATE', 'items', $new_item_id, ['data' => ['name' => $item_name, 'note' => 'Auto-created system item.']]);
            return (int)$new_item_id;
        } else {
            throw new Exception("Could not create the required '{$item_name}' item in the database.");
        }
    }
}
?>