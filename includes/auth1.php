<?php
/**
 * Authentication Middleware
 * Include this file at the top of protected pages
 */

// Include config FIRST
require_once __DIR__ . '/../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

  
require_once __DIR__ . '/../classes/User.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    // Store intended URL for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

// Function to require specific role
function requireRole(int $roleId, string $redirectUrl = null): void {
    if (!User::hasRole($roleId)) {
        $redirect = $redirectUrl ?? APP_URL . '/auth/login.php';
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: ' . $redirect);
        exit;
    }
}

// Function to require assistant role
function requireAssistant(): void {
    requireRole(ROLE_ASSISTANT, APP_URL . '/doctor/index.php');
}

// Function to require doctor role
function requireDoctor(): void {
    requireRole(ROLE_DOCTOR, APP_URL . '/assistant/index.php');
}
