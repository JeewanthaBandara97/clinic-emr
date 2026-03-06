<?php
/**
 * Logout Handler
 * Clinic EMR System
 */

// Include config FIRST
require_once __DIR__ . '/../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/User.php';

// Log the activity before destroying session
if (User::isLoggedIn()) {
    $user = new User();
    $user->logActivity('Logout', 'users', User::getUserId(), 'User logged out');
}

// Logout and redirect
User::logout();

header('Location: ' . APP_URL . '/auth/login.php');
exit;