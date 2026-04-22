<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'disaster_relief_system');

// Create Connection
$conn = null;
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check Connection
    if ($conn->connect_error) {
        $db_error = $conn->connect_error;
        $conn = null;
        error_log("Database Connection Failed: " . $db_error);
    } else {
        // Set charset to UTF-8
        $conn->set_charset("utf8");
    }
} catch (Exception $e) {
    $conn = null;
    error_log("Database Connection Error: " . $e->getMessage());
}
?>