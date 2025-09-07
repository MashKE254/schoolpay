<?php
require_once 'config.php';

echo "<h3>Seeding System Payroll Items for All Schools...</h3>";

try {
    $pdo->beginTransaction();

    // Define the essential system deductions
    $system_items = [
        ['name' => 'PAYE', 'type' => 'Deduction'],
        ['name' => 'NHIF', 'type' => 'Deduction'],
        ['name' => 'NSSF', 'type' => 'Deduction'],
        ['name' => 'Housing Levy', 'type' => 'Deduction'],
    ];

    // Get all unique school IDs from the users table as a fallback
    $stmt_schools = $pdo->query("SELECT DISTINCT id FROM schools");
    $school_ids = $stmt_schools->fetchAll(PDO::FETCH_COLUMN);

    if (empty($school_ids)) {
        die("<h3>No schools found in the database. Please add a school before running this script.</h3>");
    }

    $stmt_insert = $pdo->prepare(
        "INSERT IGNORE INTO payroll_meta (school_id, name, type, is_system) VALUES (?, ?, ?, 1)"
    );
    
    foreach ($school_ids as $school_id) {
        echo "<p>Processing School ID: $school_id</p><ul>";
        foreach ($system_items as $item) {
            $stmt_insert->execute([$school_id, $item['name'], $item['type']]);
            if ($stmt_insert->rowCount() > 0) {
                echo "<li>Added '{$item['name']}' item.</li>";
            } else {
                echo "<li>'{$item['name']}' item already exists. Skipped.</li>";
            }
        }
        echo "</ul>";
    }

    $pdo->commit();
    echo "<h4>Script 1 (Items) completed successfully! You can now proceed to the next step.</h4>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("An error occurred: " . $e->getMessage());
}