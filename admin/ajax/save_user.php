<?php
/**
 * AJAX: Save user (Add/Edit)
 * Handles doctors (with doctor_details), assistants
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Common fields
$userId    = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$role      = trim($_POST['role'] ?? '');
$fullName  = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';
$isActive  = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Doctor-specific fields
$specialization    = trim($_POST['specialization'] ?? '');
$qualification     = trim($_POST['qualification'] ?? '');
$licenseNumber     = trim($_POST['license_number'] ?? '');
$experienceYears   = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
$consultationFee   = isset($_POST['consultation_fee']) ? (float)$_POST['consultation_fee'] : 0;
$bio               = trim($_POST['bio'] ?? '');
$availableDays     = isset($_POST['available_days']) ? implode(',', $_POST['available_days']) : 'Mon,Tue,Wed,Thu,Fri';
$availableTimeStart = trim($_POST['available_time_start'] ?? '08:00');
$availableTimeEnd   = trim($_POST['available_time_end'] ?? '17:00');

// Role validation
$roleMap = [
    'doctor'    => ROLE_DOCTOR,
    'assistant' => ROLE_ASSISTANT
];

if (!isset($roleMap[$role])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
    exit;
}

$roleId = $roleMap[$role];
$roleLabel = ucfirst($role);

// Common validation
if ($fullName === '' || $username === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and username are required.']);
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'A valid email is required.']);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores.']);
    exit;
}

// Doctor-specific validation
if ($role === 'doctor') {
    if ($specialization === '') {
        echo json_encode(['success' => false, 'message' => 'Specialization is required for doctors.']);
        exit;
    }
    if ($qualification === '') {
        echo json_encode(['success' => false, 'message' => 'Qualification is required for doctors.']);
        exit;
    }
}

try {
    $user = new User();
    $db = Database::getInstance();
    
    // Check duplicates
    if ($user->usernameExists($username, $userId > 0 ? $userId : null)) {
        echo json_encode(['success' => false, 'message' => 'Username already exists.']);
        exit;
    }
    
    if ($user->emailExists($email, $userId > 0 ? $userId : null)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }
    
    // User data array
    $userData = [
        'full_name' => $fullName,
        'username'  => $username,
        'email'     => $email,
        'phone'     => $phone,
        'role_id'   => $roleId,
        'is_active' => $isActive
    ];
    
    // Doctor details array
    $doctorData = [
        'specialization'       => $specialization,
        'qualification'        => $qualification,
        'license_number'       => $licenseNumber,
        'experience_years'     => $experienceYears,
        'consultation_fee'     => $consultationFee,
        'bio'                  => $bio,
        'available_days'       => $availableDays,
        'available_time_start' => $availableTimeStart,
        'available_time_end'   => $availableTimeEnd
    ];
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        if ($userId > 0) {
            // ===== UPDATE =====
            $existing = $user->getUserByIdAndRole($userId, $roleId);
            if (!$existing) {
                $db->rollback();
                echo json_encode(['success' => false, 'message' => $roleLabel . ' not found.']);
                exit;
            }
            
            if ($password !== '') {
                if (strlen($password) < 6) {
                    $db->rollback();
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                    exit;
                }
                $userData['password'] = $password;
                $user->updateUserWithPassword($userId, $userData);
            } else {
                $user->updateUser($userId, $userData);
            }
            
            // Save doctor details if doctor role
            if ($role === 'doctor') {
                $user->saveDoctorDetails($userId, $doctorData);
            }
            
            $db->commit();
            $user->logActivity('Update', 'users', $userId, $roleLabel . ' updated: ' . $fullName);
            echo json_encode(['success' => true, 'message' => $roleLabel . ' updated successfully.']);
            
        } else {
            // ===== CREATE =====
            if ($password === '' || strlen($password) < 6) {
                $db->rollback();
                echo json_encode(['success' => false, 'message' => 'Password is required (minimum 6 characters).']);
                exit;
            }
            
            $userData['password'] = $password;
            $newId = $user->createUser($userData);
            
            // Save doctor details if doctor role
            if ($role === 'doctor' && $newId > 0) {
                $user->saveDoctorDetails($newId, $doctorData);
            }
            
            $db->commit();
            $user->logActivity('Create', 'users', $newId, $roleLabel . ' created: ' . $fullName);
            echo json_encode(['success' => true, 'message' => $roleLabel . ' added successfully.']);
        }
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}