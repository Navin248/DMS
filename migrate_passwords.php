<?php
/**
 * Password Migration Script
 * Migrates existing MD5 passwords to bcrypt (PASSWORD_DEFAULT)
 * Also creates the password_resets table if it doesn't exist.
 * 
 * Run this ONCE, then DELETE this file for security.
 */
require_once 'config/database.php';

echo "<h1>🔧 Password Migration Tool</h1>";
echo "<hr>";

// Step 1: Create password_resets table
echo "<h2>Step 1: Create password_resets table</h2>";
$create_table = "CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    KEY `idx_token` (`token`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($create_table)) {
    echo "<p style='color: green;'>✅ password_resets table created (or already exists)</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
}

// Step 2: Migrate known default passwords from MD5 to bcrypt
echo "<h2>Step 2: Migrate MD5 passwords to bcrypt</h2>";

$known_passwords = [
    'admin' => 'admin123',
    'coordinator1' => 'coord123'
];

foreach ($known_passwords as $username => $plain_password) {
    $md5_hash = md5($plain_password);
    
    // Check if user exists with MD5 hash
    $check = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if password is still MD5 (32 char hex string)
        if (strlen($user['password']) === 32 && ctype_xdigit($user['password'])) {
            $bcrypt_hash = password_hash($plain_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $bcrypt_hash, $user['id']);
            
            if ($update->execute()) {
                echo "<p style='color: green;'>✅ <strong>$username</strong>: Migrated from MD5 to bcrypt</p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>$username</strong>: Error - " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ <strong>$username</strong>: Already using bcrypt (skipped)</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>$username</strong>: User not found (skipped)</p>";
    }
}

// Step 3: Check for any remaining MD5 passwords
echo "<h2>Step 3: Check remaining passwords</h2>";
$all_users = $conn->query("SELECT id, username, password FROM users");
$md5_count = 0;
$bcrypt_count = 0;

while ($u = $all_users->fetch_assoc()) {
    if (strlen($u['password']) === 32 && ctype_xdigit($u['password'])) {
        $md5_count++;
        echo "<p style='color: orange;'>⚠️ <strong>" . htmlspecialchars($u['username']) . "</strong>: Still using MD5 hash (will need manual reset)</p>";
    } else {
        $bcrypt_count++;
    }
}

echo "<hr>";
echo "<h2>📊 Summary</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Bcrypt passwords</strong></td><td style='color: green;'>$bcrypt_count</td></tr>";
echo "<tr><td><strong>MD5 passwords remaining</strong></td><td style='color: " . ($md5_count > 0 ? 'red' : 'green') . ";'>$md5_count</td></tr>";
echo "</table>";

if ($md5_count > 0) {
    echo "<p style='color: orange; margin-top: 10px;'>⚠️ Some users still have MD5 passwords. They will need to use 'Forgot Password' to reset.</p>";
} else {
    echo "<p style='color: green; margin-top: 10px;'>✅ All passwords are now using bcrypt!</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANT:</strong> Delete this file (<code>migrate_passwords.php</code>) after running it!</p>";
echo "<p><a href='login.php'>→ Go to Login</a></p>";
?>
