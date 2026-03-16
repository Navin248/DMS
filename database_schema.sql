-- Disaster Relief Resource Management System Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS disaster_relief_system;
USE disaster_relief_system;

-- Table 1: Users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table 2: Disasters
CREATE TABLE IF NOT EXISTS disasters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    severity VARCHAR(50) NOT NULL,
    affected_population INT,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table 3: Resources
CREATE TABLE IF NOT EXISTS resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    warehouse_location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table 4: Requests
CREATE TABLE IF NOT EXISTS requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disaster_id INT NOT NULL,
    location VARCHAR(255) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    priority VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disaster_id) REFERENCES disasters(id) ON DELETE CASCADE
);

-- Table 5: Allocations
CREATE TABLE IF NOT EXISTS allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    resource_id INT NOT NULL,
    quantity_allocated INT NOT NULL,
    delivery_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
);

-- Insert Sample Admin User (username: admin, password: admin)
-- Hash generated with: password_hash('admin', PASSWORD_BCRYPT)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$0WvPHxHXvuSYQKLpNgEyBeQYGP3NsQGBnD.eFRO7GHqcIvjXwcV/S', 'admin'),
('user1', '$2y$10$0WvPHxHXvuSYQKLpNgEyBeQYGP3NsQGBnD.eFRO7GHqcIvjXwcV/S', 'user');

-- Sample Disasters
INSERT INTO disasters (type, location, latitude, longitude, severity, affected_population, status, date) VALUES 
('Flood', 'Karnataka', 15.3173, 75.7139, 'High', 50000, 'active', NOW()),
('Cyclone', 'Odisha', 20.2961, 85.8245, 'Critical', 100000, 'active', NOW()),
('Earthquake', 'Gujarat', 23.0225, 72.5714, 'Medium', 30000, 'resolved', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Sample Resources
INSERT INTO resources (resource_name, quantity, warehouse_location) VALUES 
('Food Packages', 500, 'Central Warehouse'),
('Water Bottles', 1500, 'Central Warehouse'),
('Medical Kits', 200, 'Medical Store'),
('Blankets', 1000, 'Storage A'),
('Tents', 300, 'Storage B');

-- Sample Requests
INSERT INTO requests (disaster_id, location, resource_type, quantity, priority, status) VALUES 
(1, 'Karnataka - Flood Zone', 'Food Packages', 100, 'High', 'pending'),
(1, 'Karnataka - Flood Zone', 'Water Bottles', 200, 'Critical', 'allocated'),
(2, 'Odisha - Cyclone Area', 'Medical Kits', 50, 'High', 'pending');
