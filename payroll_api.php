<?php
/**
 * payroll_api.php
 *
 * This script acts as a simple API endpoint to handle AJAX requests
 * from the Payroll Settings tab for managing payroll meta items (allowances/deductions).
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}
$school_id = $_SESSION['school_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$response = ['success' => false, 'error' => 'Invalid action.'];

try {
    switch ($action) {

        case 'get_payroll_settings':
            $stmt = $pdo->prepare("SELECT setting_key, setting_value, description FROM payroll_settings WHERE school_id = ?");
            $stmt->execute([$school_id]);
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $settings];
            break;

        case 'save_payroll_settings':
            $settings_data = $_POST['settings'] ?? [];
            if (empty($settings_data)) {
                throw new Exception("No settings data received.");
            }
            
            $pdo->beginTransaction();
            $stmt_update = $pdo->prepare(
                "UPDATE payroll_settings SET setting_value = ? WHERE school_id = ? AND setting_key = ?"
            );
            foreach($settings_data as $key => $value) {
                // Special handling for JSON fields to ensure they are well-formed
                if ($key === 'nhif_brackets' || $key === 'paye_brackets') {
                    json_decode($value); // This will throw an error if JSON is invalid
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Invalid JSON format for '$key'. Please check your brackets and commas.");
                    }
                }
                $stmt_update->execute([$value, $school_id, $key]);
            }
            $pdo->commit();
            
            $response = ['success' => true, 'message' => 'Statutory settings saved successfully.'];
            break;
            
        case 'get_meta_items':
            $stmt = $pdo->prepare("SELECT * FROM payroll_meta WHERE school_id = ? ORDER BY type, name");
            $stmt->execute([$school_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $items];
            break;

        case 'add_meta_item':
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');

            if (empty($name) || !in_array($type, ['Earning', 'Deduction'])) {
                throw new Exception("Invalid name or type provided.");
            }

            $stmt = $pdo->prepare("INSERT INTO payroll_meta (school_id, name, type) VALUES (?, ?, ?)");
            $stmt->execute([$school_id, $name, $type]);
            $response = ['success' => true];
            break;

        case 'delete_meta_item':
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                // -- MODIFICATION START --
                // Add a condition to prevent deleting system-managed items.
                $stmt = $pdo->prepare("DELETE FROM payroll_meta WHERE id = ? AND school_id = ? AND is_system = 0");
                // -- MODIFICATION END --
                
                $stmt->execute([$id, $school_id]);

                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true];
                } else {
                    throw new Exception("Item not found or it is a system-protected item that cannot be deleted.");
                }

            } else {
                 throw new Exception("Invalid ID provided.");
            }
            break;
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;

