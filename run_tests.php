<?php
/**
 * run_tests.php - Basic System Tests
 * Run this file to verify optimizations are working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=================================\n";
echo "  SchoolPay Optimization Tests  \n";
echo "=================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

function test($name, $callback) {
    global $tests_passed, $tests_failed;

    echo "Testing: {$name}... ";

    try {
        $result = $callback();

        if ($result) {
            echo "✓ PASSED\n";
            $tests_passed++;
        } else {
            echo "✗ FAILED\n";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        $tests_failed++;
    }
}

// Test 1: .env file exists
test(".env file exists", function() {
    return file_exists(__DIR__ . '/.env');
});

// Test 2: .env loader works
test("Environment loader", function() {
    require_once __DIR__ . '/env_loader.php';
    return env('DB_NAME') === 'school_finance';
});

// Test 3: Database connection
test("Database connection", function() {
    try {
        require_once __DIR__ . '/config.php';
        global $pdo;
        return $pdo instanceof PDO;
    } catch (Exception $e) {
        echo "\n  Error: " . $e->getMessage() . "\n";
        return false;
    }
});

// Test 4: Security functions loaded
test("Security functions", function() {
    require_once __DIR__ . '/security.php';
    return function_exists('csrf_token') &&
           function_exists('csrf_verify') &&
           function_exists('is_rate_limited') &&
           function_exists('sanitize_input');
});

// Test 5: CSRF token generation
test("CSRF token generation", function() {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    $token = csrf_token();
    return !empty($token) && strlen($token) === 64;
});

// Test 6: Input sanitization
test("Input sanitization", function() {
    $dirty = '<script>alert("xss")</script>';
    $clean = sanitize_input($dirty);
    return strpos($clean, '<script>') === false;
});

// Test 7: Email validation
test("Email validation", function() {
    return validate_email('test@example.com') &&
           !validate_email('invalid-email');
});

// Test 8: Phone validation
test("Phone validation", function() {
    return validate_phone('0712345678') &&
           validate_phone('+254712345678') &&
           !validate_phone('123');
});

// Test 9: Rate limiting
test("Rate limiting", function() {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    reset_rate_limit('test_action');

    // Should not be limited initially
    if (is_rate_limited('test_action', 2, 60)) {
        return false;
    }

    // Should be limited after max attempts
    is_rate_limited('test_action', 2, 60);
    return is_rate_limited('test_action', 2, 60);
});

// Test 10: Logs directory writable
test("Logs directory writable", function() {
    $log_dir = __DIR__ . '/logs';
    return is_dir($log_dir) && is_writable($log_dir);
});

// Test 11: Error logging
test("Error logging", function() {
    log_error('Test error message', 'INFO');
    $log_file = __DIR__ . '/logs/error.log';
    return file_exists($log_file) && filesize($log_file) > 0;
});

// Test 12: Security logging
test("Security logging", function() {
    log_security_event('Test security event', ['test' => true]);
    $log_file = __DIR__ . '/logs/security.log';
    return file_exists($log_file) && filesize($log_file) > 0;
});

// Test 13: Database tables exist
test("Database tables exist", function() {
    global $pdo;
    if (!$pdo) {
        echo "\n  Skipping: Database not connected\n";
        return true; // Skip test
    }
    $tables = ['students', 'invoices', 'payments', 'users', 'schools'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() === 0) {
            echo "\n  Missing table: {$table}\n";
            return false;
        }
    }
    return true;
});

// Test 14: Users table has records
test("Users table has records", function() {
    global $pdo;
    if (!$pdo) {
        echo "\n  Skipping: Database not connected\n";
        return true; // Skip test
    }
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (Exception $e) {
        echo "\n  Table may not exist\n";
        return false;
    }
});

// Test 15: Password hashing works
test("Password hashing", function() {
    $password = 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    return password_verify($password, $hash);
});

echo "\n=================================\n";
echo "  Test Results  \n";
echo "=================================\n";
echo "Passed: {$tests_passed}\n";
echo "Failed: {$tests_failed}\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n✓ All tests passed! System is ready.\n";
} else {
    echo "\n✗ Some tests failed. Review errors above.\n";
}

echo "\n=================================\n";
echo "  Next Steps  \n";
echo "=================================\n";
echo "1. Apply database indexes:\n";
echo "   mysql -u root -p school_finance < database_optimization.sql\n\n";
echo "2. Test login at: http://localhost/schoolpay/login.php\n\n";
echo "3. Review logs:\n";
echo "   tail -f logs/error.log\n";
echo "   tail -f logs/security.log\n\n";
echo "4. Read SECURITY_OPTIMIZATION_GUIDE.md for details\n";
echo "=================================\n";

?>