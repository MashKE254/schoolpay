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
 * Records an action in the audit log.
 *
 * @param PDO $pdo The database connection object.
 * @param string $action_type The type of action (CREATE, UPDATE, DELETE).
 * @param string $target_table The database table that was affected.
 * @param int|null $target_id The ID of the record that was affected.
 * @param array $details An associative array containing data about the change.
 * For UPDATE, use ['before' => $old_data, 'after' => $new_data].
 * For CREATE/DELETE, use ['data' => $data].
 * * Formats the JSON details from an audit log entry into readable HTML.
 * @param array $log The audit log row.
 * @return string The formatted HTML.
 */


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

/**
 * Sends a bulk SMS message using the Africa's Talking API.
 *
 * @param array $recipients An array of phone numbers in international format (e.g., ['+2547...','+2547...']).
 * @param string $message The text message to be sent.
 * @return array|string The result from the API or an error message.
 */
function sendBulkSms(array $recipients, string $message) {
    // Check if credentials are defined
    if (!defined('AT_USERNAME') || !defined('AT_API_KEY')) {
        return ['error' => "Africa's Talking API credentials are not defined in config.php"];
    }
    
    // Define options to force the use of TLS version 1.2
    $curl_options = [
        'curl' => [
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ],
    ];
    
    try {
        // Initialize the SDK with your username, API key, AND the new options
        $AT = new AfricasTalking(AT_USERNAME, AT_API_KEY, $curl_options);
        
        // Get the SMS service
        $sms = $AT->sms();

        // Prepare the options for the API call
        $options = [
            'to'      => implode(',', $recipients),
            'message' => $message,
        ];

        // Attempt to send the message
        return $sms->send($options);
        
    } catch (Exception $e) {
        // If an error occurs, return the error message for debugging
        error_log("Africa's Talking API Error: " . $e->getMessage());
        return ['error' => "API Exception: " . $e->getMessage()];
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

        return [
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'current_balance' => $total_income - $total_expenses,
            'total_students' => $total_students
        ];
    } catch (PDOException $e) {
        error_log("Error in getDashboardSummary: " . $e->getMessage());
        return ['total_income' => 0, 'total_expenses' => 0, 'current_balance' => 0, 'total_students' => 0];
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
            i.id, i.student_id, i.invoice_number, i.invoice_date, i.due_date, i.total_amount, i.status,
            s.name as student_name, s.address as student_address,
            sc.name as school_name, -- Removed all other school columns
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
    try {
        $pdo->beginTransaction();

        // 1. Calculate the total amount from the items array.
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        // 2. Get the new, school-specific invoice number
        $invoice_number = getInvoiceNumber($pdo, $school_id);
        
        // 3. Add the invoice_number AND the calculated total_amount to the INSERT statement
        $stmt = $pdo->prepare(
            "INSERT INTO invoices (school_id, student_id, invoice_number, invoice_date, due_date, total_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$school_id, $student_id, $invoice_number, $invoice_date, $due_date, $total_amount, $notes]);
        $invoice_id = $pdo->lastInsertId();

        // 4. Insert invoice items
        $stmt_items = $pdo->prepare("INSERT INTO invoice_items (school_id, invoice_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt_items->execute([$school_id, $invoice_id, $item['item_id'], $item['quantity'], $item['unit_price']]);
        }

        // 5. The Accounting Logic (Journal Entry) - This is the critical new part.
        if ($total_amount > 0) {
            // Get or create the necessary accounts
            $accounts_receivable_id = getOrCreateAccount($pdo, $school_id, 'Accounts Receivable', 'asset', '1200');
            $tuition_revenue_id = getOrCreateAccount($pdo, $school_id, 'Tuition Revenue', 'revenue', '4000');
            
            // Create a description for the journal entry
            $description = "Invoice #{$invoice_number} created for student ID {$student_id}.";
            
            // Debit Accounts Receivable (asset increases), Credit Tuition Revenue (revenue increases)
            create_journal_entry($pdo, $school_id, $invoice_date, $description, $total_amount, $accounts_receivable_id, $tuition_revenue_id);
        }
        
        $pdo->commit();
        return $invoice_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
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
    $stmt = $pdo->prepare("SELECT * FROM items WHERE school_id = ? AND parent_id IS NULL ORDER BY name");
    $stmt->execute([$school_id]);
    $parentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM items WHERE school_id = ? AND parent_id IS NOT NULL ORDER BY name");
    $stmt->execute([$school_id]);
    $childItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($parentItems as &$parent) {
        $parent['sub_items'] = array_values(array_filter($childItems, fn($child) => $child['parent_id'] == $parent['id']));
    }
    return $parentItems;
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
 * Calculates Kenyan statutory deductions based on gross pay.
 *
 * @param float $gross_pay The total gross earnings for the month.
 * @return array An array containing all calculated payroll components.
 */
function calculate_kenyan_deductions(float $gross_pay): array {
    $nssf = min($gross_pay * 0.06, 1080); 
    $taxable_pay = $gross_pay - $nssf;
    $nhif = 0;
    if ($gross_pay <= 5999) $nhif = 150; else if ($gross_pay <= 7999) $nhif = 300; else if ($gross_pay <= 11999) $nhif = 400; else if ($gross_pay <= 14999) $nhif = 500; else if ($gross_pay <= 19999) $nhif = 600; else if ($gross_pay <= 24999) $nhif = 750; else if ($gross_pay <= 29999) $nhif = 850; else if ($gross_pay <= 34999) $nhif = 900; else if ($gross_pay <= 39999) $nhif = 950; else if ($gross_pay <= 44999) $nhif = 1000; else if ($gross_pay <= 49999) $nhif = 1100; else if ($gross_pay <= 59999) $nhif = 1200; else if ($gross_pay <= 69999) $nhif = 1300; else if ($gross_pay <= 79999) $nhif = 1400; else if ($gross_pay <= 89999) $nhif = 1500; else if ($gross_pay <= 99999) $nhif = 1600; else $nhif = 1700;
    $paye = 0; $annual_taxable_pay = $taxable_pay * 12;
    if ($annual_taxable_pay <= 288000) { $paye = ($annual_taxable_pay * 0.10); } elseif ($annual_taxable_pay <= 388000) { $paye = 28800 + (($annual_taxable_pay - 288000) * 0.25); } else { $paye = 28800 + 25000 + (($annual_taxable_pay - 388000) * 0.30); }
    $monthly_paye = $paye / 12;
    $personal_relief = 2400; $insurance_relief = $nhif * 0.15; $final_paye = max(0, $monthly_paye - ($personal_relief + $insurance_relief));
    $housing_levy = $gross_pay * 0.015;
    $total_deductions = $final_paye + $nhif + $nssf + $housing_levy;
    $net_pay = $gross_pay - $total_deductions;

    return ['gross_pay' => round($gross_pay, 2), 'paye' => round($final_paye, 2), 'nhif' => round($nhif, 2), 'nssf' => round($nssf, 2), 'housing_levy' => round($housing_levy, 2), 'total_deductions' => round($total_deductions, 2), 'net_pay' => round($net_pay, 2)];
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


// =================================================================
// REPORTING FUNCTIONS
// =================================================================

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
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(p.name, i.name) AS category_name,
            SUM(ii.quantity) AS total_quantity,
            SUM(ii.quantity * ii.unit_price) AS total_income,
            AVG(ii.unit_price) AS average_price
        FROM invoice_items ii
        JOIN invoices inv ON ii.invoice_id = inv.id
        JOIN items i ON ii.item_id = i.id
        LEFT JOIN items p ON i.parent_id = p.id
        WHERE inv.invoice_date BETWEEN ? AND ? AND ii.school_id = ?
        GROUP BY category_name
        ORDER BY total_income DESC
    ");
    $stmt->execute([$startDate, $endDate, $school_id]);
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
        $symbol = $_SESSION['currency_symbol'] ?? '$';
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
?>
