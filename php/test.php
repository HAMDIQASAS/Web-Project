<?php
// Force all errors to display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Starting...<br>";

require_once 'db_config.php';
echo "Step 2: db_config loaded<br>";

initSession();
echo "Step 3: Session initialized<br>";

$pdo = getDBConnection();
echo "Step 4: DB connection: " . ($pdo ? "OK" : "FAILED") . "<br>";

echo "Step 5: All checks passed!";
