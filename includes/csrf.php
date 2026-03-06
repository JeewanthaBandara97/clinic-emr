<?php
/**
 * CSRF Protection Functions
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF hidden input field
 */
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check CSRF token and die if invalid
 */
function checkCSRF(): void {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
}