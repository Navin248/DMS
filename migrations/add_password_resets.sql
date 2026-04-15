-- ============================================================================
-- MIGRATION: Add password_resets table for token-based password reset
-- Run this migration to enable the Forgot Password feature
-- ============================================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    KEY `idx_token` (`token`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- MIGRATE EXISTING PASSWORDS FROM MD5 TO BCRYPT
-- Default passwords: admin = admin123, coordinator1 = coord123
-- ============================================================================

-- Update admin password (admin123) from MD5 to bcrypt
UPDATE `users` SET `password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE `username` = 'admin' AND `password` = '0192023a7bbd73250516f069df18b500';

-- Update coordinator1 password (coord123) from MD5 to bcrypt  
UPDATE `users` SET `password` = '$2y$10$zGpWLkXbS0eNdOxPjKQrTuqFJmXvk.5r9y3dKjWx8nqBdXvLhKyHu' WHERE `username` = 'coordinator1' AND `password` = '3b5d5c3712955042212316173ccf37be';
