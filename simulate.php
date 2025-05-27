<?php
// simulate.php — run from CLI to fire a sandbox C2B request
require __DIR__ . '/mpesa.php';
$config = require __DIR__ . '/config.php';

if ($argc < 4) {
    echo "Usage: php simulate.php <studentNumber> <msisdn> <amount>\n";
    exit(1);
}

[$script, $studentNumber, $msisdn, $amount] = $argv;

$mpesa = new Mpesa($config);

try {
    $resp = $mpesa->simulateC2B($msisdn, $studentNumber, (float)$amount);
    echo "Simulation response:\n";
    print_r($resp);
} catch (Exception $e) {
    echo "Error simulating C2B: ", $e->getMessage(), "\n";
}
