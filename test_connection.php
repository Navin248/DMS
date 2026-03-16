<?php
echo "<h1>Disaster Relief System - Diagnostic Test</h1><hr>";

// Test 1: Database Connection
echo "<h2>1️⃣ Testing Database Connection...</h2>";
require_once 'config/database.php';

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection Failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database Connection Successful</p>";
}

// Test 2: Check if Users Table Exists
echo "<h2>2️⃣ Checking Users Table...</h2>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Users table not found! Please import database_schema.sql</p>";
} else {
    echo "<p style='color: green;'>✅ Users table exists</p>";
}

// Test 3: Check Admin User
echo "<h2>3️⃣ Checking Admin User...</h2>";
$result = $conn->query("SELECT * FROM users WHERE username='admin'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Admin user not found! Database schema not imported.</p>";
} else {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ Admin user found</p>";
    echo "<p><strong>Username:</strong> " . $user['username'] . "</p>";
    echo "<p><strong>Password Hash:</strong> " . $user['password'] . "</p>";
    echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
    
    // Test password verification
    echo "<h2>4️⃣ Testing Password Verification...</h2>";
    if (password_verify('admin', $user['password'])) {
        echo "<p style='color: green;'>✅ Password verification works!</p>";
    } else {
        echo "<p style='color: red;'>❌ Password verification failed!</p>";
        echo "<p>Generated hash for 'admin': " . password_hash('admin', PASSWORD_BCRYPT) . "</p>";
    }
}

// Test 4: All Tables
echo "<h2>5️⃣ Checking All Required Tables...</h2>";
$tables = ['users', 'disasters', 'resources', 'requests', 'allocations'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ $table</p>";
    } else {
        echo "<p style='color: red;'>❌ $table</p>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
?>
