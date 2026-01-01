<?php
/**
 * Migration Runner for Bloomsfield Features
 *
 * This script applies the database schema changes needed for:
 * - Transport zone management
 * - One-time and annual fees
 * - Student activity enrollment
 *
 * Run this script ONCE via browser or CLI: php run_bloomsfield_migration.php
 */

require_once 'config.php';

// For security, only allow this to run in CLI or with a specific token
$allowed = (php_sapi_name() === 'cli') || (isset($_GET['token']) && $_GET['token'] === 'migrate_bloomsfield_2026');

if (!$allowed) {
    die("Unauthorized. Please run via CLI: php run_bloomsfield_migration.php");
}

echo "==============================================\n";
echo "Bloomsfield Features Migration\n";
echo "==============================================\n\n";

try {
    // Read the migration SQL file
    $sql_file = __DIR__ . '/migration_bloomsfield_features.sql';

    if (!file_exists($sql_file)) {
        die("ERROR: Migration file not found at: $sql_file\n");
    }

    $sql_content = file_get_contents($sql_file);

    // Get the school_id (you can hardcode this or prompt)
    if (php_sapi_name() === 'cli') {
        echo "Enter the school_id for Bloomsfield Kindergarten & School: ";
        $handle = fopen("php://stdin", "r");
        $school_id = trim(fgets($handle));
        fclose($handle);
    } else {
        // For browser access, get from GET parameter
        $school_id = $_GET['school_id'] ?? 1;
    }

    if (empty($school_id) || !is_numeric($school_id)) {
        die("ERROR: Invalid school_id provided.\n");
    }

    echo "Using school_id: $school_id\n\n";

    // Replace {SCHOOL_ID} placeholder with actual school_id
    $sql_content = str_replace('{SCHOOL_ID}', $school_id, $sql_content);

    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            // Filter out comments and empty statements
            return !empty($stmt) &&
                   strpos($stmt, '--') !== 0 &&
                   strpos($stmt, '/*') !== 0;
        }
    );

    echo "Found " . count($statements) . " SQL statements to execute.\n\n";

    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $index => $statement) {
        // Skip pure comment lines
        if (preg_match('/^--/', trim($statement))) {
            continue;
        }

        $statement = trim($statement);
        if (empty($statement)) continue;

        // Get first 80 characters for display
        $display_stmt = substr($statement, 0, 80) . (strlen($statement) > 80 ? '...' : '');

        echo "[" . ($index + 1) . "] Executing: $display_stmt\n";

        try {
            $pdo->exec($statement);
            $success_count++;
            echo "    ✓ Success\n";
        } catch (PDOException $e) {
            $error_count++;
            echo "    ✗ Error: " . $e->getMessage() . "\n";

            // Don't stop on errors like "table already exists" or "column already exists"
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "    (Skipping - already exists)\n";
            }
        }
        echo "\n";
    }

    echo "\n==============================================\n";
    echo "Migration Complete!\n";
    echo "==============================================\n";
    echo "Successful statements: $success_count\n";
    echo "Errors/Warnings: $error_count\n";
    echo "\n";

    // Verify tables were created
    echo "Verifying new tables...\n";
    $tables_to_check = [
        'transport_zones',
        'student_transport',
        'one_time_fees_billed',
        'annual_fees_billed',
        'activities',
        'student_activities'
    ];

    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' NOT FOUND\n";
        }
    }

    echo "\n";
    echo "Checking transport zones data...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transport_zones WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $zone_count = $stmt->fetchColumn();
    echo "✓ $zone_count transport zones created\n";

    echo "\nChecking activities data...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM activities WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $activity_count = $stmt->fetchColumn();
    echo "✓ $activity_count activities created\n";

    echo "\nChecking items table columns...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'fee_frequency'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'fee_frequency' added to items table\n";
    } else {
        echo "✗ Column 'fee_frequency' NOT FOUND in items table\n";
    }

    echo "\n==============================================\n";
    echo "NEXT STEPS:\n";
    echo "==============================================\n";
    echo "1. Update existing fee items to set fee_frequency\n";
    echo "   - Tuition, Lunch, Sports: 'recurring'\n";
    echo "   - Admission Fee, Diary, Pouch, Covers: 'one_time'\n";
    echo "   - Personal Accident Insurance: 'annual'\n";
    echo "\n2. Create transport zone management UI\n";
    echo "3. Add student transport assignment\n";
    echo "4. Build activity enrollment interface\n";
    echo "5. Update invoice generation logic\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Migration script completed.\n";
?>
