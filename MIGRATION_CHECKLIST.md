# 📋 Pre-Migration Checklist

## Before Moving to New PC

### Code Backup ✓
- [ ] All project files backed up
- [ ] Git history is preserved (`.git/` folder included)
- [ ] No uncommitted changes
- [ ] Latest code committed to git

### Database Backup ✓
- [ ] Database exported (optional but recommended)
  ```bash
  mysqldump -u root -p disaster_relief_system > backup.sql
  ```
- [ ] Backup stored safely
- [ ] Current data noted (test data only)

### Configuration Review ✓
- [ ] `config/database.php` reviewed
- [ ] Default credentials noted (admin/admin123, coordinator1/coord123)
- [ ] No hardcoded passwords in code
- [ ] `.htaccess` files present

### File Count Verification ✓
Run this command to verify all files are present:
```bash
find . -type f | wc -l
```
Expected: ~150+ files (including git history)

### Project Integrity ✓
- [ ] No large binary files
- [ ] No development-only code left
- [ ] Unnecessary files cleaned up
- [ ] Only production-ready code remains

---

## After Moving to New PC

### 1. Environment Setup (5 min)
- [ ] XAMPP/Apache installed
- [ ] PHP 7.4+ installed
- [ ] MySQL installed and running
- [ ] Browser accessible to localhost

### 2. Project Placement (2 min)
- [ ] Project copied to `C:\xampp\htdocs\DMS\` (Windows)
- [ ] File structure verified
- [ ] All files intact
- [ ] Permissions set correctly

### 3. Database Setup (3 min)
- [ ] MySQL connection verified
- [ ] New database created: `disaster_relief_system`
- [ ] Schema imported from `database_schema.sql`
- [ ] Sample data seeded
- [ ] PHPMyAdmin accessible

### 4. Configuration (2 min)
- [ ] `config/database.php` credentials updated
- [ ] Database host/user/pass verified
- [ ] Connection tested successfully
- [ ] Character set UTF-8 confirmed

### 5. Access Verification (1 min)
- [ ] Login page loads: `http://localhost/DMS/login.php`
- [ ] Can login as admin (admin/admin123)
- [ ] Can login as coordinator (coordinator1/coord123)
- [ ] Dashboards load without errors

### 6. Workflow Testing (5 min)
- [ ] Create test request as coordinator
- [ ] Approve request as admin
- [ ] Allocate resource as admin
- [ ] Update allocation status
- [ ] Coordinator confirms receipt
- [ ] All status updates visible

---

## Quick Verification Commands

```bash
# Check if MySQL is running
mysql -u root -p -e "SELECT 1;"

# List all databases
mysql -u root -p -e "SHOW DATABASES;"

# Verify tables exist
mysql -u root -p disaster_relief_system -e "SHOW TABLES;"

# Count records in each table
mysql -u root -p disaster_relief_system << EOF
SELECT 'users' as table_name, COUNT(*) as records FROM users
UNION ALL
SELECT 'disasters', COUNT(*) FROM disasters
UNION ALL
SELECT 'resources', COUNT(*) FROM resources
UNION ALL
SELECT 'requests', COUNT(*) FROM requests
UNION ALL
SELECT 'allocations', COUNT(*) FROM allocations;
EOF

# Verify admin user exists
mysql -u root -p disaster_relief_system -e "SELECT username, role FROM users WHERE role='admin';"
```

---

## Default Credentials (Change After Setup)

| Role | Username | Password | 
|------|----------|----------|
| **Admin** | admin | admin123 |
| **Coordinator** | coordinator1 | coord123 |

⚠️ **IMPORTANT:** Change these passwords in production!

```bash
# Update admin password
mysql -u root -p disaster_relief_system

UPDATE users SET password=MD5('NewSecurePassword123') WHERE username='admin';
UPDATE users SET password=MD5('NewSecurePassword123') WHERE username='coordinator1';

exit;
```

---

## Files to Be Aware Of

### Production Files (Keep)
- ✅ `config/database.php` - Update credentials only
- ✅ `database_schema.sql` - Use for setup
- ✅ All PHP files in admin/, worker/, requests/, etc.
- ✅ `assets/` folder - CSS and JS
- ✅ `.git/` folder - Version history

### Setup Files (Reference)
- 📖 `SETUP_AND_MIGRATION.md` - Detailed setup guide
- 📖 `MIGRATION_CHECKLIST.md` - This file
- 📖 `README.md` - Project overview

### Removed (Already Cleaned)
- ❌ `test_connection.php` - Deleted
- ❌ `validate_phase_8d.php` - Deleted
- ❌ `DOCUMENTATION_INDEX.md` - Deleted
- ❌ `GIT_COMMIT_GUIDE.md` - Deleted
- ❌ `QUICK_TESTING_REFERENCE.md` - Deleted
- ❌ `PHASE_5_6_7_SUMMARY.md` - Deleted

---

## Common Issues & Quick Fixes

| Issue | Solution |
|-------|----------|
| **Connection Failed** | Check MySQL is running, verify credentials in `config/database.php` |
| **Table doesn't exist** | Re-import `database_schema.sql` |
| **Login fails** | Verify users in database: `SELECT * FROM users;` |
| **Can't find page** | Ensure project is at correct path, restart Apache |
| **Permission denied** | Check file permissions: `chmod -R 755 DMS/` (Linux) |

---

## Success Indicators ✓

After complete setup, you should see:

1. **Login Page Loads**
   - URL: `http://localhost/DMS/login.php`
   - Shows 2 role selector buttons
   - Branding and styling visible

2. **Admin Can Login**
   - Credentials: admin / admin123
   - Redirects to admin dashboard
   - Shows 6 statistic cards

3. **Coordinator Can Login**
   - Credentials: coordinator1 / coord123
   - Redirects to worker dashboard
   - Shows personal requests and allocations

4. **Database Connected**
   - No "Connection Failed" errors
   - Data persists across page refreshes
   - No MySQL errors in logs

5. **All Workflows Work**
   - Create request → Approve → Allocate → Confirm → Delivered
   - Status updates reflected in real-time
   - No orphaned records or data integrity issues

---

## Next Steps

1. ✅ Complete setup using `SETUP_AND_MIGRATION.md`
2. ✅ Verify all checklist items pass
3. ✅ Test complete workflow end-to-end
4. ✅ Create production user accounts
5. ✅ Set up database backups
6. ✅ Configure Apache for your domain (if applicable)
7. ✅ Deploy to production when ready

---

**Status:** ✅ Ready for Migration  
**Last Updated:** April 2026  
**Version:** 1.0
