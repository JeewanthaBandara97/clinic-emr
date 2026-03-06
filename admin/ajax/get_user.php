<?php
/**
 * AJAX: Get user data for editing
 * Supports doctor details when details=1
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../classes/User.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$role = trim($_GET['role'] ?? '');
$includeDetails = isset($_GET['details']) && $_GET['details'] == 1;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

$roleMap = [
    'doctor'    => ROLE_DOCTOR,
    'assistant' => ROLE_ASSISTANT
];

if (!isset($roleMap[$role])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
    exit;
}

try {
    $user = new User();
    
    if ($role === 'doctor' && $includeDetails) {
        // Get doctor with full details from doctor_details table
        $data = $user->getDoctorDetails($userId);
    } else {
        $data = $user->getUserByIdAndRole($userId, $roleMap[$role]);
    }
    
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => ucfirst($role) . ' not found.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}