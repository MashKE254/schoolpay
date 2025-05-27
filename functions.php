<?php
// functions.php
// Contains helper functions

// Example: Get dashboard summary (dummy data or real query)
function getDashboardSummary($pdo) {
    try {
        // Get total income (revenue accounts)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total_income
            FROM journal_entries je
            JOIN chart_of_accounts ca ON je.credit_account = ca.id
            WHERE ca.account_type = 'Revenue'
        ");
        $total_income = $stmt->fetchColumn();

        // Get total expenses (expense accounts)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total_expenses
            FROM journal_entries je
            JOIN chart_of_accounts ca ON je.debit_account = ca.id
            WHERE ca.account_type = 'Expense'
        ");
        $total_expenses = $stmt->fetchColumn();

        // Get current balance (assets - liabilities)
        $stmt = $pdo->query("
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) 
                 FROM journal_entries je 
                 JOIN chart_of_accounts ca ON je.debit_account = ca.id 
                 WHERE ca.account_type = 'Asset')
                -
                (SELECT COALESCE(SUM(amount), 0) 
                 FROM journal_entries je 
                 JOIN chart_of_accounts ca ON je.credit_account = ca.id 
                 WHERE ca.account_type = 'Liability')
            as current_balance
        ");
        $current_balance = $stmt->fetchColumn();

        // Get total students (assuming we have a students table)
        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $total_students = $stmt->fetchColumn();
    
    return [
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
            'current_balance' => $current_balance,
        'total_students' => $total_students
    ];
    } catch (PDOException $e) {
        error_log("Error getting dashboard summary: " . $e->getMessage());
        return [
            'total_income' => 0,
            'total_expenses' => 0,
            'current_balance' => 0,
            'total_students' => 0
        ];
    }
}

// Example: Get list of invoices for customer center
function getInvoices($pdo) {
    $stmt = $pdo->query("
        SELECT i.*, 
               s.name as student_name,
               s.id as student_id,
               (SELECT SUM(quantity * unit_price) 
                FROM invoice_items 
                WHERE invoice_id = i.id) as total_amount,
               (SELECT SUM(amount) 
                FROM payments 
                WHERE invoice_id = i.id) as paid_amount
        FROM invoices i
        JOIN students s ON i.student_id = s.id
        ORDER BY i.invoice_date DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch all payroll records.
 *
 * @param PDO $pdo
 * @return array
 */
function getPayrollRecords(PDO $pdo): array
{
    // Select the actual column names, and alias them to match what your view expects
    $sql = "
        SELECT
            id,
            employee_name,
            employee_type,
            hours,
            rate,
            gross_pay,
            tax,
            insurance,
            retirement,
            other_deduction,
            total_deductions,
            net_pay,
            pay_date
        FROM payroll
        ORDER BY pay_date DESC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Example: Get report data (P&L) – dummy data
function getProfitLossData($pdo, $startDate, $endDate) {
    // Get income
    $stmt = $pdo->prepare("
        SELECT SUM(total_amount) as income
        FROM invoices
        WHERE status = 'Paid'
        AND invoice_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $income = $stmt->fetch(PDO::FETCH_ASSOC)['income'] ?? 0;
    
    // Get expenses
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as expenses
        FROM expenses
        WHERE date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $expenses = $stmt->fetch(PDO::FETCH_ASSOC)['expenses'] ?? 0;
    
    return [
        'income' => $income,
        'expenses' => $expenses,
        'net_income' => $income - $expenses
    ];
}

// Get all students
function getStudents($pdo) {
    $stmt = $pdo->query("SELECT id, name, email, phone, address, created_at FROM students ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get student by ID
function getStudentById($pdo, $student_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get student invoices
function getStudentInvoices($pdo, $student_id) {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               (SELECT SUM(quantity * unit_price) 
                FROM invoice_items 
                WHERE invoice_id = i.id) as total_amount
        FROM invoices i
        WHERE i.student_id = ?
        ORDER BY i.invoice_date DESC
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// functions.php - Update the getInvoiceDetails function

function getInvoiceDetails($pdo, $invoice_id) {
    try {
        // Get invoice details with student information
        $stmt = $pdo->prepare("
            SELECT i.*, s.name as student_name, s.email, s.phone, s.address
            FROM invoices i
            JOIN students s ON i.student_id = s.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            return null;
        }
        
        // Get invoice items
        $stmt = $pdo->prepare("
            SELECT ii.*, i.name as item_name, i.price as unit_price
            FROM invoice_items ii
            JOIN items i ON ii.item_id = i.id
            WHERE ii.invoice_id = ?
        ");
        $stmt->execute([$invoice_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Assign items to the invoice (no total_amount recalculation)
        $invoice['items'] = $items;
        
        return $invoice;
    } catch (PDOException $e) {
        error_log("Error getting invoice details: " . $e->getMessage());
        return null;
    }
}

// Get student transactions (invoices and payments)
function getStudentTransactions($pdo, $student_id) {
    // Get invoices
    // Update the payment query to:
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.payment_date as date,
        p.amount,
        p.payment_method,
        p.memo,
        p.invoice_id,
        i.id as invoice_number
    FROM payments p
    LEFT JOIN invoices i ON p.invoice_id = i.id
    WHERE p.student_id = ?
    ORDER BY p.payment_date ASC
");
    $stmt->execute([$student_id, $student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all available items
function getItems($pdo) {
    $stmt = $pdo->query("SELECT * FROM items ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Create a new invoice
function createInvoice($pdo, $student_id, $invoice_date, $due_date, $items, $notes = '') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Verify student exists
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Student with ID $student_id not found");
        }
        
        // Insert invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (student_id, invoice_date, due_date, notes) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $invoice_date, $due_date, $notes]);
        $invoice_id = $pdo->lastInsertId();
        
        // Insert invoice items
        $stmt = $pdo->prepare("
            INSERT INTO invoice_items (invoice_id, item_id, quantity, unit_price) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
            if (!isset($item['item_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
                throw new Exception("Invalid item data");
            }
            
            $stmt->execute([
                $invoice_id,
                $item['item_id'],
                $item['quantity'],
                $item['unit_price']
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        return $invoice_id;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
}

// Update invoice status
function updateInvoiceStatus($pdo, $invoice_id, $status) {
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $invoice_id]);
}

// Record a payment
function recordPayment($pdo, $invoice_id, $student_id, $payment_date, $amount, $method = null, $memo = '') {
    $stmt = $pdo->prepare("
        INSERT INTO payments (invoice_id, student_id, payment_date, amount, method, memo)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$invoice_id, $student_id, $payment_date, $amount, $method, $memo]);
}

// Create a new item
function createItem($pdo, $name, $price, $description = '', $parent_id = null, $item_type = 'parent') {
    try {
        // Check if the new columns exist
        $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'item_type'");
        $columnExists = $stmt->rowCount() > 0;
        
        if ($columnExists) {
            $stmt = $pdo->prepare("INSERT INTO items (name, price, description, parent_id, item_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $parent_id, $item_type]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO items (name, price, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $description]);
        }
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        // If there's any error, try the basic insert
        $stmt = $pdo->prepare("INSERT INTO items (name, price, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $description]);
        return $pdo->lastInsertId();
    }
}

// Get all items with their sub-items
function getItemsWithSubItems($pdo) {
    try {
        // First check if the item_type column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'item_type'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            // If column doesn't exist, just return regular items
            $stmt = $pdo->query("SELECT * FROM items ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Get all parent items
        $stmt = $pdo->query("
            SELECT * FROM items 
            WHERE item_type = 'parent' 
            ORDER BY name
        ");
        $parentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all child items
        $stmt = $pdo->query("
            SELECT * FROM items 
            WHERE item_type = 'child' 
            ORDER BY name
        ");
        $childItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize child items under their parents
        $items = [];
        foreach ($parentItems as $parent) {
            $parent['sub_items'] = [];
            foreach ($childItems as $child) {
                if ($child['parent_id'] == $parent['id']) {
                    $parent['sub_items'][] = $child;
                }
            }
            $items[] = $parent;
        }
        
        return $items;
    } catch (PDOException $e) {
        // If there's any error, just return regular items
        $stmt = $pdo->query("SELECT * FROM items ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Update an existing item
function updateItem($pdo, $id, $name, $price, $description = '', $parent_id = null, $item_type = 'parent') {
    try {
        // Check if the new columns exist
        $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'item_type'");
        $columnExists = $stmt->rowCount() > 0;
        
        if ($columnExists) {
            $stmt = $pdo->prepare("UPDATE items SET name = ?, price = ?, description = ?, parent_id = ?, item_type = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $parent_id, $item_type, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE items SET name = ?, price = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $id]);
        }
        
        return true;
    } catch (PDOException $e) {
        throw $e;
    }
}

// Delete an item
function deleteItem($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        throw $e;
    }
}

// Get unpaid invoices for a student
function getUnpaidInvoices($pdo, $student_id) {
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.invoice_date,
            i.due_date,
            i.total_amount,
            COALESCE(SUM(p.amount), 0) AS paid_amount,
            (i.total_amount - COALESCE(SUM(p.amount), 0)) AS balance
        FROM invoices i
        LEFT JOIN payments p ON p.invoice_id = i.id
        WHERE i.student_id = ?
        GROUP BY i.id
        HAVING balance > 0
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInvoiceBalance($pdo, $invoice_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT (total_amount - paid_amount) AS balance 
            FROM invoices 
            WHERE id = ?
        ");
        $stmt->execute([$invoice_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting invoice balance: " . $e->getMessage());
        return 0;
    }
}

// Record a payment receipt
function recordPaymentReceipt($pdo, $student_id, $payment_date, $amount, $method, $memo, $invoice_payments) {
    try {
        $pdo->beginTransaction();
        
        // Insert payment receipt
        $stmt = $pdo->prepare("
            INSERT INTO payment_receipts (student_id, payment_date, amount, method, memo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $payment_date, $amount, $method, $memo]);
        $receipt_id = $pdo->lastInsertId();
        
        // Record payments for each invoice
        foreach ($invoice_payments as $invoice_id => $payment_amount) {
            if ($payment_amount > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO payments (receipt_id, invoice_id, student_id, amount)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$receipt_id, $invoice_id, $student_id, $payment_amount]);
                
                // Update invoice status if fully paid
                $stmt = $pdo->prepare("
                    SELECT 
                        (SELECT SUM(amount) FROM payments WHERE invoice_id = ?) as total_paid,
                        (SELECT SUM(quantity * unit_price) FROM invoice_items WHERE invoice_id = ?) as total_amount
                ");
                $stmt->execute([$invoice_id, $invoice_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['total_paid'] >= $result['total_amount']) {
                    $stmt = $pdo->prepare("UPDATE invoices SET status = 'Paid' WHERE id = ?");
                    $stmt->execute([$invoice_id]);
                }
            }
        }
        
        $pdo->commit();
        return $receipt_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Get payment receipt details
function getPaymentReceipt($pdo, $receipt_id) {
    $stmt = $pdo->prepare("
        SELECT pr.*, s.name as student_name, s.email, s.phone
        FROM payment_receipts pr
        JOIN students s ON pr.student_id = s.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$receipt_id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($receipt) {
        $stmt = $pdo->prepare("
            SELECT p.*, i.invoice_date, i.due_date
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            WHERE p.receipt_id = ?
        ");
        $stmt->execute([$receipt_id]);
        $receipt['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $receipt;
}

function createJournalEntry($pdo, $date, $debit_account, $credit_account, $amount, $description = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO journal_entries (date, debit_account, credit_account, amount, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$date, $debit_account, $credit_account, $amount, $description]);
    } catch (PDOException $e) {
        error_log("Error creating journal entry: " . $e->getMessage());
        return false;
    }
}

function getChartOfAccounts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, account_name, account_type, account_code
            FROM chart_of_accounts
            ORDER BY account_type, account_code
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting chart of accounts: " . $e->getMessage());
        return [];
    }
}

function createAccount($pdo, $account_code, $account_name, $account_type) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$account_code, $account_name, $account_type]);
    } catch (PDOException $e) {
        error_log("Error creating account: " . $e->getMessage());
        return false;
    }
}

function updateAccount($pdo, $id, $account_code, $account_name, $account_type) {
    try {
        $stmt = $pdo->prepare("
            UPDATE chart_of_accounts 
            SET account_code = ?, account_name = ?, account_type = ?
            WHERE id = ?
        ");
        return $stmt->execute([$account_code, $account_name, $account_type, $id]);
    } catch (PDOException $e) {
        error_log("Error updating account: " . $e->getMessage());
        return false;
    }
}

function deleteAccount($pdo, $id) {
    try {
        // Check if account has any journal entries
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM journal_entries 
            WHERE debit_account = ? OR credit_account = ?
        ");
        $stmt->execute([$id, $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return false; // Account has transactions, cannot delete
        }
        
        $stmt = $pdo->prepare("DELETE FROM chart_of_accounts WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting account: " . $e->getMessage());
        return false;
    }
}

function getAccountBalance($pdo, $account_id) {
    try {
        // Get total debits
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_debits
            FROM journal_entries
            WHERE debit_account = ?
        ");
        $stmt->execute([$account_id]);
        $debits = $stmt->fetch(PDO::FETCH_ASSOC)['total_debits'];
        
        // Get total credits
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_credits
            FROM journal_entries
            WHERE credit_account = ?
        ");
        $stmt->execute([$account_id]);
        $credits = $stmt->fetch(PDO::FETCH_ASSOC)['total_credits'];
        
        // Calculate balance based on account type
        $stmt = $pdo->prepare("
            SELECT account_type
            FROM chart_of_accounts
            WHERE id = ?
        ");
        $stmt->execute([$account_id]);
        $account_type = $stmt->fetch(PDO::FETCH_ASSOC)['account_type'];
        
        // For assets and expenses, balance = debits - credits
        // For liabilities, equity, and revenue, balance = credits - debits
        if (in_array($account_type, ['Asset', 'Expense'])) {
            return $debits - $credits;
        } else {
            return $credits - $debits;
        }
    } catch (PDOException $e) {
        error_log("Error calculating account balance: " . $e->getMessage());
        return 0;
    }
}

function getChartOfAccountsWithBalances($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, account_code, account_name, account_type
            FROM chart_of_accounts
            ORDER BY account_type, account_code
        ");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add balance to each account
        foreach ($accounts as &$account) {
            $account['balance'] = getAccountBalance($pdo, $account['id']);
        }
        
        return $accounts;
    } catch (PDOException $e) {
        error_log("Error getting chart of accounts: " . $e->getMessage());
        return [];
    }
}

function createAccountWithBalance($pdo, $account_code, $account_name, $account_type, $starting_balance) {
    try {
        // First check if account code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chart_of_accounts WHERE account_code = ?");
        $stmt->execute([$account_code]);
        if ($stmt->fetchColumn() > 0) {
            return "Account code already exists. Please use a different code.";
        }

        $pdo->beginTransaction();
        
        // Insert the account
        $stmt = $pdo->prepare("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$account_code, $account_name, $account_type]);
        $account_id = $pdo->lastInsertId();
        
        // If there's a starting balance, create a journal entry
        if ($starting_balance > 0) {
            // Get the Owner's Equity account ID
            $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '3000'");
            $stmt->execute();
            $equity_account_id = $stmt->fetchColumn();
            
            if (!$equity_account_id) {
                throw new Exception("Owner's Equity account not found. Please create it first.");
            }
            
            // For assets and expenses, debit the account
            // For liabilities, equity, and revenue, credit the account
            if (in_array($account_type, ['Asset', 'Expense'])) {
                $debit_account = $account_id;
                $credit_account = $equity_account_id;
            } else {
                $debit_account = $equity_account_id;
                $credit_account = $account_id;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO journal_entries (date, debit_account, credit_account, amount, description)
                VALUES (CURDATE(), ?, ?, ?, 'Starting Balance')
            ");
            $stmt->execute([$debit_account, $credit_account, $starting_balance]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error creating account with balance: " . $e->getMessage());
        return "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating account with balance: " . $e->getMessage());
        return $e->getMessage();
    }
}


// Example of updated service payment function
function recordServicePayment($pdo, $payment_date, $provider_name, $account_id, $amount, $description) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert expense transaction
        $stmt = $pdo->prepare("
            INSERT INTO expense_transactions 
            (transaction_date, description, amount, account_id, transaction_type, entity_name, entity_type) 
            VALUES (?, ?, ?, ?, 'debit', ?, 'service')
        ");
        $stmt->execute([$payment_date, $description, $amount, $account_id, $provider_name]);
        
        // Update account balance
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        // Commit transaction
        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Example of updated supplier payment function
function recordSupplierPayment($pdo, $payment_date, $supplier_name, $invoice_number, $account_id, $amount, $description) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert expense transaction
        $stmt = $pdo->prepare("
            INSERT INTO expense_transactions 
            (transaction_date, description, amount, account_id, transaction_type, entity_name, entity_type, invoice_number) 
            VALUES (?, ?, ?, ?, 'debit', ?, 'supplier', ?)
        ");
        $stmt->execute([$payment_date, $description, $amount, $account_id, $supplier_name, $invoice_number]);
        
        // Update account balance
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        // Commit transaction
        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
function updateAccountBalance($pdo, $account_id) {
    try {
        // Get the account type (to determine if debits increase or decrease the balance)
        $stmt = $pdo->prepare("SELECT account_type FROM accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            return false;
        }
        
        // Calculate the new balance based on debits and credits
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit
            FROM expense_transactions 
            WHERE account_id = ?
        ");
        $stmt->execute([$account_id]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalDebit = $totals['total_debit'] ?? 0;
        $totalCredit = $totals['total_credit'] ?? 0;
        
        // Calculate balance based on account type
        // Assets and Expenses increase with debits, decrease with credits
        // Liabilities, Equity, and Revenue increase with credits, decrease with debits
        $balance = 0;
        
        if (in_array($account['account_type'], ['Assets', 'Expenses'])) {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }
        
        // Update the account balance
        $update = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $update->execute([$balance, $account_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating account balance: " . $e->getMessage());
        return false;
    }
}
// Example of updated vehicle expense function
function recordVehicleExpense($pdo, $expense_date, $vehicle_id, $expense_type, $account_id, $amount, $odometer, $description) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert expense transaction
        $stmt = $pdo->prepare("
            INSERT INTO expense_transactions 
            (transaction_date, description, amount, account_id, transaction_type, entity_name, entity_type, expense_type, odometer_reading) 
            VALUES (?, ?, ?, ?, 'debit', ?, 'vehicle', ?, ?)
        ");
        $stmt->execute([$expense_date, $description, $amount, $account_id, $vehicle_id, $expense_type, $odometer]);
        
        // Update account balance
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        // Commit transaction
        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
