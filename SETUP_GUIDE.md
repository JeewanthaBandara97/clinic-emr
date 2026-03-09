# EMR System - Setup & Deployment Guide

## 📋 Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Running Migrations](#running-migrations)
6. [Initial Data Setup](#initial-data-setup)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements
- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **PHP**: 8.0 or higher with extensions:
  - PDO (PHP Data Objects)
  - PDO_MySQL
  - OpenSSL
  - GD (for image handling)
  - Curl
  - JSON
  - Sessions
- **Database**: MySQL 5.7+ or MariaDB 10.5+
- **Browser**: Chrome, Firefox, Safari, Edge (latest versions)
- **RAM**: Minimum 2GB
- **Disk Space**: Minimum 500MB

### Recommended Requirements
- Apache 2.4.51+
- PHP 8.1 or higher
- MySQL 8.0+ or MariaDB 10.6+
- 4GB+ RAM
- SSD with 1GB+ space
- SSL Certificate (for HTTPS)

### Check PHP Extensions
```bash
php -m | grep -E 'pdo|mysqli|openssl|gd|curl|json'
```

---

## Installation Steps

### Step 1: Download/Clone Project
```bash
# Using Git
git clone https://github.com/JeewanthaBandara97/clinic-emr.git clinic-emr
cd clinic-emr

# OR manually extract ZIP to:
# C:\xampp\htdocs\MY\EMR\clinic-emr  (Windows)
# Or your web root directory
```

### Step 2: Set Correct Permissions
```bash
# Linux/Mac
chmod -R 755 clinic-emr
chmod -R 755 clinic-emr/logs
chmod -R 755 clinic-emr/uploads (if exists)

# Windows
# Right-click → Properties → Security → Edit permissions
# Ensure IIS_IUSRS or NETWORK SERVICE has Read/Write access
```

### Step 3: Verify File Structure
```
clinic-emr/
├── admin/                    # Admin pages
├── assistant/                # Assistant pages
├── doctor/                   # Doctor pages
├── auth/                     # Authentication
├── classes/                  # PHP classes
├── config/                   # Configuration
├── database/                 # Database files
│   ├── clinic_emr.sql       # Initial schema
│   └── migrations/          # Database migrations
├── includes/                 # Shared includes
├── assets/                   # CSS, JS, images
├── logs/                     # Log files
├── patient-api/             # Patient API endpoints
├── FEATURES_DOCUMENTATION.md # New features guide
├── SETUP_GUIDE.md           # This file
└── run-migrations.php       # Migration runner
```

---

## Database Setup

### Step 1: Create Database
```bash
# Using MySQL command line
mysql -u root -p

# Create database
CREATE DATABASE clinic_emr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Or using phpMyAdmin
# 1. Open phpMyAdmin (http://localhost/phpmyadmin)
# 2. Click "New"
# 3. Enter database name: clinic_emr
# 4. Select Collation: utf8mb4_unicode_ci
# 5. Click Create
```

### Step 2: Import Initial Schema
```bash
# Using MySQL command line
mysql -u root -p clinic_emr < database/clinic_emr.sql

# Using phpMyAdmin
# 1. Select database: clinic_emr
# 2. Click "Import"
# 3. Choose file: database/clinic_emr.sql
# 4. Click Import
```

### Step 3: Verify Installation
```bash
# Check all tables created
mysql -u root -p clinic_emr -e "SHOW TABLES;"

# Should show 25+ tables including:
# - users
# - patients
# - patient_visits
# - patient_prescriptions
# - etc.
```

---

## Configuration

### Step 1: Database Configuration
Edit `/config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_emr');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your password
define('DB_CHARSET', 'utf8mb4');
```

### Step 2: Application Configuration
Edit `/config/config.php`:
```php
// Application URL - VERY IMPORTANT!
define('APP_URL', 'http://localhost/MY/EMR/clinic-emr');

// Update clinic information
define('CLINIC_NAME', 'Your Clinic Name');
define('CLINIC_ADDRESS', 'Clinic Address');
define('CLINIC_PHONE', '+94 XX XXX XXXX');
define('CLINIC_EMAIL', 'clinic@email.com');

// Set timezone
date_default_timezone_set('Asia/Colombo');  // Or your timezone

// Session timeout (seconds)
define('SESSION_TIMEOUT', 3600);  // 1 hour

// For production, set HTTPS
define('SESSION_COOKIE_SECURE', 1);  // Only if using HTTPS
```

### Step 3: Create Initial Users
Edit `/database/cli-setup.php` to create initial users (admin, doctor, etc.)

Or use the SQL below:
```sql
-- Create admin user (password: admin123)
INSERT INTO users (username, password_hash, email, full_name, role_id, phone, is_active)
VALUES ('admin', '$2y$10$...', 'admin@clinic.com', 'Administrator', 1, '+94XXXXXXXXXX', 1);

-- Create sample doctor
INSERT INTO users (username, password_hash, email, full_name, role_id, phone, is_active)
VALUES ('doctor1', '$2y$10$...', 'doctor@clinic.com', 'Dr. John Smith', 2, '+94XXXXXXXXXX', 1);
```

To generate password hash:
```php
echo password_hash('admin123', PASSWORD_BCRYPT);
```

---

## Running Migrations

### Step 1: Run Migration Script
```bash
# From command line
php run-migrations.php

# Or from web browser
http://localhost/MY/EMR/clinic-emr/run-migrations.php
```

This will:
- Create new tables for billing, inventory, appointments
- Add new columns to existing tables
- Create indexes for performance
- Run schema_migrations tracking

### Step 2: Verify New Tables
```bash
mysql -u root -p clinic_emr -e "SHOW TABLES LIKE '%invoice%';"
# Should show: invoices, invoice_items, payments

mysql -u root -p clinic_emr -e "SHOW TABLES LIKE '%stock%';"
# Should show: medicine_stock, stock_transactions, medicine_expiry_batches

mysql -u root -p clinic_emr -e "SHOW TABLES LIKE '%appointment%';"
# Should show: appointments, appointment_slots
```

---

## Initial Data Setup

### Step 1: Create Clinic Roles
```sql
INSERT INTO roles (role_name, role_description) VALUES
('Administrator', 'Full system access'),
('Doctor', 'Doctor dashboard access'),
('Assistant', 'Assistant/Receptionist access'),
('Patient', 'Patient portal access');
```

### Step 2: Create Admin Account
```sql
INSERT INTO users (username, password_hash, email, full_name, role_id, phone, is_active)
VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMye', 'admin@clinic.com', 'System Admin', 1, '+94700000000', 1);
```

### Step 3: Create Doctor Profiles
```sql
INSERT INTO doctor_details (user_id, specialization, qualification, experience_years, consultation_fee)
VALUES 
(2, 'General Practice', 'MBBS', 5, 1500.00),
(3, 'Cardiology', 'MBBS, MD Cardiology', 10, 2500.00),
(4, 'Pediatrics', 'MBBS, DCH', 8, 2000.00);
```

### Step 4: Initialize Medicine Stock
```php
// Run once to initialize all medicines with zero stock
$db = Database::getInstance();
$medicines = $db->fetchAll("SELECT medicine_id FROM medicines WHERE is_active = 1");

$inventory = new Inventory();
foreach ($medicines as $med) {
    $inventory->initializeStock($med['medicine_id'], 0, 10);
}
echo "Stock initialized for " . count($medicines) . " medicines";
```

### Step 5: Generate Sample Appointment Slots
```php
$appointment = new Appointment();

// Generate slots for each doctor for next 30 days
$startDate = date('Y-m-d', strtotime('tomorrow'));
$endDate = date('Y-m-d', strtotime('+30 days'));

foreach ($doctors as $doctor) {
    $appointment->generateSlots($doctor['user_id'], $startDate, $endDate);
}
echo "Slots generated for all doctors";
```

---

## Testing

### Step 1: Test Database Connection
```bash
# Test from PHP
php -r "
require 'config/database.php';
\$db = new Database();
echo 'Database connection: OK';
"
```

### Step 2: Test Project Access
1. Open browser: `http://localhost/MY/EMR/clinic-emr/`
2. You should see the login page
3. Login with admin credentials

### Step 3: Test All Modules
- [ ] Admin can access admin panel
- [ ] Doctor can access doctor panel
- [ ] Can view/create patients
- [ ] Can create visits
- [ ] Can generate invoices
- [ ] Can manage inventory
- [ ] Can view reports
- [ ] Can manage appointments
- [ ] Patient can book appointments

### Step 4: Verify New Features
```bash
# Check Invoice page
http://localhost/MY/EMR/clinic-emr/admin/invoices.php

# Check Inventory page
http://localhost/MY/EMR/clinic-emr/admin/inventory.php

# Check Reports page
http://localhost/MY/EMR/clinic-emr/admin/reports.php

# Check Doctor Appointments
http://localhost/MY/EMR/clinic-emr/doctor/appointments.php

# Check Patient Appointment Booking (public)
http://localhost/MY/EMR/clinic-emr/patient-appointments.php
```

---

## Deployment

### For Production Server

#### Step 1: Environment Configuration
```bash
# Disable error display in production
php.ini:
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php-errors.log

# In config.php:
ini_set('display_errors', 0);  // Change from 1 to 0
```

#### Step 2: Enable HTTPS
```php
// config/config.php
define('APP_URL', 'https://yourdomain.com/clinic-emr');
define('SESSION_COOKIE_SECURE', 1);
ini_set('session.cookie_secure', 1);
```

#### Step 3: Set Proper Permissions
```bash
# Linux/Unix
chmod 755 clinic-emr
chmod 755 clinic-emr/logs
chmod 755 clinic-emr/uploads
chmod 644 clinic-emr/config/*.php
chmod 644 clinic-emr/includes/*.php

# Ensure proper ownership
chown -R www-data:www-data clinic-emr
```

#### Step 4: Database Backups
```bash
# Create backup directory
mkdir -p backups

# Schedule daily backups
# Add to crontab:
0 2 * * * mysqldump -u clinic_user -p clinic_emr > /path/to/backups/clinic_emr_$(date +\%Y\%m\%d).sql
```

#### Step 5: Enable Apache Modules
```bash
# Required modules
a2enmod rewrite
a2enmod ssl

# Restart Apache
systemctl restart apache2
```

#### Step 6: .htaccess Configuration
Ensure `.htaccess` exists in root with:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /clinic-emr/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
</IfModule>
```

---

## Troubleshooting

### Issue: Database Connection Error
**Solution:**
1. Verify credentials in `/config/database.php`
2. Check MySQL service is running
3. Test with: `mysql -h localhost -u root -p clinic_emr`

### Issue: "Page Not Found"
**Solution:**
1. Check APP_URL in config.php
2. Verify Apache rewrite module is enabled
3. Check folder path matches URL

### Issue: White Screen of Death
**Solution:**
1. Enable error display temporarily: 
   ```php
   ini_set('display_errors', 1);
   ```
2. Check PHP error logs
3. Verify all required classes exist

### Issue: Cannot Login
**Solution:**
1. Verify users table has data
2. Check password hashing:
   ```php
   $hash = '$2y$10$...';
   if (password_verify('admin123', $hash)) echo 'OK';
   ```
3. Verify role_id connections

### Issue: Migrations Not Running
**Solution:**
1. Check file permissions
2. Verify database has CREATE TABLE privilege
3. Check for SQL errors: `mysql > SHOW ENGINE INNODB STATUS;`

### Issue: Slow Performance
**Solution:**
1. Add indexes:
   ```sql
   CREATE INDEX idx_patient_id ON invoices(patient_id);
   CREATE INDEX idx_visit_date ON patient_visits(visit_date);
   ```
2. Enable query caching
3. Optimize images and assets

### Issue: File Upload Errors
**Solution:**
1. Check `upload_max_filesize` in php.ini
2. Verify `/uploads` folder exists and is writable
3. Check available disk space

---

## Performance Optimization

### Enable Caching
```php
// config/config.php
define('ENABLE_CACHE', true);
define('CACHE_LIFETIME', 3600);  // 1 hour
```

### Database Optimization
```sql
-- Optimize all tables
OPTIMIZE TABLE patients;
OPTIMIZE TABLE invoices;
OPTIMIZE TABLE patient_visits;

-- Analyze tables
ANALYZE TABLE patients;
ANALYZE TABLE invoices;
```

### Apache Optimization
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

---

## Documentation Files

- **FEATURES_DOCUMENTATION.md** - Complete features guide
- **SETUP_GUIDE.md** - This file
- **README.md** - Project overview
- **Database ERD** - Schema diagram
- **API Documentation** - API endpoints (coming soon)

---

## Support & Contact

For issues, questions, or suggestions:
1. Check FEATURES_DOCUMENTATION.md
2. Review logs in `/logs/` directory
3. Contact development team
4. Create GitHub issue

---

## Changelog

**v2.0.0** ✨ (Current)
- ✅ Complete billing system
- ✅ Inventory management
- ✅ Appointment scheduling
- ✅ Analytics dashboard
- ✅ Enhanced security
- ✅ New admin pages
- ✅ Public appointment booking

**v1.0.0**
- Initial release with core features

---

**Last Updated:** March 2024
**Status:** Production Ready ✓
