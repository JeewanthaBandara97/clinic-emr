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
    
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Function to require specific role
function requireRole(int $roleId, string $redirectUrl = null): void {
    if (!User::hasRole($roleId)) {
        $redirect = $redirectUrl ?? APP_URL . '/index.php';
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: ' . $redirect);
        exit;
    }
}

// Function to require admin role
function requireAdmin(): void {
    if (User::isDoctor()) {
        requireRole(ROLE_ADMIN, APP_URL . '/doctor/index.php');
    } elseif (User::isAssistant()) {
        requireRole(ROLE_ADMIN, APP_URL . '/assistant/index.php');
    } else {
        requireRole(ROLE_ADMIN, APP_URL . '/index.php');
    }
}

// Function to require assistant role
function requireAssistant(): void {
    if (User::isAdmin()) {
        // Admin can access assistant pages
        return;
    }
    requireRole(ROLE_ASSISTANT, APP_URL . '/doctor/index.php');
}

// Function to require doctor role
function requireDoctor(): void {
    if (User::isAdmin()) {
        // Admin can access doctor pages
        return;
    }
    requireRole(ROLE_DOCTOR, APP_URL . '/assistant/index.php');
}

/**
 * Check if current user can access admin area
 * Returns true without redirecting (useful for conditional UI)
 */
function canAccessAdmin(): bool {
    return User::isAdmin();
}

/**
 * Get the correct dashboard URL for the current user's role
 * Useful for "Back to Dashboard" links
 */
function getDashboardUrl(): string {
    if (User::isAdmin()) {
        return APP_URL . '/admin/index.php';
    } elseif (User::isDoctor()) {
        return APP_URL . '/doctor/index.php';
    } else {
        return APP_URL . '/assistant/index.php';
    }
}