# 🛡️ Disaster Relief Management System (DRMS)

A comprehensive web-based system for managing disaster relief operations including disaster tracking, resource management, request handling, and allocation coordination.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-10.4%2B-orange?logo=mariadb)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Prerequisites](#-prerequisites)
- [Setup Guide (Step by Step)](#-setup-guide-step-by-step)
  - [Step 1: Install XAMPP](#step-1-install-xampp)
  - [Step 2: Clone the Repository](#step-2-clone-the-repository)
  - [Step 3: Create the Database](#step-3-create-the-database)
  - [Step 4: Configure Database Connection (Optional)](#step-4-configure-database-connection-optional)
  - [Step 5: Start the Server](#step-5-start-the-server)
  - [Step 6: Access the Application](#step-6-access-the-application)
- [Default Login Credentials](#-default-login-credentials)
- [Project Structure](#-project-structure)
- [Troubleshooting](#-troubleshooting)

---

## ✨ Features

| Module | Description |
|--------|-------------|
| **🏠 Public Landing Page** | Disaster awareness, safety tips, live map, emergency contacts |
| **📊 Admin Dashboard** | Real-time statistics, activity timeline, CSV export |
| **🌪️ Disaster Management** | Add, edit, view disasters with map coordinates |
| **📦 Resource Management** | Track inventory across warehouses |
| **📝 Request Management** | Create, approve, and track resource requests |
| **🚛 Allocation Management** | Allocate resources to requests with delivery tracking |
| **👤 User Management** | Admin panel to manage users and roles |
| **👷 Worker Portal** | Dedicated dashboard for field workers |
| **🔐 Authentication** | Secure login with bcrypt, session timeout |
| **🔑 Password Reset** | Token-based forgot password flow |
| **📱 Responsive Design** | Mobile-friendly Bootstrap 5 UI |

---

## 🛠️ Tech Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 8.0+ |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ |
| **Frontend** | HTML5, CSS3, JavaScript |
| **CSS Framework** | Bootstrap 5.3 |
| **Icons** | Font Awesome 6.4 |
| **Maps** | Leaflet.js + OpenStreetMap |
| **Slider** | Swiper.js |
| **Animations** | AOS (Animate On Scroll) |
| **Server** | Apache (via XAMPP) |

---

## 📌 Prerequisites

Before setting up this project, make sure you have:

1. **XAMPP** (v8.2 or higher recommended) — [Download XAMPP](https://www.apachefriends.org/download.html)
   - Includes: Apache, MySQL/MariaDB, PHP
2. **Git** — [Download Git](https://git-scm.com/downloads)
3. **Web Browser** — Chrome, Firefox, or Edge (latest version)

> **Note:** No additional PHP packages or Composer dependencies are required. The project uses CDN-hosted libraries.

---

## 🚀 Setup Guide (Step by Step)

### Step 1: Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Run the installer and install to `C:\xampp` (default)
3. During installation, ensure these components are selected:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP

---

### Step 2: Clone the Repository

Open a terminal/command prompt and run:

```bash
cd C:\xampp\htdocs
git clone https://github.com/Navin248/DMS.git
```

This will create the project at `C:\xampp\htdocs\DMS\`

---

### Step 3: Create the Database

1. **Open XAMPP Control Panel** and start:
   - ✅ **Apache** — Click `Start`
   - ✅ **MySQL** — Click `Start`

2. **Open phpMyAdmin** in your browser:
   ```
   http://localhost/phpmyadmin
   ```

3. **Run the main database schema:**
   - Click the **SQL** tab at the top
   - Open the file `database_schema.sql` from the project folder
   - Copy the **entire contents** and paste into the SQL query box
   - Click **Go** to execute

   **Or use the command line:**
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\DMS\database_schema.sql
   ```

   This will:
   - ✅ Create the `disaster_relief_system` database
   - ✅ Create all necessary tables (`users`, `disasters`, `resources`, `requests`, `allocations`, `password_resets`)
   - ✅ Insert sample data (users, disasters, resources)

---

### Step 4: Configure Database Connection (Optional)

By default, the system connects to MySQL on `localhost` port `3306`. If your XAMPP MySQL is running on a different port (e.g., `3307`):

1. Open `C:\xampp\htdocs\DMS\config\database.php` in a text editor.
2. Change the `$port` variable to match your XAMPP MySQL port:
   ```php
   $host = '127.0.0.1';
   $port = 3307; // Update this to your MySQL port
   ```

---

### Step 5: Start the Server

1. Open **XAMPP Control Panel**
2. Ensure both services show green:
   - ✅ **Apache** — Running
   - ✅ **MySQL** — Running

> If Apache won't start, port 80 may be in use. Check the [Troubleshooting](#-troubleshooting) section.

---

### Step 6: Access the Application

Open your browser and go to:

| Page | URL |
|------|-----|
| **Home (Public)** | [http://localhost/DMS/](http://localhost/DMS/) |
| **Login** | [http://localhost/DMS/login.php](http://localhost/DMS/login.php) |
| **Admin Dashboard** | [http://localhost/DMS/dashboard.php](http://localhost/DMS/dashboard.php) |
| **phpMyAdmin** | [http://localhost/phpmyadmin](http://localhost/phpmyadmin) |

---

## 🔐 Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| **Admin** | `admin` | `admin123` |
| **Coordinator/Worker** | `coordinator1` | `coord123` |

> ⚠️ **Important:** Change these passwords after first login via the **Profile** page or **Admin > Manage Users**.

---

## 📁 Project Structure

```
DMS/
├── admin/                    # Admin panel pages
│   ├── dashboard.php         # Admin-specific dashboard
│   └── manage_users.php      # User management (CRUD)
│
├── allocations/              # Resource allocation module
│   ├── allocate_resource.php  # Create new allocation
│   ├── edit_allocation.php    # Edit existing allocation
│   └── view_allocations.php   # List all allocations
│
├── assets/                   # Static assets
│   ├── css/style.css         # Custom styles
│   ├── images/               # Image assets
│   └── js/                   # JavaScript files
│
├── config/                   # Configuration files
│   ├── database.php          # Database connection
│   └── auth.php              # Authentication middleware
│
├── disasters/                # Disaster management module
│   ├── add_disaster.php      # Add new disaster
│   ├── edit_disaster.php     # Edit disaster details
│   └── view_disasters.php    # List all disasters
│
├── includes/                 # Shared components
│   ├── header.php            # Page header
│   ├── sidebar.php           # Navigation sidebar
│   └── footer.php            # Page footer
│
├── migrations/               # Database migration scripts
│   ├── Phase_8A_migration.sql     # Request approval columns
│   └── add_password_resets.sql    # Password reset table + bcrypt migration
│
├── requests/                 # Request management module
│   ├── create_request.php    # Create resource request
│   ├── edit_request.php      # Edit request
│   └── view_requests.php     # List all requests
│
├── resources/                # Resource management module
│   ├── add_resource.php      # Add new resource
│   └── view_resources.php    # List all resources
│
├── worker/                   # Worker portal
│   ├── dashboard.php         # Worker dashboard
│   ├── my_requests.php       # Worker's own requests
│   └── view_request_detail.php # Request detail view
│
├── index.php                 # Public landing page
├── login.php                 # Login page
├── logout.php                # Logout handler
├── profile.php               # User profile management
├── dashboard.php             # Main admin dashboard
├── forgot_password.php       # Forgot password form
├── reset_password.php        # Password reset redirect
├── reset_password_token.php  # Token-based password reset
├── migrate_passwords.php     # MD5 to bcrypt migration utility
├── database_schema.sql       # Main database schema
└── README.md                 # This file
```

---

## 👥 User Roles

| Role | Access |
|------|--------|
| **Admin** | Full access — manage disasters, resources, requests, allocations, users |
| **User/Coordinator** | Create requests, view disasters & resources, manage own profile |
| **Worker** | Worker portal, view assigned requests, update delivery status |

---

## 🔧 Database Configuration

The database connection is configured in `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', 3307);             // MySQL port (default XAMPP: 3306)
define('DB_USER', 'root');
define('DB_PASS', '');               // Default XAMPP has no password
define('DB_NAME', 'disaster_relief_system');
```

If your MySQL uses the default port 3306, change `DB_PORT` to `3306`.

---

## 🗄️ Database Tables

| Table | Description |
|-------|-------------|
| `users` | User accounts with roles and bcrypt passwords |
| `disasters` | Disaster events with location, severity, coordinates |
| `resources` | Available relief resources and inventory |
| `requests` | Resource requests linked to disasters |
| `allocations` | Resource allocations to fulfill requests |
| `password_resets` | Token-based password reset records |

### Entity Relationship

```
users ──┬──> requests ──> allocations
        │        │              │
        │        ▼              ▼
        │   disasters      resources
        │
        └──> password_resets
```

---

## ❓ Troubleshooting

### MySQL won't start
- Check if another MySQL instance is running on port 3307
- Open XAMPP Config > `my.ini` and verify the port is set to 3307
- Restart XAMPP

### Apache won't start (Port 80 in use)
- Skype, IIS, or another web server may be using port 80
- Open XAMPP Config > `httpd.conf` and change `Listen 80` to `Listen 8080`
- Access the app at `http://localhost:8080/DMS/`

### "MySQL server has gone away" error
- MySQL is not running → Start MySQL from XAMPP Control Panel

### "Connection refused" error
- Both Apache and MySQL must be running
- Check XAMPP Control Panel — both should show green

### Blank page or PHP errors
- Enable error display in `C:\xampp\php\php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```
- Restart Apache after changes

### Database tables not found
- Ensure you ran `database_schema.sql` first
- Then run `migrations/add_password_resets.sql`
- Verify in phpMyAdmin that 6 tables exist

### Can't login with default credentials
- Run `migrations/add_password_resets.sql` to update password hashes
- Or manually reset in phpMyAdmin:
  ```sql
  UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
  ```

---

## 📄 License

This project is licensed under the MIT License.

---

## 👨‍💻 Author

**Navin** — [GitHub](https://github.com/Navin248)

---

> Built with ❤️ for disaster relief coordination
