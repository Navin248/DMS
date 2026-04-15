-- Disaster Relief Resource Management System Database Schema
-- Version: 1.0 (Production Ready - Phase 8D Complete)
-- Last Updated: April 2026

-- ============================================================================
-- CREATE DATABASE
-- ============================================================================
CREATE DATABASE IF NOT EXISTS `disaster_relief_system`;
USE `disaster_relief_system`;

-- ============================================================================
-- TABLE 1: USERS (Authentication & Roles)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'user',
    `location` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 2: DISASTERS (Disaster Events)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `disasters` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `type` VARCHAR(100) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `severity` VARCHAR(50) NOT NULL,
    `affected_population` INT,
    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
    `date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_status` (`status`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 3: RESOURCES (Available Resources & Inventory)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `resources` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `resource_name` VARCHAR(100) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `warehouse_location` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_resource` (`resource_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 4: REQUESTS (Resource Requests from Coordinators)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `requests` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `disaster_id` INT,
    `user_id` INT,
    `location` VARCHAR(255) NOT NULL,
    `resource_type` VARCHAR(100) NOT NULL,
    `quantity` INT NOT NULL,
    `priority` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `approval_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `approved_by` INT,
    `approval_date` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`disaster_id`) REFERENCES `disasters`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    KEY `idx_approval_status` (`approval_status`),
    KEY `idx_status` (`status`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_disaster_id` (`disaster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 5: ALLOCATIONS (Resource Allocations to Requests)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `allocations` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `request_id` INT NOT NULL,
    `resource_id` INT NOT NULL,
    `quantity_allocated` INT NOT NULL,
    `delivery_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `fulfilled_by` INT,
    `date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`fulfilled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    KEY `idx_delivery_status` (`delivery_status`),
    KEY `idx_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT SAMPLE DATA (Test Users & Initial Data)
-- ============================================================================

-- Insert Sample Users
-- Admin: admin / admin123 (password hashed with MD5)
-- Coordinator: coordinator1 / coord123
INSERT INTO `users` (`username`, `password`, `role`, `location`) VALUES 
('admin', '0192023a7bbd73250516f069df18b500', 'admin', 'Central Office'),
('coordinator1', '3b5d5c3712955042212316173ccf37be', 'user', 'Field Office');

-- Insert Sample Disasters
INSERT INTO `disasters` (`type`, `location`, `latitude`, `longitude`, `severity`, `affected_population`, `status`, `date`) VALUES 
('Flood', 'Karnataka', 15.3173, 75.7139, 'High', 50000, 'active', NOW()),
('Cyclone', 'Odisha', 20.2961, 85.8245, 'Critical', 100000, 'active', NOW()),
('Earthquake', 'Gujarat', 23.0225, 72.5714, 'Medium', 30000, 'resolved', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Insert Sample Resources
INSERT INTO `resources` (`resource_name`, `quantity`, `warehouse_location`) VALUES 
('Food Packages', 500, 'Central Warehouse'),
('Water Bottles', 1500, 'Central Warehouse'),
('Medical Kits', 200, 'Medical Store'),
('Blankets', 1000, 'Storage A'),
('First Aid Kits', 350, 'Medical Store'),
('Tents', 300, 'Storage B'),
('Sleeping Bags', 400, 'Storage A'),
('Medicines', 150, 'Medical Store');

-- ============================================================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================================================
-- (Already created above with tables)

-- ============================================================================
-- VERIFICATION QUERIES (Use these to verify setup)
-- ============================================================================
-- SHOW TABLES;
-- SELECT COUNT(*) FROM users;
-- SELECT COUNT(*) FROM disasters;
-- SELECT COUNT(*) FROM resources;
-- SELECT * FROM users;
-- DESCRIBE allocations;

-- ============================================================================
-- SETUP COMPLETE
-- ============================================================================
-- Database is ready for use. Default credentials:
-- Admin: admin / admin123
-- Coordinator: coordinator1 / coord123
--
-- To change passwords, use:
-- UPDATE users SET password=MD5('newpassword') WHERE username='admin';
-- ============================================================================

