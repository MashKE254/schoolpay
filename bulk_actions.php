<?php
/**
 * bulk_actions.php - A control center for major administrative tasks.
 * Currently features "Promote Students & Generate Invoices".
 */

require_once 'config.php';
require_once 'functions.php';

// --- POST REQUEST HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    $school_id = $_SESSION['school_id'];
    
    if ($_POST['action'] === 'promote_and_invoice') {
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        $notes = "Auto-generated invoice for the new term.";
        
        $promoted_count = 0;
        $invoiced_count = 0;
        $skipped_count = 0;
        $errors = [];

        try {
            $pdo->beginTransaction();

            // 1. Get all active students with their current and next class IDs
            $stmt = $pdo->prepare("
                SELECT s.id as student_id, s.name as student_name, s.class_id as current_class_id, c.next_class_id
                FROM students s
                JOIN classes c ON s.class_id = c.id
                WHERE s.school_id = ? AND s.status = 'active' AND s.class_id IS NOT NULL AND c.next_class_id IS NOT NULL
            ");
            $stmt->execute([$school_id]);
            $students_to_promote = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Get all invoice templates linked to classes
            $stmt = $pdo->prepare("SELECT id, class_id, items FROM invoice_templates WHERE school_id = ? AND class_id IS NOT NULL");
            $stmt->execute([$school_id]);
            $templates_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $templates = [];
            foreach ($templates_raw as $tpl) {
                $templates[$tpl['class_id']] = ['id' => $tpl['id'], 'items' => json_decode($tpl['items'], true)];
            }

            // 3. Process each student
            foreach ($students_to_promote as $student) {
                $next_class_id = $student['next_class_id'];

                // Check if a template exists for the student's next class
                if (isset($templates[$next_class_id])) {
                    $template = $templates[$next_class_id];

                    // Promote student to the new class
                    $update_stmt = $pdo->prepare("UPDATE students SET class_id = ? WHERE id = ?");
                    $update_stmt->execute([$next_class_id, $student['student_id']]);
                    $promoted_count++;

                    // Create the new invoice using the template
                    createInvoice($pdo, $school_id, $student['student_id'], $invoice_date, $due_date, $template['items'], $notes);
                    $invoiced_count++;

                } else {
                    $skipped_count++;
                    $errors[] = "Skipped " . htmlspecialchars($student['student_name']) . ": No invoice template is linked to their next class.";
                }
            }

            $pdo->commit();
            $summary = "Process complete. Promoted: $promoted_count. Invoiced: $invoiced_count. Skipped: $skipped_count.";
            $_SESSION['success_message'] = $summary;
            if(!empty($errors)) {
                 $_SESSION['warning_message'] = implode("<br>", $errors);
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        }

        header("Location: bulk_actions.php");
        exit();
    }
}

require_once 'header.php';

?>
<div class="page-header">
    <div class="page-header-title">
        <h1><i class="fas fa-cogs"></i> Bulk Actions</h1>
        <p>Perform major administrative tasks like student promotion and bulk invoicing.</p>
    </div>
</div>

<?php
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['warning_message'])) {
    echo '<div class="alert alert-warning">' . $_SESSION['warning_message'] . '</div>';
    unset($_SESSION['warning_message']);
}
?>

<div class="card">
    <h3><i class="fas fa-user-graduate"></i> Promote Students & Generate Next Term's Invoices</h3>
    <p>This tool will automatically promote all <strong>active</strong> students to their designated "next class" and generate a new invoice for the upcoming term based on the template linked to that class.</p>
    <div class="alert alert-warning">
        <strong>Warning:</strong> This is an irreversible action. Ensure your class promotion paths and invoice templates are correctly configured in the Customer Center before proceeding.
    </div>
    <form action="bulk_actions.php" method="POST" onsubmit="return confirm('Are you absolutely sure you want to proceed? This will promote students and generate new invoices for the next term.');">
        <input type="hidden" name="action" value="promote_and_invoice">
        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="invoice_date">Invoice Date for New Term</label>
                <input type="date" name="invoice_date" id="invoice_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date for New Term</label>
                <input type="date" name="due_date" id="due_date" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-danger"><i class="fas fa-arrow-right"></i> Proceed with Promotion and Invoicing</button>
        </div>
    </form>
</div>