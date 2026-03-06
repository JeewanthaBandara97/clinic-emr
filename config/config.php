<?php
/**
 * Application Configuration
 * Clinic EMR System
 */

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration - Only set if session is NOT active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Timezone
date_default_timezone_set('Asia/Colombo');

// Application Settings
define('APP_NAME', 'Clinic EMR System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/MY/EMR/clinic-emr'); // Update this to your path

// Clinic Information
define('CLINIC_NAME', 'City Health Clinic');
define('CLINIC_ADDRESS', '123 Main Street, City, Country');
define('CLINIC_PHONE', '+1 234 567 8900');
define('CLINIC_EMAIL', 'info@cityhealthclinic.com');

// File Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('CLASSES_PATH', ROOT_PATH . 'classes/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', APP_URL . '/assets/');

// Pagination Settings
define('RECORDS_PER_PAGE', 10);

// Session Timeout (in seconds)
define('SESSION_TIMEOUT', 3600); // 1 hour

// Password Requirements
define('MIN_PASSWORD_LENGTH', 8);

// Role IDs
define('ROLE_ASSISTANT', 1);
define('ROLE_DOCTOR', 2);