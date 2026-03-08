# Clinic EMR System

A complete Electronic Medical Record (EMR) web application designed for small clinics and healthcare facilities. This system provides a comprehensive solution for managing patient records, medical visits, prescriptions, and test requests with role-based access control.

**Version**: 1.0.0  
**Author**: Clinic EMR Development Team  
**License**: MIT

---

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [User Roles & Permissions](#user-roles--permissions)
- [Project Structure](#project-structure)
- [Default Credentials](#default-credentials)
- [Usage Guide](#usage-guide)
- [API Endpoints](#api-endpoints)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

---

## ✨ Features

### For Clinic Assistants
- **Session Management**: Create and manage daily clinic sessions
- **Patient Registration**: Register new patients with comprehensive medical information
- **Queue Management**: Add patients to queue and manage patient flow
- **Daily Dashboard**: View today's registered patients and sessions
- **Patient Search**: Quick search functionality for existing patients

### For Doctors
- **Patient Queue**: View and manage patients waiting for consultation
- **Patient Search**: Advanced search to find patients quickly
- **Patient Profile**: View complete patient medical history and demographics
- **Medical Records**: Create and manage patient visits with:
  - Vital signs recording (BP, Temperature, Pulse, Weight, Height)
  - Symptoms and diagnosis documentation
  - Test requests (Blood Test, Urine Test, X-Ray, ECG, Ultrasound, etc.)
  - Prescription creation with medicine schedules
- **Print Functionality**: Generate and print prescriptions in A4 format
- **Medical History**: Quick access to patient's past visits and treatments

### For Administrators
- **User Management**: Create, edit, and manage user accounts
- **Doctor Management**: Manage doctor profiles and credentials
- **Assistant Management**: Manage clinic assistant accounts
- **User Status Control**: Enable/disable user accounts
- **System Configuration**: Manage clinic information and settings

### General Features
- **Role-Based Access Control**: Different interfaces for different user roles
- **Secure Authentication**: Password hashing and session management
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Data Security**: CSRF protection and input validation
- **Audit Trail**: Track user actions and modifications

---

## 🛠️ Technology Stack

| Component | Technology |
|-----------|-----------|
| **Frontend** | HTML5, CSS3, Bootstrap 5, JavaScript (ES6+) |
| **Backend** | PHP 7.4+ (Procedural with Class-based architecture) |
| **Database** | MySQL 5.7+ |
| **Web Server** | Apache with mod_rewrite or Nginx |
| **Styling** | Bootstrap 5, Custom CSS |
| **Icons** | Bootstrap Icons, Font Awesome |
| **Authentication** | Password bcrypt hashing, PHP Sessions |

---

## 📦 Installation

### Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache (with mod_rewrite enabled) or Nginx
- **Composer** (optional, for package management)
- **Git** (optional, for version control)

### Prerequisites Check

Before installation, verify these requirements:

```bash
# Check PHP version
php -v

# Check if mysqli extension is installed
php -m | grep mysqli

# Check Apache modules (if on Apache)
apache2ctl -M | grep rewrite
```

### Step-by-Step Installation

#### 1. **Clone or Download the Project**

```bash
# Using Git
git clone [repository-url] clinic-emr
cd clinic-emr

# Or manually extract the ZIP file to your web root
# Example for XAMPP: C:\xampp\htdocs\clinic-emr
```

#### 2. **Configure Database Connection**

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_emr');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

#### 3. **Create Database**

```bash
# Using MySQL command line
mysql -u root -p < database/clinic_emr.sql

# Or using phpMyAdmin:
# 1. Open phpMyAdmin (http://localhost/phpmyadmin)
# 2. Create new database: clinic_emr
# 3. Import database/clinic_emr.sql file
```

#### 4. **Update Application Configuration**

Edit `config/config.php`:

```php
define('APP_URL', 'http://localhost/clinic-emr'); // Your application URL
define('CLINIC_NAME', 'Your Clinic Name');
define('CLINIC_ADDRESS', 'Your Clinic Address');
define('CLINIC_PHONE', 'Your Phone');
define('CLINIC_EMAIL', 'your@email.com');
```

#### 5. **Set File Permissions (Linux/Mac)**

```bash
chmod -R 755 .
chmod -R 777 assets/  # If you need to upload files
```

#### 6. **Verify Installation**

Navigate to your application URL:
```
http://localhost/clinic-emr
```

You should see the login page.

---

## ⚙️ Configuration

### config/config.php

Main application configuration file:

```php
// Application Settings
define('APP_NAME', 'Clinic EMR System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/clinic-emr');

// Clinic Information (displayed in reports)
define('CLINIC_NAME', 'City Health Clinic');
define('CLINIC_ADDRESS', '123 Main Street');
define('CLINIC_PHONE', '+1 234 567 8900');
define('CLINIC_EMAIL', 'info@clinic.com');

// Session Timeout (in seconds)
define('SESSION_TIMEOUT', 3600); // 1 hour

// Password Requirements
define('MIN_PASSWORD_LENGTH', 8);

// Pagination
define('RECORDS_PER_PAGE', 10);
```

### config/database.php

Database connection settings:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_emr');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### Database Backup

To backup the database:

```bash
# Backup to file
mysqldump -u root -p clinic_emr > backup_clinic_emr.sql

# Restore from backup
mysql -u root -p clinic_emr < backup_clinic_emr.sql
```

---

## 🗄️ Database Setup

### Database Schema

The system uses a normalized MySQL database with the following main tables:

| Table | Purpose |
|-------|---------|
| `roles` | User roles (Assistant, Doctor, Admin) |
| `users` | System users and authentication |
| `patients` | Patient demographic and medical information |
| `clinic_sessions` | Daily clinic sessions |
| `session_patients` | Link patients to sessions |
| `patient_visits` | Patient visits/consultations |
| `patient_vital_signs` | Vital signs recorded during visits |
| `patient_tests` | Medical tests requested |
| `medicines` | Medicine database for prescriptions |
| `patient_prescriptions` | Medicine prescriptions |
| `patient_prescription_medicines` | Individual medicines in prescriptions |

### Initialize Database

The database is automatically created when you import `database/clinic_emr.sql`. The schema includes:

- All necessary tables with proper relationships
- Indexes for performance optimization
- Default roles (Assistant, Doctor, Admin)
- Referential integrity constraints

---

## 👥 User Roles & Permissions

### 1. **Assistant Role (role_id: 3)**

**Permissions**:
- Create and manage clinic sessions
- Register new patients
- Add patients to session queue
- View today's patient list
- Search for existing patients

**Access Path**: `/assistant/`

**Key Modules**:
- `assistant/create-session.php` - Create clinic sessions
- `assistant/register-patient.php` - Register new patients
- `assistant/add-to-queue.php` - Add patients to queue
- `assistant/todays-patients.php` - View daily patient list

### 2. **Doctor Role (role_id: 2)**

**Permissions**:
- View patient queue
- Search and view patient profiles
- Create patient visits
- Record vital signs
- Request medical tests
- Create prescriptions
- Print prescriptions
- View patient medical history

**Access Path**: `/doctor/`

**Key Modules**:
- `doctor/patient-queue.php` - View waiting patients
- `doctor/create-visit.php` - Create patient visits
- `doctor/add-prescription.php` - Add prescriptions
- `doctor/print-prescription.php` - Print prescriptions
- `doctor/patient-history.php` - View patient history

### 3. **Admin Role (role_id: 1)**

**Permissions**:
- Manage all users (create, edit, delete)
- Manage doctor profiles
- Manage assistant accounts
- View system activity
- Configure system settings
- Manage user roles

**Access Path**: `/admin/`

**Key Modules**:
- `admin/doctors.php` - Manage doctors
- `admin/assistants.php` - Manage assistants
- `admin/login.php` - Admin login

---

## 🔐 Default Credentials

**Important**: After first login, change all default passwords immediately.

### Initial Setup

The database includes default user accounts. Contact your system administrator for initial credentials.

**Recommended First Steps**:

1. Login as Admin
2. Create your own admin account
3. Disable default admin account
4. Create Doctor and Assistant accounts as needed
5. Set up clinic information in settings

### Create New Users

**As Admin**:
1. Go to `/admin/doctors.php` or `/admin/assistants.php`
2. Click "Add New [Doctor/Assistant]"
3. Fill in user information
4. System will generate temporary password (or allow custom password)
5. User can login and change password

### Password Requirements

- Minimum 8 characters
- Should contain uppercase and lowercase letters
- Should contain numbers and special characters
- Change default passwords immediately

---

## 📁 Project Structure

```
clinic-emr/
├── admin/                          # Admin panel
│   ├── ajax/                       # AJAX endpoints for admin
│   │   ├── get_doctor.php
│   │   ├── get_assistant.php
│   │   ├── save_doctor.php
│   │   └── save_assistant.php
│   ├── includes/
│   │   └── admin_auth.php         # Admin authentication
│   ├── index.php                  # Admin dashboard
│   ├── doctors.php                # Manage doctors
│   ├── assistants.php             # Manage assistants
│   ├── login.php                  # Admin login
│   └── logout.php
│
├── assistant/                      # Assistant panel
│   ├── ajax/                       # AJAX endpoints
│   │   ├── get-sessions.php
│   │   ├── search-patient.php
│   │   └── get-subcategories.php
│   ├── index.php                  # Assistant dashboard
│   ├── create-session.php         # Create clinic session
│   ├── register-patient.php       # Register new patient
│   ├── add-to-queue.php           # Add patient to queue
│   ├── todays-patients.php        # View today's patients
│   ├── sessions.php               # View sessions
│   ├── session-queue.php          # Manage session queue
│   ├── medicines.php              # View medicines
│   ├── add-medicine.php           # Add medicine
│   └── medicine-lookups.php       # Medicine lookup
│
├── doctor/                         # Doctor panel
│   ├── ajax/                       # AJAX endpoints
│   │   ├── save-medicines.php
│   │   ├── save-tests.php
│   │   └── save-vital-signs.php
│   ├── index.php                  # Doctor dashboard
│   ├── patient-queue.php          # View queue
│   ├── patient-profile.php        # View patient profile
│   ├── patient-history.php        # View patient history
│   ├── search-patient.php         # Search patients
│   ├── create-visit.php           # Create visit
│   ├── add-prescription.php       # Add prescription
│   └── print-prescription.php     # Print prescription
│
├── auth/                           # Authentication
│   ├── login.php                  # User login
│   ├── logout.php
│   └── process-login.php          # Login processing
│
├── classes/                        # PHP Classes
│   ├── Database.php               # Database connection
│   ├── User.php                   # User management
│   ├── Patient.php                # Patient management
│   ├── Visit.php                  # Visit management
│   ├── Session.php                # Session management
│   ├── Prescription.php           # Prescription management
│   ├── Medicine.php               # Medicine management
│   ├── Test.php                   # Test management
│   └── User1.php                  # User utility
│
├── config/                         # Configuration
│   ├── config.php                 # Main configuration
│   └── database.php               # Database configuration
│
├── database/                       # Database files
│   └── clinic_emr.sql             # Database schema
│
├── includes/                       # Common includes
│   ├── auth.php                   # Authentication check
│   ├── auth1.php
│   ├── auth_check.php
│   ├── header.php                 # Page header
│   ├── footer.php                 # Page footer
│   ├── sidebar.php                # Sidebar navigation
│   ├── functions.php              # Helper functions
│   └── csrf.php                   # CSRF protection
│
├── assets/                         # Static assets
│   ├── css/
│   │   ├── style.css              # Main stylesheet
│   │   ├── custom.css             # Custom styles
│   │   └── print.css              # Print styles
│   ├── js/
│   │   └── main.js                # Main JavaScript
│   └── images/
│
├── index.php                       # Application entry point
├── debug.php                       # Debug utilities
├── test-pages.php                # Testing file
├── test-assistant.php            # Assistant testing
├── README.md                      # This file
└── .htaccess                      # Apache rewrite rules
```

---

## 🚀 Usage Guide

### For Clinic Assistants

#### Creating a Clinic Session

1. Login with assistant credentials
2. Navigate to **Create Session**
3. Set session date and time
4. Add session notes (optional)
5. Click **Create Session**

#### Registering a New Patient

1. Go to **Register Patient**
2. Fill in patient information:
   - Personal details (Name, DOB, Gender)
   - Contact information (Phone, Address)
   - Medical information (Blood Type, Allergies, Chronic Diseases)
   - Emergency contact
3. Click **Register**
4. Patient receives a unique ID

#### Adding Patient to Queue

1. Go to **Add to Queue**
2. Select clinic session
3. Search and select patient
4. Click **Add to Queue**

#### Viewing Today's Patients

1. Navigate to **Today's Patients**
2. View all registered patients
3. See their queue status

---

### For Doctors

#### Viewing Patient Queue

1. Login as doctor
2. Go to **Patient Queue**
3. View waiting patients in order
4. Click on patient name to view profile

#### Creating a Patient Visit

1. Select patient from queue
2. Click **Create Visit**
3. Record vital signs:
   - Blood Pressure
   - Temperature
   - Pulse Rate
   - Weight and Height
4. Document symptoms and diagnosis
5. Request tests if needed
6. Add prescription if needed
7. Click **Save Visit**

#### Adding Prescription

1. In visit form, go to **Prescriptions** tab
2. Search and add medicines
3. Set dosage and duration
4. Click **Save Prescription**

#### Printing Prescription

1. Find saved prescription
2. Click **Print**
3. A4 format prescription will open
4. Print using browser print function

---

### For Administrators

#### Managing Users

1. Login as admin
2. Navigate to **Doctor Management** or **Assistant Management**
3. View list of users
4. Click **Add New** to create user
5. Edit or delete users as needed

#### Changing System Settings

1. Edit `config/config.php` to change:
   - Clinic name and address
   - Session timeout
   - Password requirements
   - Records per page

---

## 🔌 API Endpoints

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/process-login.php` | Process login credentials |
| POST | `/auth/logout.php` | Logout user |

### Assistant AJAX Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/assistant/ajax/get-sessions.php` | POST | Get clinic sessions |
| `/assistant/ajax/search-patient.php` | POST | Search patients |
| `/assistant/ajax/get-subcategories.php` | POST | Get medicine subcategories |

### Doctor AJAX Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/doctor/ajax/save-medicines.php` | POST | Save prescription medicines |
| `/doctor/ajax/save-tests.php` | POST | Save requested tests |
| `/doctor/ajax/save-vital-signs.php` | POST | Save vital signs |

### Admin AJAX Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/admin/ajax/get_doctor.php` | POST | Get doctor details |
| `/admin/ajax/save_doctor.php` | POST | Save doctor information |
| `/admin/ajax/get_assistant.php` | POST | Get assistant details |
| `/admin/ajax/save_assistant.php` | POST | Save assistant information |
| `/admin/ajax/update_status.php` | POST | Update user status |

### Response Format

All AJAX endpoints return JSON:

```json
{
  "success": true/false,
  "message": "Response message",
  "data": {}
}
```

---

## 🔒 Security Features

### Implemented Security Measures

1. **Password Security**
   - Bcrypt password hashing (PHP password_hash)
   - Minimum 8-character passwords
   - Password fields never logged

2. **Session Management**
   - PHP sessions with secure cookies
   - HTTPOnly cookie flag enabled
   - Session timeout after inactivity
   - Unique session tokens

3. **CSRF Protection**
   - CSRF tokens on all forms
   - Token validation on form submission

4. **Input Validation**
   - Input sanitization in all forms
   - SQL injection prevention via prepared statements
   - XSS prevention through output escaping

5. **Access Control**
   - Role-based access control (RBAC)
   - Authentication required for all pages
   - Authorization checks per role

6. **Data Protection**
   - Medical records are confidential
   - Audit trail of data access
   - HTTPS recommended for production

### Security Best Practices

For production deployment:

```php
// In config.php
// Enable HTTPS
define('FORCE_HTTPS', true);

// Secure session cookies
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

// Disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

---

## 🐛 Troubleshooting

### Database Connection Issues

**Error**: "SQLSTATE[HY000]: General error: 1 no such table"

**Solution**:
```bash
# Verify database is imported
mysql -u root -p clinic_emr -e "SHOW TABLES;"

# Reimport database if needed
mysql -u root -p clinic_emr < database/clinic_emr.sql
```

### Login Issues

**Error**: "Invalid username or password"

**Solutions**:
1. Verify credentials are correct
2. Check if user account is active (is_active = 1)
3. Reset password through admin panel
4. Check database connection

### Session Timeout

**Increase session timeout** in `config/config.php`:

```php
define('SESSION_TIMEOUT', 7200); // 2 hours instead of 1
```

### Page Not Found (404)

**For Apache**:
1. Ensure `.htaccess` file exists in root
2. Verify mod_rewrite is enabled
3. Check AllowOverride is set to All

**For Nginx**:
Create this configuration:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Permission Denied Errors

```bash
# Fix file permissions
chmod -R 755 /path/to/clinic-emr
chmod -R 777 /path/to/clinic-emr/assets
```

### Blank Pages

**Check PHP errors**:
1. Edit `config/config.php` and set `display_errors` to 1
2. Check browser console for JavaScript errors
3. Check server error logs

### Mail/Email Not Working

Email functionality requires:
1. PHP mail() function enabled
2. Server SMTP configuration
3. Verify clinic email in config.php

---

## 📊 Database Maintenance

### Regular Backups

**Weekly backup script**:

```bash
#!/bin/bash
DATE=$(date +%Y-%m-%d_%H-%M-%S)
mysqldump -u root -p clinic_emr > backups/clinic_emr_$DATE.sql
gzip backups/clinic_emr_$DATE.sql
```

### Database Optimization

```sql
-- Optimize all tables
OPTIMIZE TABLE roles;
OPTIMIZE TABLE users;
OPTIMIZE TABLE patients;
OPTIMIZE TABLE clinic_sessions;
OPTIMIZE TABLE visits;
OPTIMIZE TABLE prescriptions;
```

### Checking Database Health

```sql
-- Check table status
SHOW TABLE STATUS FROM clinic_emr;

-- Check for corrupted tables
CHECK TABLE patients;
CHECK TABLE visits;
CHECK TABLE prescriptions;
```

---

## 🔧 Maintenance & Updates

### System Updates

1. **Backup database** before updates
2. **Backup files** before updates
3. **Test updates** in development first
4. **Disable user access** during updates if needed
5. **Verify functionality** after updates

### Clearing Cache

```php
// Clear session cache
session_destroy();

// Clear any output buffering
ob_clean();
```

### Log Files

Important logs to monitor:
- `/var/log/apache2/error.log` (Apache errors)
- `/var/log/mysql/error.log` (MySQL errors)
- Application logs in your configured error_log path

---

## 📞 Support

### Getting Help

1. **Check Documentation**: Review this README first
2. **Check Troubleshooting**: Look for your issue above
3. **Review Logs**: Check error logs for details
4. **Check Database**: Verify database integrity

### Common Questions

**Q: How do I reset a user password?**
A: Login as admin, go to user management, click edit, and set new password.

**Q: Can I backup the database?**
A: Yes, use `mysqldump` or phpMyAdmin to export the database.

**Q: How do I add new medicines?**
A: Go to Assistant > Manage Medicines or use the medicine lookup system.

**Q: Can multiple doctors work simultaneously?**
A: Yes, each login is independent. Multiple users can login simultaneously.

**Q: Is patient data encrypted?**
A: Passwords are hashed. For full encryption, enable HTTPS and set secure database connections.

---

## 📝 License

This project is licensed under the MIT License - see LICENSE file for details.

---

## 👨‍💻 Development Team

**Clinic EMR System v1.0.0**

Developed for small clinic and healthcare facility management.

---

## 🔄 Changelog

### Version 1.0.0 (Current)
- Initial release
- User authentication and role-based access
- Patient registration and management
- Clinic session management
- Visit and prescription management
- Print functionality for prescriptions
- Admin user management

---

## 📌 Important Notes

1. **Backup Regularly**: Database should be backed up daily
2. **Update Passwords**: Change default credentials immediately
3. **Use HTTPS**: Use HTTPS in production for security
4. **Monitor Logs**: Regularly check server logs
5. **Database Maintenance**: Run optimization queries monthly
6. **User Training**: Train staff on proper system usage
7. **Data Privacy**: Comply with medical data protection regulations

---

**Last Updated**: March 8, 2026  
**For issues or suggestions**: Contact your system administrator