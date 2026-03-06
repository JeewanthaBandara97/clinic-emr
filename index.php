<?php
/**
 * Main Entry Point
 * Clinic EMR System
 */

// Include config FIRST
require_once __DIR__ . '/config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/classes/User.php';

// Redirect based on login status and role
if (User::isLoggedIn()) {
    if (User::isDoctor()) {
        header('Location: ' . APP_URL . '/doctor/index.php');
    } else {
        header('Location: ' . APP_URL . '/assistant/index.php');
    }
} else {
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit;