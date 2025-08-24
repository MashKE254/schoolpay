<?php
/**
 * process_requisition.php - v5 (Final Fix)
 * Handles the server-side logic for uploading and parsing a weekly expense requisition CSV.
 * This version uses a highly robust parsing method to handle formatting inconsistencies.
 */

require_once 'config.php';
require_once 'functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$school_id = $_SESSION['school_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (empty($school_id) || empty($user_id)) {
    header("Location: login.php?error=session_expired");
    exit();
}

// This function safely converts a string to a float, removing commas and currency symbols.
function safe_float_val($string) {
    return (float)preg_replace('/[^0-9.]/', '', $string);
}


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    if (!isset($_FILES['requisition_csv']) || $_FILES['requisition_csv']['error'] != UPLOAD_ERR_OK) {
        throw new Exception("File upload failed. Error code: " . ($_FILES['requisition_csv']['error'] ?? 'Unknown'));
    }

    $file_tmp_path = $_FILES['requisition_csv']['tmp_name'];
    $file_name = $_FILES['requisition_csv']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_ext !== 'csv') {
        throw new Exception("Invalid file type. Please upload a CSV file.");
    }

    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    $payment_account_id = (int)($_POST['payment_account_id'] ?? 0);

    if (empty($payment_account_id)) {
        throw new Exception("Missing required information. Please select a payment account.");
    }

    $pdo->beginTransaction();

    $stmt_batch = $pdo->prepare(
        "INSERT INTO requisition_batches (school_id, user_id, transaction_date, payment_account_id, original_filename, status)
         VALUES (?, ?, ?, ?, ?, 'pending_categorization')"
    );
    $stmt_batch->execute([$school_id, $user_id, $transaction_date, $payment_account_id, $file_name]);
    $batch_id = $pdo->lastInsertId();

    $total_requisition_amount = 0;
    $header_map = [];
    $data_started = false;

    if (($handle = fopen($file_tmp_path, 'r')) !== FALSE) {
        $stmt_item = $pdo->prepare(
            "INSERT INTO requisition_items (batch_id, description, quantity, unit_cost, total_cost)
             VALUES (?, ?, ?, ?, ?)"
        );

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (!$data_started) {
                $header_found = false;
                foreach ($data as $index => $value) {
                    if (str_contains(strtoupper($value), 'PARTICULARS')) {
                        $header_found = true;
                        break;
                    }
                }
                if ($header_found) {
                    foreach ($data as $index => $col_name) {
                        $col_name_upper = strtoupper(trim($col_name));
                        if (str_contains($col_name_upper, 'PARTICULARS')) $header_map['description'] = $index;
                        if (str_contains($col_name_upper, 'UNIT PRICE')) $header_map['unit_cost'] = $index;
                        if (str_contains($col_name_upper, 'QUANTITY')) $header_map['quantity'] = $index;
                        if (str_contains($col_name_upper, 'AMOUNT')) $header_map['total_cost'] = $index;
                    }
                    $data_started = true;
                }
                continue;
            }

            if (empty($header_map)) continue;

            $description = isset($header_map['description']) ? trim($data[$header_map['description']]) : '';
            if (empty($description) || str_contains(strtoupper($description), 'SUB-TOTAL') || str_contains(strtoupper($description), 'TOTAL')) {
                continue;
            }

            // --- Robust Cost Calculation Logic ---
            $total_cost = 0;
            $quantity = 1.0;
            $unit_cost = 0;

            // 1. Try to get total cost directly from the 'AMOUNT' column.
            if (isset($header_map['total_cost']) && !empty($data[$header_map['total_cost']])) {
                $total_cost = safe_float_val($data[$header_map['total_cost']]);
            }
            
            // 2. If total is still zero, try to calculate from unit price and quantity.
            if ($total_cost == 0 && isset($header_map['unit_cost']) && !empty($data[$header_map['unit_cost']])) {
                $unit_cost = safe_float_val($data[$header_map['unit_cost']]);
                
                if (isset($header_map['quantity']) && !empty($data[$header_map['quantity']])) {
                    $quantity = safe_float_val($data[$header_map['quantity']]);
                }
                // Ensure quantity is at least 1 to avoid multiplying by zero.
                if ($quantity == 0) $quantity = 1;

                $total_cost = $unit_cost * $quantity;
            }

            // 3. As a final check, derive unit_cost if it's missing.
            if ($unit_cost == 0 && $total_cost > 0) {
                 if ($quantity == 0) $quantity = 1; // prevent division by zero
                 $unit_cost = $total_cost / $quantity;
            }

            if ($total_cost > 0) {
                $stmt_item->execute([$batch_id, $description, $quantity, $unit_cost, $total_cost]);
                $total_requisition_amount += $total_cost;
            }
        }
        fclose($handle);
    }
    
    if ($total_requisition_amount == 0) {
        // This exception will now only be thrown if the file is truly empty or unreadable.
        throw new Exception("Could not find any valid items with an amount greater than zero in the uploaded file. Please check the file format and ensure the 'AMOUNT' or 'Unit Price' columns contain valid numbers.");
    }

    $stmt_update_batch = $pdo->prepare("UPDATE requisition_batches SET total_amount = ? WHERE id = ?");
    $stmt_update_batch->execute([$total_requisition_amount, $batch_id]);

    $pdo->commit();

    header("Location: categorize_requisition.php?batch_id=" . $batch_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Redirect back to the expense page with a clear error message.
    header("Location: expense_management.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>
