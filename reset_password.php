<?php
require_once 'config/database.php';

echo "<h1>Reset Admin Password</h1>";

// Generate a fresh password hash for 'admin'
$password = 'admin';
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

echo "<p>Resetting admin password to: <strong>$password</strong></p>";
echo "<p>New Hash: " . $hashed_password . "</p>";

// Update the password
$query = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "<p style='color: green;'><strong>✅ Password reset successfully!</strong></p>";
    echo "<p>Try logging in again with:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to Login →</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
}
?>
