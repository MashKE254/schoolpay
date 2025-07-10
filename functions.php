<?php
// functions.php - Final, Consolidated, and Multi-Tenant Aware
// Contains all helper functions for the application, each defined only once.

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

function getDashboardSummary($pdo, $school_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $total_students = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE school_id = ? AND payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)");
        $stmt->execute([$school_id]);
        $total_income = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $total_expenses = $stmt->fetchColumn();

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

function getStudents($pdo, $school_id) {
    // MODIFIED: Changed ORDER BY clause to sort by student ID number numerically.
    $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? ORDER BY CAST(student_id_no AS UNSIGNED) ASC");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentById($pdo, $student_id, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
    $stmt->execute([$student_id, $school_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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

function getInvoices($pdo, $school_id) {
    $stmt = $pdo->prepare("
        SELECT i.*, s.name as student_name,
               (SELECT SUM(quantity * unit_price) FROM invoice_items WHERE invoice_id = i.id) as total_amount,
               (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as paid_amount
        FROM invoices i
        JOIN students s ON i.student_id = s.id
        WHERE i.school_id = ?
        ORDER BY i.invoice_date DESC
    ");
    $stmt->execute([$school_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInvoiceDetails($pdo, $invoice_id, $school_id) {
    $stmt = $pdo->prepare("
        SELECT 
            i.*, 
            s.name as student_name, s.email as student_email, s.phone as student_phone, s.address as student_address,
            sch.name as school_name,
            sch_d.address as school_address,
            sch_d.phone as school_phone,
            sch_d.email as school_email,
            sch_d.logo_url as school_logo_url
        FROM invoices i
        JOIN students s ON i.student_id = s.id
        LEFT JOIN schools sch ON i.school_id = sch.id
        LEFT JOIN school_details sch_d ON i.school_id = sch_d.school_id
        WHERE i.id = ? AND i.school_id = ?
    ");
    $stmt->execute([$invoice_id, $school_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$invoice) return null;

    $stmt = $pdo->prepare("
        SELECT ii.*, i.name as item_name, i.price as unit_price
        FROM invoice_items ii
        JOIN items i ON ii.item_id = i.id
        WHERE ii.invoice_id = ? AND i.school_id = ?
    ");
    $stmt->execute([$invoice_id, $school_id]);
    $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $invoice;
}

function createInvoice($pdo, $school_id, $student_id, $invoice_date, $due_date, $items, $notes = '') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO invoices (school_id, student_id, invoice_date, due_date, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $student_id, $invoice_date, $due_date, $notes]);
        $invoice_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO invoice_items (school_id, invoice_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->execute([$school_id, $invoice_id, $item['item_id'], $item['quantity'], $item['unit_price']]);
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
        // Start a transaction to ensure both operations complete or neither do.
        $pdo->beginTransaction();

        // Step 1: Delete all child items that belong to this parent item.
        // This resolves the foreign key constraint issue.
        $stmt_children = $pdo->prepare(
            "DELETE FROM items WHERE parent_id = ? AND school_id = ?"
        );
        $stmt_children->execute([$item_id, $school_id]);

        // Step 2: Now that any potential children are gone, delete the parent item itself.
        $stmt_parent = $pdo->prepare(
            "DELETE FROM items WHERE id = ? AND school_id = ?"
        );
        $stmt_parent->execute([$item_id, $school_id]);

        // If both statements executed without error, commit the changes.
        $pdo->commit();

        return true;
        
    } catch (PDOException $e) {
        // If any error occurs, roll back all changes.
        $pdo->rollBack();
        // Re-throw the exception to be handled by the calling script.
        throw $e;
    }
}

// =================================================================
// PAYROLL FUNCTIONS
// =================================================================

function getPayrollRecords(PDO $pdo, $school_id): array {
    $stmt = $pdo->prepare("
        SELECT p.*, e.department, e.position
        FROM payroll p
        LEFT JOIN employees e ON p.employee_id = e.id
        WHERE p.school_id = ?
        ORDER BY p.pay_date DESC
    ");
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
    
    // This part of the revenue calculation is simplified for clarity. 
    // A more direct sum from the `payments` table provides the total income.
    $stmt_total_revenue = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN :startDate AND :endDate AND school_id = :school_id");
    $stmt_total_revenue->execute([':startDate' => $startDate, ':endDate' => $endDate, ':school_id' => $school_id]);
    $total_revenue = $stmt_total_revenue->fetchColumn();
    
    // For breakdown, we use a simpler approach based on income categories.
    $income_categories = getIncomeByCategory($pdo, $startDate, $endDate, $school_id);
    foreach($income_categories as $cat) {
        $data['revenue']['accounts'][] = [
            'account_name' => $cat['category_name'],
            'total' => $cat['total_income']
        ];
    }
    $data['revenue']['total'] = $total_revenue;


    // --- Calculate Total Expenses from Expense Accounts ---
    // This query is correct based on accounting principles.
    // Ensure expenses are recorded against accounts with the type 'expense'.
    $sql_expenses = "
        SELECT
            a.account_name,
            SUM(e.amount) as total
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

    // Calculate Net Income
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

    // Use the fixed P&L data for retained earnings
    $plData = getDetailedPLData($pdo, '1970-01-01', date('Y-m-d'), $school_id);
    $retained_earnings = $plData['net_income'];
    
    // Add retained earnings to the equity section for display
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
    
    // For Asset and Expense accounts, Debits are positive (+) and Credits are negative (-)
    if (in_array($account_type, ['asset', 'expense'])) {
        if ($transaction_type === 'debit') {
            $balance_adjustment = $amount;
        } else { // 'credit'
            $balance_adjustment = -$amount;
        }
    } 
    // For Liability, Equity, and Revenue accounts, Credits are positive (+) and Debits are negative (-)
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