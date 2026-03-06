<?php
/**
 * AJAX Patient Search
 */

// Enable error reporting
ini_set('display_errors', 0); // Disable for AJAX
error_reporting(E_ALL);

header('Content-Type: application/json');

// Load dependencies
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Patient.php';

// Check authentication
if (!User::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get search term
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $patient = new Patient();
    $results = $patient->search($term, 10);
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}