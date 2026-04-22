-- ============================================================================
-- MIGRATION: Add email column to users table
-- Run this migration on existing databases to add email support
-- ============================================================================

-- Step 1: Add email column
ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `username`;

-- Step 2: Add unique index on email (only for non-null values)
ALTER TABLE `users` ADD UNIQUE KEY `unique_email` (`email`);

-- Step 3: Set default emails for existing sample users
UPDATE `users` SET `email` = 'admin@drms.com' WHERE `username` = 'admin';
UPDATE `users` SET `email` = 'coordinator1@drms.com' WHERE `username` = 'coordinator1';

-- ============================================================================
-- VERIFICATION: Run after migration
-- ============================================================================
-- SELECT id, username, email, role FROM users;
-- DESCRIBE users;
