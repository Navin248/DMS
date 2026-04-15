# 🚀 Disaster Relief System - Setup & Migration Guide

> **Comprehensive step-by-step setup guide for the Disaster Relief Resource Management System**  
> Use this guide to set up the project on any new PC without breaking any workflows.

---

## 📋 Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Migration Checklist](#pre-migration-checklist)
3. [Step-by-Step Setup](#step-by-step-setup)
4. [Database Setup](#database-setup)
5. [Configuration Files](#configuration-files)
6. [Project Structure](#project-structure)
7. [Verification Checklist](#verification-checklist)
8. [Troubleshooting](#troubleshooting)

---

## 💻 System Requirements

### Minimum Requirements:
- **Operating System:** Windows 7+, Linux, or macOS
- **Web Server:** Apache 2.4+
- **PHP Version:** 7.4 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **RAM:** 2GB minimum

### Recommended Stack:
- **XAMPP 7.4+** (includes Apache, PHP, MySQL in one package)
- **Windows 10/11** or latest Linux

---

## ✅ Pre-Migration Checklist

Before starting setup on the new PC:

- [ ] XAMPP or Apache+PHP+MySQL installed
- [ ] MySQL running and accessible
- [ ] PHP CLI available in system PATH
- [ ] Project files copied to new location
- [ ] All source files intact (check file count)
- [ ] Network connectivity for any external APIs (if used)

---

## 🔧 Step-by-Step Setup

### **STEP 1: Environment Setup (5 minutes)**

#### On Windows with XAMPP:

```bash
# 1. Start XAMPP
# - Open XAMPP Control Panel
# - Click "Start" for Apache
# - Click "Start" for MySQL

# 2. Verify Apache is running
# - Check XAMPP Control Panel shows Apache "Running"
# - Open browser: http://localhost/
# - You should see XAMPP dashboard

# 3. Verify MySQL is running
# - Check XAMPP Control Panel shows MySQL "Running"
# - Access PHPMyAdmin: http://localhost/phpmyadmin
```

#### On Linux/macOS:

```bash
# Start MySQL
sudo systemctl start mysql

# Start Apache
sudo systemctl start apache2

# Verify Apache is running
sudo systemctl status apache2

# Verify MySQL is running
sudo systemctl status mysql

# Create MySQL user if needed
mysql -u root -p
```

---

### **STEP 2: Project Placement (2 minutes)**

Copy the entire project to your web root:

```
Windows (XAMPP):
C:\xampp\htdocs\DMS\

Linux:
/var/www/html/DMS/

macOS:
/Library/WebServer/Documents/DMS/
```

**Verify file structure:**

```
DMS/
├── SETUP_AND_MIGRATION.md (this file)
├── database_schema.sql
├── config/
│   ├── database.php
│   ├── auth.php
│   └── constants.php
├── admin/
├── worker/
├── requests/
├── allocations/
├── disasters/
├── resources/
├── includes/
├── assets/
├── login.php
├── logout.php
├── index.php
└── .git/
```

---

### **STEP 3: Database Creation (3 minutes)**

#### Method 1: Using MySQL Command Line (Recommended)

```bash
# Open MySQL Command Line
mysql -u root -p

# At the MySQL prompt, execute:

CREATE DATABASE IF NOT EXISTS disaster_relief_system;
USE disaster_relief_system;

-- Create Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Disasters Table
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

-- Create Resources Table
CREATE TABLE IF NOT EXISTS resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    warehouse_location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Requests Table
CREATE TABLE IF NOT EXISTS requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disaster_id INT,
    user_id INT DEFAULT 1,
    location VARCHAR(255) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    priority VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    approval_status VARCHAR(50) DEFAULT 'pending',
    approved_by INT,
    approval_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disaster_id) REFERENCES disasters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create Allocations Table
CREATE TABLE IF NOT EXISTS allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    resource_id INT NOT NULL,
    quantity_allocated INT NOT NULL,
    delivery_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    fulfilled_by INT,
    date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (fulfilled_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert Sample Users
INSERT INTO users (username, password, role) VALUES 
('admin', MD5('admin123'), 'admin'),
('coordinator1', MD5('coord123'), 'user');

-- Insert Sample Disasters
INSERT INTO disasters (type, location, latitude, longitude, severity, affected_population, status, date) VALUES 
('Flood', 'Karnataka', 15.3173, 75.7139, 'High', 50000, 'active', NOW()),
('Cyclone', 'Odisha', 20.2961, 85.8245, 'Critical', 100000, 'active', NOW()),
('Earthquake', 'Gujarat', 23.0225, 72.5714, 'Medium', 30000, 'resolved', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Insert Sample Resources
INSERT INTO resources (resource_name, quantity, warehouse_location) VALUES 
('Food Packages', 500, 'Central Warehouse'),
('Water Bottles', 1500, 'Central Warehouse'),
('Medical Kits', 200, 'Medical Store'),
('Blankets', 1000, 'Storage A'),
('First Aid Kits', 350, 'Medical Store'),
('Tents', 300, 'Storage B');

exit;
```

#### Method 2: Using PHPMyAdmin

1. Open `http://localhost/phpmyadmin`
2. Click "New" to create new database
3. Database name: `disaster_relief_system`
4. Collation: `utf8_general_ci`
5. Click "Create"
6. Click on database name → Import tab
7. Choose `database_schema.sql` from project root
8. Click "Go" to import

#### Method 3: Import SQL File Directly

```bash
# Windows
mysql -u root -p < C:\xampp\htdocs\DMS\database_schema.sql

# Linux/macOS
mysql -u root -p < /var/www/html/DMS/database_schema.sql
```

---

### **STEP 4: Configuration (2 minutes)**

#### Edit `config/database.php`

Located at: `DMS/config/database.php`

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');    // Usually 'localhost'
define('DB_USER', 'root');         // Your MySQL username
define('DB_PASS', '');             // Your MySQL password (empty for default XAMPP)
define('DB_NAME', 'disaster_relief_system');

// Create Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check Connection
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");
?>
```

**Common Configurations:**

- **XAMPP (Windows):** Keep defaults (root/blank password)
- **Production Server:** Use strong password, change username
- **Development Machine:** Can use root temporarily

#### Edit `config/auth.php` (No changes usually needed)

Located at: `DMS/config/auth.php`

**Verify it contains:**
```php
function check_login() { ... }
function check_role($required_role) { ... }
function get_user_info() { ... }
```

---

### **STEP 5: File Permissions (1 minute)**

#### Windows (XAMPP):
- No special permissions needed
- Ensure Apache user can read/write files

#### Linux/macOS:

```bash
# Set correct ownership
sudo chown -R www-data:www-data /var/www/html/DMS/

# Set correct permissions
sudo chmod -R 755 /var/www/html/DMS/
sudo chmod -R 644 /var/www/html/DMS/*.php
sudo chmod -R 755 /var/www/html/DMS/config/
```

---

### **STEP 6: Access the Application (1 minute)**

Open your browser and navigate to:

```
http://localhost/DMS/login.php
```

**Expected output:** Login page with two role selector buttons

---

## 📊 Database Setup - Detailed Schema

### Table 1: Users
```sql
- id: Auto-incrementing primary key
- username: Unique username for login
- password: MD5 hashed password
- role: 'admin' or 'user' (coordinator)
- created_at: Auto timestamp
- updated_at: Auto timestamp
```

### Table 2: Disasters
```sql
- id: Auto-incrementing primary key
- type: Disaster type (Flood, Earthquake, etc.)
- location: Geographic location
- latitude/longitude: Coordinates
- severity: High, Medium, Low, Critical
- affected_population: Number of people affected
- status: active, resolved
- date: DateTime of disaster
```

### Table 3: Resources
```sql
- id: Auto-incrementing primary key
- resource_name: Name (Food Packages, Medical Kits, etc.)
- quantity: Available quantity
- warehouse_location: Storage location
- created_at/updated_at: Timestamps
```

### Table 4: Requests
```sql
- id: Auto-incrementing primary key
- disaster_id: Link to disasters (can be NULL for new incidents)
- user_id: ID of coordinator who created request
- location: Required location
- resource_type: Type of resource needed
- quantity: Amount requested
- priority: Critical, High, Medium, Low
- status: pending, allocated, in_transit, delivered
- approval_status: pending, approved, rejected
- approved_by: Admin user ID who approved
- approval_date: When approved
```

### Table 5: Allocations
```sql
- id: Auto-incrementing primary key
- request_id: Link to request
- resource_id: Link to resource
- quantity_allocated: Amount allocated
- delivery_status: pending, in_transit, delivered
- fulfilled_by: Admin user ID who allocated
- date: Delivery date
```

---

## 🔐 Configuration Files

### File Locations and Purpose

| File | Purpose | Edit? |
|------|---------|-------|
| `config/database.php` | Database connection params | **YES** |
| `config/auth.php` | Authentication functions | NO |
| `.htaccess` | Apache rewrite rules | NO |
| `assets/css/style.css` | Global styling | NO |

### Configuration Checklist

```markdown
✓ DB_HOST is correct
✓ DB_USER matches your MySQL user
✓ DB_PASS matches your MySQL password
✓ DB_NAME is 'disaster_relief_system'
✓ Database exists and is accessible
✓ All tables are created
✓ Sample data is seeded
✓ Apache can access the files
```

---

## 📁 Project Structure

```
DMS/
│
├── 📄 SETUP_AND_MIGRATION.md ← You are here
├── 📄 database_schema.sql
├── 📄 login.php (Entry point)
├── 📄 index.php (Redirect)
├── 📄 logout.php
├── 📄 profile.php
├── 📄 reset_password.php
│
├── 📁 config/
│   ├── database.php (⚠️ Update with your DB credentials)
│   ├── auth.php
│   └── constants.php
│
├── 📁 admin/
│   ├── dashboard.php (Admin main page)
│   └── ...
│
├── 📁 worker/
│   ├── dashboard.php (Coordinator main page)
│   ├── my_requests.php
│   ├── view_request_detail.php
│   └── ...
│
├── 📁 requests/
│   ├── view_requests.php (Admin reviews all requests)
│   ├── create_request.php
│   ├── edit_request.php
│   └── ...
│
├── 📁 allocations/
│   ├── allocate_resource.php (Admin creates allocations)
│   ├── view_allocations.php
│   ├── edit_allocation.php
│   └── ...
│
├── 📁 disasters/
│   └── (Disaster management pages)
│
├── 📁 resources/
│   └── (Resource management pages)
│
├── 📁 includes/
│   ├── sidebar.php (Navigation menu)
│   ├── header.php (Page header)
│   └── footer.php
│
├── 📁 assets/
│   ├── css/style.css (Main stylesheet)
│   ├── css/bootstrap.css
│   └── js/
│
├── 📁 .git/
│   └── (Version control history)
│
└── 📁 .planning/ (Development documentation - not needed for production)
```

---

## ✔️ Verification Checklist

After completing setup, verify each step:

### 1. Database Connection ✓
```bash
# In browser, access any page
# If you see errors with "Connection Failed", database not configured correctly
# If you can log in, connection is working
```

### 2. Login Functionality ✓
```
URL: http://localhost/DMS/login.php
Username: admin
Password: admin123
→ Should redirect to /admin/dashboard.php

OR

Username: coordinator1
Password: coord123
→ Should redirect to /worker/dashboard.php
```

### 3. Admin Dashboard ✓
```
URL: http://localhost/DMS/admin/dashboard.php
Should show:
- 6 statistic cards (clickable)
- Pending approvals table
- Recent deliveries table
- Navigation sidebar
```

### 4. Coordinator Dashboard ✓
```
URL: http://localhost/DMS/worker/dashboard.php
Should show:
- 6 statistic cards (clickable)
- My recent requests table
- My allocations table
```

### 5. Core Workflows ✓

**Complete this flow to verify everything:**

```
1. LOGIN as coordinator1/coord123
2. CREATE NEW REQUEST
   - Disaster: Select one
   - Location: Enter location
   - Resource: Select resource
   - Quantity: Enter quantity
   - Priority: Select priority
   - SUBMIT
3. See request in "My Requests" (status: pending)

4. LOGOUT and LOGIN as admin/admin123
5. VIEW REQUESTS
   - Open Requests page
   - Find coordinator's request
   - Click APPROVE button

6. VIEW ALLOCATIONS
   - Click "Approved" card on dashboard
   - Should see coordinator's approved request
   - Click "New Allocation"
   - Select the request
   - Select resource
   - Enter quantity
   - SUBMIT

7. LOGOUT and LOGIN as coordinator1/coord123
8. VIEW REQUEST DETAILS
   - Go to "My Requests"
   - Click "View Details"
   - Should see allocation with status "pending"

9. LOGOUT and LOGIN as admin/admin123
10. UPDATE ALLOCATION STATUS
    - Go to Allocations
    - Click allocation row
    - Change status: pending → in_transit → delivered
    - SAVE

11. LOGOUT and LOGIN as coordinator1/coord123
12. CHECK DELIVERY STATUS
    - Go to request detail
    - Should see "Confirm Received" button
    - Click it
    - Status should change to "delivered"
```

---

## 🐛 Troubleshooting

### Error: "Connection Failed"

**Symptom:** Page shows "Connection Failed: ..."

**Solution:**
1. Check if MySQL is running
2. Verify credentials in `config/database.php`
3. Ensure database exists: `SHOW DATABASES;`
4. Restart MySQL service

```bash
# Windows XAMPP
# Stop and Start MySQL in XAMPP Control Panel

# Linux
sudo systemctl restart mysql

# macOS
sudo /usr/local/bin/mysql.server restart
```

---

### Error: "Unknown column" or "table doesn't exist"

**Symptom:** "Unknown column 'x' in 'field list'"

**Solution:**
1. Verify database schema was imported
2. Check table exists: `SHOW TABLES;`
3. Re-import database_schema.sql
4. Clear browser cache and refresh

```bash
# Verify tables
mysql -u root -p disaster_relief_system -e "SHOW TABLES;"

# Should show:
# allocations
# disasters
# requests
# resources
# users
```

---

### Error: "Access Denied" or "User doesn't exist"

**Symptom:** Can't login even with correct credentials

**Solution:**
1. Verify users exist in database
2. Reset password with correct username

```bash
# Check users
mysql -u root -p disaster_relief_system -e "SELECT username, role FROM users;"

# Reset admin password
mysql -u root -p disaster_relief_system -e "UPDATE users SET password=MD5('admin123') WHERE username='admin';"

# Reset coordinator password
mysql -u root -p disaster_relief_system -e "UPDATE users SET password=MD5('coord123') WHERE username='coordinator1';"
```

---

### Error: "Page not found" or "404"

**Symptom:** Clicking links shows 404 error

**Solution:**
1. Verify project is in correct path: `C:\xampp\htdocs\DMS\` (Windows)
2. Check file names are correct (case-sensitive on Linux)
3. Verify `.htaccess` exists in project root
4. Restart Apache

```bash
# Windows XAMPP
# Stop and Start Apache

# Linux
sudo systemctl restart apache2
```

---

### Error: "Columns not synced" or queries failing

**Symptom:** Queries return "Unknown column" errors sporadically

**Solution:**
1. Verify `database_schema.sql` was fully imported
2. Check if database has been modified elsewhere
3. Compare your schema with the provided SQL file
4. Backup data and re-import clean schema

```bash
# Export current data (if you have important data)
mysqldump -u root -p disaster_relief_system > backup.sql

# Drop and recreate database
mysql -u root -p

DROP DATABASE disaster_relief_system;
CREATE DATABASE disaster_relief_system;

# Re-import schema
mysql -u root -p < database_schema.sql
```

---

### Error: "Session errors" or "Can't login"

**Symptom:** Login page loads but can't authenticate

**Solution:**
1. Verify PHP sessions are enabled
2. Check `config/auth.php` is in place
3. Clear browser cookies and cache
4. Try in incognito/private mode

```bash
# Verify PHP is working
# Create test.php with: <?php phpinfo(); ?>
# Access: http://localhost/DMS/test.php
# Delete after verification
```

---

### Warning: "Modified/Added files"

**Symptom:** Git shows many modified files

**Solution:**
1. This is normal - configuration changes are expected
2. Don't commit local database credentials
3. Update `.gitignore` to exclude config files (optional)

```bash
# Check what changed
git status

# To exclude config from git tracking:
git update-index --assume-unchanged config/database.php
```

---

## 📝 Post-Setup Tasks

### 1. Update Admin Password
```bash
mysql -u root -p disaster_relief_system

UPDATE users SET password=MD5('YourNewPassword123') WHERE username='admin';
```

### 2. Create Additional Users
```sql
INSERT INTO users (username, password, role) VALUES 
('coordinator2', MD5('password123'), 'user'),
('coordinator3', MD5('password123'), 'user');
```

### 3. Backup Database Regularly
```bash
# Daily automated backup
mysqldump -u root -p disaster_relief_system > backup_$(date +%Y%m%d).sql
```

### 4. Monitor Performance
- Check Apache/MySQL logs for errors
- Monitor disk space usage
- Verify backups work properly

---

## 🎯 Quick Reference

### Commands Cheat Sheet

```bash
# Start MySQL
mysql -u root -p

# Access specific database
USE disaster_relief_system;

# Show all tables
SHOW TABLES;

# Show table structure
DESCRIBE users;

# Count records in table
SELECT COUNT(*) FROM requests;

# Check user credentials
SELECT username, role FROM users;

# Export database
mysqldump -u root -p disaster_relief_system > backup.sql

# Import database
mysql -u root -p < backup.sql
```

### URLs Reference

| Page | URL |
|------|-----|
| **Login** | `http://localhost/DMS/login.php` |
| **Admin Dashboard** | `http://localhost/DMS/admin/dashboard.php` |
| **Worker Dashboard** | `http://localhost/DMS/worker/dashboard.php` |
| **View Requests** | `http://localhost/DMS/requests/view_requests.php` |
| **Create Request** | `http://localhost/DMS/requests/create_request.php` |
| **Allocations** | `http://localhost/DMS/allocations/view_allocations.php` |
| **PHPMyAdmin** | `http://localhost/phpmyadmin` (DB Management) |

---

## 📱 Test Credentials

After setup, use these credentials to test:

| Role | Username | Password | Access |
|------|----------|----------|--------|
| **Admin** | admin | admin123 | Full system control |
| **Coordinator** | coordinator1 | coord123 | Request management |

> **Note:** Change these credentials in production!

---

## ✨ Setup Complete!

If you've completed all steps and verified the checklist, your system is ready to use.

### Next Steps:
1. Create test data by following the Core Workflows section
2. Explore all admin and coordinator features
3. Test the complete request → approval → allocation → delivery workflow
4. Back up the database
5. Deploy to production when ready

---

## 📞 Support

If you encounter issues:
1. Check the Troubleshooting section
2. Review error messages carefully
3. Check Apache/MySQL logs
4. Verify all configuration files
5. Ensure database credentials are correct

---

**Last Updated:** April 2026  
**Version:** 1.0 (Production Ready)  
**Status:** ✅ All Workflows Tested & Verified
