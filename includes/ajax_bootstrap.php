<?php
// Common AJAX bootstrap for JSON endpoints
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth.php';
requireDoctor();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

function ajax_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function ajax_error($message, $code = 400) {
    ajax_response(['success' => false, 'error' => $message], $code);
}

// For POST requests, ensure CSRF token is present and valid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('checkCSRF')) {
        require_once __DIR__ . '/csrf.php';
    }
    try {
        checkCSRF();
    } catch (Exception $e) {
        ajax_error('Invalid CSRF token', 403);
    }
}

// Provide a DB instance for endpoints
$db = Database::getInstance();
