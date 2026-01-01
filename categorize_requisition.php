<?php
/**
 * categorize_requisition.php - v3 with Interpretation Engine
 *
 * This script displays items from a requisition batch and uses a rule-based
 * engine to interpret item descriptions and automatically suggest an expense category.
 */

require_once 'config.php';
require_once 'functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    header("Location: login.php?error=session_expired");
    exit();
}

$school_id = $_SESSION['school_id'];
$error_message = '';
$batch = null;
$batch_items = [];
$expense_accounts = [];

/**
 * An intelligent engine to suggest an expense account based on item description.
 *
 * @param string $description The expense item description.
 * @param array $account_map An associative array mapping lowercase account names to their IDs.
 * @return int|null The suggested account ID or null if no confident match is found.
 */
function getSuggestedAccountId($description, $account_map) {
    $description_lower = strtolower($description);

    // Rule 1: Salaries & Wages
    // Identifies casual workers by name/phone pattern or common names from samples.
    if (preg_match('/[a-zA-Z\s]+-\(\d{10}\)/', $description) || preg_match('/(joseph|dennis|teresiah|rahab|sarah|meryln|william)/i', $description_lower)) {
        if (isset($account_map['salaries & wages'])) {
            return $account_map['salaries & wages'];
        }
    }

    // Rule 2: Food & Beverages
    // Matches common food, grocery, and kitchen supply keywords.
    $food_keywords = ['milk', 'spinach', 'tomatoes', 'tomatoe', 'carrots', 'carrot', 'fruits', 'fruit', 'bread', 'dhania', 'saumu', 'water', 'cabbage', 'leek', 'firewood', 'charcoal', 'salt', 'meat', 'food'];
    foreach ($food_keywords as $keyword) {
        if (str_contains($description_lower, $keyword)) {
            if (isset($account_map['food & beverages'])) {
                return $account_map['food & beverages'];
            }
        }
    }

    // Rule 3: Vehicle & Transport Expenses (Maps to Miscellaneous or a specific vehicle account)
    $vehicle_keywords = ['fuel', 'mechanic', 'car wash', 'breakpads', 'vans', 'inspection'];
    foreach ($vehicle_keywords as $keyword) {
        if (str_contains($description_lower, $keyword)) {
            // If a specific 'Vehicle Expense' account exists, use it. Otherwise, fallback to Miscellaneous.
            if (isset($account_map['vehicle expenses'])) {
                return $account_map['vehicle expenses'];
            }
            if (isset($account_map['miscellaneous'])) {
                return $account_map['miscellaneous'];
            }
        }
    }

    // Fallback for common items that can be categorized as Miscellaneous
    $misc_keywords = ['office', 'uniforms', 'swimming'];
     foreach ($misc_keywords as $keyword) {
        if (str_contains($description_lower, $keyword)) {
            if (isset($account_map['miscellaneous'])) {
                return $account_map['miscellaneous'];
            }
        }
    }

    // If no specific rule matches, return null to prompt user for manual selection.
    return null;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['batch_id'])) {
        $batch_id = (int)$_GET['batch_id'];
        
        try {
            // Fetch batch details
            $stmt_batch = $pdo->prepare("SELECT rb.*, a.account_name FROM requisition_batches rb JOIN accounts a ON rb.payment_account_id = a.id WHERE rb.id = ? AND rb.school_id = ?");
            $stmt_batch->execute([$batch_id, $school_id]);
            $batch = $stmt_batch->fetch(PDO::FETCH_ASSOC);

            if ($batch) {
                if ($batch['status'] === 'processed') {
                    header("Location: expense_management.php?error=" . urlencode("This batch has already been processed."));
                    exit();
                }

                // Fetch batch items
                $stmt_items = $pdo->prepare("SELECT * FROM requisition_items WHERE batch_id = ? ORDER BY id ASC");
                $stmt_items->execute([$batch_id]);
                $batch_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                // Fetch all expense accounts
                $expense_accounts = getAccountsByType($pdo, $school_id, 'expense');
                
                // Create a map of lowercase account names to IDs for the engine
                $account_map = [];
                foreach ($expense_accounts as $acc) {
                    $account_map[strtolower($acc['account_name'])] = $acc['id'];
                }

                // --- Run Interpretation Engine ---
                foreach ($batch_items as &$item) {
                    $item['suggested_account_id'] = getSuggestedAccountId($item['description'], $account_map);
                }
                unset($item);

            } else {
                header("Location: expense_management.php?error=batch_not_found");
                exit();
            }
        } catch(Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
        }
    } else {
        header("Location: expense_management.php?tab=requisition");
        exit();
    }
} else {
    header("Location: expense_management.php?tab=requisition");
    exit();
}

require_once 'header.php';
?>

<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-magic"></i> Categorize Requisition Items</h1>
        <p>Review the suggested categories and complete the process.</p>
    </div>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<?php if ($batch && !empty($batch_items)): ?>
<div class="card">
    <div style="padding: 1rem; border-bottom: 1px solid #eee; margin-bottom: 1rem;">
        <p style="margin: 0;"><strong>File:</strong> <?= htmlspecialchars($batch['original_filename']) ?></p>
        <p style="margin: 0;"><strong>Transaction Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($batch['transaction_date']))) ?></p>
        <p style="margin: 0;"><strong>Total Amount:</strong> <?= format_currency($batch['total_amount']) ?></p>
        <p style="margin: 0;"><strong>Paying From Account:</strong> <?= htmlspecialchars($batch['account_name']) ?></p>
    </div>

    <form action="process_categorized_requisition.php" method="POST">
        <input type="hidden" name="action" value="process_categorized_items">
        <input type="hidden" name="batch_id" value="<?= htmlspecialchars($batch['id']) ?>">
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount-header">Total Cost</th>
                        <th>Assign Expense Account (Auto-Suggested)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batch_items as $index => $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td class="amount"><strong><?= format_currency($item['total_cost']) ?></strong></td>
                        <td>
                            <input type="hidden" name="items[<?= $index ?>][id]" value="<?= $item['id'] ?>">
                            <select name="items[<?= $index ?>][account_id]" class="form-control" required>
                                <option value="">-- Manual Selection Required --</option>
                                <?php foreach ($expense_accounts as $acc): ?>
                                    <?php $selected = (isset($item['suggested_account_id']) && $item['suggested_account_id'] == $acc['id']) ? 'selected' : ''; ?>
                                    <option value="<?= $acc['id'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <a href="expense_management.php?tab=requisition" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-success"><i class="fas fa-check"></i> Process Requisition</button>
        </div>
    </form>
</div>
<?php elseif (!$error_message): ?>
    <div class="alert alert-warning">No requisition batch found. Please <a href="expense_management.php?tab=requisition">upload a new file</a>.</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>