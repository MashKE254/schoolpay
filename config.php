<?php
/**
 * config.php - Application Configuration
 *
 * SECURITY: This file now uses environment variables from .env
 * NEVER commit .env to version control (it's in .gitignore)
 */

require_once 'vendor/autoload.php';
require_once __DIR__ . '/env_loader.php';

// --- Africa's Talking API Credentials ---
define('AT_USERNAME', env('AT_USERNAME'));
define('AT_API_KEY', env('AT_API_KEY'));
define('AT_SENDER_ID', env('AT_SENDER_ID'));
define('BASE_URL', env('BASE_URL'));

// --- Database Configuration ---
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'school_finance'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// --- Application Configuration ---
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));

// --- Session Configuration ---
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 7200)); // 2 hours
define('SESSION_SECURE', env('SESSION_SECURE', false)); // Set to true in production with HTTPS
define('SESSION_HTTPONLY', env('SESSION_HTTPONLY', true));
define('SESSION_SAMESITE', env('SESSION_SAMESITE', 'Strict'));

// --- Database Connection ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact support.");
    }
}

// --- M-Pesa/Daraja Configuration ---
return [
    'consumer_key'    => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode'       => env('MPESA_SHORTCODE', '600990'),
    'base_url'        => env('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
];

?>