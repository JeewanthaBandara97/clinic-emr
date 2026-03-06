<?php
/**
 * AJAX: Toggle user active/inactive status
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../classes/User.php';

// Auth guard
if (!User::isLoggedIn() || !User::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : -1;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

if ($status !== 0 && $status !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Prevent self-deactivation
if ($userId === (int)User::getUserId()) {
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
    exit;
}

try {
    $user = new User();
    
    // Verify user exists and is not an admin
    $targetUser = $user->getUserById($userId);
    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    
    if ((int)$targetUser['role_id'] === ROLE_ADMIN) {
        echo json_encode(['success' => false, 'message' => 'Cannot change admin account status.']);
        exit;
    }
    
    $result = $user->updateUserStatus($userId, $status);
    
    if ($result) {
        $action = $status === 1 ? 'activated' : 'deactivated';
        $user->logActivity('Update', 'users', $userId, 'User ' . $action . ': ' . $targetUser['full_name']);
        echo json_encode(['success' => true, 'message' => 'User ' . $action . ' successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}