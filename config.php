<?php
// config.php
// Database configuration file

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

return [
    // Sandbox credentials from Daraja portal
    'consumer_key'    => '9MuBesj2QnEyB0BIqykeAkJjENEIuG9CmMDrIlYdi84i70Ab',
    'consumer_secret' => 'ZG1ksJ9vfMl41TjtcFsJcji1hCZE8DYjmn2Iiux3RtroWfRyOIcR2limDxCj9EoP',
    'shortcode'       => '600990',              // Sandbox shortcode
    'base_url'        => 'https://sandbox.safaricom.co.ke',

];
    
?>
