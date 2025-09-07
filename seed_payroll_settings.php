<?php
require_once 'config.php';

echo "<h3>Seeding Payroll Settings for All Schools...</h3>";

try {
    $pdo->beginTransaction();

    // Define default statutory settings. Brackets are stored as JSON strings.
    $default_settings = [
        ['key' => 'nssf_rate', 'value' => '0.06', 'desc' => 'NSSF contribution rate (Tier 1).'],
        ['key' => 'nssf_cap', 'value' => '1080', 'desc' => 'Maximum monthly NSSF contribution amount.'],
        ['key' => 'housing_levy_rate', 'value' => '0.015', 'desc' => 'Housing Levy contribution rate.'],
        ['key' => 'personal_relief', 'value' => '2400', 'desc' => 'Monthly personal tax relief amount.'],
        ['key' => 'insurance_relief_rate', 'value' => '0.15', 'desc' => 'Insurance relief rate applied to NHIF contribution.'],
        ['key' => 'nhif_brackets', 'value' => '[
            {"max_gross": 5999, "deduction": 150},
            {"max_gross": 7999, "deduction": 300},
            {"max_gross": 11999, "deduction": 400},
            {"max_gross": 14999, "deduction": 500},
            {"max_gross": 19999, "deduction": 600},
            {"max_gross": 24999, "deduction": 750},
            {"max_gross": 29999, "deduction": 850},
            {"max_gross": 34999, "deduction": 900},
            {"max_gross": 39999, "deduction": 950},
            {"max_gross": 44999, "deduction": 1000},
            {"max_gross": 49999, "deduction": 1100},
            {"max_gross": 59999, "deduction": 1200},
            {"max_gross": 69999, "deduction": 1300},
            {"max_gross": 79999, "deduction": 1400},
            {"max_gross": 89999, "deduction": 1500},
            {"max_gross": 99999, "deduction": 1600},
            {"max_gross": "Infinity", "deduction": 1700}
        ]', 'desc' => 'NHIF contribution brackets (JSON format).'],
        ['key' => 'paye_brackets', 'value' => '[
            {"max_annual": 288000, "rate": 0.10, "base_tax": 0, "prev_max": 0},
            {"max_annual": 388000, "rate": 0.25, "base_tax": 28800, "prev_max": 288000},
            {"max_annual": "Infinity", "rate": 0.30, "base_tax": 53800, "prev_max": 388000}
        ]', 'desc' => 'PAYE tax brackets based on Annual Taxable Pay (JSON format).']
    ];

    // Get all school IDs
    $stmt_schools = $pdo->query("SELECT id FROM schools");
    $school_ids = $stmt_schools->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($school_ids)) {
        die("<h3>No schools found in the database. Please add a school before running this script.</h3>");
    }

    $stmt_insert = $pdo->prepare(
        "INSERT IGNORE INTO payroll_settings (school_id, setting_key, setting_value, description) VALUES (?, ?, ?, ?)"
    );

    foreach ($school_ids as $school_id) {
        echo "<p>Processing School ID: $school_id</p><ul>";
        foreach ($default_settings as $setting) {
            $stmt_insert->execute([$school_id, $setting['key'], $setting['value'], $setting['desc']]);
            if ($stmt_insert->rowCount() > 0) {
                echo "<li>Added setting: '{$setting['key']}'.</li>";
            } else {
                echo "<li>Setting '{$setting['key']}' already exists. Skipped.</li>";
            }
        }
        echo "</ul>";
    }

    $pdo->commit();
    echo "<h4>Script 2 (Settings) completed successfully! You can now delete both seed scripts.</h4>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("An error occurred: " . $e->getMessage());
}