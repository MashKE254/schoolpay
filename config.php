<?php
// config.php
// Database configuration file
require_once 'vendor/autoload.php';

// --- Africa's Talking API Credentials ---
define('AT_USERNAME', 'blooms-mis'); // Use 'sandbox' for testing, or your live username
define('AT_API_KEY', 'atsk_c5a9088721049e58369f9897c2e083c2b845d7ac3ea8a8c1f0f504892b2f751c7b57e638'); // Your actual API Key
define('AT_SENDER_ID', 'Bloomsfield');
define('BASE_URL', 'https://dc1495eb818e.ngrok-free.app/schoolpay');

define('DB_HOST', 'localhost');
define('DB_NAME', 'school_finance');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Configuration for other services (like Daraja)
return [
    // Sandbox credentials from Daraja portal
    'consumer_key'    => '9MuBesj2QnEyB0BIqykeAkJjENEIuG9CmMDrIlYdi84i70Ab',
    'consumer_secret' => 'ZG1ksJ9vfMl41TjtcFsJcji1hCZE8DYjmn2Iiux3RtroWfRyOIcR2limDxCj9EoP',
    'shortcode'       => '600990',              // Sandbox shortcode
    'base_url'        => 'https://sandbox.safaricom.co.ke',

];
    
?>